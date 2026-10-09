<?php
// SWU-PGN/1.0 reader — the portable game notation for Star Wars: Unlimited (.swupgn).
//
// SWU-PGN is a community standard authored by Travis Chase. A .swupgn file records a whole game as
// a header, a few NDJSON sections and an EVENTS stream, and a reader rebuilds the board at any
// moment by FOLDING the events — no rules engine involved. This directory is that reader:
//
//   SwuPgnParse($text)                 → document: header, story, decks, cards, setup, events, annotations
//   SwuPgnValidate($text)              → conformance report {valid, formatVersion, issues[]}
//   SwuPgnFold($events)                → the board after every event (spec §11 ReducedState)
//   SwuPgnStateAt($events, $seq)       → the board up to and including $seq (replay scrubbing)
//   SwuPgnCheckKeyframes($events)      → the spec §14 integrity gate {ok, mismatches[]}
//   SwuPgnRender($doc, $nameOf = null) → the human-readable %%% STORY
//
// It is a PHP port of the format's MIT-licensed reference reader and must behave identically: the
// guard is DevTools/tdd-regression/test_swupgn_vectors.php, which compares every function above
// against the reference reader's own output on the spec's test vectors and on recorded games.
// When the two disagree, the reference reader is right (spec §2).
//
// Data model: everything is a plain PHP array, decoded with json_decode(..., true). A ReducedState
// key that the spec marks optional is ABSENT (not null) until something supplies it — absent and
// zero mean different things to the integrity gate.
//
// Pure PHP, no engine, no database: usable from SWUSim, SWUDeck and SWUStats alike.

require_once __DIR__ . '/SwuPgnParse.php';
require_once __DIR__ . '/SwuPgnFold.php';
require_once __DIR__ . '/SwuPgnIntegrity.php';
require_once __DIR__ . '/SwuPgnRender.php';
require_once __DIR__ . '/SwuPgnValidate.php';
