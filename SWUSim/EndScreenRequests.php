<?php
// End-screen requests after a finished 1v1 match: Rematch, Quick Rematch and Convert to Bo3 are REQUESTS the other
// player accepts or declines (a pop-up), never something that just happens. Spec:
// docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md · tests: SWUSim/DevTools/tests/end_screen_requests_test.php
//
// PURE: functions over the match ARRAY only (no APCu, files or HTTP) — the shape of TableRematch.php. State on the match:
//   $m['endScreen'] = ['requests' => [seat => ['id', 'kind' => rematch|quick|convert, 'bestOf' => 0|1|3, 'at']],
//                      'declined' => [requesterSeat => ['id', 'kind', 'at']],
//                      'departed' => [seat => lobby|requeue|menu],
//                      'claim'    => null|['kind', 'at'],      // taken ONCE, under the match lock, by whoever fires
//                      'result'   => null|['kind' => 'quick', 'gameName'] | ['kind' => 'convert']
//                                        | ['kind' => 'room', 'lobbyID', 'keys' => [seat => authKey]]
//                                        | ['kind' => 'failed', 'message']   — an agreed request that could not start
//
// $m['blocked'] is a TRANSIENT input, never stored: the caller sets it on its copy when the two players have a block
// between them (a DB lookup this pure file cannot do). A blocked pair has nothing to ask each other.
//
// The owner's rules (2026-10-30): the other player must accept; ignoring a request does nothing; the requester may ask
// again once it is answered OR 30s have passed; both asking for the same thing is agreeing (call 2 — and a Quick Rematch
// only at the same length, call 12); a player who has left the end screen can't be asked.
include_once __DIR__ . '/../AppCore/SWU/Formats.php';   // SWUFormatIsRated

if (!defined('END_REQUEST_REPEAT_AFTER')) define('END_REQUEST_REPEAT_AFTER', 30);
if (!defined('TABLE_REMATCH_QUIET_AFTER')) define('TABLE_REMATCH_QUIET_AFTER', 120);

function _EndReqState(array $m): array {
    $es = is_array($m['endScreen'] ?? null) ? $m['endScreen'] : [];
    foreach (['requests', 'declined', 'departed'] as $k) $es[$k] = is_array($es[$k] ?? null) ? $es[$k] : [];
    $es['claim']  = $es['claim'] ?? null;
    $es['result'] = $es['result'] ?? null;
    return $es;
}
function _EndReqOpp(int $seat): int { return $seat === 1 ? 2 : 1; }

// What this finished match may still ask for. Rated (Meta Premier) matches requeue instead (spec §4.5).
function EndReqKindsAllowed(array $m): array {
    if (count($m['players'] ?? []) !== 2 || strval($m['state'] ?? '') !== 'complete') return [];
    if (function_exists('SWUFormatIsRated') && SWUFormatIsRated(strval($m['format'] ?? ''))) return [];
    if (!empty($m['blocked'])) return [];
    $kinds = ['rematch', 'quick'];
    if (intval($m['bestOf'] ?? 1) === 1) $kinds[] = 'convert';
    return $kinds;
}

// Seat $seat asks for $kind. Returns ['ok', 'error', 'accepted'] — 'accepted' is set when this request agrees with one
// the opponent already made (both asked for the same thing): ['kind', 'bestOf', 'from' => first asker, 'by' => $seat].
function EndReqOpen(array &$m, int $seat, string $kind, int $bestOf, string $id, int $now): array {
    $no = fn(string $e) => ['ok' => false, 'error' => $e, 'accepted' => null];
    if (($seat !== 1 && $seat !== 2) || !in_array($kind, EndReqKindsAllowed($m), true)) return $no('That is not available for this match.');
    $es = _EndReqState($m);
    $opp = _EndReqOpp($seat);
    if (isset($es['departed'][strval($opp)])) return $no('Your opponent has left the end-game screen.');
    $bestOf = $kind === 'convert' ? 3 : ($kind === 'rematch' ? 0 : ($bestOf === 3 ? 3 : 1));
    $theirs = $es['requests'][strval($opp)] ?? null;
    if (is_array($theirs) && $theirs['kind'] === $kind && intval($theirs['bestOf']) === $bestOf) {
        unset($es['requests']['1'], $es['requests']['2']);
        $m['endScreen'] = $es;
        return ['ok' => true, 'error' => null, 'accepted' => ['kind' => $kind, 'bestOf' => $bestOf, 'from' => $opp, 'by' => $seat]];
    }
    $mine = $es['requests'][strval($seat)] ?? null;
    if (is_array($mine)) {
        $age = $now - intval($mine['at']);
        if ($age < END_REQUEST_REPEAT_AFTER) return $no('You can ask again in ' . (END_REQUEST_REPEAT_AFTER - $age) . 's.');
    }
    $es['requests'][strval($seat)] = ['id' => $id, 'kind' => $kind, 'bestOf' => $bestOf, 'at' => $now];
    unset($es['declined'][strval($seat)]);
    if (($es['result']['kind'] ?? '') === 'failed') $es['result'] = null;   // asking again starts over
    $m['endScreen'] = $es;
    return ['ok' => true, 'error' => null, 'accepted' => null];
}

