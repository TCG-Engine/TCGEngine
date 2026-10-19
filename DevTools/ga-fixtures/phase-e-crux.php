<?php
/**
 * Phase E (Crux): Vorpal Sword Merlin Bonus abilities, Merlin, Kingslayer's even-count attack bonus,
 * The Majestic Spirit's "prevent half, rounded up" damage prevention.
 */

const GA_VORPAL_SHROUD_TEXT = '[Merlin Bonus] [REST]**, Banish a card from your material deck:** CARDNAME gains **spellshroud** until end of turn.';

// Two material cards: the player chooses which one is banished (the second, here), the Sword rests, and the effect lands.
gaScenario('vorpal-sword-merlin-bonus-rest-and-banish-a-chosen-material-card-grants-spellshroud')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myField', 'Vorpal Sword', properties: ['Status' => 2])
    ->seed('myMaterial', 'Training Sword')->seed('myMaterial', 'Spirit Blade: Ensoul')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myMaterial-5')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '1', 'The Sword rested to pay [REST]')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","SPELLSHROUD"]', 'The Sword gained spellshroud')
    ->expectCardExists('myBanish', 'Spirit Blade: Ensoul', 'The chosen material card (not the first one) was banished')
    ->expectZoneCount('myMaterial', 5, 1, 'Four deck material cards plus two seeded, minus the one banished')
    ->tested('Vorpal Sword')
    ->covers(['merlin-bonus', 'rest', 'banish-material', 'spellshroud'], GA_VORPAL_SHROUD_TEXT)
    ->register();

const GA_MAJESTIC_HALF_TEXT = 'If another crux element unit you control would take damage, prevent half of that damage, rounded up.';

// P2's Giant Tortoise (power 1, plus $buffs buff counters) attacks the Crux Ghosts of Pendragon; Majestic Spirit (if $spirit) stands beside it.
function gaMajesticCombat(string $slug, int $buffs, bool $spirit = true, string $target = 'Ghosts of Pendragon'): GaScenario {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('The Majestic Spirit');
    if ($spirit) $s->seed('myField', 'The Majestic Spirit');
    $s->seed('myField', $target);
    if ($target === 'Ghosts of Pendragon') $s->answer(1, '-');
    $props = ['Status' => 2];
    if ($buffs > 0) $props['Counters'] = ['buff' => $buffs];
    return $s->seed('myField', 'Giant Tortoise', player: 2, properties: $props)
        ->endTurn()->pass(1)->pass(1)
        ->attack('myField-1', 'theirField-' . ($spirit ? 2 : 1), player: 2)->pass(1);
}

gaMajesticCombat('the-majestic-spirit-rounds-the-prevention-of-odd-combat-damage-up', 2)
    ->expectDamage('p1Field-2', 1, 'Three combat damage: half rounded up (2) is prevented, 1 is taken')
    ->expectDqEmpty()
    ->tested('The Majestic Spirit')
    ->covers(['prevention', 'crux', 'rounding'], GA_MAJESTIC_HALF_TEXT)
    ->register();

gaMajesticCombat('the-majestic-spirit-prevents-all-of-a-single-point-of-damage', 0)
    ->expectDamage('p1Field-2', 0, 'One combat damage: half rounded up (1) is prevented')
    ->expectDqEmpty()
    ->tested('The Majestic Spirit')
    ->covers(['prevention', 'crux', 'rounding'], GA_MAJESTIC_HALF_TEXT)
    ->register();

gaMajesticCombat('without-the-majestic-spirit-odd-combat-damage-is-taken-in-full', 2, spirit: false)
    ->expectDamage('p1Field-1', 3, 'No Majestic Spirit: all three combat damage is taken')
    ->expectDqEmpty()
    ->tested('The Majestic Spirit')
    ->covers(['prevention', 'condition'], GA_MAJESTIC_HALF_TEXT)
    ->register();

