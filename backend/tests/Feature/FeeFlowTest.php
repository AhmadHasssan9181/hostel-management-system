<?php

namespace Tests\Feature;

use App\Enums\FeeStatus;
use App\Enums\Role;
use App\Models\Fee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class FeeFlowTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_staff_generate_an_invoice_and_student_pays_it(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);
        $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/fees', [
                'student_id' => $student->id,
                'type' => 'hostel_fee',
                'amount' => 6500,
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertCreated()
            ->assertJsonPath('fee.status', 'unpaid');

        $fee = Fee::first();

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/fees/{$fee->id}/pay", ['payment_method' => 'bank_transfer'])
            ->assertOk()
            ->assertJsonPath('fee.status', 'pending_verification');

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/fees/{$fee->id}/verify", ['approved' => true])
            ->assertOk()
            ->assertJsonPath('fee.status', 'paid');

        $this->assertNotNull($fee->fresh()->paid_at);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $student->id, 'type' => 'fee']);
    }

    public function test_a_student_cannot_verify_their_own_payment(): void
    {
        $student = $this->makeStudent();
        $fee = Fee::create([
            'reference' => 'FEE-TEST-1',
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'amount' => 6500,
            'status' => FeeStatus::PendingVerification,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/fees/{$fee->id}/verify", ['approved' => true])
            ->assertForbidden();
    }

    public function test_a_student_cannot_pay_someone_elses_invoice(): void
    {
        $owner = $this->makeStudent();
        $other = $this->makeStudent();
        $fee = Fee::create([
            'reference' => 'FEE-TEST-2',
            'student_id' => $owner->id,
            'type' => 'hostel_fee',
            'amount' => 6500,
            'status' => FeeStatus::Unpaid,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/fees/{$fee->id}/pay")
            ->assertUnprocessable();
    }

    public function test_student_dashboard_shows_outstanding_dues(): void
    {
        $student = $this->makeStudent();

        Fee::create([
            'reference' => 'FEE-TEST-3',
            'student_id' => $student->id,
            'type' => 'hostel_fee',
            'amount' => 6500,
            'fine' => 500,
            'status' => FeeStatus::Unpaid,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('fees.outstanding', 7000)
            ->assertJsonPath('role', 'student');
    }
}
