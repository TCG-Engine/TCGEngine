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

// ---- Vanguard's Reversal ----

gaScenario('vanguards-reversal-class-bonus-with-a-taunting-champion-costs-three-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vanguard\'s Reversal', classBonus: true, player: 2)
    ->seed('myHand', 'Vanguard\'s Reversal', player: 2)->seed('myHand', 'Take Point', player: 2)->endTurn()
    ->castSpell('Take Point', pay: 2, player: 2)
    ->play('Vanguard\'s Reversal', 2)->pay(2, 2)->target('theirField-0', 2)
    ->expectDamage('p1Field-0', 5, '[Class Bonus] The attack was activated for 5 - 3 = 2 reserve and dealt its 5 damage')
    ->tested('Vanguard\'s Reversal')
    ->covers(['class-bonus', 'condition', 'cost-reduction', 'taunt'], '[Class Bonus] As long as your champion has **taunt**, this card costs 3 less to activate.')
    ->register();

gaScenario('vanguards-reversal-without-a-taunting-champion-costs-its-full-five')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vanguard\'s Reversal', classBonus: true, player: 2)
    ->seed('myHand', 'Vanguard\'s Reversal', player: 2)->endTurn()
    ->play('Vanguard\'s Reversal', 2)->pay(2, 2)
    ->rejects(2, 'theirField-0', 'Without taunt the attack still costs 5: two cards are not enough to pick the target')
    ->tested('Vanguard\'s Reversal')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] As long as your champion has **taunt**, this card costs 3 less to activate.')
    ->register();

// ---- Camil, Basked Abundance ----

gaScenario('camil-on-attack-puts-a-veltech-ally-link-item-from-hand-onto-the-field-linked-to-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Camil, Basked Abundance')
    ->seed('myField', 'Camil, Basked Abundance')
    ->seed('myHand', 'CookTech Knife')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'myHand-7')
    ->expectCardExists('myField', 'CookTech Knife', 'The Knife came from the hand onto the field')
    ->expectPower('p1Field-1', 2, 'Linked to Camil: 1 + 1 power from the Knife')
    ->tested('Camil, Basked Abundance')
    ->covers(['on-attack', 'ally-link', 'zone-movement'], '**On Attack:** You may put a non-advanced element VelTech item card with **ally link** from your hand or memory onto the field linked to CARDNAME.')
    ->register();

