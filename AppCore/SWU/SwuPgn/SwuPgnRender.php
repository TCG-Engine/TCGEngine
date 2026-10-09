<?php
// SWU-PGN: render the human-readable %%% STORY (spec §16). See SwuPgn.php.
//
// Numbered lines are actions a player chose; indented lines are consequences of the action above;
// a round banner carries the board from that round's keyframe. The exact wording is advisory in the
// spec, but this renderer matches the reference byte for byte so a file's own STORY can be checked.

const SWUPGN_RULE_WIDTH = 78;

// A resolver backed by the file's own %%% CARDS index: id (copy suffix stripped) → display name,
// falling back to the id itself so an incomplete index degrades to raw ids, never a lost line.
function SwuPgnIndexResolver(array $cards = []): callable {
  $byId = [];
  foreach ($cards as $c) {
    if (is_array($c) && array_key_exists('id', $c)) $byId[(string)$c['id']] = $c['name'] ?? null;   // a repeated id: the last entry wins
  }
  return function ($id) use ($byId) {
    $base = SwuPgnBaseId($id);
    return $byId[$base] ?? $base;
  };
}

// Whether a record is a player's own action (spec §9, §16): it gets a number in the story.
function SwuPgnIsTopLevelAction(array $e): bool {
  $t = $e['t'] ?? null;
  if (!in_array($t, ['PLAY', 'PLAY_EVENT', 'PLAY_UPGRADE', 'PLAY_SMUGGLE', 'DEPLOY_LEADER', 'ATTACK', 'PASS', 'CLAIM_INITIATIVE', 'ABILITY_ACTIVATE'], true)) return false;
  if ($t !== 'ABILITY_ACTIVATE') return true;
  $kind = $e['kind'] ?? null;
  return $kind === 'action' || $kind === 'epic';
}

function _SwuPgnWho($p): string {
  return $p === 1 ? 'Player 1' : ($p === 2 ? 'Player 2' : '');
}

// `${x}` for a JSON value: what a template literal prints.
function _SwuPgnStr($x): string {
  if ($x === null) return 'null';
  if ($x === true) return 'true';
  if ($x === false) return 'false';
  if (is_object($x)) return '[object Object]';
  if (is_array($x)) return array_is_list($x) ? implode(',', array_map('_SwuPgnStr', $x)) : '[object Object]';
  return (string)$x;
}

// `"ASH#110:2"` → `" #2"`, so two copies read apart in prose.
function _SwuPgnCopySuffix($ref): string {
  return preg_match('/:(\d+)$/', _SwuPgnStr($ref), $m) ? " #{$m[1]}" : '';
}

function _SwuPgnName($id, callable $nameOf): string {
  if (is_string($id) && str_starts_with($id, 'base@')) {
    $n = substr($id, 5);
    return _SwuPgnWho(is_numeric($n) ? $n + 0 : null) . "'s base";
  }
  return _SwuPgnStr($nameOf(SwuPgnBaseId(_SwuPgnStr($id)))) . _SwuPgnCopySuffix($id);
}

