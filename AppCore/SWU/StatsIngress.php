<?php
// Stats ingress — translate every incoming card identifier to SET_NNN before anything is written.
//
// Karabast and Petranaki send FFG UIDs. That is the documented contract and it is not changing, so
// after the SET_NNN re-key the stat tables are SET_NNN-keyed while the wire stays UUID. Without
// this, every submission would write UUID-keyed rows straight back into the tables the migration
// just merged, and the mixed key space would be back within hours.
//
// **Accept either shape.** A value that is already SET_NNN passes through; anything else goes
// through the card dictionary. That is what lets a client conform whenever it likes, with zero
// coordination — Petranaki can keep sending UUIDs today and switch later, and both work.
//
// ── What happens to something we cannot resolve ──────────────────────────────
// Three classes (AppCore/SWU/CardIdentity.php). Only the third is an error, and it is DROPPED AND
// LOGGED rather than written verbatim — writing it verbatim is how the unreadable rows this
// migration exists to clean up got there in the first place.
//
// Two granularities, because the identifier's role differs:
//   * an unresolvable cardResults[].cardId — drop that ONE card's rows; the rest of the submission
//     proceeds. It is one card's stats, not the game's.
//   * an unresolvable leader or base — leaderID/baseID are PRIMARY KEY components of deckstats,
//     deckmetastats, deckmetamatchupstats and opponentnamedbasestats, so a partial row cannot be
//     keyed at all. Skip that PLAYER's stat writes entirely. The other player is unaffected.
//   * an unresolvable winHero/loseHero — skip the completedgame row. Those two columns are exactly
//     where the historical junk accumulated (zzzzzzz001, abcdefgMTL, blanks); letting a new one in
//     would re-open the hole.
//
// ── Contract impact: none ────────────────────────────────────────────────────
// Request and response shapes are untouched. No field is added, removed, or given a new default. A
// payload that succeeds today produces the same HTTP response. The only behavioural difference is
// for identifiers we cannot resolve, which today are written verbatim and after this are dropped
// and logged.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §2

require_once __DIR__ . '/CardIdentity.php';

// Normalise a submission IN PLACE. Returns what the caller must skip.
//
//   ['skipCompletedGame' => bool,
//    'skipPlayer'        => [1 => bool, 2 => bool],
//    'droppedCards'      => int,
//    'notes'             => string[]]     // already error_log'd; returned for tests
function SWUStatsIngressNormalize(array &$data): array
{
    $r = ['skipCompletedGame' => false, 'skipPlayer' => [1 => false, 2 => false],
          'droppedCards' => 0, 'notes' => []];

    $game = isset($data['gameName']) ? (string)$data['gameName'] : '?';
    $note = function (string $msg) use (&$r, $game) {
        $full = "SWU stats ingress [game $game]: $msg";
        $r['notes'][] = $msg;
        error_log($full);
    };

    // ── completedgame's hero columns ─────────────────────────────────────────
    foreach (['winHero', 'loseHero'] as $field) {
        if (!isset($data[$field]) || $data[$field] === null) continue;
        $raw = (string)$data[$field];
        $c = SWUCardIdentityClassify($raw, false);
        if ($c['class'] === 3) {
            $r['skipCompletedGame'] = true;
            $note("unresolvable $field '$raw' — completedgame row skipped");
            continue;
        }
        $data[$field] = $c['to'];
    }

    // ── per-player leader / base / cardResults ───────────────────────────────
    foreach ([1, 2] as $seat) {
        $key = "player$seat";
        if (!isset($data[$key])) continue;

        // The payload sends these as JSON STRINGS; tests and internal callers pass arrays. Decode,
        // rewrite, and re-encode in whichever form it arrived — changing the form would break the
        // is_string() branch inside SaveDeckStats.
        $wasString = is_string($data[$key]);
        $p = $wasString ? json_decode($data[$key], true) : $data[$key];
        if (!is_array($p)) continue;

        foreach ([['leader', false], ['base', true]] as [$field, $poly]) {
            if (!isset($p[$field]) || $p[$field] === null || $p[$field] === '') continue;
            $raw = (string)$p[$field];
            $c = SWUCardIdentityClassify($raw, $poly);
            if ($c['class'] === 3) {
                $r['skipPlayer'][$seat] = true;
                $note("unresolvable $field '$raw' for player$seat — that player's stats skipped");
                continue;
            }
            $p[$field] = $c['to'];     // class 2 returns the original, verbatim
        }

        if (isset($p['cardResults']) && is_array($p['cardResults'])) {
            $kept = [];
            foreach ($p['cardResults'] as $card) {
                if (!is_array($card) || !isset($card['cardId'])) { $kept[] = $card; continue; }
                $raw = (string)$card['cardId'];
                $c = SWUCardIdentityClassify($raw, false);
                if ($c['class'] === 3) {
                    $r['droppedCards']++;
                    $note("unresolvable cardId '$raw' for player$seat — that card's rows skipped");
                    continue;                       // drop this card only
                }
                $card['cardId'] = $c['to'];
                $kept[] = $card;
            }
            $p['cardResults'] = array_values($kept);
        }

        $data[$key] = $wasString ? json_encode($p) : $p;
    }

    return $r;
}
