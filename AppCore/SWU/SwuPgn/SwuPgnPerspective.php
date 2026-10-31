<?php
// SWU-PGN own-view cut: one seat's file from the all-seeing one. An internal feature (the 1.0 text defines no Perspective
// file a writer may publish, §21.1 / [W-36]); it hides what §21.1 says a perspective rule must hide.
//
//   your cards                         unchanged information, ids renumbered 1..k per card in your own deck order
//   their cards while hidden           "HIDDEN" (deck / hand / resource); their DRAW.cards and LOOK.cards = [],
//                                      SEARCH.found / seen dropped, a blind CHOICE's offered cards of theirs all HIDDEN
//   their cards once public            real card, copy numbered k+1, k+2, … in order of appearance (so neither their
//                                      deck order nor their copy count leaks, and no id collides with yours);
//                                      back in their deck it is forgotten — shown again, it is a new id; a deck card
//                                      you looked at is forgotten when they shuffle
//   what makes one public              a MOVE out of the hidden zones, their REVEAL / DISCARD, their ABILITY_ACTIVATE /
//                                      TRIGGER of it (CR 7.6.3a), your LOOK / SEARCH seeing it, your TAKE_CONTROL of it
//   effects                            EFFECT_START.targets / src mapped like any id; EFFECT_END.targets and keyframe
//                                      effect targets are named as their EFFECT_START named them, so the effect ends
//                                      what it started on (no effect-end-unknown in the cut)
//   their CHOICE prompts               left out (an engine prompt can name a card only its chooser may see)
//   NAME                               kept (naming a card is public)
//   their SETUP order / DECKS lists    [] (leader and base are public and kept)
//   keyframes                          their resources list left out (§14 checks it only when present); their hand —
//                                      which §13 requires — is whatever this file's own fold holds there (HIDDEN
//                                      placeholders), so the counts stay exact and the file passes its own check;
//                                      effects, abilityUses, foreignResources, copyOf, upgradeControllers keys mapped
//   CARDS                              rebuilt to the ids the cut file uses; STORY re-rendered from the cut events
// Copy ids are unique across a game (§6.1), so the seat-2 numbering of an untouched file would start after seat 1's
// copies and leak their count — which is why YOUR ids are renumbered too.

const SWUPGN_HIDDEN = 'HIDDEN';
const SWUPGN_HIDDEN_ZONES = ['deck', 'hand', 'resource'];
const SWUPGN_ID_FIELDS = ['card', 'atk', 'def', 'tgt', 'src', 'target', 'attachedTo', 'by', 'defeatedBy', 'token', 'grantedBy', 'usedBy', 'copyOf'];
const SWUPGN_ID_LIST_FIELDS = ['cards', 'offered', 'found', 'seen', 'targets'];

function SwuPgnCutPerspective(array $doc, int $seat): array {
    $other = $seat === 1 ? 2 : 1;
    $cut = _SwuPgnCutRecords($doc, $seat);
    $events = $cut['events'];
    $id = $cut['id'];
    $init = $cut['init']; $initIdx = $cut['initIdx'];

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
        $out['setup'][$initIdx]["p{$seat}DeckOrder"] = array_map($id, $cut['mine']);
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
        if (is_array($v) || is_object($v)) foreach ((array)$v as $k => $x) { if (is_string($k)) $used[SwuPgnBaseId($k)] = true; $collect($x); }
    };
    $collect([$out['headers'], $out['decks'] ?? [], $out['setup'] ?? [], $out['events']]);
    $out['cards'] = array_values(array_filter($out['cards'] ?? [], fn($c) => is_array($c) && isset($used[(string)($c['id'] ?? '')])));
    if (isset($out['story']) && is_array($out['story'])) $out['story'] = explode("\n", SwuPgnRender($out));
    return $out;
}

