<?php
/**
 * One-off data fix: authors the missing CardEditor ability database rows for the last three
 * Lorraine Pantheon Starter cards that had ZERO ability entries anywhere (confirmed via
 * exhaustive grep of GeneratedCode/GeneratedMacroCode.php and Custom/*.php before this fix):
 *
 *   - Charm of Anticipation (vkL2RFh0yM): "Banish CARDNAME: Draw a card. Activate this ability
 *     only if you have the Crowd's Favor status." -> macro "ActivateAbility" (the table
 *     $activateAbilityAbilities/$activateAbilityPrereqs is generated from; ActivateAbility() ->
 *     DoActivatedAbility() reaches it for a field-resident permanent's own activated ability).
 *   - Unity's Gale (uUWsgLmyTk): "Target ally gets +3LIFE until end of turn. At the beginning of
 *     the next end phase, if that ally is damaged and you don't control it, you gain the Crowd's
 *     Favor status." -> macro "CardActivated" (the table OnCardActivated()'s unconditional final
 *     dispatch reads for every activated card regardless of type -- Cleansing Reunion's sibling
 *     fix in this same branch established that ACTION cards must use this macro, not "Enter").
 *
 * No CardEditor ability database (local MySQL or a configured remote CardCodeService) was
 * reachable in this sandbox to author these rows directly and regenerate through the normal
 * pipeline (GeneratedCode/GeneratedMacroCode.php is gitignored and rebuilt from that database).
 * Both cards' abilities were instead hand-authored directly into tracked Custom code
 * (GrandArchiveSim/Custom/GameLogic.php, $activateAbilityAbilities["vkL2RFh0yM:0"] and
 * $cardActivatedAbilities["uUWsgLmyTk:0"]) as a working stand-in -- additive only, since
 * GeneratedMacroCode.php has no competing entries for either key, so nothing is clobbered on
 * regeneration. This script is for whoever has real database access to persist the rows properly.
 *
 * IMPORTANT -- two things this script does NOT and CANNOT cover, and that remain permanently
 * hand-written even after this script runs and the database rows are authored:
 *   1. $CardActivateAbilityCountData["vkL2RFh0yM"] is a wholesale array-literal reassignment in
 *      GeneratedMacroCode.php (not a merge), which loads AFTER Custom/GameLogic.php in
 *      GamestateParser.php's include order -- so DoActivatedAbility()'s $staticAbilityCount
 *      needed a direct one-line patch (see GrandArchiveSim/Custom/GameLogic.php, the "Charm of
 *      Anticipation" comment above the patch) to know this card has 1 static ability. Once this
 *      script's row is regenerated for real, $CardActivateAbilityCountData will contain the
 *      correct entry automatically and that one-line patch becomes redundant and should be
 *      removed (it is explicitly commented as such).
 *   2. Reaping Legacy's (XDVIiIfKZk) "[Class Bonus] gets +1POWER for each Sword regalia weapon
 *      card in your banishment" is a static self-power modifier. This codebase has NO generated
 *      macro table for static power modifiers at all -- every card of this shape (Updraft Slice,
 *      Sealed Blade, Photic Blade, dozens of others) is hand-coded as a per-CardID case inside
 *      ObjectCurrentPower()'s own "Self power modifiers" switch (Custom/GameLogic.php). Reaping
 *      Legacy's case was added there following the exact same established pattern and needs no
 *      CardEditor database row at all -- this is its permanent, correct home regardless of
 *      database access, matching every other static-power-bonus card in the game.
 *
 * Simplification: Unity's Gale's own $customDQHandlers["uUWsgLmyTk:0:Target-1"] continuation
 * (the MZCHOOSE follow-up that actually applies the LIFE buff/delayed marker to the chosen
 * target) is left as hand-written Custom code rather than represented as a second ability row
 * with the DB's own continuation-row convention (e.g. Fluvial Fatestone's
 * customDQHandlers["3h93tgm72l:0:Enter-1"], which IS DB-authored) -- whoever has real database
 * access should fold it into the ActivateAbility/CardActivated rows above using that convention.
 *
 * Usage: php DevTools/fix-lorraine-pantheon-final-three-abilities.php
 * Then regenerate: php zzGameCodeGenerator.php rootName=GrandArchiveSim
 */

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/CardEditor/Database/CardAbilityRepository.php';

$rootName = 'GrandArchiveSim';

$repo = OpenCardAbilityRepository($rootName);

function AuthorCardAbility($repo, $rootName, $cardId, $macroName, $abilityCode, $prereqCode, $abilityName) {
    $existing = $repo->loadCardAbilities($rootName, $cardId);
    if (!empty($existing)) {
        echo "Skipping $cardId ($abilityName): already has " . count($existing) . " ability row(s) -- not overwriting.\n";
        return;
    }
    $result = $repo->replaceCardAbilities($rootName, $cardId, [[
        'macroName' => $macroName,
        'abilityCode' => $abilityCode,
        'prereqCode' => $prereqCode,
        'abilityName' => $abilityName,
        'isImplemented' => 1,
        'abilityType' => 'macro',
    ]], true, null);
    echo "Authored $cardId ($abilityName) under macro '$macroName'; revision {$result['revision']}\n";
}

// Charm of Anticipation (vkL2RFh0yM): Banish CARDNAME, Draw a card, gated on Crowd's Favor.
// Mirrors Grand Crusader's Ring (2gv7DC0KID)'s existing generated "Banish CARDNAME: Draw a card"
// ability shape exactly, plus the Crowd's Favor prereq (global effect "gpmJdGYqoC").
AuthorCardAbility(
    $repo,
    $rootName,
    'vkL2RFh0yM',
    'ActivateAbility',
    <<<'PHP'
$mzID = DecisionQueueController::GetVariable("mzID");
MZMove($player, $mzID, "myBanish");
Draw($player, 1);
PHP,
    <<<'PHP'
return GlobalEffectCount($player, "gpmJdGYqoC") > 0;
PHP,
    'Banish, Draw a card'
);

// Unity's Gale (uUWsgLmyTk): Target ally +3 LIFE until end of turn; delayed Crowd's Favor check.
// The MZCHOOSE target offer and the LIFE-buff/delayed-marker grant are authored here; the actual
// ObjectCurrentHP +3 read and the EndPhase() delayed check remain hand-written Custom code (see
// the file-level docblock above) since neither has a generated-macro equivalent in this codebase.
AuthorCardAbility(
    $repo,
    $rootName,
    'uUWsgLmyTk',
    'CardActivated',
    <<<'PHP'
$targets = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
$targets = FilterSpellshroudTargets($targets);
if(empty($targets)) return;
$targetStr = implode("&", $targets);
DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "");
DecisionQueueController::AddDecision($player, "CUSTOM", "uUWsgLmyTk:0:Target-1", 1);
PHP,
    null,
    'Target ally +3 LIFE, delayed Crowd\'s Favor'
);

if (method_exists($repo, 'close')) $repo->close();
