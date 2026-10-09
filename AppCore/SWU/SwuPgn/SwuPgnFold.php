<?php
// SWU-PGN: fold events into a board (spec §11–§12). See SwuPgn.php.
//
// MOVE is the single source of truth for every zone: hand, discard, deck count, the resource row,
// credits, the Force and arena membership. DRAW / DISCARD / RESOURCE / PLAY / DEFEAT are summaries
// beside their MOVEs and are idempotent by id, so a card reported twice is never counted twice.
//
// A .swupgn is UNTRUSTED input (players upload them). Nothing here may throw on a malformed record:
// a non-seat `p`, a non-array list or a damaged keyframe degrades to "ignore that part", and every
// list the fold grows is capped so a crafted file cannot make it do unbounded work.

const SWUPGN_MAX_ZONE_LIST = 1000;
const SWUPGN_ARENAS = ['ground', 'space'];

function _SwuPgnEmptyPlayer(int $seat): array {
  return [
    'seat' => $seat, 'baseHp' => 30, 'baseMaxHp' => 30, 'handSize' => 0, 'hand' => [],
    'resourcesReady' => 0, 'resourcesExhausted' => 0, 'credits' => 0, 'hasForce' => false,
    'discard' => [], 'cards' => [],
  ];
}

// The starting board. Base HP 30 is a PLACEHOLDER: no event carries a base's starting HP, so it is
// a guess until the first keyframe supplies the real value (spec §11).
function SwuPgnEmptyState(): array {
  return ['round' => 0, 'phase' => 'setup', 'initiative' => null, 'initiativeTaken' => false,
    'players' => [1 => _SwuPgnEmptyPlayer(1), 2 => _SwuPgnEmptyPlayer(2)]];
}

// JavaScript truthiness, which the spec's reference reader branches on: an empty array/object and
// the string "0" are TRUE (PHP's empty() calls them empty); null/false/0/'' are false.
function _SwuPgnTruthy($x): bool {
  return !($x === null || $x === false || $x === 0 || $x === 0.0 || $x === '' || (is_float($x) && is_nan($x)));
}

function _SwuPgnIsSeat($p): bool { return $p === 1 || $p === 2; }
function _SwuPgnIsList($x): bool { return is_array($x) && array_is_list($x); }
function _SwuPgnArr($x): array { return _SwuPgnIsList($x) ? $x : []; }
function _SwuPgnIsArena($zone): bool { return in_array($zone, SWUPGN_ARENAS, true); }

// Copy $key from the event onto $target, or REMOVE it when the event lacks it — what assigning an
// undefined property does to the reference reader's state once serialized.
function _SwuPgnSetOrUnset(array &$target, string $key, array $e, string $eKey): void {
  if (array_key_exists($eKey, $e)) $target[$key] = $e[$eKey];
  else unset($target[$key]);
}

/**
 * True when a keyframe may be snapped to: both seats present, each with array cards/hand/discard,
 * and every card carrying the `upgrades` list the fold dereferences. A keyframe REPLACES the whole
 * state, so one missing a seat would erase that player's board — it is ignored instead (spec §13).
 */
function SwuPgnIsCompleteKeyframe($k): bool {
  if (!is_array($k) || !is_array($k['players'] ?? null)) return false;
  foreach ([1, 2] as $seat) {
    $p = $k['players'][$seat] ?? null;
    if (!is_array($p)) return false;
    if (!_SwuPgnIsList($p['cards'] ?? null) || !_SwuPgnIsList($p['hand'] ?? null) || !_SwuPgnIsList($p['discard'] ?? null)) return false;
    foreach ($p['cards'] as $c) {
      if (!is_array($c) || !_SwuPgnIsList($c['upgrades'] ?? null)) return false;
    }
  }
  return true;
}

// A ROUND_START / ROUND_END whose keyframe the fold snaps to.
function SwuPgnHasSnapKeyframe(array $e): bool {
  $t = $e['t'] ?? null;
  return ($t === 'ROUND_START' || $t === 'ROUND_END') && SwuPgnIsCompleteKeyframe($e['keyframe'] ?? null);
}

