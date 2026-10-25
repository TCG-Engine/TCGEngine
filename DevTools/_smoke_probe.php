<?php
// Throwaway smoke probe (not committed): php DevTools/_smoke_probe.php <cardID>
error_reporting(E_ALL);
ini_set('display_errors', 0);
$repoRoot = dirname(__DIR__);
$rootName = 'GrandArchiveSim';
$cardID = $argv[1];
$attackMode = (($argv[2] ?? '') === 'attack');
$P = (($argv[3] ?? '') === 'p2') ? 2 : 1; $O = 3 - $P;
$errs = []; $GLOBALS['xp'] = [];
set_error_handler(function($no, $str, $file, $line) use (&$errs) {
    if (strpos($str, 'Games/') !== false || strpos($str, 'file_put_contents') !== false) return true;
    $errs[] = basename($file) . ':' . $line . ' ' . substr($str, 0, 110) . (getenv('SMOKE_TRACE') ? ' <= ' . implode(' < ', array_map(fn($f) => ($f['function'] ?? '') . ':' . basename($f['file'] ?? '') . ':' . ($f['line'] ?? ''), array_slice(debug_backtrace(), 1, 6))) : '');
    return true;
});
require_once $repoRoot . '/Core/EngineActionRunner.php';
define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once $repoRoot . '/DevTools/TestAutomationBridge.php';
$seed = 42;
$gameName = 'prd_batch_zzsmoke_' . preg_replace('/[^A-Za-z0-9]/', '', $cardID) . '_' . $seed;
$gameDir = $repoRoot . '/' . $rootName . '/Games/' . $gameName;

