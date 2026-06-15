<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MFA Required Roles
    |--------------------------------------------------------------------------
    |
    | Roles that require MFA to be enabled before accessing the application.
    | Admins can customize this list via environment variables.
    |
    */
    'mfa_required_roles' => explode(',', env('MFA_REQUIRED_ROLES', 'admin,manager')),

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Minimum password length (cannot be set below 10)
    |
    */
    'password_min_length' => (int) env('PASSWORD_MIN_LENGTH', 12),

    /*
    |--------------------------------------------------------------------------
    | Password Expiry Days
    |--------------------------------------------------------------------------
    |
    | Number of days before password expires for different roles.
    | Set to 0 for no expiry.
    |
    */
    'password_expiry' => [
        'admin' => (int) env('PASSWORD_EXPIRY_ADMIN', 90),
        'manager' => (int) env('PASSWORD_EXPIRY_MANAGER', 90),
        'agent' => (int) env('PASSWORD_EXPIRY_AGENT', 0),
        'read-only' => (int) env('PASSWORD_EXPIRY_READONLY', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Days to keep soft-deleted contacts before anonymization (default 30)
    |
    */
    'data_retention_days' => (int) env('DATA_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | API Rate Limits
    |--------------------------------------------------------------------------
    |
    | Per-minute rate limits for API endpoints
    |
    */
    'api_rate_limits' => [
        'default' => (int) env('API_RATE_LIMIT', 60),
        'per_page_max' => 100,
    ],
];
