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
        Schema::table('agency_ratings', function (Blueprint $table) {
            $table->integer('accommodation_rating')->nullable()->change();
            $table->integer('meals_rating')->default(0)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('agency_ratings', function (Blueprint $table) {
            $table->integer('accommodation_rating')->nullable(false)->change();
            $table->integer('meals_rating')->default(0)->nullable(false)->change();
        });
    }
};

