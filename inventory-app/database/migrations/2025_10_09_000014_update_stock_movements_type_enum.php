<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            DB::table('stock_movements')->where('type', 'in')->update(['type' => 'receive']);
            DB::table('stock_movements')->where('type', 'out')->update(['type' => 'issue']);

            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('in','out','adjust','receive','issue') NOT NULL COMMENT 'ประเภทการเคลื่อนไหว'");
        DB::statement("UPDATE stock_movements SET type = CASE WHEN type = 'in' THEN 'receive' WHEN type = 'out' THEN 'issue' ELSE type END");
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('receive','issue','adjust') NOT NULL COMMENT 'ประเภทการเคลื่อนไหว'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            DB::table('stock_movements')->where('type', 'receive')->update(['type' => 'in']);
            DB::table('stock_movements')->where('type', 'issue')->update(['type' => 'out']);

            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('in','out','adjust','receive','issue') NOT NULL COMMENT 'ประเภทการเคลื่อนไหว'");
        DB::statement("UPDATE stock_movements SET type = CASE WHEN type = 'receive' THEN 'in' WHEN type = 'issue' THEN 'out' ELSE type END");
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('in','out','adjust') NOT NULL COMMENT 'ประเภทการเคลื่อนไหว'");
    }
};
