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

    private function createWithSqlite(): void
    {
        DB::statement('
            CREATE TABLE inventory_movements (
                id TEXT PRIMARY KEY NOT NULL,
                product_id TEXT NOT NULL,
                from_location_id TEXT,
                to_location_id TEXT,
                type TEXT NOT NULL,
                quantity INTEGER NOT NULL,
                reference TEXT,
                lot_number TEXT,
                reason TEXT,
                performed_by TEXT NOT NULL,
                performed_at DATETIME NOT NULL,
                idempotency_key TEXT UNIQUE,
                created_at DATETIME,
                updated_at DATETIME,

                CHECK (quantity > 0)
            )
        ');
        
        DB::statement('CREATE INDEX idx_inventory_movements_product_id ON inventory_movements (product_id)');
        DB::statement('CREATE INDEX idx_inventory_movements_from_location_id ON inventory_movements (from_location_id)');
        DB::statement('CREATE INDEX idx_inventory_movements_to_location_id ON inventory_movements (to_location_id)');
    }

    private function createWithBlueprint(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('product_id')->index();
            $table->uuid('from_location_id')->nullable()->index();
            $table->uuid('to_location_id')->nullable()->index();
            $table->string('type');
            
            // Core attributes
            $table->unsignedInteger('quantity');
            $table->string('reference')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('reason')->nullable();
            
            // Audit and idempotency
            $table->string('performed_by');
            $table->timestamp('performed_at', 6);
            $table->string('idempotency_key')->nullable()->unique();
            
            $table->timestamps();
        });

        DB::statement('ALTER TABLE inventory_movements ADD CONSTRAINT chk_inventory_movements_quantity CHECK (quantity > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
