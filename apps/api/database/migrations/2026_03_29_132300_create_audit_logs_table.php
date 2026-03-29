<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->string('actor_id')->nullable();
            $table->string('actor_type', 50)->default('human');
            
            $table->string('action', 100)->index();
            $table->string('entity_type', 100)->index(); 
            $table->string('entity_id')->index();
            
            $table->json('changeset');
            $table->uuid('correlation_id')->index();
            $table->timestamp('timestamp')->useCurrent()->index();

            // Enforces append-only immutable design by omitting updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For development/MVP we can drop it. In a production state we might refuse dropping.
        Schema::dropIfExists('audit_logs');
    }
};
