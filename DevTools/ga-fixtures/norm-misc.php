<?php
/**
 * Phase 3d (Norm): leftovers (floating memory attack, command chessman attack, element-enabling domain). 'quiet' deck unless noted.
 */

gaFloatingPays('savage-smash-class-bonus-floating-memory-pays-a-memory-cost', 'Savage Smash', '[Class Bonus] **Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost. Apply this effect only if your champion’s class matches this card’s class.)*', 'GUARDIAN');
gaFloatingPays('savage-smash-without-the-class-bonus-cannot-pay-a-memory-cost', 'Savage Smash', '[Class Bonus] **Floating Memory**', 'WARRIOR', false);

// ---- Classical Opening (Command Chessman) ----

gaScenario('classical-opening-a-chessman-ally-performs-the-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Classical Opening')
    ->seed('myField', 'Weiss Bishop')
    ->seed('myHand', 'Classical Opening')
    ->reachTurnThree()
    ->play('Classical Opening')->pay(2)->target('myField-1')->target('theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 3, 'The Chessman ally performed the attack: 1 power + the card\'s 2')
    ->tested('Classical Opening')
    ->covers(['command-chessman', 'combat'], '**Command Chessman** *(A Chessman ally you control performs this attack.)*')
    ->register();

// ---- Prismatic Sanctuary ----

gaScenario('prismatic-sanctuary-enables-fire-water-and-wind-for-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Prismatic Sanctuary')
    ->seed('myField', 'Prismatic Sanctuary')
    ->seed('myHand', 'Weiss Bishop')
    ->play('Weiss Bishop')->pay(2)
    ->expectCardExists('myField', 'Weiss Bishop', 'Water is enabled by the domain even though the champion is a Spirit of Fire')
    ->tested('Prismatic Sanctuary')
    ->covers(['static', 'element'], 'Fire, water, and wind elements are enabled for you.')
    ->register();

gaScenario('without-prismatic-sanctuary-a-water-card-cannot-be-played-by-a-fire-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Prismatic Sanctuary')
    ->seed('myHand', 'Weiss Bishop')
    ->play('Weiss Bishop')->pay(2)
    ->expectZoneCount('myField', 1, 1, 'Only the champion is on the field: the water ally was not played')
    ->tested('Prismatic Sanctuary')
    ->covers(['static', 'element'], 'Control case for the element-enabling domain.')
    ->register();

// ---- Stabilizing BladeCore (Sword Weapon Link) ----
// Seeded link: the weapon's Subcards gains the core's id; the core's Counters record the weapon's card id.

if (!function_exists('gaBladeCoreLink')) {
    function gaBladeCoreLink(string $slug, string $template, bool $classBonus): GaScenario {
        return gaScenario($slug)->deckTemplate($template, 'Dungeon Guide')
            ->readyFor('Stabilizing BladeCore', classBonus: $classBonus)
            ->seed('myField', 'Bulwark Sword', properties: ['Subcards' => [GaScenario::id('Stabilizing BladeCore')]])
            ->seed('myField', 'Stabilizing BladeCore', properties: ['Counters' => ['linkedToWeapon' => GaScenario::id('Bulwark Sword')]]);
    }
}

gaBladeCoreLink('stabilizing-bladecore-class-bonus-linked-sword-gets-plus-two-power', 'quiet', true)
    ->observe()->expectPower('p1Field-1', 4, '[Class Bonus] Bulwark Sword 2 + 2 power (the champion matches the core\'s Warrior class, not the sword\'s Guardian class)')
    ->tested('Stabilizing BladeCore')
    ->covers(['class-bonus', 'weapon-link', 'stat-change'], '[Class Bonus] Linked weapon gets +2 [POWER].')
    ->register();

gaBladeCoreLink('stabilizing-bladecore-without-the-class-bonus-linked-sword-keeps-its-power', 'quiet', false)
    ->observe()->expectPower('p1Field-1', 2, 'No class bonus: the printed 2 power')
    ->tested('Stabilizing BladeCore')
    ->covers(['class-bonus', 'weapon-link', 'condition'], '[Class Bonus] Linked weapon gets +2 [POWER].')
    ->register();