// One event's story line, or null when the event is mechanism rather than story.
function _SwuPgnLine(array $e, callable $nameOf): ?string {
  $nm = fn($id) => _SwuPgnName($id, $nameOf);
  $list = fn($ids) => implode(', ', array_map($nm, _SwuPgnArr($ids)));
  $p = $e['p'] ?? null;
  $who = _SwuPgnWho($p);
  $other = _SwuPgnWho($p === 1 ? 2 : 1);
  $has = fn(string $k) => array_key_exists($k, $e) && $e[$k] !== null;   // `!= null`
  $cost = $has('cost') ? ' (cost ' . _SwuPgnStr($e['cost']) . ')' : '';
  $card = $e['card'] ?? null;
  $count = $e['count'] ?? null;
  $abs = fn($n) => _SwuPgnStr(is_numeric($n) ? abs($n + 0) : $n);
  switch ($e['t'] ?? null) {
    case 'PLAY': case 'PLAY_SMUGGLE':
      return "$who plays {$nm($card)}" . (_SwuPgnTruthy($e['zone'] ?? null) ? ' to ' . _SwuPgnStr($e['zone']) : '') . $cost;
    case 'PLAY_UPGRADE':
      $where = _SwuPgnTruthy($e['target'] ?? null) ? " on {$nm($e['target'])}" : (_SwuPgnTruthy($e['zone'] ?? null) ? ' to ' . _SwuPgnStr($e['zone']) : '');
      return "$who plays {$nm($card)}$where$cost";
    case 'PLAY_EVENT': return "$who plays {$nm($card)}$cost";
    case 'DEPLOY_LEADER': return "$who deploys {$nm($card)}" . (_SwuPgnTruthy($e['target'] ?? null) ? " as a pilot on {$nm($e['target'])}" : '');
    case 'LEADER_FLIP': return "$who flips {$nm($card)}";
    case 'ATTACK': return "$who attacks " . (($e['defenderType'] ?? null) === 'base' ? "$other's base" : $nm($e['def'] ?? null)) . " with {$nm($e['atk'] ?? null)}";
    case 'PASS': return "$who passes";
    case 'CLAIM_INITIATIVE': return "$who claims initiative";
    case 'DAMAGE': return _SwuPgnStr($e['amt'] ?? null) . " damage to {$nm($e['tgt'] ?? null)} — " . _SwuPgnStr($e['hp'] ?? null) . ' HP left';
    case 'OVERWHELM': return _SwuPgnStr($e['amt'] ?? null) . " Overwhelm damage to $other's base — " . _SwuPgnStr($e['hp'] ?? null) . ' HP left';
    case 'HEAL': return _SwuPgnStr($e['amt'] ?? null) . " healed on {$nm($e['tgt'] ?? null)} — " . _SwuPgnStr($e['hp'] ?? null) . ' HP left';
    case 'DEFEAT': return "{$nm($card)} is defeated" . (_SwuPgnTruthy($e['defeatedBy'] ?? null) ? " by {$nm($e['defeatedBy'])}" : '');
    case 'ABILITY_ACTIVATE':
      $kind = $e['kind'] ?? null;
      return ($kind === 'action' || $kind === 'epic') ? "$who uses {$nm($card)}" . ($kind === 'epic' ? "'s Epic Action" : '') : "{$nm($card)} uses an ability";
    case 'TRIGGER': return "{$nm($card)} triggers";
    case 'STATUS_TOKEN': return "{$nm($card)} " . (is_numeric($count) && $count < 0 ? 'loses' : 'gains') . " {$abs($count)} " . _SwuPgnStr($e['token'] ?? null);
    case 'SHIELD_GAIN': return "{$nm($card)} gains " . _SwuPgnStr($count ?? 1) . ' shield';
    case 'SHIELD_USE': return "{$nm($card)} loses " . _SwuPgnStr($count ?? 1) . ' shield';
    case 'EXPERIENCE_GAIN': return "{$nm($card)} " . (is_numeric($count) && $count < 0 ? 'loses' : 'gains') . " {$abs($count)} experience";
    case 'DRAW': return "$who draws " . _SwuPgnStr($count) . (count(_SwuPgnArr($e['cards'] ?? null)) ? ": {$list($e['cards'])}" : '');
    case 'DISCARD': return "$who discards {$list($e['cards'] ?? null)}";
    case 'RESOURCE': return "$who resources {$nm($card)}";
    case 'REVEAL': return "$who reveals {$list($e['cards'] ?? null)}";
    case 'SEARCH': return _SwuPgnTruthy($e['found'] ?? null) ? "$who searches, finds {$list($e['found'])}" : "$who searches their deck";
    case 'CREATE_TOKEN': return "$who creates {$nm($e['token'] ?? null)} in " . _SwuPgnStr($e['zone'] ?? null);
    case 'CAPTURE': return "$who captures {$nm($card)}" . (_SwuPgnTruthy($e['by'] ?? null) ? " with {$nm($e['by'])}" : '');
    case 'RESCUE': return "$who rescues {$nm($card)}";
    case 'TAKE_CONTROL': return "$who takes control of {$nm($card)}";
    case 'MULLIGAN': return "$who mulligans";
    case 'KEEP_HAND': return "$who keeps their hand";
    case 'GAME_END':
      $winner = $e['winner'] ?? null;
      return '*** ' . ($winner === 'Draw' ? 'Game ends in a draw' : _SwuPgnWho($winner) . ' wins') . ' — ' . _SwuPgnStr($e['reason'] ?? null) . ' ***';
  }
  // MOVE, EXHAUST, READY, the resource counters, STATS, CHOICE, SHUFFLE, PHASE_END, ROUND_END,
  // UNDO (its own marker, handled by the caller) and anything unknown: mechanism, not story.
  return null;
}

