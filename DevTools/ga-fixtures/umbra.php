<?php
/**
 * Phase 3e (Umbra). readyFor() enables the umbra element (and the class bonus where asked) through the champion lineage. 'quiet' deck.
 */

if (!function_exists('gaNormItem')) {
    // Same helper as norm-items.php (that file loads after this one alphabetically): the regalia is seeded at myField-1 and its ability activated with Activate:N.
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

// ---- Allies ----

gaScenario('shaded-doppelganger-gets-the-highest-base-power-among-other-allies-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shaded Doppelganger')
    ->seed('myField', 'Shaded Doppelganger')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Maiden of Waning Bloom')
    ->observe()
    ->expectPower('p1Field-1', 4, '1 + the highest base power among the other allies (Maiden of Waning Bloom, 3)')
    ->tested('Shaded Doppelganger')
    ->covers(['static', 'stat-change', 'stealth'], 'CARDNAME gets +**X**[POWER], where **X** is the highest base power stat among other allies you control.')
    ->register();

gaScenario('miasmic-fog-gives-all-allies-minus-one-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Miasmic Fog')
    ->seed('myField', 'Miasmic Fog')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->observe()
    ->expectLife('p1Field-2', 2, 'Your Dungeon Guide: 3 - 1')
    ->expectLife('p2Field-1', 2, 'Their Dungeon Guide: 3 - 1')
    ->tested('Miasmic Fog')
    ->covers(['static', 'stat-change'], 'All allies get -1[LIFE].')
    ->register();

gaScenario('vampiric-slime-class-bonus-on-hit-recovers-the-damage-dealt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vampiric Slime', classBonus: true, level: 3, damage: 5)
    ->seed('myField', 'Vampiric Slime')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'Two damage dealt')
    ->expectDamage('p1Field-0', 3, 'Recover 2: 5 down to 3')
    ->tested('Vampiric Slime')
    ->covers(['class-bonus', 'on-hit', 'recover'], '[Class Bonus] **On Hit: Recover X**, where **X** is the amount of damage dealt by this hit.')
    ->register();

gaScenario('ghastly-slime-ephemeral-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ghastly Slime', classBonus: true)
    ->seed('myGraveyard', 'Ghastly Slime')
    ->seed('myHand', 'Dungeon Guide')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)
    ->expectPower('p1Field-1', 4, 'Ephemeral: 2 + 2')
    ->tested('Ghastly Slime')
    ->covers(['class-bonus', 'ephemerate', 'stat-change'], '[Class Bonus] **Ephemerate —** (2) As long as CARDNAME is **ephemeral**, it gets +2 [POWER].')
    ->register();

gaScenario('gloamspire-wraith-class-bonus-ranged-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(classes: 'RANGER', player: 2)
    ->seed('myField', 'Gloamspire Wraith', player: 2, properties: ['TurnEffects' => ['DISTANT']])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 5, 'Distant with ranged 2: 3 + 2 power')
    ->tested('Gloamspire Wraith')
    ->covers(['class-bonus', 'ranged'], '[Class Bonus] **Ranged 2**')
    ->register();

gaScenario('powered-armsmaster-on-death-summons-a-powercell')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Powered Armsmaster')
    ->seed('myField', 'Powered Armsmaster')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'PASS')
    ->expectCardExists('myField', 'Powercell', 'On Death: a Powercell token')
    ->tested('Powered Armsmaster')
    ->covers(['on-death', 'summon', 'token'], '**On Death: Summon** a Powercell token.')
    ->register();

gaScenario('relentless-hexchaser-enter-becomes-distant-when-you-control-a-distant-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Relentless Hexchaser')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Relentless Hexchaser')
    ->play('Relentless Hexchaser')->pay(2)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The Hexchaser became distant')
    ->tested('Relentless Hexchaser')
    ->covers(['on-enter', 'distant', 'condition'], '**On Enter:** If you control a **distant** unit, CARDNAME becomes **distant**.')
    ->register();

gaScenario('chasing-shadows-enter-gains-agility-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Chasing Shadows')
    ->seed('myMemory', 'Gray Wolf')
    ->seed('myHand', 'Chasing Shadows')
    ->play('Chasing Shadows')->pay(2)
    ->expectGlobalEffect(1, 'AGILITY_3', 'Agility 3 until the end phase')
    ->tested('Chasing Shadows')
    ->covers(['on-enter', 'agility'], '**On Enter:** You gain **agility 3** for this turn.')
    ->register();

gaScenario('siphoning-fractal-whenever-an-ally-dies-deals-one-damage-to-the-opposing-champion-and-recovers-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Siphoning Fractal', damage: 3)
    ->seed('myField', 'Siphoning Fractal')
    ->seed('myField', 'Powered Armsmaster')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, 'PASS')
    ->expectDamage('p2Field-0', 1, 'One damage to the opposing champion')
    ->expectDamage('p1Field-0', 2, 'Recover 1: 3 down to 2')
    ->tested('Siphoning Fractal')
    ->covers(['trigger', 'damage', 'recover'], 'Whenever an ally dies, deal 1 damage to target champion you don\'t control and **recover 1**.')
    ->register();

gaScenario('gloamspire-wraith-without-the-class-bonus-has-no-ranged')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Gloamspire Wraith', player: 2, properties: ['TurnEffects' => ['DISTANT']])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 3, 'No class bonus: no ranged, plain 3 power')
    ->tested('Gloamspire Wraith')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] **Ranged 2**')
    ->register();

// ---- Curse cards in the lineage ----

gaScenario('gloamspire-prowler-costs-three-less-with-two-curse-cards-in-the-champions-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Creeping Torment', 'Creeping Torment', 'Shadecursed Hunter'])
    ->seed('myHand', 'Gloamspire Prowler')
    ->play('Gloamspire Prowler')->pay(1)
    ->expectCardExists('myField', 'Gloamspire Prowler', 'Cost 4 - 3 = 1')
    ->tested('Gloamspire Prowler')
    ->covers(['cost-reduction', 'lineage', 'condition'], 'This card costs 3 less to activate as long as there are two or more Curse cards in your champion\'s lineage.')
    ->register();

