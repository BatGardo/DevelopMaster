<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection(config('olap.connection'));

        $schema->dropIfExists('fact_user_profile_updates');
        $schema->dropIfExists('fact_user_registrations');
        $schema->dropIfExists('fact_user_logins');
        $schema->dropIfExists('dim_roles');
        $schema->dropIfExists('dim_users');
        $schema->dropIfExists('dim_dates');

        $schema->create('dim_dates', function (Blueprint $table) {
            $table->string('date_key', 8)->primary();
            $table->date('date');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('week_of_year');
            $table->string('day_name', 16);
            $table->string('month_name', 16);
            $table->timestamps();
        });

        $schema->create('dim_users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_key')->primary();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        $schema->create('dim_roles', function (Blueprint $table) {
            $table->string('role_key', 64)->primary();
            $table->string('role_name');
            $table->timestamps();
        });

        $schema->create('fact_user_logins', function (Blueprint $table) {
            $table->id();
            $table->string('date_key', 8)->index();
            $table->unsignedBigInteger('user_key')->nullable()->index();
            $table->string('role_key', 64)->nullable()->index();
            $table->unsignedInteger('login_count')->default(1);
            $table->string('source', 32)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        $schema->create('fact_user_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('date_key', 8)->index();
            $table->unsignedBigInteger('user_key')->nullable()->index();
            $table->string('role_key', 64)->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        $schema->create('fact_user_profile_updates', function (Blueprint $table) {
            $table->id();
            $table->string('date_key', 8)->index();
            $table->unsignedBigInteger('user_key')->nullable()->index();
            $table->string('role_key', 64)->nullable()->index();
            $table->json('changed_columns')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection(config('olap.connection'));

        $schema->dropIfExists('fact_user_profile_updates');
        $schema->dropIfExists('fact_user_registrations');
        $schema->dropIfExists('fact_user_logins');
        $schema->dropIfExists('dim_roles');
        $schema->dropIfExists('dim_users');
        $schema->dropIfExists('dim_dates');
    }
};
