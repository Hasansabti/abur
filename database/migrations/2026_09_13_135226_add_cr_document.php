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
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('pending_commercial_id')
                ->nullable()
                ->after('commercial_id');

            $table->date('pending_cr_expiry_date')
                ->nullable()
                ->after('pending_commercial_id');

            $table->string('pending_cr_document_path')
                ->nullable()
                ->after('pending_cr_expiry_date');

            $table->enum('cr_status', [
                'approved',
                'pending',
                'rejected'
            ])
            ->default('approved')
            ->after('pending_cr_document_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'pending_commercial_id',
                'pending_cr_expiry_date',
                'pending_cr_document_path',
                'cr_status',
            ]);
        });
    }
};