gaScenario('diana-haunt-reminiscence-class-bonus-ranged-grows-with-the-curse-cards-in-the-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(classes: 'RANGER', lineage: ['Creeping Torment', 'Creeping Torment'], player: 2)
    ->seed('myField', 'Diana, Haunt Reminiscence', player: 2, properties: ['TurnEffects' => ['DISTANT']])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 11, '2 power + ranged 3 + 3 * 2 curse cards')
    ->tested('Diana, Haunt Reminiscence')
    ->covers(['class-bonus', 'ranged', 'lineage'], '[Class Bonus] CARDNAME has **ranged 3+X**, where **X** is three times the amount of Curse cards in your champion\'s lineage.')
    ->register();

gaScenario('shadecursed-hunter-on-death-goes-to-the-bottom-of-the-champions-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shadecursed Hunter')
    ->seed('myField', 'Shadecursed Hunter')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","oqk2c7wklz"]', 'The Hunter went to the bottom of the champion\'s lineage')
    ->tested('Shadecursed Hunter')
    ->covers(['on-death', 'lineage', 'zone-movement'], '**On Death:** Put CARDNAME on the bottom of your champion\'s lineage.')
    ->register();

gaScenario('shadecursed-hunter-inherited-effect-gives-minus-two-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Shadecursed Hunter'])
    ->observe()
    ->expectLife('p1Field-0', 13, 'Spirit of Fire 15 - 2 from the inherited effect')
    ->tested('Shadecursed Hunter')
    ->covers(['inherited-effect', 'stat-change'], '**Inherited Effect:** This object gets -2 [LIFE].')
    ->register();

gaScenario('possessed-remnant-tristan-bonus-on-death-summons-an-ominous-shadow-and-recovers-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Shadowreaver', damage: 5)
    ->seed('myField', 'Possessed Remnant')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'PASS')
    ->expectCardExists('myField', 'Ominous Shadow', 'An Ominous Shadow token')
    ->expectDamage('p1Field-0', 2, 'Recover 3: 5 down to 2')
    ->tested('Possessed Remnant')
    ->covers(['tristan-bonus', 'on-death', 'summon', 'recover', 'token'], '[Tristan Bonus] **On Death:** **Summon** an Ominous Shadow token and **recover 3**.')
    ->register();

gaScenario('elusive-headhunter-class-bonus-on-kill-gains-agility-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Elusive Headhunter', classBonus: true)
    ->seed('myField', 'Elusive Headhunter')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')
    ->expectGlobalEffect(1, 'AGILITY_3', 'Agility 3 until the end phase')
    ->tested('Elusive Headhunter')
    ->covers(['class-bonus', 'on-kill', 'agility'], '[Class Bonus] **On Kill:** You gain **agility 3** for this turn.')
    ->register();

gaScenario('gloamspire-sniper-class-bonus-on-kill-generates-a-creeping-torment-into-the-targets-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gloamspire Sniper', classBonus: true)
    ->seed('myField', 'Gloamspire Sniper')
    ->seed('myField', 'Gray Wolf', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')->answer(1, 'theirField-0')
    ->expectProperty('p2Field-0', 'Subcards', '["zrplywc08c"]', 'A Creeping Torment was put into the target champion\'s lineage')
    ->tested('Gloamspire Sniper')
    ->covers(['class-bonus', 'on-kill', 'generate', 'lineage'], '[Class Bonus] **On Kill: Generate** a Creeping Torment card and put it on the bottom of target champion’s lineage.')
    ->register();

gaScenario('carter-synthetic-reaper-class-bonus-whenever-an-ally-dies-recovers-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Carter, Synthetic Reaper', classBonus: true, damage: 4)
    ->seed('myField', 'Carter, Synthetic Reaper')
    ->seed('myField', 'Powered Armsmaster')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, 'PASS')
    ->expectDamage('p1Field-0', 3, 'An ally died: recover 1, 4 down to 3')
    ->tested('Carter, Synthetic Reaper')
    ->covers(['class-bonus', 'trigger', 'recover'], 'Whenever an ally dies, **recover 1.**')
    ->register();

gaScenario('carter-synthetic-reaper-enter-sacrificing-an-automaton-ally-gets-plus-two-power-and-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Carter, Synthetic Reaper')
    ->seed('myField', 'Powered Armsmaster')
    ->seed('myHand', 'Carter, Synthetic Reaper')
    ->play('Carter, Synthetic Reaper')->pay(2)->answer(1, 'myField-1')
    ->expectPower('p1Field-1', 4, 'Carter got +2 power: 2 + 2')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand, minus Carter and the two paid, plus the card drawn for the sacrificed Automaton')
    ->expectCardExists('myGraveyard', 'Powered Armsmaster', 'The Automaton ally was sacrificed')
    ->tested('Carter, Synthetic Reaper')
    ->covers(['on-enter', 'sacrifice', 'stat-change', 'draw', 'optional'], '**On Enter:** You may sacrifice another ally. If you do, Carter gets +2 [POWER] until end of turn. If an Automaton ally was sacrificed this way, draw a card.')
    ->register();

gaScenario('bandersnatch-frumious-foe-class-bonus-taunt-and-recovers-three-when-dealt-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bandersnatch, Frumious Foe', classBonus: true, damage: 5)
    ->seed('myField', 'Bandersnatch, Frumious Foe', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Class bonus taunt: the champion cannot be attacked while Bandersnatch is awake')
    ->target('theirField-1', 2)->answer(1, 'myField-1')
    ->expectDamage('p1Field-0', 2, 'Dealt damage: recover 3, 5 down to 2')
    ->tested('Bandersnatch, Frumious Foe')
    ->covers(['class-bonus', 'taunt', 'trigger', 'recover'], '[Class Bonus] **Taunt** Whenever CARDNAME is dealt damage, **recover 3**.')
    ->register();

gaScenario('jabberwocky-calamitys-call-prevents-all-damage-without-a-vorpal-sword')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Jabberwocky, Calamity\'s Call')
    ->seed('myField', 'Jabberwocky, Calamity\'s Call')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'PASS')
    ->expectDamage('p1Field-1', 0, 'No Vorpal Sword on the field: all damage is prevented')
    ->tested('Jabberwocky, Calamity\'s Call')
    ->covers(['prevention', 'condition'], 'As long as a player doesn\'t control an object named Vorpal Sword, prevent all damage that would be dealt to CARDNAME.')
    ->register();

