<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('telescope.storage.database.connection');
    }

    /**
     * Store the resolved API type so it can be filtered and queried in plain SQL.
     *
     * The column is appended last (instant ADD COLUMN on MySQL 8 and MariaDB) and
     * filled by the package: new entries right after Telescope stores them,
     * existing ones by `telescope-dashboard:sync-api-types`.
     *
     * The index leads with api_type so one index serves the dashboard filter
     * (api_type + type + should_display_on_index, ordered by the implicit primary
     * key without a filesort) as well as ad-hoc `WHERE api_type = ?` queries.
     *
     * The column and the index are separate ALTER TABLE statements and DDL is not
     * transactional on MySQL / MariaDB, so an interrupted run (building the index
     * on a large table takes minutes) leaves the column without the index and the
     * migration unrecorded. Each step is skipped when it already exists, so a
     * re-run finishes the job instead of failing on "Duplicate column name".
     */
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        if (! $schema->hasColumn('telescope_entries', 'api_type')) {
            $schema->table('telescope_entries', function (Blueprint $table) {
                $table->string('api_type', 191)->nullable();
            });
        }

        if (! $schema->hasIndex('telescope_entries', 'idx_te_api_type')) {
            $schema->table('telescope_entries', function (Blueprint $table) {
                $table->index(['api_type', 'type', 'should_display_on_index'], 'idx_te_api_type');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        if ($schema->hasIndex('telescope_entries', 'idx_te_api_type')) {
            $schema->table('telescope_entries', function (Blueprint $table) {
                $table->dropIndex('idx_te_api_type');
            });
        }

        if ($schema->hasColumn('telescope_entries', 'api_type')) {
            $schema->table('telescope_entries', function (Blueprint $table) {
                $table->dropColumn('api_type');
            });
        }
    }
};
