<?php
// Sets that are PREVIEWING — cards exist as mocks (AppCore/SWU/CardMocks.php) but the set
// has not released. Excluded from the "released" card pool (Eternal, Twin Suns) while listed
// here; still included by the wildcard pool ('*': Open, Goldfish, Hotseat).
//
// Remove a set from this list on its release day — that alone promotes it into the released
// pool. See docs/superpowers/specs/2026-07-29-swu-preview-format-design.md.
return array(
    'HMW',
    'IC27',
);
