<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pilots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('key_hash', 64)->unique();
            $table->unsignedInteger('monthly_quota');
            $table->boolean('active')->default(true);
            $table->mediumText('logo_base64')->nullable();
        });

        Schema::create('pilot_usage', function (Blueprint $table) {
            $table->foreignId('pilot_id')->constrained('pilots')->cascadeOnDelete();
            $table->string('period', 7);
            $table->unsignedInteger('consumed')->default(0);
            $table->primary(['pilot_id', 'period']);
        });

        Schema::create('renders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('pilot_id')->constrained('pilots')->cascadeOnDelete();
            $table->string('idempotency_key', 100);
            $table->string('request_hash', 64);
            $table->string('state', 20);
            $table->longText('pdf_base64')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('created_at');
            $table->unique(['pilot_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renders');
        Schema::dropIfExists('pilot_usage');
        Schema::dropIfExists('pilots');
    }
};
