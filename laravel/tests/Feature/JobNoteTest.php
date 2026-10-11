<?php

namespace Tests\Feature;

use App\Models\JobNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobNoteTest extends TestCase
{
    use RefreshDatabase;

    /** 1x1 transparent PNG */
    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->signIn();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'customer_name' => 'Nimal Perera',
            'contact_person' => '',
            'contact_no' => '0771234567',
            'email' => 'nimal@example.com',
            'item' => 'MacBook Pro 16',
            'serial_no' => 'C02XYZ',
            'fault' => 'No display',
            'accessories_received' => 'Charger',
            'collected_date' => '2026-10-08',
            'collected_by' => 'tester',
            'terms_conditions' => '',
        ];
    }

    public function test_create_without_signature_then_sign(): void
    {
        $this->postJson('/jobs', $this->payload())
            ->assertOk()
            ->assertJson(['status' => 'success', 'job_no' => 10, 'message' => 'Job note 010 saved.']);

        $job = JobNote::firstOrFail();
        $this->assertFalse($job->is_signed);
        $this->assertStringContainsString('backed up their data', $job->terms_conditions);

        $this->postJson("/jobs/{$job->id}/signature", ['customer_signature' => 'garbage'])
            ->assertStatus(400)->assertJsonPath('message', 'Please sign before saving.');

        $this->postJson("/jobs/{$job->id}/signature", ['customer_signature' => self::SIGNATURE, 'signed_name' => ''])
            ->assertOk()->assertJson(['status' => 'success']);
        $job->refresh();
        $this->assertTrue($job->is_signed);
        $this->assertSame('Nimal Perera', $job->signed_name);

        // Can't sign twice
        $this->postJson("/jobs/{$job->id}/signature", ['customer_signature' => self::SIGNATURE])->assertStatus(409);
    }

    public function test_numbers_continue_from_highest(): void
    {
        $this->postJson('/jobs', $this->payload());
        $this->postJson('/jobs', $this->payload())->assertJson(['job_no' => 11]);
    }

    public function test_update_keeps_or_clears_signature(): void
    {
        $this->postJson('/jobs', $this->payload(['customer_signature' => self::SIGNATURE, 'signed_name' => 'Nimal']));
        $job = JobNote::firstOrFail();
        $this->assertTrue($job->is_signed);

        $this->putJson("/jobs/{$job->id}", $this->payload(['fault' => 'No display, fan noise']))->assertOk();
        $this->assertTrue($job->refresh()->is_signed);

        $this->putJson("/jobs/{$job->id}", $this->payload(['clear_signature' => '1']))->assertOk();
        $job->refresh();
        $this->assertFalse($job->is_signed);
        $this->assertNull($job->signed_at);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->postJson('/jobs', $this->payload(['email' => 'not-an-email']))->assertUnprocessable();
    }

    public function test_pages_render_and_delete(): void
    {
        $this->postJson('/jobs', $this->payload());
        $job = JobNote::firstOrFail();

        $this->get('/jobs')->assertOk()->assertSee('MacBook Pro 16');
        $this->get('/jobs/create')->assertOk()->assertSee('Job No. 011');
        $this->get("/jobs/{$job->id}")->assertOk()->assertSee('Awaiting customer signature')->assertSee('signNow');
        $this->get("/jobs/{$job->id}/edit")->assertOk()->assertSee('No display');
        $this->get("/jobs/{$job->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->deleteJson("/jobs/{$job->id}")->assertOk();
        $this->assertDatabaseCount('job_notes', 0);
    }
}
