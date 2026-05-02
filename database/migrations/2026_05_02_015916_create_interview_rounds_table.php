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
        Schema::create('interview_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->date('date')->nullable();
            $table->enum('type', ['technical', 'hr', 'system_design', 'take_home'])->nullable();
            $table->string('interviewer_name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('self_rating')->nullable(); // 1–5
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_rounds');
    }
};
