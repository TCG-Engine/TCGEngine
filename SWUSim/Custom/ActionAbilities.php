<?php
// ── Named Action abilities ────────────────────────────────────────────────────────────────────────────────────
// Every Action (and Epic Action) ability has a stable id, `<sourceCardID>.<n>`, where n is its 0-based position
// among the Action clauses on that side of the card (own and granted, in text order). A leader's DEPLOYED side —
// and JTL_013 Poe attached as a pilot, whose Action is on that side — is `<CardID>.u<n>`.
// Spec: docs/superpowers/specs/2026-11-02-swusim-named-action-abilities-design.md.
//
// The per-card registries ($unitAbilities, $leaderAbilities, $baseAbilities, $baseUpgradeAbilities, …) stay the
// source of truth. A card with ONE Action keeps its plain `CardID` key; a card with several registers each under
// its id (`SHD_087.0`, `SHD_087.1`). SWUActionReg() resolves either form.

// Descriptive use limits, keyed by id. They feed the greyed reason ("Used this round") and SWU-PGN's `limit`;
// enforcement stays in the card handlers. action_abilities_inventory_test.php checks this table against the card
// text, Tests/Cases/core/NamedActions_LimitsMatchEnforcement.md against the engine.
$actionAbilityLimits = [
    'SHD_017.u0' => ['max' => 1, 'per' => 'round'],   // Lando Calrissian (deployed)
    'TWI_082.0'  => ['max' => 1, 'per' => 'round'],   // MagnaGuard Wing Leader
    'JTL_013.u0' => ['max' => 1, 'per' => 'round'],   // Poe Dameron (as a pilot)
    'LOF_022.0'  => ['max' => 3, 'per' => 'game'],    // Mystic Monastery
    'LAW_094.0'  => ['max' => 1, 'per' => 'round'],   // Hondo Ohnaka
    'ASH_230.0'  => ['max' => 1, 'per' => 'round'],   // Improvised Identity (per copy)
    'HMW_009.u0' => ['max' => 1, 'per' => 'round'],   // Chewbacca (deployed)
    'HMW_172.0'  => ['max' => 1, 'per' => 'phase'],   // Heavy Ion Cannon (granted to the base)
];

// Hand-written labels; everything else is derived (SWUActionAbilityLabel). A leader-slot Action (`<CardID>.0`, or a flipped
// TWI_017's `.u0`) otherwise reads "Leader Ability": name it here to give it flavour. A deployed leader's Actions are the
// unit's (`<CardID>.u<n>`, card title by default). Keys must be ids the card text has (action_abilities_inventory_test.php).
$actionAbilityLabelOverrides = [
    // Admiral Trench / Bail Organa: their second front Action IS their deploy, and keeps the deploy button's name.
    'JTL_014.1' => 'Deploy Leader',
    'SEC_008.1' => 'Deploy Leader',
];

// The Action clauses of one side's text, in order: ['text' => clause, 'cost' => bracket contents or '',
// 'granted' => bool]. Own clauses are unquoted lines starting "Action:" / "Action [..]:" (never "Epic Action"), or
// those preceded by a keyword ("Coordinate - Action …");
// granted ones are quoted strings starting the same way ("Attached unit gains: "Action [Exhaust]: …"").
function SWUActionClauses(string $text): array {
    $out = [];
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $bare = trim(preg_replace('/"[^"]*"|“[^”]*”/u', '""', $line));
        // A keyword may prefix an own Action ("Coordinate - Action [1 resource, Exhaust]: …" — TWI_008, TWI_011).
        if (preg_match('/^(?:[A-Z][A-Za-z]+ - )?Action(?: \[([^\]]*)\])?:/', $bare, $m)) {
            $out[] = ['text' => $line, 'cost' => $m[1] ?? '', 'granted' => false];
        }
        if (preg_match_all('/"([^"]*)"|“([^”]*)”/u', $line, $qm, PREG_SET_ORDER)) {
            foreach ($qm as $q) {
                $inner = trim(($q[1] ?? '') !== '' ? $q[1] : ($q[2] ?? ''));
                if (preg_match('/^Action(?: \[([^\]]*)\])?:/', $inner, $m)) {
                    $out[] = ['text' => $inner, 'cost' => $m[1] ?? '', 'granted' => true];
                }
            }
        }
    }
    return $out;
}