// Seat $seat answers the opponent's open request $id. Accept clears every request and returns 'accepted'; decline
// clears that request, tells the requester, and returns 'declined' => the kind (for the log line).
function EndReqAnswer(array &$m, int $seat, string $id, bool $accept, int $now): array {
    $no = fn(string $e) => ['ok' => false, 'error' => $e, 'accepted' => null, 'declined' => null];
    if ($seat !== 1 && $seat !== 2) return $no('That request is no longer open.');
    $es = _EndReqState($m);
    $opp = _EndReqOpp($seat);
    $theirs = $es['requests'][strval($opp)] ?? null;
    if (!is_array($theirs) || $id === '' || !hash_equals(strval($theirs['id']), $id)) return $no('That request is no longer open.');
    // (No "opponent departed" check needed: EndReqMarkDeparted voids every request, so a departed requester has none.)
    if (!$accept) {
        unset($es['requests'][strval($opp)]);
        $es['declined'][strval($opp)] = ['id' => $id, 'kind' => $theirs['kind'], 'at' => $now];
        $m['endScreen'] = $es;
        return ['ok' => true, 'error' => null, 'accepted' => null, 'declined' => $theirs['kind']];
    }
    unset($es['requests']['1'], $es['requests']['2']);
    $m['endScreen'] = $es;
    return ['ok' => true, 'error' => null, 'declined' => null,
            'accepted' => ['kind' => $theirs['kind'], 'bestOf' => intval($theirs['bestOf']), 'from' => $opp, 'by' => $seat]];
}

// Seat $seat left the end screen ('lobby' | 'requeue' | 'menu'). Every open request is void either way.
function EndReqMarkDeparted(array &$m, int $seat, string $where): void {
    $es = _EndReqState($m);
    $es['departed'][strval($seat)] = $where;
    $es['requests'] = [];
    $m['endScreen'] = $es;
}

// What one seat's end screen draws. $seen = the end-screen heartbeat store (seat => last poll, '_since' => store birth).
function EndReqView(array $m, int $viewerSeat, array $seen, int $now): array {
    $es = _EndReqState($m);
    $isSeat = $viewerSeat === 1 || $viewerSeat === 2;
    $opp = _EndReqOpp($viewerSeat);
    $went = $isSeat ? ($es['departed'][strval($opp)] ?? null) : null;
    if ($isSeat && $went === null) {
        // Quiet = no end-screen poll for 120s (the tab was closed). A wiped store gives everyone time to re-stamp.
        $base = max(intval($seen[$opp] ?? 0), intval($seen['_since'] ?? 0));
        if ($base > 0 && ($now - $base) >= TABLE_REMATCH_QUIET_AFTER) $went = 'gone';
    }
    $theirs = $isSeat ? ($es['requests'][strval($opp)] ?? null) : null;
    $mine   = $isSeat ? ($es['requests'][strval($viewerSeat)] ?? null) : null;
    $declined = $isSeat ? ($es['declined'][strval($viewerSeat)] ?? null) : null;
    $result = null;
    if ($isSeat && is_array($es['result'])) {
        $kind = strval($es['result']['kind'] ?? '');
        if ($kind === 'room') {
            $key = strval($es['result']['keys'][strval($viewerSeat)] ?? '');
            if ($key !== '') $result = ['kind' => 'room', 'lobbyID' => strval($es['result']['lobbyID'] ?? ''), 'authKey' => $key];
        } elseif ($kind === 'quick') {
            $result = ['kind' => 'quick', 'gameName' => strval($es['result']['gameName'] ?? '')];
        } elseif ($kind === 'convert') {
            $result = ['kind' => 'convert'];
        } elseif ($kind === 'failed') {
            $result = ['kind' => 'failed', 'message' => strval($es['result']['message'] ?? '')];
        }
    }
    return [
        'incoming'     => is_array($theirs) ? ['id' => strval($theirs['id']), 'kind' => $theirs['kind'], 'bestOf' => intval($theirs['bestOf'])] : null,
        'outgoing'     => is_array($mine) ? ['kind' => $mine['kind'], 'bestOf' => intval($mine['bestOf']),
                                             'repeatIn' => max(0, END_REQUEST_REPEAT_AFTER - ($now - intval($mine['at'])))] : null,
        'declined'     => is_array($declined) ? ['kind' => $declined['kind']] : null,
        'opponentWent' => $went,
        'kinds'        => ($isSeat && $went === null) ? EndReqKindsAllowed($m) : [],
        'result'       => $result,
    ];
}
