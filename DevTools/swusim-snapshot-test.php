<?php
// ─────────────────────────────────────────────────────────────────────────────
// swusim-snapshot-test.php — emit a DSL Schema Test scaffold from a live SWUSim game.
//
// Loads Games/<gameName>/Gamestate.txt, reads every zone for both players, and prints a
// GIVEN-only DSL .md (faithful board reconstruction) + an empty WHEN scaffold + an EXPECT
// header, to stdout. Redirect it to SWUSim/Tests/Snapshots/<gameName>.md.
//
// Run from the TCGEngine web root (where SWUSim/ lives), e.g.:
//   docker exec -w /var/www/html/TCGEngine <web-container> \
//     php -d xdebug.mode=off DevTools/swusim-snapshot-test.php 2035 > SWUSim/Tests/Snapshots/2035.md
//
// Notes / known limitations (also surfaced as comments in the generated file):
//   • Lands the game in the MAIN phase; if the live phase differed, that's flagged.
//   • An in-flight decision (pending decision queue) is NOT captured — only the board.
//   • Resources are rebuilt as generic vanilla filler split by ready/exhausted count (exact
//     resource card identities aren't preserved — rarely matters for a repro).
//   • Negative leader/base damage (the old constructor -1 artifact) is clamped to 0.
//   • The full deck order is included for draw faithfulness; trim it if you don't need it.
// ─────────────────────────────────────────────────────────────────────────────

$gameArg = $argv[1] ?? '';
if ($gameArg === '') { fwrite(STDERR, "usage: swusim-snapshot-test.php <gameName>\n"); exit(1); }

// Animation stubs (no-ops outside the real engine, same as the regression harness).
function ConvertMzIDToAbsolute($m, $p): string { return ''; }
function QueueDamageAnimation($m, $a): void {}
function QueueRestoreAnimation($m, $a): void {}
function QueuePreventedDamageAnimation($m): void {}
function QueueShieldBreakAnimation($m): void {}

include_once './Core/DeterministicRNG.php';
include_once './Core/CoreZoneModifiers.php';
include_once './SWUSim/ZoneClasses.php';
include_once './SWUSim/ZoneAccessors.php';
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/GamestateParser.php';

InitializeGamestate();
$GLOBALS['gameName'] = $gameArg;
if (!is_file("./SWUSim/Games/{$gameArg}/Gamestate.txt")) {
    fwrite(STDERR, "no Gamestate.txt for game '{$gameArg}'\n"); exit(1);
}
ParseGamestate('./SWUSim/');
global $gTurnPlayer, $gCurrentPhase;

$active = intval($gTurnPlayer) ?: 1;
$initPlayer = HasInitiative(1) ? 1 : (HasInitiative(2) ? 2 : $active);

// ── Warnings for things a static snapshot can't faithfully carry ──────────────
$warnings = [];
if ($gCurrentPhase !== 'MAIN') $warnings[] = "Live phase was '{$gCurrentPhase}'; snapshot lands in MAIN.";
foreach ([1, 2] as $p) {
    if (!empty(GetDecisionQueue($p))) $warnings[] = "P{$p} had a pending decision (in-flight; NOT captured — only the board is).";
}

$clampNeg = fn($n) => max(0, intval($n));
$live = fn($arr) => array_values(array_filter($arr, fn($o) => $o !== null && empty($o->removed)));

// ── Leaders / bases → CommonSetup opts ────────────────────────────────────────
function leaderInfo($p) {
    $l = (GetLeader($p)[0] ?? null);
    if ($l === null) return null;
    $deployed = (bool)($l->Deployed ?? false);
    $info = [
        'cid'      => $l->CardID,
        'ready'    => ((bool)($l->Ready ?? true)),
        'deployed' => $deployed,
        'epic'     => ((bool)($l->EpicActionUsed ?? false)),
        'damage'   => max(0, intval($l->Damage ?? 0)),
        'index'    => -1, 'unitUID' => -1, 'unitHasUpgrades' => false,
    ];
    if ($deployed) {
        // Reconstruct via regular deploy: the deployed UNIT's Status/Damage/index drive the inline
        // myLeader form + indexOverride. The Leader-zone Ready is vestigial while deployed (a leader
        // always returns exhausted on defeat), so we take `ready` from the deployed unit's Status.
        $uid = intval($l->DeployedUniqueID ?? -1);
        $idx = 0;
        foreach (GetGroundArena($p) as $u) {
            if ($u === null || !empty($u->removed)) continue;
            if (intval($u->UniqueID ?? -2) === $uid) {
                $info['ready']  = (intval($u->Status ?? 1) === 1);
                $info['damage'] = max(0, intval($u->Damage ?? 0));
                $info['index']  = $idx;
                $info['unitUID'] = $uid;
                foreach (($u->Subcards ?? []) as $sub) {
                    $subCid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
                    if ($subCid !== '' && strpos(CardType($subCid) ?? '', 'Upgrade') !== false) $info['unitHasUpgrades'] = true;
                }
                break;
            }
            $idx++;
        }
    }
    return $info;
}
function baseInfo($p) {
    $b = (GetBase($p)[0] ?? null);
    if ($b === null) return null;
    return ['cid' => $b->CardID, 'damage' => max(0, intval($b->Damage ?? 0)),
            'epic' => ((bool)($b->EpicActionUsed ?? false))];
}