function SWUActionAbilityId(string $cardID, int $n, bool $deploySide = false): string {
    return $cardID . '.' . ($deploySide ? 'u' : '') . $n;
}

// ['cardID' => …, 'n' => int, 'deploySide' => bool]; an id that isn't one comes back as n 0 on the whole string.
function SWUActionIdParts(string $id): array {
    if (preg_match('/^(.+)\.(u?)(\d+)$/', $id, $m)) return ['cardID' => $m[1], 'n' => intval($m[3]), 'deploySide' => $m[2] === 'u'];
    return ['cardID' => $id, 'n' => 0, 'deploySide' => false];
}

// A registry value for an ability id: the id's own key, else — for the first Action of a card — the plain CardID
// key every single-Action card file already uses.
function SWUActionReg(array $registry, string $id) {
    if (array_key_exists($id, $registry)) return $registry[$id];
    $p = SWUActionIdParts($id);
    if ($p['n'] === 0 && array_key_exists($p['cardID'], $registry)) return $registry[$p['cardID']];
    return null;
}

// The button label: the override, else $fallback (the leader slot's "Leader Ability"), else the source card's title —
// plus the printed cost when that source has two or more entries on the same surface ("Crosshair [Exhaust]"),
// "[Action]" when the clause has no cost bracket.
function SWUActionAbilityLabel(string $id, string $cost, int $sameSourceCount, ?string $fallback = null): string {
    global $actionAbilityLabelOverrides;
    if (isset($actionAbilityLabelOverrides[$id])) return $actionAbilityLabelOverrides[$id];
    $label = $fallback ?? (string)(CardTitle(SWUActionIdParts($id)['cardID']) ?? SWUActionIdParts($id)['cardID']);
    if ($sameSourceCount >= 2) $label .= ' [' . ($cost !== '' ? $cost : 'Action') . ']';
    return $label;
}

// Gives every entry its label once the whole surface is known (the cost suffix depends on how many entries share
// a source).
function _SWUActionLabelEntries(array $entries, ?string $fallback = null): array {
    $perSource = [];
    foreach ($entries as $e) $perSource[$e['source']] = ($perSource[$e['source']] ?? 0) + 1;
    foreach ($entries as &$e) { $e['label'] = SWUActionAbilityLabel($e['id'], $e['cost'], $perSource[$e['source']], $fallback); }
    unset($e);
    return $entries;
}

// The Action entries a surface offers $clicker. $surface is a unit mz in the clicker's frame ("myGroundArena-0",
// "theirSpaceArena-1", "p3GroundArena-0"); bases and leaders join in their own phase.
function SWUActionEntriesFor(int $clicker, string $surface): array {
    if (preg_match('/^myBase-\d+$/', $surface)) return SWUBaseActionEntries($clicker);
    if (preg_match('/^myLeader-(\d+)$/', $surface, $m)) return SWULeaderActionEntries($clicker, intval($m[1]));
    if (preg_match('/^myDiscard-(\d+)$/', $surface, $m)) return SWUDiscardActionEntries($clicker, intval($m[1]));
    return SWUUnitActionEntries($clicker, $surface);
}

