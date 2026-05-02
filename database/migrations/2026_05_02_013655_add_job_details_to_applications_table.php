<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('location')->nullable()->after('job_url');
            $table->enum('work_type', ['remote', 'onsite', 'hybrid'])->nullable()->after('location');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship', 'freelance'])->nullable()->after('work_type');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['location', 'work_type', 'employment_type']);
        });
    }
};
