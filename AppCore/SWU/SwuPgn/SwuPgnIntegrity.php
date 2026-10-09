<?php
// SWU-PGN: the keyframe integrity gate (spec §14). See SwuPgn.php.
//
// Fold forward; at every keyframe compare the running fold against it, record each difference,
// then snap to the keyframe and carry on. `expected` is the keyframe (the engine's truth), `got` is
// what folding produced. A field the keyframe does not carry is skipped: absent means "not
// recorded", never zero. `active` is keyframe-supplied and deliberately never compared.
//
// A mismatch whose side is ABSENT in the state carries no `expected` / `got` key at all, rather than
// null: absent and null are different answers to "what did the keyframe say".

// A key's value, or this marker when the key is absent.
function _SwuPgnAbsent(): object { static $absent; return $absent ??= new stdClass(); }
function _SwuPgnGet($arr, string $key) {
  return (is_array($arr) && array_key_exists($key, $arr)) ? $arr[$key] : _SwuPgnAbsent();
}
// JavaScript `!==` on JSON values: absent and null differ; arrays compare by their JSON text.
function _SwuPgnNe($a, $b): bool {
  $absent = _SwuPgnAbsent();
  if ($a === $absent || $b === $absent) return ($a === $absent) !== ($b === $absent);
  return $a !== $b;
}

// Order-free comparison of two id lists; a missing list is an empty one.
function _SwuPgnSameSet($a, $b): bool {
  $norm = function ($x) { $l = _SwuPgnIsList($x) ? array_map('strval', $x) : []; sort($l, SORT_STRING); return $l; };
  return $norm($a) === $norm($b);
}

function _SwuPgnMismatch(string $seq, string $path, $expected, $got): array {
  $m = ['seq' => $seq, 'path' => $path];
  if ($expected !== _SwuPgnAbsent()) $m['expected'] = $expected;
  if ($got !== _SwuPgnAbsent()) $m['got'] = $got;
  return $m;
}

// Compare $field of two records; on a difference, record it under "$path.$field".
function _SwuPgnCompareField(array &$out, string $seq, string $path, $e, $g, string $field): void {
  $ev = _SwuPgnGet($e, $field); $gv = _SwuPgnGet($g, $field);
  if (_SwuPgnNe($ev, $gv)) $out[] = _SwuPgnMismatch($seq, "$path.$field", $ev, $gv);
}

function _SwuPgnDiffCard(string $seq, int $seat, array $e, array $g): array {
  $out = [];
  $base = "players.$seat.cards[" . ($e['id'] ?? '') . ']';
  foreach (['zone', 'damage', 'exhausted', 'shields', 'experience'] as $f) _SwuPgnCompareField($out, $seq, $base, $e, $g, $f);
  // By JSON text, so key ORDER counts too, as it does for the reference reader.
  $es = $e['statusTokens'] ?? []; $gs = $g['statusTokens'] ?? [];
  if ($es !== $gs) $out[] = _SwuPgnMismatch($seq, "$base.statusTokens", _SwuPgnGet($e, 'statusTokens'), _SwuPgnGet($g, 'statusTokens'));
  foreach (['upgrades', 'captured'] as $f) {
    if (!_SwuPgnSameSet($e[$f] ?? null, $g[$f] ?? null)) $out[] = _SwuPgnMismatch($seq, "$base.$f", _SwuPgnGet($e, $f), _SwuPgnGet($g, $f));
  }
  // Live stats are compared only when the keyframe recorded them.
  foreach (['power', 'hp'] as $f) {
    if (is_int($e[$f] ?? null)) _SwuPgnCompareField($out, $seq, $base, $e, $g, $f);
  }
  if (_SwuPgnIsList($e['keywords'] ?? null) && !_SwuPgnSameSet($e['keywords'], $g['keywords'] ?? null)) {
    $out[] = _SwuPgnMismatch($seq, "$base.keywords", $e['keywords'], _SwuPgnGet($g, 'keywords'));
  }
  return $out;
}

