<?php

/**
 * Envoy Deployment Configuration
 *
 * Fill out the server entries below before running any Envoy tasks.
 * Each key is the server hostname and must match a server defined in Envoy.blade.php.
 *
 *   webroot     — Absolute path to the server's web root directory.
 *   env         — Path (relative to webroot) to the .env file to copy during install.
 *   folder      — The subdirectory name the app lives in under webroot.
 *   app_url     — The full public URL of the application (e.g. https://example.com).
 *   db_database — Database name.
 *   db_username — Database username.
 *   db_password — Database password.
 *
 * Available Envoy commands:
 *
 *   Install (first-time setup — will prompt for confirmation):
 *     vendor/bin/envoy run install --server=your-server-hostname
 *
 *   Update (backup + pull + deploy):
 *     vendor/bin/envoy run update --server=your-server-hostname
 *
 *   Restore (roll back to last backup — will prompt for confirmation):
 *     vendor/bin/envoy run restore --server=your-server-hostname
 */

$servers = [
    'your-server-name.com' => [
        'webroot' => '/home/user/web/public_html',
        'env' => '.env.power',
        'folder' => 'support-manager',
        'app_url' => 'https://your-server-name.com',
        'db_database' => 'database name',
        'db_username' => 'database username',
        'db_password' => 'database password',
    ],
];