gaScenario('jabberwocky-calamitys-call-banish-a-specter-ally-card-recovers-two-and-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Jabberwocky, Calamity\'s Call', damage: 5)
    ->seed('myField', 'Jabberwocky, Calamity\'s Call')
    ->seed('myGraveyard', 'Ghastly Slime')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->answer(1, 'myGraveyard-0')
    ->expectDamage('p1Field-0', 3, 'Recover 2: 5 down to 3')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Ticket rested')
    ->expectZoneCount('myMemory', 2, 1, 'The (2) was paid')
    ->expectPower('p1Field-1', 6, '4 + 2')
    ->tested('Jabberwocky, Calamity\'s Call')
    ->covers(['activated-ability', 'banish', 'recover', 'stat-change', 'cost'], '(2)**, Banish a Specter ally card from your graveyard:** **Recover 2** and CARDNAME gets +2 [POWER] until end of turn.')
    ->register();

// ---- Actions ----

gaScenario('abnegation-recovers-eight-plus-twice-the-curse-cards-in-the-champions-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Creeping Torment', 'Shadecursed Hunter'], damage: 15)
    ->seed('myHand', 'Abnegation')
    ->play('Abnegation')->pay(2)
    ->expectDamage('p1Field-0', 3, 'Recover 8 + 2 * 2 curse cards = 12: 15 down to 3')
    ->tested('Abnegation')
    ->covers(['recover', 'lineage'], '**Recover 8+X**, where **X** is twice the amount of Curse cards in your champion\'s lineage.')
    ->register();

gaScenario('embrace-noir-all-allies-gain-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Embrace Noir')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Embrace Noir')
    ->play('Embrace Noir')->pay(2)
    ->expectGlobalEffect(1, 'pw9b6IJWEr', 'All allies have stealth until end of turn')
    ->tested('Embrace Noir')
    ->covers(['stealth', 'global'], 'Until end of turn, all allies gain **stealth**.')
    ->register();

gaScenario('false-step-prevents-two-damage-to-the-champion-and-makes-it-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('False Step')
    ->seed('myHand', 'False Step')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->play('False Step')->pay(1)->answer(1, 'NO')
    ->expectProperty('p1Field-0', 'TurnEffects', '["PREVENT_CHAMP_2","DISTANT","vz4kc558yx-ACTION_ACTIVATED"]', 'Two damage prevented this turn and the champion is distant')
    ->tested('False Step')
    ->covers(['prevention', 'distant', 'optional'], 'If damage would be dealt to your champion this turn, prevent 2 of that damage. Your champion becomes **distant**.')
    ->register();

gaScenario('sinister-composure-draws-a-card-and-puts-a-preparation-counter-on-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sinister Composure')
    ->seed('myHand', 'Sinister Composure')
    ->play('Sinister Composure')->pay(2)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'A preparation counter')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand, minus the card and two paid, plus one drawn')
    ->tested('Sinister Composure')
    ->covers(['draw', 'counter'], 'Draw a card, then put a **preparation** counter on your champion.')
    ->register();

gaScenario('reckonings-wake-looks-at-the-top-three-cards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reckoning\'s Wake')
    ->seed('myHand', 'Reckoning\'s Wake')
    ->play('Reckoning\'s Wake')->pay(2)->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectDqEmpty()
    ->tested('Reckoning\'s Wake')
    ->covers(['look', 'rearrange'], 'Look at the top 3+**X** cards of your deck, where **X** is the amount of Distortion objects you control. Put those cards back on top of your deck in any order.')
    ->register();

gaScenario('slip-away-makes-a-unit-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Slip Away')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Slip Away')
    ->play('Slip Away')->answer(1, 'NO')->pay(2)->target('theirField-1')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The unit became distant')
    ->tested('Slip Away')
    ->covers(['distant', 'targeting', 'imbue'], 'Target unit becomes **distant**.')
    ->register();

gaScenario('remnant-of-will-recovers-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Remnant of Will', damage: 6)
    ->seed('myHand', 'Remnant of Will')
    ->play('Remnant of Will')->pay(1)
    ->expectDamage('p1Field-0', 2, 'Recover 4: 6 down to 2')
    ->tested('Remnant of Will')
    ->covers(['recover'], 'Put two **haunt** counters on your Phantasmagoria and **recover 4**.')
    ->register();

gaScenario('reflected-blight-returns-a-graveyard-card-to-memory-and-goes-to-the-lineage-hurting-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reflected Blight')
    ->seed('myGraveyard', 'Gray Wolf')
    ->seed('myHand', 'Reflected Blight')
    ->play('Reflected Blight')->pay(1)->answer(1, 'myGraveyard-0')
    ->expectCardExists('myMemory', 'Gray Wolf', 'The graveyard card returned to memory')
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","1leLWsgBkb"]', 'Reflected Blight went to the bottom of the lineage')
    ->expectDamage('p1Field-0', 1, 'One unpreventable damage for the one Curse card in the lineage')
    ->tested('Reflected Blight')
    ->covers(['zone-movement', 'lineage', 'damage'], 'Return target card from your graveyard to your memory. Then put CARDNAME on the bottom of your champion\'s lineage. When you do, deal 1 unpreventable damage to your champion for each Curse card in your champion’s lineage.')
    ->register();

gaScenario('maledictum-vitae-goes-to-the-targets-lineage-and-its-controller-recovers-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Maledictum Vitae')
    ->patch('myField-0', ['Damage' => 6], player: 2)
    ->seed('myHand', 'Maledictum Vitae')
    ->play('Maledictum Vitae')->pay(2)->target('theirField-0')
    ->expectProperty('p2Field-0', 'Subcards', '["24I0xn0OQ1"]', 'The Maledictum Vitae is at the bottom of the target champion\'s lineage')
    ->expectDamage('p2Field-0', 2, 'The target champion\'s controller recovers 4')
    ->tested('Maledictum Vitae')
    ->covers(['lineage', 'recover', 'targeting'], 'Put CARDNAME on the bottom of target champion\'s lineage. Whenever this card is put into a champion\'s lineage, that champion\'s controller **recovers 4**.')
    ->register();

