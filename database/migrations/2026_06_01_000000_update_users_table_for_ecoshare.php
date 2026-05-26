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
        Schema::table('users', function (Blueprint $table) {
            $table->string('eco_username')->unique()->nullable()->after('username');
            $table->string('linkedin')->nullable()->after('email');
            if (Schema::hasColumn('users', 'instagram')) {
                $table->dropColumn('instagram');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('instagram')->nullable();
            $table->dropColumn(['eco_username', 'linkedin']);
        });
    }
};
