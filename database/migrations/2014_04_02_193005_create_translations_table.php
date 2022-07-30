<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(config('translation-manager.connection'))
            ->create('ltm_translations', static function (Blueprint $table) {
                $table->collation = 'utf8mb4_bin';
                $table->bigIncrements('id');
                $table->string('locale');
                $table->string('group');
                $table->text('key');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['locale','group','key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('translation-manager.connection'))->drop('ltm_translations');
    }
};
