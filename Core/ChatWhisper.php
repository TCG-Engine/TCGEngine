<?php
// Whisper chat — sim-agnostic pieces (spec docs/superpowers/specs/2026-09-17-swusim-twinsuns-whisper-chat-design.md).
// A whisper is an ordinary chat row carrying `to` (int seats). Only the TEXT is private: every viewer
// still receives the row (id, sender, recipients) so they can render "X whispered something to Y".
// Redaction happens at egress (GetChatMessagesSince), and an unknown viewer is treated as a NON-party.

// Parse the `whisperTo` request value. [] = not a whisper; int[] (sorted, unique) = valid targets;
// null = malformed / out of range / includes the sender.
function ChatParseWhisperTargets($raw, int $senderSeat, int $maxSeats)
{
    $raw = trim(strval($raw));
    if ($raw === '') return [];
    $out = [];
    foreach (explode(',', $raw) as $part) {
        $part = trim($part);
        if ($part === '' || !ctype_digit($part)) return null;
        $seat = intval($part);
        if ($seat < 1 || $seat > $maxSeats || $seat === $senderSeat) return null;
        $out[$seat] = $seat;
    }
    $out = array_values($out);
    sort($out);
    return $out;
}

function ChatViewerIsWhisperParty(array $row, $viewerInfo): bool
{
    if (!is_array($viewerInfo) || !empty($viewerInfo['isSpectator'])) return false;
    $seat = intval($viewerInfo['viewerSeat'] ?? 0);
    if ($seat <= 0) return false;
    if (intval($row['playerID'] ?? 0) === $seat) return true;
    return in_array($seat, array_map('intval', (array)($row['to'] ?? [])), true);
}

function ChatRowForViewer(array $row, $viewerInfo): array
{
    if (!isset($row['to'])) return $row;                 // public row: untouched, byte-identical
    if (ChatViewerIsWhisperParty($row, $viewerInfo)) return $row;
    $row['text'] = '';
    $row['redacted'] = true;
    return $row;
}
