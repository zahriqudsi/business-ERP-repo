<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/invoices')->assertRedirect('/login');
    }

    public function test_login_with_valid_credentials(): void
    {
        config(['sjpos.otp.enabled' => false]);
        User::create(['username' => 'admin', 'password' => 'Admin@123']);

        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_accepts_legacy_password_hash(): void
    {
        config(['sjpos.otp.enabled' => false]);
        // Hash produced by PHP's password_hash(), as stored by the old system
        User::query()->insert(['username' => 'legacy', 'password' => password_hash('Shane@123', PASSWORD_DEFAULT)]);

        $this->post('/login', ['username' => 'legacy', 'password' => 'Shane@123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::create(['username' => 'admin', 'password' => 'Admin@123']);

        $this->post('/login', ['username' => 'admin', 'password' => 'nope'])
            ->assertSessionHasErrors(['login' => 'Invalid username or password!']);
        $this->assertGuest();
    }

    public function test_otp_flow_when_enabled(): void
    {
        config(['sjpos.otp.enabled' => true, 'sjpos.otp.email' => 'office@sj.test']);
        Mail::fake();
        $user = User::create(['username' => 'admin', 'password' => 'Admin@123']);

        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@123'])->assertRedirect(route('otp.create'));
        $this->assertGuest();
        Mail::assertSent(LoginOtpMail::class);

        // Replace the emailed code with a known one
        OtpCode::where('user_id', $user->id)->update(['otp_hash' => Hash::make('123456')]);

        $this->post('/verify-otp', ['otp' => '000000'])->assertSessionHasErrors(['otp' => 'Invalid code. Please try again.']);
        $this->assertGuest();

        $this->post('/verify-otp', ['otp' => '123456'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_otp_code_goes_to_the_user_own_email_from_the_business_name(): void
    {
        config(['sjpos.otp.enabled' => true, 'sjpos.otp.email' => 'office@sj.test']);
        Mail::fake();
        User::create(['username' => 'admin', 'email' => 'admin@sj.test', 'password' => 'Admin@123']);

        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@123'])->assertRedirect(route('otp.create'));

        Mail::assertSent(LoginOtpMail::class, fn (LoginOtpMail $mail) => $mail->hasTo('admin@sj.test')
            && ! $mail->hasTo('office@sj.test')
            && $mail->hasFrom(config('mail.from.address'), 'SJ Technology'));
    }

    public function test_otp_code_for_a_user_without_email_goes_to_the_business_mailbox(): void
    {
        config(['sjpos.otp.enabled' => true, 'sjpos.otp.email' => 'office@sj.test']);
        Mail::fake();
        User::create(['username' => 'admin', 'password' => 'Admin@123']);

        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@123'])->assertRedirect(route('otp.create'));

        Mail::assertSent(LoginOtpMail::class, fn (LoginOtpMail $mail) => $mail->hasTo('office@sj.test'));
    }

    public function test_otp_sign_in_is_refused_when_there_is_no_address_for_the_code(): void
    {
        config(['sjpos.otp.enabled' => true, 'sjpos.otp.email' => '']);
        Mail::fake();
        User::create(['username' => 'admin', 'password' => 'Admin@123']);

        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@123'])
            ->assertSessionHasErrors(['login' => 'Your account has no email address for sign-in codes. Please ask the owner to add one.']);

        $this->assertGuest();
        $this->assertDatabaseCount('otp_codes', 0);
        Mail::assertNothingSent();
    }

    public function test_logout(): void
    {
        $this->signIn();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
