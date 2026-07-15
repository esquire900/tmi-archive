<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->longText('description')->nullable();
            // Relative path (e.g. "8/original.mp3") under the media host.
            $table->string('audio_original')->nullable();
            $table->string('audio_cleaned')->nullable();
            $table->date('recorded_date')->nullable();
            $table->string('original_file_name', 300)->nullable();
            // Plain-text transcription (legacy "[timestamp] text" lines).
            $table->longText('transcription')->nullable();
            // Whisper JSON output (array of {start,end,text}).
            $table->longText('whisper_transcription')->nullable();
            // Audio length in whole seconds.
            $table->unsignedInteger('audio_length')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('recorded_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talks');
    }
};