gaScenario('stabilizing-bladecore-champion-level-up-puts-a-durability-counter-on-the-linked-sword')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Bulwark Sword', properties: ['Subcards' => [GaScenario::id('Stabilizing BladeCore')]])
    ->seed('myField', 'Stabilizing BladeCore', properties: ['Counters' => ['linkedToWeapon' => GaScenario::id('Bulwark Sword')]])
    ->seed('myGraveyard', 'Ordinary Horse')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->expectProperty('p1Field-0', 'Counters', '{"durability":3}', 'The champion levelled up: 3 durability counters: the 2 the Bulwark Sword enters with plus one from the core at the level up')
    ->tested('Stabilizing BladeCore')
    ->covers(['trigger', 'level-up', 'weapon-link', 'counter'], 'Whenever your champion levels up, put a **durability** counter on linked weapon.')
    ->register();

// ---- Business Card ----

if (!function_exists('gaBusinessCard')) {
    // The Raccoon sits $depth cards down the deck (Dungeon Guides above it); the card's name is chosen as it enters (seeding fires the choice).
    function gaBusinessCard(string $slug, int $depth, bool $phone): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Business Card')
            ->deckTop(array_merge(array_fill(0, $depth, 'Dungeon Guide'), ['Banner Raccoon']));
        if ($phone) $s->seed('myField', 'SignalTech One')->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=');
        return $s->seed('myField', 'Business Card')->answer(1, 'Banner Raccoon');
    }
}

gaBusinessCard('business-card-sacrifice-scavenges-eight-for-the-chosen-ally-name', 7, false)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->answer(1, 'myTempZone-0')
    ->expectCardExists('myHand', 'Banner Raccoon', 'Scavenged the chosen ally (it is within the top eight cards)')
    ->expectCardExists('myGraveyard', 'Business Card', 'The card was sacrificed')
    ->tested('Business Card')
    ->covers(['activated-ability', 'sacrifice', 'scavenge', 'name-choice'], '**(2), Sacrifice CARDNAME:** **Scavenge 8** for an ally card with the chosen name.')
    ->register();

gaBusinessCard('business-card-with-a-phone-item-scavenges-twenty-instead', 9, true)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(2)->answer(1, 'myTempZone-9')
    ->expectCardExists('myHand', 'Banner Raccoon', 'The Phone item widens the scavenge to twenty: the Raccoon ten cards down was found')
    ->tested('Business Card')
    ->covers(['activated-ability', 'scavenge', 'condition'], 'If you control a Phone item, **scavenge 20** for an ally card with the chosen name instead.')
    ->register();

gaBusinessCard('business-card-without-a-phone-item-cannot-reach-ten-cards-down', 9, false)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->expectCardExists('myGraveyard', 'Business Card', 'The card was sacrificed')
    ->expectZoneCount('myHand', 5, 1, 'Scavenge 8 does not reach the Raccoon: nothing is added to the hand')
    ->tested('Business Card')
    ->covers(['activated-ability', 'scavenge', 'condition'], '**Scavenge 8** for an ally card with the chosen name.')
    ->register();

// ---- Sacramental Rite (Divine Relic) ----

gaScenario('sacramental-rite-enter-banishes-a-material-card-face-down')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sacramental Rite')
    ->seed('myField', 'Sacramental Rite')
    ->answer(1, 'myMaterial-1')
    ->expectZoneCount('myMaterial', 3, 1, 'A material card was banished')
    ->expectZoneCount('myBanish', 1, 1, 'It sits in the banishment')
    ->expectProperty('myBanish-0', 'Counters', '{"_sacramentalFaceDown":true}', 'It is recorded as banished face down by the Rite')
    ->tested('Sacramental Rite')
    ->covers(['on-enter', 'banish', 'face-down'], '**On Enter:** Banish a non-champion card from your material deck face down.')
    ->register();

gaScenario('sacramental-rite-banish-makes-the-champion-an-ascendant-and-lets-you-play-the-banished-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sacramental Rite')
    ->seed('myField', 'Sacramental Rite')
    ->answer(1, 'myMaterial-1')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'YES')
    ->expectProperty('myField-0', 'Counters', '{"_overrides":{"type":"CHAMPION,ASCENDANT"}}', 'The champion is also an Ascendant')
    ->pay(3)->answer(1, 'NO')
    ->expectCardExists('myField', 'Dungeon Guide', 'The banished face-down card (a Dungeon Guide from the material deck) was played from the banishment')
    ->expectZoneCount('myBanish', 1, 1, 'Only Sacramental Rite itself remains in the banishment')
    ->tested('Sacramental Rite')
    ->covers(['activated-ability', 'banish-self', 'type-change', 'play-from-banishment'], '**Banish Sacramental Rite:** Your champion becomes an Ascendant in addition to its other types. You may play the banished card.')
    ->register();
