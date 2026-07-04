<?php
// Core/Match/Sideboard.default.php — fallback between-games sideboard for sims
// that opt into Bo3 but ship no bespoke card editor. Submits the seat's
// originalDeck unchanged (a valid "no sideboard changes" ready).
// Query params: root, gameName, matchId, seat, authKey (same contract as a sim's SubmitSideboard.php).
require_once __DIR__ . '/MatchFlow.php';

$rootName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['root'] ?? ''));
$matchId  = $_GET['matchId'] ?? '';
$seat     = (int)($_GET['playerID'] ?? $_GET['seat'] ?? 0);
$m = ($rootName !== '' && $matchId !== '') ? MatchRead($rootName, $matchId) : null;
$deck = ($m && isset($m['players'][strval($seat)]['originalDeck'])) ? $m['players'][strval($seat)]['originalDeck'] : [];
?><!doctype html><meta charset="utf-8"><title>Sideboard</title>
<body style="font-family:sans-serif;max-width:640px;margin:40px auto">
<h2>Between games</h2>
<p>You may keep your deck as-is. Click ready to continue to the next game.</p>
<button id="ready">Submit &amp; Ready</button>
<script>
var P = new URLSearchParams(location.search);
var DECK = JSON.stringify(<?php echo json_encode(array_values((array)$deck)); ?>);
var PID = P.get('playerID') || P.get('seat') || '';
function send(){
  var fd = new FormData();
  fd.append('gameName', P.get('gameName')); fd.append('matchId', P.get('matchId'));
  fd.append('playerID', PID); fd.append('seat', PID); fd.append('authKey', P.get('authKey'));
  fd.append('deck', DECK);
  return fetch('./SubmitSideboard.php', {method:'POST', body:fd}).then(function(r){ return r.json(); });
}
function go(next){ location.href = './NextTurn.php?gameName=' + next; }
// A rejected request (transient 500 / non-JSON under load) MUST reschedule — otherwise the waiting
// player hangs forever once they've submitted.
function poll(){
  send().then(function(r){ if(r && r.nextGameName){ go(r.nextGameName); } else { setTimeout(poll, 2000); } })
        .catch(function(){ setTimeout(poll, 2000); });
}
document.getElementById('ready').onclick = function(){
  document.getElementById('ready').disabled = true;
  document.body.insertAdjacentHTML('beforeend', '<p>Submitted — waiting for opponent…</p>');
  send().then(function(r){ if(r && r.nextGameName){ go(r.nextGameName); } else { poll(); } })
        .catch(function(){ poll(); }); // submit hit a transient error — poll re-submits, don't strand
};
</script>
