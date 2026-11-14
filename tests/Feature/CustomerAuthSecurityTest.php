<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->user = User::create(['name' => 'Rahim', 'email' => 'rahim@example.com', 'password' => 'Secret123', 'is_email_verified' => true]);
    }

    public function test_login_is_locked_after_five_wrong_passwords(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/en/login', ['email' => 'rahim@example.com', 'password' => 'wrong-' . $i])->assertSessionHasErrors('email');
        }

        $this->post('/en/login', ['email' => 'rahim@example.com', 'password' => 'Secret123'])
            ->assertSessionHasErrors(['email' => __('Too many login attempts. Please try again in :minutes minute(s) or reset your password.', ['minutes' => 5])]);
        $this->assertGuest('web');
    }

    public function test_remember_me_keeps_the_customer_signed_in(): void
    {
        $this->post('/en/login', ['email' => 'rahim@example.com', 'password' => 'Secret123', 'remember' => '1'])->assertRedirect();

        $this->assertAuthenticatedAs($this->user, 'web');
        $this->assertNotNull($this->user->fresh()->remember_token);
    }

    public function test_forgot_password_does_not_reveal_which_emails_have_accounts(): void
    {
        $known = $this->post('/en/forgot-password', ['email' => 'rahim@example.com']);
        $unknown = $this->post('/en/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame($known->getSession()->get('success'), $unknown->getSession()->get('success'));
        $unknown->assertSessionHasNoErrors();

        $stored = DB::table('password_reset_tokens')->where('email', 'rahim@example.com')->value('token');
        $this->assertNotNull($stored);
        $this->assertStringStartsWith('$2y$', $stored, 'Only a hash of the reset token is stored.');
        $this->assertNull(DB::table('password_reset_tokens')->where('email', 'nobody@example.com')->first());
    }

    public function test_password_reset_needs_the_real_token_and_signs_out_old_sessions(): void
    {
        DB::table('password_reset_tokens')->insert(['email' => 'rahim@example.com', 'token' => Hash::make('plain-token'), 'created_at' => now()]);
        $oldRemember = $this->user->fresh()->remember_token;

        $this->post('/en/reset-password', ['email' => 'rahim@example.com', 'token' => 'guessed', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHas('error');

        $this->post('/en/reset-password', ['email' => 'rahim@example.com', 'token' => 'plain-token', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertRedirect(route('user.auth.login'));

        $user = $this->user->fresh();
        $this->assertTrue(Hash::check('NewPass123', $user->password));
        $this->assertNotSame($oldRemember, $user->remember_token);
        $this->assertNull(DB::table('password_reset_tokens')->where('email', 'rahim@example.com')->first(), 'The token works only once.');
    }

    public function test_weak_passwords_are_rejected_at_registration(): void
    {
        $this->post('/en/register', ['name' => 'Karim', 'email' => 'karim@example.com', 'password' => '123456', 'password_confirmation' => '123456'])
            ->assertSessionHasErrors('password');
        $this->post('/en/register', ['name' => 'Karim', 'email' => 'karim@example.com', 'password' => 'Karim2026', 'password_confirmation' => 'Karim2026'])
            ->assertSessionHasNoErrors();
        $this->assertStringStartsWith('U-', User::where('email', 'karim@example.com')->value('user_id'));
    }

    public function test_profile_save_keeps_verification_and_needs_the_current_password_for_sensitive_changes(): void
    {
        $profile = ['name' => 'Rahim Uddin', 'email' => 'rahim@example.com'];

        $this->actingAs($this->user)->put('/en/user/profile', $profile)->assertSessionHasNoErrors();
        $this->assertTrue((bool) $this->user->fresh()->is_email_verified, 'Saving the profile must not un-verify the email.');

        $this->actingAs($this->user)->put('/en/user/profile', ['email' => 'thief@example.com'] + $profile)
            ->assertSessionHasErrors('current_password');
        $this->actingAs($this->user)->put('/en/user/profile', ['password' => 'Hijack123', 'password_confirmation' => 'Hijack123'] + $profile)
            ->assertSessionHasErrors('current_password');
        $this->assertSame('rahim@example.com', $this->user->fresh()->email);

        $this->actingAs($this->user)->put('/en/user/profile', ['email' => 'new@example.com', 'current_password' => 'Secret123'] + $profile)
            ->assertSessionHasNoErrors();
        $user = $this->user->fresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertFalse((bool) $user->is_email_verified, 'A new address has to be verified again.');

        $this->actingAs($user)->put('/en/user/profile', ['is_email_verified' => 1] + ['name' => 'Rahim', 'email' => 'new@example.com'])->assertSessionHasNoErrors();
        $this->assertFalse((bool) $user->fresh()->is_email_verified, 'Customers cannot verify themselves.');
    }

    public function test_social_login_customers_can_set_a_first_password_without_a_current_one(): void
    {
        $social = User::create(['name' => 'Social', 'email' => 'social@example.com', 'password' => null, 'provider' => 'google', 'provider_id' => 'g-1']);

        $this->actingAs($social)->put('/en/user/profile', ['name' => 'Social', 'email' => 'social@example.com', 'password' => 'MyFirst123', 'password_confirmation' => 'MyFirst123'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('MyFirst123', $social->fresh()->password));
        $this->assertAuthenticatedAs($social->fresh(), 'web');
    }

    public function test_unknown_or_unconfigured_social_providers_are_not_found(): void
    {
        $this->get('/auth/github/redirect')->assertNotFound();
        config(['services.google.client_id' => null]);
        $this->get('/auth/google/redirect')->assertNotFound();
    }
}