// ── Bases ────────────────────────────────────────────────────────────────────────────────────────────────────
// The base's own Action / Epic Action (`<BaseID>.0`) and each Fortify upgrade's Action — its own printed one
// (HMW_037, HMW_095) or the one it grants the base (HMW_172, kind 'grant'). Same presence rules as
// _SWUBaseActionProviders (Galen suppression, a blanked Fortify), but an Action the base has and can't use now is
// listed greyed instead of dropped. `subIndex` is the upgrade's index among the base's live upgrades.
function SWUBaseActionEntries(int $player): array {
    global $playerID, $baseAbilities, $baseActionNumUses, $baseActionRepeatable, $baseUpgradeAbilities,
           $baseUpgradeActionAvailable, $baseEpicResourceCosts, $actionAbilityLimits;
    $saved = $playerID; $playerID = $player;
    $base = null;
    foreach (GetBase($player) as $b) { if (empty($b->removed)) { $base = $b; break; } }
    if ($base === null) { $playerID = $saved; return []; }
    $cid = (string)($base->CardID ?? '');
    $uid = SWUObjUID($base, 0);
    $entries = [];
    $entry = fn(string $id, string $kind, string $cost, string $text, ?string $grantedBy, int $idx, bool $ok, string $why) => [
        'id' => $id, 'label' => '', 'source' => SWUActionIdParts($id)['cardID'], 'hostMz' => 'myBase-0', 'hostUid' => $uid, 'hostCardID' => $cid,
        'grantedBy' => $grantedBy, 'enabled' => $ok, 'reason' => $ok ? '' : $why, 'limit' => $actionAbilityLimits[$id] ?? null,
        'subIndex' => $idx, 'kind' => $kind, 'cost' => $cost, 'title' => $text];

    if (isset($baseAbilities[$cid]) && !_SWUGalenSuppressesCard($player, $cid)) {
        $text = (string)(CardText($cid) ?? '');
        $isEpic = (bool)preg_match('/(^|\R)\s*Epic Action/', $text);
        $clause = '';
        foreach (preg_split('/\R/', $text) as $line) if (preg_match('/^\s*(Epic )?Action/', $line)) { $clause = trim($line); break; }
        $cost = preg_match('/^(?:Epic )?Action \[([^\]]*)\]/', $clause, $cm) ? $cm[1] : '';
        $ok = true; $why = '';
        if (isset($baseActionRepeatable[$cid])) {
            $ok = false;   // LOF_028 Tomb of Eilram: "[exhaust a friendly unit]"
            foreach (array_merge(GetGroundArena($player), GetSpaceArena($player)) as $ru) {
                if (!SWUObjGone($ru) && intval($ru->Status ?? 0) === 1) { $ok = true; break; }
            }
            $why = "Can't be used now";
        } elseif (isset($baseActionNumUses[$cid])) {
            $ok = _SWUBaseActionUsesLeft($base, $cid) > 0;
            $why = 'Used this ' . ($actionAbilityLimits["$cid.0"]['per'] ?? 'game');
        } else {
            if (!empty($base->EpicActionUsed)) { $ok = false; $why = 'Used this game'; }
            elseif ($cid === 'LAW_023' && !_SWULaw023CanPayCost($player)) { $ok = false; $why = "Can't be used now"; }
            elseif ($cid === 'LAW_019' && !_SWULaw019CanPayCost($player)) { $ok = false; $why = "Can't be used now"; }
            elseif (($rc = SWUApplyCostHalving($player, intval($baseEpicResourceCosts[$cid] ?? 0))) > 0
                    && SWUTotalPaymentCapacity($player) < $rc) { $ok = false; $why = 'Not enough resources'; }
        }
        $entries[] = $entry("$cid.0", $isEpic ? 'epic' : 'own', $cost, $clause, null, -1, $ok, $why);
    }

    $idx = 0;
    foreach (GetUpgradesOnUnit($base) as $sub) {
        $ucid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        if ($ucid !== '' && isset($baseUpgradeAbilities[$ucid]) && !_SWUFortifyBlanked($player, $ucid)) {
            $id = "$ucid.0";
            $seen = false;
            foreach ($entries as $e) if ($e['id'] === $id) $seen = true;   // copies fold
            if (!$seen) {
                $cl = SWUActionClauses((string)(CardText($ucid) ?? ''))[0] ?? ['text' => '', 'cost' => '', 'granted' => false];
                $gate = $baseUpgradeActionAvailable[$ucid] ?? null;
                $ok = !is_callable($gate) || $gate($player, $idx, $base);
                $why = "Can't be used now";
                // HMW_172: its gate refuses an empty hand (the discard cost) or a spent once-each-phase use.
                if (!$ok && $ucid === 'HMW_172' && count(array_filter(GetHand($player), fn($c) => empty($c->removed))) > 0) $why = 'Used this phase';
                $entries[] = $entry($id, $cl['granted'] ? 'grant' : 'upgrade-own', $cl['cost'], $cl['text'],
                    $cl['granted'] ? $ucid : null, $idx, $ok, $why);
            }
        }
        $idx++;
    }
    $playerID = $saved;
    return _SWUActionLabelEntries($entries);
}

