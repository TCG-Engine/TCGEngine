<?php
/**
 * One-off data fix: Strike of Singularity (AMv1u54B2s, Zander Pantheon Starter, ATTACK card)
 * has an "OnAttack" ability in the CardEditor ability database whose code calls
 * AddTurnEffect($mzID, "soO3hjaVfN_DOUBLE") -- "soO3hjaVfN" is Rending Flames' CardID, not
 * Strike of Singularity's own. This looks like the ability was authored by copy-pasting Rending
 * Flames' own damage-doubling clause without updating the tag string.
 *
 * GrandArchiveSim/Custom/CombatLogic.php's consumer (formerly AttackHasRendingFlamesDouble(),
 * renamed to the generic AttackHasDamageDoubleEffect()) now checks for a TurnEffect of the form
 * "{CardID}_DOUBLE" -- self-referential, matching the pattern every other card's own closure
 * produces -- in TWO places: the intent card itself (how Rending Flames' own Custom-authored
 * soO3hjaVfN:0 closure tags it, by explicitly resolving the real intent-card mzID), AND the
 * attacking unit's own field object (because this card's generated closure calls
 * AddTurnEffect($mzID, ...) using the ambient "mzID" DQ variable, which for an ATTACK card played
 * from hand is documented (OnAttackTrigger()'s own comment) to be the ATTACKING UNIT's field mzID,
 * not the intent card -- confirmed live: the tag lands on the champion, not on the Strike of
 * Singularity intent object). Given that, this DB fix only needs to correct the tag STRING (the
 * consumer already checks the location this AddTurnEffect call actually writes to); it does not
 * need to change which object the generated closure tags. For Strike of Singularity to ever
 * trigger its printed damage-doubling clause, its own AddTurnEffect call must tag
 * "AMv1u54B2s_DOUBLE" instead of "soO3hjaVfN_DOUBLE".
 *
 * This script corrects the ability_code text for Strike of Singularity's OnAttack row. It requires
 * a reachable CardEditor ability database (local MySQL or a configured remote CardCodeService) --
 * neither was reachable in the sandbox this fix was developed in, so this script is committed
 * un-run. Whoever has DB access should run it, then regenerate:
 *   php DevTools/fix-strike-of-singularity-double-tag.php
 *   php zzGameCodeGenerator.php rootName=GrandArchiveSim
 * and confirm (re-read the row, don't just trust a truthy save) that GeneratedCode/GeneratedMacroCode.php's
 * onAttackAbilities["AMv1u54B2s:0"] now calls AddTurnEffect($mzID, "AMv1u54B2s_DOUBLE").
 *
 * Usage: php DevTools/fix-strike-of-singularity-double-tag.php
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/CardEditor/Database/CardAbilityRepository.php';

$rootName = 'GrandArchiveSim';
$cardId = 'AMv1u54B2s';
$from = 'soO3hjaVfN_DOUBLE';
$to = 'AMv1u54B2s_DOUBLE';

$repo = OpenCardAbilityRepository($rootName);
$abilities = $repo->loadCardAbilities($rootName, $cardId);

$fixed = 0;
foreach ($abilities as $row) {
    if (strpos((string)$row['ability_code'], $from) === false) continue;
    $newCode = str_replace($from, $to, (string)$row['ability_code']);
    $ok = $repo->saveAbility(
        (int)$row['id'],
        $rootName,
        $cardId,
        $row['macro_name'],
        $newCode,
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
    echo "Corrected ability id {$row['id']} for $cardId: '$from' -> '$to'\n";
    $fixed++;
}

if ($fixed === 0) {
    echo "No ability row containing '$from' found for $cardId (already fixed, or nothing to do).\n";
} else {
    // Confirm the write actually persisted rather than trusting saveAbility()'s return value.
    $reloaded = $repo->loadCardAbilities($rootName, $cardId);
    $verified = 0;
    foreach ($reloaded as $row) {
        if (strpos((string)$row['ability_code'], $to) !== false) $verified++;
    }
    if ($verified < $fixed) {
        fwrite(STDERR, "Warning: re-read after save did not show the expected number of corrected rows ($verified/$fixed). Verify manually.\n");
        exit(1);
    }
    echo "Verified: re-read of $cardId's abilities shows $verified row(s) with the corrected tag.\n";
}

if (method_exists($repo, 'close')) $repo->close();
