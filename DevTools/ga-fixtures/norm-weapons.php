<?php
/**
 * Phase 3d (Norm): weapon regalia. A weapon's computed POWER is observed on the seeded weapon (champion p1Field-0, weapon p1Field-1). 'quiet' deck.
 */

if (!function_exists('gaNormWeaponPower')) {
    // $level/$classBonus select the champion; $counters are put on the weapon.
    function gaNormWeaponPower(string $slug, string $weapon, int $expected, string $label, array $mechanics, string $clause, bool $classBonus = false, ?int $level = null, array $weaponProps = [], array $extra = []): void {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor($weapon, classBonus: $classBonus, level: $level)->seed('myField', $weapon, properties: $weaponProps);
        foreach ($extra as [$zone, $card]) $s->seed($zone, $card);
        $s->observe()->expectPower('p1Field-1', $expected, $label)->tested($weapon)->covers($mechanics, $clause)->register();
    }
}

gaNormWeaponPower('crescent-glaive-class-bonus-level-two-gets-plus-one-power', 'Crescent Glaive', 2, '[Class Bonus] [Level 2+] 1 + 1 power', ['class-bonus', 'level-gate', 'stat-change'], '[Class Bonus] [Level 2+] CARDNAME gets +1 [POWER].', true, 2);
gaNormWeaponPower('crescent-glaive-below-level-two-has-its-printed-power', 'Crescent Glaive', 1, 'Level 1: the printed 1 power', ['class-bonus', 'level-gate', 'condition'], '[Class Bonus] [Level 2+] CARDNAME gets +1 [POWER].', true, 1);
gaNormWeaponPower('defenders-maul-class-bonus-level-two-gets-plus-two-power', 'Defender\'s Maul', 4, '[Class Bonus] [Level 2+] 2 + 2 power', ['class-bonus', 'level-gate', 'stat-change'], '[Class Bonus] [Level 2+] CARDNAME gets +2 [POWER].', true, 2);
gaNormWeaponPower('defenders-maul-without-the-class-bonus-has-its-printed-power', 'Defender\'s Maul', 2, 'No class bonus: the printed 2 power', ['class-bonus', 'condition'], '[Class Bonus] [Level 2+] CARDNAME gets +2 [POWER].', false, 2);
gaNormWeaponPower('emergent-dagger-class-bonus-level-two-gets-plus-two-power', 'Emergent Dagger', 3, '[Class Bonus] [Level 2+] 1 + 2 power', ['class-bonus', 'level-gate', 'stat-change'], '[Class Bonus] [Level 2+] CARDNAME gets +2 [POWER].', true, 2);
gaNormWeaponPower('emergent-dagger-below-level-two-has-its-printed-power', 'Emergent Dagger', 1, 'Level 1: the printed 1 power', ['class-bonus', 'level-gate', 'condition'], '[Class Bonus] [Level 2+] CARDNAME gets +2 [POWER].', true, 1);
gaNormWeaponPower('sword-of-adversity-class-bonus-without-allies-gets-plus-one-power', 'Sword of Adversity', 2, '[Class Bonus] No allies: 1 + 1 power', ['class-bonus', 'condition', 'stat-change'], '[Class Bonus] CARDNAME gets +1 [POWER] as long as you control no allies.', true);
gaNormWeaponPower('sword-of-adversity-with-an-ally-has-its-printed-power', 'Sword of Adversity', 1, 'An ally is controlled: the printed 1 power', ['class-bonus', 'condition'], '[Class Bonus] CARDNAME gets +1 [POWER] as long as you control no allies.', true, null, [], [['myField', 'Dungeon Guide']]);
gaNormWeaponPower('sealed-blade-class-bonus-gets-plus-one-power', 'Sealed Blade', 3, '[Class Bonus] 2 + 1 power', ['class-bonus', 'stat-change'], '[Class Bonus] CARDNAME gets +1 [POWER].', true);
gaNormWeaponPower('sword-saints-vow-class-bonus-gets-plus-one-power-per-durability-counter', 'Sword Saint\'s Vow', 3, '[Class Bonus] Three durability counters: 0 + 3 power', ['class-bonus', 'counter', 'stat-change'], '[Class Bonus] Sword Saint\'s Vow gets +1 [POWER] for each **durability** counter on it.', true, null, ['Counters' => ['durability' => 3]]);
gaNormWeaponPower('galatine-class-bonus-gets-plus-one-power-per-three-durability-counters', 'Galatine, Sword of Sunlight', 3, '[Class Bonus] Six durability counters: 1 + 2 power', ['class-bonus', 'counter', 'stat-change'], '[Class Bonus] Galatine gets +1 [POWER] for every three **durability** counters on it.', true, null, ['Counters' => ['durability' => 6]]);
gaNormWeaponPower('galatine-without-the-class-bonus-has-its-printed-power', 'Galatine, Sword of Sunlight', 1, 'No class bonus: the printed 1 power', ['class-bonus', 'condition'], '[Class Bonus] Galatine gets +1 [POWER] for every three **durability** counters on it.', false, null, ['Counters' => ['durability' => 6]]);
gaNormWeaponPower('training-sword-has-its-printed-power', 'Training Sword', 1, 'The printed 1 power', ['vanilla'], 'Training Sword (no rules text)');

// ---- Naga's Fang / Assassin's Ripper: remove a preparation counter for +power ----

gaScenario('nagas-fang-removing-a-preparation-counter-gets-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Naga\'s Fang')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myField', 'Naga\'s Fang')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectPower('p1Field-1', 2, 'Removed a preparation counter: 1 + 1 power until end of turn')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The preparation counter was removed')
    ->tested('Naga\'s Fang')
    ->covers(['activated-ability', 'counter', 'stat-change'], '**Remove a preparation counter from your champion:** CARDNAME gets +1 [POWER] until end of turn.')
    ->register();

gaScenario('assassins-ripper-class-bonus-rest-and-a-preparation-counter-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Assassin\'s Ripper', classBonus: true)
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => 1]])
    ->seed('myField', 'Assassin\'s Ripper')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectPower('p1Field-1', 3, '[Class Bonus] 1 + 2 power until end of turn')
    ->tested('Assassin\'s Ripper')
    ->covers(['class-bonus', 'activated-ability', 'counter', 'stat-change', 'rest'], '[Class Bonus] [REST]**, Remove a preparation counter from your champion:** CARDNAME gets +2 [POWER] until end of turn.')
    ->register();

gaScenario('nagas-fang-can-be-activated-again-for-another-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Naga\'s Fang')
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myField', 'Naga\'s Fang')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectPower('p1Field-1', 3, 'Two preparation counters removed: 1 + 1 + 1 power')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Both preparation counters were removed')
    ->tested('Naga\'s Fang')
    ->covers(['activated-ability', 'counter', 'stat-change'], '**Remove a preparation counter from your champion:** CARDNAME gets +1 [POWER] until end of turn.')
    ->register();
