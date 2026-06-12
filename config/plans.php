<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Available Features
    |--------------------------------------------------------------------------
    |
    | Each feature maps to one or more Shield entity suffixes (the part after
    | the colon in permission names like "ViewAny:Product").
    |
    */
    'features' => [
        'products' => [
            'label' => 'Productos',
            'entities' => ['Product'],
        ],
        'users' => [
            'label' => 'Usuarios',
            'entities' => ['User'],
        ],
        'roles' => [
            'label' => 'Roles',
            'entities' => ['Role'],
        ],
        'departments' => [
            'label' => 'Departamentos',
            'entities' => ['Department'],
        ],
        'felicidad-organizacional' => [
            'label' => 'Felicidad Organizacional',
            'entities' => ['Mood', 'HappinessSuggestion', 'CompanyHappiness', 'CompanyHappinessBarWidget', 'DailyMoodWidget', 'AiSuggestionsPanel', 'MoodTodayPieChart', 'MoodFilteredPieChart', 'MoodMonthlyDistributionChart', 'MoodPromptOverlay', 'ManageSettings'],
        ],
        'file_manager' => [
            'label' => 'Gestor de Archivos',
            'entities' => ['FileManager'],
        ],
        'mis_archivos' => [
            'label' => 'Mis Archivos',
            'entities' => ['CpanelFileManager'],
        ],
        'email' => [
            'label' => 'Cuentas de Correo',
            'entities' => ['EmailAccount'],
        ],
        'meet_rooms' => [
            'label' => 'Salas de Reunion',
            'entities' => ['MeetRoom'],
        ],
    ],

    'default' => 'free',

    'plans' => [
        'free' => [
            'name' => 'Gratuito',
            'features' => ['products', 'felicidad-organizacional', 'file_manager', 'mis_archivos', 'email'],
            'max_users' => null,
        ],
        'basic' => [
            'name' => 'Basico',
            'features' => ['products', 'users', 'felicidad-organizacional', 'file_manager', 'mis_archivos', 'email'],
            'max_users' => null,
        ],
        'pro' => [
            'name' => 'Profesional',
            'features' => ['products', 'users', 'roles', 'felicidad-organizacional', 'file_manager', 'mis_archivos', 'email', 'meet_rooms'],
            'max_users' => null,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'features' => ['products', 'users', 'roles', 'departments', 'felicidad-organizacional', 'file_manager', 'mis_archivos', 'email', 'meet_rooms'],
            'max_users' => null,
        ],
    ],
];
