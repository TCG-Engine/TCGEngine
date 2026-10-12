<?php
/**
 * Phase 3b (Water): unique allies.
 */

gaScenario('dahlia-idyllic-dreamer-ranged-x-counts-water-cards-in-the-graveyard')
    ->standardDeck('Dahlia, Idyllic Dreamer')
    ->readyFor('Dahlia, Idyllic Dreamer', player: 2)
    ->seed('myGraveyard', 'Giant Tortoise', player: 2)->seed('myGraveyard', 'Giant Tortoise', player: 2)->seed('myGraveyard', 'Giant Tortoise', player: 2)
    ->seed('myField', 'Dahlia, Idyllic Dreamer', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 4, 'Ranged X with 3 water cards while distant: 1 + 3 = 4 damage')
    ->tested('Dahlia, Idyllic Dreamer')
    ->covers(['keyword', 'ranged', 'scaling', 'combat'], 'Dahlia has **ranged X**, where **X** is the amount of water element cards in your graveyard.')
    ->register();

gaScenario('dahlia-idyllic-dreamer-with-no-water-cards-has-no-ranged-bonus')
    ->standardDeck('Dahlia, Idyllic Dreamer')
    ->readyFor('Dahlia, Idyllic Dreamer', player: 2)
    ->seed('myField', 'Dahlia, Idyllic Dreamer', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 1, 'No water cards in the graveyard: Ranged 0, only the printed 1 POWER')
    ->tested('Dahlia, Idyllic Dreamer')
    ->covers(['keyword', 'ranged', 'scaling', 'condition'], 'Dahlia has **ranged X**, where **X** is the amount of water element cards in your graveyard.')
    ->register();

gaScenario('lunete-frostbinder-priest-opponents-allies-enter-rested')
    ->standardDeck('Lunete, Frostbinder Priest')
    ->readyFor('Lunete, Frostbinder Priest')
    ->seed('myField', 'Lunete, Frostbinder Priest')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->play('Dungeon Guide', player: 2)->pay(3, 2)
    ->expectProperty('p2Field-1', 'Status', '1', 'An ally the opponent activated entered the field rested')
    ->tested('Lunete, Frostbinder Priest')
    ->covers(['static-ability', 'enters-rested'], 'Allies your opponents control enter the field rested.')
    ->register();

gaScenario('lunete-frostbinder-priest-your-own-allies-enter-awake')
    ->standardDeck('Lunete, Frostbinder Priest')
    ->readyFor('Lunete, Frostbinder Priest')
    ->seed('myField', 'Lunete, Frostbinder Priest')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)->pass(2)
    ->expectProperty('p1Field-2', 'Status', '2', 'Your own ally entered awake')
    ->tested('Lunete, Frostbinder Priest')
    ->covers(['static-ability', 'enters-rested', 'condition'], 'Allies your opponents control enter the field rested.')
    ->register();

gaScenario('tweedledee-contrarian-poet-class-bonus-taunt-protects-the-champion')
    ->standardDeck('Tweedledee, Contrarian Poet')
    ->readyFor('Tweedledee, Contrarian Poet', classBonus: true)
    ->seed('myField', 'Tweedledee, Contrarian Poet', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while Tweedledee is awake')
    ->tested('Tweedledee, Contrarian Poet')
    ->covers(['taunt', 'class-bonus', 'attack-declaration'], '[Class Bonus] **Taunt**')
    ->register();

gaScenario('tweedledee-contrarian-poet-on-hit-loses-three-power-permanently')
    ->standardDeck('Tweedledee, Contrarian Poet')
    ->readyFor('Tweedledee, Contrarian Poet')
    ->seed('myField', 'Tweedledee, Contrarian Poet')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 3, 'It hit for its printed 3 POWER')
    ->expectProperty('p1Field-1', 'Counters', '{"power_loss":3}', 'On Hit: it lost 3 POWER indefinitely')
    ->tested('Tweedledee, Contrarian Poet')
    ->covers(['on-hit', 'stat-change', 'permanent'], '**On Hit:** CARDNAME gets -3 [POWER]. (This effect lasts indefinitely.)')
    ->register();

