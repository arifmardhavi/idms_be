<?php

namespace Tests\Feature;

use App\Models\HakAkses;
use App\Models\HistoricalMemorandum;
use App\Models\LampiranMemo;
use App\Models\User;
use App\Models\UserHakAkses;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class FileAccessTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, string> */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    private function userWithAccess(bool $withAccess = true, string $permission = 'get_historical_memorandum', int $status = 1): User
    {
        $suffix = bin2hex(random_bytes(5));
        $username = 'file_access_'.$suffix;

        $user = User::create([
            'fullname' => 'File access test',
            'email' => $username.'@example.test',
            'username' => $username,
            'password' => 'secret123',
            'level_user' => 1,
            'status' => $status,
        ]);

        if ($withAccess) {
            $hakAksesId = HakAkses::where('hak_akses', $permission)->value('id');
            $this->assertNotNull($hakAksesId, 'Seeder hak akses tidak ditemukan: '.$permission);

            UserHakAkses::create(['user_id' => $user->id, 'hak_akses_id' => $hakAksesId]);
        }

        return $user;
    }

    /** @return array{0: HistoricalMemorandum, 1: string} */
    private function memorandumWithFile(): array
    {
        $filename = 'file_access_test_'.bin2hex(random_bytes(6)).'.pdf';
        $path = public_path('historical_memorandum/'.$filename);
        file_put_contents($path, "%PDF-1.4\n% file access test\n%%EOF");
        $this->createdFiles[] = $path;

        $memorandum = HistoricalMemorandum::create([
            'unit_id' => 1,
            'category_id' => 1,
            'no_dokumen' => 'TEST/'.$filename,
            'perihal' => 'File access test',
            'tipe_memorandum' => 1,
            'tanggal_terbit' => now()->toDateString(),
            'memorandum_file' => $filename,
        ]);

        return [$memorandum, $filename];
    }

    /** @return array{0: LampiranMemo, 1: string} */
    private function lampiranWithFile(): array
    {
        [$memorandum] = $this->memorandumWithFile();

        $filename = 'lampiran_test_'.bin2hex(random_bytes(6)).'.pdf';
        $path = public_path('historical_memorandum/lampiran/'.$filename);
        file_put_contents($path, "%PDF-1.4\n% lampiran test\n%%EOF");
        $this->createdFiles[] = $path;

        $lampiran = LampiranMemo::create([
            'historical_memorandum_id' => $memorandum->id,
            'lampiran_memo' => $filename,
        ]);

        return [$lampiran, $filename];
    }

    public function test_user_with_access_can_open_memorandum_file_url(): void
    {
        $user = $this->userWithAccess();
        [$memorandum, $filename] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/'.$filename);

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('Content-Type') ?? ''));
    }

    public function test_guest_cannot_open_memorandum_file_url(): void
    {
        [$memorandum, $filename] = $this->memorandumWithFile();

        $this->get('/historical_memorandum/'.$filename)->assertNotFound();
    }

    public function test_user_without_permission_cannot_open_memorandum_file_url(): void
    {
        $user = $this->userWithAccess(false);
        [$memorandum, $filename] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/'.$filename)
            ->assertNotFound();
    }

    public function test_login_cookie_opens_memorandum_file_url(): void
    {
        $user = $this->userWithAccess();
        [$memorandum, $filename] = $this->memorandumWithFile();

        $login = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'secret123',
        ]);
        $login->assertOk();

        $cookieName = config('file_access.cookie');
        $cookie = $login->getCookie($cookieName, false);

        $this->assertNotNull($cookie, 'Login harus mengirim cookie akses file.');

        $this->withUnencryptedCookie($cookieName, $cookie->getValue())
            ->get('/historical_memorandum/'.$filename)
            ->assertOk();
    }

    public function test_logout_removes_file_access_cookie(): void
    {
        $user = $this->userWithAccess();
        $login = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'secret123',
        ]);
        $login->assertOk();

        $cookieName = config('file_access.cookie');
        $token = $login->getCookie($cookieName, false)->getValue();

        $logout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $logout->assertOk();
        $logout->assertCookieExpired($cookieName);
    }

    public function test_refresh_rotates_file_access_cookie(): void
    {
        $user = $this->userWithAccess();
        $login = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'secret123',
        ]);
        $login->assertOk();

        $cookieName = config('file_access.cookie');
        $token = $login->getCookie($cookieName, false)->getValue();

        $refresh = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/refresh');

        $refresh->assertOk();
        $refreshedCookie = $refresh->getCookie($cookieName, false);
        $this->assertNotNull($refreshedCookie);
        $this->assertNotEmpty($refreshedCookie->getValue());
    }

    public function test_user_with_lampiran_permission_can_open_lampiran_file_url(): void
    {
        $user = $this->userWithAccess(true, 'get_historical_memorandum_lampiran');
        [$lampiran, $filename] = $this->lampiranWithFile();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/lampiran/'.$filename);

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('Content-Type') ?? ''));
    }

    public function test_guest_cannot_open_lampiran_file_url(): void
    {
        [$lampiran, $filename] = $this->lampiranWithFile();

        $this->get('/historical_memorandum/lampiran/'.$filename)->assertNotFound();
    }

    public function test_user_without_lampiran_permission_cannot_open_lampiran_file_url(): void
    {
        $user = $this->userWithAccess();
        [$lampiran, $filename] = $this->lampiranWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/lampiran/'.$filename)
            ->assertNotFound();
    }

    public function test_unknown_filename_returns_404(): void
    {
        $user = $this->userWithAccess();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/not_registered_'.$token.'.pdf')
            ->assertNotFound();
    }

    public function test_path_traversal_returns_404(): void
    {
        $user = $this->userWithAccess();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/..%2Fnot_registered.pdf')
            ->assertNotFound();
    }

    public function test_inactive_user_cannot_open_memorandum_file_url(): void
    {
        $user = $this->userWithAccess(true, 'get_historical_memorandum', 0);
        [$memorandum, $filename] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/'.$filename)
            ->assertNotFound();
    }

    public function test_superuser_without_hak_akses_row_can_open_memorandum_file_url(): void
    {
        $user = $this->userWithAccess(false);
        [$memorandum, $filename] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        config()->set('file_access.superusers', [$user->username]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/'.$filename)
            ->assertOk();
    }

    public function test_user_without_permission_cannot_use_api_download_endpoint(): void
    {
        $user = $this->userWithAccess(false);
        [$memorandum] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/historical_memorandum/download_file/'.$memorandum->id)
            ->assertNotFound();
    }

    public function test_user_with_permission_can_use_api_download_endpoint(): void
    {
        $user = $this->userWithAccess();
        [$memorandum] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/historical_memorandum/download_file/'.$memorandum->id)
            ->assertOk();
    }

    public function test_api_download_response_has_private_no_store_cache_header(): void
    {
        $user = $this->userWithAccess();
        [$memorandum] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/historical_memorandum/download_file/'.$memorandum->id)
            ->assertOk()
            ->assertHeader('Cache-Control');

        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_direct_file_response_has_private_no_store_cache_header(): void
    {
        $user = $this->userWithAccess();
        [, $filename] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/'.$filename)
            ->assertOk()
            ->assertHeader('Cache-Control');

        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_user_without_permission_cannot_use_lampiran_api_download_endpoint(): void
    {
        $user = $this->userWithAccess();
        [$lampiran] = $this->lampiranWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/lampiran_memo/download_file/'.$lampiran->id)
            ->assertNotFound();
    }

    public function test_guest_cannot_open_generated_memorandum_zip_url(): void
    {
        $this->get('/file_historical_memorandum.zip')->assertNotFound();
    }

    public function test_user_with_permission_can_open_generated_memorandum_zip_url(): void
    {
        $user = $this->userWithAccess();
        $token = JWTAuth::fromUser($user);

        $path = public_path('file_historical_memorandum.zip');
        $original = is_file($path) ? file_get_contents($path) : null;
        file_put_contents($path, "PK\x03\x04 file access test");

        try {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->get('/file_historical_memorandum.zip')
                ->assertOk();
        } finally {
            if ($original !== null) {
                file_put_contents($path, $original);
            } else {
                unlink($path);
            }
        }
    }

    public function test_api_download_rejects_traversal_filename_from_database(): void
    {
        $user = $this->userWithAccess();
        $token = JWTAuth::fromUser($user);

        $secretName = 'download_traversal_secret_'.bin2hex(random_bytes(5)).'.pdf';
        $secretPath = public_path($secretName);
        file_put_contents($secretPath, '%PDF-1.4 secret');
        $this->createdFiles[] = $secretPath;

        $memorandum = $this->memorandumWithFile()[0];
        $memorandum->update(['memorandum_file' => '../'.$secretName]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/historical_memorandum/download_file/'.$memorandum->id)
            ->assertNotFound();
    }

    public function test_memorandum_permission_can_read_lampiran_metadata(): void
    {
        $user = $this->userWithAccess();
        [$lampiran, $filename] = $this->lampiranWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/historical_memorandum/lampiran/'.$lampiran->historical_memorandum_id)
            ->assertOk();
    }

    public function test_user_with_lampiran_permission_can_open_generated_lampiran_zip_url(): void
    {
        $user = $this->userWithAccess(true, 'get_historical_memorandum_lampiran');
        $token = JWTAuth::fromUser($user);

        $path = public_path('file_lampiran_memo.zip');
        $original = is_file($path) ? file_get_contents($path) : null;
        file_put_contents($path, "PK\x03\x04 lampiran test");

        try {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->get('/file_lampiran_memo.zip')
                ->assertOk();
        } finally {
            if ($original !== null) {
                file_put_contents($path, $original);
            } else {
                unlink($path);
            }
        }
    }

    public function test_invalid_cookie_token_returns_404(): void
    {
        [, $filename] = $this->memorandumWithFile();

        $this->withUnencryptedCookie(config('file_access.cookie'), 'not-a-jwt')
            ->get('/historical_memorandum/'.$filename)
            ->assertNotFound();
    }

    public function test_stale_header_does_not_block_valid_cookie(): void
    {
        $user = $this->userWithAccess();
        [, $filename] = $this->memorandumWithFile();
        $validToken = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->withUnencryptedCookie(config('file_access.cookie'), $validToken)
            ->get('/historical_memorandum/'.$filename)
            ->assertOk();
    }

    public function test_token_cannot_be_reused_after_logout(): void
    {
        $user = $this->userWithAccess();
        [, $filename] = $this->memorandumWithFile();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/historical_memorandum/'.$filename)
            ->assertNotFound();
    }

    public function test_wrong_permission_cannot_open_memorandum_zip(): void
    {
        $user = $this->userWithAccess(true, 'get_historical_memorandum_lampiran');
        $token = JWTAuth::fromUser($user);

        $path = public_path('file_historical_memorandum.zip');
        $original = is_file($path) ? file_get_contents($path) : null;
        file_put_contents($path, "PK\x03\x04 zip test");

        try {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->get('/file_historical_memorandum.zip')
                ->assertNotFound();
        } finally {
            if ($original !== null) {
                file_put_contents($path, $original);
            } else {
                unlink($path);
            }
        }
    }

    public function test_refresh_replaces_file_access_cookie_token(): void
    {
        $user = $this->userWithAccess();
        $login = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'secret123',
        ]);
        $login->assertOk();

        $cookieName = config('file_access.cookie');
        $oldToken = $login->getCookie($cookieName, false)->getValue();

        $refresh = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->postJson('/api/refresh');
        $refresh->assertOk();

        $newToken = $refresh->getCookie($cookieName, false)->getValue();
        $this->assertNotEmpty($newToken);
        $this->assertNotSame($oldToken, $newToken);
    }
}
