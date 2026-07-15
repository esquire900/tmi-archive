<?php

namespace App\Support\Hashing;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Hashing\BcryptHasher;

/**
 * Password hasher that transparently verifies legacy Django
 * `pbkdf2_sha256$<iterations>$<salt>$<hash>` password hashes, while hashing
 * any newly-set passwords with bcrypt.
 *
 * This lets the users imported from the old Django/Postgres database keep
 * logging in with their existing passwords. As soon as a user (or an admin)
 * updates a password it is re-hashed with bcrypt, and `needsRehash()` reports
 * legacy hashes as stale so the framework upgrades them opportunistically.
 */
class DjangoPbkdf2Hasher implements HasherContract
{
    public function __construct(
        protected BcryptHasher $bcrypt = new BcryptHasher(),
    ) {
    }

    /**
     * Hash the given value. New passwords use bcrypt.
     */
    public function make(#[\SensitiveParameter] $value, array $options = []): string
    {
        return $this->bcrypt->make($value, $options);
    }

    /**
     * Check the given plain value against a hash (Django pbkdf2 or bcrypt).
     */
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool
    {
        if ($hashedValue === null || $hashedValue === '') {
            return false;
        }

        if (str_starts_with($hashedValue, 'pbkdf2_sha256$')) {
            return $this->checkDjango($value, $hashedValue);
        }

        return $this->bcrypt->check($value, $hashedValue, $options);
    }

    /**
     * Legacy Django hashes should be upgraded to bcrypt on next login.
     */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        if (is_string($hashedValue) && str_starts_with($hashedValue, 'pbkdf2_sha256$')) {
            return true;
        }

        return $this->bcrypt->needsRehash($hashedValue, $options);
    }

    public function info($hashedValue): array
    {
        if (is_string($hashedValue) && str_starts_with($hashedValue, 'pbkdf2_sha256$')) {
            return ['algo' => 'django-pbkdf2_sha256', 'algoName' => 'pbkdf2_sha256'];
        }

        return $this->bcrypt->info($hashedValue);
    }

    /**
     * Verify a `pbkdf2_sha256$iterations$salt$b64hash` Django hash.
     */
    protected function checkDjango(#[\SensitiveParameter] string $value, string $encoded): bool
    {
        $parts = explode('$', $encoded, 4);
        if (count($parts) !== 4) {
            return false;
        }

        [$algorithm, $iterations, $salt, $hash] = $parts;

        if ($algorithm !== 'pbkdf2_sha256') {
            return false;
        }

        $iterations = (int) $iterations;
        if ($iterations <= 0) {
            return false;
        }

        // Django stores the derived key base64-encoded; its length in bytes
        // determines the key length passed to PBKDF2.
        $expected = base64_decode($hash, true);
        if ($expected === false) {
            return false;
        }

        $derived = hash_pbkdf2('sha256', $value, $salt, $iterations, strlen($expected), true);

        return hash_equals($expected, $derived);
    }
}