gaScenario('xia-hou-dun-ranged-two-while-distant')
    ->standardDeck('Xia Hou Dun, Gloryseeker')
    ->readyFor('Xia Hou Dun, Gloryseeker', player: 2)
    ->seed('myField', 'Xia Hou Dun, Gloryseeker', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage')
    ->tested('Xia Hou Dun, Gloryseeker')
    ->covers(['keyword', 'ranged', 'combat'], '**Ranged 2**')
    ->register();

gaScenario('xia-hou-dun-class-bonus-with-a-sword-gets-plus-one-power')
    ->standardDeck('Xia Hou Dun, Gloryseeker')
    ->readyFor('Xia Hou Dun, Gloryseeker', classBonus: true, player: 2)
    ->seed('myField', 'Warrior\'s Longsword', player: 2)
    ->seed('myField', 'Xia Hou Dun, Gloryseeker', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-2', 'theirField-0', player: 2)->pass(2)->pass(1)
    ->expectDamage('p1Field-0', 4, '[Class Bonus] A Sword weapon: 1 + 1 + 2 ranged = 4 damage')
    ->tested('Xia Hou Dun, Gloryseeker')
    ->covers(['static-ability', 'class-bonus', 'weapon-condition', 'stat-change'], '[Class Bonus] As long as you control a Sword or Bow weapon, CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('zhang-he-cloak-of-night-class-bonus-enter-gives-another-ally-stealth')
    ->standardDeck('Zhang He, Cloak of Night')
    ->readyFor('Zhang He, Cloak of Night', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Zhang He, Cloak of Night')
    ->castSpell('Zhang He, Cloak of Night', pay: 3, window: 'opp', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"zhangHeStealth":true}', '[Class Bonus] The other ally gained stealth for as long as Zhang He stays')
    ->tested('Zhang He, Cloak of Night')
    ->covers(['on-enter', 'class-bonus', 'stealth', 'targeting'], '[Class Bonus] **On Enter**: Another target ally you control gains **stealth** for as long as you control Zhang He.')
    ->register();

gaScenario('bleu-ace-of-diamonds-total-suited-reserve-cost-six-generates-one-bolt')
    ->standardDeck('Bleu, Ace of Diamonds')
    ->readyFor('Bleu, Ace of Diamonds')
    ->seed('myField', 'Five of Hearts')
    ->seed('myHand', 'Bleu, Ace of Diamonds')
    ->castSpell('Bleu, Ace of Diamonds', pay: 1)->pass(1)->pass(2)
    ->expectCardExists('myMemory', 'Bolt of Diamonds', 'Total Suited reserve cost 5 + 1 = 6: X is 1, so one Bolt of Diamonds was generated into memory')
    ->tested('Bleu, Ace of Diamonds')
    ->covers(['on-enter', 'threshold', 'generate'], '**On Enter:** Generate up to **X** Bolt of Diamonds cards and put them into your memory. **X** is 0. Depending on the total reserve cost of Suited objects you control -- 6: **X** is 1 instead.')
    ->register();

gaScenario('snow-white-weiss-queen-level-one-enter-rests-a-champion')
    ->standardDeck('Snow White, Weiss Queen')
    ->readyFor('Snow White, Weiss Queen', level: 1)
    ->seed('myHand', 'Snow White, Weiss Queen')
    ->castSpell('Snow White, Weiss Queen', pay: 3, window: 'opp', target: 'theirField-0')
    ->expectProperty('p2Field-0', 'Status', '1', '[Level 1+] On Enter: the target champion is rested')
    ->expectProperty('p2Field-0', 'TurnEffects', '["SKIP_WAKEUP"]', '...and does not wake up during its controller\'s next wake up phase')
    ->tested('Snow White, Weiss Queen')
    ->covers(['on-enter', 'level', 'rest', 'wake-up'], '[Level 1+] **On Enter**: Rest target champion. It doesn\'t wake up during its controller\'s next wake up phase.')
    ->register();
