<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type'); // 'prediction', 'suggestion', 'stat'
            $table->string('input_text')->nullable();
            $table->string('prediction')->nullable(); // 'completed' or 'pending'
            $table->integer('confidence')->nullable(); // 0-100
            $table->integer('completion_likelihood')->nullable(); // 0-100
            $table->json('meta')->nullable(); // any extra data
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
