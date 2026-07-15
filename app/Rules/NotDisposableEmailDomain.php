<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Reject email addresses whose domain (or TLD) is on the disposable/spam
 * blocklist in config/spam_domains.php.
 *
 * Usage (e.g. on a registration or user-creation form):
 *
 *     use App\Rules\NotDisposableEmailDomain;
 *
 *     $request->validate([
 *         'email' => ['required', 'email', new NotDisposableEmailDomain()],
 *     ]);
 *
 * Malformed values are ignored here so the standard `email` rule owns that
 * error message.
 */
class NotDisposableEmailDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            return;
        }

        $domain = Str::lower(trim(Str::afterLast($value, '@')));

        if ($domain === '') {
            return;
        }

        $blockedDomains = array_map('strtolower', (array) config('spam_domains.domains', []));
        $blockedTlds = array_map('strtolower', (array) config('spam_domains.tlds', []));

        $tld = Str::afterLast($domain, '.');

        if (in_array($domain, $blockedDomains, true) || in_array($tld, $blockedTlds, true)) {
            $fail('The :attribute uses a blocked or disposable email domain.');
        }
    }
}
