<?php

namespace Database\Seeders;

use App\Models\CaseAction;
use App\Models\CaseDocument;
use App\Models\CaseModel;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UkrainianCaseSeeder extends Seeder
{
    private const STATUS_DISTRIBUTION = [
        'new' => 420,
        'in_progress' => 480,
        'done' => 360,
        'closed' => 240,
    ];

    private const ACTION_TYPES = [
        'created',
        'comment',
        'document_added',
        'hearing_scheduled',
        'payment_received',
        'reminder_sent',
    ];

    private array $regionPool = [
        'Kyiv', 'Lviv', 'Odesa', 'Dnipro', 'Kharkiv', 'Mykolaiv', 'Zaporizhzhia', 'Chernihiv',
        'Sumy', 'Poltava', 'Cherkasy', 'Vinnytsia', 'Zhytomyr', 'Khmelnytskyi', 'Ivano-Frankivsk',
        'Ternopil', 'Chernivtsi', 'Rivne', 'Volyn', 'Zakarpattia', 'Kirovohrad', 'Luhansk'
    ];

    public function run(): void
    {
        DB::statement('TRUNCATE TABLE case_documents, case_actions, cases RESTART IDENTITY CASCADE');

        $faker = FakerFactory::create('uk_UA');

        $executors = User::whereIn('role', ['executor', 'admin'])->get();
        if ($executors->isEmpty()) {
            $executors = User::factory()->count(5)->executor()->create();
        }

        $owners = User::whereIn('role', ['applicant', 'viewer', 'admin'])->get();
        if ($owners->isEmpty()) {
            $owners = User::factory()->count(5)->applicant()->create();
        }

        $statusPool = collect(self::STATUS_DISTRIBUTION)
            ->flatMap(fn (int $count, string $status) => array_fill(0, $count, $status))
            ->shuffle();

        $prefixes = [
            'Позов щодо',
            'Скарга про',
            'Клопотання про',
            'Провадження стосовно',
            'Заява щодо',
        ];

        $subjects = [
            'стягнення заборгованості за договором поставки',
            'визнання недійсним рішення тендерного комітету',
            'розірвання довгострокового договору оренди',
            'заборону на відчуження промислового обладнання',
            'поновлення строків виконання судового рішення',
            'проведення повторної фінансової експертизи',
            'скасування арешту банківських рахунків',
            'відшкодування збитків через зрив будівельних робіт',
            'забезпечення доказів у господарській справі',
            'повернення авансового платежу за контрактом',
        ];

        $progressNotes = [
            'Отримано додаткові документи від заявника.',
            'Погоджено правову позицію з керівництвом.',
            'Проведено консультацію зі свідками справи.',
            'Сформовано та направлено запит до державних органів.',
            'Уточнено розрахунки збитків та оновлено доказову базу.',
        ];

        $completionNotes = [
            'Справу закрито у зв’язку з виконанням вимог.',
            'Сторони уклали мирову угоду, матеріали передано в архів.',
            'Рішення суду виконано, подальших дій не потрібно.',
            'Адміністративний орган задовольнив вимоги заявника.',
        ];

        $actionNotes = [
            'created' => 'Справу зареєстровано та призначено відповідального виконавця.',
            'document_added' => 'Додано новий документ до матеріалів справи.',
            'payment_received' => 'Підтверджено надходження платежу від відповідача.',
            'reminder_sent' => 'Надіслано нагадування щодо необхідних процесуальних дій.',
        ];

        $documentMimeMap = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
        ];

        $actionRequirements = collect(self::ACTION_TYPES);
        $caseIndex = 0;

        foreach ($statusPool as $status) {
            $caseIndex++;

            $owner = $owners->random();
            $executor = $executors->random();

            $createdAt = Carbon::instance($faker->dateTimeBetween('-9 months', '-10 days'));
            $updatedBoundary = (clone $createdAt)->addMonths(6);
            $updatedAt = Carbon::instance($faker->dateTimeBetween($createdAt, min($updatedBoundary, now())));

            $deadline = null;
            if (in_array($status, ['new', 'in_progress'], true)) {
                $deadline = Carbon::instance($faker->dateTimeBetween('+2 weeks', '+6 months'));
            } elseif ($faker->boolean(40)) {
                $deadline = Carbon::instance($faker->dateTimeBetween($createdAt, '+2 months'));
            }

            $region = $this->normalizeRegion(Arr::random($this->regionPool));
            $title = sprintf('%s %s (%s)', Arr::random($prefixes), Arr::random($subjects), $region);
            $description = $faker->paragraphs($faker->numberBetween(2, 4), true);

            $case = CaseModel::create([
                'title' => Str::ucfirst($title),
                'region' => $region,
                'description' => $description,
                'user_id' => $owner->id,
                'status' => $status,
                'executor_id' => $executor->id,
                'claimant_name' => 'ТОВ «' . Str::title($faker->words(2, true)) . '»',
                'debtor_name' => $faker->name(),
                'deadline_at' => $deadline,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            $actions = collect(self::ACTION_TYPES)->shuffle()->take($faker->numberBetween(3, 5));
            if ($actionRequirements->isNotEmpty()) {
                $actions = $actions->merge([$actionRequirements->shift()]);
            }
            $actions = $actions->prepend('created')->unique()->values();

            $actionMoment = clone $createdAt;
            foreach ($actions as $type) {
                $actionMoment = $actionMoment->addDays($faker->numberBetween(3, 18));
                $note = $actionNotes[$type] ?? $faker->sentence();

                if ($type === 'comment') {
                    $note = $faker->realText($faker->numberBetween(80, 140));
                }

                if ($type === 'hearing_scheduled') {
                    $hearingDate = Carbon::instance($faker->dateTimeBetween('+1 week', '+5 months'));
                    $note = 'Призначено слухання на ' . $hearingDate->format('d.m.Y') . ' о ' . $hearingDate->format('H:i') . '.';
                }

                $action = CaseAction::create([
                    'case_id' => $case->id,
                    'user_id' => $executor->id,
                    'type' => $type,
                    'notes' => $note,
                    'created_at' => $actionMoment,
                    'updated_at' => $actionMoment,
                ]);

                if ($type === 'document_added') {
                    $extension = Arr::random(['pdf', 'docx', 'jpg']);
                    $title = Str::title($faker->words($faker->numberBetween(2, 4), true)) . '.' . $extension;

                    CaseDocument::create([
                        'case_id' => $case->id,
                        'uploaded_by' => $executor->id,
                        'title' => $title,
                        'path' => 'cases/' . $case->id . '/' . Str::uuid() . '.' . $extension,
                        'file_size' => $faker->numberBetween(40_000, 320_000),
                        'mime_type' => $documentMimeMap[$extension],
                        'created_at' => $actionMoment,
                        'updated_at' => $actionMoment,
                    ]);
                }
            }

            if (in_array($status, ['done', 'closed'], true)) {
                CaseAction::create([
                    'case_id' => $case->id,
                    'user_id' => $executor->id,
                    'type' => 'comment',
                    'notes' => Arr::random($completionNotes),
                    'created_at' => $updatedAt->copy()->addDays(1),
                    'updated_at' => $updatedAt->copy()->addDays(1),
                ]);
            } else {
                CaseAction::create([
                    'case_id' => $case->id,
                    'user_id' => $executor->id,
                    'type' => 'comment',
                    'notes' => Arr::random($progressNotes),
                    'created_at' => $updatedAt->copy()->subDays(2),
                    'updated_at' => $updatedAt->copy()->subDays(2),
                ]);
            }
        }
    }

    private function normalizeRegion(string $value): string
    {
        return Str::of($value)->squish()->title()->value();
    }
}
