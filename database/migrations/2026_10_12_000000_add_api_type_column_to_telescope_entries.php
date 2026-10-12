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
     */
    public function up(): void
    {
        Schema::connection($this->getConnection())->table('telescope_entries', function (Blueprint $table) {
            $table->string('api_type', 191)->nullable();

            $table->index(['api_type', 'type', 'should_display_on_index'], 'idx_te_api_type');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->table('telescope_entries', function (Blueprint $table) {
            $table->dropIndex('idx_te_api_type');
            $table->dropColumn('api_type');
        });
    }
};