// The seat a `base@N` ref points at, or null.
function _SwuPgnSeatOfBaseRef($ref): ?int {
  return (is_string($ref) && preg_match('/^base@([12])$/', $ref, $m)) ? (int)$m[1] : null;
}

// The two reserved token names (spec §6.1): the only things that drive `credits` and `hasForce`.
function _SwuPgnIsCreditToken($id): bool { return is_string($id) && str_starts_with($id, 'TOKEN:credit#'); }
function _SwuPgnIsForceToken($id): bool { return is_string($id) && str_starts_with($id, 'TOKEN:the-force#'); }
function _SwuPgnIsTokenId($id): bool { return is_string($id) && str_starts_with($id, 'TOKEN:'); }

// [seat, index] of the arena card `$id`, searching seat 1 then seat 2.
function _SwuPgnFindCard(array $s, $id): ?array {
  foreach ([1, 2] as $seat) {
    foreach ($s['players'][$seat]['cards'] ?? [] as $i => $c) {
      if (($c['id'] ?? null) === $id) return [$seat, $i];
    }
  }
  return null;
}

// The seat whose leader is `$id`, once a keyframe or a DEPLOY_LEADER has named it.
function _SwuPgnLeaderOwner(array $s, $id): ?int {
  foreach ([1, 2] as $seat) {
    if (isset($s['players'][$seat]['leader']) && ($s['players'][$seat]['leader']['id'] ?? null) === $id) return $seat;
  }
  return null;
}

// Make sure seat $p exists and return it, or null when $p is not a seat.
function _SwuPgnPlayer(array &$s, $p): ?int {
  if (!_SwuPgnIsSeat($p)) return null;
  if (!isset($s['players'][$p])) $s['players'][$p] = _SwuPgnEmptyPlayer($p);
  return $p;
}

function _SwuPgnNewCard(string $id, string $zone): array {
  return ['id' => $id, 'zone' => $zone, 'damage' => 0, 'exhausted' => false, 'upgrades' => [],
    'shields' => 0, 'experience' => 0, 'statusTokens' => [], 'captured' => []];
}

// Put a card in an arena ONCE: the MOVE and the PLAY beside it both report the same arrival.
function _SwuPgnPlaceCard(array &$s, $p, $id, $zone): void {
  if (!is_string($id)) return;
  $loc = _SwuPgnFindCard($s, $id);
  if ($loc) { $s['players'][$loc[0]]['cards'][$loc[1]]['zone'] = $zone; return; }
  $seat = _SwuPgnPlayer($s, $p);
  if ($seat === null || count($s['players'][$seat]['cards']) >= SWUPGN_MAX_ZONE_LIST) return;
  $s['players'][$seat]['cards'][] = _SwuPgnNewCard($id, (string)$zone);
}

function _SwuPgnRemoveFromArenas(array &$s, $id): void {
  $loc = _SwuPgnFindCard($s, $id);
  if ($loc) array_splice($s['players'][$loc[0]]['cards'], $loc[1], 1);
}

// Attach a printed card to its host once. Token upgrades are counters, never in upgrades[].
function _SwuPgnAttachTo(array &$s, $hostId, $id): void {
  if (_SwuPgnIsTokenId($id)) return;
  $loc = _SwuPgnFindCard($s, $hostId);
  if ($loc && !in_array($id, $s['players'][$loc[0]]['cards'][$loc[1]]['upgrades'], true)) {
    $s['players'][$loc[0]]['cards'][$loc[1]]['upgrades'][] = $id;
  }
}

