<?php
/**
 * Phase 3b (Water): allies, second file. Floating Memory is exercised the way Honorable Vanguard's fixture does it: Lorraine's 1-memory
 * champion level-up is paid entirely by banishing the card from the graveyard.
 */

if (!function_exists('gaFloatingPays')) {
    // $card sits in the graveyard with no memory seeded; levelling up banishes it as the memory payment. $classes overrides the champion class.
    function gaFloatingPays(string $slug, string $card, string $clause, ?string $classes = null, bool $pays = true, string $label = ''): void {
        $s = gaScenario($slug)->deckTemplate('lorraine-windslice', 'Dungeon Guide');
        if ($classes !== null) $s->champion(classes: $classes);
        $s->seed('myGraveyard', $card)->endTurn()->endTurn(2)->target('myMaterial-0');
        if ($pays) {
            $s->target('myGraveyard-0')
              ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion levelled up, so the 1-memory cost was paid')
              ->expectZoneCount('myGraveyard', 0, 1, $label !== '' ? $label : "$card was banished from the graveyard as the floating memory payment");
        } else {
            $s->expectZoneCount('myGraveyard', 1, 1, $label !== '' ? $label : "$card stays in the graveyard: its Floating Memory needs the class bonus")
              ->expectZoneCount('myMaterial', 4, 1, 'No memory could be paid, so no champion was materialized');
        }
        $s->tested($card)->covers(['floating-memory', 'memory-cost', 'banish'] + ($classes !== null || !$pays ? [3 => 'class-bonus'] : []), $clause)->register();
    }
}

gaFloatingPays('croaking-webfoot-floating-memory-pays-a-memory-cost', 'Croaking Webfoot', '**Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost.)*');
gaFloatingPays('evercurrent-raider-floating-memory-pays-a-memory-cost', 'Evercurrent Raider', '**Floating Memory**');
gaFloatingPays('shademist-priestess-floating-memory-pays-a-memory-cost', 'Shademist Priestess', '**Floating Memory**');
gaFloatingPays('brookside-scout-floating-memory-pays-a-memory-cost', 'Brookside Scout', '**Floating Memory**');
gaFloatingPays('seafaring-mercenary-class-bonus-floating-memory-pays-a-memory-cost', 'Seafaring Mercenary', '[Class Bonus] **Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost. Apply this effect only if your champion\'s class matches this card\'s class.)*', 'WARRIOR');
gaFloatingPays('seafaring-mercenary-without-the-class-bonus-cannot-pay-a-memory-cost', 'Seafaring Mercenary', '[Class Bonus] **Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost. Apply this effect only if your champion\'s class matches this card\'s class.)*', 'MAGE', false);
gaFloatingPays('awakened-deacon-class-bonus-floating-memory-pays-a-memory-cost', 'Awakened Deacon', '[Class Bonus] **Floating Memory**', 'CLERIC');
gaFloatingPays('awakened-deacon-without-the-class-bonus-cannot-pay-a-memory-cost', 'Awakened Deacon', '[Class Bonus] **Floating Memory**', 'MAGE', false);

// ---- Deluge On Death ----
if (!function_exists('gaDelugeDeath')) {
    // $ally (damage $damage) is killed by Aenean Spark Alight with $water filler water cards in the graveyard.
    function gaDelugeDeath(string $slug, string $ally, int $water, int $damage, string $clause, array $mechanics, callable $assert, ?string $classes = null): void {
        $s = gaScenario($slug)->standardDeck('Aenean Spark Alight')->readyFor('Aenean Spark Alight');
        if ($classes !== null) $s->champion(classes: $classes);
        gaWaterGraveyard($s, $water);
        $s->seed('myField', $ally, properties: ['Damage' => $damage])
          ->seed('myHand', 'Aenean Spark Alight')
          ->castSpell('Aenean Spark Alight', pay: 2, window: 'both', target: 'myField-1');
        $assert($s);
        $s->tested($ally)->covers($mechanics, $clause)->register();
    }
}