// The event records as $seat may see them (one out per one in, same order), plus the id map and the INIT it read.
// Used by SwuPgnCutPerspective and by the replay viewer's seat captions (SwuPgnSteps).
function _SwuPgnCutRecords(array $doc, int $seat): array {
    $other = $seat === 1 ? 2 : 1;
    $init = null; $initIdx = null;
    foreach ($doc['setup'] ?? [] as $i => $r) if (is_array($r) && ($r['t'] ?? '') === 'INIT') { $init = $r; $initIdx = $i; break; }
    $mine = is_array($init["p{$seat}DeckOrder"] ?? null) ? $init["p{$seat}DeckOrder"] : [];
    $theirs = array_flip(array_map('strval', array_filter(is_array($init["p{$other}DeckOrder"] ?? null) ? $init["p{$other}DeckOrder"] : [], 'is_scalar')));

    // Your copies: 1..k per card, in your own original order.
    $map = []; $mineCount = [];
    $byRef = [];
    foreach ($mine as $c) if (is_scalar($c)) $byRef[SwuPgnBaseId((string)$c)][] = (string)$c;
    foreach ($byRef as $ref => $list) {
        usort($list, fn($a, $b) => (SwuPgnCopyNumber($a) ?? 1) <=> (SwuPgnCopyNumber($b) ?? 1));
        foreach ($list as $i => $c) $map[$c] = $i === 0 ? $ref : "$ref:" . ($i + 1);
        $mineCount[$ref] = count($list);
    }
    $shownCount = [];
    $deckSeen = [];   // their deck cards you looked at: forgotten when they shuffle
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
    $ids = fn($list) => array_map($id, $list);
    $fx = [];   // effect id → [original target → the id its EFFECT_START printed]
    $fxId = function ($effect, $x) use (&$fx, $id) {
        return is_string($effect) && is_string($x) && array_key_exists($x, $fx[$effect] ?? []) ? $fx[$effect][$x] : $id($x);
    };

    $events = [];
    foreach ($doc['events'] ?? [] as $e) {
        if (!is_array($e)) { $events[] = $e; continue; }
        $t = (string)($e['t'] ?? '');
        $p = is_scalar($e['p'] ?? null) ? intval($e['p']) : 0;
        $lists = fn(string $f) => is_array($e[$f] ?? null) ? $e[$f] : [];
        // What this record makes public: a card leaving the hidden zones, one the opponent reveals, discards or uses an
        // ability of (a trigger from a hidden zone writes no REVEAL, CR 7.6.3a), one you look at, search or take.
        if ($t === 'MOVE' && !in_array($e['to'] ?? '', SWUPGN_HIDDEN_ZONES, true)) $reveal($e['card'] ?? null);
        if ($t === 'MOVE' && ($e['from'] ?? '') === 'deck' && is_string($e['card'] ?? null)) unset($deckSeen[$e['card']]);
        if (($t === 'REVEAL' || $t === 'DISCARD') && $p === $other) foreach ($lists('cards') as $c) $reveal($c);
        if (($t === 'ABILITY_ACTIVATE' || $t === 'TRIGGER') && $p === $other) $reveal($e['card'] ?? null);
        if ($p === $seat && ($t === 'LOOK' || $t === 'SEARCH')) {
            $seen = array_merge($lists($t === 'LOOK' ? 'cards' : 'seen'), $lists('found'));
            foreach ($seen as $c) {
                $reveal($c);
                if (($e['zone'] ?? 'deck') === 'deck' && is_string($c) && isset($theirs[$c])) $deckSeen[$c] = true;
            }
        }
        if ($t === 'TAKE_CONTROL' && $p === $seat) $reveal($e['card'] ?? null);
        if ($t === 'SHUFFLE' && $p === $other) { foreach ($deckSeen as $c => $_) unset($map[$c]); $deckSeen = []; }
        // What the other seat saw that you did not (§21.1).
        if ($p === $other) {
            if ($t === 'DRAW' || $t === 'LOOK') $e['cards'] = [];
            if ($t === 'SEARCH') unset($e['found'], $e['seen']);
            if ($t === 'CHOICE') unset($e['prompt']);   // an engine prompt can name a card only its chooser sees
            if ($t === 'CHOICE' && array_key_exists('blind', $e) && is_array($e['offered'] ?? null))
                $e['offered'] = array_map(fn($c) => is_string($c) && isset($theirs[$c]) ? SWUPGN_HIDDEN : $c, $e['offered']);
        }
        $original = $e['card'] ?? null;
        $effect = $e['id'] ?? null;
        $startTargets = $t === 'EFFECT_START' ? $lists('targets') : [];
        foreach (SWUPGN_ID_FIELDS as $f) if (array_key_exists($f, $e)) $e[$f] = $id($e[$f]);
        foreach (SWUPGN_ID_LIST_FIELDS as $f) {
            if (!is_array($e[$f] ?? null)) continue;
            $e[$f] = ($f === 'targets' && $t === 'EFFECT_END') ? array_map(fn($x) => $fxId($effect, $x), $e[$f]) : $ids($e[$f]);
        }
        if ($t === 'EFFECT_START' && is_string($effect)) {
            $fx[$effect] = [];
            foreach ($startTargets as $k => $orig) if (is_string($orig)) $fx[$effect][$orig] = $e['targets'][$k];
        }
        if (is_array($e['keyframe'] ?? null)) $e['keyframe'] = _SwuPgnCutKeyframe($e['keyframe'], $other, $id, $fxId);
        // Back into their deck, it is forgotten (its move there still names it): drawn again it is HIDDEN, shown again a
        // new id. The fold keeps no deck contents, so nothing in this file still holds the old one.
        if ($t === 'MOVE' && ($e['to'] ?? '') === 'deck' && is_string($original) && isset($theirs[$original])) unset($map[$original]);
        $events[] = $e;
    }
    return ['events' => $events, 'id' => $id, 'mine' => $mine, 'init' => $init, 'initIdx' => $initIdx];
}

