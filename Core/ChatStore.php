<?php
// Saved chat — disk is the record, APCu is the fast path
// (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md).
//
// APCu alone forgot a conversation an hour after its last message, kept only the last 100 rows, and lost
// everything when it filled up or was cleared. A room that lasts a whole session, and an abuse report that needs
// what was said, both need more than that. So, for Petranaki only, every message is also appended to a file:
//
//   <Games>/<gameName>/Chat.jsonl   a game's own conversation — kept exactly as long as the game's folder
//   <Chats>/l_<lobbyID>.jsonl       a room's conversation (and every match that adopted it)
//   <Chats>/m_<matchId>.jsonl       a match with no room (rematch, convert-to-Bo3)
//
// One JSON object per line, in id order (appends happen under an flock on "<file>.lock"). A saved row is the live
// row plus 'src' — where it was sent from: 'game:<n>', 'room:<lobbyID>' or 'sideboard:<matchId>'. 'src' NEVER reaches APCu, so the
// live row shape (the contract above ChatAppendMessage) is unchanged.
//
// ⚠ PERSISTENCE IS SWITCHED ON PER REQUEST, NOT PER FILE. Core/NetworkingLibraries.php is shared by every sim, and a
// bare scope token cannot say which sim it belongs to ("4242" exists in several). Each SWUSim entry point calls
// ChatPersistUseRoot('SWUSim') once; any request that never does keeps today's APCu-only behaviour byte-for-byte.
//
// ⚠ A DISK FAILURE NEVER BREAKS CHAT. No lock, no directory, a short write: the message still goes through APCu
// exactly as before, and the failure goes to error_log.

if (!function_exists('ChatPersistUseRoot')) {

function ChatPersistUseRoot($rootName): void {
    if ($rootName === 'SWUSim') $GLOBALS['CHAT_PERSIST_ROOT'] = 'SWUSim';
    else unset($GLOBALS['CHAT_PERSIST_ROOT']);
}

function ChatPersistActive(): bool {
    return ($GLOBALS['CHAT_PERSIST_ROOT'] ?? '') === 'SWUSim';
}

function ChatStoreDir(): string {
    $dir = getenv('SWUSIM_CHAT_DIR');
    return ($dir !== false && $dir !== '') ? rtrim($dir, '/') : dirname(__DIR__) . '/SWUSim/Chats';
}

function _ChatStoreGamesDir(): string {
    $dir = getenv('SWUSIM_CHAT_GAMES_DIR');
    return ($dir !== false && $dir !== '') ? rtrim($dir, '/') : dirname(__DIR__) . '/SWUSim/Games';
}

// The file for a RESOLVED scope key (a ChatScopeKey() result), or null when the key cannot address one.
function ChatStorePath($scopeKey): ?string {
    $scopeKey = strval($scopeKey);
    if ($scopeKey !== '' && ctype_digit($scopeKey) && strlen($scopeKey) <= 32) {
        return _ChatStoreGamesDir() . '/' . $scopeKey . '/Chat.jsonl';
    }
    if (preg_match('/^([lm]):([A-Za-z0-9_-]{1,64})$/', $scopeKey, $mm)) {
        return ChatStoreDir() . '/' . $mm[1] . '_' . $mm[2] . '.jsonl';
    }
    return null;
}

// Where a message was sent from, from the token the CALLER passed (before redirection): a game that belongs to a
// room or match writes into that shared conversation, so this tag is the only record of which game a line was in.
// The id rides along ('sideboard:<matchId>'): once a room hosts several matches, a sideboard line is otherwise
// impossible to place, and a line that was never tagged cannot be tagged later.
function ChatSourceTag($scopeToken): string {
    $t = strval($scopeToken);
    if ($t !== '' && ctype_digit($t)) return 'game:' . $t;
    if (strncmp($t, 'l:', 2) === 0) return 'room:' . substr($t, 2);
    if (strncmp($t, 'm:', 2) === 0) return 'sideboard:' . substr($t, 2);
    return '';
}

// Every saved row of a conversation, in id order, including 'src' and 'to'. [] when nothing is saved.
function ChatSavedHistory($scopeKey): array {
    $path = ChatStorePath($scopeKey);
    if ($path === null || !is_file($path)) return [];
    $rows = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $r = json_decode($line, true);
        if (is_array($r) && isset($r['id'])) $rows[] = $r;
    }
    return $rows;
}

