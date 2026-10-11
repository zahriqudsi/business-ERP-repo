<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(): Invoice
    {
        return Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01', 'subtotal' => 100]);
    }

    public function test_owner_adds_a_staff_user_who_can_sign_in(): void
    {
        $this->signIn();

        $this->post('/users', [
            'username' => 'nimal', 'name' => 'Nimal Perera', 'role' => 'staff', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1',
        ])->assertRedirect(route('users.index'));
        $this->get('/users')->assertOk()->assertSee('Nimal Perera')->assertSee('Staff');

        auth()->logout();
        $this->post('/login', ['username' => 'nimal', 'password' => 'long-enough-1'])->assertRedirect(route('dashboard'));
        $this->assertNotNull(User::where('username', 'nimal')->value('last_login_at'));
    }

    public function test_staff_cannot_delete_see_reports_or_manage_the_business(): void
    {
        $invoice = $this->invoice();
        $this->signIn('staff');

        $this->get('/invoices')->assertOk()->assertDontSee('data-delete-url', false);
        $this->deleteJson('/invoices/'.$invoice->id)->assertForbidden();
        $this->get('/reports')->assertForbidden();
        $this->get('/reports/sales')->assertForbidden();
        foreach (['/settings', '/users', '/backups', '/activity'] as $page) {
            $this->get($page)->assertForbidden();
        }
        $this->get('/')->assertOk()->assertDontSee(route('reports.index'))->assertDontSee(route('settings.edit'));

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_managers_can_delete_and_see_reports_but_not_settings(): void
    {
        $invoice = $this->invoice();
        $this->signIn('manager');

        $this->get('/reports/sales')->assertOk();
        $this->get('/settings')->assertForbidden();
        $this->deleteJson('/invoices/'.$invoice->id)->assertOk();
    }

    public function test_switched_off_users_cannot_sign_in_and_are_signed_out(): void
    {
        User::create(['username' => 'gone', 'password' => 'long-enough-1', 'role' => 'staff', 'is_active' => false]);
        $this->post('/login', ['username' => 'gone', 'password' => 'long-enough-1'])
            ->assertSessionHasErrors(['login' => 'This account has been switched off. Please contact the owner.']);
        $this->assertGuest();

        $user = $this->signIn('staff');
        $user->update(['is_active' => false]);
        $this->get('/')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_there_is_always_an_active_owner(): void
    {
        $owner = $this->signIn();
        $form = ['username' => 'tester', 'role' => 'staff', 'is_active' => '1'];

        $this->put('/users/'.$owner->id, $form)->assertSessionHasErrors(['role' => 'There must always be at least one active owner.']);
        $this->put('/users/'.$owner->id, ['role' => 'owner', 'is_active' => '0'] + $form)->assertSessionHasErrors('role');
        $this->assertSame('owner', $owner->refresh()->role);

        // With a second owner, the first can step down
        User::create(['username' => 'boss2', 'password' => 'long-enough-1', 'role' => 'owner']);
        $this->put('/users/'.$owner->id, $form)->assertSessionHasNoErrors();
        $this->assertSame('staff', $owner->refresh()->role);
    }

    public function test_owner_cannot_switch_off_their_own_account(): void
    {
        $owner = $this->signIn();
        User::create(['username' => 'boss2', 'password' => 'long-enough-1', 'role' => 'owner']);

        $this->put('/users/'.$owner->id, ['username' => 'tester', 'role' => 'owner', 'is_active' => '0'])
            ->assertSessionHasErrors(['is_active' => 'You cannot switch off your own account.']);
    }

    public function test_changing_my_password_needs_the_current_one(): void
    {
        $user = $this->signIn('staff');

        $this->put('/account', ['name' => 'Tess', 'current_password' => 'wrong', 'password' => 'new-password-9', 'password_confirmation' => 'new-password-9'])
            ->assertSessionHasErrors(['current_password' => 'Your current password is not correct.']);
        $this->put('/account', ['name' => 'Tess', 'current_password' => 'secret-password', 'password' => 'new-password-9', 'password_confirmation' => 'new-password-9'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Tess', $user->refresh()->name);
        $this->assertTrue(password_verify('new-password-9', $user->password));
    }

    public function test_command_creates_users_with_a_role(): void
    {
        $this->artisan('sjpos:user', ['username' => 'cli', '--password' => 'long-enough-1', '--role' => 'manager'])->assertSuccessful();
        $this->assertSame('manager', User::where('username', 'cli')->value('role'));

        $this->artisan('sjpos:user', ['username' => 'cli2', '--password' => 'x', '--role' => 'king'])->assertFailed();
    }
}
