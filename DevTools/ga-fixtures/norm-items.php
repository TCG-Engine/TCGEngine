<?php
/**
 * Phase 3d (Norm): regalia items, first batch (banish-for-an-effect items). The regalia is seeded on the field of the 'quiet' deck's player (regalia must not be the
 * deck's {MAIN} card) and its ability is activated with the Activate:N custom input.
 */

if (!function_exists('gaNormItem')) {
    // $abilityIndex: which printed ability; $pay: reserve paid; $effects: callable receiving the scenario to add the observation.
    function gaNormItem(string $slug, string $item, int $ability, int $pay, callable $setup, callable $observe, array $mechanics, string ...$clauses): void {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor($item);
        $s->seed('myField', $item);
        $setup($s);
        $s->action(1, 10001, 'myField-' . 1 . '!CustomInput!Activate:' . $ability);
        if ($pay > 0) $s->pay($pay);
        $observe($s);
        $s->tested($item)->covers($mechanics, ...$clauses)->register();
    }
}

// The item is the champion's first field neighbour (myField-1) unless the setup seeds something before it; setups that seed first pass the index through $idx.

gaNormItem('backup-charger-summons-a-rested-powercell-and-draws-into-memory', 'Backup Charger', 0, 3,
    fn($s) => null,
    fn($s) => $s->expectCardExists('myField', 'Powercell', 'A Powercell was summoned')->expectZoneCount('myMemory', 4, 1, 'Three paid plus one drawn into memory'),
    ['activated-ability', 'summon', 'draw-into-memory', 'banish-self'], '(3), **Banish CARDNAME:** **Summon** a Powercell token rested. Draw a card into your memory.');

gaNormItem('chalice-of-blood-with-twenty-damage-draws-two', 'Chalice of Blood', 0, 0,
    fn($s) => $s->patch('myField-0', ['Damage' => 20]),
    fn($s) => $s->expectZoneCount('myHand', 9, 1, 'Two cards drawn'),
    ['activated-ability', 'draw', 'condition', 'banish-self'], '**Banish CARDNAME:** Draw two cards. Activate this ability only if your champion has twenty or more damage counters on them.');

gaNormItem('clandestine-chart-glimpses-two-and-puts-a-preparation-counter-on-the-champion', 'Clandestine Chart', 0, 2,
    fn($s) => $s->deckTop(['Dungeon Guide', 'Gray Wolf']),
    fn($s) => $s->answer(1, 'Top=em6eEh9q8y,hJ2xh9lNMR;Bottom=')->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'A preparation counter went on the champion'),
    ['activated-ability', 'glimpse', 'counter', 'banish-self'], '(2)**, Banish CARDNAME**: **Glimpse 2**. Put a **preparation** counter on your champion.');

gaNormItem('gem-of-sorority-banish-empowers-two', 'Gem of Sorority', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectCardExists('myBanish', 'Gem of Sorority', 'The Gem banished itself'),
    ['activated-ability', 'empower', 'banish-self'], '**Banish CARDNAME**: **Empower 2**.');

gaNormItem('grand-crusaders-ring-banish-draws-a-card', 'Grand Crusader\'s Ring', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('myHand', 8, 1, 'A card was drawn'),
    ['activated-ability', 'draw', 'banish-self'], '**Banish CARDNAME:** Draw a card.');

gaNormItem('jewel-of-enlightenment-banish-puts-an-enlighten-counter-on-the-champion', 'Jewel of Enlightenment', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectProperty('p1Field-0', 'Counters', '{"enlighten":1}', 'An enlighten counter went on the champion'),
    ['activated-ability', 'counter', 'banish-self'], '**Banish CARDNAME:** Put an **enlighten** counter on your champion.');

gaNormItem('orb-of-glitter-banish-draws-a-card', 'Orb of Glitter', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('myHand', 8, 1, 'A card was drawn'),
    ['activated-ability', 'draw', 'banish-self'], '**Banish CARDNAME:** Draw a card.');

gaNormItem('eye-of-argus-banish-gives-an-ally-true-sight-and-draws', 'Eye of Argus', 0, 0,
    fn($s) => $s->seed('myField', 'Dungeon Guide'),
    fn($s) => $s->answer(1, 'myField-1')->expectZoneCount('myHand', 8, 1, 'A card was drawn'),
    ['activated-ability', 'true-sight', 'draw', 'targeting', 'banish-self'], '**Banish CARDNAME:** Target ally you control gains **true sight** until end of turn. Draw a card.');

