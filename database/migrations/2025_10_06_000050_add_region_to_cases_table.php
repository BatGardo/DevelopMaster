<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'region')) {
                $table->string('region', 120)->nullable()->after('executor_id');
            }
        });

        if (!Schema::hasColumn('cases', 'region')) {
            return;
        }

        try {
            DB::statement('CREATE INDEX IF NOT EXISTS cases_region_index ON cases (region)');
        } catch (\Throwable $e) {
            // index might not be supported; ignore and proceed
        }

        DB::table('cases')
            ->select('id', 'region')
            ->whereNotNull('region')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = Str::of($row->region)->squish();
                    $value = $normalized->isEmpty() ? null : $normalized->title()->value();

                    if ($value !== $row->region) {
                        DB::table('cases')->where('id', $row->id)->update(['region' => $value]);
                    }
                }
            });

        DB::table('cases')
            ->select('id', 'title')
            ->whereNull('region')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    if (empty($row->title)) {
                        continue;
                    }

                    if (preg_match('/\(([^,]+),/u', $row->title, $matches)) {
                        $region = Str::of($matches[1])->squish();
                        $value = $region->isEmpty() ? null : $region->title()->value();

                        if ($value !== null) {
                            DB::table('cases')->where('id', $row->id)->update(['region' => $value]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('cases', 'region')) {
            return;
        }

        try {
            DB::statement('DROP INDEX IF EXISTS cases_region_index');
        } catch (\Throwable $e) {
            // ignore if index does not exist or cannot be dropped with this statement
        }

        Schema::table('cases', function (Blueprint $table) {
            if (Schema::hasColumn('cases', 'region')) {
                $table->dropColumn('region');
            }
        });
    }
};