<?php
/**
 * Phase 3d (Norm): allies, second batch (combat keywords: intercept, retort, steadfast, ambush, stealth, true sight). 'quiet' deck.
 */

// A defender retaliating against player 2's Dungeon Guide (1 power, 3 life): $damage is the damage the attacker takes back.
if (!function_exists('gaNormRetort')) {
    function gaNormRetort(string $slug, string $ally, int $damage, string $label, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'retort', 'combat'], array $properties = [], array $extraSeeds = []): void {
        $s = gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus)
            ->seed('myField', $ally, properties: $properties);
        foreach ($extraSeeds as [$zone, $card]) $s->seed($zone, $card);
        $s->seed('myField', 'Dungeon Guide', player: 2)
            ->endTurn()
            ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
            ->expectDamage('p2Field-1', $damage, $label)
            ->tested($ally)
            ->covers($mechanics, $clause)
            ->register();
    }
}

// Intercept: player 2's Dungeon Guide attacks the champion and the awake ally takes the attack instead.
if (!function_exists('gaNormIntercept')) {
    function gaNormIntercept(string $slug, string $ally, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'intercept', 'combat'], array $extraSeeds = []): void {
        $s = gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus)
            ->seed('myField', $ally);
        foreach ($extraSeeds as [$zone, $card]) $s->seed($zone, $card);
        $s->seed('myField', 'Dungeon Guide', player: 2)
            ->endTurn()
            ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myField-1')->answer(1, '-')
            ->expectDamage('p1Field-0', 0, 'The champion takes no damage: the attack was redirected')
            ->expectDamage('p1Field-1', 1, 'The interceptor takes the redirected 1 damage')
            ->tested($ally)
            ->covers($mechanics, $clause)
            ->register();
    }
}

// ---- Fatebound Caracal (both printings) ----

gaNormIntercept('fatebound-caracal-intercept-redirects-the-attack-on-the-champion', 'Fatebound Caracal', '**Intercept** *(Whenever your champion is attacked while this ally is awake, you may redirect that attack to this ally.)*');
gaNormRetort('fatebound-caracal-retort-two-retaliates-for-two-extra-power', 'Fatebound Caracal', 3, 'Retaliating with 1 + 2 power', '**Retort 2** *(As long as this ally is retaliating, it gets +2 [POWER].)*');
gaNormIntercept('fatebound-caracal-second-printing-intercept-redirects-the-attack-on-the-champion', '#x8wc254ris', '**Intercept** *(Whenever your champion is attacked while this ally is awake, you may redirect that attack to this ally.)*');
gaNormRetort('fatebound-caracal-second-printing-retort-two-retaliates-for-two-extra-power', '#x8wc254ris', 3, 'Retaliating with 1 + 2 power', '**Retort 2** *(As long as this ally is retaliating, it gets +2 [POWER].)*');

// ---- Brash Defender ----

gaNormRetort('brash-defender-retort-three-retaliates-for-three-extra-power', 'Brash Defender', 4, 'Retaliating with 1 + 3 power', '**Retort 3** *(As long as this ally is retaliating, it gets +3 [POWER].)*');

gaScenario('brash-defender-level-one-vigor-wakes-it-at-the-end-of-the-turn')
    ->deckTemplate('quiet', 'Brash Defender')
    ->readyFor('Brash Defender', level: 1)
    ->seed('myField', 'Brash Defender', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'Level 1: vigor woke the Defender at the end phase')
    ->tested('Brash Defender')
    ->covers(['level-gate', 'vigor'], '[Level 1+] **Vigor** *(This unit wakes up at the beginning of your end phase. Apply this effect only if your champion is level 1 or higher.)*')
    ->register();

gaScenario('brash-defender-below-level-one-stays-rested')
    ->deckTemplate('quiet', 'Brash Defender')
    ->readyFor('Brash Defender', level: 0)
    ->seed('myField', 'Brash Defender', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '1', 'Level 0: no vigor, the Defender stays rested')
    ->tested('Brash Defender')
    ->covers(['level-gate', 'vigor'], '[Level 1+] **Vigor**')
    ->register();

// ---- Pride's Vanguard ----