function _SwuPgnCutKeyframe(array $kf, int $other, callable $id, ?callable $fxId = null): array {
    $fxId ??= fn($effect, $x) => $id($x);
    $mapList = fn($v) => is_array($v) ? array_map($id, $v) : $v;
    foreach ($kf['players'] ?? [] as $s => $pl) {
        if (!is_array($pl)) continue;
        if (intval($s) === $other) unset($pl['resources']);
        foreach (['hand', 'resources', 'discard', 'baseUpgrades', 'baseCaptured', 'foreignResources'] as $f) if (array_key_exists($f, $pl)) $pl[$f] = $mapList($pl[$f]);
        if (is_array($pl['leader'] ?? null) && isset($pl['leader']['id'])) $pl['leader']['id'] = $id($pl['leader']['id']);
        if (is_array($pl['abilityUses'] ?? null)) {
            foreach ($pl['abilityUses'] as $i => $u) if (is_array($u) && array_key_exists('card', $u)) $pl['abilityUses'][$i]['card'] = $id($u['card']);
        }
        foreach (is_array($pl['cards'] ?? null) ? $pl['cards'] : [] as $i => $c) {
            if (!is_array($c)) continue;
            $c['id'] = $id($c['id'] ?? null);
            if (array_key_exists('copyOf', $c)) $c['copyOf'] = $id($c['copyOf']);
            foreach (['upgrades', 'captured'] as $f) if (array_key_exists($f, $c)) $c[$f] = $mapList($c[$f]);
            if (is_array($c['upgradeControllers'] ?? null)) {
                $uc = [];
                foreach ($c['upgradeControllers'] as $u => $ctl) $uc[(string)$id((string)$u)] = $ctl;
                $c['upgradeControllers'] = $uc ?: new stdClass();
            }
            $pl['cards'][$i] = $c;
        }
        $kf['players'][$s] = $pl;
    }
    if (is_array($kf['effects'] ?? null)) {
        foreach ($kf['effects'] as $i => $fx) {
            if (!is_array($fx)) continue;
            if (array_key_exists('src', $fx)) $fx['src'] = $id($fx['src']);
            if (is_array($fx['targets'] ?? null)) $fx['targets'] = array_map(fn($x) => $fxId($fx['id'] ?? null, $x), $fx['targets']);
            $kf['effects'][$i] = $fx;
        }
    }
    return $kf;
}