$magisterClause = '***Deluge 3*** -- **On Death**: If you have three or more water element cards in your graveyard, put two **enlighten** counters on your champion.';
gaDelugeDeath('mistsworn-magister-deluge-three-on-death-puts-two-enlighten-counters-on-your-champion', 'Mistsworn Magister', 2, 2, $magisterClause, ['on-death', 'deluge', 'enlighten-counter'],
    fn ($s) => $s->expectCardExists('myGraveyard', 'Mistsworn Magister', 'The Magister died')
                 ->expectProperty('p1Field-0', 'Counters', '{"enlighten":2}', 'Two enlighten counters went on the champion (2 filler + the dying Magister = 3 water cards)'));
gaDelugeDeath('mistsworn-magister-with-two-water-cards-on-death-adds-no-counters', 'Mistsworn Magister', 1, 2, $magisterClause, ['on-death', 'deluge', 'condition'],
    fn ($s) => $s->expectCardExists('myGraveyard', 'Mistsworn Magister', 'The Magister died')
                 ->expectProperty('p1Field-0', 'Counters', '[]', 'No counters: the dying Magister is the second water card in the graveyard'));

$chancellorClause = '***Deluge 3*** -- **On Death:** If you have three or more water element cards in your graveyard, draw a card.';
gaDelugeDeath('dynasty-chancellor-deluge-three-on-death-draws-a-card', 'Dynasty Chancellor', 2, 0, $chancellorClause, ['on-death', 'deluge', 'draw'],
    fn ($s) => $s->expectCardExists('myGraveyard', 'Dynasty Chancellor', 'The Chancellor died')
                 ->expectZoneCount('myHand', 6, 1, 'Drew a card: 7 - Spark Alight - 2 paid + 1'));
gaDelugeDeath('dynasty-chancellor-with-two-water-cards-on-death-does-not-draw', 'Dynasty Chancellor', 1, 0, $chancellorClause, ['on-death', 'deluge', 'condition'],
    fn ($s) => $s->expectCardExists('myGraveyard', 'Dynasty Chancellor', 'The Chancellor died')
                 ->expectZoneCount('myHand', 5, 1, 'No draw: with the Chancellor itself only two water cards are in the graveyard'));

// ---- Aquaveil Ambusher: [Class Bonus] costs 2 less ----
gaScenario('aquaveil-ambusher-class-bonus-costs-two-less')
    ->standardDeck('Aquaveil Ambusher')
    ->readyFor('Aquaveil Ambusher', classBonus: true)
    ->seed('myHand', 'Aquaveil Ambusher')
    ->castSpell('Aquaveil Ambusher', pay: 3, window: 'opp')
    ->expectCardExists('myField', 'Aquaveil Ambusher', 'Class Bonus: cost 5 - 2 = 3 was enough')
    ->expectZoneCount('myMemory', 3, 1, 'Three cards were paid')
    ->tested('Aquaveil Ambusher')
    ->covers(['cost-reduction', 'class-bonus'], '[Class Bonus] This card costs 2 less to activate.')
    ->register();

gaScenario('aquaveil-ambusher-without-the-class-bonus-costs-full-price')
    ->standardDeck('Aquaveil Ambusher')
    ->readyFor('Aquaveil Ambusher')
    ->seed('myHand', 'Aquaveil Ambusher')
    ->castSpell('Aquaveil Ambusher', pay: 5, window: 'opp')
    ->expectCardExists('myField', 'Aquaveil Ambusher', 'No Class Bonus: the full cost 5 was paid')
    ->expectZoneCount('myMemory', 5, 1, 'Five cards were paid')
    ->tested('Aquaveil Ambusher')
    ->covers(['cost-reduction', 'class-bonus', 'condition'], '[Class Bonus] This card costs 2 less to activate.')
    ->register();