gaNormRetort('prides-vanguard-retort-two-retaliates-for-two-extra-power', 'Pride\'s Vanguard', 3, 'Retaliating with 1 + 2 power', '**Retort 2** *(As long as this ally is retaliating, it gets +2[POWER].)*');

gaScenario('prides-vanguard-ambush-retaliates-for-another-ally-with-retort')
    ->deckTemplate('quiet', 'Pride\'s Vanguard')
    ->readyFor('Pride\'s Vanguard')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Pride\'s Vanguard')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-2')
    ->expectDamage('p2Field-1', 3, 'The ambusher retaliated for another ally: 1 + 2 retort power')
    ->expectDamage('p1Field-2', 0, 'The Vanguard took no damage: it was never the defender')
    ->tested('Pride\'s Vanguard')
    ->covers(['keyword', 'ambush', 'retaliate'], '**Ambush** *(This ally may retaliate against attackers while not defending.)*')
    ->register();

// ---- Woodland Shoats ----

gaNormRetort('woodland-shoats-steadfast-retaliates-while-rested', 'Woodland Shoats', 3, 'A rested steadfast ally retaliated with its 3 power', '**Steadfast** *(This ally can retaliate while rested and doesn\'t rest to do so.)*', false, ['keyword', 'steadfast', 'retaliate'], ['Status' => 1]);

// ---- Hardy Veteran ----

gaNormIntercept('hardy-veteran-intercept-redirects-the-attack-on-the-champion', 'Hardy Veteran', '**Intercept** *(Whenever your champion is attacked while this ally is awake, you may redirect that attack to this ally.)*');
gaNormRetort('hardy-veteran-class-bonus-with-a-weapon-has-retort-three', 'Hardy Veteran', 4, '[Class Bonus] With a weapon: retaliating with 1 + 3 power', '[Class Bonus] As long as you control a weapon, CARDNAME has **retort 3**.', true, ['class-bonus', 'condition', 'retort', 'combat'], [], [['myField', 'Zephyr\'s Edge']]);
gaNormRetort('hardy-veteran-class-bonus-without-a-weapon-has-no-retort', 'Hardy Veteran', 1, '[Class Bonus] No weapon: retaliating with its printed 1 power', '[Class Bonus] As long as you control a weapon, CARDNAME has **retort 3**.', true, ['class-bonus', 'condition', 'retort']);

// ---- Palace Guard ----

gaNormRetort('palace-guard-class-bonus-retort-two-retaliates-for-two-extra-power', 'Palace Guard', 3, '[Class Bonus] Retaliating with 1 + 2 power', '[Class Bonus] **Retort 2** *(As long as this ally is retaliating, it gets +2 [POWER].)*', true, ['class-bonus', 'retort', 'combat']);
gaNormRetort('palace-guard-without-the-class-bonus-retaliates-for-its-printed-power', 'Palace Guard', 1, 'No class bonus: retaliating with 1 power', '[Class Bonus] **Retort 2**', false, ['class-bonus', 'retort', 'condition']);

// ---- Dreamy Unicorn ----

gaNormIntercept('dreamy-unicorn-intercept-redirects-the-attack-on-the-champion', 'Dreamy Unicorn', '**Intercept** *(Whenever your champion is attacked while this ally is awake, you may redirect that attack to this ally.)*');

gaScenario('dreamy-unicorn-on-death-puts-a-preparation-counter-on-the-champion')
    ->deckTemplate('quiet', 'Dreamy Unicorn')
    ->readyFor('Dreamy Unicorn')
    ->seed('myField', 'Dreamy Unicorn')
    ->seed('myField', 'Rotund Squirrel', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectCardExists('myGraveyard', 'Dreamy Unicorn', 'The Unicorn took 2 damage and died')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Death: a preparation counter went on the champion')
    ->tested('Dreamy Unicorn')
    ->covers(['on-death', 'counter'], '**On Death**: Put a **preparation** counter on your champion.')
    ->register();

// ---- Stealth family ----

if (!function_exists('gaNormStealth')) {
    function gaNormStealth(string $slug, string $ally, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'stealth']): void {
        gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus)
            ->seed('myField', $ally, properties: ['Status' => 2])
            ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
            ->endTurn()->action(2, 10002, 'myField-1!FSM!')
            ->rejects(2, 'theirField-1', 'Stealth: the attack cannot target the ' . $ally)
            ->answer(2, 'theirField-0')
            ->expectDamage('p1Field-1', 0, 'The stealth ally took no damage')
            ->tested($ally)->covers($mechanics, $clause)->register();
    }
}

