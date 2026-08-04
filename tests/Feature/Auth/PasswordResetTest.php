<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** 1. GET forgot-password berhasil */
    public function test_forgot_password_page_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Lupa Password');
    }

    /** 2. Email kosong ditolak */
    public function test_empty_email_is_rejected(): void
    {
        $response = $this->post('/forgot-password', ['email' => '']);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
    }

    /** 3. Format email salah ditolak */
    public function test_invalid_email_format_is_rejected(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'format-salah-bukan-email']);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
    }

    /** 4. Email Petani terdaftar menghasilkan notifikasi */
    public function test_registered_petani_email_sends_reset_notification(): void
    {
        Notification::fake();

        $petani = User::factory()->create([
            'email' => 'petani@smartfarm.test',
            'role'  => 'user',
        ]);

        $response = $this->post('/forgot-password', ['email' => 'petani@smartfarm.test']);

        $response->assertStatus(302);
        Notification::assertSentTo($petani, ResetPasswordNotification::class);
    }

    /** 5. Email Admin terdaftar menghasilkan notifikasi */
    public function test_registered_admin_email_sends_reset_notification(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email' => 'admin@smartfarm.test',
            'role'  => 'admin',
        ]);

        $response = $this->post('/forgot-password', ['email' => 'admin@smartfarm.test']);

        $response->assertStatus(302);
        Notification::assertSentTo($admin, ResetPasswordNotification::class);
    }

    /** 6. Email tidak terdaftar tidak menghasilkan notifikasi */
    public function test_unregistered_email_does_not_send_notification(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', ['email' => 'tidak_ada@smartfarm.test']);

        $response->assertStatus(302);
        Notification::assertNothingSent();
    }

    /** 7. Popup email terdaftar dan tidak terdaftar memiliki judul yang sama */
    public function test_registered_and_unregistered_email_popups_have_same_title(): void
    {
        $user = User::factory()->create(['email' => 'ada@smartfarm.test']);

        $res1 = $this->post('/forgot-password', ['email' => 'ada@smartfarm.test']);
        $title1 = session('popup')['title'] ?? null;

        $res2 = $this->post('/forgot-password', ['email' => 'tidak_ada@smartfarm.test']);
        $title2 = session('popup')['title'] ?? null;

        $this->assertEquals('Permintaan Reset Password Diproses', $title1);
        $this->assertEquals('Permintaan Reset Password Diproses', $title2);
        $this->assertEquals($title1, $title2);
    }

    /** 8. Popup email terdaftar dan tidak terdaftar memiliki pesan yang sama */
    public function test_registered_and_unregistered_email_popups_have_same_message(): void
    {
        $user = User::factory()->create(['email' => 'ada@smartfarm.test']);

        $res1 = $this->post('/forgot-password', ['email' => 'ada@smartfarm.test']);
        $msg1 = session('popup')['message'] ?? null;

        $res2 = $this->post('/forgot-password', ['email' => 'tidak_ada@smartfarm.test']);
        $msg2 = session('popup')['message'] ?? null;

        $expectedMsg = 'Jika alamat email terdaftar pada sistem, tautan pengaturan ulang password telah dikirim. Silakan periksa kotak masuk atau folder spam.';
        $this->assertEquals($expectedMsg, $msg1);
        $this->assertEquals($expectedMsg, $msg2);
        $this->assertEquals($msg1, $msg2);
    }

    /** 9. Token dibuat hanya untuk email terdaftar */
    public function test_token_created_only_for_registered_email(): void
    {
        $user = User::factory()->create(['email' => 'ada@smartfarm.test']);

        $this->post('/forgot-password', ['email' => 'ada@smartfarm.test']);

        $tokenRecord = DB::table('password_reset_tokens')->where('email', 'ada@smartfarm.test')->first();
        $this->assertNotNull($tokenRecord);
    }

    /** 10. Token tidak dibuat untuk email tidak terdaftar */
    public function test_token_not_created_for_unregistered_email(): void
    {
        $this->post('/forgot-password', ['email' => 'tidak_ada@smartfarm.test']);

        $tokenRecord = DB::table('password_reset_tokens')->where('email', 'tidak_ada@smartfarm.test')->first();
        $this->assertNull($tokenRecord);
    }

    /** 11. Halaman reset dapat dibuka dengan token valid */
    public function test_reset_password_screen_can_be_opened_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);
        $token = Password::createToken($user);

        $response = $this->get('/reset-password/' . $token . '?email=' . urlencode($user->email));

        $response->assertStatus(200);
        $response->assertSee('Reset Password');
    }

    /** 12. Password dan konfirmasi berbeda ditolak */
    public function test_different_password_and_confirmation_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword456!',
        ]);

        $response->assertStatus(302);
        $this->assertEquals('Reset Password Gagal', session('popup')['title'] ?? null);
        $this->assertStringContainsString('harus sama', session('popup')['message'] ?? '');
    }

    /** 13. Token salah ditolak */
    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);

        $response = $this->post('/reset-password', [
            'token'                 => 'invalid-token-12345',
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(302);
        $this->assertEquals('Tautan Tidak Valid', session('popup')['title'] ?? null);
    }

    /** 14. Token kedaluwarsa ditolak */
    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);
        $token = Password::createToken($user);

        DB::table('password_reset_tokens')->where('email', $user->email)->update([
            'created_at' => now()->subHours(5),
        ]);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(302);
        $this->assertEquals('Tautan Tidak Valid', session('popup')['title'] ?? null);
    }

    /** 15. Email yang tidak sesuai token ditolak */
    public function test_email_mismatching_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => 'other_user@smartfarm.test',
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(302);
        $this->assertEquals('Tautan Tidak Valid', session('popup')['title'] ?? null);
    }

    /** 16. Token valid memperbarui password */
    public function test_valid_token_updates_user_password(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $this->assertEquals('Reset Password Berhasil', session('popup')['title'] ?? null);
    }

    /** 17. Password tersimpan dalam bentuk hash */
    public function test_password_is_saved_as_hash(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $updatedUser = $user->fresh();
        $this->assertTrue(Hash::check('NewPassword123!', $updatedUser->password));
        $this->assertNotEquals('NewPassword123!', $updatedUser->password);
    }

    /** 18. Password lama tidak dapat digunakan */
    public function test_old_password_cannot_be_used_after_reset(): void
    {
        $user = User::factory()->create([
            'email'    => 'user@smartfarm.test',
            'password' => Hash::make('OldPassword123!'),
        ]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $this->assertFalse(Hash::check('OldPassword123!', $user->fresh()->password));
    }

    /** 19. Password baru dapat digunakan untuk login */
    public function test_new_password_can_be_used_for_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'user@smartfarm.test',
            'password' => Hash::make('OldPassword123!'),
        ]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $loginResponse = $this->post('/login', [
            'email'    => 'user@smartfarm.test',
            'password' => 'NewPassword123!',
        ]);

        $loginResponse->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }

    /** 20. Token tidak dapat digunakan kembali */
    public function test_token_cannot_be_reused(): void
    {
        $user = User::factory()->create(['email' => 'user@smartfarm.test']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $secondResponse = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'AnotherPassword789!',
            'password_confirmation' => 'AnotherPassword789!',
        ]);

        $secondResponse->assertStatus(302);
        $this->assertEquals('Tautan Tidak Valid', session('popup')['title'] ?? null);
    }

    /** 21. Role Petani tidak berubah */
    public function test_petani_role_unchanged_after_password_reset(): void
    {
        $petani = User::factory()->create([
            'email' => 'petani@smartfarm.test',
            'role'  => 'user',
        ]);
        $token = Password::createToken($petani);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $petani->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $this->assertEquals('user', $petani->fresh()->role);
    }

    /** 22. Role Admin tidak berubah */
    public function test_admin_role_unchanged_after_password_reset(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@smartfarm.test',
            'role'  => 'admin',
        ]);
        $token = Password::createToken($admin);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $this->assertEquals('admin', $admin->fresh()->role);
    }

    /** 23. Nama dan email tidak berubah */
    public function test_user_name_and_email_unchanged_after_password_reset(): void
    {
        $user = User::factory()->create([
            'name'  => 'Petani Pak Budi',
            'email' => 'budi@smartfarm.test',
        ]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $updated = $user->fresh();
        $this->assertEquals('Petani Pak Budi', $updated->name);
        $this->assertEquals('budi@smartfarm.test', $updated->email);
    }

    /** 24. Popup reset berhasil muncul satu kali */
    public function test_success_popup_appears_once_on_login_view(): void
    {
        $response = $this->withSession([
            'popup' => [
                'type'    => 'success',
                'title'   => 'Reset Password Berhasil',
                'message' => 'Password berhasil diperbarui. Silakan masuk menggunakan password baru.',
            ]
        ])->get('/login');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, 'id="smartfarm-popup-overlay"'));
        $this->assertEquals(1, substr_count($content, 'id="smartfarm-popup-card"'));
    }

    /** 25. Popup gagal muncul satu kali */
    public function test_failed_popup_appears_once_on_forgot_password_view(): void
    {
        $response = $this->withSession([
            'popup' => [
                'type'    => 'error',
                'title'   => 'Tautan Tidak Valid',
                'message' => 'Tautan pengaturan ulang password tidak valid. Silakan kirim permintaan reset password kembali.',
            ]
        ])->get('/forgot-password');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, 'id="smartfarm-popup-overlay"'));
        $this->assertEquals(1, substr_count($content, 'id="smartfarm-popup-card"'));
    }
}
