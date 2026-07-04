<?php
// Core/Match/Sideboard.default.php — fallback between-games sideboard for sims
// that opt into Bo3 but ship no bespoke card editor. Submits the seat's
// originalDeck unchanged (a valid "no sideboard changes" ready).
// Query params: root, gameName, matchId, seat, authKey (same contract as a sim's SubmitSideboard.php).
require_once __DIR__ . '/MatchFlow.php';

$rootName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['root'] ?? ''));
$matchId  = $_GET['matchId'] ?? '';
$seat     = (int)($_GET['seat'] ?? 0);
$m = ($rootName !== '' && $matchId !== '') ? MatchRead($rootName, $matchId) : null;
$deck = ($m && isset($m['players'][strval($seat)]['originalDeck'])) ? $m['players'][strval($seat)]['originalDeck'] : [];
?><!doctype html><meta charset="utf-8"><title>Sideboard</title>
<body style="font-family:sans-serif;max-width:640px;margin:40px auto">
<h2>Between games</h2>
<p>You may keep your deck as-is. Click ready to continue to the next game.</p>
<button id="ready">Submit &amp; Ready</button>
<script>
document.getElementById('ready').onclick = async () => {
  const p = new URLSearchParams(location.search);
  const fd = new FormData();
  fd.append('gameName', p.get('gameName')); fd.append('matchId', p.get('matchId'));
  fd.append('seat', p.get('seat')); fd.append('authKey', p.get('authKey'));
  fd.append('deck', JSON.stringify(<?php echo json_encode(array_values((array)$deck)); ?>));
  const r = await (await fetch('./SubmitSideboard.php', {method:'POST', body:fd})).json();
  if (r && r.nextGameName) location.href = './NextTurn.php?gameName=' + r.nextGameName;
  else document.body.insertAdjacentHTML('beforeend', '<p>Waiting for opponent…</p>');
};
</script>
