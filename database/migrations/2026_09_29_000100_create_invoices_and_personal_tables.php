<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Simple key/value store for the business profile printed on bills.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('sent'); // draft|sent|cancelled (paid/partial/overdue are derived)
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->string('tax_label')->nullable();
            $table->text('notes')->nullable();
            $table->string('share_token', 64)->unique();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('rate', 12, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('server_id')->constrained()->nullOnDelete();
        });

        // Personal money, kept apart from the business books.
        Schema::create('personal_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->string('paid_to')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['date', 'category']);
        });

        Schema::create('personal_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('category')->unique();
            $table->decimal('monthly_limit', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_budgets');
        Schema::dropIfExists('personal_expenses');
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('settings');
    }
};
