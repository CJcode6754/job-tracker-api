<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // On MySQL, we need to use a raw statement to update the enum
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('wishlist', 'applied', 'phone_screen', 'interview', 'offer', 'rejected', 'archived') NOT NULL DEFAULT 'wishlist'");
    }

    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('wishlist', 'applied', 'phone_screen', 'interview', 'offer', 'rejected') NOT NULL DEFAULT 'wishlist'");
    }
};
