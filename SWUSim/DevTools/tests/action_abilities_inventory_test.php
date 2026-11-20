<?php
// Named Action abilities, Task 1 — every Action printed (or granted) in the card text is reachable through the
// engine's registries under its stable id, and every "once each round / phase / N times each game" limit in the
// text has matching $actionAbilityLimits metadata (and nothing else does). The metadata is DESCRIPTIVE (it feeds
// the greyed reason and the SWU-PGN `limit`); enforcement lives in the card handlers, and
// Tests/Cases/core/NamedActions_LimitsMatchEnforcement.md checks the two agree.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/action_abilities_inventory_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
require_once __DIR__ . '/../../Custom/DeckImport.php';   // SWUResolveToImplementedPrint — the printing a deck loads as
global $unitAbilities, $leaderAbilities, $baseAbilities, $baseUpgradeAbilities, $actionAbilityLimits, $actionAbilityLabelOverrides;

$check(function_exists('SWUActionClauses') && function_exists('SWUActionAbilityId') && function_exists('SWUActionReg'),
    'ActionAbilities.php helpers are defined');
if (!function_exists('SWUActionClauses')) { bot_test_finish(); exit(1); }

$mismatch = [];
$miss = function (string $id, string $rule) use (&$mismatch) { $mismatch[] = "MISMATCH $id $rule"; };

// Exempt, with reasons: a "play this from your discard pile" Action is the play-from-discard path (no ability
// registry), and TWI_017 Palpatine's "deployed" side is a FLIP, handled by the leader closure of the other side.
$isDiscardPlay = fn(string $t) => (bool)preg_match('/play (it|this upgrade|this unit) from your discard pile/', $t);
$granted = ['SHD_155' => 'unit', 'TWI_120' => 'unit', 'LAW_126' => 'unit', 'ASH_230' => 'unit', 'TWI_047' => 'unit',
            'LOF_098' => 'unit', 'HMW_172' => 'baseUpgrade'];
$fortifyOwn = ['HMW_037', 'HMW_095'];

$limitFromText = function (string $t): ?array {
    if (preg_match('/no more than (\d+) times each game/', $t, $m)) return ['max' => intval($m[1]), 'per' => 'game'];
    if (preg_match('/only once each round/', $t)) return ['max' => 1, 'per' => 'round'];
    if (preg_match('/only once each phase/', $t)) return ['max' => 1, 'per' => 'phase'];
    return null;
};