// ---- attackers ----
gaScenario('eternal-dreamer-class-bonus-on-attack-glimpses-its-power')
    ->standardDeck('Eternal Dreamer')
    ->readyFor('Eternal Dreamer', classBonus: true)
    ->seed('myField', 'Eternal Dreamer')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'Top=n8wyfG9hbY,px60u5n1do;Bottom=')
    ->pass(2)->pass(1)
    ->expectProperty('myDeck-0', 'CardID', 'n8wyfG9hbY', 'Glimpse 2 (the Dreamer\'s power): the two cards were put back in the order chosen')
    ->tested('Eternal Dreamer')
    ->covers(['on-attack', 'class-bonus', 'glimpse'], '[Class Bonus] **On Attack:** **Glimpse X**, where **X** is CARDNAME’s power.')
    ->register();

gaScenario('vaporjet-shieldbearer-class-bonus-on-hit-may-mill-the-top-card')
    ->standardDeck('Vaporjet Shieldbearer')
    ->readyFor('Vaporjet Shieldbearer', classBonus: true)
    ->seed('myField', 'Vaporjet Shieldbearer')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'myDeck-0')
    ->expectZoneCount('myGraveyard', 1, 1, 'The looked-at top card was put into the graveyard')
    ->expectZoneCount('myDeck', 7, 1, 'The deck is one card shorter (after the draw for turn: 8 - 1)')
    ->tested('Vaporjet Shieldbearer')
    ->covers(['on-hit', 'class-bonus', 'look', 'mill'], '[Class Bonus] **On Hit:** Look at the top card of your deck. You may put that card into your graveyard.')
    ->register();

gaScenario('vaporjet-shieldbearer-declining-keeps-the-top-card-on-the-deck')
    ->standardDeck('Vaporjet Shieldbearer')
    ->readyFor('Vaporjet Shieldbearer', classBonus: true)
    ->seed('myField', 'Vaporjet Shieldbearer')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(1)
    ->expectZoneCount('myGraveyard', 0, 1, 'Declined: the top card stays')
    ->expectZoneCount('myDeck', 8, 1, 'The deck is untouched')
    ->tested('Vaporjet Shieldbearer')
    ->covers(['on-hit', 'class-bonus', 'look', 'optional'], '[Class Bonus] **On Hit:** Look at the top card of your deck. You may put that card into your graveyard.')
    ->register();

gaScenario('vaporjet-shieldbearer-without-the-class-bonus-does-not-look-at-the-top-card')
    ->standardDeck('Vaporjet Shieldbearer')
    ->readyFor('Vaporjet Shieldbearer')
    ->seed('myField', 'Vaporjet Shieldbearer')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectZoneCount('myGraveyard', 0, 1, 'No Class Bonus: nothing was offered')
    ->expectDamage('p2Field-0', 1, 'The attack still hit the champion')
    ->tested('Vaporjet Shieldbearer')
    ->covers(['on-hit', 'class-bonus', 'condition'], '[Class Bonus] **On Hit:** Look at the top card of your deck. You may put that card into your graveyard.')
    ->register();

gaScenario('waterfall-sage-class-bonus-on-attack-cascade-one-empowers-one')
    ->standardDeck('Waterfall Sage')
    ->readyFor('Waterfall Sage', classBonus: true)
    ->seed('myField', 'Waterfall Sage')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["lbhW6j1bJN","EMPOWERED","EMPOWER_PLUS_1"]', 'Cascade 1: Empower 1 went on the champion')
    ->expectProperty('p1Field-1', 'Counters', '{"cascade":1}', 'The Sage\'s cascade counter advanced to 1')
    ->tested('Waterfall Sage')
    ->covers(['on-attack', 'class-bonus', 'cascade', 'empower'], '[Class Bonus] **On Attack**: **Cascade**-- 1-- **Empower 1**. 2-- **Empower 2**. 3-- This attack gets +3[POWER].')
    ->register();

gaScenario('waterfall-sage-without-the-class-bonus-does-not-cascade')
    ->standardDeck('Waterfall Sage')
    ->readyFor('Waterfall Sage')
    ->seed('myField', 'Waterfall Sage')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '[]', 'No Class Bonus: nothing was empowered')
    ->tested('Waterfall Sage')
    ->covers(['on-attack', 'class-bonus', 'condition'], '[Class Bonus] **On Attack**: **Cascade**-- 1-- **Empower 1**. 2-- **Empower 2**. 3-- This attack gets +3[POWER].')
    ->register();

