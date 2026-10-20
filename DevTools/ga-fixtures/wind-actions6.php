<?php
/**
 * Phase 3b (Wind): the remaining actions, attacks and boons that need no champion bonus. Written on the 'quiet' deck.
 */

// ---- Breath's Coloratura ----

gaScenario('breaths-coloratura-puts-a-buff-counter-on-each-human-ally-and-later-humans-enter-with-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Breath\'s Coloratura')
    ->seed('myField', 'Dungeon Guide')->seed('myField', 'Gale\'s Mare')
    ->seed('myHand', 'Breath\'s Coloratura')->seed('myHand', 'Blightheart Adept')
    ->castSpell('Breath\'s Coloratura', pay: 3, window: '')
    ->play('Blightheart Adept')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Human Dungeon Guide got a buff counter')
    ->expectProperty('p1Field-2', 'Counters', '[]', 'The Mare is not Human: no counter')
    ->expectProperty('p1Field-3', 'Counters', '{"buff":1}', 'A Human ally that entered later this turn came in with a buff counter')
    ->tested('Breath\'s Coloratura')
    ->covers(['counter', 'trigger', 'targeting'], 'Put a **buff** counter on each Human ally you control. Until end of turn, Human allies that enter the field under your control enter with an additional **buff** counter on them.')
    ->register();

// ---- Destined Encounter ----

gaScenario('destined-encounter-scavenge-six-finds-an-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Destined Encounter')
    ->deckTop(['Nascent Blast', 'Nascent Blast', 'Gray Wolf'])
    ->seed('myHand', 'Destined Encounter')
    ->castSpell('Destined Encounter', pay: 3, window: '')->answer(1, 'myTempZone-3')
    ->expectCardExists('myHand', 'Gray Wolf', 'The ally within the top six was put into the hand')
    ->tested('Destined Encounter')
    ->covers(['scavenge'], '**Scavenge 6** for an ally card.')
    ->register();

gaScenario('destined-encounter-an-elysian-ally-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Destined Encounter')
    ->deckTop(['Nascent Blast', 'Elysian Aspirant'])
    ->seed('myHand', 'Destined Encounter')
    ->castSpell('Destined Encounter', pay: 3, window: '')->answer(1, 'myTempZone-4')
    ->expectCardExists('myHand', 'Elysian Aspirant', 'The Elysian ally was scavenged')
    ->expectZoneCount('myMemory', 4, 1, 'Three paid cards plus a card drawn into memory (the scavenged card was Elysian)')
    ->tested('Destined Encounter')
    ->covers(['scavenge', 'draw-into-memory', 'condition'], 'If the **scavenged **card is Elysian, draw a card into your memory.')
    ->register();

// ---- Combo Strike / Updraft Slice ----

gaScenario('combo-strike-class-bonus-level-two-on-kill-wakes-up-your-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Combo Strike', classBonus: true, level: 2)
    ->seed('myField', 'Owl Familiar', player: 2)
    ->seed('myHand', 'Combo Strike')
    ->reachTurnThree()
    ->play('Combo Strike')->pay(2)->target('theirField-1')->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The 1-life Owl Familiar was killed')
    ->expectProperty('p1Field-0', 'Status', '2', 'On Kill: the champion woke up')
    ->tested('Combo Strike')
    ->covers(['class-bonus', 'level-gate', 'on-kill', 'status', 'combat'], '[Class Bonus] [Level 2+] **On Kill:** Wake up your champion.')
    ->register();

gaScenario('combo-strike-below-level-two-on-kill-leaves-the-champion-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Combo Strike', classBonus: true, level: 1)
    ->seed('myField', 'Owl Familiar', player: 2)
    ->seed('myHand', 'Combo Strike')
    ->reachTurnThree()
    ->play('Combo Strike')->pay(2)->target('theirField-1')->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The Owl Familiar was killed')
    ->expectProperty('p1Field-0', 'Status', '1', 'Level 1: the champion stays rested')
    ->tested('Combo Strike')
    ->covers(['class-bonus', 'level-gate', 'on-kill', 'condition'], '[Class Bonus] [Level 2+] **On Kill:**')
    ->register();

gaScenario('updraft-slice-class-bonus-gets-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Updraft Slice', classBonus: true)
    ->seed('myHand', 'Updraft Slice')
    ->reachTurnThree()
    ->play('Updraft Slice')->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0', 3, 'Class Bonus: 2 + 1 = 3 damage')
    ->tested('Updraft Slice')
    ->covers(['class-bonus', 'stat-change', 'combat'], '[Class Bonus] CARDNAME gets +1[POWER].')
    ->register();

gaScenario('updraft-slice-without-the-class-bonus-has-its-printed-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Updraft Slice')
    ->seed('myHand', 'Updraft Slice')
    ->reachTurnThree()
    ->play('Updraft Slice')->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0', 2, 'No class bonus: 2 damage')
    ->tested('Updraft Slice')
    ->covers(['class-bonus', 'stat-change', 'condition'], '[Class Bonus] CARDNAME gets +1[POWER].')
    ->register();