// Take $id off every card's upgrades and captured lists — keyed on the zone transition, not `kind`.
function _SwuPgnDetach(array &$s, $id): void {
  foreach ([1, 2] as $seat) {
    if (!isset($s['players'][$seat])) continue;
    foreach ($s['players'][$seat]['cards'] as $i => $c) {
      $u = array_search($id, $c['upgrades'], true);
      if ($u !== false) array_splice($s['players'][$seat]['cards'][$i]['upgrades'], $u, 1);
      $captured = _SwuPgnArr($c['captured'] ?? null);
      $k = array_search($id, $captured, true);
      if ($k !== false) {
        array_splice($captured, $k, 1);
        $s['players'][$seat]['cards'][$i]['captured'] = $captured;
      }
    }
  }
}

// Move $n resources from ready to exhausted ($n > 0) or back ($n < 0), clamped to what the row holds.
function _SwuPgnShiftResources(array &$ps, int $n): void {
  if ($n > 0) {
    $moved = min($n, $ps['resourcesReady']);
    $ps['resourcesReady'] -= $moved; $ps['resourcesExhausted'] += $moved;
  } elseif ($n < 0) {
    $moved = min(-$n, $ps['resourcesExhausted']);
    $ps['resourcesExhausted'] -= $moved; $ps['resourcesReady'] += $moved;
  }
}

function _SwuPgnCountResource(array &$ps, int $delta, bool $exhausted): void {
  $key = $exhausted ? 'resourcesExhausted' : 'resourcesReady';
  $ps[$key] = max(0, $ps[$key] + $delta);
}

function _SwuPgnCountBaseToken(array &$ps, $id, int $delta): void {
  if (_SwuPgnIsCreditToken($id)) $ps['credits'] = max(0, $ps['credits'] + $delta);
  elseif (_SwuPgnIsForceToken($id)) $ps['hasForce'] = $delta > 0;
}

// Zone-list membership: every id is unique for the game, so adding is idempotent; lists are capped.
function _SwuPgnAddOnce(array &$list, $id): void {
  if (count($list) >= SWUPGN_MAX_ZONE_LIST) return;
  if (!in_array($id, $list, true)) $list[] = $id;
}

function _SwuPgnRemoveOne(array &$list, $id): void {
  $i = array_search($id, $list, true);
  if ($i !== false) array_splice($list, $i, 1);
}

// The resource-row membership list stays ABSENT until a MOVE or a keyframe supplies one.
function &_SwuPgnResourceList(array &$ps): array {
  if (!_SwuPgnIsList($ps['resources'] ?? null)) $ps['resources'] = [];
  return $ps['resources'];
}

// Store `active` only when it names a seat.
function _SwuPgnSetActive(array &$s, array $e): void {
  if (_SwuPgnIsSeat($e['active'] ?? null)) $s['active'] = $e['active'];
}

function _SwuPgnSetExhausted(array &$s, $id, bool $exhausted): void {
  $loc = _SwuPgnFindCard($s, $id);
  if ($loc) $s['players'][$loc[0]]['cards'][$loc[1]]['exhausted'] = $exhausted;
  $owner = _SwuPgnLeaderOwner($s, $id);
  if ($owner !== null) $s['players'][$owner]['leader']['exhausted'] = $exhausted;
}

