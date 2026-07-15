<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->longText('description')->nullable();
            $table->date('first_recording_date')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('playlist_talk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained('playlists')->cascadeOnDelete();
            $table->foreignId('talk_id')->constrained('talks')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['playlist_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_talk');
        Schema::dropIfExists('playlists');
    }
};
