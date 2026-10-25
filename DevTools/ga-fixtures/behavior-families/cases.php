<?php
// Explicit membership: similar rules text alone does not establish equivalent timing/costs.
// Expected amounts are reviewed rules data, never queried from the implementation under test.
return [
    'palace-guard-taunt-protects-the-champion' => ['taunt', 'Palace Guard', '**Taunt**', false],
    'unyielding-wraithguard-taunt-protects-the-champion' => ['taunt', 'Unyielding Wraithguard', '**Taunt**', false],
    'titan-mk-ii-token-taunt-must-be-attacked-first' => ['taunt', 'Titan Mk II', '**Taunt**, **Vigor**', false],
    'unrelenting-warden-class-bonus-taunt-protects-the-champion' => ['taunt', 'Unrelenting Warden', '[Class Bonus] **Taunt**', true],
    'vigilant-sentry-class-bonus-taunt-protects-the-champion' => ['taunt', 'Vigilant Sentry', '[Class Bonus] **Taunt**', true],
    'sworn-windhand-class-bonus-taunt-protects-the-champion' => ['taunt', 'Sworn Windhand', '[Class Bonus] **Taunt**', true],
    // family, card, recover amount, reserve payment, priority window
    'aenean-swelling-tides-recovers-three' => ['recover', 'Aenean Swelling Tides', 3, 2, 'both'],
    'refreshing-charge-recovers-three' => ['recover', 'Refreshing Charge', 3, 2, 'both'],
];
