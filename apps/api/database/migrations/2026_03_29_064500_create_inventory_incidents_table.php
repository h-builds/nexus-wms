<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
        DB::statement("
            CREATE TABLE inventory_incidents (
                id TEXT PRIMARY KEY NOT NULL,
                product_id TEXT NOT NULL,
                location_id TEXT,
                type TEXT NOT NULL,
                severity TEXT NOT NULL,
                status TEXT NOT NULL,
                description TEXT NOT NULL,
                quantity_affected INTEGER,
                reported_by TEXT NOT NULL,
                assigned_to TEXT,
                notes TEXT,
                idempotency_key TEXT UNIQUE,
                created_at DATETIME,
                updated_at DATETIME,

                CHECK (quantity_affected IS NULL OR quantity_affected >= 0),
                CHECK (type IN ('damage','shortage','overage','expiration','misplacement','broken_packaging','nonconforming_product','picking_blocker','lot_error')),
                CHECK (severity IN ('low','medium','high')),
                CHECK (status IN ('open','in_review','resolved','closed'))
            )
        ");
        
        DB::statement('CREATE INDEX idx_inventory_incidents_product_id ON inventory_incidents (product_id)');
        DB::statement('CREATE INDEX idx_inventory_incidents_location_id ON inventory_incidents (location_id)');
        DB::statement('CREATE INDEX idx_inventory_incidents_reported_by ON inventory_incidents (reported_by)');
    }

    private function createWithBlueprint(): void
    {
        Schema::create('inventory_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->index();
            $table->uuid('location_id')->nullable()->index();
            $table->string('type');
            $table->string('severity');
            $table->string('status');
            $table->text('description');
            $table->integer('quantity_affected')->nullable();
            $table->string('reported_by')->index();
            $table->string('assigned_to')->nullable()->index();
            $table->text('notes')->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE inventory_incidents ADD CONSTRAINT chk_incident_qty_positive CHECK (quantity_affected IS NULL OR quantity_affected >= 0)');
        
        $types = "'damage','shortage','overage','expiration','misplacement','broken_packaging','nonconforming_product','picking_blocker','lot_error'";
        DB::statement("ALTER TABLE inventory_incidents ADD CONSTRAINT chk_incident_type CHECK (type IN ($types))");
        
        $severities = "'low','medium','high'";
        DB::statement("ALTER TABLE inventory_incidents ADD CONSTRAINT chk_incident_severity CHECK (severity IN ($severities))");

        $statuses = "'open','in_review','resolved','closed'";
        DB::statement("ALTER TABLE inventory_incidents ADD CONSTRAINT chk_incident_status CHECK (status IN ($statuses))");
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_incidents');
    }
};
