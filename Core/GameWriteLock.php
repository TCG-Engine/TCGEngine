<?php
// Per-game WRITE LOCK — serialises every read-modify-write of one game's state.
//
// ProcessInput.php is a plain ParseGamestate -> execute -> WriteGamestate with nothing in between to
// stop a second request on the same game. Two overlapping requests both read the same state and the
// LAST write wins, silently discarding the other action. Live bot seats make that routine: every
// human browser POSTs the bot's step (mode 10017), and a step that spends time choosing can overwrite
// a human's move committed meanwhile. Holding this lock from before the parse until after the write
// makes the second request wait for — and then build on — the first one's result.
//
// flock(), not APCu: the kernel drops the lock when the process ends, so a fatal or a timeout can
// never strand a game locked. Waiting is a usleep() spin, which costs no CPU time and so does not eat
// ProcessInput's set_time_limit(1) budget (that limit counts CPU time on Linux).
//
// Fails OPEN: a game with no Games/<id>/ directory, or a lock file that cannot be opened, runs
// unlocked exactly as before rather than refusing the action.
// Pinned by DevTools/tdd-regression/test_core_game_write_lock.php.

// Longest a request waits for the lock. A bot step can legitimately hold it for a few seconds
// (lookahead), so this sits well above that and below the 15s bot-step time limit.
const SIM_GAME_WRITE_LOCK_WAIT_MS = 10000;

function SimGameWriteLockPath(string $rootName, string $gameName): ?string
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $rootName) || !preg_match('/^[A-Za-z0-9_-]+$/', $gameName)) return null;
    $dir = dirname(__DIR__) . "/$rootName/Games/$gameName";
    return is_dir($dir) ? "$dir/.writelock" : null;
}

// Returns the lock handle on success, true when there is nothing to lock (fail open), or false when
// another request still held the lock after $waitMs.
function SimGameAcquireWriteLock(string $rootName, string $gameName, int $waitMs = SIM_GAME_WRITE_LOCK_WAIT_MS)
{
    $path = SimGameWriteLockPath($rootName, $gameName);
    if ($path === null) return true;
    // 'c' creates the file; 'r' still works on one another user created (flock needs no write access).
    $handle = @fopen($path, 'c');
    if ($handle === false) $handle = @fopen($path, 'r');
    if ($handle === false) {
        error_log("GameWriteLock: cannot open $path; running unlocked");
        return true;
    }
    $deadline = microtime(true) + max(0, $waitMs) / 1000;
    while (true) {
        if (flock($handle, LOCK_EX | LOCK_NB)) return $handle;
        if (microtime(true) >= $deadline) {
            fclose($handle);
            return false;
        }
        usleep(20000);
    }
}

function SimGameReleaseWriteLock($handle): void
{
    if (!is_resource($handle)) return;
    flock($handle, LOCK_UN);
    fclose($handle);
}
