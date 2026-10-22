<?php
// SWU-PGN own-view cut: one seat's file from the all-seeing one (SWU-PGN/1.0 spec §17: with a `Perspective`, the other
// player's hidden cards are blanked).
//
//   your cards                         unchanged information, ids renumbered 1..k per card in your own deck order
//   their cards while hidden           "HIDDEN" (deck / hand / resource); their DRAW.cards = [], SEARCH.found dropped
//   their cards once public            real card, copy numbered k+1, k+2, … in order of appearance (so neither their
//                                      deck order nor their copy count leaks, and no id collides with yours)
//   their CHOICE prompts               left out (an engine prompt can name a card only its chooser may see)
//   their SETUP order / DECKS lists    [] (leader and base are public and kept)
//   keyframes                          their resources list left out (§14 checks it only when present); their hand —
//                                      which §13 requires — is whatever this file's own fold holds there (HIDDEN
//                                      placeholders), so the counts stay exact and the file passes its own check
//   CARDS                              rebuilt to the ids the cut file uses; STORY re-rendered from the cut events
// Copy ids are unique across a game (§6.1), so the seat-2 numbering of an untouched file would start after seat 1's
// copies and leak their count — which is why YOUR ids are renumbered too.

const SWUPGN_HIDDEN = 'HIDDEN';
const SWUPGN_HIDDEN_ZONES = ['deck', 'hand', 'resource'];
const SWUPGN_ID_FIELDS = ['card', 'atk', 'def', 'tgt', 'src', 'target', 'attachedTo', 'by', 'defeatedBy', 'token'];
const SWUPGN_ID_LIST_FIELDS = ['cards', 'offered', 'found'];