// ── Leaders (not deployed) ───────────────────────────────────────────────────────────────────────────────────
// The leader's front Action(s) — every leader Action costs [Exhaust], so an exhausted leader's is greyed. JTL_014
// Admiral Trench / SEC_008 Bail Organa's SECOND front Action is their deploy: it stays the "Deploy Leader" button and
// is not an entry here. A deployed leader lists nothing (its Actions are the unit's), except TWI_017 "Flipatine",
// whose "deployed" state is the flipped face with its own Action (`TWI_017.u0`).
function SWULeaderActionEntries(int $player, int $leaderIndex): array {
    global $playerID, $leaderAbilities, $leaderActionResourceCosts, $actionAbilityLimits;
    $saved = $playerID; $playerID = $player;
    $leader = SWUGetLeaderByIndex($player, $leaderIndex);
    if ($leader === null || !empty($leader->removed)) { $playerID = $saved; return []; }
    $cid = (string)($leader->CardID ?? '');
    $flipped = !empty($leader->Deployed);
    if (($flipped && $cid !== 'TWI_017') || !is_callable($leaderAbilities[$cid] ?? null)) { $playerID = $saved; return []; }
    $entries = [];
    foreach (SWUActionClauses((string)(($flipped ? CardDeployText($cid) : CardText($cid)) ?? '')) as $k => $cl) {
        if ($cl['granted']) continue;
        if ($k === 1 && in_array($cid, ['JTL_014', 'SEC_008'], true)) continue;   // the deploy Action
        $id = SWUActionAbilityId($cid, $k, $flipped);
        $ok = true; $why = '';
        if (empty($leader->Ready)) { $ok = false; $why = 'Exhausted'; }
        elseif (!SWULeaderActionAffordable($player, $cid)) {
            $rc = SWUApplyCostHalving($player, intval($leaderActionResourceCosts[$cid] ?? 0));
            $ok = false; $why = ($rc > 0 && SWUTotalPaymentCapacity($player) < $rc) ? 'Not enough resources' : "Can't be used now";
        }
        $entries[] = ['id' => $id, 'label' => '', 'source' => $cid, 'hostMz' => "myLeader-$leaderIndex", 'hostUid' => SWUObjUID($leader, 0), 'hostCardID' => $cid,
            'grantedBy' => null, 'enabled' => $ok, 'reason' => $why, 'limit' => $actionAbilityLimits[$id] ?? null,
            'subIndex' => -1, 'kind' => 'own', 'cost' => $cl['cost'], 'title' => $cl['text']];
    }
    $playerID = $saved;
    return _SWUActionLabelEntries($entries, 'Leader Ability');
}

