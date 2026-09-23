<?php
// Board features for the learned value model — spec docs/superpowers/specs/2026-09-19-swusim-value-model-design.md §5.
// THE ONLY definition of the features: the logger (training) and the @value chooser (play) both call
// SWUValueFeatures(), so train and serve cannot drift. Adding, removing or reordering a name changes
// SWUValueFeatureVersion(), and a model with another version is refused (SwuValue.php).
//
// Everything is from $seat's view and uses only what $seat may see: the opponent's hand and both decks as SIZES,
// never contents, and NEVER the opponent's archetype (not observable in real play — Arenabot faces humans).
// HAND FEATURES read $hand, never the Hand zone: at decision time the caller passes the hand BEFORE the move minus
// the card played, so a lookahead that crosses the regroup can never see the cards it would draw.

const SWU_VALUE_CLOCK_CAP = 10;
const SWU_VALUE_BOMB_COST = 6;

function SWUValueFeatureNames(): array {
    static $names = null;
    if ($names !== null) return $names;
    $side = ['units_ground', 'units_space', 'power', 'hp_left_units', 'value', 'ready', 'sentinels', 'shields',
             'upgrades', 'leader_deployed', 'leader_epic_avail', 'res_total', 'res_ready', 'credits', 'force', 'hand', 'deck'];
    $names = ['round', 'my_hp_left', 'their_hp_left', 'my_hp_frac', 'their_hp_frac',
              'my_pot_all', 'their_pot_all', 'my_pot_ready', 'their_pot_ready', 'my_clock', 'their_clock',
              'i_lethal_next', 'they_lethal_next',
              'to_act', 'init_mine_unclaimed', 'init_mine_claimed', 'init_theirs_unclaimed', 'init_theirs_claimed',
              'i_passed', 'they_passed'];
    foreach ($side as $s) $names[] = "my_$s";
    foreach ($side as $s) $names[] = "their_$s";
    array_push($names, 'hand_removal', 'hand_wipe', 'hand_castable_now', 'hand_castable_next', 'hand_bombs');
    foreach (SWU_BOT_ARCHETYPES as $a) $names[] = "style_$a";
    array_push($names, 'their_max_threat', 'hpdiff_x_round', 'their_pot_x_early', 'my_pot_x_ctrl', 'removal_x_threat');
    return $names;
}

function SWUValueFeatureVersion(): string {
    return substr(sha1(implode(',', SWUValueFeatureNames())), 0, 12);
}

// $seat's hand as [['cid', 'cost'], …] in zone order, costs priced NOW (aspect penalties included).
function SWUValueHandSnapshot(int $seat): array {
    $out = [];
    foreach (GetHand($seat) as $o) {
        if ($o === null || !empty($o->removed)) continue;
        $out[] = ['cid' => strval($o->CardID ?? ''), 'cost' => intval(SWUComputePlayCost($seat, $o))];
    }
    return $out;
}

function _SWUValueSide(int $p, bool $mine): array {
    $units = SWUBotUnits($p);
    $truthy = fn($v) => !empty($v) && strval($v) !== 'false';
    $leader = GetLeader($p)[0] ?? null;
    $live = fn(array $z) => count(array_filter($z, fn($o) => $o !== null && empty($o->removed)));
    return [
        'units_ground' => count(array_filter($units, fn($v) => $v['arena'] === 'Ground')),
        'units_space'  => count(array_filter($units, fn($v) => $v['arena'] === 'Space')),
        'power'        => array_sum(array_map(fn($v) => $v['power'], $units)),
        'hp_left_units'=> array_sum(array_map(fn($v) => max(0, $v['remaining']), $units)),
        'value'        => array_sum(array_map('SWUBotUnitValue', $units)),
        'ready'        => count(array_filter($units, fn($v) => $v['ready'])),
        'sentinels'    => count(array_filter($units, fn($v) => $v['sentinel'])),
        'shields'      => array_sum(array_map(fn($v) => $v['shields'], $units)),
        'upgrades'     => array_sum(array_map(fn($v) => $v['upgrades'], $units)),
        'leader_deployed'   => ($leader !== null && $truthy($leader->Deployed ?? null)) ? 1 : 0,
        'leader_epic_avail' => ($leader !== null && !$truthy($leader->EpicActionUsed ?? null)) ? 1 : 0,
        'res_total'    => SWUResourceCount($p),
        'res_ready'    => SWUResourceCount($p, true),
        'credits'      => count(SWUUsableCreditTokenMzIDs($p)),
        'force'        => PlayerHasTheForce($p) ? 1 : 0,
        'hand'         => $live(GetHand($p)),   // overwritten for MY side from $hand
        'deck'         => $live(GetDeck($p)),
    ];
}

