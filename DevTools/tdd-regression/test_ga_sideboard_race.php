<?php
// http://localhost:3200/TCGEngine/DevTools/tdd-regression/test_ga_sideboard_race.php
//
// REGRESSION GUARD (GrandArchiveSim) for the "sideboard submit → waiting player hangs" bug in both
// directions. GA's SubmitSideboard.php is a thin wrapper over the SHARED Core/Match sideboard logic
// (MatchSubmitSideboardDeck + MatchMaybeSpawnAfterSideboard + the in_progress recovery), so this
// drives that shared logic under GA's rootName/storage with a stub setupGame (GA's real setupGame
// creates a full game + needs a resolved deck, which we don't in a self-contained test). The
// true-concurrency double-spawn guard lives in test_swusim_sideboard_race.php (identical Core code).
header('Content-Type: text/plain');
include __DIR__ . '/../../Core/Match/MatchFlow.php';

$SPAWNED = [];
MatchRegisterHooks('GrandArchiveSim', [
  'resolveLobbyDecks' => function($l){ return null; },
  'validateDeck'      => function($d,$f){ return true; },
  'setupGame'         => function($lobby,$opts) use (&$SPAWNED){
      $n = 'GAsbtest'.(count($SPAWNED)+1).substr(md5(uniqid('',true)),0,6);
      $SPAWNED[] = $n;
      @mkdir(__DIR__.'/../../GrandArchiveSim/Games/'.$n, 0777, true); // so MatchWriteRef/NextGame pointer writes land
      return $n;
  },
]);

function _mkSideboarding(){
  $mid = MatchCreate('GrandArchiveSim','open','bo3',[
    1 => ['originalDeck'=>['mainDeck'=>['cardX']], 'authKey'=>'a1'],
    2 => ['originalDeck'=>['mainDeck'=>['cardY']], 'authKey'=>'a2'],
  ]);
  MatchBeginSideboarding('GrandArchiveSim', $mid, 1); // loser (seat 1) first
  return $mid;
}
// mimic the endpoint: submit a seat's (already-validated) deck, then try to advance; return the
// nextGameName a waiting client would receive (direct spawn OR the in_progress recovery).
function _submit($mid, $seat, $deck){
  MatchSubmitSideboardDeck('GrandArchiveSim', $mid, $seat, $deck);
  $next = MatchMaybeSpawnAfterSideboard('GrandArchiveSim', $mid);
  if ($next) return $next;
  // recovery branch (as GA/SubmitSideboard.php does): opponent already spawned while we polled
  $m = MatchRead('GrandArchiveSim', $mid);
  if (($m['state']??'')==='in_progress' && !empty($m['games'])) return strval($m['games'][count($m['games'])-1]['gameName']);
  return null;
}

$checks=[];
$deck1=['mainDeck'=>['cardX']]; $deck2=['mainDeck'=>['cardY']];

// ── Ordering A: seat 1 first ─────────────────────────────────────────────────
$mA=_mkSideboarding();
$a1=_submit($mA,1,$deck1);                     // waiting — no game yet
$checks['A seat1-first: no game yet']       = ($a1===null);
$a2=_submit($mA,2,$deck2);                     // both in → spawns
$checks['A seat2: spawned next game']        = !empty($a2);
$a1poll=_submit($mA,1,$deck1);                 // waiting seat's poll → recovery
$checks['A seat1 poll: gets nextGameName']    = !empty($a1poll);
$checks['A seat1 poll: same game']            = (strval($a1poll)===strval($a2));
$mAf=MatchRead('GrandArchiveSim',$mA);
$checks['A: exactly one game spawned']        = (count($mAf['games'])===1) && (($mAf['state']??'')==='in_progress');
$checks['A: MatchRef pointer written']        = is_array(MatchReadRef('GrandArchiveSim',$a2)) && MatchReadRef('GrandArchiveSim',$a2)['matchId']===$mA;

// ── Ordering B: seat 2 first ─────────────────────────────────────────────────
$mB=_mkSideboarding();
$b2=_submit($mB,2,$deck2);
$checks['B seat2-first: no game yet']         = ($b2===null);
$b1=_submit($mB,1,$deck1);
$checks['B seat1: spawned next game']         = !empty($b1);
$b2poll=_submit($mB,2,$deck2);
$checks['B seat2 poll: gets nextGameName']     = !empty($b2poll);
$checks['B seat2 poll: same game']             = (strval($b2poll)===strval($b1));

// ── Idempotent advance: extra MaybeSpawn calls never spawn a second game ──────
$mC=_mkSideboarding();
MatchSubmitSideboardDeck('GrandArchiveSim',$mC,1,$deck1);
MatchSubmitSideboardDeck('GrandArchiveSim',$mC,2,$deck2);
$n1=MatchMaybeSpawnAfterSideboard('GrandArchiveSim',$mC);
$n2=MatchMaybeSpawnAfterSideboard('GrandArchiveSim',$mC); // must be a no-op
$n3=MatchMaybeSpawnAfterSideboard('GrandArchiveSim',$mC);
$mCf=MatchRead('GrandArchiveSim',$mC);
$checks['C: idempotent — one game after 3 spawn calls'] = (count($mCf['games'])===1) && !empty($n1) && empty($n2) && empty($n3);

$fails=array_keys(array_filter($checks,fn($v)=>$v!==true));
echo empty($fails) ? "PASS (".count($checks)." checks)\n"
  : "FAIL: ".implode(', ',$fails)."\n a2=".json_encode($a2)." a1poll=".json_encode($a1poll)."\n";
