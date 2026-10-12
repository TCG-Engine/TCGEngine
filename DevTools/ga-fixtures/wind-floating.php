<?php
/**
 * Phase 3b (Wind): Floating Memory, exercised with the gaFloatingPays() shape helper (Scenario.php): the champion level-up's 1-memory cost
 * is paid by banishing the card from the graveyard. Class-bonus cards also get the twin with a champion of another class.
 */

$gaWindFloatingClause = '**Floating Memory** *(While paying for a memory cost, you may banish this card from your graveyard to pay for 1 of that cost.)*';
$gaWindFloatingBonus = '[Class Bonus] ' . $gaWindFloatingClause;

// [card, printed clause, bonus class (null = no class bonus on the clause), a class that does NOT match]
foreach ([
    ['Galed Erasure', $gaWindFloatingClause, null, null],
    ['Welcome Merriment', $gaWindFloatingClause, null, null],
    ['Repelling Palmblast', $gaWindFloatingClause, null, null],
    ['Lunge of Evoking Winds', '**Floating Memory**', null, null],
    ['Channel the Wind', $gaWindFloatingBonus, 'MAGE', 'WARRIOR'],
    ['Evanescent Winds', $gaWindFloatingBonus, 'CLERIC', 'WARRIOR'],
    ['Infusion of Crescent Jade', $gaWindFloatingBonus, 'WARRIOR', 'MAGE'],
    ['Updraft Slice', $gaWindFloatingBonus, 'WARRIOR', 'MAGE'],
    ['Cell Vanguard', $gaWindFloatingBonus, 'WARRIOR', 'MAGE'],
    ['Shu Frontliner', $gaWindFloatingBonus, 'WARRIOR', 'MAGE'],
    ['Winbless Rangefinder', $gaWindFloatingBonus, 'RANGER', 'WARRIOR'],
    ['Menagerie Beastbonder', $gaWindFloatingBonus, 'TAMER', 'WARRIOR'],
    ['Ensnaring Fumes', '[Class Bonus] **Floating Memory**', 'ASSASSIN', 'WARRIOR'],
    ['Skilled Aerotheurge', '[Class Bonus] **Floating Memory**', 'RANGER', 'WARRIOR'],
] as [$gaCard, $gaClause, $gaClass, $gaOther]) {
    $gaSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($gaCard)), '-');
    if ($gaClass === null) {
        gaFloatingPays("$gaSlug-floating-memory-pays-a-memory-cost", $gaCard, $gaClause);
    } else {
        gaFloatingPays("$gaSlug-class-bonus-floating-memory-pays-a-memory-cost", $gaCard, $gaClause, $gaClass);
        gaFloatingPays("$gaSlug-without-the-class-bonus-cannot-pay-a-memory-cost", $gaCard, $gaClause, $gaOther, false);
    }
}