gaScenario('demons-aim-goes-to-the-lineage-and-the-champions-attacks-gain-true-sight')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Demon\'s Aim')
    ->seed('myHand', 'Demon\'s Aim')
    ->play('Demon\'s Aim')->pay(2)
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","6g7xgwve1d"]', 'Demon\'s Aim went to the bottom of the lineage')
    ->expectProperty('p1Field-0', 'TurnEffects', '["TRUE_SIGHT","6g7xgwve1d","vz4kc558yx-ACTION_ACTIVATED"]', 'The champion\'s attacks gain true sight and ignore intercept and taunt')
    ->tested('Demon\'s Aim')
    ->covers(['lineage', 'true-sight'], 'Put CARDNAME on the bottom of your champion\'s lineage. Then your champion\'s attacks this turn gain **true sight**')
    ->register();

gaScenario('load-soul-materializes-a-bullet-and-may-put-two-durability-counters-on-a-gun-then-goes-to-the-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Load Soul')
    ->seed('myMaterial', 'Plated Bullet')
    ->seed('myField', 'Blastshot Pump')
    ->seed('myHand', 'Load Soul')
    ->play('Load Soul')->pay(2)->answer(1, 'myMaterial-4')->answer(1, 'myField-1')
    ->expectCardExists('myField', 'Plated Bullet', 'The Bullet was materialized')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":4}', 'Two durability counters on top of the printed two')
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","8tuhuy4xip"]', 'Load Soul went to the bottom of the champion\'s lineage')
    ->tested('Load Soul')
    ->covers(['materialize', 'counter', 'lineage'], 'Materialize a Bullet card from your material deck, then you may put two **durability** counters on a Gun weapon you control. If you do, put CARDNAME on the bottom of your champion\'s lineage.')
    ->register();

gaScenario('expunge-discards-a-curse-from-a-lineage-and-damages-all-units-by-its-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Shadecursed Hunter'])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Expunge')
    ->play('Expunge')->pay(2)
    ->expectDamage('p2Field-1', 2, 'The discarded Shadecursed Hunter has reserve cost 2: two damage to every unit')
    ->expectDamage('p1Field-1', 2, 'Including your own units')
    ->expectProperty('p1Field-0', 'Subcards', '[]', 'The Curse card left the lineage')
    ->tested('Expunge')
    ->covers(['additional-cost', 'lineage', 'damage'], 'Deal **X** damage to all units where **X** is the reserve cost of the discarded card.')
    ->register();

gaScenario('profane-bindings-negates-an-activation-banishes-it-and-goes-to-the-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Profane Bindings')
    ->seed('myHand', 'Profane Bindings')
    ->endTurn()->pass(1)->pass(1)
    ->seed('myHand', 'Gray Wolf', player: 2)
    ->play('Gray Wolf', player: 2)->pay(2, 2)
    ->answer(1, 'myHand-7')->pay(2)->answer(1, 'EffectStack-0')->pass(2)
    ->expectZoneCount('theirBanish', 1, 1, 'The negated Gray Wolf was banished')
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","7h2k6p8fss"]', 'Profane Bindings went to the bottom of the champion\'s lineage')
    ->tested('Profane Bindings')
    ->covers(['negate', 'banish', 'lineage'], '**Negate** target card activation. Banish the card that had its activation **negated** this way. Put Profane Bindings on the bottom of your champion\'s lineage.')
    ->register();

gaScenario('spectral-haunting-returns-an-ally-card-from-the-graveyard-to-the-field-ephemeral')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Spectral Haunting')
    ->seed('myGraveyard', 'Gray Wolf')
    ->seed('myHand', 'Spectral Haunting')
    ->play('Spectral Haunting')->pay(6)->answer(1, 'myGraveyard-0')
    ->expectCardExists('myField', 'Gray Wolf', 'The ally card returned from the graveyard to the field')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'It is ephemeral')
    ->tested('Spectral Haunting')
    ->covers(['zone-movement', 'ephemeral', 'targeting'], 'Return target ally card from your graveyard to the field. It becomes **ephemeral**.')
    ->register();

gaScenario('accursed-strength-draws-three-plus-the-curse-cards-minus-influence')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Shadecursed Hunter'])
    ->emptyZone('myHand')
    ->seed('myHand', 'Accursed Strength')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->action(1, 10002, 'myHand-0!FSM!')->pay(2)
    ->expectZoneCount('myHand', 2, 1, 'Influence is the two memory cards: 3 + 1 curse - 2 = 2 cards drawn')
    ->tested('Accursed Strength')
    ->covers(['draw', 'lineage', 'influence'], 'Draw 3+**X** cards, where **X** is the amount of Curse cards in your champion\'s lineage minus your **influence**.')
    ->register();

gaScenario('accursed-strength-with-a-full-hand-and-memory-draws-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Shadecursed Hunter'])
    ->seed('myHand', 'Accursed Strength')
    ->play('Accursed Strength')->pay(2)
    ->expectZoneCount('myHand', 5, 1, 'Influence 7 (hand and memory): 3 + 1 - 7 is negative, nothing drawn')
    ->tested('Accursed Strength')
    ->covers(['draw', 'condition'], '*(If the amount of cards you would draw is negative, don\'t draw any cards.)*')
    ->register();

gaScenario('accursed-strength-diana-bonus-costs-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Cursebreaker')
    ->seed('myHand', 'Accursed Strength')
    ->play('Accursed Strength')->pay(1)
    ->expectDqEmpty()
    ->tested('Accursed Strength')
    ->covers(['diana-bonus', 'cost-reduction'], '[Diana Bonus] This card costs 1 less to activate.')
    ->register();

gaScenario('accursed-strength-a-ranger-that-is-not-diana-pays-full-price')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(classes: 'RANGER', lineage: ['Shadecursed Hunter'])
    ->seed('myHand', 'Accursed Strength')
    ->play('Accursed Strength')->pay(2)
    ->expectDqEmpty()
    ->tested('Accursed Strength')
    ->covers(['diana-bonus', 'condition'], '[Diana Bonus] This card costs 1 less to activate.')
    ->register();

