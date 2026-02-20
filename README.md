# Laravel Telescope Dashboard

Advanced replacement for the default Laravel Telescope UI. Built with **Vue 3**, **Tailwind CSS**, and type-specific filtering optimized with MySQL virtual generated columns.

## Features

- **18 entry types** - Requests, Queries, Exceptions, Jobs, Logs, Mail, Events, Cache, Commands, Schedule, Models, Gates, Dumps, Notifications, Redis, Client Requests, Batches, Views
- **Type-specific filters** - Each entry type has its own set of relevant filters (HTTP method, status code, duration, log level, job status, etc.)
- **Virtual column optimization** - MySQL generated columns with composite indexes for fast filtering on large datasets
- **Dark / Light mode** - Theme toggle with localStorage persistence
- **URL state sync** - Filters and sorting are synced to URL query params for bookmarkable views
- **Column sorting** - Sort by sequence, created_at, duration, or query time with ASC/DESC toggle
- **Inline & full-page detail** - Expand entries in the table or open a full-page detail view with tabs
- **Related entries** - View all entries from the same batch grouped by type
- **JSON viewer** - Recursive viewer with expand/collapse and string truncation
- **Copy to clipboard** - Copy entry UUID or full JSON content
- **Multi-language** - English, Slovak, and Czech translations
- **Authorization** - Gate-based access control (default: `viewTelescope`)
- **Responsive** - Collapsible sidebar for mobile devices

## Requirements

- PHP 8.2+
- Laravel 11.0+
- Laravel Telescope 5.0+
- MySQL 8.0+ (for virtual generated columns)

## Installation

### 1. Install via Composer

```bash
composer require wamesk/laravel-telescope-dashboard
```

### 2. Publish assets

```bash
php artisan vendor:publish --tag=telescope-dashboard-assets
```

### 3. Run migrations

The package adds virtual generated columns and indexes to the `telescope_entries` table for optimized filtering:

```bash
php artisan migrate
```

### 4. (Optional) Publish configuration

```bash
php artisan vendor:publish --tag=telescope-dashboard-config
```

## Configuration

After publishing, the config file is located at `config/wame-telescope-dashboard.php`:

```php
return [
    // Enable or disable the dashboard
    'enabled' => env('TELESCOPE_DASHBOARD_ENABLED', true),

    // URL path for the dashboard
    'path' => env('TELESCOPE_DASHBOARD_PATH', 'telescope-dashboard'),

    // Laravel gate used for authorization
    'gate' => env('TELESCOPE_DASHBOARD_GATE', 'viewTelescope'),

    // Database connection for telescope data
    'connection' => env('DB_CONNECTION_TELESCOPE', 'mysql_telescope'),

    // Default number of entries per page
    'per_page' => 50,

    // Maximum entries per page (security limit)
    'max_per_page' => 200,

    // Route group patterns for request filtering
    'route_groups' => [
        'api'      => '/api/v*',
        'nova-api' => '/nova-api/*',
        'web'      => '/*',
    ],

    // Path to original Telescope (for "View in Telescope" links)
    'telescope_path' => env('TELESCOPE_PATH', 'telescope'),

    // Middleware applied to all dashboard routes
    'middleware' => ['web', 'auth'],
];
```

## Authorization

The dashboard uses a Laravel gate for access control. By default, it checks the `viewTelescope` gate, which is typically defined by Telescope itself in `app/Providers/TelescopeServiceProvider.php`:

```php
Gate::define('viewTelescope', function ($user) {
    return in_array($user->email, [
        'admin@example.com',
    ]);
});
```

You can change the gate name via the `gate` config option or the `TELESCOPE_DASHBOARD_GATE` environment variable.

## Usage

Once installed, visit your application at:

```
https://your-app.test/telescope-dashboard
```

### Filtering

Each entry type provides its own set of filters. Examples:

| Entry Type | Available Filters |
|---|---|
| **Requests** | HTTP method, URI, status code (2xx/3xx/4xx/5xx), min duration, route group, user email |
| **Queries** | Slow queries (>100ms), query type (SELECT/INSERT/UPDATE/DELETE), min duration |
| **Exceptions** | Exception class |
| **Jobs** | Status (pending/completed/failed), job name |
| **Logs** | Level (emergency, alert, critical, error, warning, notice, info, debug) |
| **Mail** | Mailable class, recipient, subject |
| **Models** | Action (created/updated/deleted), model type |
| **Gates** | Ability, result (allowed/denied) |
| **Cache** | Type (hit/missed/set/forget), key |

All entry types also support **date range** and **content search** filters.

### Sorting

Entries can be sorted by:
- **Sequence** (default) - Insertion order
- **Created at** - Timestamp
- **Duration** - Request duration (requests only)
- **Time** - Query execution time (queries only)

### URL State

All filter and sorting states are synced to URL query parameters. This means you can bookmark or share filtered views:

```
/telescope-dashboard/#/requests?methods=GET,POST&statuses=4xx,5xx&sort_by=c_duration&sort_direction=desc
```

## Database Optimization

The package adds **MySQL virtual generated columns** to the `telescope_entries` table for efficient filtering without modifying stored data:

| Column | Source | Type |
|---|---|---|
| `c_method` | `content->$.method` | `VARCHAR(10)` |
| `c_uri` | `content->$.uri` | `VARCHAR(500)` |
| `c_response_status` | `content->$.response_status` | `SMALLINT UNSIGNED` |
| `c_duration` | `content->$.duration` | `DECIMAL(10,2)` |
| `c_time` | `content->$.time` | `DECIMAL(10,2)` |

Each virtual column has a **composite index** with `(type, should_display_on_index, <column>)` for optimal query performance.

## API Endpoints

The dashboard exposes the following JSON API endpoints (all protected by auth + gate middleware):

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/telescope-dashboard/api/entries` | Search entries with filters and sorting |
| `GET` | `/telescope-dashboard/api/entries/{uuid}` | Get a single entry |
| `GET` | `/telescope-dashboard/api/entries/{uuid}/detail` | Get entry with related batch entries |
| `GET` | `/telescope-dashboard/api/filters/{type}` | Get available filter values for an entry type |

## Frontend Development

To modify the Vue 3 frontend:

```bash
cd wamesk/laravel-telescope-dashboard

# Install dependencies
npm install

# Start dev server with HMR
npm run dev

# Build for production
npm run build
```

The frontend stack:
- **Vue 3.4+** with Composition API
- **Vue Router 4.3+** (hash mode)
- **Tailwind CSS 3.4+**
- **Vite 5.0+**

After building, publish updated assets:

```bash
php artisan vendor:publish --tag=telescope-dashboard-assets --force
```

## Translations

The package ships with English, Slovak, and Czech translations. To publish and customize:

```bash
php artisan vendor:publish --tag=telescope-dashboard-translations
```

Translation files are located in `resources/lang/{en,sk,cz}/telescope-dashboard.php`.

## Comparison with Default Telescope UI

| Feature | Default Telescope | This Package |
|---|---|---|
| UI framework | Blade templates | Vue 3 SPA |
| Filtering | Basic | Type-specific with 18 filter variants |
| Sorting | Sequence only | Sequence, created_at, duration, time |
| Query performance | Direct JSON queries | Virtual columns + composite indexes |
| Theme | Light only | Dark / Light with toggle |
| URL state | None | Full filter/sort sync |
| Entry details | Separate page | Inline expand + full-page detail |
| Batch viewing | Flat list | Grouped by entry type |
| Copy support | None | UUID and JSON copy |
| Responsive | Desktop-focused | Mobile-friendly |
| Translations | English only | EN, SK, CZ |

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.