// ── Units ────────────────────────────────────────────────────────────────────────────────────────────────────
// Every Action the unit at $mz has, for $clicker:
//   own        its printed Actions (a deployed leader unit's are on the deployed side: `<id>.u<n>`)
//   grant      one entry per granting upgrade SOURCE attached (copies fold; `subIndex` = the copy a use spends)
//   pilot      JTL_013 Poe attached as a pilot (his deployed-side Action)
//   satine     TWI_047's grant to every unit, folded across every Satine in play
//   self-grant LOF_098 Leia's own conditional grant, only while she is in the space arena
// A unit the clicker doesn't control lists only its "any player may use" Actions. An Action the unit doesn't have
// right now is not an entry; one it has but can't use is an entry with enabled=false and a reason.
function SWUUnitActionEntries(int $clicker, string $mz): array {
    global $playerID, $unitAbilities, $unitActionSubcardNumUses, $anyPlayerUnitActions, $actionAbilityLimits;
    $saved = $playerID; $playerID = $clicker;
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) { $playerID = $saved; return []; }
    $hostCardID = (string)($obj->CardID ?? '');
    $hostUid = SWUObjUID($obj, 0);
    // A deployed LEADER CARD prints its unit Actions on the deployed side. Not IsLeaderUnit(): a Pilot leader makes its
    // Vehicle a leader unit too (CR 3.4.7), and that Vehicle's own Actions are still on its front.
    $isLeaderUnit = strpos((string)(CardType($hostCardID) ?? ''), 'Leader') !== false;
    $entries = [];
    $add = function (string $id, string $kind, string $cost, string $text, ?string $grantedBy, int $subIndex) use (&$entries, $mz, $hostUid, $hostCardID, $actionAbilityLimits) {
        foreach ($entries as $e) if ($e['id'] === $id) return;   // copies fold
        $entries[] = ['id' => $id, 'label' => '', 'source' => SWUActionIdParts($id)['cardID'], 'hostMz' => $mz,
            'hostUid' => $hostUid, 'hostCardID' => $hostCardID, 'grantedBy' => $grantedBy, 'enabled' => true, 'reason' => '',
            'limit' => $actionAbilityLimits[$id] ?? null, 'subIndex' => $subIndex, 'kind' => $kind, 'cost' => $cost, 'title' => $text];
    };

    // Own printed Actions. Poe's deployed-side Action ("attach THIS UPGRADE …") exists only while he is a pilot
    // upgrade, never on Poe deployed as a unit.
    $ownText = (string)(($isLeaderUnit ? CardDeployText($hostCardID) : CardText($hostCardID)) ?? '');
    foreach (SWUActionClauses($ownText) as $k => $cl) {
        if ($cl['granted']) continue;
        if ($hostCardID === 'JTL_013') continue;
        $id = SWUActionAbilityId($hostCardID, $k, $isLeaderUnit);
        if (is_callable(SWUActionReg($unitAbilities, $id))) $add($id, 'own', $cl['cost'], $cl['text'], null, -1);
    }
    // LOF_098 Leia — "While this unit is in the space arena, she … gains: "Action [use the Force]: …"".
    if ($hostCardID === 'LOF_098' && strpos($mz, 'SpaceArena') !== false) {
        foreach (SWUActionClauses((string)(CardText('LOF_098') ?? '')) as $k => $cl) {
            if ($cl['granted']) $add(SWUActionAbilityId('LOF_098', $k), 'self-grant', $cl['cost'], $cl['text'], null, -1);
        }
    }

    // Attached cards: an upgrade's granted Action ("Attached unit gains: …"), or a pilot leader's own Action.
    $subs = is_array($obj->Subcards ?? null) ? $obj->Subcards : [];
    foreach ($subs as $i => $sub) {
        $cid  = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        $capt = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
        $gone = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
        if ($cid === '' || $capt || $gone) continue;
        $isPilotLeader = strpos((string)(CardType($cid) ?? ''), 'Leader') !== false;
        $text = (string)(($isPilotLeader ? CardDeployText($cid) : CardText($cid)) ?? '');
        foreach (SWUActionClauses($text) as $k => $cl) {
            if ($cl['granted'] === $isPilotLeader) continue;   // upgrades grant; a pilot leader prints its own
            $id = SWUActionAbilityId($cid, $k, $isPilotLeader);
            if (!is_callable(SWUActionReg($unitAbilities, $id))) continue;
            // The copy a use spends: the first with a use left, else the first.
            $existing = null;
            foreach ($entries as $ei => $e) if ($e['id'] === $id) $existing = $ei;
            if ($existing !== null) {
                if (isset($unitActionSubcardNumUses[$cid]) && _SWUUnitActionSubcardUsesLeft($obj, $entries[$existing]['subIndex']) <= 0
                    && _SWUUnitActionSubcardUsesLeft($obj, $i) > 0) $entries[$existing]['subIndex'] = $i;
                continue;
            }
            $add($id, $isPilotLeader ? 'pilot' : 'grant', $cl['cost'], $cl['text'], $isPilotLeader ? null : $cid, $i);
        }
    }

    // TWI_047 Satine — "Each unit (including enemy units) gains: "Action [Exhaust]: …"", one entry however many.
    if (_SWUSatineInPlay()) {
        foreach (SWUActionClauses((string)(CardText('TWI_047') ?? '')) as $k => $cl) {
            if ($cl['granted']) $add(SWUActionAbilityId('TWI_047', $k), 'satine', $cl['cost'], $cl['text'], 'TWI_047', -1);
        }
    }

    // A unit the clicker doesn't control: only its "any player may use" Actions.
    if (intval($obj->Controller ?? $clicker) !== $clicker) {
        $entries = array_values(array_filter($entries, fn($e) => !empty($anyPlayerUnitActions[$e['source']])));
    }

    foreach ($entries as &$e) { [$e['enabled'], $e['reason']] = _SWUActionEntryState($clicker, $mz, $e); }
    unset($e);
    $playerID = $saved;
    return _SWUActionLabelEntries($entries);
}

