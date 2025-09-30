<?php

namespace Database\Seeders;

use App\Models\CaseAction;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AdditionalUkrainianCasesSeeder extends Seeder
{
    private const TOTAL_NEW_CASES = 564;

    private array $statusDistribution = [
        'new' => 150,
        'in_progress' => 200,
        'done' => 120,
        'closed' => 94,
    ];

    private array $caseTopics = [
        'pozov pro stjagnennya borgu za kredytnym dogovorom',
        'sprava pro arest rukhomogo majna promyslovogo kombajnu',
        'klopotannya pro pryznachennya ekspertyzy finansovyh dokumentiv',
        'skarga shchodo nesvochasnogo vykonannya derzhavnogo kontraktu',
        'provadzhennya pro vstanovlennya prava vlasnosti na skhidni skladu',
        'pozov pro vznannya nedijsnym aktu prymusovoyi realizaciyi',
        'sprava pro rozglyad zvernennya kredytora do zastavnogo majna',
        'klopotannya pro zabezpechennya dokaziv shchodo prohresu rekonstrukciyi',
        'sprava pro zaboronu vydachu licenziyi na eksport tonkoj stalji',
        'pozov pro usunennya pereshkod u vykorystanni prychalnoyi liniyi',
        'sprava pro rozirvannya dogovoru orendy torgovogo centra',
        'klopotannya pro arest rahunkiv pidpryyemstva postachalnyka',
        'sprava pro stjagnennya zbytiv vid prostoyu logistichnoyi bazy',
        'pozov pro okhoronu komercijnoj tajny ta arest serveriv',
        'sprava pro pereglyad rishennya shchodo nevykonannya arbitrazhu',
    ];

    private array $descriptionFragments = [
        'Case team is collecting financial statements and preparing a verified evidence log.',
        'Focus is on freezing collateral through coordinated requests to national registries.',
        'Additional monitoring of court rulings is underway to track related enforcement files.',
        'Mediation unit is preparing an out-of-court roadmap backed by compliance reviews.',
        'Analysts reconstructed transaction flows and drafted recommendations for new claims.',
        'Supervisory board is submitting a complaint to the competition authority for safeguards.',
        'Audit subgroup is checking movable assets and vendor obligations for hidden risks.',
        'Communications plan covers weekly updates with stakeholders and risk briefings.',
        'Coordination desk keeps contact with law enforcement units for operative follow-up.',
        'Technical specialists assess storage conditions for seized industrial equipment.',
        'Finance desk aligns banking partners and insurers on recovery milestones.',
        'Auction preparation checklist is issued for the sale of arrested inventory.',
    ];

    private array $claimants = [
        'TOV "DniproInvest"',
        'AT "UkrLease"',
        'PrAT "EnergoGarant"',
        'KP "MiskTransService"',
        'DP "PivdenMetal"',
        'AO "LexControl"',
        'PF "Fund of Stabilization"',
        'PP "Consulting Group Rada"',
        'FOP Andriy Mazur',
        'TOV "Nordic Trade UA"',
    ];

    private array $debtors = [
        'TOV "Karpaty Logistics"',
        'PP "AgroLviv"',
        'FOP Olha Chernysh',
        'PrAT "Prombudholding"',
        'DP "Stal Invest"',
        'TOV "Marshal Freight"',
        'FOP Serhii Danylko',
        'TOB "Rynok Servis"',
        'PrAT "SvitloTekh"',
        'FOP Mariya Savchuk',
    ];

    private array $progressNotes = [
        'Executor reviewed asset registers and refreshed the freeze list with current balances.',
        'Support team requested supplementary certificates from the tax authority for the file.',
        'Draft notices for the next mediation round were prepared together with a senior expert.',
        'Digital contract set was exported from the archive and indexed for rapid reference.',
        'Court register monitoring is active to capture every companion proceeding in time.',
        'Independent valuers scheduled an appraisal of pledged machinery and transport.',
        'Financial intelligence requests were filed to trace suspect money movements.',
    ];

    private array $completionNotes = [
        'Court approved a settlement with partial repayment and liquidated damages.',
        'Compensation transfer completed and the final acceptance report was signed.',
        'Seized goods were assigned to state storage following the arrest order.',
        'Lending bank confirmed credit closure and released the long-term mortgage.',
        'Bailiffs recorded asset disposal via the ProZorro.Sale auction process.',
        'Parties signed a protocol confirming full performance and case closure.',
    ];

    private array $actionTypes = ['document_added', 'notice_sent', 'reminder_sent', 'asset_arrest', 'payment_received'];

    public function run(): void
    {
        $totalPlanned = array_sum($this->statusDistribution);
        if ($totalPlanned !== self::TOTAL_NEW_CASES) {
            $this->statusDistribution['new'] += self::TOTAL_NEW_CASES - $totalPlanned;
        }

        $executors = User::whereIn('role', ['executor', 'admin'])->get();
        $owners = User::whereIn('role', ['applicant', 'viewer', 'admin'])->get();

        if ($executors->isEmpty()) {
            $executors = User::factory()->count(3)->executor()->create();
        }

        if ($owners->isEmpty()) {
            $owners = User::factory()->count(3)->applicant()->create();
        }

        $faker = fake('en_US');
        $now = Carbon::now();
        $sequence = 1;

        foreach ($this->statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++, $sequence++) {
                $createdAt = Carbon::instance($faker->dateTimeBetween('-11 months', '-2 months'));
                $updatedAt = (clone $createdAt)->addDays($faker->numberBetween(5, 160));
                if ($status === 'new') {
                    $updatedAt = (clone $createdAt)->addDays($faker->numberBetween(0, 10));
                }
                if ($updatedAt->greaterThan($now)) {
                    $updatedAt = $now->copy()->subDays($faker->numberBetween(0, 10));
                }

                $deadline = (clone $createdAt)->addDays($faker->numberBetween(20, 150));
                if (in_array($status, ['done', 'closed'], true) && $deadline->greaterThan($updatedAt)) {
                    $deadline = (clone $updatedAt)->subDays($faker->numberBetween(1, 7));
                }
                if ($deadline->lessThan($createdAt)) {
                    $deadline = (clone $createdAt)->addDays($faker->numberBetween(5, 20));
                }

                $owner = $owners->random();
                $executor = $executors->random();
                $assignedExecutorId = ($status === 'new' && $faker->boolean(40)) ? null : $executor->id;

                $title = sprintf('[SUPP-2025 %03d] %s', $sequence, $this->buildTitle($faker));
                if (CaseModel::where('title', $title)->exists()) {
                    continue;
                }

                $description = $this->composeDescription();

                $case = new CaseModel([
                    'user_id' => $owner->id,
                    'executor_id' => $assignedExecutorId,
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                    'claimant_name' => Arr::random($this->claimants),
                    'debtor_name' => Arr::random($this->debtors),
                    'deadline_at' => $deadline,
                ]);
                $case->created_at = $createdAt;
                $case->updated_at = $updatedAt;
                $case->save();

                $this->seedActionsForCase($case, $executor, $status, $createdAt, $updatedAt, $faker);
            }
        }
    }

    private function buildTitle($faker): string
    {
        $topic = Arr::random($this->caseTopics);
        $region = Arr::random(['Kyiv', 'Lviv', 'Odesa', 'Dnipro', 'Kharkiv', 'Mykolaiv', 'Zakarpattia', 'Vinnytsia']);
        $ref = strtoupper($faker->bothify('ref-??##'));

        return ucfirst($topic) . ' (' . $region . ', ' . $ref . ')';
    }

    private function composeDescription(): string
    {
        $segments = Arr::random($this->descriptionFragments, 2);
        return implode(' ', $segments);
    }

    private function seedActionsForCase(CaseModel $case, User $executor, string $status, Carbon $createdAt, Carbon $updatedAt, $faker): void
    {
        if ($status === 'new') {
            if ($case->executor_id) {
                $this->createAction(
                    $case,
                    $executor->id,
                    'document_added',
                    'Initial bundle of materials received and shared with the working group.',
                    (clone $createdAt)->addDays($faker->numberBetween(1, 4))
                );
            }
            return;
        }

        $actionCount = $status === 'in_progress' ? $faker->numberBetween(2, 4) : $faker->numberBetween(3, 5);
        $window = max(10, $updatedAt->diffInDays($createdAt));

        for ($step = 0; $step < $actionCount; $step++) {
            $segment = (int) floor($window / max(2, $actionCount));
            $from = $segment * $step;
            $to = $segment * ($step + 1) + 1;
            $actionTime = (clone $createdAt)->addDays($faker->numberBetween($from, $to));
            if ($actionTime->greaterThan($updatedAt)) {
                $actionTime = (clone $updatedAt)->subDays($faker->numberBetween(0, 2));
            }

            $type = Arr::random($this->actionTypes);
            $note = Arr::random($this->progressNotes);

            $this->createAction($case, $executor->id, $type, $note, $actionTime);
        }

        if (in_array($status, ['done', 'closed'], true)) {
            $finalType = $status === 'closed' ? 'asset_arrest' : 'payment_received';
            $finalNote = Arr::random($this->completionNotes);
            $finalTime = (clone $updatedAt)->subHours($faker->numberBetween(1, 24));
            $this->createAction($case, $executor->id, $finalType, $finalNote, $finalTime);
        }
    }

    private function createAction(CaseModel $case, int $userId, string $type, string $note, Carbon $timestamp): void
    {
        $action = new CaseAction([
            'case_id' => $case->id,
            'user_id' => $userId,
            'type' => $type,
            'notes' => $note,
        ]);

        $action->created_at = $timestamp;
        $action->updated_at = $timestamp;
        $action->save();
    }
}