// An exclusive lock on one conversation's file, or null when it cannot be taken (the caller then falls back to
// APCu alone). A room/match file's directory is created on demand; a game's folder is never created here — no
// folder means no game, and nothing to keep.
function ChatStoreLock($scopeKey) {
    $path = ChatStorePath($scopeKey);
    if ($path === null) return null;
    $dir = dirname($path);
    if (!ctype_digit(strval($scopeKey)) && !is_dir($dir)) @mkdir($dir, 0775, true);
    if (!is_dir($dir)) return null;
    $fh = @fopen($path . '.lock', 'c');
    if ($fh === false) return null;
    if (!flock($fh, LOCK_EX)) { fclose($fh); return null; }
    return $fh;
}

function ChatStoreUnlock($fh): void {
    if (!$fh) return;
    flock($fh, LOCK_UN);
    fclose($fh);
}

// The highest saved id, read from the file's tail (a whole-file read on every send would grow with the session).
function ChatStoreLastSavedId($scopeKey): int {
    $path = ChatStorePath($scopeKey);
    if ($path === null || !is_file($path)) return 0;
    $size = filesize($path);
    if (!$size) return 0;
    $fh = @fopen($path, 'r');
    if ($fh === false) return 0;
    $len = min($size, 16384);
    fseek($fh, -$len, SEEK_END);
    $tail = fread($fh, $len);
    fclose($fh);
    $lines = array_reverse(array_filter(explode("\n", strval($tail)), fn($l) => $l !== ''));
    foreach ($lines as $line) {
        $r = json_decode($line, true);
        if (is_array($r) && isset($r['id'])) return intval($r['id']);
    }
    return 0;
}

// Append one saved row. False on any failure, including a short write.
function ChatStoreAppend($scopeKey, array $row): bool {
    $path = ChatStorePath($scopeKey);
    if ($path === null) return false;
    $line = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($line)) return false;
    $line .= "\n";
    return @file_put_contents($path, $line, FILE_APPEND) === strlen($line);
}

// The live (APCu) form of saved rows: everything but 'src'.
function ChatStoreLiveRows(array $rows): array {
    return array_map(function ($r) { unset($r['src']); return $r; }, $rows);
}

// Does the game (numeric owner) or match (any other owner id) actually exist? Only then may a MISS be cached:
// the game-scope chat read is unauthenticated, so caching a miss for any number a caller invents would let anyone
// fill APCu — and a full APCu evicts every live game's seat keys.
function ChatStoreOwnerExists($ownerId): bool {
    $id = strval($ownerId);
    if ($id !== '' && ctype_digit($id)) return is_dir(_ChatStoreGamesDir() . '/' . $id);
    $id = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
    return $id !== '' && is_file(dirname(__DIR__) . '/SWUSim/Matches/' . $id . '/Match.json');
}

// Refill APCu from disk after a miss: the last 100 rows, and a version no lower than the last saved id. Uses
// apcu_add for the rows so a send that raced in first is never overwritten. Returns the conversation's rows as
// they now stand in APCu ([] when nothing is saved). A REAL game with nothing on disk gets a short-lived 0 version,
// so a game nobody chats in does not stat the disk on every board poll (GetChatUpdateVersion). The messages key is
// never cached empty, so a reader of GetChatMessagesSince on an empty conversation still pays one is_file() — cheap,
// and only reached once the version says something changed (or from GetChat.php's own poll).
function ChatStoreRehydrate($scopeToken): array {
    $key = ChatScopeKey($scopeToken);
    $msgKey = GetChatMessagesCacheKey($scopeToken);
    $verKey = GetChatVersionCacheKey($scopeToken);
    if ($key === null || $msgKey === null || $verKey === null) return [];
    $rememberEmpty = function () use ($key, $verKey) {
        if (ctype_digit($key) && ChatStoreOwnerExists($key)) apcu_add($verKey, 0, 300);
    };
    $path = ChatStorePath($key);
    if ($path === null || !is_file($path)) {
        $rememberEmpty();
        return [];
    }
    $saved = ChatSavedHistory($key);
    if (empty($saved)) { $rememberEmpty(); return []; }
    apcu_add($msgKey, ChatStoreLiveRows(array_slice($saved, -100)), 3600);
    ChatStoreRaiseVersion($scopeToken, intval(end($saved)['id']));
    $now = apcu_fetch($msgKey);
    return is_array($now) ? $now : [];
}

// Never let the version sit below $atLeast (a wiped counter would restart at 1 and hide new messages from every
// client that polled before the wipe).
function ChatStoreRaiseVersion($scopeToken, int $atLeast): void {
    $verKey = GetChatVersionCacheKey($scopeToken);
    if ($verKey === null) return;
    $cur = apcu_fetch($verKey);
    if ($cur === false || intval($cur) < $atLeast) apcu_store($verKey, $atLeast, 3600);
}

}