gaNormStealth('imperial-assassin-class-bonus-stealth-cannot-be-targeted-by-attacks', 'Imperial Assassin', '[Class Bonus] **Stealth** *(This unit can\'t be targeted by attacks unless permitted by **true sight**.)*', true, ['class-bonus', 'keyword', 'stealth']);
gaNormStealth('kingdom-informant-stealth-cannot-be-targeted-by-attacks', 'Kingdom Informant', '**Stealth** *(This unit can\'t be targeted by attacks unless permitted by **true sight**.)*');
gaNormStealth('lurching-rogue-class-bonus-stealth-cannot-be-targeted-by-attacks', 'Lurching Rogue', '[Class Bonus] **Stealth** *(This unit can’t be targeted by attacks unless permitted by** true sight**.)*', true, ['class-bonus', 'keyword', 'stealth']);
gaNormStealth('mourning-veilbound-stealth-cannot-be-targeted-by-attacks', 'Mourning Veilbound', '**Stealth** *(This unit can’t be targeted by attacks unless permitted by **true sight**.)*');
gaNormStealth('proto-archive-scout-awake-has-stealth', 'Proto Archive Scout', 'CARDNAME has **stealth** as long as it’s awake.', false, ['condition', 'keyword', 'stealth']);

gaScenario('proto-archive-scout-rested-has-no-stealth')
    ->deckTemplate('quiet', 'Proto Archive Scout')
    ->readyFor('Proto Archive Scout')
    ->seed('myField', 'Proto Archive Scout', properties: ['Status' => 1])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)
    ->expectDamage('p1Field-1', 1, 'Rested: no stealth, so it could be attacked')
    ->tested('Proto Archive Scout')
    ->covers(['condition', 'keyword', 'stealth'], 'CARDNAME has **stealth** as long as it’s awake.')
    ->register();

// ---- True sight family ----

if (!function_exists('gaNormTrueSight')) {
    function gaNormTrueSight(string $slug, string $ally, string $clause, bool $classBonus = false, ?int $level = null, array $mechanics = ['keyword', 'true-sight', 'stealth', 'combat']): void {
        gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus, level: $level)
            ->seed('myField', $ally)
            ->seed('myField', 'Haze Droid', player: 2)
            ->reachTurnThree()
            ->attack('myField-1', 'theirField-1')->pass(2)
            ->expectDamage('p2Field-1', 2, 'True Sight: the ally could attack the stealth Haze Droid')
            ->tested($ally)->covers($mechanics, $clause)->register();
    }
}

gaNormTrueSight('ghostsight-mare-true-sight-attacks-a-stealth-unit', 'Ghostsight Mare', '**True Sight** *(This ally can attack units with **stealth**.)*');
gaNormTrueSight('gray-lupindroid-true-sight-attacks-a-stealth-unit', 'Gray Lupindroid', '**True Sight** *(This ally can attack units with **stealth**.)*');
gaNormTrueSight('bladed-riftseer-level-one-true-sight-attacks-a-stealth-unit', 'Bladed Riftseer', '[Level 1+] **True Sight**', false, 1, ['level-gate', 'keyword', 'true-sight', 'stealth']);

gaScenario('bladed-riftseer-below-level-one-cannot-attack-a-stealth-unit')
    ->deckTemplate('quiet', 'Bladed Riftseer')
    ->readyFor('Bladed Riftseer', level: 0)
    ->seed('myField', 'Bladed Riftseer')
    ->seed('myField', 'Haze Droid', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->rejects(1, 'theirField-1', 'Level 0: no true sight, the stealth Haze Droid is not a legal target')
    ->answer(1, 'theirField-0')
    ->expectDamage('p2Field-1', 0, 'The Haze Droid was not attacked')
    ->tested('Bladed Riftseer')
    ->covers(['level-gate', 'true-sight', 'condition'], '[Level 1+] **True Sight**')
    ->register();
