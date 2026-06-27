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
        Schema::create('chapters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('manga_id')->index();
            $table->string('title')->nullable();
            $table->string('chapter_number');
            $table->string('volume_number')->nullable();
            $table->string('language')->default('en');
            $table->string('hash')->nullable();
            $table->json('pages')->nullable();
            $table->json('pages_saver')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->unique(['manga_id', 'chapter_number', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