gaScenario('curse-amplification-diana-bonus-costs-three-less-and-recovers-four-at-twenty-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Cursebreaker', damage: 20)
    ->seed('myHand', 'Curse Amplification')
    ->play('Curse Amplification')->pay(3)
    ->expectDamage('p1Field-0', 16, 'Twenty damage counters: recover 4')
    ->tested('Curse Amplification')
    ->covers(['diana-bonus', 'cost-reduction', 'recover', 'condition'], '[Diana Bonus] This card costs 3 less to activate. If your champion has twenty or more damage counters on them, **recover 4**.')
    ->register();

gaScenario('curtain-of-shadows-tristan-bonus-summons-an-ominous-shadow-for-each-preparation-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Shadowreaver')
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Curtain of Shadows')
    ->play('Curtain of Shadows')->pay(10)
    ->expectZoneCount('myField', 3, 1, 'Two preparation counters: two Ominous Shadow tokens')
    ->tested('Curtain of Shadows')
    ->covers(['tristan-bonus', 'summon', 'token', 'counter'], '[Tristan Bonus] **Summon** **X** Ominous Shadow tokens where **X** is the amount of **preparation** counters on your champion.')
    ->register();

gaScenario('devised-conspiracy-tristan-bonus-prepare-two-and-the-champions-next-attack-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Shadowreaver')
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myHand', 'Devised Conspiracy')
    ->play('Devised Conspiracy')->pay(1)->answer(1, 'YES')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Prepare 2 removed both preparation counters')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","dih0LPaigc"]', 'The next attack is marked for +2 power')
    ->expectGlobalEffect(1, 'dih0LPaigc_ON_HIT', 'Prepared: the champion and Ominous Shadows banish the top card on a champion hit')
    ->tested('Devised Conspiracy')
    ->covers(['tristan-bonus', 'prepare', 'stat-change'], '[Tristan Bonus] **Prepare 2** Your champion\'s next attack this turn gets +2[POWER].')
    ->register();

gaScenario('penumbral-waltz-removes-x-preparation-counters-and-prevents-x-plus-three-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Shadowreaver')
    ->patch('myField-0', ['Counters' => ['preparation' => 3]])
    ->seed('myHand', 'Penumbral Waltz')
    ->play('Penumbral Waltz')->answer(1, 'YES')->answer(1, 'YES')->answer(1, 'YES')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'X = 3: all three preparation counters removed')
    ->expectZoneCount('myField', 3, 1, 'Tristan Bonus with X 3 or greater: two Ominous Shadow tokens')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_CHAMP_6"]', 'The next 3 + 3 = 6 damage to the champion is prevented')
    ->tested('Penumbral Waltz')
    ->covers(['tristan-bonus', 'additional-cost', 'prevention', 'summon', 'token'], 'Prevent the next **X+3** damage that would be dealt to your champion this turn. [Tristan Bonus] If **X** is 3 or greater, **summon** two Ominous Shadow tokens.')
    ->register();

gaScenario('nightmare-coil-cannot-be-activated-in-the-main-phase-even-while-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Nightmare Coil')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Nightmare Coil')
    ->action(1, 10002, 'myHand-7!FSM!')
    ->expectZoneCount('myHand', 8, 1, 'Refused: only usable during your recollection phase')
    ->expectDqEmpty()
    ->tested('Nightmare Coil')
    ->covers(['timing-restriction', 'condition'], 'Activate this card only if your champion is **distant**, and only during your recollection phase.')
    ->register();

// ---- Regalia ----

gaScenario('blights-ring-pay-four-and-rest-draws-into-memory-and-goes-to-the-target-champions-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Blight\'s Ring')
    ->seed('myField', 'Blight\'s Ring')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)->answer(1, 'myField-0')
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","u8LjHnH6iC"]', 'Blight\'s Ring went to the bottom of the champion\'s lineage')
    ->expectZoneCount('myMemory', 5, 1, 'Four paid plus the card drawn into memory')
    ->tested('Blight\'s Ring')
    ->covers(['activated-ability', 'rest', 'draw-into-memory', 'lineage'], '(4), [REST]: Draw a card into your memory. Then put CARDNAME on the bottom of target champion\'s lineage.')
    ->register();

gaNormItem('tome-of-ignorance-banish-draws-a-card', 'Tome of Ignorance', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('myHand', 8, 1, 'A card was drawn'),
    ['activated-ability', 'draw', 'banish-self'], '**Banish Tome of Ignorance**: Draw a card.');

gaScenario('tome-of-ignorance-class-bonus-opposing-champions-get-minus-one-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tome of Ignorance', classBonus: true)
    ->champion(level: 2, player: 2)
    ->seed('myField', 'Tome of Ignorance')
    ->observe()
    ->expectLevel('p2Field-0', 1, 'Level 2 minus 1')
    ->tested('Tome of Ignorance')
    ->covers(['class-bonus', 'level', 'static'], '[Class Bonus] Champions you don\'t control get -1 level.')
    ->register();

gaScenario('memento-mori-puts-a-prize-counter-on-it-whenever-an-ally-dies')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Memento Mori')
    ->seed('myField', 'Memento Mori')
    ->seed('myField', 'Powered Armsmaster')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, 'PASS')
    ->expectProperty('p1Field-1', 'Counters', '{"prize":1}', 'A prize counter for the dead ally')
    ->tested('Memento Mori')
    ->covers(['trigger', 'counter'], 'Whenever an ally dies, put a **prize** counter on CARDNAME.')
    ->register();

gaNormItem('memento-mori-banish-with-six-prize-counters-draws-three-cards', 'Memento Mori', 0, 0,
    fn($s) => $s->patch('myField-1', ['Counters' => ['prize' => 6]]),
    fn($s) => $s->expectZoneCount('myHand', 10, 1, 'Seven opening cards plus three drawn'),
    ['activated-ability', 'draw', 'condition', 'banish-self'], '**Banish CARDNAME:** Draw three cards. Activate this ability only at slow speed and only if there are six or more **prize** counters on CARDNAME.');