$seenIds = [];
$cards = 0;
// A printing a deck never loads as itself never reaches a registry: a reprint from a set outside SWUImplementedSets
// loads AS its original (SWUResolveToImplementedPrint), whose text is inventoried here already. Skip exactly those —
// IC27_139 / IC27_154 since IC27 went official (2026-11-07). A reprint that DOES load as itself (IBH_027, IBH_036,
// IBH_100: IBH is implemented) is still checked, since its own id is what the registries are asked for in play.
$isReprint = fn(string $cid) => SWUResolveToImplementedPrint(SWUCanonicalCardID($cid)) !== $cid;
foreach (GetAllCardIds() as $cid) {
    if ($isReprint($cid)) continue;
    $type = (string)(CardType($cid) ?? '');
    foreach ([false, true] as $deploy) {
        $text = (string)($deploy ? (CardDeployText($cid) ?? '') : (CardText($cid) ?? ''));
        if ($text === '' || strpos($text, 'Action') === false) continue;
        // One counter per card side, in text order, across own AND granted clauses — ids stay unique per side.
        $k = -1;
        foreach (SWUActionClauses($text) as $cl) {
            $k++;
            if ($isDiscardPlay($cl['text'])) continue;
            if ($cid === 'TWI_017' && $deploy) continue;
            $cards++;
            if ($cl['granted']) {
                $id = SWUActionAbilityId($cid, $k, $deploy);
                $where = $granted[$cid] ?? null;
                if ($where === null) { $miss($id, 'granted clause on a card with no grant registry'); continue; }
                $reg = $where === 'unit' ? $unitAbilities : $baseUpgradeAbilities;
                if (!is_callable(SWUActionReg($reg, $id))) $miss($id, "granted: no \$" . ($where === 'unit' ? 'unitAbilities' : 'baseUpgradeAbilities') . " entry");
            } else {
                $id = SWUActionAbilityId($cid, $k, $deploy);
                if ($type === 'Unit' || ($type === 'Leader' && $deploy)) {
                    if (!is_callable(SWUActionReg($unitAbilities, $id))) $miss($id, 'unit/deployed: no $unitAbilities entry');
                } elseif ($type === 'Leader') {
                    $isDeployAction = in_array($cid, ['JTL_014', 'SEC_008'], true) && SWUActionIdParts($id)['n'] === 1;
                    if ($isDeployAction) {
                        if (($actionAbilityLabelOverrides[$id] ?? '') !== 'Deploy Leader') $miss($id, 'deploy Action: label override must be "Deploy Leader"');
                    } elseif (!is_callable($leaderAbilities[$cid] ?? null)) $miss($id, 'leader front: no $leaderAbilities entry');
                } elseif ($type === 'Base') {
                    if (!is_callable($baseAbilities[$cid] ?? null)) $miss($id, 'base: no $baseAbilities entry');
                } elseif ($type === 'Upgrade' && in_array($cid, $fortifyOwn, true)) {
                    if (!is_callable($baseUpgradeAbilities[$cid] ?? null)) $miss($id, 'fortify: no $baseUpgradeAbilities entry');
                } else {
                    $miss($id, "own Action on an unhandled type '$type'");
                }
            }
            $seenIds[$id] = true;
            $want = $limitFromText($cl['text']);
            $have = $actionAbilityLimits[$id] ?? null;
            if ($want !== $have) $miss($id, 'limit metadata ' . json_encode($have) . ' but text says ' . json_encode($want));
        }
    }
}
// A base's Epic Action is not an "Action" clause but is a named entry on the base surface (`<BaseID>.0`), so it
// must be registered too. (A leader's Epic Action is its deploy — a different path, not inventoried here.)
foreach (GetAllCardIds() as $cid) {
    if ($isReprint($cid) || (string)(CardType($cid) ?? '') !== 'Base') continue;
    if (!preg_match('/(^|\R)\s*Epic Action/', (string)(CardText($cid) ?? ''))) continue;
    $cards++;
    if (!is_callable($baseAbilities[$cid] ?? null)) $miss("$cid.0", 'base Epic Action: no $baseAbilities entry');
}

// Metadata that names no Action in the text is stale.
foreach (array_keys($actionAbilityLimits ?? []) as $id) if (!isset($seenIds[$id])) $miss($id, 'limit metadata for an id the text does not have');
foreach (array_keys($actionAbilityLabelOverrides ?? []) as $id) if (!isset($seenIds[$id])) $miss($id, 'label override for an id the text does not have');

// Every limited id has its enforcement section ("# <CardID>_UsedUp_EntryGreyed"), so the limit table can't gain a
// row the engine was never checked against.
$limitsMd = (string)@file_get_contents('./SWUSim/Tests/Cases/core/NamedActions_LimitsMatchEnforcement.md');
foreach (array_keys($actionAbilityLimits ?? []) as $id) {
    $sec = SWUActionIdParts($id)['cardID'] . '_UsedUp_EntryGreyed';
    if (!preg_match('/^# ' . preg_quote($sec, '/') . '\s*$/m', $limitsMd)) $miss($id, "no '# $sec' section in NamedActions_LimitsMatchEnforcement.md");
}

foreach ($mismatch as $line) echo "$line\n";
$check($cards > 200, "scanned the Action clauses of the card pool ($cards)");
$check(empty($mismatch), 'every Action clause is registered and its limit metadata matches the text (' . count($mismatch) . ' mismatches)');
bot_test_finish();
