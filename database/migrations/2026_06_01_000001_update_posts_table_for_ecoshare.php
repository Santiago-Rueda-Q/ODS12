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
        Schema::table('posts', function (Blueprint $table) {
            $table->string('impacto_estimado', 200)->nullable()->after('description');
            $table->integer('eco_score')->default(0)->after('impacto_estimado');
            $table->json('eco_analysis')->nullable()->after('eco_score');
            
            // Check if analysis_status and status already exist from previous migrations
            if (!Schema::hasColumn('posts', 'analysis_status')) {
                $table->string('analysis_status')->default('pending');
            }
            if (!Schema::hasColumn('posts', 'status')) {
                $table->string('status')->default('active');
            }

            if (Schema::hasColumn('posts', 'food')) {
                $table->dropColumn('food');
            }
            if (Schema::hasColumn('posts', 'drink')) {
                $table->dropColumn('drink');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('food')->nullable();
            $table->string('drink')->nullable();
            $table->dropColumn(['impacto_estimado', 'eco_score', 'eco_analysis']);
        });
    }
};
