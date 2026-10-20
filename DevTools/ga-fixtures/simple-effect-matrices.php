<?php
// Shared source scenarios retain full snapshots and explicit expected values.
// These are not assertion-only behavior families.
// Add matching cards as rows with stable slugs and reviewed expectations.

foreach ([
    ['spirit-shard-level-three-sacrifice-draws-a-card', 'Spirit Shard', 3, false, '[Level 3+] Sacrificed: a card was drawn', '[Level 3+] **Sacrifice CARDNAME**: Draw a card.'],
    ['waited-accord-level-two-sacrifice-draws-a-card', 'Waited Accord', 2, true, '[Level 2+] Sacrificed: a card was drawn', '[Level 2+]** Sacrifice CARDNAME: **Draw a card.'],
] as [$slug, $card, $level, $ready, $label, $clause]) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
    // Preserve the token's champion-only setup and the card's element requirements.
    if ($ready) $s->readyFor($card, level: $level);
    else $s->champion(level: $level);
    $s->seed('myField', $card)
        ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
        ->expectZoneCount('myHand', 8, 1, $label)
        ->tested($card)
        ->covers(['level-gate', 'activated-ability', 'sacrifice-self', 'draw'], $clause)
        ->register();
}

// Null payment preserves the existing seeded-entry case; other rows play from hand.
foreach ([
    ['aenean-flux-generator-on-enter-draws-a-card-into-memory', 'Aenean Flux Generator', 3, 4, 'The 3 paid cards plus the card drawn into memory', '**On Enter: **Draw a card into your memory.'],
    ['poisonous-breezecap-on-enter-draws-a-card-into-memory', 'Poisonous Breezecap', 3, 4, 'The 3 paid cards plus the card drawn into memory', '**On Enter: **Draw a card into your memory.'],
    ['whisperwind-compass-on-enter-draws-a-card-into-memory', 'Whisperwind Compass', 3, 4, 'The 3 paid cards plus the card drawn into memory', '**On Enter: **Draw a card into your memory.'],
    ['captivating-opulence-on-enter-draws-a-card-into-memory', 'Captivating Opulence', null, 1, 'On Enter: drew a card into memory', '**On Enter**: Draw a card into your memory.'],
] as [$slug, $card, $payment, $memoryCount, $label, $clause]) {
    $s = gaScenario($slug);
    if ($payment === null) $s->standardDeck($card)->readyFor($card)->seed('myField', $card)->observe();
    else $s->deckTemplate('quiet', $card)->readyFor($card)->seed('myHand', $card)->play($card)->pay($payment);
    $s->expectZoneCount('myMemory', $memoryCount, 1, $label)
        ->tested($card)->covers(['on-enter', 'draw-into-memory'], $clause)->register();
}