function SwuPgnCutPerspective(array $doc, int $seat): array {
    $other = $seat === 1 ? 2 : 1;
    $init = null; $initIdx = null;
    foreach ($doc['setup'] ?? [] as $i => $r) if (is_array($r) && ($r['t'] ?? '') === 'INIT') { $init = $r; $initIdx = $i; break; }
    $mine = is_array($init["p{$seat}DeckOrder"] ?? null) ? $init["p{$seat}DeckOrder"] : [];
    $theirs = array_flip(array_map('strval', is_array($init["p{$other}DeckOrder"] ?? null) ? $init["p{$other}DeckOrder"] : []));

    // Your copies: 1..k per card, in your own original order.
    $map = []; $mineCount = [];
    $byRef = [];
    foreach ($mine as $c) $byRef[SwuPgnBaseId((string)$c)][] = (string)$c;
    foreach ($byRef as $ref => $list) {
        usort($list, fn($a, $b) => (SwuPgnCopyNumber($a) ?? 1) <=> (SwuPgnCopyNumber($b) ?? 1));
        foreach ($list as $i => $c) $map[$c] = $i === 0 ? $ref : "$ref:" . ($i + 1);
        $mineCount[$ref] = count($list);
    }
    $shownCount = [];
    $reveal = function ($id) use (&$map, &$shownCount, $theirs, $mineCount): void {
        if (!is_string($id) || !isset($theirs[$id]) || isset($map[$id])) return;
        $ref = SwuPgnBaseId($id);
        $shownCount[$ref] = ($shownCount[$ref] ?? 0) + 1;
        $n = ($mineCount[$ref] ?? 0) + $shownCount[$ref];
        $map[$id] = $n === 1 ? $ref : "$ref:$n";
    };
    $id = function ($x) use (&$map, $theirs) {
        if (!is_string($x)) return $x;
        if (isset($theirs[$x])) return $map[$x] ?? SWUPGN_HIDDEN;
        return $map[$x] ?? $x;
    };

    $events = [];
    foreach ($doc['events'] ?? [] as $e) {
        if (!is_array($e)) { $events[] = $e; continue; }
        $t = (string)($e['t'] ?? '');
        $p = intval($e['p'] ?? 0);
        // What this record makes public: a card leaving the hidden zones, one the opponent reveals or discards.
        if ($t === 'MOVE' && !in_array($e['to'] ?? '', SWUPGN_HIDDEN_ZONES, true)) $reveal($e['card'] ?? null);
        if (($t === 'REVEAL' || $t === 'DISCARD') && $p === $other) foreach ((array)($e['cards'] ?? []) as $c) $reveal($c);
        if ($t === 'DRAW' && $p === $other) $e['cards'] = [];
        if ($t === 'SEARCH' && $p === $other) unset($e['found']);
        if ($t === 'CHOICE' && $p === $other) unset($e['prompt']);   // an engine prompt can name a card only its chooser sees
        foreach (SWUPGN_ID_FIELDS as $f) if (array_key_exists($f, $e)) $e[$f] = $id($e[$f]);
        foreach (SWUPGN_ID_LIST_FIELDS as $f) if (is_array($e[$f] ?? null)) $e[$f] = array_map($id, $e[$f]);
        if (is_array($e['keyframe'] ?? null)) $e['keyframe'] = _SwuPgnCutKeyframe($e['keyframe'], $other, $id);
        $events[] = $e;
    }

    // The opponent's keyframe hand: what a reader of THIS file folds to at that point.
    foreach ($events as $i => $e) {
        if (!is_array($e) || !is_array($e['keyframe']['players'][strval($other)] ?? null)) continue;
        $state = SwuPgnFold(array_slice($events, 0, $i));
        $events[$i]['keyframe']['players'][strval($other)]['hand'] = array_values($state['players'][$other]['hand'] ?? []);
    }

    $out = $doc;
    $out['events'] = $events;
    $out['headers']['Perspective'] = 'P' . $seat;
    $out['headers']['Seed'] = 'unseeded';
    if ($initIdx !== null) {
        $out['setup'][$initIdx]["p{$seat}DeckOrder"] = array_map($id, $mine);
        $out['setup'][$initIdx]["p{$other}DeckOrder"] = [];
    }
    foreach ($out['decks'] ?? [] as $i => $d) {
        if (!is_array($d) || intval($d['p'] ?? 0) !== $other) continue;
        $out['decks'][$i]['deck'] = [];
        if (array_key_exists('sideboard', $d)) $out['decks'][$i]['sideboard'] = [];
    }
    // CARDS: only the ids this file still uses.
    $used = [];
    $collect = function ($v) use (&$collect, &$used): void {
        if (is_string($v)) { $used[SwuPgnBaseId($v)] = true; return; }
        if (is_array($v) || is_object($v)) foreach ((array)$v as $x) $collect($x);
    };
    $collect([$out['headers'], $out['decks'] ?? [], $out['setup'] ?? [], $out['events']]);
    $out['cards'] = array_values(array_filter($out['cards'] ?? [], fn($c) => is_array($c) && isset($used[(string)($c['id'] ?? '')])));
    if (isset($out['story']) && is_array($out['story'])) $out['story'] = explode("\n", SwuPgnRender($out));
    return $out;
}

function _SwuPgnCutKeyframe(array $kf, int $other, callable $id): array {
    foreach ($kf['players'] ?? [] as $s => $pl) {
        if (!is_array($pl)) continue;
        if (intval($s) === $other) unset($pl['resources']);
        foreach (['hand', 'resources', 'discard'] as $f) if (is_array($pl[$f] ?? null)) $pl[$f] = array_map($id, $pl[$f]);
        if (is_array($pl['leader'] ?? null) && isset($pl['leader']['id'])) $pl['leader']['id'] = $id($pl['leader']['id']);
        foreach ($pl['cards'] ?? [] as $i => $c) {
            if (!is_array($c)) continue;
            $c['id'] = $id($c['id'] ?? null);
            foreach (['upgrades', 'captured'] as $f) if (is_array($c[$f] ?? null)) $c[$f] = array_map($id, $c[$f]);
            $pl['cards'][$i] = $c;
        }
        $kf['players'][$s] = $pl;
    }
    return $kf;
}
