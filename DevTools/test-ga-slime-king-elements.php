<?php
// Slime King (f0ymeslfpw) "three Slime ally cards each with different elements": every card in the selection needs an element
// no other selected card is counted for. Exalted cards carry two elements ("EXALTED,FIRE"), so overlapping sets are exercised here
// with real card IDs (no printed Slime is Exalted).
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$checks = 0;
function slimeCheck($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
    echo "PASS $label\n";
}
$fire = 'mttsvbgl6f';       // Red Slime, FIRE
$arcane = 'blqryebvwj';     // Storm Slime, ARCANE
$luxem = 'ejvddohjdu';      // Lustrous Slime, LUXEM
$exFire = 'HPDawzCDdr';     // EXALTED,FIRE
slimeCheck(GAElementsOfCard($exFire) === ['EXALTED', 'FIRE'], 'Exalted Fire card has both elements');
slimeCheck(GAElementsOfCard($fire) === ['FIRE'], 'plain Fire card has one element');
slimeCheck(GADistinctElementSelectionPossible([], [$fire, $arcane, $luxem], 3), 'three single-element cards with different elements are legal');
slimeCheck(!GADistinctElementSelectionPossible([], [$fire, $fire, $arcane], 3), 'two identical Fire cards plus Arcane are not legal');
slimeCheck(GADistinctElementSelectionPossible([], [$fire, $exFire, $arcane], 3), 'Fire + Exalted/Fire + Arcane is legal (Exalted/Fire is counted as Exalted)');
slimeCheck(!GADistinctElementSelectionPossible([], [$exFire, $exFire, $fire], 3), 'two Exalted/Fire plus Fire is not legal');
slimeCheck(!GADistinctElementSelectionPossible([$fire, $arcane], [$fire], 1), 'a second Fire card cannot be the third pick after Fire + Arcane');
slimeCheck(GADistinctElementSelectionPossible([$fire, $arcane], [$exFire], 1), 'Exalted/Fire is a legal third pick after Fire + Arcane');
slimeCheck(GADistinctElementSelectionPossible([$exFire, $fire], [$arcane], 1), 'Exalted/Fire + Fire + Arcane is legal in any order');
slimeCheck(GAMaxDistinctElementCount([$fire, $fire, $arcane, $luxem]) === 3, 'Slime Party count: duplicate Fire counts once');
slimeCheck(GAMaxDistinctElementCount([$exFire, $exFire]) === 2, 'Slime Party count: two Exalted/Fire cards give Exalted and Fire (2)');
slimeCheck(GAMaxDistinctElementCount([$exFire, $fire]) === 2, 'Slime Party count: Fire and Exalted/Fire is 2');
slimeCheck(GAMaxDistinctElementCount([$exFire, $exFire, $fire]) === 2, 'Slime Party count: three cards sharing two elements is 2');
slimeCheck(GAMaxDistinctElementCount([]) === 0, 'Slime Party count: no Slimes is 0');
echo "ALL $checks CHECKS PASSED\n";
