<?php

namespace App\CpanelPages;

use App\Mail\CpanelFileShareAckCodeMail;
use App\Models\CpanelFileShare;
use App\Models\User;
use App\Services\CPanelFilemanService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class CpanelFileManager extends Page implements HasTable
{
    use HasPageShield {
        canAccess as protected shieldCanAccess;
    }
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Mi Disco';

    protected static string|UnitEnum|null $navigationGroup = 'Mis Aplicaciones';

    protected static ?int $navigationSort = 1;

    protected string $view = 'cpanel-pages.file-manager';

    public const SHARED_PATH = 'Compartidos';

    public string $currentPath = '/';

    public string $userDir;

    public ?string $tenantDomain = null;

    public ?array $currentShareInfoFile = null;

    public ?string $currentAckShareId = null;

    public ?array $pendingAckRecord = null;

    public static function canAccess(): bool
    {
        if (! tenant()?->hasEntity(class_basename(static::class))) {
            return false;
        }

        $tenant = tenant();

        if (empty($tenant?->cpanel_host)
            || empty($tenant?->cpanel_user)
            || empty($tenant?->cpanel_token)
            || empty($tenant?->cpanel_password)) {
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

    public function getTitle(): string|Htmlable
    {
        return __('Mi Disco');
    }

    public function mount(CPanelFilemanService $cpanel): void
    {
        $tenantId = tenant()->id;
        $domain = tenant()->domains()->first()?->domain ?? $tenantId;
        $this->tenantDomain = $domain;

        $userDir = $cpanel->ensureUserDir($domain, Auth::id());
        $this->userDir = $userDir;
        $this->currentPath = $this->normalizePath($this->currentPath);

        $this->cleanupOldFiles();
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    private function getService(): CPanelFilemanService
    {
        return app(CPanelFilemanService::class);
    }

    private function cleanupOldFiles(): void
    {
        $tenantId = tenant()->id;
        $dir = storage_path("app/tenants/{$tenantId}/onlyoffice/cpanel");

        if (! is_dir($dir)) {
            return;
        }

        $cutoff = now()->subHour()->timestamp;
        $deleted = 0;

        foreach (glob("{$dir}/*") as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            logger()->info('CpanelFileManager: cleaned up old temp files', [
                'tenant' => $tenantId,
                'deleted' => $deleted,
            ]);
        }
    }

    private function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/'.trim($path, '/').'/';
    }

    private function currentDiskDir(): string
    {
        $path = $this->currentPath === '/'
            ? '/'
            : rtrim($this->currentPath, '/');

        return $this->userDir.$path;
    }

    private function cpanelDir(string $name = ''): string
    {
        $dir = $this->currentDiskDir();

        if ($name !== '') {
            $dir .= '/'.$name;
        }

        return $dir;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    protected function getHeaderActions(): array
    {
        $service = $this->getService();

        return [
            CreateAction::make('createFolder')
                ->label('Crear carpeta')
                ->icon('heroicon-o-folder-plus')
                ->successNotification(null)
                ->disabled(fn () => $this->currentPath === '/'.self::SHARED_PATH.'/')
                ->tooltip(fn () => $this->currentPath === '/'.self::SHARED_PATH.'/'
                    ? 'No se pueden crear carpetas en la sección compartida.'
                    : null)
                ->schema([
                    TextInput::make('folderName')->label('Nombre de la carpeta')->required(),
                ])
                ->action(function (array $data) use ($service) {
                    $name = trim($data['folderName']);
                    $dir = $this->currentDiskDir();

                    try {
                        $service->createFolder($dir, $name);

                        $this->resetTable();

                        Notification::make()->title('Carpeta creada')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al crear carpeta')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('createFile')
                ->label('Crear archivo')
                ->icon('heroicon-o-document-plus')
                ->disabled(fn () => $this->currentPath === '/'.self::SHARED_PATH.'/')
                ->tooltip(fn () => $this->currentPath === '/'.self::SHARED_PATH.'/'
                    ? 'No se pueden crear archivos en la sección compartida.'
                    : null)
                ->schema([
                    TextInput::make('fileName')
                        ->label('Nombre del archivo')
                        ->required(),
                    Select::make('fileType')
                        ->label('Tipo de archivo')
                        ->options([
                            'txt' => 'Texto (.txt)',
                            'xlsx' => 'Excel (.xlsx)',
                            'docx' => 'Word (.docx)',
                            'pptx' => 'PowerPoint (.pptx)',
                        ])
                        ->default('txt')
                        ->required(),
                ])
                ->action(function (array $data) use ($service) {
                    $name = trim($data['fileName']);
                    $type = $data['fileType'];
                    $fullName = $name.'.'.$type;
                    $dir = $this->currentDiskDir();

                    try {
                        $service->saveFileContent($dir, $fullName, '');

                        $this->resetTable();

                        Notification::make()->title('Archivo creado')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al crear archivo')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('uploadFile')
                ->label('Subir archivos')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->disabled(fn () => $this->currentPath === '/'.self::SHARED_PATH.'/')
                ->tooltip(fn () => $this->currentPath === '/'.self::SHARED_PATH.'/'
                    ? 'No se pueden subir archivos en la sección compartida.'
                    : null)
                ->schema([
                    FileUpload::make('files')
                        ->disk('public')
                        ->directory('tmp')
                        ->multiple()
                        ->preserveFilenames()
                        ->required()
                        ->maxSize(256000)
                        ->validationMessages([
                            'max.file' => 'El archivo supera el tamaño máximo permitido de 256 MB.',
                            'uploaded' => 'El archivo no pudo subirse. Verifica que no supere los 256 MB.',
                        ])
                        ->helperText('Tamaño máximo por archivo: 256 MB.')
                        ->label('Archivos'),
                ])
                ->action(function (array $data) use ($service) {
                    $disk = Storage::disk('public');
                    $dir = $this->currentDiskDir();

                    $uploaded = 0;
                    $skipped = 0;

                    logger()->info('Upload action started', [
                        'dir' => $dir,
                        'files_data' => $data['files'] ?? 'NO FILES KEY',
                    ]);

                    foreach ($data['files'] as $tmp) {
                        $name = basename($tmp);
                        $exists = $disk->exists($tmp);
                        $content = $disk->get($tmp);

                        logger()->info('Processing file', [
                            'tmp' => $tmp,
                            'name' => $name,
                            'disk_exists' => $exists,
                            'content_null' => $content === null,
                            'content_length' => $content !== null ? strlen($content) : 0,
                        ]);

                        if ($content === null) {
                            $skipped++;

                            continue;
                        }

                        try {
                            $service->uploadFile($dir, $name, $content);
                            $uploaded++;
                        } catch (\Exception $e) {
                            logger()->error('Error subiendo archivo a cPanel', [
                                'dir' => $dir,
                                'name' => $name,
                                'error' => $e->getMessage(),
                            ]);
                            $skipped++;
                        }

                        $disk->delete($tmp);
                    }

                    $this->resetTable();

                    if ($uploaded > 0 && $skipped === 0) {
                        Notification::make()
                            ->title('Subida completada')
                            ->body("{$uploaded} archivo(s) subido(s) correctamente.")
                            ->success()
                            ->send();
                    } elseif ($uploaded > 0 && $skipped > 0) {
                        Notification::make()
                            ->title('Subida parcial')
                            ->body("{$uploaded} subido(s), {$skipped} omitido(s). Algunos archivos no pudieron subirse.")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No se pudo subir ningún archivo')
                            ->body('Verifica que los archivos no superen el tamaño máximo de 256 MB.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $service = $this->getService();
        $dir = $this->currentDiskDir();
        $isSharedRoot = $this->currentPath === '/'.self::SHARED_PATH.'/';

        return $table
            ->records(function () use ($service, $dir, $isSharedRoot): Collection {
                $search = $this->getTableSearch();

                if ($search) {
                    $results = $isSharedRoot
                        ? $this->getSharedWithMeFiles()
                        : $this->listAllFilesRecursive($service, $this->userDir);

                    return $results->filter(fn (array $item): bool => stripos($item['file'] ?? '', $search) !== false);
                }

                if ($isSharedRoot) {
                    return $this->getSharedWithMeFiles();
                }

                try {
                    $files = collect($service->listFiles($dir))
                        ->map(fn (array $item): array => $item + ['__key' => $item['file'] ?? uniqid()]);

                    if ($this->currentPath === '/') {
                        $sharedFolder = [
                            '__key' => self::SHARED_PATH,
                            'file' => self::SHARED_PATH,
                            'type' => 'dir',
                            'path' => '/',
                            'size' => 0,
                            'mtime' => 0,
                            'nicemode' => '0755',
                        ];
                        $files->prepend($sharedFolder);
                    }

                    return $files;
                } catch (\Exception) {
                    return collect();
                }
            })
            ->checkIfRecordIsSelectableUsing(fn (array $record): bool => ($record['file'] ?? '') !== self::SHARED_PATH)
            ->emptyStateIcon('heroicon-o-cloud')
            ->emptyStateHeading(fn () => $isSharedRoot ? 'Sin archivos compartidos' : 'Carpeta vacia')
            ->emptyStateDescription(fn () => $isSharedRoot ? 'No tienes archivos compartidos contigo.' : 'No hay archivos en esta ubicacion.')
            ->columns([
                IconColumn::make('type')
                    ->label('')
                    ->size(IconSize::TwoExtraLarge)
                    ->width('50px')
                    ->icon(function (string $state, array $record): string {
                        if ($state === 'dir' && ($record['file'] ?? '') === self::SHARED_PATH) {
                            return 'heroicon-o-share';
                        }

                        if ($state === 'dir') {
                            return 'heroicon-o-folder';
                        }

                        $extension = strtolower(pathinfo($record['file'] ?? '', PATHINFO_EXTENSION));

                        return match ($extension) {
                            'pdf' => 'heroicon-o-document-chart-bar',
                            'xlsx', 'xls', 'csv' => 'heroicon-o-table-cells',
                            'docx', 'doc' => 'heroicon-o-document-text',
                            'pptx', 'ppt' => 'heroicon-o-presentation-chart-bar',
                            'jpg', 'jpeg', 'png', 'gif', 'svg' => 'heroicon-o-photo',
                            'zip', 'rar', '7z' => 'heroicon-o-archive-box',
                            'txt' => 'heroicon-o-document-minus',
                            'mp4', 'avi', 'mov' => 'heroicon-o-film',
                            'mp3', 'wav', 'aac', 'm4a' => 'heroicon-o-musical-note',
                            default => 'heroicon-o-document',
                        };
                    })
                    ->color(function (string $state, array $record): string {
                        if ($state === 'dir' && ($record['file'] ?? '') === self::SHARED_PATH) {
                            return 'success';
                        }

                        if ($state === 'dir') {
                            return 'warning';
                        }

                        $extension = strtolower(pathinfo($record['file'] ?? '', PATHINFO_EXTENSION));

                        return match ($extension) {
                            'pdf' => 'danger',
                            'xlsx', 'xls' => 'success',
                            'docx', 'doc' => 'info',
                            'pptx', 'ppt' => 'warning',
                            'jpg', 'jpeg', 'png' => 'primary',
                            'zip', 'rar' => 'gray',
                            'txt' => 'secondary',
                            'mp4', 'avi', 'mov' => 'info',
                            'mp3', 'wav', 'aac', 'm4a' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->action(function (array $record) use ($service): void {
                        $isDir = ($record['type'] ?? '') === 'dir';

                        if ($isDir) {
                            if ($this->getTableSearch()) {
                                Notification::make()
                                    ->title('Búsqueda activa')
                                    ->body('Desactiva la búsqueda para navegar carpetas.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $name = $record['file'] ?? '';
                            $this->currentPath = $this->normalizePath($this->currentPath.$name.'/');
                            $this->resetTable();

                            return;
                        }

                        $name = $record['file'] ?? '';
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $dir = isset($record['owner_id'])
                            ? ($record['path'] ?? $this->currentDiskDir())
                            : $this->currentDiskDir();

                        if (isset($record['share_id']) && ($record['requires_ack'] ?? false) && empty($record['ack_completed_at'] ?? null)) {
                            $this->pendingAckRecord = $record;
                            $this->dispatch('open-ack-confirm', shareId: (string) $record['share_id']);

                            return;
                        }

                        $tenantId = tenant()->id;
                        $docKey = md5($tenantId.$dir.$name.microtime());
                        $cpanelDir = storage_path("app/tenants/{$tenantId}/onlyoffice/cpanel");

                        if (! is_dir($cpanelDir)) {
                            mkdir($cpanelDir, 0755, true);
                        }

                        $content = null;

                        try {
                            $content = $service->getFileContent($dir, $name);
                        } catch (\Exception) {
                        }

                        if ($content !== null) {
                            file_put_contents("{$cpanelDir}/{$docKey}.dat", $content);
                        }

                        file_put_contents("{$cpanelDir}/{$docKey}.meta.json", json_encode([
                            'dir' => $dir,
                            'name' => $name,
                            'owner_id' => $record['owner_id'] ?? Auth::id(),
                        ]));

                        if ($content === null) {
                            Notification::make()
                                ->title('No se pudo leer el archivo')
                                ->body('El archivo no está disponible actualmente.')
                                ->warning()
                                ->send();

                            return;
                        }

                        if (in_array($ext, ['docx', 'xlsx', 'pptx', 'pdf'])) {
                            $this->dispatch('open-onlyoffice', url: route('onlyoffice.cpanel.open', ['docKey' => $docKey]));

                            return;
                        }

                        if (in_array($ext, ['txt', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'mp4', 'avi', 'mov', 'mp3', 'wav', 'aac', 'm4a'])) {
                            $downloadUrl = route('onlyoffice.cpanel.download', ['docKey' => $docKey]);

                            $this->dispatch('open-preview', path: route('onlyoffice.cpanel.download', ['docKey' => $docKey, 'preview' => 1]), downloadUrl: $downloadUrl, type: $ext, name: $name, docKey: $docKey);

                            return;
                        }

                        $this->dispatch('open-download', url: route('onlyoffice.cpanel.download', ['docKey' => $docKey, 'preview' => 1]));
                    }),

                TextColumn::make('file')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('path')
                    ->label('Ubicación')
                    ->visible(fn (): bool => (bool) $this->getTableSearch())
                    ->formatStateUsing(fn (?string $state): string => $state === '/' || $state === null ? '/' : $state),

                TextColumn::make('size')
                    ->label('Tamaño')
                    ->state(fn (array $record): int => (int) ($record['size'] ?? 0))
                    ->formatStateUsing(fn (int $state): string => $this->formatBytes($state)),

                TextColumn::make('mtime')
                    ->label('Modificado')
                    ->formatStateUsing(function (mixed $state): string {
                        if (! $state) {
                            return '-';
                        }

                        return Carbon::createFromTimestamp((int) $state)->format('d-m-Y H:i:s');
                    }),

                TextColumn::make('owner_name')
                    ->label('Propietario')
                    ->badge()
                    ->state(function (array $record) use ($isSharedRoot): string {
                        if ($isSharedRoot) {
                            return $record['owner_name'] ?? 'Desconocido';
                        }

                        return 'Yo';
                    })
                    ->color(fn (string $state): string => $state === 'Yo' ? 'success' : 'info'),

                TextColumn::make('permission')
                    ->label('Permiso')
                    ->badge()
                    ->state(function (array $record) use ($isSharedRoot): string {
                        if ($isSharedRoot) {
                            return $record['permission'] ?? 'view';
                        }

                        return 'full';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'full' => 'Propietario',
                        'edit' => 'Editar',
                        'view' => 'Solo ver',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'full' => 'success',
                        'edit' => 'warning',
                        default => 'success',
                    }),

                IconColumn::make('shared')
                    ->label('Compartido')
                    ->size(IconSize::Large)
                    ->width('40px')
                    ->state(function (?array $record) use ($isSharedRoot): bool {
                        if ($isSharedRoot || $record === null) {
                            return false;
                        }

                        $name = $record['file'] ?? '';
                        $type = $record['type'] ?? '';

                        if ($type === 'dir' || $name === self::SHARED_PATH) {
                            return false;
                        }

                        return CpanelFileShare::where('tenant_id', tenant()->id)
                            ->where('owner_id', Auth::id())
                            ->where('name', $name)
                            ->exists();
                    })
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-user-group' : 'heroicon-o-user')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip(function (?array $record) use ($isSharedRoot): ?string {
                        if ($record === null) {
                            return null;
                        }

                        $name = $record['file'] ?? '';
                        $type = $record['type'] ?? '';

                        if ($type === 'dir' || $name === self::SHARED_PATH) {
                            return null;
                        }

                        if ($isSharedRoot) {
                            return 'No puede ver esta información';
                        }

                        $count = CpanelFileShare::where('tenant_id', tenant()->id)
                            ->where('owner_id', Auth::id())
                            ->where('name', $name)
                            ->count();

                        return $count > 0 ? "Compartido con {$count} usuario(s)" : 'No compartido';
                    })
                    ->action(function (?array $record) use ($isSharedRoot): void {
                        if ($record !== null && ! $isSharedRoot) {
                            $this->openShareInfo($record);
                        }
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('rename')
                        ->label('Renombrar')
                        ->size(Size::Large)
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->modalHeading('Renombrar')
                        ->visible(fn (?array $record) => ! $isSharedRoot
                            && ($record['file'] ?? '') !== self::SHARED_PATH)
                        ->schema([
                            TextInput::make('newName')
                                ->label('Nuevo nombre')
                                ->required()
                                ->default(fn (?array $record) => $record['file'] ?? ''),
                        ])
                        ->action(function (array $data, ?array $record) use ($service) {
                            $oldName = $record['file'] ?? '';
                            $newName = trim($data['newName']);

                            if ($newName === '' || $newName === $oldName) {
                                return;
                            }

                            $dir = $this->currentDiskDir();

                            try {
                                $service->renameFile($dir, $oldName, $newName);
                                $this->resetTable();
                                Notification::make()->title('Renombrado exitosamente')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al renombrar')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('open')
                        ->label('Abrir')
                        ->size(Size::Large)
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->visible(fn (?array $record): bool => ! $isSharedRoot && in_array(
                            strtolower(pathinfo($record['file'] ?? '', PATHINFO_EXTENSION)),
                            ['docx', 'xlsx', 'pptx', 'pdf'],
                        ))
                        ->action(function (?array $record) use ($service): void {
                            $name = $record['file'] ?? '';
                            $dir = isset($record['owner_id'])
                                ? ($record['path'] ?? $this->currentDiskDir())
                                : $this->currentDiskDir();

                            $tenantId = tenant()->id;
                            $docKey = md5($tenantId.$dir.$name.microtime());
                            $cpanelDir = storage_path("app/tenants/{$tenantId}/onlyoffice/cpanel");

                            if (! is_dir($cpanelDir)) {
                                mkdir($cpanelDir, 0755, true);
                            }

                            file_put_contents("{$cpanelDir}/{$docKey}.meta.json", json_encode([
                                'dir' => $dir,
                                'name' => $name,
                                'owner_id' => Auth::id(),
                            ]));

                            try {
                                $content = $service->getFileContent($dir, $name);
                                if ($content !== null) {
                                    file_put_contents("{$cpanelDir}/{$docKey}.dat", $content);
                                }
                            } catch (\Exception) {
                            }

                            $this->dispatch('open-onlyoffice', url: route('onlyoffice.cpanel.open', ['docKey' => $docKey]));
                        }),

                    Action::make('download')
                        ->label('Descargar')
                        ->size(Size::Large)
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->visible(function (?array $record) use ($isSharedRoot): bool {
                            if (($record['type'] ?? '') === 'dir') {
                                return false;
                            }

                            if ($isSharedRoot) {
                                return ! ($record['requires_ack'] ?? false)
                                    || ! empty($record['ack_completed_at'] ?? null);
                            }

                            return true;
                        })
                        ->action(function (?array $record) use ($service) {
                            $name = $record['file'] ?? '';
                            $dir = isset($record['owner_id'])
                                ? ($record['path'] ?? $this->currentDiskDir())
                                : $this->currentDiskDir();

                            try {
                                $content = $service->getFileContent($dir, $name);

                                if ($content === null) {
                                    Notification::make()->title('Error al leer archivo')->danger()->send();

                                    return;
                                }

                                $tempPath = storage_path('app/tmp/'.$name);

                                if (! is_dir(dirname($tempPath))) {
                                    mkdir(dirname($tempPath), 0755, true);
                                }

                                file_put_contents($tempPath, $content);

                                return response()->download($tempPath, $name)->deleteFileAfterSend(true);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al descargar')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('delete')
                        ->label('Eliminar')
                        ->size(Size::Large)
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (?array $record) => ! $isSharedRoot
                            && ($record['file'] ?? '') !== self::SHARED_PATH)
                        ->action(function (?array $record) use ($service, $dir) {
                            $name = $record['file'] ?? '';

                            try {
                                $service->deleteFile($dir, $name);
                                $this->resetTable();
                                Notification::make()->title('Eliminado')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al eliminar')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('share')
                        ->label('Compartir')
                        ->size(Size::Large)
                        ->icon('heroicon-o-share')
                        ->color('info')
                        ->visible(function (?array $record) use ($isSharedRoot): bool {
                            if ($isSharedRoot) {
                                return false;
                            }

                            return ($record['type'] ?? '') !== 'dir'
                                && ($record['file'] ?? '') !== self::SHARED_PATH;
                        })
                        ->modalHeading('Compartir archivo')
                        ->schema([
                            Select::make('user_id')
                                ->label('Usuario')
                                ->options(fn () => User::where('id', '!=', Auth::id())
                                    ->where('is_internal', true)
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            Select::make('permission')
                                ->label('Permiso')
                                ->options([
                                    'view' => 'Solo ver',
                                    'edit' => 'Editar',
                                ])
                                ->default('view')
                                ->required(),
                            Toggle::make('requires_ack')
                                ->label('Requiere confirmación (toma de conocimiento)')
                                ->default(false),
                        ])
                        ->action(function (array $data, ?array $record): void {
                            $userId = $data['user_id'];
                            $perm = $data['permission'];
                            $needsAck = (bool) ($data['requires_ack'] ?? false);
                            $sharePath = rtrim($this->currentDiskDir(), '/');
                            $shareSize = (int) ($record['size'] ?? 0);
                            $shareMtime = (int) ($record['mtime'] ?? 0);

                            $exists = CpanelFileShare::where('tenant_id', tenant()->id)
                                ->where('owner_id', Auth::id())
                                ->where('user_id', $userId)
                                ->where('path', $sharePath)
                                ->where('name', $record['file'] ?? '')
                                ->first();

                            $ackCode = null;
                            $expiresAt = null;

                            if ($needsAck) {
                                $ackCode = $this->generateAckCode();
                                $expiresAt = now()->addDays(7);
                            }

                            if ($exists) {
                                $exists->update([
                                    'permission' => $perm,
                                    'requires_ack' => $needsAck,
                                    'ack_code' => $ackCode,
                                    'ack_code_expires_at' => $expiresAt,
                                    'ack_completed_at' => null,
                                    'size' => $shareSize,
                                    'mtime' => $shareMtime,
                                ]);
                            } else {
                                CpanelFileShare::create([
                                    'tenant_id' => tenant()->id,
                                    'owner_id' => Auth::id(),
                                    'user_id' => $userId,
                                    'path' => $sharePath,
                                    'name' => $record['file'] ?? '',
                                    'size' => $shareSize,
                                    'mtime' => $shareMtime,
                                    'permission' => $perm,
                                    'requires_ack' => $needsAck,
                                    'ack_code' => $ackCode,
                                    'ack_code_expires_at' => $expiresAt,
                                ]);
                            }

                            if ($needsAck) {
                                $recipient = User::find($userId);
                                $senderName = Auth::user()->name;

                                if ($recipient?->email) {
                                    Mail::to($recipient->email)->send(new CpanelFileShareAckCodeMail(
                                        fileName: $record['file'] ?? '',
                                        code: $ackCode,
                                        expiresAt: $expiresAt,
                                        senderName: $senderName,
                                    ));
                                }
                            }

                            $this->dispatch('refresh-sidebar');
                            Notification::make()
                                ->title('Archivo compartido')
                                ->success()
                                ->send();
                        }),

                    Action::make('acknowledge')
                        ->label('Confirmar recepción')
                        ->size(Size::Large)
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(function (?array $record) use ($isSharedRoot): bool {
                            if (! $isSharedRoot) {
                                return false;
                            }

                            return ($record['requires_ack'] ?? false)
                                && empty($record['ack_completed_at'] ?? null);
                        })
                        ->schema([
                            TextInput::make('code')
                                ->label('Código de confirmación')
                                ->required()
                                ->maxLength(12),
                        ])
                        ->action(function (array $data, ?array $record): void {
                            $shareId = $record['share_id'] ?? null;

                            $this->confirmAck((string) $shareId, $data['code']);
                        }),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->action(function (Collection $records) use ($service) {
                        $dir = $this->currentDiskDir();

                        foreach ($records as $record) {
                            $name = $record['file'] ?? '';
                            if ($name && $name !== self::SHARED_PATH) {
                                try {
                                    $service->deleteFile($dir, $name);
                                } catch (\Exception) {
                                }
                            }
                        }

                        $this->resetTable();

                        Notification::make()->title('Eliminados')->success()->send();
                    }),
                Action::make('zip')
                    ->label('Descargar ZIP')
                    ->icon('heroicon-o-archive-box')
                    ->color('primary')
                    ->accessSelectedRecords()
                    ->action(function (Collection $records) {
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('Sin archivos seleccionados')
                                ->body('Selecciona al menos un archivo para descargar en ZIP.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $dir = $this->currentDiskDir();
                        $service = $this->getService();

                        $zipPath = storage_path('app/tmp/export_'.Auth::id().'_'.now()->timestamp.'.zip');
                        $zipDir = dirname($zipPath);

                        if (! is_dir($zipDir)) {
                            mkdir($zipDir, 0755, true);
                        }

                        $zip = new \ZipArchive;
                        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
                            Notification::make()->title('Error al crear ZIP')->danger()->send();

                            return;
                        }

                        $added = 0;

                        foreach ($records as $record) {
                            $name = $record['file'] ?? '';
                            $type = $record['type'] ?? '';

                            if ($name === '' || $name === self::SHARED_PATH) {
                                continue;
                            }

                            if ($type === 'dir') {
                                $added += $this->addDirectoryToZip($zip, $service, $dir.'/'.$name, $name.'/');

                                continue;
                            }

                            try {
                                $content = $service->getFileContent($dir, $name);
                                if ($content !== null) {
                                    $zip->addFromString($name, $content);
                                    $added++;
                                }
                            } catch (\Exception) {
                            }
                        }

                        $zip->close();

                        if ($added === 0) {
                            @unlink($zipPath);

                            Notification::make()
                                ->title('No se pudo crear el ZIP')
                                ->body('Ninguno de los archivos seleccionados pudo ser leído.')
                                ->warning()
                                ->send();

                            return;
                        }

                        return response()->download($zipPath)->deleteFileAfterSend(true);
                    }),

                BulkAction::make('move')
                    ->label('Mover')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->modalHeading('Mover seleccionados')
                    ->requiresConfirmation()
                    ->visible(fn () => ! $isSharedRoot)
                    ->schema([
                        TextInput::make('targetPath')
                            ->label('Ruta destino')
                            ->placeholder('/subcarpeta')
                            ->default(fn () => $this->currentPath)
                            ->helperText('Ruta desde la raíz. Ej: /documentos/2025')
                            ->required(),
                    ])
                    ->action(function (array $data, Collection $records) use ($service) {
                        $target = $data['targetPath'];
                        $target = '/'.trim($target, '/');

                        if ($target !== '/') {
                            $target .= '/';
                        }

                        $target = preg_replace('#/+#', '/', $target);

                        if ($target === $this->normalizePath($this->currentPath)) {
                            Notification::make()
                                ->title('Mismo destino')
                                ->body('La ruta destino es la misma que la actual.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $sourceDir = $this->currentDiskDir();
                        $destDir = $this->userDir.$target;
                        $destDir = rtrim($destDir, '/');

                        $moved = 0;
                        $skipped = 0;

                        foreach ($records as $record) {
                            $name = $record['file'] ?? '';
                            $type = $record['type'] ?? '';

                            if ($name === '' || $name === self::SHARED_PATH) {
                                $skipped++;

                                continue;
                            }

                            try {
                                $service->moveCpanelFile($sourceDir, $name, $destDir);
                                $moved++;
                            } catch (\Exception) {
                                $skipped++;
                            }
                        }

                        $this->resetTable();

                        Notification::make()
                            ->title('Movidos')
                            ->body("{$moved} movido(s), {$skipped} omitido(s)")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    private function addDirectoryToZip(\ZipArchive $zip, CPanelFilemanService $service, string $dir, string $prefix): int
    {
        $added = 0;

        try {
            foreach ($service->listFiles($dir) as $f) {
                $name = $f['file'] ?? '';

                if ($name === '') {
                    continue;
                }

                if (($f['type'] ?? '') === 'dir') {
                    $added += $this->addDirectoryToZip($zip, $service, $dir.'/'.$name, $prefix.$name.'/');
                } else {
                    $content = $service->getFileContent($dir, $name);

                    if ($content !== null) {
                        $zip->addFromString($prefix.$name, $content);
                        $added++;
                    }
                }
            }
        } catch (\Exception) {
        }

        return $added;
    }

    public function navigateTo(string $path): void
    {
        if (str_contains($path, '..')) {
            return;
        }

        $this->currentPath = $this->normalizePath($path);
        $this->resetTable();
    }

    public function goToRoot(): void
    {
        $this->currentPath = '/';
        $this->resetTable();
    }

    public function saveTxt(string $docKey, string $content): void
    {
        $tenantId = tenant()->id;
        $metaPath = storage_path("app/tenants/{$tenantId}/onlyoffice/cpanel/{$docKey}.meta.json");

        if (! file_exists($metaPath)) {
            Notification::make()
                ->title('Error al guardar')
                ->body('No se encontró el archivo de referencia.')
                ->danger()
                ->send();

            return;
        }

        $meta = json_decode(file_get_contents($metaPath), true);
        $dir = $meta['dir'] ?? '';
        $name = $meta['name'] ?? '';

        try {
            $service = $this->getService();
            $service->saveFileContent($dir, $name, $content);

            $dataPath = storage_path("app/tenants/{$tenantId}/onlyoffice/cpanel/{$docKey}.dat");
            file_put_contents($dataPath, $content);

            Notification::make()
                ->title('Guardado')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al guardar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        if (! tenant()) {
            return null;
        }

        $count = CpanelFileShare::where('user_id', Auth::id())
            ->where('requires_ack', true)
            ->whereNull('ack_completed_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function openShareInfo(array $record): void
    {
        $name = $record['file'] ?? '';

        $shares = CpanelFileShare::where('tenant_id', tenant()->id)
            ->where('owner_id', Auth::id())
            ->where('name', $name)
            ->with('recipient')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->recipient->id,
                'name' => $s->recipient->name,
                'permission' => $s->permission,
                'ack_required' => $s->requires_ack,
                'ack_completed' => $s->ack_completed_at !== null,
                'ack_completed_at' => $s->ack_completed_at?->format('d/m/Y H:i'),
            ])
            ->toArray();

        $this->currentShareInfoFile = $record;

        $this->dispatch('open-share-info', shares: $shares);
    }

    public function removeShare(int $userId): void
    {
        if (! $this->currentShareInfoFile) {
            return;
        }

        $name = $this->currentShareInfoFile['file'] ?? '';

        CpanelFileShare::where('tenant_id', tenant()->id)
            ->where('owner_id', Auth::id())
            ->where('user_id', $userId)
            ->where('name', $name)
            ->delete();

        $this->dispatch('refresh-sidebar');
        $this->openShareInfo($this->currentShareInfoFile);
    }

    private function getSharedWithMeFiles(): Collection
    {
        $shares = CpanelFileShare::where('tenant_id', tenant()->id)
            ->where('user_id', Auth::id())
            ->with('owner')
            ->get();

        $service = $this->getService();

        return $shares->map(function (CpanelFileShare $share) use ($service): array {
            $size = $share->size;
            $mtime = $share->mtime;

            if ($size === 0 || $mtime === 0) {
                try {
                    $files = $service->listFiles($share->path);
                    foreach ($files as $f) {
                        if (($f['file'] ?? '') === $share->name) {
                            $size = (int) ($f['size'] ?? 0);
                            $mtime = (int) ($f['mtime'] ?? 0);

                            $share->update(['size' => $size, 'mtime' => $mtime]);

                            break;
                        }
                    }
                } catch (\Exception) {
                }
            }

            return [
                '__key' => (string) $share->id,
                'file' => $share->name,
                'type' => 'file',
                'path' => $share->path,
                'size' => $size,
                'mtime' => $mtime,
                'nicemode' => '-',
                'owner_id' => $share->owner_id,
                'owner_name' => $share->owner?->name ?? 'Desconocido',
                'share_id' => $share->id,
                'requires_ack' => $share->requires_ack,
                'ack_completed_at' => $share->ack_completed_at,
                'permission' => $share->permission,
            ];
        });
    }

    private function listAllFilesRecursive(CPanelFilemanService $service, string $dir, string $displayPath = '/'): Collection
    {
        $result = collect();

        try {
            $files = $service->listFiles($dir);
        } catch (\Exception) {
            return $result;
        }

        foreach ($files as $file) {
            $name = $file['file'] ?? '';
            $type = $file['type'] ?? '';

            if ($name === '' || $name === self::SHARED_PATH) {
                continue;
            }

            if ($type === 'dir') {
                $subResults = $this->listAllFilesRecursive(
                    $service,
                    $dir.'/'.$name,
                    $displayPath === '/' ? '/'.$name.'/' : $displayPath.$name.'/',
                );
                $result = $result->merge($subResults);

                continue;
            }

            $file['path'] = $displayPath;
            $file['__key'] = $displayPath.$name;
            $result->push($file);
        }

        return $result;
    }

    public function confirmAck(string $shareId, string $code): void
    {
        $share = CpanelFileShare::find($shareId);

        if (! $share || ! $share->requires_ack) {
            Notification::make()
                ->title('Error')
                ->body('No se requiere confirmación para este archivo.')
                ->danger()
                ->send();

            return;
        }

        if (! hash_equals((string) $share->ack_code, (string) trim($code))) {
            Notification::make()
                ->title('Código incorrecto')
                ->body('El código ingresado no coincide.')
                ->danger()
                ->send();

            return;
        }

        if ($share->ack_code_expires_at && now()->gt($share->ack_code_expires_at)) {
            Notification::make()
                ->title('Código expirado')
                ->body('Solicita un nuevo código al propietario del archivo.')
                ->danger()
                ->send();

            return;
        }

        $share->update(['ack_completed_at' => now()]);
        $this->dispatch('refresh-sidebar');

        Notification::make()
            ->title('Confirmado')
            ->body('Ya puedes acceder al archivo.')
            ->success()
            ->send();

        $this->dispatch('close-modal', id: 'ack-confirm');

        if ($this->pendingAckRecord) {
            $record = $this->pendingAckRecord;
            $this->pendingAckRecord = null;

            $name = $record['file'] ?? '';
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $dir = $record['path'] ?? $this->currentDiskDir();
            $service = $this->getService();

            $tenantId = tenant()->id;
            $docKey = md5($tenantId.$dir.$name.microtime());
            $cpanelDir = storage_path("app/tenants/{$tenantId}/onlyoffice/cpanel");

            if (! is_dir($cpanelDir)) {
                mkdir($cpanelDir, 0755, true);
            }

            $content = null;
            try {
                $content = $service->getFileContent($dir, $name);
            } catch (\Exception) {
            }

            if ($content !== null) {
                file_put_contents("{$cpanelDir}/{$docKey}.dat", $content);
            }

            file_put_contents("{$cpanelDir}/{$docKey}.meta.json", json_encode([
                'dir' => $dir,
                'name' => $name,
                'owner_id' => $record['owner_id'] ?? Auth::id(),
            ]));

            if ($content === null) {
                Notification::make()
                    ->title('No se pudo leer el archivo')
                    ->warning()
                    ->send();

                return;
            }

            if (in_array($ext, ['txt', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'mp4', 'avi', 'mov', 'mp3', 'wav', 'aac', 'm4a'])) {
                $downloadUrl = route('onlyoffice.cpanel.download', ['docKey' => $docKey]);
                $this->dispatch('open-preview', path: route('onlyoffice.cpanel.download', ['docKey' => $docKey, 'preview' => 1]), downloadUrl: $downloadUrl, type: $ext, name: $name, docKey: $docKey);

                return;
            }

            $this->dispatch('open-download', url: route('onlyoffice.cpanel.download', ['docKey' => $docKey, 'preview' => 1]));
        }
    }

    public function resendAckCode(): void
    {
        $share = CpanelFileShare::where('user_id', Auth::id())
            ->where('requires_ack', true)
            ->whereNull('ack_completed_at')
            ->first();

        if (! $share) {
            Notification::make()
                ->title('Sin confirmaciones pendientes')
                ->warning()
                ->send();

            return;
        }

        $code = $this->generateAckCode();
        $share->update([
            'ack_code' => $code,
            'ack_code_expires_at' => now()->addDays(7),
        ]);

        $owner = User::find($share->owner_id);

        if ($owner && $owner->email) {
            Mail::to($owner->email)->send(new CpanelFileShareAckCodeMail(
                fileName: $share->name,
                code: $code,
                expiresAt: now()->addDays(7),
                senderName: Auth::user()->name,
            ));
        }

        Notification::make()
            ->title('Código reenviado')
            ->body('Se ha enviado un nuevo código al propietario del archivo.')
            ->success()
            ->send();
    }

    private function generateAckCode(int $length = 6): string
    {
        $length = max(4, min(12, $length));
        $min = (int) pow(10, $length - 1);
        $max = (int) (pow(10, $length) - 1);

        do {
            $code = (string) random_int($min, $max);
        } while (CpanelFileShare::where('ack_code', $code)->exists());

        return $code;
    }
}
