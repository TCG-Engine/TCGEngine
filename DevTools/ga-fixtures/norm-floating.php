<?php
/**
 * Phase 3d (Norm): Floating Memory on every Norm card that prints it, exercised with gaFloatingPays() (Scenario.php). Class Bonus cards also get the no-bonus twin.
 */

$gaNormFloatingPlain = '**Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost.)*';
$gaNormFloatingBonus = '[Class Bonus] ' . $gaNormFloatingPlain;

// [card, printed clause, bonus class (null = no class bonus on the clause), a class that does NOT match]
foreach ([
    ['Combat Training', $gaNormFloatingBonus, 'WARRIOR', 'MAGE'],
    ['Fortification', '[Class Bonus] **Floating Memory**', 'GUARDIAN', 'WARRIOR'],
    ['Equip with Courage', $gaNormFloatingPlain, null, null],
    ['Exorcise Curses', $gaNormFloatingPlain, null, null],
    ['Feed Nourishment', $gaNormFloatingPlain, null, null],
    ['Manifest Threat', '**Floating Memory**', null, null],
    ['Reprogram', '**Floating Memory**', null, null],
    ['Right of Realm', $gaNormFloatingPlain, null, null],
    ['Summon Pawn', $gaNormFloatingPlain, null, null],
    ['Tune Up', $gaNormFloatingPlain, null, null],
    ['Ameliorating Mantra', $gaNormFloatingPlain, null, null],
    ['Deploy Gunshield', $gaNormFloatingPlain, null, null],
    ['Fast Cure', '**Floating Memory**', null, null],
    ['Floral Arrangement', $gaNormFloatingPlain, null, null],
    ['Incantation of Prosperity', $gaNormFloatingPlain, null, null],
    ['Leading Charge', $gaNormFloatingBonus, 'WARRIOR', 'MAGE'],
    ['March On', $gaNormFloatingPlain, null, null],
    ['Organize the Alliance', $gaNormFloatingBonus, 'GUARDIAN', 'WARRIOR'],
    ['Savage Arrow', '**Floating Memory**', null, null],
    ['Seal the Past', $gaNormFloatingPlain, null, null],
    ['Angelic Channeling', '**Floating Memory**', null, null],
    ['Harness Mana', $gaNormFloatingBonus, 'MAGE', 'WARRIOR'],
    ['Maiden of Shimmering Air', $gaNormFloatingPlain, null, null],
    ['Ordinary Bear', $gaNormFloatingBonus, 'TAMER', 'WARRIOR'],
    ['Ordinary Horse', $gaNormFloatingPlain, null, null],
    ['Raze the Land', '**Floating Memory**', null, null],
    ['Five of Spades', '**Floating Memory**', null, null],
    ['Hefty Hammering', '[Class Bonus] **Floating Memory**', 'WARRIOR', 'MAGE'],
    ['Navigate the Streets', '**Floating Memory**', null, null],
    ['Unwelcome Fortune', '**Floating Memory**', null, null],
] as [$gaCard, $gaClause, $gaClass, $gaOther]) {
    $gaSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($gaCard)), '-');
    if ($gaClass === null) {
        gaFloatingPays("$gaSlug-floating-memory-pays-a-memory-cost", $gaCard, $gaClause);
    } else {
        gaFloatingPays("$gaSlug-class-bonus-floating-memory-pays-a-memory-cost", $gaCard, $gaClause, $gaClass);
        gaFloatingPays("$gaSlug-without-the-class-bonus-cannot-pay-a-memory-cost", $gaCard, $gaClause, $gaOther, false);
    }
}
