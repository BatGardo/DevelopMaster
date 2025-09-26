<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name'=>'Адміністратор','slug'=>'admin'],
            ['name'=>'Виконавець','slug'=>'executor'],
            ['name'=>'Переглядач','slug'=>'viewer'],
            ['name'=>'Заявник','slug'=>'applicant'],
            ['name'=>'Аналітик','slug'=>'analyst'],
        ];
        foreach ($defaults as $p) {
            DB::table('positions')->updateOrInsert(
                ['slug' => $p['slug']],
                ['name' => $p['name'], 'active'=>true, 'updated_at'=>now(), 'created_at'=>now()]
            );
        }
    }
}