// The MOVE rule (spec §12.1).
function _SwuPgnApplyMove(array &$s, array $e): void {
  $card = $e['card'] ?? null; $from = $e['from'] ?? null; $to = $e['to'] ?? null;

  // 0. Leaving an arena, or the capture zone, ends every attachment and captivity of this card.
  if ((_SwuPgnIsArena($from) && !_SwuPgnIsArena($to)) || $from === 'capture') _SwuPgnDetach($s, $card);

  if (($e['p'] ?? null) === null) {
    // No seat: counts are unattributable; only a tracked card's zone can be updated.
    $loc = _SwuPgnFindCard($s, $card);
    if ($loc) $s['players'][$loc[0]]['cards'][$loc[1]]['zone'] = $to;
    return;
  }
  $seat = _SwuPgnPlayer($s, $e['p']);
  if ($seat === null) return;
  $ps = &$s['players'][$seat];

  // 1. The hand — count and contents.
  if ($to === 'hand' && $from !== 'hand') { $ps['handSize'] += 1; _SwuPgnAddOnce($ps['hand'], $card); }
  elseif ($from === 'hand' && $to !== 'hand') { $ps['handSize'] = max(0, $ps['handSize'] - 1); _SwuPgnRemoveOne($ps['hand'], $card); }

  // 1a. The discard pile. The MOVE files it; a DEFEAT arrives after and is a no-op.
  if ($to === 'discard' && $from !== 'discard') _SwuPgnAddOnce($ps['discard'], $card);
  elseif ($from === 'discard' && $to !== 'discard') _SwuPgnRemoveOne($ps['discard'], $card);

  // 1b. Deck count, once a keyframe has supplied it.
  if (is_int($ps['deckSize'] ?? null)) {
    if ($to === 'deck' && $from !== 'deck') $ps['deckSize'] += 1;
    elseif ($from === 'deck' && $to !== 'deck') $ps['deckSize'] = max(0, $ps['deckSize'] - 1);
  }

  // 1c. The leader coming home.
  if ($to === 'base' && _SwuPgnIsArena($from)) {
    $owner = _SwuPgnLeaderOwner($s, $card);
    if ($owner !== null) $s['players'][$owner]['leader']['deployed'] = false;
  }

  // 2. Resource row: the counts and the membership.
  if ($to === 'resource' && $from !== 'resource') {
    _SwuPgnCountResource($ps, 1, false);
    $res = &_SwuPgnResourceList($ps); _SwuPgnAddOnce($res, $card); unset($res);
  } elseif ($from === 'resource' && $to !== 'resource') {
    _SwuPgnCountResource($ps, -1, ($e['exhausted'] ?? null) === true);
    $res = &_SwuPgnResourceList($ps); _SwuPgnRemoveOne($res, $card); unset($res);
  }

  // 2b. Credits and the Force.
  if ($to === 'base' && $from !== 'base') _SwuPgnCountBaseToken($ps, $card, 1);
  elseif ($from === 'base' && $to !== 'base') _SwuPgnCountBaseToken($ps, $card, -1);
  unset($ps);

  // 3. Arena membership. An upgrade never has any; it attaches through `attachedTo`.
  if (($e['kind'] ?? null) === 'upgrade') {
    if (_SwuPgnIsArena($to) && _SwuPgnTruthy($e['attachedTo'] ?? null)) _SwuPgnAttachTo($s, $e['attachedTo'], $card);
    $loc = _SwuPgnFindCard($s, $card);
    if ($loc) $s['players'][$loc[0]]['cards'][$loc[1]]['zone'] = $to;
    return;
  }
  $loc = _SwuPgnFindCard($s, $card);
  if (_SwuPgnIsArena($to)) {
    if ($loc) $s['players'][$loc[0]]['cards'][$loc[1]]['zone'] = $to;
    elseif (is_string($card) && count($s['players'][$seat]['cards']) < SWUPGN_MAX_ZONE_LIST) $s['players'][$seat]['cards'][] = _SwuPgnNewCard($card, $to);
  } elseif ($loc && _SwuPgnIsArena($s['players'][$loc[0]]['cards'][$loc[1]]['zone'] ?? null)) {
    _SwuPgnRemoveFromArenas($s, $card);
  } elseif ($loc) {
    $s['players'][$loc[0]]['cards'][$loc[1]]['zone'] = $to;
  }
}

function _SwuPgnInt($x): int { return is_int($x) ? $x : ((is_float($x) && is_finite($x)) ? (int)$x : 0); }