gaScenario('camil-leaving-the-field-puts-its-linked-items-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Camil, Basked Abundance')
    ->seed('myField', 'Camil, Basked Abundance', properties: ['Subcards' => [GaScenario::id('CookTech Knife')]])
    ->seed('myField', 'CookTech Knife', properties: ['Counters' => ['linkedToAlly' => GaScenario::id('Camil, Basked Abundance')]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->attack('myField-2', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectCardExists('myMemory', 'CookTech Knife', 'Camil was destroyed on the opponent\'s turn: the linked Knife went into memory')
    ->expectCardExists('theirField', 'Dungeon Guide', 'Neither opposing Dungeon Guide was swept into the wrong zone')
    ->expectZoneCount('myMemory', 1, 1, 'Nothing else landed in memory')
    ->tested('Camil, Basked Abundance')
    ->covers(['on-leave', 'ally-link', 'zone-movement'], '**On Leave:** Put the items that were linked to CARDNAME into your memory.')
    ->register();

gaScenario('camil-linked-to-three-or-more-objects-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Camil, Basked Abundance')
    ->seed('myField', 'Camil, Basked Abundance', properties: ['Subcards' => [GaScenario::id('CookTech Knife'), GaScenario::id('CookTech Apron')]])
    ->seed('myField', 'CookTech Knife', properties: ['Counters' => ['linkedToAlly' => GaScenario::id('Camil, Basked Abundance')]])
    ->seed('myField', 'CookTech Apron', properties: ['Counters' => ['linkedToAlly' => GaScenario::id('Camil, Basked Abundance')]])
    ->seed('myHand', 'PlasmaTech Blaster')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'myHand-7')
    ->expectZoneCount('myMemory', 1, 1, 'Linked to three objects: a card was drawn into memory')
    ->tested('Camil, Basked Abundance')
    ->covers(['on-attack', 'ally-link', 'draw-into-memory', 'condition'], 'Then if CARDNAME is linked to three or more objects, draw a card into your memory.')
    ->register();

// ---- Forese, Fervid Cantor ----

gaScenario('forese-activating-a-melody-rests-it-to-scavenge-a-resonator-onto-the-field')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Forese, Fervid Cantor')
    ->deckTop(['Performance Enthusiast'])
    ->seed('myField', 'Forese, Fervid Cantor')
    ->seed('myHand', 'Belted Tune')
    ->play('Belted Tune')->pay(2)->target('myField-1')->answer(1, 'YES')->answer(1, 'myTempZone-4')
    ->expectCardExists('myField', 'Performance Enthusiast', 'The scavenged Resonator ally was put onto the field')
    ->expectProperty('p1Field-1', 'Status', '1', 'Forese rested')
    ->tested('Forese, Fervid Cantor')
    ->covers(['trigger', 'rest', 'scavenge', 'zone-movement'], 'Whenever you activate a Harmony or Melody card, you may rest CARDNAME. If you do, **scavenge 5** for a non-advanced element Resonator ally card with reserve cost 3 or less. Put the scavenged card onto the field rather than into your hand.')
    ->register();

// ---- Nightframe, Hound's Bike (Ally Link): the linked Dungeon Guide is myField-1, Nightframe myField-2 ----

if (!function_exists('gaNightframe')) {
    function gaNightframe(string $slug): GaScenario {
        return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Nightframe, Hound\'s Bike')
            ->seed('myField', 'Dungeon Guide', properties: ['Subcards' => [GaScenario::id('Nightframe, Hound\'s Bike')]])
            ->seed('myField', 'Nightframe, Hound\'s Bike', properties: ['Counters' => ['linkedToAlly' => GaScenario::id('Dungeon Guide')]]);
    }
}

gaNightframe('nightframe-activating-a-fast-action-puts-a-buff-counter-on-the-linked-ally')
    ->seed('myHand', 'Journey\'s Beginning')
    ->play('Journey\'s Beginning')->pay(3)->answer(1, 'YES')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Nightframe put a buff counter on the linked Dungeon Guide')
    ->tested('Nightframe, Hound\'s Bike')
    ->covers(['trigger', 'fast-activation', 'counter', 'ally-link'], 'Whenever you activate a fast speed action card or a card with **fast activation**, you may put a **buff** counter on linked ally if it has two or less **buff** counters on it.')
    ->register();

gaNightframe('nightframe-protection-declining-to-pay-negates-the-opposing-action-targeting-the-linked-ally')
    ->seed('myHand', 'Aenean Ward', player: 2)->endTurn()
    ->answer(2, 'myHand-7')->pay(1, 2)->target('theirField-1', 2)->answer(2, 'NO')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Declined to pay (3): the activation was negated and the ally got no damage prevention')
    ->tested('Nightframe, Hound\'s Bike')
    ->covers(['negate', 'targeting', 'ally-link', 'tax'], '**On Enter:** For each activation and trigger targeting linked ally, **negate** it unless the controller of the activation or trigger pays (3).')
    ->register();

gaNightframe('nightframe-protection-paying-three-lets-the-opposing-action-resolve')
    ->seed('myHand', 'Aenean Ward', player: 2)->endTurn()
    ->answer(2, 'myHand-7')->pay(1, 2)->target('theirField-1', 2)->answer(2, 'YES')->pay(3, 2)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2"]', 'Paid (3): the Ward resolved and the linked ally has its damage prevention')
    ->tested('Nightframe, Hound\'s Bike')
    ->covers(['negate', 'targeting', 'ally-link', 'tax'], 'negate it unless the controller of the activation or trigger pays (3).')
    ->register();
