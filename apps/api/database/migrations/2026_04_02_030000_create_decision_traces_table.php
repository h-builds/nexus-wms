<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_traces', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('trace_type', 50);
            $table->string('agent_id', 100);
            $table->string('agent_domain', 50);

            $table->text('detection');
            $table->text('reasoning');
            $table->text('suggestion');

            $table->string('severity', 20);

            $table->string('causation_id');
            $table->string('correlation_id');

            $table->json('trigger_event_ids');

            $table->string('status', 30)->default('advisory');

            $table->timestamp('acted_upon_at')->nullable();
            $table->string('acted_upon_by')->nullable();

            $table->timestamps();

            $table->index('trace_type');
            $table->index('agent_id');
            $table->index('agent_domain');
            $table->index('severity');
            $table->index('causation_id');
            $table->index('correlation_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_traces');
    }
};