/** Apply one event to a state and return the new state (spec §12.2). Unknown types change nothing. */
function SwuPgnReduce(array $s, $e): array {
  if (!is_array($e)) return $s;
  $p = $e['p'] ?? null;
  switch ($e['t'] ?? null) {
    case 'ROUND_START':
      _SwuPgnSetOrUnset($s, 'round', $e, 'round');
      $s['initiativeTaken'] = false;
      _SwuPgnSetActive($s, $e);
      break;
    case 'PHASE_START':
      _SwuPgnSetOrUnset($s, 'phase', $e, 'phase');
      _SwuPgnSetActive($s, $e);
      break;
    case 'CLAIM_INITIATIVE':
      _SwuPgnSetOrUnset($s, 'initiative', $e, 'p');
      $s['initiativeTaken'] = true;
      break;
    case 'PLAY': case 'PLAY_SMUGGLE':
      _SwuPgnPlaceCard($s, $p, $e['card'] ?? null, $e['zone'] ?? 'ground');
      break;
    case 'PLAY_EVENT':
      if (_SwuPgnPlayer($s, $p) !== null) _SwuPgnAddOnce($s['players'][$p]['discard'], $e['card'] ?? null);
      break;
    case 'PLAY_UPGRADE':
      // Never an arena card: with no tracked host the attachment is simply not modelled.
      if (_SwuPgnTruthy($e['target'] ?? null)) _SwuPgnAttachTo($s, $e['target'], $e['card'] ?? null);
      break;
    case 'DEPLOY_LEADER':
      if (_SwuPgnPlayer($s, $p) !== null) {
        $prev = $s['players'][$p]['leader'] ?? null;
        $s['players'][$p]['leader'] = [
          'id' => $e['card'] ?? null, 'deployed' => true, 'exhausted' => false,
          'epicActionUsed' => (is_array($prev) && ($prev['id'] ?? null) === ($e['card'] ?? null) && _SwuPgnTruthy($prev['epicActionUsed'] ?? null)) || ($e['epic'] ?? null) === true,
        ];
      }
      if (($e['kind'] ?? null) === 'upgrade') {   // deployed as a pilot: an attachment, never a body
        if (_SwuPgnTruthy($e['target'] ?? null)) _SwuPgnAttachTo($s, $e['target'], $e['card'] ?? null);
        break;
      }
      _SwuPgnPlaceCard($s, $p, $e['card'] ?? null, $e['zone'] ?? 'ground');
      break;
    case 'ABILITY_ACTIVATE':
      if (($e['epic'] ?? null) === true) {
        $baseSeat = _SwuPgnSeatOfBaseRef($e['card'] ?? null);
        if ($baseSeat !== null) { _SwuPgnPlayer($s, $baseSeat); $s['players'][$baseSeat]['baseEpicActionUsed'] = true; break; }
        $owner = _SwuPgnLeaderOwner($s, $e['card'] ?? null);
        if ($owner !== null) $s['players'][$owner]['leader']['epicActionUsed'] = true;
      }
      break;
    case 'LEADER_FLIP':
      $owner = _SwuPgnLeaderOwner($s, $e['card'] ?? null) ?? _SwuPgnPlayer($s, $p);
      if ($owner !== null) {
        if (!isset($s['players'][$owner]['leader'])) {
          $s['players'][$owner]['leader'] = ['id' => $e['card'] ?? null, 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false];
        }
        _SwuPgnSetOrUnset($s['players'][$owner]['leader'], 'onStartingSide', $e, 'onStartingSide');
      }
      break;
    case 'STATS':
      $loc = _SwuPgnFindCard($s, $e['card'] ?? null);
      if ($loc) {
        $c = &$s['players'][$loc[0]]['cards'][$loc[1]];
        _SwuPgnSetOrUnset($c, 'power', $e, 'power');
        _SwuPgnSetOrUnset($c, 'hp', $e, 'hp');
        if (_SwuPgnIsList($e['keywords'] ?? null)) {
          $kw = array_map('strval', $e['keywords']);
          sort($kw, SORT_STRING);
          $c['keywords'] = $kw;
        }
        unset($c);
      }
      break;
    case 'TAKE_CONTROL':
      if (_SwuPgnPlayer($s, $p) === null) break;
      $zone = $e['zone'] ?? null;
      if ($zone === 'resource' || $zone === 'base') {
        $from = $e['from'] ?? null;
        if (_SwuPgnPlayer($s, $from) === null) break;
        if ($zone === 'resource') {
          $ex = ($e['exhausted'] ?? null) === true;
          _SwuPgnCountResource($s['players'][$from], -1, $ex);
          _SwuPgnCountResource($s['players'][$p], 1, $ex);
          $res = &_SwuPgnResourceList($s['players'][$from]); _SwuPgnRemoveOne($res, $e['card'] ?? null); unset($res);
          $res = &_SwuPgnResourceList($s['players'][$p]); _SwuPgnAddOnce($res, $e['card'] ?? null); unset($res);
        } else {
          _SwuPgnCountBaseToken($s['players'][$from], $e['card'] ?? null, -1);
          _SwuPgnCountBaseToken($s['players'][$p], $e['card'] ?? null, 1);
        }
        break;
      }
      if (!_SwuPgnIsArena($zone)) break;   // no zone (an early-1.0 note): nothing to re-seat
      foreach ([1, 2] as $seat) {
        if ($seat === $p || !isset($s['players'][$seat])) continue;
        foreach ($s['players'][$seat]['cards'] as $i => $c) {
          if (($c['id'] ?? null) === ($e['card'] ?? null)) {
            array_splice($s['players'][$seat]['cards'], $i, 1);
            $s['players'][$p]['cards'][] = $c;
            break 2;
          }
        }
      }
      break;
    case 'CAPTURE':
      _SwuPgnRemoveFromArenas($s, $e['card'] ?? null);
      $loc = _SwuPgnTruthy($e['by'] ?? null) ? _SwuPgnFindCard($s, $e['by']) : null;
      if ($loc) {
        $captured = _SwuPgnArr($s['players'][$loc[0]]['cards'][$loc[1]]['captured'] ?? null);
        if (!in_array($e['card'] ?? null, $captured, true)) $captured[] = $e['card'] ?? null;
        $s['players'][$loc[0]]['cards'][$loc[1]]['captured'] = $captured;
      }
      break;
    case 'RESCUE':
      _SwuPgnDetach($s, $e['card'] ?? null);
      break;
    case 'CREATE_TOKEN':
      // Arena zones only: a token named outside an arena is placed by its own MOVE later.
      if (($e['kind'] ?? null) !== 'upgrade' && _SwuPgnIsArena($e['zone'] ?? null)) _SwuPgnPlaceCard($s, $p, $e['token'] ?? null, $e['zone']);
      break;
    case 'EXHAUST_RESOURCES': case 'READY_RESOURCES':
      if (_SwuPgnPlayer($s, $p) !== null) {
        _SwuPgnShiftResources($s['players'][$p], ($e['t'] === 'EXHAUST_RESOURCES' ? 1 : -1) * max(0, _SwuPgnInt($e['amount'] ?? 0)));
      }
      break;
    case 'DAMAGE': case 'HEAL': case 'OVERWHELM':
      $baseSeat = _SwuPgnSeatOfBaseRef($e['tgt'] ?? null);
      if ($baseSeat !== null) {
        _SwuPgnPlayer($s, $baseSeat);
        _SwuPgnSetOrUnset($s['players'][$baseSeat], 'baseHp', $e, 'hp');
      } elseif ($e['t'] !== 'OVERWHELM') {
        $loc = _SwuPgnFindCard($s, $e['tgt'] ?? null);
        if ($loc) {
          $amt = _SwuPgnInt($e['amt'] ?? 0);
          $dmg = &$s['players'][$loc[0]]['cards'][$loc[1]]['damage'];
          $dmg = max(0, _SwuPgnInt($dmg) + ($e['t'] === 'DAMAGE' ? $amt : -$amt));
          unset($dmg);
        }
      }
      break;
    case 'DEFEAT':
      _SwuPgnDetach($s, $e['card'] ?? null);
      foreach ([1, 2] as $seat) {
        if (!isset($s['players'][$seat])) continue;
        foreach ($s['players'][$seat]['cards'] as $i => $c) {
          if (($c['id'] ?? null) === ($e['card'] ?? null)) {
            _SwuPgnAddOnce($s['players'][$seat]['discard'], $c['id']);
            array_splice($s['players'][$seat]['cards'], $i, 1);
            break;
          }
        }
      }
      break;
    case 'EXHAUST': _SwuPgnSetExhausted($s, $e['card'] ?? null, true); break;
    case 'READY': _SwuPgnSetExhausted($s, $e['card'] ?? null, false); break;
    case 'MOVE': _SwuPgnApplyMove($s, $e); break;
    case 'DRAW': case 'DISCARD':
      if (_SwuPgnPlayer($s, $p) !== null) {
        $list = $e['t'] === 'DRAW' ? 'hand' : 'discard';
        foreach (_SwuPgnArr($e['cards'] ?? null) as $c) _SwuPgnAddOnce($s['players'][$p][$list], $c);
      }
      break;
    case 'SHIELD_GAIN': case 'SHIELD_USE': case 'EXPERIENCE_GAIN': case 'STATUS_TOKEN':
      $loc = _SwuPgnFindCard($s, $e['card'] ?? null);
      if (!$loc) break;
      $c = &$s['players'][$loc[0]]['cards'][$loc[1]];
      if ($e['t'] === 'SHIELD_GAIN') $c['shields'] = _SwuPgnInt($c['shields'] ?? 0) + _SwuPgnInt($e['count'] ?? 1);
      elseif ($e['t'] === 'SHIELD_USE') $c['shields'] = max(0, _SwuPgnInt($c['shields'] ?? 0) - _SwuPgnInt($e['count'] ?? 1));
      elseif ($e['t'] === 'EXPERIENCE_GAIN') $c['experience'] = max(0, _SwuPgnInt($c['experience'] ?? 0) + _SwuPgnInt($e['count'] ?? 0));
      else {
        // A token count that reaches 0 is DELETED, not left as {advantage: 0} (the token contract).
        $tokens = is_array($c['statusTokens'] ?? null) ? $c['statusTokens'] : [];
        $token = (string)($e['token'] ?? '');
        $tokens[$token] = max(0, _SwuPgnInt($tokens[$token] ?? 0) + _SwuPgnInt($e['count'] ?? 0));
        $c['statusTokens'] = array_filter($tokens, fn($n) => $n > 0);
      }
      unset($c);
      break;
    // Everything else — ATTACK, PASS, CHOICE, RESOURCE, TRIGGER, UNDO, an unknown type — is a note.
  }
  return $s;
}