function _SwuPgnDiffSeat(string $seq, int $seat, array $e, array $g, bool $pastFirstKeyframe): array {
  $out = [];
  $at = "players.$seat";
  if ($pastFirstKeyframe) _SwuPgnCompareField($out, $seq, $at, $e, $g, 'baseHp');
  foreach (['handSize', 'resourcesReady', 'resourcesExhausted', 'credits', 'hasForce'] as $f) _SwuPgnCompareField($out, $seq, $at, $e, $g, $f);
  // A hand is unordered; a discard pile is ordered.
  if (!_SwuPgnSameSet($e['hand'] ?? null, $g['hand'] ?? null)) $out[] = _SwuPgnMismatch($seq, "$at.hand", _SwuPgnGet($e, 'hand'), _SwuPgnGet($g, 'hand'));
  if (($e['discard'] ?? []) !== ($g['discard'] ?? [])) $out[] = _SwuPgnMismatch($seq, "$at.discard", _SwuPgnGet($e, 'discard'), _SwuPgnGet($g, 'discard'));
  // Resource-row membership, as a set, only when the keyframe carries it.
  if (_SwuPgnIsList($e['resources'] ?? null) && !_SwuPgnSameSet($e['resources'], $g['resources'] ?? null)) {
    $out[] = _SwuPgnMismatch($seq, "$at.resources", $e['resources'], _SwuPgnGet($g, 'resources'));
  }
  if (is_bool($e['baseEpicActionUsed'] ?? null) && $e['baseEpicActionUsed'] !== ($g['baseEpicActionUsed'] ?? false)) {
    $out[] = _SwuPgnMismatch($seq, "$at.baseEpicActionUsed", $e['baseEpicActionUsed'], $g['baseEpicActionUsed'] ?? false);
  }
  // Like baseHp, the starting deck is not in the stream: the first keyframe supplies it.
  if ($pastFirstKeyframe && is_int($e['deckSize'] ?? null)) _SwuPgnCompareField($out, $seq, $at, $e, $g, 'deckSize');
  // The leader, once both sides have named one.
  if (_SwuPgnTruthy($e['leader'] ?? null) && _SwuPgnTruthy($g['leader'] ?? null)) {
    $el = $e['leader']; $gl = $g['leader'];
    if (is_bool($el['onStartingSide'] ?? null)) _SwuPgnCompareField($out, $seq, "$at.leader", $el, $gl, 'onStartingSide');
    foreach (['id', 'deployed', 'exhausted', 'epicActionUsed'] as $f) _SwuPgnCompareField($out, $seq, "$at.leader", $el, $gl, $f);
  }

  // In-play cards matched by id, reported both ways.
  $gotById = [];
  foreach ($g['cards'] as $c) $gotById[(string)($c['id'] ?? '')] = $c;
  $expectedIds = [];
  foreach ($e['cards'] as $ec) {
    $id = (string)($ec['id'] ?? '');
    $expectedIds[$id] = true;
    if (!array_key_exists($id, $gotById)) { $out[] = _SwuPgnMismatch($seq, "$at.cards[$id]", 'present', 'absent'); continue; }
    array_push($out, ..._SwuPgnDiffCard($seq, $seat, $ec, $gotById[$id]));
  }
  foreach ($g['cards'] as $gc) {
    $id = (string)($gc['id'] ?? '');
    if (!array_key_exists($id, $expectedIds)) $out[] = _SwuPgnMismatch($seq, "$at.cards[$id]", 'absent', 'present');
  }
  return $out;
}

function _SwuPgnDiff(string $seq, array $expected, array $got, bool $pastFirstKeyframe): array {
  $out = [];
  if (is_bool($expected['initiativeTaken'] ?? null) && $expected['initiativeTaken'] !== ($got['initiativeTaken'] ?? false)) {
    $out[] = _SwuPgnMismatch($seq, 'initiativeTaken', $expected['initiativeTaken'], $got['initiativeTaken'] ?? false);
  }
  foreach ([1, 2] as $seat) {
    $e = $expected['players'][$seat] ?? null;
    $g = $got['players'][$seat] ?? null;
    if (is_array($e) && is_array($g)) array_push($out, ..._SwuPgnDiffSeat($seq, $seat, $e, $g, $pastFirstKeyframe));
  }
  return $out;
}

/**
 * The spec §14 gate: {ok, mismatches[]}. `baseHp` and `deckSize` are exempt at the FIRST keyframe
 * only — the stream carries neither starting value, so the first keyframe is what supplies them.
 * Every other field is compared at every keyframe.
 */
function SwuPgnCheckKeyframes(array $events): array {
  $s = SwuPgnEmptyState();
  $mismatches = [];
  $seenKeyframe = false;
  foreach ($events as $e) {
    $t = is_array($e) ? ($e['t'] ?? null) : null;
    if (($t === 'ROUND_START' || $t === 'ROUND_END') && _SwuPgnTruthy($e['keyframe'] ?? null)) {
      $seq = (string)($e['seq'] ?? '');
      if (!SwuPgnIsCompleteKeyframe($e['keyframe'])) {
        // A damaged checkpoint: reported, never snapped to; folding carries on.
        $mismatches[] = _SwuPgnMismatch($seq, 'keyframe', 'both seats, with array cards/hand/discard', 'damaged keyframe (ignored)');
        $s = SwuPgnReduce($s, $e);
        continue;
      }
      // A ROUND_START keyframe describes a round that has begun: the counter is available again.
      if ($t === 'ROUND_START') $s['initiativeTaken'] = false;
      array_push($mismatches, ..._SwuPgnDiff($seq, $e['keyframe'], $s, $seenKeyframe));
      $seenKeyframe = true;
      $s = $e['keyframe'];
      continue;
    }
    $s = SwuPgnReduce($s, $e);
  }
  return ['ok' => count($mismatches) === 0, 'mismatches' => $mismatches];
}
