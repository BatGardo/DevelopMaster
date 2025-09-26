<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Якщо таблиці cases ще немає — створимо базову
        if (!Schema::hasTable('cases')) {
            Schema::create('cases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // власник
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('new'); // new|in_progress|done|closed
                $table->foreignId('executor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('claimant_name')->nullable();
                $table->string('debtor_name')->nullable();
                $table->timestamp('deadline_at')->nullable();
                $table->timestamps();
            });
            return; // все створили — на цьому кроку більше нічого не треба
        }

        // 2) Таблиця існує — додаємо відсутні колонки по одній
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases','status')) {
                $table->string('status')->default('new');
            }
            if (!Schema::hasColumn('cases','executor_id')) {
                // додаємо колонку без констрейнта
                $table->unsignedBigInteger('executor_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('cases','claimant_name')) {
                $table->string('claimant_name')->nullable();
            }
            if (!Schema::hasColumn('cases','debtor_name')) {
                $table->string('debtor_name')->nullable();
            }
            if (!Schema::hasColumn('cases','deadline_at')) {
                $table->timestamp('deadline_at')->nullable();
            }
        });

        // 3) Додаємо зовнішній ключ окремо і тільки якщо його ще немає
        // для Postgres перевіримо існування констрейнта через information_schema
        if (Schema::hasColumn('cases','executor_id')) {
            $constraintExists = DB::selectOne("
                SELECT COUNT(*) AS c
                FROM information_schema.table_constraints tc
                WHERE tc.table_name = 'cases'
                  AND tc.constraint_type = 'FOREIGN KEY'
                  AND tc.constraint_name = 'cases_executor_id_foreign'
            ")->c ?? 0;

            if (!$constraintExists) {
                Schema::table('cases', function (Blueprint $table) {
                    // переконаймося, що індексу/констрейнта ще нема
                    try {
                        $table->foreign('executor_id')
                              ->references('id')->on('users')
                              ->onDelete('set null');
                    } catch (\Throwable $e) {
                        // ігноруємо, якщо вже додано іншим шляхом
                    }
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cases')) {
            // спочатку знімаємо FK, якщо він є
            try {
                Schema::table('cases', function (Blueprint $table) {
                    $table->dropForeign('cases_executor_id_foreign');
                });
            } catch (\Throwable $e) { /* already dropped */ }

            // далі дропаємо додані поля (якщо вони існують)
            Schema::table('cases', function (Blueprint $table) {
                foreach (['status','executor_id','claimant_name','debtor_name','deadline_at'] as $col) {
                    if (Schema::hasColumn('cases', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
