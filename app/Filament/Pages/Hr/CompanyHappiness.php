<?php

namespace App\Filament\Pages\Hr;

use App\Filament\Widgets\AiSuggestionsPanel;
use App\Filament\Widgets\MoodFilteredPieChart;
use App\Filament\Widgets\MoodMonthlyDistributionChart;
use App\Filament\Widgets\MoodTodayPieChart;
use App\Models\HappinessSuggestion;
use App\Models\Mood;
use App\Services\AiMessageService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompanyHappiness extends Page implements HasTable
{
    use HasPageShield {
        canAccess as protected shieldCanAccess;
    }
    use InteractsWithTable {
        removeTableFilters as protected baseRemoveTableFilters;
    }

    public int $filtersTick = 0;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-face-smile';

    protected static string|UnitEnum|null $navigationGroup = 'Recursos Humanos';

    protected static ?string $slug = 'hr/company-happiness';

    protected static ?string $navigationLabel = 'Felicidad Organizacional';

    protected static ?string $title = 'Felicidad Organizacional';

    protected string $view = 'filament.pages.hr.company-happiness';

    public static function canAccess(): bool
    {
        if (! tenant()?->hasEntity(class_basename(static::class))) {
            return false;
        }

        return static::shieldCanAccess();
    }

    protected static function getPagePermission(): ?string
    {
        return 'View:'.class_basename(static::class);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->ensureYearSuggestion();
    }

    public function getHeading(): ?string
    {
        return static::$title;
    }

    public function removeTableFilters(): void
    {
        $this->baseRemoveTableFilters();
        $this->filtersTick++;
        $this->dispatch(
            'filters-changed',
            filters: $this->currentPageFilters(),
        )->to(MoodFilteredPieChart::class);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MoodMonthlyDistributionChart::class,
            AiSuggestionsPanel::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getSideWidgets(): array
    {
        return [
            MoodTodayPieChart::class,
            MoodFilteredPieChart::class,
        ];
    }

    public function currentPageFilters(): array
    {
        $data = $this->getTableFilterFormState('periodo') ?? [];

        return [
            'year' => $data['year'] ?? now()->year,
            'month' => $data['month'] ?? (string) now()->format('n'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = Mood::query()
                    ->with('user.department')
                    ->whereHas('user', function (Builder $userQuery): void {
                        $userQuery->whereDoesntHave('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'super_admin'));
                    });
                $data = $this->getTableFilterFormState('periodo') ?? [];
                if (! empty($data['year'])) {
                    $query->whereYear('date', (int) $data['year']);
                }
                if (! empty($data['month'])) {
                    $query->whereMonth('date', (int) $data['month']);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Empleado')
                    ->searchable(),
                TextColumn::make('user.department.name')
                    ->label('Departamento')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('Y-m-d'),
                TextColumn::make('created_at')
                    ->label('Hora')
                    ->time('H:i:s'),
                TextColumn::make('mood')
                    ->label('Estado de animo')
                    ->formatStateUsing(fn ($state) => $this->labelForMood($state)),
            ])
            ->filters([
                Filter::make('periodo')
                    ->label('Periodo')
                    ->schema([
                        Select::make('year')
                            ->label('Ano')
                            ->live()
                            ->afterStateUpdated(function (): void {
                                $this->dispatch('$refresh');
                                $this->dispatch(
                                    'filters-changed',
                                    filters: $this->currentPageFilters(),
                                )->to(MoodFilteredPieChart::class);
                            })
                            ->options(fn () => Mood::query()
                                ->selectRaw('YEAR(date) as y')
                                ->distinct()
                                ->orderByDesc('y')
                                ->pluck('y', 'y')
                                ->mapWithKeys(fn ($v) => [(string) $v => (string) $v])
                                ->toArray()
                            )
                            ->default((string) now()->year),
                        Select::make('month')
                            ->label('Mes')
                            ->live()
                            ->default((string) now()->format('n'))
                            ->afterStateUpdated(function (): void {
                                $this->dispatch('$refresh');
                                $this->dispatch(
                                    'filters-changed',
                                    filters: $this->currentPageFilters(),
                                )->to(MoodFilteredPieChart::class);
                            })
                            ->options([
                                '1' => 'Enero',
                                '2' => 'Febrero',
                                '3' => 'Marzo',
                                '4' => 'Abril',
                                '5' => 'Mayo',
                                '6' => 'Junio',
                                '7' => 'Julio',
                                '8' => 'Agosto',
                                '9' => 'Septiembre',
                                '10' => 'Octubre',
                                '11' => 'Noviembre',
                                '12' => 'Diciembre',
                            ]),
                    ]),
            ])
            ->filtersTriggerAction(function (Action $action) use ($table) {
                return $action->extraModalFooterActions([
                    $table->getFiltersApplyAction()->close(),
                ]);
            })
            ->deferFilters(false)
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50]);
    }

    protected function ensureYearSuggestion(): void
    {
        $year = now()->year;
        $stats = $this->currentYearAggregates();
        $latest = HappinessSuggestion::whereYear('date', $year)
            ->orderByDesc('date')
            ->first();

        $latestDistribution = $latest?->context['distribution'] ?? null;
        $changed = $latestDistribution === null || $latestDistribution !== $stats['distribution'];

        if ($changed) {
            $ai = app(AiMessageService::class)->generateCompanySuggestions($stats);
            HappinessSuggestion::updateOrCreate(
                ['date' => now()->toDateString()],
                [
                    'requested_by' => auth()->id(),
                    'suggestion' => $ai['text'] ?? $ai['message'] ?? '',
                    'context' => $stats,
                    'model' => $ai['model'] ?? null,
                ]
            );
        }
    }

    protected function currentYearAggregates(): array
    {
        $year = now()->year;
        $counts = Mood::query()
            ->whereYear('date', $year)
            ->whereHas('user', function (Builder $userQuery): void {
                $userQuery->whereDoesntHave('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'super_admin'));
            })
            ->selectRaw('mood, COUNT(*) as c')
            ->groupBy('mood')
            ->pluck('c', 'mood')
            ->toArray();

        $total = array_sum($counts);

        return [
            'year' => $year,
            'distribution' => $counts,
            'total' => $total,
            'average_score' => $total > 0 ? Mood::whereYear('date', $year)
                ->whereHas('user', function (Builder $userQuery): void {
                    $userQuery->whereDoesntHave('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'super_admin'));
                })
                ->avg('score') : 0,
        ];
    }

    protected function labelForMood(?string $mood): string
    {
        return match ($mood) {
            'happy' => 'Feliz',
            'med_happy' => 'Med Feliz',
            'neutral' => 'Neutral',
            'med_sad' => 'Med Triste',
            'sad' => 'Triste',
            default => '—',
        };
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
