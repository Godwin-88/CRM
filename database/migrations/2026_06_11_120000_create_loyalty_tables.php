<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('currency_label', 50)->default('points');
            $table->boolean('is_active')->default(true);
            $table->string('expiry_policy')->default('never'); // never | inactivity_months | fixed_date
            $table->unsignedInteger('expiry_inactivity_months')->nullable();
            $table->date('expiry_fixed_date')->nullable();
            $table->json('matching_rules')->nullable(); // contact type or segment matching
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active']);
        });

        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->unsignedInteger('min_points_threshold')->default(0);
            $table->json('benefits')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['program_id', 'min_points_threshold']);
        });

        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->enum('type', [
                'points_per_currency',
                'points_per_interaction',
                'points_for_profile_completion',
                'bonus_points_event',
            ]);
            $table->string('name');
            $table->json('config')->nullable(); // e.g. interaction type, event date, currency link
            $table->unsignedInteger('points_amount')->default(0);
            $table->decimal('multiplier', 3, 2)->default(1.00); // e.g. 2.00 for double points
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['program_id', 'type', 'is_active']);
        });

        Schema::create('loyalty_redemption_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->enum('type', ['discount_voucher', 'free_product', 'tier_upgrade_credit']);
            $table->string('name');
            $table->json('config')->nullable(); // voucher value, product id, etc.
            $table->unsignedInteger('min_points_threshold');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['program_id', 'type', 'is_active']);
        });

        Schema::create('loyalty_enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->date('enrolled_at');
            $table->date('unenrolled_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'contact_id']);
            $table->index(['contact_id', 'is_active']);
        });

        Schema::create('points_ledgers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('enrollment_id')->constrained('loyalty_enrollments')->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignUlid('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->integer('points_amount');
            $table->integer('running_balance');
            $table->string('description')->nullable();
            $table->string('triggered_by_event')->nullable(); // deal_closed, interaction, manual, redemption, expiry
            $table->json('metadata')->nullable();
            $table->timestamp('transaction_date');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason_note')->nullable(); // for manual adjustments
            $table->timestamps();

            $table->index(['enrollment_id', 'transaction_date']);
            $table->index(['contact_id', 'transaction_date']);
            $table->index(['triggered_by_event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_ledgers');
        Schema::dropIfExists('loyalty_enrollments');
        Schema::dropIfExists('loyalty_redemption_rules');
        Schema::dropIfExists('loyalty_rules');
        Schema::dropIfExists('loyalty_tiers');
        Schema::dropIfExists('loyalty_programs');
    }
};
