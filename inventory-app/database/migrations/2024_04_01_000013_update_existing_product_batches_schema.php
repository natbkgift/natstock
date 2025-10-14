<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_batches')) {
            return;
        }

        if (Schema::hasColumn('product_batches', 'sub_sku')) {
            if ($this->indexExists('product_batches', 'product_batches_product_id_sub_sku_unique')) {
                Schema::table('product_batches', function (Blueprint $table): void {
                    $table->dropUnique('product_batches_product_id_sub_sku_unique');
                });
            }

            if (!Schema::hasColumn('product_batches', 'lot_no')) {
                if ($this->isSqliteConnection()) {
                    Schema::table('product_batches', function (Blueprint $table): void {
                        $table->renameColumn('sub_sku', 'lot_no');
                    });
                } else {
                    DB::statement('ALTER TABLE product_batches CHANGE sub_sku lot_no VARCHAR(16) NOT NULL');
                }
            }
        }

        Schema::table('product_batches', function (Blueprint $table): void {
            if (!Schema::hasColumn('product_batches', 'received_at')) {
                $table->dateTime('received_at')->nullable()->after('qty');
            }
        });

        if ($this->indexExists('product_batches', 'product_batches_product_id_expire_date_index')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->dropIndex('product_batches_product_id_expire_date_index');
            });
        }

        if ($this->indexExists('product_batches', 'product_batches_expire_date_index')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->dropIndex('product_batches_expire_date_index');
            });
        }

        if (!Schema::hasColumn('product_batches', 'lot_no')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->string('lot_no', 16)->after('product_id');
            });
        }

        if (!$this->indexExists('product_batches', 'product_batches_product_id_lot_no_unique')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->unique(['product_id', 'lot_no'], 'product_batches_product_id_lot_no_unique');
            });
        }

        if (!$this->indexExists('product_batches', 'product_batches_product_id_expire_date_index')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->index(['product_id', 'expire_date'], 'product_batches_product_id_expire_date_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_batches')) {
            return;
        }

        if ($this->indexExists('product_batches', 'product_batches_product_id_lot_no_unique')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->dropUnique('product_batches_product_id_lot_no_unique');
            });
        }

        if (Schema::hasColumn('product_batches', 'received_at')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->dropColumn('received_at');
            });
        }

        if (!Schema::hasColumn('product_batches', 'sub_sku')) {
            if ($this->isSqliteConnection()) {
                Schema::table('product_batches', function (Blueprint $table): void {
                    $table->renameColumn('lot_no', 'sub_sku');
                });
            } else {
                DB::statement('ALTER TABLE product_batches CHANGE lot_no sub_sku VARCHAR(64) NOT NULL');
            }
        }

        if (!$this->indexExists('product_batches', 'product_batches_product_id_sub_sku_unique')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->unique(['product_id', 'sub_sku'], 'product_batches_product_id_sub_sku_unique');
            });
        }

        if ($this->indexExists('product_batches', 'product_batches_product_id_expire_date_index')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->dropIndex('product_batches_product_id_expire_date_index');
            });
        }

        if (!$this->indexExists('product_batches', 'product_batches_expire_date_index')) {
            Schema::table('product_batches', function (Blueprint $table): void {
                $table->index('expire_date', 'product_batches_expire_date_index');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if ($this->isSqliteConnection()) {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                $name = is_object($index) ? ($index->name ?? null) : ($index['name'] ?? null);

                if ($name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) AS total FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return ($result->total ?? 0) > 0;
    }

    private function isSqliteConnection(): bool
    {
        return Schema::getConnection()->getDriverName() === 'sqlite';
    }
};