gaScenario('gloamspire-mantle-tristan-bonus-enter-pay-three-summons-an-ominous-shadow')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Shadowreaver')
    ->seed('myField', 'Gloamspire Mantle')
    ->answer(1, 'YES')->pay(3)
    ->expectCardExists('myField', 'Ominous Shadow', 'Paid (3): an Ominous Shadow token')
    ->tested('Gloamspire Mantle')
    ->covers(['tristan-bonus', 'on-enter', 'payment', 'summon', 'token'], '[Tristan Bonus] **On Enter:** You may pay (3). If you do, **summon** an Ominous Shadow token.')
    ->register();

gaScenario('mary-ann-gets-plus-one-power-for-each-omen-with-a-different-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mary Ann, Maladroit Maid')
    ->seed('myField', 'Mary Ann, Maladroit Maid')
    ->seed('myBanish', 'Foretold Bloom', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Gray Wolf', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->observe()
    ->expectPower('p1Field-1', 2, 'Omens with reserve costs 2, 2 and 3: two different costs')
    ->tested('Mary Ann, Maladroit Maid')
    ->covers(['static', 'stat-change', 'omen'], 'Mary Ann gets +1[POWER] for each omen you have with different reserve costs.')
    ->register();

gaScenario('gloamspire-lance-class-bonus-on-hit-deals-unpreventable-damage-per-curse-card-plus-one-and-recovers-as-much')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(classes: 'RANGER', lineage: ['Tristan, Shadowdancer', 'Shadecursed Hunter'], damage: 4)
    ->seed('myField', 'Gloamspire Lance', properties: ['Subcards' => [GaScenario::id('Plated Bullet')]])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(2, '-')
    ->expectDamage('p2Field-0', 5, 'Gun power 1 plus the loaded Plated Bullet 2 plus 2 unpreventable from the On Hit (1 Curse card + 1)')
    ->expectDamage('p1Field-0', 2, 'Recover 2: 4 down to 2')
    ->tested('Gloamspire Lance')
    ->covers(['class-bonus', 'on-hit', 'damage', 'recover', 'lineage'], '[Class Bonus] **On Hit:** Deal **X** unpreventable damage to the hit object, then **recover X**, where **X** is the amount of Curse cards in the attacker\'s lineage plus 1.')
    ->register();

gaScenario('sorrowcaller-class-bonus-champion-hit-deals-damage-per-revealed-memory-card-with-reserve-cost-three-or-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sorrowcaller', classBonus: true)
    ->seed('myField', 'Sorrowcaller', properties: ['Subcards' => [GaScenario::id('Plated Bullet')]])
    ->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Gray Wolf', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(2, '-')
    ->expectDamage('p2Field-0', 5, 'Gun power 1 plus the loaded Plated Bullet 2 plus 2 unpreventable (two revealed cards with reserve cost 3 or more)')
    ->tested('Sorrowcaller')
    ->covers(['class-bonus', 'on-champion-hit', 'reveal', 'damage'], '[Class Bonus] **On Champion Hit:** Reveal the hit opponent’s memory. Deal an amount of unpreventable damage to the hit champion equal to the amount of cards with reserve cost 3 or greater revealed this way.')
    ->register();

gaScenario('ticket-to-the-afterlife-alice-bonus-rest-banishes-a-specter-card-from-the-graveyard-to-recover-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Alice, Phantom Monarch', damage: 5)
    ->seed('myField', 'Ticket to the Afterlife')
    ->seed('myGraveyard', 'Ghastly Slime')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->answer(1, 'myGraveyard-0')
    ->expectDamage('p1Field-0', 3, 'Recover 2: 5 down to 3')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Ticket rested')
    ->expectZoneCount('myMemory', 2, 1, 'The (2) was paid')
    ->expectCardExists('myBanish', 'Ghastly Slime', 'The Specter card was banished as the cost')
    ->tested('Ticket to the Afterlife')
    ->covers(['alice-bonus', 'activated-ability', 'rest', 'banish', 'recover'], '[Alice Bonus](2), [REST], **Banish a Specter card from your graveyard:** **Recover 2**.')
    ->register();

gaScenario('ticket-to-the-afterlife-specter-cards-activated-from-the-graveyard-cost-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ticket to the Afterlife', classBonus: true)
    ->seed('myField', 'Ticket to the Afterlife')
    ->seed('myGraveyard', 'Ghastly Slime')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(1)->pass(1)
    ->expectCardExists('myField', 'Ghastly Slime', 'Ephemerate (2) costs 1 with the Ticket')
    ->tested('Ticket to the Afterlife')
    ->covers(['cost-reduction', 'graveyard-activation'], 'Specter cards you activate from your graveyard cost (1) less to activate.')
    ->register();

gaScenario('folded-shadows-ciel-bonus-gives-the-champion-stealth-with-omens-totalling-33-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Ciel, Mirage\'s Grave')
    ->seed('myField', 'Folded Shadows')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->expectDqEmpty()
    ->expectProperty('p2Field-1', 'Status', '2', 'The only opposing target would be the stealthy champion: no attack was declared, the Dungeon Guide stays awake')
    ->tested('Folded Shadows')
    ->covers(['ciel-bonus', 'stealth', 'spellshroud', 'omen', 'condition'], '[Ciel Bonus] As long as the total reserve cost among your **omens** is 33 or greater, your champion has **spellshroud** and **stealth**.')
    ->register();

gaScenario('folded-shadows-with-omens-totalling-less-than-33-leaves-the-champion-targetable')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Ciel, Mirage\'s Grave')
    ->seed('myField', 'Folded Shadows')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->target('theirField-0', 2)
    ->expectProperty('p2Field-1', 'Status', '1', 'Six total omen cost: no stealth, the attack on the champion was declared')
    ->tested('Folded Shadows')
    ->covers(['ciel-bonus', 'omen', 'condition'], 'As long as the total reserve cost among your **omens** is 33 or greater')
    ->register();

