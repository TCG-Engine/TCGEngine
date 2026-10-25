<?php
/**
 * Phase 3d (Norm): regalia items, second batch (links, statics and counters). A link is seeded as in cards-p.php: the linked unit's Subcards holds the regalia id, the regalia
 * has Counters['linkedToAlly'] set to the unit's card id.
 */

if (!function_exists('gaNormLinked')) {
    // Player 1 field: champion, linked unit (a Dungeon Guide), the linked regalia.
    function gaNormLinked(string $slug, string $regalia, string $unit = 'Dungeon Guide'): GaScenario {
        return gaScenario($slug)
            ->deckTemplate('quiet', 'Dungeon Guide')
            ->readyFor($regalia)
            ->seed('myField', $unit)
            ->patch('myField-1', ['Subcards' => [GaScenario::id($regalia)]])
            ->seed('myField', $regalia)
            ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id($unit)]]);
    }
}

// ---- Alliance Gearshield ----

gaNormLinked('alliance-gearshield-linked-ally-gets-plus-one-life', 'Alliance Gearshield')
    ->observe()
    ->expectLife('p1Field-1', 4, 'Linked ally: 3 + 1 life')
    ->tested('Alliance Gearshield')
    ->covers(['link', 'stat-change'], 'Linked ally gets +1 [LIFE].')
    ->register();

gaScenario('alliance-gearshield-class-bonus-linked-retaliating-ally-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Alliance Gearshield', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Subcards' => [GaScenario::id('Alliance Gearshield')]])
    ->seed('myField', 'Alliance Gearshield')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id('Dungeon Guide')]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p2Field-1', 3, '[Class Bonus] Retaliating with 1 + 2 power')
    ->tested('Alliance Gearshield')
    ->covers(['link', 'class-bonus', 'stat-change', 'retaliate'], '**Class Bonus:** That ally also gets +2 [POWER] as long as they\'re retaliating.')
    ->register();

// ---- Protector's Plate ----

gaNormLinked('protectors-plate-linked-ally-gets-plus-one-life-and-intercept', 'Protector\'s Plate')
    ->observe()
    ->expectLife('p1Field-1', 4, 'Linked ally: 3 + 1 life')
    ->tested('Protector\'s Plate')
    ->covers(['link', 'stat-change'], 'Linked ally gets +1 [LIFE] and has **intercept**.')
    ->register();

gaScenario('protectors-plate-linked-ally-intercepts-the-attack-on-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Protector\'s Plate')
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Subcards' => [GaScenario::id('Protector\'s Plate')]])
    ->seed('myField', 'Protector\'s Plate')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id('Dungeon Guide')]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myField-1')->answer(1, '-')
    ->expectDamage('p1Field-0', 0, 'The linked ally intercepted: the champion took nothing')
    ->expectDamage('p1Field-1', 1, 'The linked ally took the redirected attack')
    ->tested('Protector\'s Plate')
    ->covers(['link', 'intercept'], 'Linked ally gets +1 [LIFE] and has **intercept**.')
    ->register();

// ---- Shield of Parvati ----

gaScenario('shield-of-parvati-prevents-two-damage-to-the-linked-ally-and-is-sacrificed')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shield of Parvati')
    ->seed('myField', 'Dungeon Guide')
    ->patch('myField-1', ['Subcards' => [GaScenario::id('Shield of Parvati')]])
    ->seed('myField', 'Shield of Parvati')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id('Dungeon Guide')]])
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectDamage('p1Field-1', 1, 'The Steed\'s 3 damage was reduced by 2')
    ->tested('Shield of Parvati')
    ->covers(['link', 'prevention', 'sacrifice'], 'If linked ally would take damage, prevent 2 of that damage and sacrifice CARDNAME instead.')
    ->register();

// ---- Cowl of the Wild ----

gaScenario('cowl-of-the-wild-with-a-non-human-tamer-ally-the-champion-gets-plus-one-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cowl of the Wild')
    ->seed('myField', 'Cowl of the Wild')
    ->seed('myField', 'Gray Wolf')
    ->observe()
    ->expectLevel('p1Field-0', 1, 'A non-Human Tamer ally: +1 level')
    ->tested('Cowl of the Wild')
    ->covers(['condition', 'level', 'stat-change'], 'As long as you control one or more non-Human Tamer allies, your champion gets +1 level.')
    ->register();

gaScenario('cowl-of-the-wild-without-a-non-human-tamer-ally-gives-no-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cowl of the Wild')
    ->seed('myField', 'Cowl of the Wild')
    ->seed('myField', 'Dungeon Guide')
    ->observe()
    ->expectLevel('p1Field-0', 0, 'No non-Human Tamer ally: no level bonus')
    ->tested('Cowl of the Wild')
    ->covers(['condition', 'level'], 'As long as you control one or more non-Human Tamer allies, your champion gets +1 level.')
    ->register();

// ---- Crystal of Argus ----

gaScenario('crystal-of-argus-class-bonus-gives-plus-one-level-per-three-enlighten-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crystal of Argus', classBonus: true)
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'MAGE'], 'enlighten' => 3]])
    ->seed('myField', 'Crystal of Argus')
    ->observe()
    ->expectLevel('p1Field-0', 1, '[Class Bonus] Three enlighten counters: +1 level')
    ->tested('Crystal of Argus')
    ->covers(['class-bonus', 'level', 'counter'], '[Class Bonus] Your champion gets +1 level for every three **enlighten** counters on them.')
    ->register();

// ---- Fatal Timepiece ----

gaScenario('fatal-timepiece-recollection-without-a-materialization-deals-two-unpreventable-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fatal Timepiece')
    ->seed('myField', 'Fatal Timepiece')
    ->reachTurnThree()
    ->expectDamage('p1Field-0', 2, 'No card materialized this turn: 2 unpreventable damage to the champion at recollection')
    ->tested('Fatal Timepiece')
    ->covers(['trigger', 'condition', 'damage'], 'At the beginning of each player\'s recollection phase, if they did not materialize a card this turn, deal 2 unpreventable damage to their champion.')
    ->register();

// ---- Etherealys' Promise / Veilara's Promise ----

gaScenario('etherealys-promise-an-ally-dying-puts-a-refinement-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Etherealys\' Promise')
    ->seed('myField', 'Etherealys\' Promise')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, '-')
    ->expectCardExists('myGraveyard', 'Dungeon Guide', 'The 3-life ally died to the Steed\'s 3 power')
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":1}', 'A refinement counter went on the Promise')
    ->tested('Etherealys\' Promise')
    ->covers(['trigger', 'counter'], 'Whenever an ally you control dies, put a **refinement** counter on CARDNAME.')
    ->register();

// ---- Tome of Knowledge ----

gaScenario('tome-of-knowledge-class-bonus-on-the-field-gives-the-champion-plus-one-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tome of Knowledge', classBonus: true)
    ->seed('myField', 'Tome of Knowledge')
    ->observe()
    ->expectLevel('p1Field-0', 1, '[Class Bonus] The Tome on the field: +1 level')
    ->tested('Tome of Knowledge')
    ->covers(['class-bonus', 'level', 'stat-change'], '[Class Bonus] Your champion gets +1 level.')
    ->register();

gaScenario('tome-of-knowledge-without-the-class-bonus-gives-no-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tome of Knowledge')
    ->seed('myField', 'Tome of Knowledge')
    ->observe()
    ->expectLevel('p1Field-0', 0, 'No class bonus: no level')
    ->tested('Tome of Knowledge')
    ->covers(['class-bonus', 'level', 'condition'], '[Class Bonus] Your champion gets +1 level.')
    ->register();
