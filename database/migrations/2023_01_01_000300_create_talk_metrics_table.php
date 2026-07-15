<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talk_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talk_id')->constrained('talks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // 1 = view, 2 = download (see App\Enums\MetricType).
            $table->unsignedTinyInteger('metric_type');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            // Requests flagged as bot/crawler traffic are stored but excluded from stats.
            $table->boolean('is_bot')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index(['talk_id', 'metric_type', 'is_bot']);
            $table->index(['metric_type', 'is_bot', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talk_metrics');
    }
};