gaScenario('devouring-malice-rest-removes-a-gem-counter-and-puts-three-debuff-counters-on-an-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Devouring Malice')
    ->seed('myField', 'Devouring Malice', properties: ['Counters' => ['gem' => 3]])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'A')->answer(1, 'theirField-1')
    ->expectProperty('p2Field-1', 'Counters', '{"debuff":3}', 'Three debuff counters')
    ->expectProperty('p1Field-1', 'Counters', '{"gem":2,"devouring_malice_modes":[true]}', 'One gem counter removed and the first mode recorded as chosen')
    ->tested('Devouring Malice')
    ->covers(['activated-ability', 'rest', 'counter', 'modal', 'spell'], '[REST], Remove a **gem** **counter from Devouring Malice**: As a Spell, choose one that hasn\'t been chosen— • Put three **debuff** counters on target ally.')
    ->register();

gaScenario('death-essence-amulet-an-ally-dying-on-the-opponents-turn-lets-you-banish-it-to-make-them-discard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Death Essence Amulet')
    ->seed('myField', 'Death Essence Amulet')
    ->seed('myField', 'Powered Armsmaster')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, 'PASS')->answer(1, 'YES')->answer(1, 'YES')->answer(1, 'myTempZone-0')
    ->expectCardExists('myBanish', 'Death Essence Amulet', 'The Amulet banished itself')
    ->expectZoneCount('theirGraveyard', 1, 1, 'A card from the opponent\'s hand was discarded')
    ->tested('Death Essence Amulet')
    ->covers(['trigger', 'banish-self', 'discard', 'look'], 'Whenever an ally you control dies while it\'s not your turn, you may banish Death Essence Amulet. When you do, look at target opponent\'s hand or memory and discard a card from it.')
    ->register();

gaScenario('punishing-cartridge-class-bonus-rest-loads-it-into-an-unloaded-gun')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Punishing Cartridge', classBonus: true)
    ->seed('myField', 'Punishing Cartridge')
    ->seed('myField', 'Blastshot Pump')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myField-2')
    ->expectProperty('p1Field-1', 'Subcards', '["XgzTexcCSA"]', 'The Cartridge left the field and is loaded into the Blastshot Pump')
    ->tested('Punishing Cartridge')
    ->covers(['class-bonus', 'activated-ability', 'rest', 'load'], '[Class Bonus] [REST]: Load Punishing Cartridge into target unloaded Gun weapon you control.')
    ->register();

// ---- Attacks (cast as intent for a real champion attack on turn three; the defender declines to respond) ----

gaScenario('revenants-scourge-class-bonus-champion-hit-with-a-curse-in-their-lineage-wakes-the-attacker-and-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Revenant\'s Scourge', classBonus: true)
    ->champion(lineage: ['Shadecursed Hunter'], player: 2)
    ->seed('myHand', 'Revenant\'s Scourge')
    ->reachTurnThree()
    ->castSpell('Revenant\'s Scourge', pay: 1)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectDamage('p2Field-0', 4, 'Revenant\'s Scourge power 4')
    ->expectProperty('p1Field-0', 'Status', '2', 'The attacker woke up')
    ->expectZoneCount('myHand', 8, 1, 'Nine in hand, minus the card played and the one paid, plus the card drawn')
    ->tested('Revenant\'s Scourge')
    ->covers(['class-bonus', 'on-champion-hit', 'wake-up', 'draw', 'lineage'], '[Class Bonus] **On Champion Hit:** If there are one or more Curse cards in the hit champion’s lineage, wake up the attacker and draw a card.')
    ->register();

gaScenario('exsanguinating-wallop-class-bonus-on-attack-recovers-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Exsanguinating Wallop', classBonus: true, damage: 6)
    ->seed('myHand', 'Exsanguinating Wallop')
    ->reachTurnThree()
    ->castSpell('Exsanguinating Wallop', pay: 5)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectDamage('p1Field-0', 3, 'On Attack: recover 3, 6 down to 3')
    ->expectDamage('p2Field-0', 5, 'Power 5')
    ->tested('Exsanguinating Wallop')
    ->covers(['class-bonus', 'on-attack', 'recover'], '[Class Bonus] **On Attack**: **Recover 3**.')
    ->register();

gaScenario('debilitating-grasp-class-bonus-champion-hit-goes-to-the-hit-champions-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Debilitating Grasp', classBonus: true)
    ->seed('myHand', 'Debilitating Grasp')
    ->reachTurnThree()
    ->castSpell('Debilitating Grasp', pay: 2)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectProperty('p2Field-0', 'Subcards', '["wbsmks4etk"]', 'Debilitating Grasp went to the bottom of the hit champion\'s lineage')
    ->tested('Debilitating Grasp')
    ->covers(['class-bonus', 'on-champion-hit', 'lineage'], '[Class Bonus] **On Champion Hit:** Put CARDNAME on the bottom of the hit champion’s lineage.')
    ->register();

gaScenario('hexbound-blade-tristan-bonus-champion-hit-goes-to-the-target-champions-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Shadowreaver')
    ->seed('myHand', 'Hexbound Blade')
    ->reachTurnThree()
    ->castSpell('Hexbound Blade', pay: 2)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectProperty('p2Field-0', 'Subcards', '["RfQhLQ539Z"]', 'Hexbound Blade went to the bottom of the target champion\'s lineage')
    ->tested('Hexbound Blade')
    ->covers(['tristan-bonus', 'on-hit', 'lineage'], '[Tristan Bonus] **On Hit: **Put CARDNAME on the bottom of target champion\'s lineage.')
    ->register();

gaScenario('extorting-blackjack-with-omens-totalling-exactly-21-gets-plus-ten-power-and-class-bonus-recovers-per-distinct-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Extorting Blackjack', classBonus: true, damage: 4)
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Extorting Blackjack')
    ->reachTurnThree()
    ->castSpell('Extorting Blackjack', pay: 2)
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectDamage('p2Field-0', 12, '2 power + 10 with omens totalling exactly 21')
    ->expectDamage('p1Field-0', 3, 'On Attack: one distinct omen cost, recover 1 (4 down to 3)')
    ->tested('Extorting Blackjack')
    ->covers(['omen', 'stat-change', 'class-bonus', 'on-attack', 'recover', 'condition'], 'As long as the total reserve cost among your omens is exactly 21, Extorting Blackjack gets +10[POWER]. [Class Bonus] **On Attack**: **Recover** **X**, where **X** is the amount of **omens** you have with different costs.')
    ->register();

