<?php
// SWUSim whisper policy — consulted by SubmitChat.php for `whisperTo` sends only (a public message never
// loads this file). Spec: docs/superpowers/specs/2026-09-17-swusim-twinsuns-whisper-chat-design.md
//   Premier (< 3 seats): never.  Twin Suns: any non-empty set of other seats in SeatOrder.
//   Team Suns: exactly the sender's teammates by seat PARITY over SeatOrder — deliberately NOT LiveSeats
//   (SWUTeammatesOf filters to live seats), so an eliminated player can still whisper their team.
// ⚠ Must be included at TOP-LEVEL scope (SubmitChat.php does), never from inside a function:
//   GamestateParser.php pulls in the whole engine, whose file-scope variables must be globals.
// Measured 2026-09-17: ≈14 ms for a typical gamestate, ≈39 ms for the largest on disk (8.2 MB).
$swuWhisperPrevCwd = getcwd();
chdir(__DIR__ . '/..');
include_once __DIR__ . '/../GamestateParser.php';
include_once __DIR__ . '/../ZoneAccessors.php';
include_once __DIR__ . '/../ZoneClasses.php';
include_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';
chdir($swuWhisperPrevCwd);

function ChatWhisperAllowed(string $gameName, int $senderSeat, array $targetSeats): bool
{
    $GLOBALS['gameName'] = $gameName;          // ParseGamestate reads the global
    ParseGamestate(__DIR__ . '/../');
    $seats = GetSeatOrderArray();              // [1,2] when the game has no SeatOrder (or no gamestate)
    if (count($seats) < 3) return false;
    if (!in_array($senderSeat, $seats, true)) return false;
    if (count($targetSeats) === 0) return false;
    foreach ($targetSeats as $t) {
        if (!is_int($t) || $t === $senderSeat || !in_array($t, $seats, true)) return false;
    }
    if (SWUIsTeamGame()) {
        $mates = [];
        foreach ($seats as $s) {
            if ($s !== $senderSeat && SWUTeamOf($s) === SWUTeamOf($senderSeat)) $mates[] = $s;
        }
        sort($mates);
        $targets = $targetSeats;
        sort($targets);
        return $targets === $mates;
    }
    return true;
}
