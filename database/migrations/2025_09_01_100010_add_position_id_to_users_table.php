<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'position_id')) {
                $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete()->after('role');
            }
        });

        // якщо вже колись хтось писав у users.position (varchar) — перенесемо
        if (Schema::hasColumn('users', 'position')) {
            // створимо позиції за унікальними значеннями
            $names = DB::table('users')->select('position')->whereNotNull('position')->distinct()->pluck('position');
            foreach ($names as $name) {
                $slug = \Illuminate\Support\Str::of($name)->lower()->slug('_'); // напр. "Головний виконавець" -> "holovnyi_vykonavec"
                $pid = DB::table('positions')->where('slug', $slug)->value('id');
                if (!$pid) {
                    $pid = DB::table('positions')->insertGetId([
                        'name' => $name,
                        'slug' => $slug,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('users')->where('position', $name)->update(['position_id' => $pid]);
            }
        }

        // Мапінг від ролей на типові позиції (на випадок, якщо position пусте)
        $map = [
            'admin'     => ['name' => 'Адміністратор', 'slug' => 'admin'],
            'executor'  => ['name' => 'Виконавець',    'slug' => 'executor'],
            'viewer'    => ['name' => 'Переглядач',    'slug' => 'viewer'],
            'applicant' => ['name' => 'Заявник',       'slug' => 'applicant'],
        ];

        foreach ($map as $role => $p) {
            $pid = DB::table('positions')->where('slug', $p['slug'])->value('id');
            if (!$pid) {
                $pid = DB::table('positions')->insertGetId([
                    'name' => $p['name'],
                    'slug' => $p['slug'],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('users')->whereNull('position_id')->where('role', $role)->update(['position_id' => $pid]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users','position_id')) {
                $table->dropConstrainedForeignId('position_id');
            }
        });
    }
};
