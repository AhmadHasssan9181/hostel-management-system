<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesHmsData;
use Tests\TestCase;

class ComplaintFlowTest extends TestCase
{
    use CreatesHmsData, RefreshDatabase;

    public function test_a_resident_can_file_and_track_a_complaint(): void
    {
        $student = $this->makeStudent();
        $employee = $this->makeUser(Role::Employee);
        $booking = $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/complaints', [
                'category' => 'plumbing',
                'priority' => 'urgent',
                'description' => 'Water is leaking under the sink in my room.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('complaint.status', 'open')
            ->assertJsonPath('complaint.room_id', $booking->room_id);

        $complaint = Complaint::first();
        $this->assertStringStartsWith('HMS-', $complaint->ticket_no);

        $this->actingAs($employee, 'sanctum')
            ->postJson("/api/v1/complaints/{$complaint->id}/assign", ['assigned_to' => $employee->id])
            ->assertOk()
            ->assertJsonPath('complaint.status', 'in_progress');

        $this->actingAs($employee, 'sanctum')
            ->patchJson("/api/v1/complaints/{$complaint->id}/status", [
                'status' => 'resolved',
                'resolution_note' => 'Pipe replaced.',
            ])
            ->assertOk()
            ->assertJsonPath('complaint.status', 'resolved');

        $this->assertNotNull($complaint->fresh()->resolved_at);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $student->id,
            'type' => 'complaint',
        ]);
    }

    public function test_a_student_needs_an_allocation_to_file_a_complaint(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/complaints', [
                'category' => 'wifi',
                'description' => 'The Wi-Fi is not working in the corridor.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_a_student_cannot_read_another_students_complaint(): void
    {
        $owner = $this->makeStudent();
        $other = $this->makeStudent();
        $booking = $this->allocate($owner, $this->makeRoom($this->makeHostel()));

        $complaint = Complaint::create([
            'student_id' => $owner->id,
            'room_id' => $booking->room_id,
            'category' => 'wifi',
            'description' => 'Wi-Fi drops every evening on the third floor.',
        ]);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/complaints/{$complaint->id}")
            ->assertForbidden();
    }

    public function test_stale_unassigned_complaints_get_escalated(): void
    {
        $student = $this->makeStudent();
        $warden = $this->makeUser(Role::Employee);
        $this->allocate($student, $this->makeRoom($this->makeHostel()));

        $complaint = Complaint::create([
            'student_id' => $student->id,
            'category' => 'electrical',
            'description' => 'The corridor light has been out for a week.',
        ]);

        $complaint->forceFill([
            'created_at' => now()->subHours(72),
            'updated_at' => now()->subHours(72),
        ])->save();

        $count = app(ComplaintService::class)->escalateStale();

        $this->assertSame(1, $count);
        $this->assertSame('escalated', $complaint->fresh()->status->value);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $warden->id, 'type' => 'complaint_escalation']);
    }
}
