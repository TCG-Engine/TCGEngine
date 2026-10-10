<?php
// The read-only SWU-PGN replay viewer game (written by SWUSim/SwuPgnViewer.php). A viewer game is
// flagged by the SWUVar SWUPGN_VIEWER = '1'; nothing here changes a normal game.

function SwuPgnIsViewerGame(): bool
{
    return function_exists('GetSWUVar') && GetSWUVar('SWUPGN_VIEWER') === '1';
}

// {uid: {"p": power, "h": hp, "k": [keywords]}} for the board on screen, decoded once per gamestate.
function _SwuPgnViewerStats(): array
{
    static $raw = null, $decoded = [];
    $now = GetSWUVar('SWUPGN_STATS', '{}');
    if ($now !== $raw) { $raw = $now; $d = json_decode($now, true); $decoded = is_array($d) ? $d : []; }
    return $decoded;
}

// The file's stated power ('p') or HP ('h') for this unit; null outside a viewer game or when not stated.
function SwuPgnViewerStat($obj, string $field): ?int
{
    if (!SwuPgnIsViewerGame() || !is_object($obj) || !isset($obj->UniqueID)) return null;
    $v = _SwuPgnViewerStats()[(string)$obj->UniqueID][$field] ?? null;
    return is_int($v) ? $v : null;
}

// 1/0 from the stated keyword list ("raid 2" counts as raid); null outside a viewer game.
function SwuPgnViewerKeyword($obj, string $keyword): ?int
{
    if (!SwuPgnIsViewerGame()) return null;
    if (!is_object($obj) || !isset($obj->UniqueID)) return 0;
    foreach (_SwuPgnViewerStats()[(string)$obj->UniqueID]['k'] ?? [] as $k) {
        if (is_string($k) && strtolower(strtok($k, ' ')) === $keyword) return 1;
    }
    return 0;
}

// Core/EngineActionRunner.php's optional seam: a viewer game accepts no engine input at all.
function GameValidateEngineAction($action): array
{
    if (SwuPgnIsViewerGame()) return ['allowed' => false, 'message' => 'This is a replay viewer; use the replay controls.'];
    return ['allowed' => true, 'message' => ''];
}
