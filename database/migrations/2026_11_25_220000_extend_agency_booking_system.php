<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_seats', 'nationality_code')) {
                $table->string('nationality_code', 10)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('booking_seats', 'identity_version')) {
                $table->string('identity_version', 30)->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'identity_issue_place')) {
                $table->string('identity_issue_place', 191)->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'identity_expiry_date')) {
                $table->date('identity_expiry_date')->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'companion_id')) {
                $table->unsignedBigInteger('companion_id')->nullable()->index();
            }
            if (!Schema::hasColumn('booking_seats', 'bed_type')) {
                $table->string('bed_type', 50)->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'extra_bed_quantity')) {
                $table->unsignedTinyInteger('extra_bed_quantity')->default(0);
            }
            if (!Schema::hasColumn('booking_seats', 'extra_bed_unit_price')) {
                $table->decimal('extra_bed_unit_price', 10, 2)->default(0);
            }
        });

        Schema::table('companions', function (Blueprint $table) {
            if (!Schema::hasColumn('companions', 'nationality_code')) {
                $table->string('nationality_code', 10)->nullable();
            }
            if (!Schema::hasColumn('companions', 'identity_version')) {
                $table->string('identity_version', 30)->nullable();
            }
            if (!Schema::hasColumn('companions', 'identity_issue_place')) {
                $table->string('identity_issue_place', 191)->nullable();
            }
            if (!Schema::hasColumn('companions', 'identity_expiry_date')) {
                $table->date('identity_expiry_date')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nationality_code')) {
                $table->string('nationality_code', 10)->nullable();
            }
            if (!Schema::hasColumn('users', 'loyalty_points')) {
                $table->unsignedInteger('loyalty_points')->default(0);
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'discount_code')) {
                $table->string('discount_code', 50)->nullable();
            }
            if (!Schema::hasColumn('bookings', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('bookings', 'points_redeemed')) {
                $table->unsignedInteger('points_redeemed')->default(0);
            }
            if (!Schema::hasColumn('bookings', 'points_value')) {
                $table->decimal('points_value', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('bookings', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('bookings', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0);
            }
        });

        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'extra_bed_price')) {
                $table->decimal('extra_bed_price', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('trips', 'max_extra_beds')) {
                $table->unsignedTinyInteger('max_extra_beds')->default(1);
            }
        });

        if (!Schema::hasTable('booking_custom_services')) {
            Schema::create('booking_custom_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('booking_seat_id')->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total_price', 10, 2)->default(0);
                $table->boolean('applies_to_booking')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('discount_codes')) {
            Schema::create('discount_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->enum('type', ['fixed', 'percent']);
                $table->decimal('value', 10, 2);
                $table->decimal('max_discount', 10, 2)->nullable();
                $table->decimal('min_booking_amount', 10, 2)->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('max_uses_per_customer')->nullable();
                $table->unsignedInteger('uses_count')->default(0);
                $table->json('agency_ids')->nullable();
                $table->json('trip_ids')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('discount_code_usages')) {
            Schema::create('discount_code_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_code_id')->constrained('discount_codes')->cascadeOnDelete();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('customer_phone', 30)->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->timestamps();
                $table->unique(['discount_code_id', 'booking_id']);
            });
        }

        if (!Schema::hasTable('booking_invoices')) {
            Schema::create('booking_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('agency_id')->index();
                $table->string('invoice_number', 40)->unique();
                $table->timestamp('issued_at');
                $table->json('snapshot');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('discount_total', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('remaining_amount', 12, 2)->default(0);
                $table->string('issued_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_invoice_items')) {
            Schema::create('booking_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_invoice_id')->constrained('booking_invoices')->cascadeOnDelete();
                $table->string('item_type', 50);
                $table->string('label');
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_change_logs')) {
            Schema::create('booking_change_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->string('action', 80);
                $table->string('entity_type', 80)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('reason')->nullable();
                $table->string('performed_by')->nullable();
                $table->string('actor_type', 30)->nullable();
                $table->string('source', 50)->nullable();
                $table->timestamps();
                $table->index(['booking_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('customer_point_transactions')) {
            Schema::create('customer_point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->unsignedBigInteger('booking_id')->nullable()->index();
                $table->string('transaction_type', 30);
                $table->integer('points');
                $table->decimal('monetary_value', 10, 2)->default(0);
                $table->integer('balance_before')->default(0);
                $table->integer('balance_after')->default(0);
                $table->string('reference', 80)->nullable()->unique();
                $table->text('notes')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_message_logs')) {
            Schema::create('booking_message_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('channel', 20);
                $table->string('phone', 30)->nullable();
                $table->text('body');
                $table->string('template_key', 50)->nullable();
                $table->string('status', 30)->default('queued');
                $table->string('provider_message_id')->nullable();
                $table->text('failure_reason')->nullable();
                $table->string('sent_by')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_access_tokens')) {
            Schema::create('booking_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->string('token', 64)->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_access_tokens');
        Schema::dropIfExists('booking_message_logs');
        Schema::dropIfExists('customer_point_transactions');
        Schema::dropIfExists('booking_change_logs');
        Schema::dropIfExists('booking_invoice_items');
        Schema::dropIfExists('booking_invoices');
        Schema::dropIfExists('discount_code_usages');
        Schema::dropIfExists('discount_codes');
        Schema::dropIfExists('booking_custom_services');

        Schema::table('booking_seats', function (Blueprint $table) {
            foreach ([
                'nationality_code', 'identity_version', 'identity_issue_place', 'identity_expiry_date',
                'date_of_birth', 'companion_id', 'bed_type', 'extra_bed_quantity', 'extra_bed_unit_price',
            ] as $column) {
                if (Schema::hasColumn('booking_seats', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('companions', function (Blueprint $table) {
            foreach (['nationality_code', 'identity_version', 'identity_issue_place', 'identity_expiry_date'] as $column) {
                if (Schema::hasColumn('companions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['nationality_code', 'loyalty_points'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            foreach ([
                'discount_code', 'discount_amount', 'points_redeemed', 'points_value', 'subtotal_amount', 'tax_amount',
            ] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('trips', function (Blueprint $table) {
            foreach (['extra_bed_price', 'max_extra_beds'] as $column) {
                if (Schema::hasColumn('trips', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
