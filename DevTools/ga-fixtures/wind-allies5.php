<?php
/**
 * Phase 3b (Wind): allies, seventh batch (imbue allies). Imbue asks YES/NO before payment; YES reserves the paid cards by hand pick, and the ally is imbued when
 * at least the required number of them are wind. Windrider Mage is the cheap non-fast wind card used for the reserve.
 */

// ---- Windmill Engineer ----

gaScenario('windmill-engineer-imbued-on-enter-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Windmill Engineer')
    ->readyFor('Windmill Engineer')
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->seed('myHand', 'Windmill Engineer')
    ->play('Windmill Engineer')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Two wind cards were reserved: imbued')
    ->expectZoneCount('myMemory', 3, 1, 'Two reserved cards plus the card drawn into memory')
    ->tested('Windmill Engineer')
    ->covers(['imbue', 'on-enter', 'draw-discard', 'condition'], '**Imbue 2**', '**On Enter:** If CARDNAME is **imbued**, draw a card into your memory.')
    ->register();

gaScenario('windmill-engineer-not-imbued-on-enter-draws-nothing')
    ->deckTemplate('quiet', 'Windmill Engineer')
    ->readyFor('Windmill Engineer')
    ->seed('myHand', 'Windmill Engineer')
    ->play('Windmill Engineer')->answer(1, 'NO')->pay(2)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Not imbued')
    ->expectZoneCount('myMemory', 2, 1, 'Only the two paid cards')
    ->tested('Windmill Engineer')
    ->covers(['imbue', 'on-enter', 'condition'], 'If CARDNAME is **imbued**')
    ->register();

// ---- Blessed Clergy ----

gaScenario('blessed-clergy-imbued-class-bonus-on-enter-limits-the-opponent-to-two-cards-next-turn')
    ->deckTemplate('quiet', 'Blessed Clergy')
    ->readyFor('Blessed Clergy', classBonus: true)
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->seed('myHand', 'Blessed Clergy')
    ->play('Blessed Clergy')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Imbued')
    ->expectGlobalEffect(2, 'a3pmmloejo-restrict', 'The opponent is limited to two cards next turn')
    ->tested('Blessed Clergy')
    ->covers(['imbue', 'on-enter', 'class-bonus', 'targeting'], '[Class Bonus] **On Enter:** If CARDNAME is **imbued**, target player can’t play more than two cards during their next turn.')
    ->register();

gaScenario('blessed-clergy-not-imbued-leaves-the-opponent-unrestricted')
    ->deckTemplate('quiet', 'Blessed Clergy')
    ->readyFor('Blessed Clergy', classBonus: true)
    ->seed('myHand', 'Blessed Clergy')
    ->play('Blessed Clergy')->answer(1, 'NO')->pay(2)
    ->expectGlobalEffect(2, 'a3pmmloejo-restrict', 'Not imbued: no restriction', 0)
    ->tested('Blessed Clergy')
    ->covers(['imbue', 'on-enter', 'condition'], 'If CARDNAME is **imbued**')
    ->register();

gaScenario('blessed-clergy-imbued-without-the-class-bonus-leaves-the-opponent-unrestricted')
    ->deckTemplate('quiet', 'Blessed Clergy')
    ->readyFor('Blessed Clergy')
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->seed('myHand', 'Blessed Clergy')
    ->play('Blessed Clergy')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectGlobalEffect(2, 'a3pmmloejo-restrict', 'No class bonus: no restriction', 0)
    ->tested('Blessed Clergy')
    ->covers(['imbue', 'class-bonus', 'condition'], '[Class Bonus] **On Enter:**')
    ->register();

// ---- Eternal Magistrate ----

// The imbued static ("cards can't leave your opponents' material decks unless it's their materialize phase") is not implemented: see ENGINE-FINDINGS.
gaScenario('eternal-magistrate-imbued-is-marked-imbued')
    ->deckTemplate('quiet', 'Eternal Magistrate')
    ->readyFor('Eternal Magistrate')
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->seed('myHand', 'Eternal Magistrate')
    ->play('Eternal Magistrate')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Imbued with two wind cards')
    ->tested('Eternal Magistrate')
    ->covers(['imbue'], '**Imbue 2**')
    ->register();

// ---- Skilled Aerotheurge ----