// "Another": the Spirit itself is not protected.
gaScenario('the-majestic-spirit-does-not-halve-damage-dealt-to-itself')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('The Majestic Spirit')
    ->seed('myField', 'The Majestic Spirit')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2, 'Counters' => ['buff' => 2]])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-1', player: 2)->pass(1)
    ->expectDamage('p1Field-1', 3, 'The Spirit is not "another" unit: all three combat damage is taken')
    ->expectDqEmpty()
    ->tested('The Majestic Spirit')
    ->covers(['prevention', 'another'], GA_MAJESTIC_HALF_TEXT)
    ->register();

// Only crux element units are protected.
gaScenario('the-majestic-spirit-does-not-halve-damage-dealt-to-a-non-crux-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('The Majestic Spirit')
    ->seed('myField', 'The Majestic Spirit')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2, 'Counters' => ['buff' => 2]])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-2', player: 2)->pass(1)
    ->expectZoneCount('myGraveyard', 1, 1, 'The 3-life Dungeon Guide died: a non-crux unit takes all three combat damage (halved it would have survived)')
    ->expectDqEmpty()
    ->tested('The Majestic Spirit')
    ->covers(['prevention', 'crux', 'condition'], GA_MAJESTIC_HALF_TEXT)
    ->register();

const GA_VORPAL_HIT_TEXT = '[Merlin Bonus] **On Ally Hit:** You may remove a **preparation** counter from your champion. If you do, wake up the attacker. Trigger this ability only once per turn.';

// The Merlin champion (with a preparation counter) attacks with Vorpal Sword and hits P2's Night Barker.
function gaVorpalHit(string $slug, bool $merlin = true, int $prep = 1): GaScenario {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
    if ($merlin) $s->champion(cardName: 'Merlin, Memorite Vassal');
    $s->seed('myField', 'Vorpal Sword');
    if ($prep > 0) $s->patch('myField-0', ['Counters' => ['preparation' => $prep]]);
    return $s->seed('myField', 'Ghosts of Pendragon', player: 2)
        ->reachTurnThree(fastCardInHand: true)
        ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-1');
}

$gaVorpalResolveHit = fn($sc) => $sc->pass(1)->pass(2);

$gaVorpalResolveHit(gaVorpalHit('vorpal-sword-merlin-bonus-on-ally-hit-yes-removes-a-preparation-counter-and-wakes-the-attacking-champion'))
    ->answer(1, 'YES')
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion woke up after attacking with the Sword')
    ->expectProperty('p1Field-1', 'TurnEffects', '["PIcB5KuuMd_USED"]', 'The once-per-turn marker is on the Sword')
    ->expectDamage('p2Field-1', 3, 'The Sword hit the ally for 3')
    ->tested('Vorpal Sword')
    ->covers(['merlin-bonus', 'on-ally-hit', 'counter', 'wake-up'], GA_VORPAL_HIT_TEXT)
    ->register();

$gaVorpalResolveHit(gaVorpalHit('vorpal-sword-merlin-bonus-on-ally-hit-declined-keeps-the-counter-and-the-attacker-rested'))
    ->answer(1, 'NO')
    ->expectProperty('p1Field-0', 'Status', '1', 'Declined: the champion stays rested')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1,"_champCombatDamageDealtThisTurn":3}', 'Declined: the preparation counter stays')
    ->tested('Vorpal Sword')
    ->covers(['merlin-bonus', 'on-ally-hit', 'optional-choice'], GA_VORPAL_HIT_TEXT)
    ->register();

$gaVorpalResolveHit(gaVorpalHit('vorpal-sword-on-ally-hit-needs-the-merlin-bonus', merlin: false))
    ->expectDqEmpty()
    ->expectDamage('p2Field-1', 3, 'The Sword attack hit the ally')
    ->expectProperty('p1Field-0', 'Status', '1', 'No Merlin Bonus: nothing woke the champion')
    ->tested('Vorpal Sword')
    ->covers(['merlin-bonus', 'on-ally-hit', 'condition'], GA_VORPAL_HIT_TEXT)
    ->register();

