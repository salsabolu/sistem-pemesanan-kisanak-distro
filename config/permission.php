<?php

return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    'teams' => false,

    'register_permission_check_method' => true,

    'register_octane_reset_listener' => false,

    'events' => [
        'role' => Spatie\Permission\Events\RoleCreated::class,
        'permission' => Spatie\Permission\Events\PermissionCreated::class,
    ],

    'team_resolver' => Spatie\Permission\DefaultTeamResolver::class,

    'use_passport_client_credentials' => false,

    'display_permission_in_exception' => false,

    'permission' => [
        'cache' => [
            'expiration_time' => \DateInterval::createFromDateString('24 hours'),
            'key' => 'spatie.permission.cache',
            'store' => 'default',
        ],
    ],
];