function SWUValueFeatures(int $seat, array $hand, string $style): array {
    $opp = SWUBotOpponent($seat);
    $hpMax = function (int $p) { $b = GetBase($p)[0] ?? null; return $b === null ? 1 : max(1, intval(CardHp($b->CardID))); };
    $myHp = SWUBaseRemainingHp($seat); $theirHp = SWUBaseRemainingHp($opp);
    $round = intval(GetTurnNumber());
    $f = ['round' => $round, 'my_hp_left' => $myHp, 'their_hp_left' => $theirHp,
          'my_hp_frac' => $myHp / $hpMax($seat), 'their_hp_frac' => $theirHp / $hpMax($opp),
          'my_pot_all' => SWUBotBasePotential($seat, $opp, false), 'their_pot_all' => SWUBotBasePotential($opp, $seat, false),
          'my_pot_ready' => SWUBotBasePotential($seat, $opp, true), 'their_pot_ready' => SWUBotBasePotential($opp, $seat, true),
          'my_clock' => min(SWU_VALUE_CLOCK_CAP, SWUBotClock($seat, $opp)), 'their_clock' => min(SWU_VALUE_CLOCK_CAP, SWUBotClock($opp, $seat)),
          'i_lethal_next' => SWUBotLethalNextRound($seat, $opp) ? 1 : 0, 'they_lethal_next' => SWUBotLethalNextRound($opp, $seat) ? 1 : 0];
    // Tempo. The next free-play actor: a pending decision's seat, else the turn player. (SWUBotPendingDecisionSeat
    // returns 0 — not null — when nothing is pending, so `??` would never fall through.)
    $pend = intval(SWUBotPendingDecisionSeat());
    $next = $pend > 0 ? $pend : intval(GetTurnPlayer());
    $f['to_act'] = intval($next) === $seat ? 1 : 0;
    $ic = strval(GetInitiativeCounter() ?? '');
    $f['init_mine_unclaimed']   = $ic === "P{$seat}_UNCLAIMED" ? 1 : 0;
    $f['init_mine_claimed']     = $ic === "P{$seat}_CLAIMED" ? 1 : 0;
    $f['init_theirs_unclaimed'] = $ic === "P{$opp}_UNCLAIMED" ? 1 : 0;
    $f['init_theirs_claimed']   = $ic === "P{$opp}_CLAIMED" ? 1 : 0;
    // The engine's consecutive-pass counter: 1 = the LAST actor passed. Whoever is not to act acted last.
    $passed = strval(DecisionQueueController::GetVariable('PASS') ?? '0') === '1';
    $f['i_passed'] = ($passed && !$f['to_act']) ? 1 : 0;
    $f['they_passed'] = ($passed && $f['to_act']) ? 1 : 0;
    foreach (_SWUValueSide($seat, true) as $k => $v) $f["my_$k"] = $v;
    foreach (_SWUValueSide($opp, false) as $k => $v) $f["their_$k"] = $v;
    // My hand — from $hand ONLY (the lookahead guard).
    $f['my_hand'] = count($hand);
    $capNow = SWUTotalPaymentCapacity($seat);
    $capNext = SWUResourceCount($seat) + 1 + count(SWUUsableCreditTokenMzIDs($seat));
    $f['hand_removal'] = $f['hand_wipe'] = $f['hand_castable_now'] = $f['hand_castable_next'] = $f['hand_bombs'] = 0;
    foreach ($hand as $h) {
        $tags = SWUBotCardTags(strval($h['cid']));
        if (in_array('removal', $tags, true)) $f['hand_removal']++;
        if (in_array('wipe', $tags, true)) $f['hand_wipe']++;
        if (intval($h['cost']) <= $capNow) $f['hand_castable_now']++;
        if (intval($h['cost']) <= $capNext) $f['hand_castable_next']++;
        if (intval(CardCost(strval($h['cid']))) >= SWU_VALUE_BOMB_COST) $f['hand_bombs']++;
    }
    $rank = SWUBotStyleRank($style);
    foreach (SWU_BOT_ARCHETYPES as $i => $a) $f["style_$a"] = $i === $rank ? 1 : 0;
    $threat = 0;
    foreach (SWUBotUnits($opp) as $u) $threat = max($threat, SWUBotUnitBaseThreat($seat, $u));
    $f['their_max_threat'] = $threat;
    $f['hpdiff_x_round'] = ($myHp - $theirHp) * $round;
    $f['their_pot_x_early'] = $round <= 6 ? $f['their_pot_all'] : 0;
    $f['my_pot_x_ctrl'] = $rank >= 3 ? $f['my_pot_all'] : 0;
    $f['removal_x_threat'] = $f['hand_removal'] * $threat;
    $out = [];
    foreach (SWUValueFeatureNames() as $n) $out[$n] = floatval($f[$n]);
    return $out;
}