function smokeSig() {
    $o = [];
    foreach ([1, 2] as $pp) {
        foreach (['Deck', 'Banish', 'Graveyard', 'Hand', 'Memory', 'Material', 'Mastery', 'GlobalEffects', 'Intent'] as $zn) {
            $z = ($pp == $GLOBALS['P'] ? 'my' : 'their') . $zn; $cnt = 0; $props = [];
            foreach (GetZone($z) as $x) { if ($x && empty($x->removed)) { ++$cnt; if ($zn === 'Mastery' || $zn === 'GlobalEffects' || $zn === 'Intent') $props[] = $x->CardID . json_encode($x->Counters ?? []) . json_encode($x->TurnEffects ?? []); } }
            $o[$pp][$zn] = ($zn === 'Mastery' || $zn === 'GlobalEffects' || $zn === 'Intent') ? $props : $cnt;
        }
        $f = [];
        foreach (GetZone(($pp == $GLOBALS['P'] ? 'my' : 'their') . 'Field') as $x) { if ($x && empty($x->removed)) $f[] = [$x->CardID, intval($x->Damage ?? 0), intval($x->Status ?? 0), array_diff_key(is_array($x->Counters ?? null) ? $x->Counters : [], ['enlighten' => 1, 'charge' => 1, 'preparation' => 1, 'quest' => 1]), array_values(array_filter($x->TurnEffects ?? [], fn($te) => !preg_match('/ACTION_ACTIVATED$|ATTACK_ACTIVATED$/', strval($te)))), $x->Subcards ?? []]; }
        $o[$pp]['Field'] = $f;
    }
    return $o;
}
function smokeNormalize($sig, $P) {
    // hand+memory conserved under reserve payments; the played card leaves hand for graveyard/field/stack
    foreach ([1, 2] as $pp) { $sig[$pp]['HM'] = $sig[$pp]['Hand'] + $sig[$pp]['Memory']; unset($sig[$pp]['Hand'], $sig[$pp]['Memory']); }
    return $sig;
}
function pshow($rootName) { EngineLoadRootRuntime($rootName); ParseGamestate('./' . $rootName . '/'); $GLOBALS['playerID'] = $GLOBALS['P']; }
function smokeDrain($rootName, $gameName, &$log, $max) {
    for ($step = 0; $step < $max; ++$step) {
        pshow($rootName);
        $who = null; $top = null;
        foreach ([1, 2] as $pp) { foreach (GetDecisionQueue($pp) as $d) { $top = $d; $who = $pp; break; } if ($top !== null) break; }
        if ($top === null) return true;
        $t = $top->Type; $param = strval($top->Param);
        if (in_array($t, ['MZCHOOSE', 'MZMAYCHOOSE', 'MZMULTICHOOSE'])) {
            $optStr = ($t === 'MZMULTICHOOSE') ? (explode('|', $param, 3)[2] ?? '') : $param;
            $GLOBALS['playerID'] = $who;
            foreach (explode('&', $optStr) as $opt) {
                $opt = trim(explode('@', $opt)[0]); if ($opt === '' || strpos($opt, '-') === false) continue;
                $o = GetZoneObject($opt);
                if ($o === null || !empty($o->removed)) { if (getenv('SMOKE_DEBUG')) fwrite(STDERR, 'INVALID-CTX who=' . $who . ' type=' . $t . ' param=' . substr($param, 0, 120) . ' gy=' . json_encode(array_map(fn($g) => $g->CardID . ($g->removed ? '(R)' : ''), GetZone('myGraveyard'))) . "\n"); } if ($o === null || !empty($o->removed)) $GLOBALS['xp'][] = 'INVALID:' . $opt . ':' . substr(strval($top->Tooltip ?? ''), 0, 30);
                elseif ($who != $GLOBALS['P'] && preg_match('/^their(Hand|Graveyard|Material|Memory|Banish|Deck)-/', $opt)) $GLOBALS['xp'][] = 'THEIRPRIV:' . $opt . ':' . substr(strval($top->Tooltip ?? ''), 0, 30);
            }
            $GLOBALS['playerID'] = $GLOBALS['P'];
        }
        $ans = null;
        if ($t === 'MZCHOOSE') { $ans = explode('&', $param)[0]; if (strpos($ans, '-') === false && $ans !== '') $ans = $ans . '-0'; }
        elseif ($t === 'MZMAYCHOOSE') { $ans = (strpos($param, '@Activate-') !== false) ? 'PASS' : explode('&', $param)[0]; }
        elseif ($t === 'YESNO') $ans = 'YES';
        elseif ($t === 'MZMULTICHOOSE') { $pp2 = explode('|', $param, 3); $ans = implode('&', array_slice(explode('&', $pp2[2] ?? ''), 0, max(1, intval($pp2[0])))); }
        elseif ($t === 'MZMODAL') $ans = '0';
        elseif ($t === 'NUMBERCHOOSE') { $ans = explode('|', $param)[0]; }
        elseif ($t === 'MZREARRANGE') $ans = $param;
        elseif ($t === 'NAMECARD') $ans = 'Dungeon Guide';
        elseif ($t === 'MZSPLITASSIGN') { $sp = explode('|', $param, 3); $amt = ctype_digit($sp[0]) ? intval($sp[0]) : intval($sp[1] ?? 1); $tg = ctype_digit($sp[0]) ? ($sp[1] ?? '') : $sp[0]; $ans = explode('&', $tg)[0] . ':' . max(1, $amt); }
        elseif ($t === 'CHOOSEZONE') $ans = trim(explode('&', $param)[0]);
        elseif ($t === 'ICONCHOICE') $ans = trim(explode('&', explode('|', $param, 2)[0])[0]);
        else { $log[] = "STUCK:$t:" . substr($param, 0, 30); return false; }
        if (getenv('SMOKE_DEBUG') === '2') fwrite(STDERR, "DRAIN p$who $t " . substr($param, 0, 100) . " => $ans\n");
        $r2 = EngineRunAction(['playerID' => $who, 'mode' => 100, 'buttonInput' => '', 'cardID' => $ans, 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]);
        if (!$r2['success']) { $log[] = 'FAIL:' . $t . ':' . substr($r2['message'] ?? '', 0, 40); return false; }
    }
    $log[] = 'LOOP'; return false;
}
$result = ['id' => $cardID];
try {
    RegressionEnsureDir($gameDir);
    EngineLoadRootRuntime($rootName);
    $GLOBALS['gameName'] = $gameName;
    InitializeGamestate(); SetDeterministicRandomCounter($seed); WriteGamestate('./'); ParseGamestate('./'); SetDeterministicRandomCounter($seed);
    $GLOBALS['bridgeDeterministicDeckShuffle'] = true; $GLOBALS['bridgeDeterministicDeckShuffleSeed'] = $seed;
    $deck = "# Material\n1 Spirit of Fire\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
    $ds = [];
    BridgeLoadDeckForPlayer($rootName, 1, $deck, $ds); BridgeLoadDeckForPlayer($rootName, 2, $deck, $ds);
    $fp = &GetFirstPlayer(); $fp = 1; $tp = &GetTurnPlayer(); $tp = 1; $ct = &GetTurnNumber(); $ct = 1;
    BridgeRunRootSelfplayStartup($rootName); RegressionFlushCurrentGamestate($rootName);
    foreach ([1, 2] as $p) EngineRunAction(['playerID' => $p, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    if ($P === 2) { EngineRunAction(['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]); $pre = []; smokeDrain($rootName, $gameName, $pre, 40); }
    $cls = strval(CardClasses($cardID)); $types = strval(CardType($cardID));
    $patch = function($mz, $props, $player = null) use ($rootName, $P) { pshow($rootName); $GLOBALS['playerID'] = $player ?? $P; $o = GetZoneObject($mz); if ($o) { foreach ($props as $k => $v) $o->$k = $v; WriteGamestate('./' . $rootName . '/'); } };
    $add = function($zone, $cid, $player = null) use ($rootName, $gameName, $P, $O) { if ($zone === 'theirField') { $zone = 'myField'; $player = $O; } return BridgeAddToZone($rootName, $gameName, $zone, $cid, $player ?? $P); };
    $patch('myField-0', ['CardID' => 'enxi6tshtu', 'Damage' => 20, 'Subcards' => ['pNiyaGlIe7', 'tafqldAGRF', 'LMyKyVC2O9', 'he6kd7hocc', 'rz1bqry41l', 'mdwbkuhtjm', '4FtNBFaOJp', 'q3huqj5bba', 'g92bHLtTNl', 'n2jnltv5kl'], 'Counters' => ['_overrides' => ['classes' => $cls !== '' ? $cls : 'WARRIOR', 'element' => (strval(CardElement($cardID)) !== '' ? explode(',', strval(CardElement($cardID)))[0] : 'NORM')], 'level' => 7] + (getenv('SMOKE_NOWINDOW') ? [] : ['preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3])]);
    $add('myField', 'em6eEh9q8y'); $add('myField', 'px60u5n1do'); $add('myField', 'qzzadf9q1v'); $add('myField', 'aqlbuznsz4'); $add('myField', '9ggfiy38t2');
    $add('theirField', 'em6eEh9q8y', 1); $add('theirField', 'px60u5n1do', 1);
    $add('myGraveyard', 'r7oifozaog'); $add('myGraveyard', '3oda2ha4dk'); $add('myGraveyard', '9ggfiy38t2');
    $add('myMemory', 'em6eEh9q8y'); $add('myMemory', 'px60u5n1do');
    $add('myGraveyard', 'em6eEh9q8y', $O); $add('myGraveyard', 'px60u5n1do', $O); $add('myGraveyard', '3oda2ha4dk', $O); $add('myGraveyard', 'r7oifozaog', $O);
    for ($i = 0; $i < 6; ++$i) $add('myHand', 'em6eEh9q8y');
    $log = [];
    $isHandCard = (strpos($types, 'ACTION') !== false) || (strpos($types, 'ATTACK') !== false);
    if (getenv('SMOKE_HANDALL') && strpos($types, 'REGALIA') === false && strpos($types, 'CHAMPION') === false && strpos($types, 'TOKEN') === false && strpos($types, 'MASTERY') === false) $isHandCard = true;
    $isAttackCard = strpos($types, 'ATTACK') !== false;
    $isActionCard = (strpos($types, 'ACTION') !== false);
    $advanceOnly = $attackMode && !$isAttackCard && strpos($types, 'ALLY') === false;
    if ($advanceOnly && $isActionCard) { $result['skip'] = 'action'; throw new \RuntimeException('skip'); }
    if (!$attackMode && $isAttackCard) { $result['skip'] = 'attack'; throw new \RuntimeException('skip'); }
    $advance = function() use ($rootName, $gameName, &$log) {
        foreach ([1, 2] as $pp) {
            EngineRunAction(['playerID' => $pp, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]);
            smokeDrain($rootName, $gameName, $log, 40);
        }
    };
    if ($isHandCard) {
        $r = $add('myHand', $cardID); $mz = $r['mzID'];
        RegressionFlushCurrentGamestate($rootName);
        if (getenv('SMOKE_NOOP')) { pshow($rootName); $GLOBALS['sigBefore'] = smokeNormalize(smokeSig(), $P); }
        $res = EngineRunAction(['playerID' => $P, 'mode' => 10002, 'buttonInput' => '', 'cardID' => $mz . '!FSM!', 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]);
    } else {
        $r = $add('myField', $cardID); $mz = $r['mzID'];
        RegressionFlushCurrentGamestate($rootName);
        $res = ['success' => true];
    }
    if (getenv('SMOKE_DEBUG')) fwrite(STDERR, 'play=' . json_encode($res) . "\n");
    $ok = smokeDrain($rootName, $gameName, $log, 30);
    if ($attackMode && $ok) {
        $advance();
        pshow($rootName);
        if ($isAttackCard) {
            $r = $add('myHand', $cardID); $mz = $r['mzID'];
            RegressionFlushCurrentGamestate($rootName);
            $pr = EngineRunAction(['playerID' => $P, 'mode' => 10002, 'buttonInput' => '', 'cardID' => $mz . '!FSM!', 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]);
            if (getenv('SMOKE_DEBUG')) fwrite(STDERR, 'play ' . $mz . ' => ' . json_encode($pr) . "\n");
        } else if ($advanceOnly) {
            // no attack: the turn advance above already ran every recollection / end-phase trigger
        } else {
            pshow($rootName);
            $field = GetZone('myField'); $fm = null; foreach ($field as $i => $o) if ($o && empty($o->removed) && $o->CardID === $cardID) { $fm = 'myField-' . $i; break; }
            if ($fm !== null) { $obj = GetZoneObject($fm); if ($obj) { $obj->Status = 2; WriteGamestate('./' . $rootName . '/'); } EngineRunAction(['playerID' => $P, 'mode' => 10002, 'buttonInput' => '', 'cardID' => $fm . '!FSM!', 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]); }
        }
        $ok = smokeDrain($rootName, $gameName, $log, 60);
        if (getenv('SMOKE_CALL')) { pshow($rootName); global $cardActivatedAbilities; $GLOBALS['playerID'] = $P; $f = $cardActivatedAbilities[$cardID . ':0'] ?? null; fwrite(STDERR, 'closure=' . ($f ? 'yes' : 'NO') . "\n"); if ($f) { DecisionQueueController::StoreVariable('mzID', 'myHand-0'); $f($P); foreach ([1,2] as $pp) foreach (GetDecisionQueue($pp) as $d) fwrite(STDERR, "AFTERCALL DQ$pp " . $d->Type . ' ' . substr(strval($d->Param), 0, 80) . "\n"); } }
    pshow($rootName); if (getenv('SMOKE_DEBUG')) { $tn = &GetTurnNumber(); fwrite(STDERR, 'turn=' . $tn . ' phase=' . GetCurrentPhase() . ' tp=' . GetTurnPlayer() . ' hand=' . count(GetHand(1)) . ' log=' . json_encode($log) . "\n"); foreach ([1,2] as $pp) foreach (GetDecisionQueue($pp) as $d) fwrite(STDERR, "DQ$pp " . $d->Type . ' ' . substr(strval($d->Param), 0, 80) . "\n"); }
        $oc = GetZoneObject('theirField-0'); $result['oppDmg'] = $oc ? intval($oc->Damage) : null; $td = 0; foreach (GetZone('theirField') as $to) if ($to && empty($to->removed)) $td += intval($to->Damage); $result['oppFieldDmg'] = $td; $result['oppFieldCount'] = count(array_filter(GetZone('theirField'), fn($to) => $to && empty($to->removed))); $result['attacked'] = true;
        $isHandCard = true; // skip the field-ability loop below
    }
    if ($ok && !$isHandCard) {
        // activate each field ability
        pshow($rootName);
        $cnt = function_exists('CardActivateAbilityCount') ? CardActivateAbilityCount($cardID) : 0;
        for ($a = 0; $a < $cnt && $ok; ++$a) {
            pshow($rootName);
            $field = GetZone('myField'); $fm = null; foreach ($field as $i => $o) if ($o && empty($o->removed) && $o->CardID === $cardID) { $fm = 'myField-' . $i; break; }
            if ($fm === null) break;
            $obj = GetZoneObject($fm); if ($obj) { $obj->Status = 2; WriteGamestate('./' . $rootName . '/'); }
            EngineRunAction(['playerID' => $P, 'mode' => 10001, 'buttonInput' => '', 'cardID' => $fm . '!CustomInput!Activate:' . $a, 'chkInput' => [], 'inputText' => ''], $rootName, $gameName, ['updateCache' => false, 'disableRecording' => true]);
            $ok = smokeDrain($rootName, $gameName, $log, 30);
        }
    }
    pshow($rootName); if (getenv('SMOKE_DEBUG')) { fwrite(STDERR, 'lvl=' . PlayerLevel($P) . ' stack=' . count(GetZone('EffectStack')) . ' hand=' . count(array_filter(GetZone('myHand'), fn($g) => $g && empty($g->removed))) . ' phase=' . GetCurrentPhase() . ' champ=' . GetZoneObject('myField-0')->CardID . ' ' . json_encode(GetZoneObject('myField-0')->Counters) . ' oppGY=' . count(array_filter(GetZone('theirGraveyard'), fn($g) => $g && empty($g->removed))) . ' oppBanish=' . count(GetZone('theirBanish')) . ' myGY=' . count(GetZone('myGraveyard')) . ' log=' . json_encode($log) . "\n"); foreach ([1,2] as $pp) foreach (GetDecisionQueue($pp) as $d) fwrite(STDERR, "DQ$pp " . $d->Type . ' ' . substr(strval($d->Param), 0, 80) . "\n"); }
    $result['tp'] = GetTurnPlayer(); $result['gy'] = count(array_filter(GetZone('myGraveyard'), fn($g) => $g && empty($g->removed)));
    if (getenv('SMOKE_NOOP') && !$attackMode && $isHandCard && isset($GLOBALS['sigBefore'])) {
        pshow($rootName); $after = smokeNormalize(smokeSig(), $P); $before = $GLOBALS['sigBefore'];
        // played card: me graveyard may +1 (or field/intent +1 for permanents); ignore those by comparing with tolerance
        $diff = [];
        foreach ([1, 2] as $pp) foreach ($before[$pp] as $k => $v) { if ($v != $after[$pp][$k]) { $diff[] = $pp . $k; if (getenv('SMOKE_NOOP') === '2' && $k === 'Field') $diff[] = json_encode([$v, $after[$pp][$k]]); } }
        $result['diff'] = $diff; $result['sigmd5'] = md5(json_encode($after));
    }
    if (getenv('SMOKE_NOTPLAYED') && $isHandCard && !$attackMode) { $stillInHand = false; foreach (GetZone('myHand') as $ho) if ($ho && empty($ho->removed) && $ho->CardID === $cardID) { $stillInHand = true; break; } if ($stillInHand) $result['notplayed'] = true; }
    $result['ok'] = $ok; $result['log'] = array_slice($log, -4);
} catch (\Throwable $e) {
    if (($result['skip'] ?? '') !== 'attack') $result['exception'] = substr($e->getMessage(), 0, 160) . ' @' . basename($e->getFile()) . ':' . $e->getLine();
}
$result['errs'] = array_values(array_unique($errs)); if (!empty($GLOBALS['xp'])) $result['xp'] = array_values(array_unique($GLOBALS['xp']));
echo json_encode($result) . "\n";
if (is_dir($gameDir)) RegressionDeleteDirRecursive($gameDir);
