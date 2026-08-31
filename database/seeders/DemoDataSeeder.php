<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Assessment;
use App\Models\BreachReport;
use App\Models\CarePlan;
use App\Models\CareTimelineEntry;
use App\Models\ComplianceCheck;
use App\Models\Consent;
use App\Models\EmployeePayProfile;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\Message;
use App\Models\PayrollRun;
use App\Models\Policy;
use App\Models\PolicyAcknowledgment;
use App\Models\RotaPeriod;
use App\Models\SafeguardingReport;
use App\Models\ServiceUser;
use App\Models\Shift;
use App\Models\SubjectAccessRequest;
use App\Models\Task;
use App\Models\Timesheet;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use App\Models\User;
use App\Models\VisitCheckin;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fills the demo agency created by UserSeeder ("Affinity Healthcare Services
 * Ltd") with realistic data covering every module this app has actually
 * built, deliberately spread across every stage/status each workflow can be
 * in — a draft rota next to a published one, a timesheet at each approval
 * stage, a safeguarding report at each point in its lifecycle, and so on —
 * so every screen in the app has something real to look at rather than an
 * empty state.
 *
 * NOT added to DatabaseSeeder's call list — this is test/demo data, run it
 * by hand whenever you want a populated environment:
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Safe to run on top of an already-seeded database (Batches 1-4's
 * seeders) since it looks up the demo agency/users by the identifiers
 * UserSeeder gives them rather than assuming IDs. Re-running THIS seeder a
 * second time is guarded — it checks for its own demo service user before
 * doing anything, so it won't duplicate data; delete the demo service
 * users first (cascades via SoftDeletes/FKs) if you want a clean reseed.
 */
class DemoDataSeeder extends Seeder
{
    private Agency $agency;

    private User $admin;
    private User $manager;
    private User $carer1; // pre-existing "Demo Carer" from UserSeeder
    private User $carer2;
    private User $family1;
    private User $family2;

    public function run(): void
    {
        $this->agency = Agency::where('slug', 'affinity-hcsl')->first();

        if (! $this->agency) {
            $this->command?->error('Demo agency not found — run "php artisan db:seed --class=UserSeeder" first (or the full db:seed).');
            return;
        }

        if (ServiceUser::where('agency_id', $this->agency->id)->where('name', 'Grace Thompson')->exists()) {
            $this->command?->info('Demo data already present for Affinity Healthcare Services Ltd — skipping.');
            return;
        }

        $this->seedUsers();
        [$grace, $walter, $amara, $leon] = $this->seedServiceUsers();
        $this->seedFamily($grace, $walter);
        $this->seedConsents($grace, $walter, $amara);
        $this->seedCarePlansAndTasks($grace, $walter, $amara);
        $this->seedAssessments($grace, $walter, $amara);
        $this->seedMedications($grace, $walter);
        [$periodB, $periodA, $periodDraft] = $this->seedRotaAndShifts($grace, $walter, $amara);
        $this->seedTimesheetsAndPayroll($periodB, $periodA);
        $this->seedSafeguarding($grace, $walter, $amara);
        $this->seedPoliciesAndTraining();
        $this->seedComplianceChecks();
        $this->seedDataProtection($grace, $walter);
        $this->seedMessagesAndNotifications();

        $this->command?->info('Demo data seeded for Affinity Healthcare Services Ltd. See DEMO_DATA_GUIDE.md for a walkthrough and login details.');
    }

    // ------------------------------------------------------------------
    // Users
    // ------------------------------------------------------------------

    private function seedUsers(): void
    {
        $this->admin = User::where('email', 'admin@affinityhealthcare.test')->firstOrFail();
        $this->carer1 = User::where('email', 'carer@affinityhealthcare.test')->firstOrFail();

        $this->manager = User::firstOrCreate(
            ['email' => 'manager@affinityhealthcare.test'],
            ['uid' => (string) Str::uuid(), 'name' => 'Ada Manager', 'agency_id' => $this->agency->id, 'password' => Hash::make('Password123!'), 'is_active' => true]
        );
        $this->manager->syncRoles(['Manager']);

        $this->carer2 = User::firstOrCreate(
            ['email' => 'carer2@affinityhealthcare.test'],
            ['uid' => (string) Str::uuid(), 'name' => 'Ben Carer', 'agency_id' => $this->agency->id, 'password' => Hash::make('Password123!'), 'is_active' => true]
        );
        $this->carer2->syncRoles(['Carer']);

        $this->family1 = User::firstOrCreate(
            ['email' => 'family1@affinityhealthcare.test'],
            ['uid' => (string) Str::uuid(), 'name' => 'Nadia Family', 'agency_id' => $this->agency->id, 'password' => Hash::make('Password123!'), 'is_active' => true]
        );
        $this->family1->syncRoles(['Family']);

        $this->family2 = User::firstOrCreate(
            ['email' => 'family2@affinityhealthcare.test'],
            ['uid' => (string) Str::uuid(), 'name' => 'Tom Family', 'agency_id' => $this->agency->id, 'password' => Hash::make('Password123!'), 'is_active' => true]
        );
        $this->family2->syncRoles(['Family']);

        // Pay profiles for everyone who'll show up in a timesheet/payslip.
        EmployeePayProfile::firstOrCreate(
            ['user_id' => $this->manager->id],
            ['agency_id' => $this->agency->id, 'employee_no' => 'EMP-1001', 'job_title' => 'Care Manager', 'employment_type' => 'full_time', 'hourly_rate' => 18.50, 'overtime_multiplier' => 1.5, 'weekly_overtime_threshold_hours' => 40, 'pay_frequency' => 'monthly', 'status' => 'active']
        );
        EmployeePayProfile::firstOrCreate(
            ['user_id' => $this->carer1->id],
            ['agency_id' => $this->agency->id, 'manager_id' => $this->manager->id, 'employee_no' => 'EMP-1002', 'job_title' => 'Care Worker', 'employment_type' => 'full_time', 'hourly_rate' => 12.20, 'overtime_multiplier' => 1.5, 'weekly_overtime_threshold_hours' => 37.5, 'pay_frequency' => 'weekly', 'status' => 'active']
        );
        EmployeePayProfile::firstOrCreate(
            ['user_id' => $this->carer2->id],
            ['agency_id' => $this->agency->id, 'manager_id' => $this->manager->id, 'employee_no' => 'EMP-1003', 'job_title' => 'Care Worker', 'employment_type' => 'part_time', 'hourly_rate' => 12.20, 'overtime_multiplier' => 1.5, 'weekly_overtime_threshold_hours' => 25, 'pay_frequency' => 'weekly', 'status' => 'active']
        );
    }

    // ------------------------------------------------------------------
    // Service users
    // ------------------------------------------------------------------

    /** @return array{0: ServiceUser, 1: ServiceUser, 2: ServiceUser, 3: ServiceUser} */
    private function seedServiceUsers(): array
    {
        $grace = ServiceUser::create([
            'agency_id' => $this->agency->id, 'name' => 'Grace Thompson', 'dob' => '1938-04-12', 'gender' => 'Female',
            'address' => '14 Willow Court, London', 'nhs_number' => '400 123 4561',
            'next_of_kin_name' => 'Nadia Family', 'next_of_kin_contact' => '07700 900111',
            'consent_status' => true, 'created_by' => $this->admin->id,
        ]);

        $walter = ServiceUser::create([
            'agency_id' => $this->agency->id, 'name' => 'Walter Higgins', 'dob' => '1945-09-03', 'gender' => 'Male',
            'address' => '2 Beech Road, London', 'nhs_number' => '400 123 4562',
            'next_of_kin_name' => 'Tom Family', 'next_of_kin_contact' => '07700 900112',
            'consent_status' => true, 'created_by' => $this->admin->id,
        ]);

        $amara = ServiceUser::create([
            'agency_id' => $this->agency->id, 'name' => 'Amara Obi', 'dob' => '1952-01-21', 'gender' => 'Female',
            'address' => '9 Cedar House, London', 'nhs_number' => '400 123 4563',
            'next_of_kin_name' => 'Chidi Obi', 'next_of_kin_contact' => '07700 900113',
            'consent_status' => false, 'created_by' => $this->admin->id, // consent still pending — see Consents below
        ]);

        // A fourth, inactive/off-boarded service user — soft-deleted so the
        // dashboard's "inactive" count (which compares withTrashed() to the
        // active count) has something in it.
        $leon = ServiceUser::create([
            'agency_id' => $this->agency->id, 'name' => 'Leon Brooks', 'dob' => '1940-06-30', 'gender' => 'Male',
            'address' => '21 Oak Street, London', 'nhs_number' => '400 123 4564',
            'consent_status' => true, 'created_by' => $this->admin->id,
        ]);
        $leon->delete();

        return [$grace, $walter, $amara, $leon];
    }

    private function seedFamily(ServiceUser $grace, ServiceUser $walter): void
    {
        FamilyMember::create([
            'service_user_id' => $grace->id, 'user_id' => $this->family1->id, 'relationship' => 'Daughter',
            'is_primary_contact' => true, 'can_receive_updates' => true, 'created_by' => $this->manager->id,
        ]);

        FamilyMember::create([
            'service_user_id' => $walter->id, 'user_id' => $this->family2->id, 'relationship' => 'Son',
            'is_primary_contact' => true, 'can_receive_updates' => true, 'created_by' => $this->manager->id,
        ]);
    }

    private function seedConsents(ServiceUser $grace, ServiceUser $walter, ServiceUser $amara): void
    {
        // Grace: an active consent and a declined one.
        Consent::create(['service_user_id' => $grace->id, 'consent_type' => 'information_sharing', 'granted' => true, 'granted_by' => $this->manager->id, 'granted_at' => now()->subMonths(3), 'created_by' => $this->manager->id]);
        Consent::create(['service_user_id' => $grace->id, 'consent_type' => 'photography', 'granted' => false, 'granted_by' => $this->manager->id, 'granted_at' => now()->subMonths(3), 'created_by' => $this->manager->id]);

        // Walter: granted then later revoked.
        Consent::create(['service_user_id' => $walter->id, 'consent_type' => 'family_updates', 'granted' => true, 'granted_by' => $this->manager->id, 'granted_at' => now()->subYear(), 'revoked_at' => now()->subMonth(), 'notes' => 'Family requested this be withdrawn.', 'created_by' => $this->manager->id]);

        // Amara: granted but expired — matches her still-pending overall consent_status.
        Consent::create(['service_user_id' => $amara->id, 'consent_type' => 'medication_communication', 'granted' => true, 'granted_by' => $this->manager->id, 'granted_at' => now()->subYears(2), 'expires_at' => now()->subMonths(2), 'created_by' => $this->manager->id]);
    }

    // ------------------------------------------------------------------
    // Care plans, tasks & timeline
    // ------------------------------------------------------------------

    private function seedCarePlansAndTasks(ServiceUser $grace, ServiceUser $walter, ServiceUser $amara): void
    {
        // Grace: an on-track plan with a full spread of task states.
        $gracePlan = CarePlan::create([
            'service_user_id' => $grace->id, 'created_by' => $this->manager->id, 'title' => 'Personal Care & Mobility Plan',
            'summary' => 'Daily personal care, mobility support, and medication prompts.', 'review_date' => now()->addMonths(2), 'is_active' => true,
        ]);

        $completedTask = Task::create(['care_plan_id' => $gracePlan->id, 'title' => 'Morning personal care', 'type' => 'personal_care', 'scheduled_at' => now()->subHours(3), 'due_at' => now()->subHours(2), 'assigned_to' => $this->carer1->id, 'priority' => 2, 'requires_photo' => false, 'requires_signature' => false, 'created_by' => $this->manager->id]);
        $log = $completedTask->complete($this->carer1->id, 'completed', 'Assisted with washing and dressing, no concerns.');
        $this->recordTimelineEntry($grace, 'personal_care', "Morning personal care — completed. {$log->notes}", true, $this->carer1->id);

        $refusedTask = Task::create(['care_plan_id' => $gracePlan->id, 'title' => 'Afternoon medication prompt', 'type' => 'medication', 'scheduled_at' => now()->subHours(1), 'due_at' => now()->subMinutes(30), 'assigned_to' => $this->carer1->id, 'priority' => 3, 'requires_photo' => false, 'requires_signature' => false, 'created_by' => $this->manager->id]);
        $refusedLog = $refusedTask->complete($this->carer1->id, 'refused', 'Grace declined her afternoon dose, will monitor.');
        $this->recordTimelineEntry($grace, 'medication', "Afternoon medication prompt — was refused by the service user for. {$refusedLog->notes}", true, $this->carer1->id);

        Task::create(['care_plan_id' => $gracePlan->id, 'title' => 'Assist with lunch', 'type' => 'meal', 'scheduled_at' => now()->addHour(), 'due_at' => now()->addHours(2), 'assigned_to' => $this->carer1->id, 'priority' => 1, 'requires_photo' => false, 'requires_signature' => false, 'created_by' => $this->manager->id]);
        // ^ still pending — due later today, nothing logged against it yet.

        Task::create(['care_plan_id' => $gracePlan->id, 'title' => 'Evening check-in (photo required)', 'type' => 'welfare_check', 'scheduled_at' => now()->subDay(), 'due_at' => now()->subDay(), 'assigned_to' => $this->carer1->id, 'priority' => 2, 'requires_photo' => true, 'requires_signature' => false, 'created_by' => $this->manager->id]);
        // ^ left uncompleted on purpose — due_at is in the past, so this shows as "overdue".

        // Walter: an overdue-review plan (his own review_date is in the past).
        $walterPlan = CarePlan::create([
            'service_user_id' => $walter->id, 'created_by' => $this->manager->id, 'title' => 'Mobility & Falls Prevention Plan',
            'summary' => 'Mobility support following a fall risk assessment.', 'review_date' => now()->subWeeks(2), 'is_active' => true,
        ]);
        $skippedTask = Task::create(['care_plan_id' => $walterPlan->id, 'title' => 'Assisted walk (garden)', 'type' => 'mobility', 'scheduled_at' => now()->subDay(), 'due_at' => now()->subDay(), 'assigned_to' => $this->carer2->id, 'priority' => 1, 'requires_photo' => false, 'requires_signature' => false, 'created_by' => $this->manager->id]);
        $skippedLog = $skippedTask->complete($this->carer2->id, 'skipped', 'Weather unsuitable, rescheduled for tomorrow.');
        $this->recordTimelineEntry($walter, 'mobility', "Assisted walk (garden) — skipped. {$skippedLog->notes}", true, $this->carer2->id);

        // Amara: a plan due for review soon.
        CarePlan::create([
            'service_user_id' => $amara->id, 'created_by' => $this->manager->id, 'title' => 'Nutrition & Hydration Plan',
            'summary' => 'Support with meal preparation and fluid intake monitoring.', 'review_date' => now()->addDays(10), 'is_active' => true,
        ]);

        // A manual (non-task) timeline entry, and one deliberately not shared with family.
        CareTimelineEntry::create(['service_user_id' => $grace->id, 'entry_type' => 'visit', 'content' => 'GP visit — reviewed blood pressure, no changes to medication.', 'visible_to_family' => true, 'created_by' => $this->manager->id]);
        CareTimelineEntry::create(['service_user_id' => $walter->id, 'entry_type' => 'note', 'content' => 'Internal note: safeguarding concern under investigation (see Safeguarding).', 'visible_to_family' => false, 'created_by' => $this->manager->id]);
    }

    private function recordTimelineEntry(ServiceUser $serviceUser, string $type, string $content, bool $visibleToFamily, int $createdBy): void
    {
        CareTimelineEntry::create([
            'service_user_id' => $serviceUser->id, 'entry_type' => $type, 'content' => $content,
            'visible_to_family' => $visibleToFamily, 'created_by' => $createdBy,
        ]);
    }

    private function seedAssessments(ServiceUser $grace, ServiceUser $walter, ServiceUser $amara): void
    {
        Assessment::create([
            'service_user_id' => $grace->id, 'conducted_by' => $this->manager->id, 'assessment_type' => 'falls_risk',
            'questions_and_answers' => [['question' => 'Any falls in the last 12 months?', 'answer' => 'One, in the bathroom, no injury.'], ['question' => 'Uses mobility aid?', 'answer' => 'Walking stick.']],
            'risk_level' => Assessment::RISK_MEDIUM, 'recommendations' => 'Non-slip mat in bathroom, continue mobility support.', 'review_date' => now()->addMonths(4), 'created_by' => $this->manager->id,
        ]);

        Assessment::create([
            'service_user_id' => $walter->id, 'conducted_by' => $this->manager->id, 'assessment_type' => 'moving_handling',
            'questions_and_answers' => [['question' => 'Can transfer independently?', 'answer' => 'Requires one-person assist.']],
            'risk_level' => Assessment::RISK_HIGH, 'recommendations' => 'Hoist review recommended.', 'review_date' => now()->subWeek(), 'created_by' => $this->manager->id,
        ]); // review_date in the past — shows as overdue for review.

        Assessment::create([
            'service_user_id' => $amara->id, 'conducted_by' => $this->manager->id, 'assessment_type' => 'initial',
            'questions_and_answers' => [['question' => 'Any known allergies?', 'answer' => 'None known.']],
            'risk_level' => Assessment::RISK_LOW, 'review_date' => now()->addMonths(6), 'created_by' => $this->manager->id,
        ]);
    }

    private function seedMedications(ServiceUser $grace, ServiceUser $walter): void
    {
        $paracetamol = Medication::create(['service_user_id' => $grace->id, 'medication_name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'Twice daily', 'administration_route' => 'Oral', 'scheduled_times' => '08:00', 'start_date' => now()->subMonths(2), 'is_prn' => false, 'is_active' => true]);
        $prnMed = Medication::create(['service_user_id' => $grace->id, 'medication_name' => 'Ibuprofen', 'dosage' => '200mg', 'frequency' => 'As needed', 'administration_route' => 'Oral', 'start_date' => now()->subMonths(2), 'is_prn' => true, 'instructions' => 'For pain, max 3 per day.', 'is_active' => true]);

        MedicationAdministration::create(['medication_id' => $paracetamol->id, 'administered_by' => $this->carer1->id, 'scheduled_time' => now()->setTime(8, 0), 'actual_time' => now()->setTime(8, 5), 'status' => 'given']);
        MedicationAdministration::create(['medication_id' => $paracetamol->id, 'administered_by' => $this->carer1->id, 'scheduled_time' => now()->subDay()->setTime(20, 0), 'actual_time' => now()->subDay()->setTime(20, 10), 'status' => 'prompted', 'notes' => 'Needed a reminder before taking.']);
        MedicationAdministration::create(['medication_id' => $paracetamol->id, 'administered_by' => $this->carer1->id, 'scheduled_time' => now()->subDays(2)->setTime(8, 0), 'status' => 'missed', 'notes' => 'Carer arrived after the window — flagged to manager.']);
        MedicationAdministration::create(['medication_id' => $prnMed->id, 'administered_by' => $this->carer1->id, 'scheduled_time' => now()->subHours(4), 'actual_time' => now()->subHours(4), 'status' => 'refused', 'refusal_reason' => 'Reported no pain at the time.']);

        $walterMed = Medication::create(['service_user_id' => $walter->id, 'medication_name' => 'Amlodipine', 'dosage' => '5mg', 'frequency' => 'Once daily', 'administration_route' => 'Oral', 'scheduled_times' => '09:00', 'start_date' => now()->subYear(), 'is_prn' => false, 'is_active' => true]);
        MedicationAdministration::create(['medication_id' => $walterMed->id, 'administered_by' => $this->carer2->id, 'scheduled_time' => now()->setTime(9, 0), 'actual_time' => now()->setTime(9, 2), 'status' => 'given']);
        MedicationAdministration::create(['medication_id' => $walterMed->id, 'administered_by' => $this->carer2->id, 'scheduled_time' => now()->subDay()->setTime(9, 0), 'actual_time' => now()->subDay()->setTime(9, 0), 'status' => 'given']);
    }

    // ------------------------------------------------------------------
    // Rota & shifts
    // ------------------------------------------------------------------

    /** @return array{0: RotaPeriod, 1: RotaPeriod, 2: RotaPeriod} */
    private function seedRotaAndShifts(ServiceUser $grace, ServiceUser $walter, ServiceUser $amara): array
    {
        $periodB = RotaPeriod::create(['agency_id' => $this->agency->id, 'week_commencing' => now()->subWeeks(2)->startOfWeek(), 'status' => 'draft', 'created_by' => $this->manager->id]);
        $periodA = RotaPeriod::create(['agency_id' => $this->agency->id, 'week_commencing' => now()->subWeek()->startOfWeek(), 'status' => 'draft', 'created_by' => $this->manager->id]);
        $periodDraft = RotaPeriod::create(['agency_id' => $this->agency->id, 'week_commencing' => now()->addWeek()->startOfWeek(), 'status' => 'draft', 'created_by' => $this->manager->id]);

        $this->fillWeekOfShifts($periodB, $grace, $walter, $amara, completeAll: true, leaveUncompletedOnDay: null);
        $this->fillWeekOfShifts($periodA, $grace, $walter, $amara, completeAll: true, leaveUncompletedOnDay: 4); // Friday's shifts are left un-checked-in — shows as "missed" once the week's in the past
        $this->fillWeekOfShifts($periodDraft, $grace, $walter, $amara, completeAll: false, leaveUncompletedOnDay: null); // next week — nothing actualised yet, still upcoming

        // Publishing periodB/periodA (not the future draft) also fires the
        // "your rota has been published" notification to every assigned
        // carer via RotaPeriod::publish() — see Notifications below.
        $periodB->publish();
        $periodA->publish();

        return [$periodB, $periodA, $periodDraft];
    }

    /**
     * One day-shift per weekday for each of two service users (Grace with
     * carer1, Walter with carer2), plus a night shift on the Wednesday.
     * With $completeAll true, every shift gets actual_start/actual_end set
     * (reads as completed) except those on $leaveUncompletedOnDay if given —
     * for a week already in the past that day's shifts show up as "missed".
     * With $completeAll false, nothing is actualised at all (a future week,
     * still "upcoming").
     *
     * Batch 6: Amara isn't in this daily rota — RotaPeriod::generateTimesheets()
     * only carries a single day-type shift's start/end through to each day's
     * TimesheetEntry (`$dayShifts->firstWhere('shift_type', 'day')`), even
     * though it correctly sums break_minutes across every shift that date.
     * Giving one carer two same-day 'day'-type shifts (as this originally did,
     * pairing Amara with carer1 alongside Grace) means the second shift's
     * time silently drops out of the hours calculation while its break time
     * still gets subtracted — undercounting that carer's day. Real double-booked
     * visits are entirely normal in domiciliary care, so this is a genuine gap
     * in generateTimesheets() worth fixing properly at some point (e.g.
     * summing each same-type shift's own duration rather than reading one
     * shift's start/end), but it's a separate change from the Carbon
     * diffInMinutes fix in this batch — see CHANGES6.md. Sidestepping it here
     * so the seeded numbers are correct without a schema/logic change.
     */
    private function fillWeekOfShifts(RotaPeriod $period, ServiceUser $grace, ServiceUser $walter, ServiceUser $amara, bool $completeAll, ?int $leaveUncompletedOnDay): void
    {
        $weekStart = Carbon::parse($period->week_commencing);
        $assignments = [
            [$grace, $this->carer1], [$walter, $this->carer2],
        ];

        for ($day = 0; $day < 5; $day++) { // Mon-Fri
            $date = $weekStart->copy()->addDays($day);
            foreach ($assignments as $i => [$serviceUser, $carer]) {
                $start = $date->copy()->setTime(8, 0)->addHours($i * 2);
                $end = $start->copy()->addHours(2);

                $isCompleted = $completeAll && $day !== $leaveUncompletedOnDay;

                $shift = Shift::create([
                    'agency_id' => $this->agency->id, 'rota_period_id' => $period->id,
                    'service_user_id' => $serviceUser->id, 'assigned_to' => $carer->id,
                    'scheduled_start' => $start, 'scheduled_end' => $end,
                    'actual_start' => $isCompleted ? $start->copy()->addMinutes(2) : null,
                    'actual_end' => $isCompleted ? $end->copy()->addMinutes(5) : null,
                    'shift_type' => 'day', 'break_minutes' => 30, 'status' => 'scheduled',
                    'created_by' => $this->manager->id,
                ]);

                // A real check-in/check-out for a couple of the completed shifts.
                if ($isCompleted && $i === 0) {
                    VisitCheckin::create(['shift_id' => $shift->id, 'user_id' => $carer->id, 'checkin_method' => 'manual', 'checkin_time' => $start->copy()->addMinutes(2), 'checkout_time' => $end->copy()->addMinutes(5), 'location_verified' => true]);
                }
            }
        }

        // One explicit night shift midweek, for variety.
        $wed = $weekStart->copy()->addDays(2)->setTime(20, 0);
        Shift::create([
            'agency_id' => $this->agency->id, 'rota_period_id' => $period->id,
            'service_user_id' => $grace->id, 'assigned_to' => $this->carer2->id,
            'scheduled_start' => $wed, 'scheduled_end' => $wed->copy()->addHours(10),
            'shift_type' => 'night', 'break_minutes' => 30, 'status' => 'scheduled',
            'created_by' => $this->manager->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Timesheets & payroll
    // ------------------------------------------------------------------

    private function seedTimesheetsAndPayroll(RotaPeriod $periodB, RotaPeriod $periodA): void
    {
        // Period B (two weeks ago) — fully worked, generate timesheets, then
        // run this one all the way through payroll to "paid".
        $periodB->generateTimesheets();
        $carer1TimesheetB = Timesheet::where('rota_period_id', $periodB->id)->where('user_id', $this->carer1->id)->first();
        $carer2TimesheetB = Timesheet::where('rota_period_id', $periodB->id)->where('user_id', $this->carer2->id)->first();

        $carer1TimesheetB?->submit();
        $carer1TimesheetB?->approve($this->manager->id);
        // carer2's period-B timesheet is left in "submitted" — approved manually below is skipped on purpose so
        // generateFromApprovedTimesheets() only picks up carer1's, demonstrating a rejected-vs-approved contrast.
        $carer2TimesheetB?->submit();
        $carer2TimesheetB?->update(['status' => 'rejected', 'comments' => 'Hours don\'t match the rota — please resubmit.']);

        $payrollB = PayrollRun::create(['agency_id' => $this->agency->id, 'reference' => 'PR-' . now()->subWeeks(2)->format('Ym') . '-01', 'pay_period_start' => $periodB->week_commencing, 'pay_period_end' => Carbon::parse($periodB->week_commencing)->addDays(6), 'frequency' => 'weekly', 'status' => 'draft', 'processed_by' => $this->manager->id]);
        $payrollB->generateFromApprovedTimesheets();
        $payslip = $payrollB->payslips()->first();
        $payslip?->addEarning('bonus', 15.00, 'Attendance bonus');
        $payslip?->addDeduction('tax', 22.50, 'Estimated tax');
        $payrollB->approve($this->admin->id);
        $payrollB->markPaid();

        // Period A (last week) — mid-flight: one approved (ready for the
        // next payroll run), one still just submitted (awaiting the manager).
        $periodA->generateTimesheets();
        $carer1TimesheetA = Timesheet::where('rota_period_id', $periodA->id)->where('user_id', $this->carer1->id)->first();
        $carer2TimesheetA = Timesheet::where('rota_period_id', $periodA->id)->where('user_id', $this->carer2->id)->first();

        $carer1TimesheetA?->submit();
        $carer1TimesheetA?->approve($this->manager->id);
        $carer2TimesheetA?->submit();
        // carer2TimesheetA is left "submitted" — a manager still needs to act on it.

        $payrollA = PayrollRun::create(['agency_id' => $this->agency->id, 'reference' => 'PR-' . now()->subWeek()->format('Ym') . '-02', 'pay_period_start' => $periodA->week_commencing, 'pay_period_end' => Carbon::parse($periodA->week_commencing)->addDays(6), 'frequency' => 'weekly', 'status' => 'draft', 'processed_by' => $this->manager->id]);
        $payrollA->generateFromApprovedTimesheets();
        // Left as "draft" with one payslip (carer1's) — carer2's timesheet
        // isn't approved yet so it's correctly excluded.
    }

    // ------------------------------------------------------------------
    // Safeguarding
    // ------------------------------------------------------------------

    private function seedSafeguarding(ServiceUser $grace, ServiceUser $walter, ServiceUser $amara): void
    {
        // 1. Open — just reported, nothing else done yet.
        $open = SafeguardingReport::create(['service_user_id' => $amara->id, 'reported_by' => $this->carer1->id, 'type' => 'neglect', 'description' => 'Service user found without heating on a cold day; boiler appeared to be off.']);
        $open->reportOpened($this->carer1);

        // 2. Escalated + investigating — reported, escalated to the manager, then an investigation note logged.
        $escalated = SafeguardingReport::create(['service_user_id' => $walter->id, 'reported_by' => $this->carer2->id, 'type' => 'physical', 'description' => 'Unexplained bruising noticed on service user\'s arm during personal care.']);
        $escalated->reportOpened($this->carer2);
        $escalated->escalate($this->carer2, $this->manager, 'Please advise on next steps — service user unable to explain the bruising.');
        $escalated->addInvestigationNote($this->manager, 'Spoken with service user and family; safeguarding lead at the local authority notified.');

        // 3. Resolved.
        $resolved = SafeguardingReport::create(['service_user_id' => $grace->id, 'reported_by' => $this->carer1->id, 'type' => 'financial', 'description' => 'Service user reported a cold caller asking for bank details.']);
        $resolved->reportOpened($this->carer1);
        $resolved->addInvestigationNote($this->manager, 'Confirmed no money was given. Family and bank informed as a precaution.');
        $resolved->resolve($this->manager, 'No further action needed — service user and family reassured, added to at-risk call list with the bank.');

        // 4. Closed — the full lifecycle, from an earlier date.
        $closed = SafeguardingReport::create(['service_user_id' => $grace->id, 'reported_by' => $this->manager->id, 'type' => 'self_neglect', 'description' => 'Concern raised after a missed personal care visit led to a decline in hygiene.']);
        $closed->reportOpened($this->manager);
        $closed->addInvestigationNote($this->manager, 'Reviewed rota gap that caused the missed visit; additional carer added to the rota.');
        $closed->resolve($this->manager, 'Rota gap fixed, service user\'s hygiene needs are being met again.');
        $closed->close($this->manager, 'No recurrence after one month of follow-up.');
    }

    // ------------------------------------------------------------------
    // Policies, training & compliance
    // ------------------------------------------------------------------

    private function seedPoliciesAndTraining(): void
    {
        $safeguardingPolicy = Policy::create(['agency_id' => $this->agency->id, 'title' => 'Safeguarding Adults Policy', 'category' => 'Safeguarding', 'version' => '2.1', 'effective_date' => now()->subYear(), 'review_date' => now()->addMonths(6), 'is_mandatory_reading' => true, 'is_active' => true, 'created_by' => $this->admin->id]);
        $medicationPolicy = Policy::create(['agency_id' => $this->agency->id, 'title' => 'Medication Administration Policy', 'category' => 'Medication', 'version' => '1.4', 'effective_date' => now()->subMonths(8), 'review_date' => now()->addMonths(4), 'is_mandatory_reading' => true, 'is_active' => true, 'created_by' => $this->admin->id]);
        $dataProtectionPolicy = Policy::create(['agency_id' => $this->agency->id, 'title' => 'Data Protection Policy', 'category' => 'Compliance', 'version' => '1.0', 'effective_date' => now()->subMonths(3), 'review_date' => now()->subDays(5), 'is_mandatory_reading' => true, 'is_active' => true, 'created_by' => $this->admin->id]); // review_date already past — due for review.

        foreach ([$safeguardingPolicy, $medicationPolicy, $dataProtectionPolicy] as $policy) {
            PolicyAcknowledgment::create(['policy_id' => $policy->id, 'user_id' => $this->manager->id, 'acknowledged_at' => now()->subMonths(2)]);
        }
        PolicyAcknowledgment::create(['policy_id' => $safeguardingPolicy->id, 'user_id' => $this->carer1->id, 'acknowledged_at' => now()->subMonth()]);
        PolicyAcknowledgment::create(['policy_id' => $medicationPolicy->id, 'user_id' => $this->carer1->id, 'acknowledged_at' => now()->subMonth()]);
        // carer1 hasn't acknowledged the Data Protection Policy yet; carer2 hasn't acknowledged anything yet.

        $modules = [
            TrainingModule::create(['title' => 'Safeguarding Awareness', 'description' => 'Recognising and reporting safeguarding concerns.', 'duration_minutes' => 45, 'category' => 'Safeguarding']),
            TrainingModule::create(['title' => 'Medication Administration', 'description' => 'Safe administration and recording of medication.', 'duration_minutes' => 60, 'category' => 'Medication']),
            TrainingModule::create(['title' => 'Moving & Handling', 'description' => 'Safe manual handling techniques.', 'duration_minutes' => 90, 'category' => 'Health & Safety']),
            TrainingModule::create(['title' => 'Fire Safety', 'description' => 'Fire prevention and evacuation procedures.', 'duration_minutes' => 30, 'category' => 'Health & Safety']),
        ];

        foreach ($modules as $module) {
            TrainingProgress::create(['user_id' => $this->manager->id, 'module_id' => $module->id, 'status' => TrainingProgress::STATUS_COMPLETED, 'score' => 95, 'completed_at' => now()->subMonths(2)]);
        }
        TrainingProgress::create(['user_id' => $this->carer1->id, 'module_id' => $modules[0]->id, 'status' => TrainingProgress::STATUS_COMPLETED, 'score' => 88, 'completed_at' => now()->subMonths(3)]);
        TrainingProgress::create(['user_id' => $this->carer1->id, 'module_id' => $modules[1]->id, 'status' => TrainingProgress::STATUS_COMPLETED, 'score' => 91, 'completed_at' => now()->subMonths(1)]);
        TrainingProgress::create(['user_id' => $this->carer1->id, 'module_id' => $modules[2]->id, 'status' => TrainingProgress::STATUS_STARTED]);
        // carer1 hasn't started Fire Safety at all — no row, which is the "not started" state.
        TrainingProgress::create(['user_id' => $this->carer2->id, 'module_id' => $modules[0]->id, 'status' => TrainingProgress::STATUS_COMPLETED, 'score' => 79, 'completed_at' => now()->subWeeks(2)]);
        TrainingProgress::create(['user_id' => $this->carer2->id, 'module_id' => $modules[1]->id, 'status' => TrainingProgress::STATUS_STARTED]);
    }

    private function seedComplianceChecks(): void
    {
        ComplianceCheck::create(['agency_id' => $this->agency->id, 'category' => 'CQC Registration Renewal', 'status' => ComplianceCheck::STATUS_COMPLETE, 'next_due_at' => now()->addYear()]);
        ComplianceCheck::create(['agency_id' => $this->agency->id, 'category' => 'Fire Risk Assessment', 'status' => ComplianceCheck::STATUS_IN_PROGRESS, 'notes' => 'Assessor booked for next month.', 'next_due_at' => now()->addWeeks(3)]);
        ComplianceCheck::create(['agency_id' => $this->agency->id, 'category' => 'DBS Checks Renewal', 'status' => ComplianceCheck::STATUS_NOT_STARTED, 'next_due_at' => now()->subWeek()]); // overdue
        ComplianceCheck::create(['agency_id' => $this->agency->id, 'category' => 'Public Liability Insurance Renewal', 'status' => ComplianceCheck::STATUS_COMPLETE, 'next_due_at' => now()->addMonths(9)]);
    }

    private function seedDataProtection(ServiceUser $grace, ServiceUser $walter): void
    {
        SubjectAccessRequest::create(['requested_by' => $this->family1->id, 'service_user_id' => $grace->id, 'type' => 'access', 'status' => SubjectAccessRequest::STATUS_PENDING]);
        SubjectAccessRequest::create(['requested_by' => $this->family2->id, 'service_user_id' => $walter->id, 'type' => 'rectification', 'status' => SubjectAccessRequest::STATUS_IN_PROGRESS, 'notes' => 'Correcting an out-of-date next-of-kin phone number.']);
        SubjectAccessRequest::create(['requested_by' => $this->admin->id, 'service_user_id' => $grace->id, 'type' => 'portability', 'status' => SubjectAccessRequest::STATUS_FULFILLED, 'fulfilled_by' => $this->admin->id, 'notes' => 'Care records exported and provided to the family.']);

        BreachReport::create(['reported_by' => $this->carer1->id, 'agency_id' => $this->agency->id, 'description' => 'A printed rota sheet with service user names was left visible in a car.', 'severity' => 'low', 'reported_to_ico' => false]);
        BreachReport::create(['reported_by' => $this->manager->id, 'agency_id' => $this->agency->id, 'description' => 'Care plan PDF emailed to the wrong recipient outside the agency.', 'severity' => 'high', 'action_taken' => 'Recipient confirmed deletion; affected family notified; email policy reviewed.', 'reported_to_ico' => true]);
    }

    // ------------------------------------------------------------------
    // Messaging & notifications
    // ------------------------------------------------------------------

    private function seedMessagesAndNotifications(): void
    {
        $t = now()->subHours(3);
        $this->createMessageAt($this->manager->id, $this->carer1->id, 'Can you cover an extra visit for Amara tomorrow morning?', $t, $t->copy()->addMinutes(5));
        $this->createMessageAt($this->carer1->id, $this->manager->id, 'Yes, that works for me.', $t->copy()->addMinutes(10), $t->copy()->addMinutes(20));
        $this->createMessageAt($this->manager->id, $this->carer1->id, 'Thanks — added it to your rota.', $t->copy()->addMinutes(25), null); // unread

        // A few extra notifications beyond the ones already generated by
        // rota publishing and safeguarding escalation above, so the bell
        // and notification center have a realistic mix of read/unread and
        // priority.
        NotificationService::send($this->carer1->id, 'new_message', 'New message', 'Ada Manager: Thanks — added it to your rota.', 'normal', ['sender_id' => $this->manager->id]);
        $welcomeNotif = NotificationService::send($this->carer2->id, 'shift_assigned', 'Welcome to Affinity Healthcare', 'Your account is set up — check My Rota for your upcoming shifts.', 'low');
        $welcomeNotif->markRead();

        AuditLogger::log('DEMO_DATA_SEEDED', null, ['agency' => $this->agency->name], $this->admin->id);
    }

    /**
     * created_at isn't fillable on Message (nor on most models here), so a
     * backdated timestamp has to be set directly on the model and saved
     * rather than passed through create()'s mass assignment.
     */
    private function createMessageAt(int $senderId, int $receiverId, string $text, Carbon $sentAt, ?Carbon $readAt): Message
    {
        $message = Message::create(['sender_id' => $senderId, 'receiver_id' => $receiverId, 'message' => $text, 'encrypted' => false, 'read_at' => $readAt]);
        $message->created_at = $sentAt;
        $message->updated_at = $sentAt;
        $message->save();

        return $message;
    }
}
