<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DjangoPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function djangoHash(string $password, string $salt = 'saltsaltsalt', int $iterations = 216000): string
    {
        $dk = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);

        return sprintf('pbkdf2_sha256$%d$%s$%s', $iterations, $salt, base64_encode($dk));
    }

    public function test_django_hash_verifies_and_is_flagged_for_rehash(): void
    {
        $hash = $this->djangoHash('correct horse');

        $this->assertTrue(Hash::check('correct horse', $hash));
        $this->assertFalse(Hash::check('wrong', $hash));
        $this->assertTrue(Hash::needsRehash($hash));
    }

    public function test_user_with_imported_django_hash_can_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy@example.com',
            'password' => $this->djangoHash('s3cret-pass'),
        ]);

        $this->assertTrue(Auth::attempt([
            'email' => 'legacy@example.com',
            'password' => 's3cret-pass',
        ]));

        $this->assertFalse(Auth::attempt([
            'email' => 'legacy@example.com',
            'password' => 'nope',
        ]));
    }
}