$myL = leaderInfo(1); $thL = leaderInfo(2);
$myB = baseInfo(1);   $thB = baseInfo(2);
foreach ([1 => ['P1', $myL], 2 => ['P2', $thL]] as [$tagW, $L]) {
    if (!$L || !$L['deployed']) continue;
    if ($L['index'] < 0)          $warnings[] = "{$tagW} leader {$L['cid']} is DEPLOYED but its unit couldn't be located (DeployedUniqueID mismatch) — verify manually.";
    if ($L['unitHasUpgrades'])    $warnings[] = "{$tagW} deployed leader {$L['cid']} has upgrade(s) on its unit — regular deploy can't attach those; add a WithP{n}GroundArenaUpgrade for its index by hand.";
    else                          $warnings[] = "{$tagW} leader {$L['cid']} is DEPLOYED — reconstructed via regular deploy (myLeader:…:deployed) + indexOverride; verify the leader-unit flip side.";
}

// Build the {opts} for one side. A deployed leader uses the inline "CID:ready:deployed:epicUsed:damage"
// form (deployed=true → regular deploy, creates the leader unit) plus LeaderIndexOverride to place it
// at its real arena index; the unit is skipped from the arena brackets below to avoid a duplicate.
function sideOpts($who, $L, $B) {
    $o = [];
    if ($L) {
        $rd = $L['ready'] ? 'true' : 'false';
        $ep = $L['epic']  ? 'true' : 'false';
        if ($L['deployed']) {
            // Inline form incl. the 6th field, indexOverride, to place the leader unit at its arena index.
            $idxField = ($L['index'] >= 0) ? ":{$L['index']}" : '';
            $o[] = "{$who}Leader:{$L['cid']}:{$rd}:true:{$ep}:{$L['damage']}{$idxField}";
        } else {
            $o[] = "{$who}Leader:{$L['cid']}:{$rd}:false:{$ep}:{$L['damage']}";
        }
    }
    if ($B) {
        $o[] = "{$who}Base:{$B['cid']}";
        if ($B['damage'] > 0) $o[] = "{$who}BaseDamage:{$B['damage']}";
    }
    return $o;
}
$opts = array_merge(sideOpts('my', $myL, $myB), sideOpts('their', $thL, $thB));

// ── Per-side directive lines ──────────────────────────────────────────────────
$lines = [];   // GIVEN body lines
// CommonSetup opts as a multiline block — one param per line, each ending in ';' (the brace-folding
// parser rejoins them and skips the trailing empty segment). Preferred style for readability.
$lines[] = "CommonSetup: ngw/ngw/{";
foreach ($opts as $opt) $lines[] = "  {$opt};";
$lines[] = "}";
$lines[] = "SkipPreGame: true";
$lines[] = "WithActivePlayer: {$active}";
$lines[] = "WithInitiativePlayer: {$initPlayer}";

foreach ([1 => 'P1', 2 => 'P2'] as $p => $tag) {
    // Force
    if (PlayerHasTheForce($p)) $lines[] = "With{$tag}Force: true";

    // Resources: split non-credit resources by ready/exhausted; count Credit tokens separately.
    $ready = 0; $exhausted = 0; $credits = 0;
    foreach (GetResources($p) as $r) {
        if ($r === null || !empty($r->removed)) continue;
        if (($r->CardID ?? '') === 'LAW_T01') { $credits++; continue; }
        if (intval($r->Status ?? 1) === 1) $ready++; else $exhausted++;
    }
    if ($ready > 0 || $exhausted > 0) {
        $groups = [];
        if ($ready > 0)     $groups[] = "{$ready}:SOR_095:1";
        if ($exhausted > 0) $groups[] = "{$exhausted}:SOR_095:0";
        $lines[] = "With{$tag}Resources: " . implode(',', $groups);
    }
    if ($credits > 0) $lines[] = "With{$tag}Credits: {$credits}";
}

