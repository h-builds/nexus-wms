<?php

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
        Schema::create('event_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->unsignedSmallInteger('event_version');
            $table->timestamp('occurred_at', 6);
            $table->string('actor_id');
            $table->string('correlation_id')->index();
            $table->string('causation_id')->index();
            $table->json('payload');
            $table->boolean('dispatched')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_outbox');
    }
};
