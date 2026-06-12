<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('database.connections.mysql.prefix', '');

        DB::statement("ALTER TABLE {$prefix}appointments MODIFY additional_notes LONGTEXT NULL");
        DB::statement("ALTER TABLE {$prefix}prescriptions MODIFY prescription_text LONGTEXT NOT NULL");
        DB::statement("ALTER TABLE {$prefix}booking_diagnostic MODIFY address LONGTEXT NOT NULL");
        DB::statement("ALTER TABLE {$prefix}booking_diagnostic MODIFY additional_notes LONGTEXT NULL");
        DB::statement("ALTER TABLE {$prefix}test_results MODIFY file_path LONGTEXT NOT NULL");
    }

    public function down(): void
    {
        $prefix = config('database.connections.mysql.prefix', '');

        DB::statement("ALTER TABLE {$prefix}appointments MODIFY additional_notes TEXT NULL");
        DB::statement("ALTER TABLE {$prefix}prescriptions MODIFY prescription_text TEXT NOT NULL");
        DB::statement("ALTER TABLE {$prefix}booking_diagnostic MODIFY address TEXT NOT NULL");
        DB::statement("ALTER TABLE {$prefix}booking_diagnostic MODIFY additional_notes TEXT NULL");
        DB::statement("ALTER TABLE {$prefix}test_results MODIFY file_path VARCHAR(255) NOT NULL");
    }
};
