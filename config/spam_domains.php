<?php

/*
|--------------------------------------------------------------------------
| Blocked / disposable email domains
|--------------------------------------------------------------------------
|
| Used by App\Rules\NotDisposableEmailDomain to reject sign-ups from
| throwaway email providers. Seeded from the domains observed in the legacy
| TMI Archive spam-registration wave (2023–2025). The app currently has no
| open registration, so nothing calls the rule yet — it is ready to attach to
| any future signup / user-creation form (e.g. a Filament registration page).
|
| Extend these lists as new spam patterns appear.
|
*/

return [

    // Exact domains observed spamming the archive. High confidence — these are
    // throwaway domains used to create bulk fake accounts.
    'domains' => [
        'rightbliss.beauty',
        'silesia.life',
        'monochord.xyz',
        'carnana.art',
        'pointel.xyz',
        'tonetics.biz',
        'anaphora.team',
        'balneary.biz',
        'chilgoza.buzz',
        'paravane.biz',
        'purline.top',
        'sabletree.foundation',
        'sandcress.xyz',
        'scranch.shop',
        'spectrail.world',
        'spinapp.bar',
        'tarboosh.shop',
        'usufruct.bar',
        'virilia.life',
        'do-not-respond.me',
    ],

    // Whole TLDs that, for this site, were used exclusively by spam. This is a
    // deliberately aggressive heuristic — trim it if you expect legitimate
    // members on any of these TLDs.
    'tlds' => [
        'beauty',
        'life',
        'xyz',
        'art',
        'biz',
        'team',
        'buzz',
        'top',
        'foundation',
        'shop',
        'world',
        'bar',
    ],

];
