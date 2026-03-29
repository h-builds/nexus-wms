<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->createWithSqlite();
        } else {
            $this->createWithBlueprint();
        }
    }

    /**
     * SQLite requires CHECK constraints inline in CREATE TABLE.
     * ALTER TABLE ... ADD CONSTRAINT is not supported.
     */
    private function createWithSqlite(): void
    {
        DB::statement('
            CREATE TABLE stock_items (
                id TEXT PRIMARY KEY NOT NULL,
                product_id TEXT NOT NULL,
                location_id TEXT NOT NULL,
                quantity_on_hand INTEGER NOT NULL DEFAULT 0,
                quantity_available INTEGER NOT NULL DEFAULT 0,
                quantity_blocked INTEGER NOT NULL DEFAULT 0,
                lot_number TEXT,
                serial_number TEXT,
                received_at DATETIME,
                expires_at DATETIME,
                status TEXT NOT NULL DEFAULT \'available\',
                version INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME,

                CHECK (quantity_available >= 0),
                CHECK (quantity_blocked >= 0),
                CHECK (quantity_on_hand = quantity_available + quantity_blocked),

                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
                UNIQUE (product_id, location_id, lot_number)
            )
        ');
    }

    /**
     * MySQL / PostgreSQL: use Blueprint for structure, then ALTER for CHECK constraints.
     */
    private function createWithBlueprint(): void
    {
        Schema::create('stock_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('product_id');
            $table->uuid('location_id');
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_blocked')->default(0);
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('available');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->restrictOnDelete();

            $table->unique(['product_id', 'location_id', 'lot_number'], 'stock_items_product_location_lot_unique');
        });

        // CHECK constraints — database-level safety nets
        DB::statement('ALTER TABLE stock_items ADD CONSTRAINT chk_quantity_available_non_negative CHECK (quantity_available >= 0)');
        DB::statement('ALTER TABLE stock_items ADD CONSTRAINT chk_quantity_blocked_non_negative CHECK (quantity_blocked >= 0)');
        DB::statement('ALTER TABLE stock_items ADD CONSTRAINT chk_quantity_on_hand_derivation CHECK (quantity_on_hand = quantity_available + quantity_blocked)');
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