// The board at a round boundary, laid out from that round's keyframe.
function _SwuPgnBoardSummary(array $k, callable $nameOf): array {
  $nm = fn($id) => _SwuPgnName($id, $nameOf);
  $out = [];
  foreach ([1, 2] as $seat) {
    $p = $k['players'][$seat] ?? null;
    if (!is_array($p)) { $out[] = " P$seat  (not recorded)"; continue; }
    $total = ($p['resourcesReady'] ?? 0) + ($p['resourcesExhausted'] ?? 0);
    $line = " P$seat  base " . _SwuPgnStr($p['baseHp'] ?? null) . '/' . _SwuPgnStr($p['baseMaxHp'] ?? null) . '   hand ' . _SwuPgnStr($p['handSize'] ?? null)
      . '   resources ' . _SwuPgnStr($p['resourcesReady'] ?? null) . "/$total";
    if (is_int($p['deckSize'] ?? null)) $line .= "   deck {$p['deckSize']}";
    if (_SwuPgnTruthy($p['leader'] ?? null)) {
      $l = $p['leader'];
      $line .= '   leader ' . (_SwuPgnTruthy($l['deployed'] ?? null) ? 'deployed' : (_SwuPgnTruthy($l['exhausted'] ?? null) ? 'exhausted' : 'ready'));
    }
    $out[] = $line;
    foreach (['ground', 'space'] as $zone) {
      $inZone = array_values(array_filter($p['cards'], fn($c) => ($c['zone'] ?? null) === $zone));
      if (!$inZone) continue;
      $rendered = array_map(function ($c) use ($nm) {
        $bits = [];
        $stats = (is_int($c['power'] ?? null) && is_int($c['hp'] ?? null)) ? " {$c['power']}/{$c['hp']}" : '';
        if (_SwuPgnTruthy($c['damage'] ?? null)) $bits[] = _SwuPgnStr($c['damage']) . ' dmg';
        if (_SwuPgnTruthy($c['exhausted'] ?? null)) $bits[] = 'exhausted';
        if (_SwuPgnTruthy($c['shields'] ?? null)) $bits[] = _SwuPgnStr($c['shields']) . ' shield';
        if (_SwuPgnTruthy($c['experience'] ?? null)) $bits[] = _SwuPgnStr($c['experience']) . ' xp';
        foreach ((is_array($c['statusTokens'] ?? null) ? $c['statusTokens'] : []) as $token => $count) $bits[] = _SwuPgnStr($count) . " $token";
        foreach (_SwuPgnArr($c['upgrades'] ?? null) as $up) $bits[] = $nm($up);
        foreach (_SwuPgnArr($c['captured'] ?? null) as $held) $bits[] = "holds {$nm($held)}";
        return $nm($c['id'] ?? null) . $stats . ($bits ? ' [' . implode(', ', $bits) . ']' : '');
      }, $inZone);
      $out[] = "      $zone: " . implode('  ·  ', $rendered);
    }
  }
  return $out;
}

/**
 * Render the story. $nameOf maps a base id to a display name; omitted, the document's own %%% CARDS
 * index is used, so a file that carries one needs no card database.
 */
function SwuPgnRender(array $doc, ?callable $nameOf = null): string {
  $nameOf ??= SwuPgnIndexResolver(_SwuPgnArr($doc['cards'] ?? null));
  $out = [];
  $actionNum = 0;
  foreach ($doc['events'] ?? [] as $e) {
    if (!is_array($e)) continue;
    $t = $e['t'] ?? null;
    if ($t === 'ROUND_START') {
      array_push($out, '', str_repeat('═', SWUPGN_RULE_WIDTH));
      $left = ' ROUND ' . _SwuPgnStr($e['round'] ?? null);
      $keyframe = SwuPgnIsCompleteKeyframe($e['keyframe'] ?? null) ? $e['keyframe'] : null;
      $right = ($keyframe && _SwuPgnTruthy($keyframe['initiative'] ?? null)) ? 'initiative: ' . _SwuPgnWho($keyframe['initiative']) . ' ' : '';
      $out[] = $right !== '' ? str_pad($left, SWUPGN_RULE_WIDTH - strlen($right)) . $right : $left;
      if ($keyframe) array_push($out, ..._SwuPgnBoardSummary($keyframe, $nameOf));
      array_push($out, str_repeat('═', SWUPGN_RULE_WIDTH), '');
      $actionNum = 0;
      continue;
    }
    if ($t === 'UNDO') {
      $out[] = '    ·· ' . (_SwuPgnTruthy($e['by'] ?? null) ? _SwuPgnWho($e['by']) : 'A player') . ' undid back to ' . _SwuPgnStr($e['at'] ?? null) . ' ··';
      continue;
    }
    if ($t === 'PHASE_START') {
      $out[] = ' ── ' . _SwuPgnStr($e['phase'] ?? null) . ' ──';
      $actionNum = 0;
      continue;
    }
    $text = _SwuPgnLine($e, $nameOf);
    if ($text === null) continue;
    if (SwuPgnIsTopLevelAction($e)) {
      $actionNum++;
      $out[] = ' ' . str_pad((string)$actionNum, 2, ' ', STR_PAD_LEFT) . ". $text";
    } else {
      $out[] = "       ↳ $text";
    }
  }
  return implode("\n", $out);
}
