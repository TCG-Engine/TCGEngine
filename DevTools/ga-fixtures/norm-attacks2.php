<?php
/**
 * Phase 3d (Norm): attack cards and tokens, second batch. 'quiet' deck.
 */

// ---- Charged Directive (Command Automaton) ----

gaScenario('charged-directive-the-automaton-attacks-summons-a-powercell-and-the-opponent-banishes-cards-for-each-powercell')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Charged Directive')
    ->seed('myField', 'Automaton Drone')
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Charged Directive')
    ->reachTurnThree()
    ->play('Charged Directive')->pay(2)->target('myField-1')->target('theirField-0')->pass(2)
    ->expectCardExists('myField', 'Powercell', 'On Attack: a Powercell was summoned')
    ->expectZoneCount('theirGraveyard', 1, 1, 'X = 1 Powercell: the opponent banished one card from their graveyard')
    ->tested('Charged Directive')
    ->covers(['command-automaton', 'on-attack', 'summon', 'banish', 'variable'], '**On Attack:** **Summon** a Powercell token rested. Then each opponent banishes **X** cards from their graveyard, where **X** is the amount of Powercells you control.')
    ->register();

// ---- Uncanny Realization (Command Automaton) ----

gaScenario('uncanny-realization-a-unique-ally-attacking-with-it-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Uncanny Realization')
    ->seed('myField', 'Harvester Mk II')
    ->seed('myHand', 'Uncanny Realization')
    ->reachTurnThree()
    ->play('Uncanny Realization')->pay(1)->target('myField-1')->target('theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 6, 'Unique Automaton attacker: 1 power + the card\'s 3 + 2 = 6')
    ->tested('Uncanny Realization')
    ->covers(['command-automaton', 'condition', 'stat-change', 'combat'], 'As long as a unique ally is attacking with CARDNAME, it gets +2[POWER].')
    ->register();

// ---- Clean Cut ----

foreach ([
    ['clean-cut-level-two-on-kill-draws-a-card', 2, 'Clean Cut\'s 2 power destroyed the 2-life Wyrmling', 7, '[Level 2+] On Kill: a card was drawn (the level 1 twin ends with 6)', ['level-gate', 'on-kill', 'draw']],
    ['clean-cut-below-level-two-draws-nothing-on-kill', 1, 'The Wyrmling died', 6, 'Level 1: no draw', ['level-gate', 'on-kill', 'condition']],
] as [$slug, $level, $deathLabel, $handCount, $drawLabel, $mechanics]) {
    gaScenario($slug)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->readyFor('Clean Cut', level: $level, player: 2)
        ->seed('myField', 'Young Wyrmling', properties: ['Status' => 1])
        ->seed('myHand', 'Clean Cut', player: 2)
        ->endTurn()
        ->play('Clean Cut', 2)->pay(2, 2)->target('theirField-1', 2)
        ->expectCardExists('myGraveyard', 'Young Wyrmling', $deathLabel)
        ->expectZoneCount('myHand', $handCount, 2, $drawLabel)
        ->tested('Clean Cut')
        ->covers($mechanics, '[Level 2+] **On Kill:** Draw a card.')
        ->register();
}

// ---- Raised Slash ----

gaScenario('raised-slash-class-bonus-on-attack-puts-a-buff-counter-on-suited-allies')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Raised Slash', classBonus: true, player: 2)
    ->seed('myField', 'Two of Spades', player: 2)
    ->seed('myHand', 'Raised Slash', player: 2)
    ->endTurn()->passBoth()->passBoth()
    ->play('Raised Slash', 2)->pay(3, 2)->pass(2)->target('theirField-0', 2)->answer(2, 'myField-1')
    ->expectProperty('p2Field-1', 'Counters', '{"buff":1}', '[Class Bonus] The only Suited ally got one buff counter (it is not offered a second time)')
    ->tested('Raised Slash')
    ->covers(['class-bonus', 'on-attack', 'counter', 'targeting'], '[Class Bonus] **On Attack**: Put a **buff** counter on up to two target Suited allies you control.')
    ->register();

// ---- Tokens ----

gaScenario('lost-being-token-has-its-printed-stats')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Lost Being')
    ->observe()
    ->expectPower('p1Field-1', 2, 'Printed 2 power')
    ->expectLife('p1Field-1', 2, 'Printed 2 life')
    ->tested('Lost Being')
    ->covers(['vanilla'], 'Lost Being (no rules text)')
    ->register();

gaScenario('spirit-shard-level-three-sacrifice-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(level: 3)
    ->seed('myField', 'Spirit Shard')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myHand', 8, 1, '[Level 3+] Sacrificed: a card was drawn')
    ->tested('Spirit Shard')
    ->covers(['level-gate', 'activated-ability', 'sacrifice-self', 'draw'], '[Level 3+] **Sacrifice CARDNAME**: Draw a card.')
    ->register();

gaScenario('memorite-obelith-gets-plus-one-power-and-life-for-each-of-up-to-five-sheen-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Memorite Obelith', properties: ['Counters' => ['sheen' => 7]])
    ->observe()
    ->expectPower('p1Field-1', 5, 'Seven sheen counters, counted up to five: 0 + 5')
    ->expectLife('p1Field-1', 6, '1 + 5 life')
    ->tested('Memorite Obelith')
    ->covers(['counter', 'stat-change'], 'CARDNAME gets +1 [POWER] and +1 [LIFE] for each of up to five **sheen** counters on it.')
    ->register();

gaScenario('memorite-blade-activating-a-spell-gets-plus-one-power-once')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Purification')
    ->seed('myField', 'Memorite Blade')
    ->seed('myHand', 'Purification')
    ->castSpell('Purification', pay: 1, window: '')
    ->expectPower('p1Field-1', 1, 'Activating a Spell: +1 power (printed 0)')
    ->tested('Memorite Blade')
    ->covers(['trigger', 'stat-change'], 'Whenever you activate a Spell card, CARDNAME gets +1 [POWER]. Trigger this ability only once.')
    ->register();

// ---- Brackish Lutist ----

gaScenario('brackish-lutist-a-floating-memory-card-dying-is-banished-instead-of-going-to-the-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Brackish Lutist')
    ->seed('myField', 'Brackish Lutist')
    ->seed('myField', 'Ordinary Horse')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2, 'Counters' => ['buff' => 1]])
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, '-')
    ->expectCardExists('myBanish', 'Ordinary Horse', 'The dying floating-memory Horse was banished instead')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing reached the graveyard')
    ->tested('Brackish Lutist')
    ->covers(['replacement-effect', 'floating-memory', 'banish'], 'If a card with **floating memory** would be put into a player\'s graveyard, that player banishes it instead.')
    ->register();

// ---- Performance Enthusiast ----

gaScenario('performance-enthusiast-activating-a-melody-puts-a-buff-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Belted Tune')
    ->seed('myField', 'Performance Enthusiast')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Belted Tune')
    ->castSpell('Belted Tune', pay: 2, window: '', target: 'myField-2')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Activating a Melody card: a buff counter on the Enthusiast')
    ->tested('Performance Enthusiast')
    ->covers(['trigger', 'counter', 'melody'], 'Whenever you activate a Harmony or Melody card, if there are no **buff** counters on CARDNAME, put a **buff** counter on CARDNAME.')
    ->register();