function _SwuPgnFoldRange(array $events, int $start, int $end, array $s): array {
  for ($i = $start; $i <= $end; $i++) {
    $e = $events[$i];
    // A keyframe is authoritative: snap to it. A damaged one falls through to its ordinary rule.
    if (is_array($e) && SwuPgnHasSnapKeyframe($e)) { $s = $e['keyframe']; continue; }
    $s = SwuPgnReduce($s, $e);
  }
  return $s;
}

/** The board after every event. */
function SwuPgnFold(array $events): array {
  $events = array_values($events);
  return _SwuPgnFoldRange($events, 0, count($events) - 1, SwuPgnEmptyState());
}

/**
 * The board up to and including the first event whose seq is $seq; an unknown seq folds the whole
 * list (spec §12.3). Starts from the last usable keyframe at or before it, so scrubbing a replay
 * one position at a time stays linear.
 */
function SwuPgnStateAt(array $events, string $seq): array {
  $events = array_values($events);
  $end = count($events) - 1;
  foreach ($events as $i => $e) {
    if (is_array($e) && ($e['seq'] ?? null) === $seq) { $end = $i; break; }
  }
  for ($i = $end; $i >= 0; $i--) {
    if (is_array($events[$i]) && SwuPgnHasSnapKeyframe($events[$i])) {
      return _SwuPgnFoldRange($events, $i + 1, $end, $events[$i]['keyframe']);
    }
  }
  return _SwuPgnFoldRange($events, 0, $end, SwuPgnEmptyState());
}
