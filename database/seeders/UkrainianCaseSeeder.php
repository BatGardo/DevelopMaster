<?php

namespace Database\Seeders;

use App\Models\CaseAction;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UkrainianCaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('TRUNCATE TABLE case_documents, case_actions, cases RESTART IDENTITY CASCADE');

        $executors = User::whereIn('role', ['executor', 'admin'])->get();
        $owners = User::whereIn('role', ['applicant', 'viewer', 'admin'])->get();

        if ($executors->isEmpty()) {
            $executors = User::factory()->count(3)->executor()->create();
        }

        if ($owners->isEmpty()) {
            $owners = User::factory()->count(3)->applicant()->create();
        }

        $faker = fake('uk_UA');

        $statusPool = collect(array_merge(
            array_fill(0, 120, 'new'),
            array_fill(0, 180, 'in_progress'),
            array_fill(0, 100, 'done'),
            array_fill(0, 100, 'closed')
        ));

        $prefixes = [
            'Позов про',
            'Заява щодо',
            'Скарга на',
            'Провадження у справі про',
            'Клопотання про'
        ];

        $subjects = [
            'стягнення заборгованості за договором поставки',
            'розірвання договору оренди комерційного приміщення',
            'оскарження податкового повідомлення-рішення',
            'визнання недійсним рішення загальних зборів учасників',
            'відшкодування збитків за невиконання контракту',
            'накладення арешту на корпоративні права боржника',
            'витребування майна із чужого незаконного володіння',
            'захист ділової репутації юридичної особи',
            'відновлення строку на апеляційне оскарження',
            'визнання права власності на об’єкт незавершеного будівництва',
            'визнання виконавчого напису нотаріуса таким, що не підлягає виконанню',
            'банкрутство боржника та введення процедури санації',
            'спростування інформації у засобах масової інформації',
            'забезпечення позову шляхом арешту нерухомості',
            'скасування результатів електронних торгів'
        ];

        $descriptionTemplates = [
            'Суть спору полягає у {subject}. Представляємо інтереси клієнта у судовому провадженні, готуємо процесуальні документи та координуємо збір доказів.',
            'Компанія звернулася з питанням щодо {subject}. Наразі триває опрацювання позиції та формування доказової бази для подальших засідань.',
            'Отримали доручення на супровід справи щодо {subject}. Завдання включає підготовку процесуальних документів і взаємодію зі сторонами спору.',
            'Клієнт потребує захисту інтересів у спорі про {subject}. Забезпечуємо стратегічне планування, комунікацію з судом та контроль виконання ухвал.'
        ];

        $progressNotes = [
            'Підготовлено та подано клопотання про витребування доказів.',
            'Суд задовольнив клопотання про виклик свідка.',
            'Отримано ухвалу про відкриття провадження.',
            'Сформовано та направлено запит до контролюючого органу.',
            'Підготовлено позицію для судового засідання та погоджено з клієнтом.'
        ];

        $completionNotes = [
            'Суд ухвалив рішення на користь клієнта, стягнуто суму боргу.',
            'Затверджено мирову угоду, сторони погодили графік платежів.',
            'Отримано рішення про зняття арешту з майна.',
            'Постановою суду залишено без розгляду у зв’язку з відмовою від позову.',
            'Виконавче провадження завершено, кошти перераховано клієнту.'
        ];

        $start = Carbon::create(2025, 9, 1, 0, 0, 0, 'UTC');
        $end = Carbon::create(2025, 9, 30, 23, 59, 59, 'UTC');

        for ($i = 0; $i < 500; $i++) {
            $owner = $owners->random();
            $executor = $executors->random();
            $status = $statusPool->random();

            $createdAt = Carbon::instance($faker->dateTimeBetween($start, $end));
            $updatedAt = (clone $createdAt);

            if (in_array($status, ['in_progress', 'done', 'closed'])) {
                $updatedAt = Carbon::instance($faker->dateTimeBetween($createdAt, $end));
            }

            $deadline = $faker->optional(0.7)->dateTimeBetween($createdAt, (clone $createdAt)->addDays(30));

            $subject = Arr::random($subjects);
            $title = Arr::random($prefixes) . ' ' . $subject;
            $descriptionTemplate = Arr::random($descriptionTemplates);
            $description = str_replace('{subject}', $subject, $descriptionTemplate);

            $case = new CaseModel([
                'title' => $title,
                'description' => $description,
                'user_id' => $owner->id,
                'status' => $status,
                'executor_id' => $executor->id,
                'claimant_name' => $faker->company(),
                'debtor_name' => $faker->name(),
                'deadline_at' => $deadline ? Carbon::instance($deadline) : null,
            ]);

            $case->created_at = $createdAt;
            $case->updated_at = $updatedAt;
            $case->save();

            CaseAction::create([
                'case_id' => $case->id,
                'user_id' => $owner->id,
                'type' => 'created',
                'notes' => 'Справу зареєстровано, підготовлено позовну заяву та пакет доказів.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($status === 'in_progress') {
                $this->addProgressActions($case, $executor, $createdAt, $progressNotes, $faker);
            }

            if (in_array($status, ['done', 'closed'])) {
                $this->addCompletionActions($case, $executor, $createdAt, $updatedAt, $completionNotes, $faker, $status);
            }
        }
    }

    protected function addProgressActions(CaseModel $case, User $executor, Carbon $createdAt, array $notesPool, $faker): void
    {
        $actionTime = (clone $createdAt)->addDays(rand(2, 8));

        CaseAction::create([
            'case_id' => $case->id,
            'user_id' => $executor->id,
            'type' => 'document_added',
            'notes' => 'Подано клопотання про забезпечення позову.',
            'created_at' => $actionTime,
            'updated_at' => $actionTime,
        ]);

        if ($faker->boolean(60)) {
            $reminderTime = (clone $actionTime)->addDays(rand(1, 5));
            CaseAction::create([
                'case_id' => $case->id,
                'user_id' => $executor->id,
                'type' => 'reminder_sent',
                'notes' => Arr::random($notesPool),
                'created_at' => $reminderTime,
                'updated_at' => $reminderTime,
            ]);
        }
    }

    protected function addCompletionActions(CaseModel $case, User $executor, Carbon $createdAt, Carbon $updatedAt, array $notesPool, $faker, string $status): void
    {
        $midPoint = Carbon::instance($faker->dateTimeBetween($createdAt, $updatedAt));

        CaseAction::create([
            'case_id' => $case->id,
            'user_id' => $executor->id,
            'type' => 'document_added',
            'notes' => 'Додано ухвалу суду про призначення розгляду по суті.',
            'created_at' => $midPoint,
            'updated_at' => $midPoint,
        ]);

        $finalType = $status === 'closed' ? 'asset_arrest' : 'payment_received';
        $finalNote = Arr::random($notesPool);

        CaseAction::create([
            'case_id' => $case->id,
            'user_id' => $executor->id,
            'type' => $finalType,
            'notes' => $finalNote,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
    }
}
