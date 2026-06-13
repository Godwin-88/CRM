<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('po_number')->unique();
            $table->foreignUlid('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->string('category')->nullable();
            $table->string('currency', 3)->default('KES');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->date('required_by_date');
            $table->date('approved_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('po_number');
            $table->index('required_by_date');
        });

        Schema::create('po_line_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number');
            $table->date('receipt_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('po_line_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('received_quantity', 15, 2);
            $table->timestamps();
        });

        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('vendor_invoice_number');
            $table->decimal('total', 15, 2);
            $table->date('invoice_date');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_invoice_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('vendor_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('po_line_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('invooiced_quantity', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_invoice_items');
        Schema::dropIfExists('vendor_invoices');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('po_line_items');
        Schema::dropIfExists('purchase_orders');
    }
};