gaScenario('beguiling-coup-element-bonus-as-your-only-reserve-cost-one-omen-gives-the-champions-attacks-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Beguiling Coup')
    ->seed('myBanish', 'Beguiling Coup', properties: ['Counters' => ['omen' => 1]])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('theirField-0')->answer(2, '-')
    ->expectDamage('p2Field-0', 1, 'The omen Beguiling Coup is the only reserve-cost-1 omen: the champion\'s attack deals 0 + 1')
    ->tested('Beguiling Coup')
    ->covers(['element-bonus', 'omen', 'stat-change', 'condition'], '[Element Bonus] As long as this card is one of your **omens** and you have no other reserve cost 1 **omens**, your champion\'s attacks get +1[POWER].')
    ->register();

// ---- Champions ----

gaScenario('alice-phantom-monarch-inherited-playing-an-advanced-element-card-with-no-curse-in-the-lineage-deals-seven-unpreventable-damage-to-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Spirit of Fire', lineage: ['Alice, Phantom Monarch', 'Tristan, Shadowdancer'])
    ->seed('myHand', 'Sinister Composure')
    ->play('Sinister Composure')->pay(2)
    ->expectDamage('p1Field-0', 7, 'Playing an advanced element card with no Curse card in the lineage: 7 unpreventable damage')
    ->tested('Alice, Phantom Monarch')
    ->covers(['inherited-effect', 'damage', 'condition'], '**Inherited Effect —** Whenever you play an advanced element card while there are no Curse cards in Alice\'s lineage, deal 7 unpreventable damage to Alice.')
    ->register();

gaScenario('diana-cursebreaker-banishing-four-curse-cards-from-the-lineage-materializes-two-bullets')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Cursebreaker', lineage: ['Creeping Torment', 'Creeping Torment', 'Creeping Torment', 'Creeping Torment'])
    ->seed('myMaterial', 'Plated Bullet')->seed('myMaterial', 'Tasershot')
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->answer(1, 'myMaterial-4')->answer(1, 'myMaterial-4')
    ->expectProperty('p1Field-0', 'Subcards', '[]', 'All four Curse cards were banished from the lineage')
    ->expectZoneCount('myBanish', 4, 1, 'In the banishment')
    ->expectZoneCount('myField', 3, 1, 'Two Bullets were materialized')
    ->expectProperty('p1Field-0', 'TurnEffects', '["CURSEBREAKER_ON_ATTACK"]', 'Diana gains the On Attack wake up until end of turn')
    ->tested('Diana, Cursebreaker')
    ->covers(['activated-ability', 'lineage', 'materialize', 'condition'], '**Banish all Curse cards from Diana\'s lineage:** Materialize two Bullet cards from your material deck. Until end of turn, Diana gains "**On Attack:** Wake up Diana." Activate this ability only if there are four or more Curse cards in Diana\'s lineage.')
    ->register();

// ---- Ominous Shadow, Spirelle, Soutirer Vortex, Awaken Ombre ----

gaScenario('ominous-shadow-prevents-three-damage-dealt-to-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ominous Shadow')
    ->seed('myField', 'Ominous Shadow')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'PASS')
    ->expectDamage('p1Field-1', 0, 'Maiden of Waning Bloom deals 3: all of it is prevented')
    ->tested('Ominous Shadow')
    ->covers(['prevention', 'static'], 'If damage would be dealt to CARDNAME, prevent 3 of that damage.')
    ->register();

gaScenario('ominous-shadow-may-only-attack-units-your-champion-has-dealt-combat-damage-to-this-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ominous Shadow')
    ->seed('myField', 'Ominous Shadow')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'The champion has dealt no combat damage this turn: the Shadow has no legal attack target and stays awake')
    ->tested('Ominous Shadow')
    ->covers(['attack-restriction', 'unblockable'], 'CARDNAME may only declare attacks against units your champion has dealt combat damage to this turn.')
    ->register();

gaScenario('spirelle-schwartz-queen-enter-deals-three-damage-to-a-unit-and-may-banish-a-card-face-down')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Spirelle, Schwartz Queen')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Spirelle, Schwartz Queen')
    ->play('Spirelle, Schwartz Queen')->pay(2)->target('theirField-1')
    ->expectDamage('p2Field-1', 3, 'Three damage')
    ->tested('Spirelle, Schwartz Queen')
    ->covers(['on-enter', 'damage', 'banish', 'spell', 'targeting'], '**On Enter**: As a Spell, deal 3 damage to target unit. Then you may banish a card from your hand or memory face down.')
    ->register();

gaScenario('soutirer-vortex-costs-three-less-with-three-omens-of-different-reserve-costs')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Soutirer Vortex')
    ->seed('myBanish', 'Foretold Bloom', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Giant Tortoise', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Soutirer Vortex')
    ->play('Soutirer Vortex')->pay(3)
    ->expectCardExists('myField', 'Soutirer Vortex', 'Cost 6 - 3 = 3 with omens costing 2, 3 and 4')
    ->tested('Soutirer Vortex')
    ->covers(['cost-reduction', 'omen', 'condition'], 'As long as you have three or more **omens** with different reserve costs, this card costs 3 less to activate.')
    ->register();

gaScenario('awaken-ombre-ciel-bonus-puts-ally-omens-onto-the-field-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Ciel, Mirage\'s Grave')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Awaken Ombre')
    ->play('Awaken Ombre')->answer(1, '1')->pay(4)->answer(1, 'myBanish-0')
    ->expectCardExists('myField', 'Dungeon Guide', 'The ally omen was put onto the field')
    ->expectProperty('p1Field-1', 'Status', '1', 'Rested: a norm element ally is not woken (only non-norm elements matching the champion\'s count)')
    ->tested('Awaken Ombre')
    ->covers(['ciel-bonus', 'omen', 'additional-cost', 'zone-movement'], '[Ciel Bonus] Put up to **X** target ally **omens** you have onto the field rested.')
    ->register();
