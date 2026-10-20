<?php
/**
 * Phase 3a (Fire): allies, batch 3 -- ranged / distant units, On Enter and On Death effects, recollection and end-phase
 * triggers. Ranged units are seeded on player 2's field (awake, DISTANT lasts until their controller's own end phase) and attack
 * on player 2's first turn, the same way automaton-bomber-ranged-attack does.
 */

// --- Ranged ---
gaScenario('flamebolt-arbalist-distant-attack-gets-plus-four-power')
    ->standardDeck('Flamebolt Arbalist')
    ->seed('myField', 'Flamebolt Arbalist', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->tested('Flamebolt Arbalist')
    ->expectDamage('p1Field-0', 6, 'Ranged 4 while distant: 2 + 4 = 6 damage')
    ->covers(['keyword', 'ranged', 'combat'], 'Ranged 4 (As long as this unit is distant, its attacks get +4 [POWER].)')
    ->register();

gaScenario('flamebolt-arbalist-not-distant-attack-has-base-power')
    ->standardDeck('Flamebolt Arbalist')
    ->seed('myField', 'Flamebolt Arbalist', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->tested('Flamebolt Arbalist')
    ->expectDamage('p1Field-0', 2, 'Not distant: only its 2 POWER')
    ->covers(['keyword', 'ranged', 'condition'], 'Ranged 4 (As long as this unit is distant, its attacks get +4 [POWER].)')
    ->register();

gaScenario('airship-cannoneer-class-bonus-distant-attack-gets-plus-four-power')
    ->standardDeck('Airship Cannoneer')
    ->readyFor('Airship Cannoneer', classBonus: true, player: 2)
    ->seed('myField', 'Airship Cannoneer', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->tested('Airship Cannoneer')
    ->expectDamage('p1Field-0', 6, '[Class Bonus] Ranged 4 while distant: 2 + 4 = 6 damage')
    ->covers(['keyword', 'ranged', 'class-bonus'], '[Class Bonus] Ranged 4 (As long as this unit is distant, its attacks get +4 [POWER].)')
    ->register();

gaScenario('airship-cannoneer-without-the-class-bonus-distant-attack-has-base-power')
    ->standardDeck('Airship Cannoneer')
    ->seed('myField', 'Airship Cannoneer', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->tested('Airship Cannoneer')
    ->expectDamage('p1Field-0', 2, 'No Class Bonus: Ranged does not apply, only 2 POWER')
    ->covers(['keyword', 'ranged', 'class-bonus', 'condition'], '[Class Bonus] Ranged 4 (As long as this unit is distant, its attacks get +4 [POWER].)')
    ->register();

gaScenario('ashfletched-bowman-ranged-three-distant-attack-gets-plus-three-power')
    ->standardDeck('Ashfletched Bowman')
    ->seed('myField', 'Ashfletched Bowman', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->tested('Ashfletched Bowman')
    ->expectDamage('p1Field-0', 4, 'Ranged 3 while distant: 1 + 3 = 4 damage')
    ->covers(['keyword', 'ranged', 'combat'], 'Ranged 3 (As long as this unit is distant, its attacks get +3 [POWER].)')
    ->register();

gaScenario('fiery-duelist-enter-discarding-a-fire-card-draws-and-becomes-distant')
    ->standardDeck('Fiery Duelist')
    ->readyFor('Fiery Duelist', classBonus: true)
    ->seed('myHand', 'Red Slime')
    ->seed('myHand', 'Fiery Duelist')
    ->castSpell('Fiery Duelist', pay: 3)
    ->answer(1, 'myHand-4')
    ->tested('Fiery Duelist')
    ->expectProperty('p1Graveyard-0', 'CardID', 'mttsvbgl6f', 'On Enter: the fire card was discarded')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'It becomes distant')
    ->expectZoneCount('myHand', 5, 1, 'Hand: 9 - Duelist - 3 reserve - discard + 1 draw')
    ->covers(['on-enter', 'discard', 'draw-discard', 'distant'], 'On Enter: You may discard a fire element card. If you do, draw a card and CARDNAME becomes distant.')
    ->register();

gaScenario('volatile-fusilier-class-bonus-enter-deals-four-to-your-champion-and-becomes-distant')
    ->standardDeck('Volatile Fusilier')
    ->readyFor('Volatile Fusilier', classBonus: true)
    ->seed('myHand', 'Volatile Fusilier')
    ->castSpell('Volatile Fusilier', pay: 1)
    ->answer(1, 'YES')
    ->tested('Volatile Fusilier')
    ->expectDamage('p1Field-0', 4, '[Class Bonus] On Enter: it dealt 4 damage to your champion')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'It becomes distant')
    ->covers(['on-enter', 'damage', 'distant', 'class-bonus'], '[Class Bonus] On Enter: You may have CARDNAME deal 4 damage to your champion. If you do, CARDNAME becomes distant.')
    ->register();

// --- Retaliation / combat triggers ---
gaScenario('blazing-destrier-attacked-deals-two-to-the-attacker-and-your-champion')
    ->standardDeck('Blazing Destrier')
    ->seed('myField', 'Blazing Destrier')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->pass(2)->pass(1)
    ->tested('Blazing Destrier')
    ->expectDamage('p2Field-1', 2, 'The attacker took 2 damage when it attacked the Destrier')
    ->expectDamage('p1Field-0', 2, 'Your champion took 2 damage')
    ->covers(['combat', 'damage', 'steadfast'], 'Whenever CARDNAME is attacked, deal 2 damage to the attacker and your champion.')
    ->register();

gaScenario('inflamed-bladehand-class-bonus-gets-plus-two-power-when-your-champion-attacks')
    ->standardDeck('Inflamed Bladehand')
    ->readyFor('Inflamed Bladehand', classBonus: true)
    ->seed('myField', 'Warrior\'s Longsword')
    ->seed('myField', 'Inflamed Bladehand')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->tested('Inflamed Bladehand')
    ->expectPower('p1Field-2', 4, '[Class Bonus] Your champion attacked: 2 + 2 = 4 POWER')
    ->covers(['triggered-ability', 'class-bonus', 'stat-change'], '[Class Bonus] Whenever your champion attacks, CARDNAME gets +2 [POWER] until end of turn.')
    ->register();

gaScenario('inflamed-bladehand-without-the-class-bonus-keeps-its-printed-power-when-your-champion-attacks')
    ->standardDeck('Inflamed Bladehand')
    ->readyFor('Inflamed Bladehand')
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Warrior\'s Longsword')
    ->seed('myField', 'Inflamed Bladehand')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->tested('Inflamed Bladehand')
    ->expectPower('p1Field-2', 2, 'Control: the same champion attack without the Class Bonus')
    ->covers(['triggered-ability', 'class-bonus', 'condition'], '[Class Bonus] Whenever your champion attacks, CARDNAME gets +2 [POWER] until end of turn.')
    ->register();

// --- Recollection / end phase ---
gaScenario('patient-rogue-gets-plus-three-power-at-the-beginning-of-your-recollection-phase')
    ->standardDeck('Patient Rogue')
    ->seed('myField', 'Patient Rogue')
    ->reachTurnThree()
    ->observe()
    ->tested('Patient Rogue')
    ->expectPower('p1Field-1', 3, 'Recollection phase: 0 + 3 = 3 POWER until end of turn')
    ->covers(['recollection-phase', 'stat-change'], 'At the beginning of your recollection phase, CARDNAME gets +3 [POWER] until end of turn.')
    ->register();

gaScenario('emberwrath-witch-is-sacrificed-at-the-beginning-of-your-end-phase')
    ->standardDeck('Emberwrath Witch')
    ->readyFor('Emberwrath Witch')
    ->seed('myHand', 'Emberwrath Witch')
    ->castSpell('Emberwrath Witch', pay: 2)
    ->endTurn()
    ->tested('Emberwrath Witch')
    ->expectProperty('p1Graveyard-0', 'CardID', 'PptfA8gG6h', 'Sacrificed at the beginning of the end phase')
    ->covers(['end-phase', 'sacrifice'], 'At the beginning of your end phase, sacrifice CARDNAME.')
    ->register();

gaScenario('embershield-keeper-class-bonus-fostered-gets-plus-two-life')
    ->standardDeck('Embershield Keeper')
    ->readyFor('Embershield Keeper', classBonus: true)
    ->seed('myField', 'Embershield Keeper')
    ->reachTurnThree()
    ->observe()
    ->tested('Embershield Keeper')
    ->expectLife('p1Field-1', 4, '[Class Bonus] Fostered at the recollection phase (undamaged): 2 + 2 = 4 LIFE')
    ->covers(['foster', 'class-bonus', 'stat-change'], '[Class Bonus] As long as CARDNAME is fostered, it gets +2 [LIFE].')
    ->register();

// --- Enter / death ---
gaScenario('priestess-of-flame-class-bonus-enter-deals-three-to-your-champion-and-gathers-twice')
    ->standardDeck('Priestess of Flame')
    ->readyFor('Priestess of Flame', classBonus: true)
    ->seed('myHand', 'Priestess of Flame')
    ->castSpell('Priestess of Flame', pay: 2)
    ->answer(1, 'YES')
    ->tested('Priestess of Flame')
    ->expectDamage('p1Field-0', 3, '[Class Bonus] On Enter: it dealt 3 damage to your champion')
    ->expectZoneCount('myField', 4, 1, 'Gather twice: two tokens joined the champion and Priestess of Flame')
    ->covers(['on-enter', 'damage', 'gather', 'class-bonus'], '[Class Bonus] On Enter: You may have CARDNAME deal 3 damage to your champion. If you do, gather twice.')
    ->register();

gaScenario('incinerated-templar-class-bonus-enter-returns-a-specter-ally-as-ephemeral')
    ->standardDeck('Incinerated Templar')
    ->readyFor('Incinerated Templar', classBonus: true)
    ->seed('myGraveyard', 'Emberwrath Witch')
    ->seed('myHand', 'Incinerated Templar')
    ->castSpell('Incinerated Templar', pay: 3)
    ->answer(1, 'myGraveyard-0')
    ->tested('Incinerated Templar')
    ->expectProperty('p1Field-2', 'CardID', 'PptfA8gG6h', 'The Specter ally came back from the graveyard')
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL","SACRIFICE_NEXT_END_PHASE","PptfA8gG6h-ENDPHASE_SACRIFICE"]', 'It is ephemeral and sacrificed at the next end phase')
    ->covers(['on-enter', 'reanimate', 'ephemeral', 'class-bonus'], '[Class Bonus] On Enter: Put target Specter ally card with reserve cost 3 or less from your graveyard onto the field. It becomes ephemeral.')
    ->register();

gaScenario('inferno-slime-class-bonus-on-death-banishes-two-fire-cards-to-deal-four-to-each-champion')
    ->standardDeck('Undeniable Truth')
    ->readyFor('Inferno Slime', classBonus: true)
    ->seed('myField', 'Inferno Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myHand', 'Undeniable Truth')
    ->play('Undeniable Truth')->target('myField-1')
    ->answer(1, 'YES')->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-1')->pay(1)
    ->tested('Inferno Slime')
    ->expectDamage('p1Field-0', 4, '[Class Bonus] On Death: 4 damage to your champion')
    ->expectDamage('p2Field-0', 4, '[Class Bonus] On Death: 4 damage to the opposing champion')
    ->expectZoneCount('myBanish', 2, 1, 'Two fire cards were banished from the graveyard')
    ->covers(['on-death', 'banish', 'damage', 'class-bonus'], '[Class Bonus] On Death: You may banish two fire element cards from your graveyard. If you do, deal 4 damage to each champion.')
    ->register();

// --- Batch 3b ---
gaScenario('emberwrath-witch-ephemerate-from-the-graveyard-enters-ephemeral')
    ->standardDeck('Emberwrath Witch')
    ->readyFor('Emberwrath Witch')
    ->seed('myGraveyard', 'Emberwrath Witch')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)
    ->tested('Emberwrath Witch')
    ->expectCardExists('myField', 'Emberwrath Witch', 'Ephemerate (2): the Witch came back from the graveyard')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL","PptfA8gG6h-ENDPHASE_SACRIFICE"]', 'It entered ephemeral')
    ->covers(['ephemerate', 'graveyard-activation', 'ephemeral'], 'Ephemerate -- (2) (You may activate this card from your graveyard by paying this cost. Ally cards played this way become ephemeral as they enter the field.)')
    ->register();

gaScenario('cinderbloom-tender-class-bonus-gather-deals-one-damage-to-each-champion')
    ->standardDeck('Cinderbloom Tender')
    ->readyFor('Cinderbloom Tender', classBonus: true)
    ->seed('myField', 'Cinderbloom Tender')
    ->seed('myHand', 'Priestess of Flame')
    ->castSpell('Priestess of Flame', pay: 2)
    ->answer(1, 'YES')
    ->tested('Cinderbloom Tender')
    ->expectDamage('p1Field-0', 5, 'Priestess dealt 3 to its champion, then two gathers dealt 1 each: 3 + 2 = 5')
    ->expectDamage('p2Field-0', 2, 'Each gather dealt 1 damage to the opposing champion')
    ->covers(['triggered-ability', 'gather', 'damage', 'class-bonus'], '[Class Bonus] Whenever you gather, deal 1 damage to each champion.')
    ->register();

gaScenario('blazing-direwolf-class-bonus-on-attack-deals-two-damage-to-a-unit')
    ->standardDeck('Blazing Direwolf')
    ->readyFor('Blazing Direwolf', classBonus: true, level: 5)
    ->seed('myField', 'Blazing Direwolf')
    ->seed('theirField', 'Dungeon Guide')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->answer(1, 'theirField-1')
    ->tested('Blazing Direwolf')
    ->expectDamage('p2Field-1', 2, '[Class Bonus] On Attack: 2 damage to the targeted unit')
    ->expectDamage('p2Field-0', 4, 'The attack itself dealt the Direwolf\'s 4 POWER to the champion')
    ->covers(['on-attack', 'damage', 'class-bonus', 'pride'], '[Class Bonus] On Attack: Deal 2 damage to target unit.')
    ->register();

gaScenario('energetic-beastbonder-lets-a-pride-beast-obey-by-raising-your-champion-level')
    ->standardDeck('Energetic Beastbonder')
    ->readyFor('Energetic Beastbonder', level: 1)
    ->seed('myField', 'Energetic Beastbonder')
    ->seed('myField', 'Inferno Slime')
    ->reachTurnThree()
    ->attack('myField-2', 'theirField-0')
    ->tested('Energetic Beastbonder')
    ->expectDamage('p2Field-0', 3, 'Level 1 + 1 = 2 with a Beast ally: Pride 2 is satisfied and the Slime attacked for 3')
    ->covers(['static-ability', 'level-gate', 'pride'], 'Your champion gets +1 level as long as you control an Animal or Beast ally.')
    ->register();

gaScenario('flamelash-subduer-lets-a-pride-beast-obey-by-raising-your-champion-level')
    ->standardDeck('Flamelash Subduer')
    ->readyFor('Flamelash Subduer', level: 1)
    ->seed('myField', 'Flamelash Subduer')
    ->seed('myField', 'Inferno Slime')
    ->reachTurnThree()
    ->attack('myField-2', 'theirField-0')
    ->tested('Flamelash Subduer')
    ->expectDamage('p2Field-0', 3, 'Level 1 + 1 = 2 with a Beast ally: Pride 2 is satisfied and the Slime attacked for 3')
    ->covers(['static-ability', 'level-gate', 'pride'], 'Your champion gets +1 level as long as you control an Animal or Beast ally.')
    ->register();

gaScenario('pride-beast-does-not-obey-without-the-level')
    ->standardDeck('Inferno Slime')
    ->readyFor('Inferno Slime', level: 1)
    ->seed('myField', 'Inferno Slime')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->tested('Inferno Slime')
    ->expectDamage('p2Field-0', 0, 'Pride 2: a level 1 champion cannot make the Slime attack, so no damage was dealt')
    ->covers(['pride', 'condition'], 'Pride 2 (This ally won\'t obey you unless your champion is level 2 or higher.)')
    ->register();

gaScenario('fumant-shieldmaiden-on-death-draws-for-each-player-with-low-influence')
    ->standardDeck('Fumant Shieldmaiden')
    ->readyFor('Fumant Shieldmaiden')
    ->emptyZone('myHand', destZone: 'myBanish')
    ->emptyZone('myHand', player: 2, destZone: 'myBanish')
    ->seed('myField', 'Fumant Shieldmaiden')
    ->seed('myHand', 'Undeniable Truth')
    ->seed('myHand', 'Dungeon Guide')
    ->playSlot(0)->target('myField-1')->pay(1)
    ->tested('Fumant Shieldmaiden')
    ->expectZoneCount('theirHand', 1, 1, 'On Death: the opponent (influence 0) drew a card')
    ->covers(['on-death', 'draw-discard', 'condition'], 'On Death: Each player with influence four or less draws a card.')
    ->register();