// ---- Mechanized Smasher ----
// Player 2 attacks on turn 2 without a recollection phase, so the memory cards seeded for the additional cost are still in memory.

gaScenario('mechanized-smasher-attack-reveals-four-wind-cards-from-memory-as-an-additional-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mechanized Smasher', player: 2)
    ->seed('myField', 'Mechanized Smasher', player: 2)
    ->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->target('myField-1', 2)
    ->answer(2, 'myMemory-0')->answer(2, 'myMemory-1')->answer(2, 'myMemory-2')->answer(2, 'myMemory-3')->target('theirField-0', 2)
    ->expectDamage('p1Field-0', 4, 'The Smasher hit for its 4 power')
    ->tested('Mechanized Smasher')
    ->covers(['additional-cost', 'reveal', 'combat'], 'As an additional cost to use CARDNAME for an attack, reveal four wind element cards from your memory.')
    ->register();

gaScenario('mechanized-smasher-cannot-attack-with-fewer-than-four-wind-cards-in-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mechanized Smasher', player: 2)
    ->seed('myField', 'Mechanized Smasher', player: 2)
    ->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Windrider Mage', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')
    ->expectProperty('p2Field-0', 'Status', '2', 'Only three wind cards in memory: the champion could not attack with the Smasher')
    ->tested('Mechanized Smasher')
    ->covers(['additional-cost', 'condition'], 'reveal four wind element cards from your memory')
    ->register();

// ---- Lesser Boon of Astraeus ----

gaScenario('lesser-boon-of-astraeus-pays-three-to-suppress-an-ally-once-only')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Astraeus')
    ->seed('myField', 'Lesser Boon of Astraeus')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('theirField-1')
    ->expectZoneCount('theirBanish', 1, 1, 'The ally was suppressed')
    ->expectZoneCount('myMemory', 3, 1, 'The (3) cost was paid')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myMemory', 3, 1, 'Activate this ability only once: the second activation paid nothing')
    ->tested('Lesser Boon of Astraeus')
    ->covers(['activated-ability', 'suppress', 'activation-limit', 'targeting'], '(3): As a Spell, **suppress** target ally, item, or weapon. Activate this ability only once.')
    ->register();

// ---- Winbless Kiteshield ----
// Unit Link is made when the regalia is materialized; the link is seeded directly (the unit carries the Kiteshield in its Subcards, the Kiteshield points back).

gaScenario('winbless-kiteshield-unit-link-gives-the-linked-unit-vigor')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Winbless Kiteshield')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1, 'Subcards' => [GaScenario::id('Winbless Kiteshield')]])
    ->seed('myField', 'Winbless Kiteshield', properties: ['Counters' => ['linkedToAlly' => GaScenario::id('Dungeon Guide')]])
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'The linked unit has vigor: it woke at the end phase')
    ->tested('Winbless Kiteshield')
    ->covers(['unit-link', 'vigor'], '**Unit Link**', 'Linked unit has **vigor**.')
    ->register();

// ---- Orchestrated Seizure ----

gaScenario('orchestrated-seizure-can-be-activated-during-the-opponents-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Orchestrated Seizure')
    ->seed('myHand', 'Orchestrated Seizure')
    ->seed('myGraveyard', 'Throne-Keeper Bullfrog', player: 2)
    ->endTurn()
    ->endTurn(2)->answer(1, 'myHand-7')->pay(3)
    ->expectGlobalEffect(1, 'pwscn0esog_ACTIVE', 'Activated in the opponent\'s end phase: the floating-memory access is active in my materialize phase', 1, 8)
    ->expectCardExists('myGraveyard', 'Orchestrated Seizure', 'The action resolved')
    ->answer(1, 'myMaterial-0')->answer(1, 'theirGraveyard-0')
    ->expectCardExists('theirBanish', 'Throne-Keeper Bullfrog', 'The opponent\'s floating-memory card was banished (into its owner\'s banishment) to pay the 1 memory cost', 1)
    ->tested('Orchestrated Seizure')
    ->covers(['floating-memory', 'materialize'], 'During your next materialize phase, you may banish cards with **floating memory** from your opponents’ graveyards to pay for memory costs.')
    ->covers(['activation-timing', 'floating-memory', 'materialize'], 'Activate this card only during an opponent’s end phase.')
    ->register();

// ---- Diablerie ----

gaScenario('diablerie-registers-the-steal-the-next-divine-relic-effect')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Diablerie')
    ->seed('myHand', 'Diablerie')
    ->play('Diablerie')->pay(2)
    ->expectGlobalEffect(1, '0plqbtjuxz', 'The next-divine-relic effect is registered for this turn')
    ->tested('Diablerie')
    ->covers(['divine-relic', 'replacement-effect'], 'The next time a regalia with **divine relic** would enter the field this turn, it enters the field under your control instead.')
    ->register();