gaScenario('hailfinch-class-bonus-on-hit-removes-a-buff-counter-to-rest-the-hit-champion')
    ->standardDeck('Hailfinch')
    ->readyFor('Hailfinch', classBonus: true)
    ->seed('myField', 'Hailfinch', properties: ['Counters' => ['buff' => 1]])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The buff counter was removed')
    ->expectProperty('p2Field-0', 'Status', '1', 'The hit champion is rested')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","SKIP_WAKEUP"]', 'It will not wake up during its controller\'s next wake up phase')
    ->tested('Hailfinch')
    ->covers(['on-hit', 'class-bonus', 'buff-counter', 'rest'], '[Class Bonus] **On Hit:** You may remove a **buff** counter from CARDNAME. If you do, rest the hit object and it doesn\'t wake up during its controller\'s next wake up phase.')
    ->register();

gaScenario('hailfinch-declining-keeps-the-buff-counter-and-the-champion-awake')
    ->standardDeck('Hailfinch')
    ->readyFor('Hailfinch', classBonus: true)
    ->seed('myField', 'Hailfinch', properties: ['Counters' => ['buff' => 1]])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Declined: the buff counter stays')
    ->expectProperty('p2Field-0', 'Status', '2', 'The champion was not rested')
    ->tested('Hailfinch')
    ->covers(['on-hit', 'class-bonus', 'buff-counter', 'optional'], '[Class Bonus] **On Hit:** You may remove a **buff** counter from CARDNAME. If you do, rest the hit object and it doesn\'t wake up during its controller\'s next wake up phase.')
    ->register();

