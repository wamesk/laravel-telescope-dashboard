<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('telescope.storage.database.connection');
    }

    public function up(): void
    {
        $connection = $this->getConnection();
        $prefix = DB::connection($connection)->getTablePrefix();
        $table = $prefix . 'telescope_entries';

        DB::connection($connection)->statement("
            ALTER TABLE `{$table}`
                ADD COLUMN `c_method` VARCHAR(10) AS (JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.method'))) VIRTUAL NULL,
                ADD COLUMN `c_uri` VARCHAR(500) AS (LEFT(JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.uri')), 500)) VIRTUAL NULL,
                ADD COLUMN `c_response_status` SMALLINT UNSIGNED AS (JSON_EXTRACT(`content`, '$.response_status')) VIRTUAL NULL,
                ADD COLUMN `c_duration` DECIMAL(10,2) AS (JSON_EXTRACT(`content`, '$.duration')) VIRTUAL NULL,
                ADD COLUMN `c_time` DECIMAL(10,2) AS (JSON_EXTRACT(`content`, '$.time')) VIRTUAL NULL
        ");

        $schema = Schema::connection($connection);

        $schema->table('telescope_entries', function ($blueprint) {
            $blueprint->index(['type', 'should_display_on_index', 'c_method'], 'idx_te_type_display_method');
            $blueprint->index(['type', 'should_display_on_index', 'c_response_status'], 'idx_te_type_display_status');
            $blueprint->index(['type', 'should_display_on_index', 'c_duration'], 'idx_te_type_display_duration');
            $blueprint->index(['type', 'should_display_on_index', 'c_time'], 'idx_te_type_display_time');
        });

        // URI needs a prefix index — must use raw SQL
        DB::connection($connection)->statement("
            CREATE INDEX `idx_te_type_display_uri` ON `{$table}` (`type`, `should_display_on_index`, `c_uri`(255))
        ");
    }

    public function down(): void
    {
        $connection = $this->getConnection();
        $prefix = DB::connection($connection)->getTablePrefix();
        $table = $prefix . 'telescope_entries';

        $schema = Schema::connection($connection);

        $schema->table('telescope_entries', function ($blueprint) {
            $blueprint->dropIndex('idx_te_type_display_method');
            $blueprint->dropIndex('idx_te_type_display_uri');
            $blueprint->dropIndex('idx_te_type_display_status');
            $blueprint->dropIndex('idx_te_type_display_duration');
            $blueprint->dropIndex('idx_te_type_display_time');
        });

        DB::connection($connection)->statement("
            ALTER TABLE `{$table}`
                DROP COLUMN `c_method`,
                DROP COLUMN `c_uri`,
                DROP COLUMN `c_response_status`,
                DROP COLUMN `c_duration`,
                DROP COLUMN `c_time`
        ");
    }
};