// Whether a limited unit entry's uses are spent, read from wherever the card keeps its count: an upgrade copy's
// subcard NumUses, a pilot leader's NumUses, the unit's own NumUses — or a card-specific flag (SHD_017 Lando's
// SWU_SHD017_USED). A new limited card that keeps its count elsewhere shows up red in
// Tests/Cases/core/NamedActions_LimitsMatchEnforcement.md ("Can't be used now" instead of "Used this …").
function _SWUActionLimitSpent(array $entry, $obj, int $clicker): bool {
    global $unitActionSubcardNumUses;
    $src  = $entry['source'];
    $ctrl = intval($obj->Controller ?? $clicker);
    if ($src === 'SHD_017') return GlobalEffectCount($ctrl, 'SWU_SHD017_USED') > 0;
    return match ($entry['kind']) {
        'grant'  => isset($unitActionSubcardNumUses[$src]) && _SWUUnitActionSubcardUsesLeft($obj, $entry['subIndex']) <= 0,
        'pilot'  => !SWUHasUseAvailable(SWUFindLeaderByCardID($ctrl, $src)),
        default  => !SWUHasUseAvailable($obj),
    };
}

// [enabled, reason] for one unit entry. First match wins: lost abilities, an [Exhaust] cost on an exhausted unit,
// an unpayable resource cost, a spent limited use, a no-target gate, then any other refusal of the existing gate.
function _SWUActionEntryState(int $clicker, string $mz, array $entry): array {
    global $playerID, $unitActionCostKind, $unitActionResourceCosts, $unitActionSubcardNumUses;
    $saved = $playerID; $playerID = $clicker;
    $obj = GetZoneObject($mz);
    $id = $entry['id']; $src = $entry['source'];
    $per = $entry['limit']['per'] ?? 'round';
    $out = [true, ''];
    if ($obj === null || LostAbilities($obj)) {
        $out = [false, "Can't be used now"];
    } elseif ((SWUActionReg($unitActionCostKind, $id) ?? 'exhaust') === 'exhaust' && intval($obj->Status ?? 0) !== 1) {
        $out = [false, 'Exhausted'];
    } elseif (($rc = SWUApplyCostHalving($clicker, intval(SWUActionReg($unitActionResourceCosts, $id) ?? 0))) > 0
              && SWUTotalPaymentCapacity($clicker) < $rc) {
        $out = [false, 'Not enough resources'];
    } elseif ($entry['limit'] !== null && _SWUActionLimitSpent($entry, $obj, $clicker)) {
        $out = [false, 'Used this ' . $per];
    } elseif (!SWUUnitActionAffordable($clicker, $mz, $src, $entry['subIndex'])) {
        $out = [false, in_array($src, ['HMW_009', 'HMW_001'], true) ? 'No legal target' : "Can't be used now"];
    }
    $playerID = $saved;
    return $out;
}

