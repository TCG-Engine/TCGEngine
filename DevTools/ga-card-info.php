<?php
/**
 * Print the card data a fixture author needs: id, element, type, classes, subtypes, stats and the effect text.
 *
 *   php DevTools/ga-card-info.php "Thunderclap" "#0xm513tj3j" ...
 *
 * A name that matches more than one printing prints every match (use '#<id>' in a scenario to pick one).
 */
chdir(dirname(__DIR__));
require 'GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';

$ids = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '#')) { $ids[] = substr($arg, 1); continue; }
    $found = false;
    foreach (GaCardIdsForName($arg) as $id) { $ids[] = $id; $found = true; }
    if (!$found) fwrite(STDERR, "No card named '{$arg}'\n");
}
foreach ($ids as $id) {
    echo CardName($id), ' (', $id, ")\n";
    echo '  ', implode(' | ', array_filter([
        'element ' . CardElement($id), 'type ' . CardType($id), 'classes ' . CardClasses($id), 'subtypes ' . CardSubtypes($id),
        'reserve ' . CardCost_reserve($id), 'memory ' . CardCost_memory($id), 'level ' . CardLevel($id), 'power ' . CardPower($id), 'life ' . CardLife($id), 'speed ' . CardSpeed($id),
    ])), "\n";
    echo '  ', str_replace("\n", "\n  ", trim(strval(CardEffect($id)))), "\n";
}

function GaCardIdsForName($name) {
    global $nameData;
    $ids = [];
    foreach ($nameData as $id => $cardName) {
        if (strcasecmp($cardName, $name) === 0) $ids[] = $id;
    }
    return $ids;
}