function SWUValueVector(array $features): array {
    return array_values($features);
}

// POSITION LOGGER (spec §4 row 2, §6). Off unless SWU_VALUE_LOG names a file. At a free-play decision it logs the
// CURRENT position from BOTH seats' views — pure reads, no lookahead, so what is logged is exactly what the game
// produced and logging cannot change a decision. The header is written once per file. Styles per seat come from
// $GLOBALS['SWUValueLogStyles'] (set by the harness from --chooser/--chooser2); a missing one falls back to the
// acting seat's own style from $ctx. SWU_VALUE_LOG_RATE (default 1) keeps a row pair with that probability, decided
// by a hash of (SWU_VALUE_SEED, decision #) — deterministic, and the engine RNG is never touched.
function SWUValueLogPosition(array $ctx): void {
    // CLI sweeps keep the env var and are byte-for-byte unchanged; a live Arenabot game logs into
    // BotData instead (spec 2026-09-23 §1.4, the optional fourth stream for the value model).
    $path = strval(getenv('SWU_VALUE_LOG') ?: '');
    $liveGame = false;
    if ($path === '' && function_exists('SWUBotDataDir')) {
        $d = SWUBotDataDir();
        // ⚠ Only take the BotData path if the directory really exists or can be made. Unlike a CLI
        // sweep, this runs on a LIVE request: a bare file_put_contents warning here lands in the middle
        // of the game's JSON response and the client's parse fails. CreateGame.php:29-38 documents that
        // exact failure ("Unexpected server response while joining queue") from the same mistake.
        if ($d !== '' && (is_dir($d) || @mkdir($d, 0777, true) || is_dir($d))) {
            $path = $d . '/value.jsonl';
            $liveGame = true;
        }
    }
    if ($path === '' || ($ctx['kind'] ?? '') !== 'free-play') return;
    $n = $GLOBALS['SWUValueLogN'] = intval($GLOBALS['SWUValueLogN'] ?? 0) + 1;
    $rate = getenv('SWU_VALUE_LOG_RATE');
    $rate = ($rate === false || $rate === '') ? 1.0 : floatval($rate);
    if ($rate < 1.0 && (crc32(strval(getenv('SWU_VALUE_SEED') ?: '') . '|' . $n) % 1000000) / 1000000.0 >= $rate) return;
    $lines = '';
    if (!is_file($path) || filesize($path) === 0) {
        $lines .= json_encode(['h' => 1, 'names' => SWUValueFeatureNames(), 'featureVersion' => SWUValueFeatureVersion()]) . "\n";
    }
    $styles = $GLOBALS['SWUValueLogStyles'] ?? [];
    foreach ([1, 2] as $s) {
        $style = strval($styles[$s] ?? ($s === intval($ctx['seat']) ? $ctx['style'] : 'midrange'));
        $lines .= json_encode(array_merge([$s], SWUValueVector(SWUValueFeatures($s, SWUValueHandSnapshot($s), $style)))) . "\n";
    }
    // A live game's write is silenced and locked (an overlapping bot poll and human action share the
    // file); a CLI sweep keeps the original bare call so a broken --out path still fails loudly there.
    if ($liveGame) @file_put_contents($path, $lines, FILE_APPEND | LOCK_EX);
    else file_put_contents($path, $lines, FILE_APPEND);
}
