<?php

namespace App\Filament\Widgets;

use App\Models\Mood;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class DailyMoodWidget extends Widget
{
    protected string $view = 'filament.widgets.daily-mood-widget';

    protected static ?int $sort = 2;

    public ?Mood $today = null;

    public bool $isSuperAdmin = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'sm' => 12,
        'xl' => 6,
    ];

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->isSuperAdmin = $user->hasRole('super_admin');

        if ($this->isSuperAdmin) {
            return;
        }

        $this->today = Mood::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    #[On('daily-mood-updated')]
    #[On('mood-saved')]
    public function reloadToday(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->today = null;

            return;
        }

        $this->isSuperAdmin = $user->hasRole('super_admin');

        if ($this->isSuperAdmin) {
            return;
        }

        $this->today = Mood::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();
    }
}
