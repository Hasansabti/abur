<?php

use App\Support\Nationalities;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nationalities')) {
            Schema::create('nationalities', function (Blueprint $table) {
                $table->id();
                $table->string('iso2', 8)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        foreach (Nationalities::all() as $row) {
            DB::table('nationalities')->updateOrInsert(
                ['iso2' => $row['iso2']],
                [
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        Schema::table('booking_seats', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_seats', 'nationality_id')) {
                $table->foreignId('nationality_id')->nullable()->after('gender')->constrained('nationalities')->nullOnDelete();
            }
            if (!Schema::hasColumn('booking_seats', 'identity_version')) {
                $table->string('identity_version', 50)->nullable()->after('passenger_national_id');
            }
            if (!Schema::hasColumn('booking_seats', 'identity_issue_place')) {
                $table->string('identity_issue_place', 150)->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'identity_expiry_date')) {
                $table->date('identity_expiry_date')->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('booking_seats', 'companion_id')) {
                $table->foreignId('companion_id')->nullable()->constrained('companions')->nullOnDelete();
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
            if (!Schema::hasColumn('booking_seats', 'meal_snapshot')) {
                $table->json('meal_snapshot')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nationality_id')) {
                $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'points_balance')) {
                $table->unsignedInteger('points_balance')->default(0);
            }
        });

        Schema::table('companions', function (Blueprint $table) {
            if (!Schema::hasColumn('companions', 'nationality_id')) {
                $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->nullOnDelete();
            }
            if (!Schema::hasColumn('companions', 'identity_version')) {
                $table->string('identity_version', 50)->nullable();
            }
            if (!Schema::hasColumn('companions', 'identity_issue_place')) {
                $table->string('identity_issue_place', 150)->nullable();
            }
            if (!Schema::hasColumn('companions', 'identity_expiry_date')) {
                $table->date('identity_expiry_date')->nullable();
            }
        });

        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'extra_bed_price')) {
                $table->decimal('extra_bed_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('trips', 'hotel_name')) {
                $table->string('hotel_name')->nullable();
            }
        });

        Schema::table('trip_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('trip_rooms', 'hotel_notes')) {
                $table->text('hotel_notes')->nullable();
            }
            if (!Schema::hasColumn('trip_rooms', 'bed_type')) {
                $table->string('bed_type', 50)->nullable();
            }
            if (!Schema::hasColumn('trip_rooms', 'extra_bed_quantity')) {
                $table->unsignedTinyInteger('extra_bed_quantity')->default(0);
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'discount_code_id')) {
                $table->unsignedBigInteger('discount_code_id')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'discount_code')) {
                $table->string('discount_code', 50)->nullable();
            }
            if (!Schema::hasColumn('bookings', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('bookings', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('bookings', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('bookings', 'points_redeemed')) {
                $table->unsignedInteger('points_redeemed')->default(0);
            }
            if (!Schema::hasColumn('bookings', 'points_value')) {
                $table->decimal('points_value', 10, 2)->default(0);
            }
        });

        if (!Schema::hasTable('booking_custom_services')) {
            Schema::create('booking_custom_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->foreignId('booking_seat_id')->nullable()->constrained('booking_seats')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total_price', 10, 2);
                $table->string('idempotency_key', 64)->nullable();
                $table->timestamps();

                $table->index(['booking_id', 'agency_id']);
                $table->unique(['booking_id', 'idempotency_key']);
            });
        }

        if (!Schema::hasTable('discount_codes')) {
            Schema::create('discount_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
                $table->string('code', 50);
                $table->enum('type', ['fixed', 'percent']);
                $table->decimal('value', 10, 2);
                $table->decimal('max_discount', 10, 2)->nullable();
                $table->decimal('min_booking_amount', 10, 2)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('max_uses_per_customer')->nullable();
                $table->json('eligible_trip_ids')->nullable();
                $table->boolean('stack_with_trip_discounts')->nullable();
                $table->timestamps();

                $table->unique(['agency_id', 'code']);
                $table->index(['code', 'is_active']);
            });
        }

        if (!Schema::hasTable('discount_code_usages')) {
            Schema::create('discount_code_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_code_id')->constrained()->cascadeOnDelete();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
                $table->string('customer_key')->nullable();
                $table->decimal('amount', 10, 2);
                $table->timestamps();

                $table->unique(['discount_code_id', 'booking_id']);
                $table->index(['discount_code_id', 'customer_key']);
            });
        }

        if (Schema::hasColumn('bookings', 'discount_code_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                try {
                    $table->foreign('discount_code_id')->references('id')->on('discount_codes')->nullOnDelete();
                } catch (\Throwable $e) {
                    // already exists
                }
            });
        }

        if (!Schema::hasTable('booking_invoices')) {
            Schema::create('booking_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->timestamp('issued_at');
                $table->json('snapshot');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('discount_total', 10, 2)->default(0);
                $table->decimal('tax_total', 10, 2)->default(0);
                $table->decimal('grand_total', 10, 2)->default(0);
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->decimal('remaining_amount', 10, 2)->default(0);
                $table->string('status')->default('issued');
                $table->foreignId('issued_by')->nullable();
                $table->string('issued_by_name')->nullable();
                $table->string('idempotency_key', 64)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_invoice_items')) {
            Schema::create('booking_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_invoice_id')->constrained('booking_invoices')->cascadeOnDelete();
                $table->string('item_type');
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
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->string('action');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('reason')->nullable();
                $table->string('performed_by')->nullable();
                $table->string('actor_type')->nullable();
                $table->string('source')->nullable();
                $table->timestamps();

                $table->index(['booking_id', 'created_at']);
                $table->index(['agency_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('customer_point_transactions')) {
            Schema::create('customer_point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
                $table->string('transaction_type');
                $table->integer('points');
                $table->decimal('monetary_value', 10, 2)->default(0);
                $table->unsignedInteger('balance_before')->default(0);
                $table->unsignedInteger('balance_after')->default(0);
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->string('created_by')->nullable();
                $table->string('idempotency_key', 64)->nullable();
                $table->timestamps();

                $table->unique(['idempotency_key']);
                $table->index(['customer_id', 'created_at']);
                $table->index(['booking_id', 'transaction_type']);
            });
        }

        if (!Schema::hasTable('booking_message_logs')) {
            Schema::create('booking_message_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->string('channel');
                $table->string('template_key')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('phone', 30);
                $table->text('message');
                $table->string('status')->default('queued');
                $table->string('provider_message_id')->nullable();
                $table->string('delivery_status')->nullable();
                $table->text('failure_reason')->nullable();
                $table->boolean('counts_against_quota')->default(false);
                $table->string('sent_by')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('idempotency_key', 64)->nullable();
                $table->timestamps();

                $table->unique(['idempotency_key']);
                $table->index(['booking_id', 'channel']);
                $table->index(['agency_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('booking_access_tokens')) {
            Schema::create('booking_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
                $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
                $table->string('token', 80)->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamp('last_accessed_at')->nullable();
                $table->timestamps();

                $table->index(['booking_id', 'revoked_at']);
            });
        }

        Schema::table('agency_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('agency_transactions', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable();
            }
            if (!Schema::hasColumn('agency_transactions', 'reference')) {
                $table->string('reference')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agency_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('agency_transactions', 'idempotency_key')) {
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('agency_transactions', 'reference')) {
                $table->dropColumn('reference');
            }
        });

        Schema::dropIfExists('booking_access_tokens');
        Schema::dropIfExists('booking_message_logs');
        Schema::dropIfExists('customer_point_transactions');
        Schema::dropIfExists('booking_change_logs');
        Schema::dropIfExists('booking_invoice_items');
        Schema::dropIfExists('booking_invoices');
        Schema::dropIfExists('discount_code_usages');

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'discount_code_id')) {
                try {
                    $table->dropForeign(['discount_code_id']);
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::dropIfExists('discount_codes');
        Schema::dropIfExists('booking_custom_services');

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['discount_code_id', 'discount_code', 'discount_amount', 'tax_amount', 'subtotal_amount', 'points_redeemed', 'points_value'] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('trip_rooms', function (Blueprint $table) {
            foreach (['hotel_notes', 'bed_type', 'extra_bed_quantity'] as $col) {
                if (Schema::hasColumn('trip_rooms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('trips', function (Blueprint $table) {
            foreach (['extra_bed_price', 'hotel_name'] as $col) {
                if (Schema::hasColumn('trips', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('companions', function (Blueprint $table) {
            if (Schema::hasColumn('companions', 'nationality_id')) {
                $table->dropConstrainedForeignId('nationality_id');
            }
            foreach (['identity_version', 'identity_issue_place', 'identity_expiry_date'] as $col) {
                if (Schema::hasColumn('companions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nationality_id')) {
                $table->dropConstrainedForeignId('nationality_id');
            }
            if (Schema::hasColumn('users', 'points_balance')) {
                $table->dropColumn('points_balance');
            }
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            if (Schema::hasColumn('booking_seats', 'nationality_id')) {
                $table->dropConstrainedForeignId('nationality_id');
            }
            if (Schema::hasColumn('booking_seats', 'companion_id')) {
                $table->dropConstrainedForeignId('companion_id');
            }
            foreach ([
                'identity_version', 'identity_issue_place', 'identity_expiry_date',
                'date_of_birth', 'bed_type', 'extra_bed_quantity', 'extra_bed_unit_price', 'meal_snapshot',
            ] as $col) {
                if (Schema::hasColumn('booking_seats', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('nationalities');
    }
};
