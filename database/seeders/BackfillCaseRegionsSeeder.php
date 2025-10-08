<?php

namespace Database\Seeders;

use App\Models\CaseModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillCaseRegionsSeeder extends Seeder
{
    public function run(): void
    {
        CaseModel::query()
            ->select(['id', 'title', 'region', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($cases) {
                foreach ($cases as $case) {
                    $resolved = $this->resolveRegion($case->region, $case->title);

                    if ($resolved === $case->region) {
                        continue;
                    }

                    DB::table('cases')
                        ->where('id', $case->id)
                        ->update([
                            'region' => $resolved,
                            'updated_at' => $case->updated_at,
                        ]);
                }
            });
    }

    private function resolveRegion(?string $region, ?string $title): ?string
    {
        $normalized = $this->normalize($region);

        if ($normalized !== null) {
            return $normalized;
        }

        if (empty($title)) {
            return null;
        }

        if (preg_match('/\(([^,]+),/u', $title, $matches)) {
            return $this->normalize($matches[1] ?? null);
        }

        return null;
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::of($value)->squish();

        if ($normalized->isEmpty()) {
            return null;
        }

        return $normalized->title()->value();
    }
}