gaNormItem('marksmans-charm-banish-gives-a-unit-true-sight', 'Marksman\'s Charm', 0, 0,
    fn($s) => $s->seed('myField', 'Dungeon Guide'),
    fn($s) => $s->answer(1, 'myField-1')->expectCardExists('myBanish', 'Marksman\'s Charm', 'The Charm banished itself'),
    ['activated-ability', 'true-sight', 'targeting', 'banish-self'], '**Banish CARDNAME**: Target unit gains **true sight** until end of turn.');

gaNormItem('smoke-bombs-banish-gives-an-ally-stealth-and-draws', 'Smoke Bombs', 0, 0,
    fn($s) => $s->seed('myField', 'Dungeon Guide'),
    fn($s) => $s->answer(1, 'myField-1')->expectZoneCount('myHand', 8, 1, 'A card was drawn'),
    ['activated-ability', 'stealth', 'draw', 'targeting', 'banish-self'], '**Banish CARDNAME:** Target ally gains **stealth** until end of turn. Draw a card.');

gaNormItem('poisoned-coating-oil-banish-gives-a-stealth-ally-plus-two-power', 'Poisoned Coating Oil', 0, 0,
    fn($s) => $s->seed('myField', 'Imperial Assassin', properties: ['TurnEffects' => ['STEALTH']]),
    fn($s) => $s->answer(1, 'myField-1')->expectPower('p1Field-1', 4, 'The stealth ally got +2 power: 2 + 2'),
    ['activated-ability', 'stat-change', 'targeting', 'banish-self'], '**Banish CARDNAME:** Target ally you control with **stealth** gets +2 [POWER] until end of turn.');

gaNormItem('winged-talaria-banish-makes-a-unit-distant', 'Winged Talaria', 0, 2,
    fn($s) => $s->seed('myField', 'Dungeon Guide'),
    fn($s) => $s->answer(1, 'myField-1')->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The unit became distant'),
    ['activated-ability', 'distant', 'targeting', 'banish-self'], '(2)**, Banish CARDNAME**: Target unit becomes **distant**.');

gaNormItem('spectral-beacon-banish-banishes-a-graveyard-card', 'Spectral Beacon', 0, 1,
    fn($s) => $s->seed('myGraveyard', 'Gray Wolf', player: 2),
    fn($s) => $s->answer(1, 'theirGraveyard-0')->expectZoneCount('theirGraveyard', 0, 1, 'The graveyard card was banished')->expectZoneCount('theirBanish', 1, 1, 'Into its owner\'s banishment')->expectZoneCount('myMemory', 1, 1, 'The (1) was paid'),
    ['activated-ability', 'banish', 'targeting', 'banish-self'], '(1)**, Banish CARDNAME:** Banish target card in a graveyard.');

gaNormItem('trivial-trinket-banish-mills-three-from-the-opponents-deck', 'Trivial Trinket', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('theirGraveyard', 3, 1, 'Three cards from the opponent\'s deck went to their graveyard'),
    ['activated-ability', 'mill', 'banish-self'], '**Banish CARDNAME:** Target opponent puts the top three cards of their deck into their graveyard.');

gaNormItem('enfeebling-orb-banish-makes-the-opponent-put-two-hand-cards-into-memory', 'Enfeebling Orb', 0, 0,
    fn($s) => null,
    fn($s) => $s->answer(2, 'myHand-0&myHand-1')->expectZoneCount('theirMemory', 2, 1, 'The opponent put two hand cards into memory'),
    ['activated-ability', 'zone-movement', 'targeting', 'banish-self'], '**Banish CARDNAME: **Target opponent puts two cards from their hand into their memory.');

gaNormItem('rusted-warshield-banish-prevents-two-damage-and-draws-into-memory', 'Rusted Warshield', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('myMemory', 1, 1, 'A card was drawn into memory'),
    ['activated-ability', 'prevention', 'draw-into-memory', 'banish-self'], '**Banish CARDNAME:** Prevent the next 2 damage that would be dealt to your champion this turn. Draw a card into your memory.');
