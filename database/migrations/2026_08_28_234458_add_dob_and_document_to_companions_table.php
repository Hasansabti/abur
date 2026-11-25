<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companions', function (Blueprint $table) {
            $table->date('dob_gregorian')->nullable()->after('birth_date');
            $table->string('id_document_path')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('companions', function (Blueprint $table) {
            $table->dropColumn(['dob_gregorian', 'id_document_path']);
        });
    }
};