<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_outbox', function (Blueprint $table) {
            $table->timestamp('dispatched_at', 6)->nullable()->after('dispatched')->index();
        });
    }

    public function down(): void
    {
        Schema::table('event_outbox', function (Blueprint $table) {
            $table->dropColumn('dispatched_at');
        });
    }
};
