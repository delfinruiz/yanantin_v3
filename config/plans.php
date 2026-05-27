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
            'features' => ['products'],
            'max_users' => null,
        ],
        'basic' => [
            'name' => 'Basico',
            'features' => ['products', 'users'],
            'max_users' => null,
        ],
        'pro' => [
            'name' => 'Profesional',
            'features' => ['products', 'users', 'roles'],
            'max_users' => null,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'features' => ['products', 'users', 'roles'],
            'max_users' => null,
        ],
    ],
];