// The unit entry a use would run: the one named by $id if it's listed AND usable; with no id, the unit's only usable
// one. Null = refuse (nothing spent): a stale or greyed id, no usable Action, or two or more and no id to choose.
function SWUUnitActionPick(int $player, string $mz, string $id = ''): ?array {
    $entries = SWUUnitActionEntries($player, $mz);
    if ($id === '') {
        $usable = array_values(array_filter($entries, fn($e) => $e['enabled']));
        return count($usable) === 1 ? $usable[0] : null;
    }
    foreach ($entries as $e) if ($e['id'] === $id) return $e['enabled'] ? $e : null;
    return null;
}

// How many of a surface's entries are usable right now (the bare-input rule's "exactly one").
function SWUActionEnabledCount(int $player, string $surface): int {
    return count(array_filter(SWUActionEntriesFor($player, $surface), fn($e) => $e['enabled']));
}

// ── Game log + SWU-PGN for a used Action ─────────────────────────────────────────────────────────────────────
// The SWU-PGN `ability` id: the entry id, plus — for a granted Action whose limit is PER COPY (Improvised Identity) —
// the copy used ("@<its uid>", or "@i<index>" for a copy that never got one), so two copies on one host keep two use
// counts in the fold (§10.1 limited abilities). Writer-chosen; readers never branch on it.
function SWUActionPgnAbilityId(array $entry): string {
    global $playerID, $unitActionSubcardNumUses;
    $id = (string)$entry['id'];
    if (($entry['kind'] ?? '') !== 'grant' || !isset($unitActionSubcardNumUses[$entry['source'] ?? ''])) return $id;
    $saved = $playerID; $playerID = intval($entry['clicker'] ?? $playerID);
    $host = GetZoneObject((string)($entry['hostMz'] ?? ''));
    $playerID = $saved;
    $sub = is_object($host) && is_array($host->Subcards ?? null) ? ($host->Subcards[intval($entry['subIndex'] ?? -1)] ?? null) : null;
    $uid = is_array($sub) ? intval($sub['UniqueID'] ?? 0) : (is_object($sub) ? intval($sub->UniqueID ?? 0) : 0);
    return $id . '@' . ($uid > 0 ? $uid : 'i' . intval($entry['subIndex'] ?? -1));
}

// The log line's verb phrase for a used Action entry: a granted one names the ability AND the unit that used it
// ("used Improvised Identity on Chewbacca"); an own one is "used <card>'s Action", plus the cost when that card
// prints two ("used Crosshair's Action [Exhaust]").
function SWUActionLogPhrase(array $entry, string $kind = 'Action'): string {
    if (in_array($entry['kind'] ?? '', ['grant', 'satine'], true)) {
        return 'used ' . GameLogCardRef((string)$entry['source']) . ' on ' . GameLogCardRef((string)($entry['hostCardID'] ?? ''));
    }
    $suffix = (str_contains((string)($entry['label'] ?? ''), ' [') && ($entry['cost'] ?? '') !== '') ? ' [' . $entry['cost'] . ']' : '';
    return 'used ' . GameLogCardRef((string)$entry['source']) . "'s {$kind}{$suffix}";
}