// ---- Sunken Battle Priest: [Class Bonus] other ephemeral allies get +1 POWER ----
gaScenario('sunken-battle-priest-class-bonus-gives-other-ephemeral-allies-plus-one-power')
    ->standardDeck('Sunken Battle Priest')
    ->readyFor('Sunken Battle Priest', classBonus: true)
    ->seed('myField', 'Sunken Battle Priest')
    ->seed('myField', 'Giant Tortoise', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->observe()
    ->expectPower('p1Field-2', 2, 'An ephemeral ally: 1 + 1 = 2 POWER')
    ->tested('Sunken Battle Priest')
    ->covers(['static-ability', 'class-bonus', 'ephemeral', 'stat-change'], '[Class Bonus] Other **ephemeral** allies you control get +1[POWER].')
    ->register();

gaScenario('sunken-battle-priest-does-not-buff-allies-that-are-not-ephemeral')
    ->standardDeck('Sunken Battle Priest')
    ->readyFor('Sunken Battle Priest', classBonus: true)
    ->seed('myField', 'Sunken Battle Priest')
    ->seed('myField', 'Giant Tortoise')
    ->observe()
    ->expectPower('p1Field-2', 1, 'A normal ally keeps its printed POWER')
    ->tested('Sunken Battle Priest')
    ->covers(['static-ability', 'class-bonus', 'ephemeral', 'condition'], '[Class Bonus] Other **ephemeral** allies you control get +1[POWER].')
    ->register();

gaScenario('sunken-battle-priest-without-the-class-bonus-gives-no-power')
    ->standardDeck('Sunken Battle Priest')
    ->readyFor('Sunken Battle Priest')
    ->seed('myField', 'Sunken Battle Priest')
    ->seed('myField', 'Giant Tortoise', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->observe()
    ->expectPower('p1Field-2', 1, 'No Class Bonus: no extra POWER')
    ->tested('Sunken Battle Priest')
    ->covers(['static-ability', 'class-bonus', 'ephemeral', 'condition'], '[Class Bonus] Other **ephemeral** allies you control get +1[POWER].')
    ->register();

// ---- Shademist Priestess: whenever your champion is dealt damage from a source you don't control, you may recover 1 ----
gaScenario('shademist-priestess-recovers-one-when-an-opposing-source-damages-your-champion')
    ->standardDeck('Shademist Priestess')
    ->readyFor('Shademist Priestess')
    ->seed('myField', 'Shademist Priestess')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->answer(1, 'YES')
    ->expectDamage('p1Field-0', 0, 'The champion took 1 damage and recovered 1')
    ->tested('Shademist Priestess')
    ->covers(['triggered-ability', 'damage', 'recover'], 'Whenever your champion is dealt damage from a source you don\'t control, you may **recover 1**.')
    ->register();

gaScenario('shademist-priestess-declining-keeps-the-damage')
    ->standardDeck('Shademist Priestess')
    ->readyFor('Shademist Priestess')
    ->seed('myField', 'Shademist Priestess')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->answer(1, 'NO')
    ->expectDamage('p1Field-0', 1, 'Declined: the champion stays damaged')
    ->tested('Shademist Priestess')
    ->covers(['triggered-ability', 'damage', 'recover', 'optional'], 'Whenever your champion is dealt damage from a source you don\'t control, you may **recover 1**.')
    ->register();

// ---- Hailstorm Guard ----
gaScenario('hailstorm-guard-class-bonus-prevents-the-next-champion-damage-and-mills-that-many')
    ->standardDeck('Hailstorm Guard')
    ->readyFor('Hailstorm Guard', classBonus: true)
    ->seed('myHand', 'Hailstorm Guard')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(4)
    ->expectDamage('p1Field-0', 0, 'The attack\'s damage to the champion was prevented')
    ->expectZoneCount('myGraveyard', 2, 1, 'Hailstorm Guard itself plus one milled card (1 damage prevented)')
    ->expectZoneCount('myMemory', 4, 1, 'Class Bonus: cost 6 - 2 = 4 was paid')
    ->tested('Hailstorm Guard')
    ->covers(['reaction', 'class-bonus', 'prevent-damage', 'mill', 'cost-reduction'], '[Class Bonus] This card costs 2 less to activate.', 'The next time damage would be dealt to your champion this turn, prevent that damage. Put an amount of cards from the top of your deck into your graveyard equal to the amount of damage prevented this way.')
    ->register();

// ---- Imbue allies ----
gaScenario('splashing-spearguard-imbued-enter-mills-two-from-the-target-player')
    ->standardDeck('Splashing Spearguard')
    ->readyFor('Splashing Spearguard')
    ->seed('myHand', 'Giant Tortoise')->seed('myHand', 'Giant Tortoise')->seed('myHand', 'Giant Tortoise')
    ->seed('myHand', 'Splashing Spearguard')
    ->play('Splashing Spearguard')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'theirField-0')
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Three water cards were reserved: the Spearguard is imbued')
    ->expectZoneCount('theirGraveyard', 2, 1, 'On Enter: the targeted opponent put the top two cards of their deck into their graveyard')
    ->expectZoneCount('theirDeck', 7, 1, 'Their deck is two cards shorter')
    ->tested('Splashing Spearguard')
    ->covers(['imbue', 'on-enter', 'mill', 'targeting'], '**Imbue 3**', '**On Enter**: If Splashing Spearguard is **imbued**, target player puts the top two cards of their deck into their graveyard.')
    ->register();

gaScenario('splashing-spearguard-not-imbued-enter-does-nothing')
    ->standardDeck('Splashing Spearguard')
    ->readyFor('Splashing Spearguard')
    ->seed('myHand', 'Splashing Spearguard')
    ->play('Splashing Spearguard')->answer(1, 'NO')->pay(3)
    ->expectCardExists('myField', 'Splashing Spearguard', 'The ally entered')
    ->expectZoneCount('theirGraveyard', 0, 1, 'Not imbued: nothing was milled')
    ->tested('Splashing Spearguard')
    ->covers(['imbue', 'on-enter', 'condition'], '**Imbue 3**', '**On Enter**: If Splashing Spearguard is **imbued**, target player puts the top two cards of their deck into their graveyard.')
    ->register();

gaScenario('moontide-illusionist-imbued-on-death-puts-the-top-two-cards-into-the-graveyard')
    ->standardDeck('Aenean Spark Alight')
    ->readyFor('Moontide Illusionist')
    ->seed('myHand', 'Aenean Spark Alight')
    ->seed('myHand', 'Giant Tortoise')->seed('myHand', 'Giant Tortoise')
    ->seed('myHand', 'Moontide Illusionist')
    ->play('Moontide Illusionist')->answer(1, 'YES')->answer(1, 'myHand-8')->answer(1, 'myHand-8')->pass(1)->pass(2)
    ->action(1, 10002, 'myHand-7!FSM!')->pay(2)->pass(1)->pass(2)->answer(1, 'myField-1')->pass(1)->pass(2)
    ->expectCardExists('myGraveyard', 'Moontide Illusionist', 'The imbued Illusionist died')
    ->expectZoneCount('myGraveyard', 4, 1, 'Spark Alight, the Illusionist and the two milled cards')
    ->expectZoneCount('myDeck', 7, 1, 'On Death (imbued): the top two cards left the deck')
    ->tested('Moontide Illusionist')
    ->covers(['imbue', 'on-death', 'mill'], '**Imbue 2**', '**On Death**: If Moontide Illusionist was **imbued**, put the top two cards of your deck into your graveyard.')
    ->register();

gaScenario('moontide-illusionist-not-imbued-on-death-mills-nothing')
    ->standardDeck('Aenean Spark Alight')
    ->readyFor('Moontide Illusionist')
    ->seed('myHand', 'Aenean Spark Alight')
    ->seed('myHand', 'Moontide Illusionist')
    ->play('Moontide Illusionist')->answer(1, 'NO')->pay(2)->pass(1)->pass(2)
    ->action(1, 10002, 'myHand-5!FSM!')->pay(2)->pass(1)->pass(2)->answer(1, 'myField-1')->pass(1)->pass(2)
    ->expectCardExists('myGraveyard', 'Moontide Illusionist', 'The Illusionist died')
    ->expectZoneCount('myGraveyard', 2, 1, 'Only Spark Alight and the Illusionist: not imbued, nothing milled')
    ->expectZoneCount('myDeck', 9, 1, 'The deck is untouched')
    ->tested('Moontide Illusionist')
    ->covers(['imbue', 'on-death', 'condition'], '**Imbue 2**', '**On Death**: If Moontide Illusionist was **imbued**, put the top two cards of your deck into your graveyard.')
    ->register();

// ---- Floating Memory on actions (plain, then [Class Bonus] with a negative twin) ----
$gaFloatClause = '**Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost.)*';
$gaFloatCbClause = '[Class Bonus] **Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost. Apply this effect only if your champion\'s class matches this card\'s class.)*';
foreach (['Castling Boon' => 'castling-boon', 'Freezing Steel' => 'freezing-steel', 'Fracturize' => 'fracturize', 'Nipping Kicker' => 'nipping-kicker', 'Primordial Ritual' => 'primordial-ritual', 'Spalling Cleanse' => 'spalling-cleanse', 'Weight of Looking Up' => 'weight-of-looking-up'] as $gaFloatCard => $gaFloatSlug) {
    gaFloatingPays($gaFloatSlug . '-floating-memory-pays-a-memory-cost', $gaFloatCard, $gaFloatClause);
}
foreach (['Mire Reparation' => ['mire-reparation', 'GUARDIAN'], 'Refreshing Charge' => ['refreshing-charge', 'RANGER'], 'Storm of Thorns' => ['storm-of-thorns', 'GUARDIAN'], 'Tidal Tirade' => ['tidal-tirade', 'GUARDIAN'], 'Weaving Manastream' => ['weaving-manastream', 'RANGER']] as $gaFloatCard => [$gaFloatSlug, $gaFloatClass]) {
    gaFloatingPays($gaFloatSlug . '-class-bonus-floating-memory-pays-a-memory-cost', $gaFloatCard, $gaFloatCbClause, $gaFloatClass);
    gaFloatingPays($gaFloatSlug . '-without-the-class-bonus-cannot-pay-a-memory-cost', $gaFloatCard, $gaFloatCbClause, 'MAGE', false);
}