gaWindRanged('skilled-aerotheurge-ranged-two-while-distant', 'Skilled Aerotheurge', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');

// ---- Shu Frontliner ----

gaScenario('shu-frontliner-equestrian-costs-one-less-with-a-horse-ally')
    ->deckTemplate('quiet', 'Shu Frontliner')
    ->readyFor('Shu Frontliner')
    ->seed('myField', 'Gale\'s Mare')
    ->seed('myHand', 'Shu Frontliner')
    ->play('Shu Frontliner')->pay(1)
    ->expectCardExists('myField', 'Shu Frontliner', 'A Horse ally: the Frontliner cost 2 - 1 = 1')
    ->expectZoneCount('myMemory', 1, 1, 'One payment')
    ->tested('Shu Frontliner')
    ->covers(['cost-reduction', 'condition'], '***Equestrian*** — As long as you control a Horse ally, this card costs 1 less to activate.')
    ->register();

gaScenario('shu-frontliner-without-a-horse-ally-costs-the-printed-two')
    ->deckTemplate('quiet', 'Shu Frontliner')
    ->readyFor('Shu Frontliner')
    ->seed('myHand', 'Shu Frontliner')
    ->play('Shu Frontliner')->pay(1)
    ->expectZoneCount('myField', 1, 1, 'One payment is not enough: the Frontliner has not entered')
    ->tested('Shu Frontliner')
    ->covers(['cost-reduction', 'condition'], 'As long as you control a Horse ally')
    ->register();

// ---- Winbless Forecaster ----

gaScenario('winbless-forecaster-adds-one-to-enlighten-counters-put-on-your-champion')
    ->deckTemplate('quiet', 'Winbless Forecaster')
    ->readyFor('Winbless Forecaster', classBonus: true)
    ->seed('myField', 'Winbless Forecaster')
    ->seed('myField', 'Windrider Mage')
    ->reachTurnThree()
    ->answer(1, 'YES')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"},"enlighten":2}', 'Windrider Mage puts one enlighten counter on the champion; the Forecaster makes it two')
    ->tested('Winbless Forecaster')
    ->covers(['replacement-effect', 'counter'], 'If one or more **enlighten** counters would be put on your champion, that many plus one **enlighten** counters are put on your champion instead.')
    ->register();

// ---- Rousing Slime ----

gaScenario('rousing-slime-class-bonus-on-enter-rests-itself-to-wake-up-another-slime')
    ->deckTemplate('quiet', 'Rousing Slime')
    ->readyFor('Rousing Slime', classBonus: true)
    ->seed('myField', 'Inferno Slime', properties: ['Status' => 1])
    ->seed('myHand', 'Rousing Slime')
    ->play('Rousing Slime')->pay(3)->answer(1, 'YES')
    ->expectProperty('p1Field-2', 'Status', '1', 'The Rousing Slime rested itself')
    ->expectProperty('p1Field-1', 'Status', '2', 'The other Slime woke up')
    ->tested('Rousing Slime')
    ->covers(['on-enter', 'class-bonus', 'status', 'optional-cost'], '[Class Bonus] **On Enter:** You may rest CARDNAME. If you do, wake up another Slime ally you control.')
    ->register();

gaScenario('rousing-slime-class-bonus-on-enter-declined-wakes-nothing')
    ->deckTemplate('quiet', 'Rousing Slime')
    ->readyFor('Rousing Slime', classBonus: true)
    ->seed('myField', 'Inferno Slime', properties: ['Status' => 1])
    ->seed('myHand', 'Rousing Slime')
    ->play('Rousing Slime')->pay(3)->answer(1, 'NO')
    ->expectProperty('p1Field-2', 'Status', '2', 'Declined: the Rousing Slime stays awake')
    ->expectProperty('p1Field-1', 'Status', '1', 'The other Slime stays rested')
    ->tested('Rousing Slime')
    ->covers(['on-enter', 'class-bonus', 'optional-cost'], 'You may rest CARDNAME.')
    ->register();

// ---- Bushwhack Bandit ----

gaScenario('bushwhack-bandit-class-bonus-on-enter-empowers-two-and-buffs-another-ally')
    ->deckTemplate('quiet', 'Bushwhack Bandit')
    ->readyFor('Bushwhack Bandit', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Bushwhack Bandit')
    ->play('Bushwhack Bandit')->pay(3)
    ->answer(1, 'myField-1')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","kT8CeTFj82","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2 on the champion')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Whenever you empower: a buff counter on another ally')
    ->tested('Bushwhack Bandit')
    ->covers(['on-enter', 'class-bonus', 'empower', 'trigger', 'counter'], '[Class Bonus] **On Enter:** **Empower 2**.', '[Class Bonus] Whenever you **empower**, put a **buff** counter on another target ally.')
    ->register();

gaScenario('bushwhack-bandit-without-the-class-bonus-does-not-empower')
    ->deckTemplate('quiet', 'Bushwhack Bandit')
    ->readyFor('Bushwhack Bandit')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Bushwhack Bandit')
    ->play('Bushwhack Bandit')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No class bonus: no empower, no counter')
    ->tested('Bushwhack Bandit')
    ->covers(['on-enter', 'class-bonus', 'empower', 'condition'], '[Class Bonus] **On Enter:** **Empower 2**.')
    ->register();
