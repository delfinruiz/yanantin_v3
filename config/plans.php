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
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Plan
    |--------------------------------------------------------------------------
    |
    | Default plan assigned to new tenants when no plan is selected.
    |
    */
    'default' => 'free',

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | Predefined plans with their features. These are seeded into the database.
    |
    */
    'plans' => [
        'free' => [
            'name' => 'Gratuito',
            'features' => ['products', 'felicidad-organizacional'],
            'max_users' => null,
        ],
        'basic' => [
            'name' => 'Basico',
            'features' => ['products', 'users', 'felicidad-organizacional'],
            'max_users' => null,
        ],
        'pro' => [
            'name' => 'Profesional',
            'features' => ['products', 'users', 'roles', 'felicidad-organizacional'],
            'max_users' => null,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'features' => ['products', 'users', 'roles', 'departments', 'felicidad-organizacional'],
            'max_users' => null,
        ],
    ],
];