// Arenas: one bracketed line per arena — WithP{n}{Ground|Space}Arena: [CID:ready:dmg ...] — plus a
// bracketed upgrade line — WithP{n}{arena}ArenaUpgrade: [unitIdx:CID ...]. A deployed leader's unit
// is skipped here (it's created by myLeader:…:deployed + LeaderIndexOverride); upgrade indices are
// bracket-relative (the leader is excluded), which is how the builder attaches them before it splices
// the leader into place.
$skipUID = [1 => (($myL['deployed'] ?? false) ? intval($myL['unitUID']) : -999),
            2 => (($thL['deployed'] ?? false) ? intval($thL['unitUID']) : -999)];
foreach ([1 => 'P1', 2 => 'P2'] as $p => $tag) {
    foreach (['Ground' => GetGroundArena($p), 'Space' => GetSpaceArena($p)] as $arena => $units) {
        $unitSpecs = []; $upgradeSpecs = []; $bidx = 0;
        foreach ($units as $u) {
            if ($u === null || !empty($u->removed)) continue;
            if (intval($u->UniqueID ?? -1) === $skipUID[$p]) continue; // deployed leader — emitted via myLeader
            $ready = (intval($u->Status ?? 1) === 1) ? 1 : 0;
            $dmg   = max(0, intval($u->Damage ?? 0));
            // Active TurnEffects (4th spec field): keep the player-facing ones, drop SWU_-prefixed
            // backend bookkeeping (cost/phase trackers), matching what the Active Effects UI shows.
            $effs = array_values(array_filter(($u->TurnEffects ?? []), fn($e) => strpos((string)$e, 'SWU_') !== 0));
            $effField = !empty($effs) ? ':' . implode('~', $effs) : '';
            $unitSpecs[] = "{$u->CardID}:{$ready}:{$dmg}{$effField}";
            // Upgrades ride as Subcards (type Upgrade); index is bracket-relative (leader excluded).
            foreach (($u->Subcards ?? []) as $sub) {
                $subCid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
                if ($subCid === '') continue;
                if (strpos(CardType($subCid) ?? '', 'Upgrade') === false) continue; // captives/other subcards aren't upgrades
                $upgradeSpecs[] = "{$bidx}:{$subCid}";
            }
            $bidx++;
        }
        if (!empty($unitSpecs))    $lines[] = "With{$tag}{$arena}Arena: [" . implode(' ', $unitSpecs) . "]";
        if (!empty($upgradeSpecs)) $lines[] = "With{$tag}{$arena}ArenaUpgrade: [" . implode(' ', $upgradeSpecs) . "]";
    }
}

// Hand / discard / deck: one bracketed line each — WithP{n}Hand: [CID CID ...].
foreach ([1 => 'P1', 2 => 'P2'] as $p => $tag) {
    $collect = function($zone) { $o = []; foreach ($zone as $c) if ($c && empty($c->removed)) $o[] = $c->CardID; return $o; };
    $hand = $collect(GetHand($p));  $disc = $collect(GetDiscard($p));  $deck = $collect(GetDeck($p));
    if (!empty($hand)) $lines[] = "With{$tag}Hand: ["    . implode(' ', $hand) . "]";
    if (!empty($disc)) $lines[] = "With{$tag}Discard: [" . implode(' ', $disc) . "]";
    if (!empty($deck)) $lines[] = "With{$tag}Deck: ["    . implode(' ', $deck) . "]";
}

// ── Emit the .md ──────────────────────────────────────────────────────────────
$out = [];
$out[] = "# Snapshot of game {$gameArg} — board reconstructed from its live Gamestate.txt.";
$out[] = "# GIVEN rebuilds the exact board; fill in WHEN with the action to reproduce, then add EXPECT.";
foreach ($warnings as $w) $out[] = "# ⚠ {$w}";
$out[] = "";
$out[] = "## GIVEN";
foreach ($lines as $l) $out[] = $l;
$out[] = "";
$out[] = "## WHEN";
$out[] = "# Add the action(s) to reproduce below (uncomment & edit), e.g.:";
$out[] = "# - P{$active}>AttackGroundArena:0:theirGroundArena-0";
$out[] = "# - P{$active}>PlayHand:0";
$out[] = "";
$out[] = "## EXPECT";
$out[] = "# Add assertions here, e.g.:  P1GROUNDARENACOUNT:1   /   P2BASEDMG:4";
echo implode("\n", $out) . "\n";