// Any surface's entry a use would run (SWUUnitActionPick's rule, for bases and leaders too): the named entry if listed
// and usable; with no id, the surface's only usable one. Null = refuse, nothing spent.
function SWUActionPick(int $player, string $surface, string $id = ''): ?array {
    $entries = SWUActionEntriesFor($player, $surface);
    if ($id === '') {
        $usable = array_values(array_filter($entries, fn($e) => $e['enabled']));
        return count($usable) === 1 ? $usable[0] : null;
    }
    foreach ($entries as $e) if ($e['id'] === $id) return $e['enabled'] ? $e : null;
    return null;
}

// ── Discard pile (spec §9) ───────────────────────────────────────────────────────────────────────────────────
// The ways $player may play their discard card at visible index $idx — one entry per own grant left after the stacking
// rule (SWUDiscardOwnGrants). Ids: the card's own Action is `<CardID>.<n>` (Kylo's TIE Silencer `SHD_135.0`); a grant
// from another card is `<source>.d<n>` (Boga `HMW_122.d0`, Cobb Vanth `SHD_115.d0`); a source-less legacy grant is
// `<CardID>.d0`. Label: the source's title; `note`: what it costs ("Free", "1 resource").
function SWUDiscardActionEntries(int $player, int $idx): array {
    global $playerID;
    $saved = $playerID; $playerID = $player;
    $entry = null; $actual = -1; $n = 0;
    $discard = GetDiscard($player);
    for ($i = 0; $i < count($discard); $i++) {
        if (!empty($discard[$i]->removed)) continue;
        if ($n === $idx) { $entry = $discard[$i]; $actual = $i; break; }
        $n++;
    }
    if ($entry === null) { $playerID = $saved; return []; }
    $cid = (string)($entry->CardID ?? '');
    $ownK = 0;
    foreach (SWUActionClauses((string)(CardText($cid) ?? '')) as $k => $cl) if (!$cl['granted']) { $ownK = $k; break; }
    $blocked = SWUCardPlayBlocked($player, $cid);
    $perSource = [];
    $out = [];
    foreach (SWUDiscardOwnGrants($player, $entry) as $g) {
        $src = $g['source'];
        if ($src === $cid) $id = SWUActionAbilityId($cid, $ownK);
        else { $s = $src !== '' ? $src : $cid; $id = $s . '.d' . ($perSource[$s] ?? 0); $perSource[$s] = ($perSource[$s] ?? 0) + 1; }
        $p = $g['parsed'];
        $free = $p['kind'] === 'TPF';
        $price = $free ? 0 : max(0, intval(CardCost($cid)) + SWUAspectPenalty($player, $cid) - intval($p['discount']));
        $ok = !$blocked && ($free || CanAffordActivationReserve($player, $entry, $p['discount'], false, !$p['unitOnly']));
        $out[] = ['id' => $id, 'label' => (string)(CardTitle($src !== '' ? $src : $cid) ?? $cid), 'source' => $src !== '' ? $src : $cid,
            'hostMz' => "myDiscard-$actual", 'hostUid' => 0, 'hostCardID' => $cid, 'grantedBy' => $src !== $cid && $src !== '' ? $src : null,
            'enabled' => $ok, 'reason' => $ok ? '' : ($blocked ? "Can't be used now" : 'Not enough resources'),
            'limit' => null, 'subIndex' => -1, 'kind' => 'discard', 'cost' => '', 'title' => '',
            'mod' => $g['mod'], 'free' => $free, 'note' => $free ? 'Free' : ($price . ' resource' . ($price === 1 ? '' : 's'))];
    }
    $playerID = $saved;
    return $out;
}
