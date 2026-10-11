<?php
/**
 * One-off data fix: Cleansing Reunion (xpnjvt9y59, Lorraine Pantheon Starter, ACTION card)
 * had its ability authored in the CardEditor ability database under the "Enter" macro (the
 * table for a permanent's on-enter trigger, GeneratedCode/GeneratedMacroCode.php's
 * $enterAbilities). OnCardActivated()'s unconditional final dispatch
 * (GrandArchiveSim/Custom/GameLogic.php ~5365) only ever calls
 * $cardActivatedAbilities[$obj->CardID . ":0"] regardless of card type, so an ACTION card's
 * effect must be authored under the "CardActivated" macro to ever run. 755 other ACTION cards
 * in this database already use "CardActivated"; only a small minority (including this one) were
 * misclassified under "Enter" at authoring time -- this script corrects this one card's row.
 *
 * Usage: php DevTools/fix-cleansing-reunion-macro-classification.php
 * Then regenerate: php zzGameCodeGenerator.php rootName=GrandArchiveSim
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/CardEditor/Database/CardAbilityRepository.php';

$rootName = 'GrandArchiveSim';
$cardId = 'xpnjvt9y59';

$repo = OpenCardAbilityRepository($rootName);
$abilities = $repo->loadCardAbilities($rootName, $cardId);

$fixed = 0;
foreach ($abilities as $row) {
    if ($row['macro_name'] !== 'Enter') continue;
    $ok = $repo->saveAbility(
        (int)$row['id'],
        $rootName,
        $cardId,
        'CardActivated',
        $row['ability_code'],
        $row['prereq_code'],
        $row['ability_name'],
        (int)$row['is_implemented'],
        $row['ability_type'],
        $row['listener_zones']
    );
    if ($ok === false) {
        fwrite(STDERR, "Failed to update ability id {$row['id']} for $cardId\n");
        exit(1);
    }
    echo "Corrected ability id {$row['id']} for $cardId: macro_name 'Enter' -> 'CardActivated'\n";
    $fixed++;
}

if ($fixed === 0) {
    echo "No 'Enter'-classified ability found for $cardId (already fixed, or nothing to do).\n";
}

if (method_exists($repo, 'close')) $repo->close();