$gaVorpalResolveHit(gaVorpalHit('vorpal-sword-on-ally-hit-needs-a-preparation-counter-on-the-champion', prep: 0))
    ->expectDqEmpty()
    ->expectDamage('p2Field-1', 3, 'The Sword attack hit the ally')
    ->expectProperty('p1Field-0', 'Status', '1', 'No preparation counter to remove: no prompt, the champion stays rested')
    ->tested('Vorpal Sword')
    ->covers(['merlin-bonus', 'on-ally-hit', 'counter', 'condition'], GA_VORPAL_HIT_TEXT)
    ->register();

// A lethal hit is still a hit: the ally dies from the Sword's 3 damage, and the trigger still offers the wake-up.
gaScenario('vorpal-sword-on-ally-hit-triggers-even-when-the-hit-kills-the-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myField', 'Vorpal Sword')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree(fastCardInHand: true)
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-1')
    ->pass(1)->pass(2)
    ->answer(1, 'YES')
    ->expectCardExists('theirGraveyard', 'Dungeon Guide', 'The 3-life ally died to the 3 damage')
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion woke up: a lethal hit still triggers On Ally Hit')
    ->tested('Vorpal Sword')
    ->covers(['merlin-bonus', 'on-ally-hit', 'wake-up'], GA_VORPAL_HIT_TEXT)
    ->register();

// "On Ally Hit" on a weapon belongs to attacks made with it: an ally attacking on its own does not trigger the Sword.
gaScenario('vorpal-sword-on-ally-hit-does-not-trigger-for-an-ally-attacking-without-the-sword')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Memorite Vassal')
    ->seed('myField', 'Vorpal Sword')
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 2])
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myField', 'Ghosts of Pendragon', player: 2)
    ->reachTurnThree(fastCardInHand: true)
    ->attack('myField-2', 'theirField-1')->pass(1)->pass(2)
    ->expectDqEmpty()
    ->expectDamage('p2Field-1', 1, 'The Tortoise hit the ally for 1')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'The Sword did not take part: the preparation counter is still there')
    ->tested('Vorpal Sword')
    ->covers(['merlin-bonus', 'on-ally-hit', 'condition'], GA_VORPAL_HIT_TEXT)
    ->register();

const GA_KINGSLAYER_TEXT = 'At the beginning of your recollection phase, put a **level** counter on CARDNAME. Then, if there\'s an even amount of **level** counters on CARDNAME, draw a card and CARDNAME\'s attacks get +2 [POWER] until end of turn.';

// Merlin, Kingslayer attacks the opposing champion with a Training Sword (power 1) on his turn 3 recollection with $levels level counters already on him.
function gaKingslayerAttack(string $slug, int $levels): GaScenario {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->champion(cardName: 'Merlin, Kingslayer');
    if ($levels > 0) $s->patch('myField-0', ['Counters' => ['level' => $levels]]);
    return $s->seed('myField', 'Training Sword')->reachTurnThree()
        ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0');
}

gaKingslayerAttack('merlin-kingslayer-an-even-level-count-gives-the-champions-attack-plus-two-power', 1)
    ->expectDamage('p2Field-0', 3, 'Training Sword (1) + 2: the champion attack dealt 3')
    ->tested('Merlin, Kingslayer')
    ->covers(['recollection', 'stat-change', 'attack'], GA_KINGSLAYER_TEXT)
    ->register();

gaKingslayerAttack('merlin-kingslayer-an-odd-level-count-gives-the-champions-attack-no-bonus', 0)
    ->expectDamage('p2Field-0', 1, 'Odd count: the champion attack dealt only the Training Sword\'s 1')
    ->tested('Merlin, Kingslayer')
    ->covers(['recollection', 'stat-change', 'condition'], GA_KINGSLAYER_TEXT)
    ->register();

gaScenario('merlin-kingslayer-the-even-count-bonus-does-not-boost-an-allys-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(cardName: 'Merlin, Kingslayer')
    ->patch('myField-0', ['Counters' => ['level' => 1]])
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 2])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(1)
    ->expectDamage('p2Field-0', 1, 'Only CARDNAME\'s attacks get +2: the Tortoise dealt its own 1')
    ->tested('Merlin, Kingslayer')
    ->covers(['recollection', 'stat-change', 'condition'], GA_KINGSLAYER_TEXT)
    ->register();
