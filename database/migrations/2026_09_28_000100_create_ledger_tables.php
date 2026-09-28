<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // People who pay me for projects.
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Friends who get a share of ad revenue on some projects.
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('upi_or_bank')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Provider logins: registrars, hosting panels, ad networks, etc.
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('provider');
            $table->string('type')->default('registrar'); // registrar|hosting|ads|payment|other
            $table->string('login_email')->nullable();
            $table->string('dashboard_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('plan')->nullable();
            $table->string('location')->nullable();
            $table->string('billing_cycle')->default('monthly'); // monthly|quarterly|half_yearly|yearly
            $table->decimal('cost', 12, 2)->default(0);
            $table->date('next_due_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('partner_share_percent', 5, 2)->default(0);
            $table->string('type')->default('client'); // client|own|ads
            $table->string('status')->default('active'); // active|paused|completed|cancelled
            $table->decimal('build_fee', 12, 2)->default(0);
            $table->string('billing_cycle')->default('none'); // none|monthly|quarterly|half_yearly|yearly
            $table->decimal('billing_amount', 12, 2)->default(0);
            $table->date('next_billing_date')->nullable();
            $table->date('started_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->date('registered_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->decimal('renewal_cost', 12, 2)->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->string('paid_by')->default('me'); // me|client
            $table->string('status')->default('active'); // active|expired|transferred|dropped
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Every rupee in or out.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('type'); // income|expense
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->decimal('partner_share', 12, 2)->default(0);
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'date']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('clients');
    }
};
