<?php
// Blocked-users endpoint (SWUSim-local, swusim DB). Actions: list / add / remove / blockOpponent.
// Privacy: never reveals a block to the blocked user; only the blocker reads their own list.
$__test = !empty($GLOBALS['__BLOCKED_TEST']);
if (!$__test) ob_start();
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../Database/ConnectionManager.php';
require_once __DIR__ . '/../Database/functions.inc.php';

$respond = function($arr) use ($__test) {
    if ($__test) return $arr;
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json'); echo json_encode($arr); exit;
};

if ($__test) {
    $uid = (int)($GLOBALS['__BLOCKED_TEST_UID'] ?? 0);
} else {
    CheckSession();
    $uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
}
if ($uid <= 0) return $respond(['success'=>false,'error'=>'not_logged_in']);

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// Resolve a username -> usersId (exact match on usersUid).
$lookupUser = function($username) {
    $username = trim((string)$username);
    if ($username === '') return 0;
    $conn = GetLocalMySQLConnection();
    $stmt = mysqli_prepare($conn, "SELECT usersId FROM `users` WHERE usersUid = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id);
    $found = mysqli_stmt_fetch($stmt) ? (int)$id : 0;
    mysqli_stmt_close($stmt); mysqli_close($conn);
    return $found;
};

if ($action === 'list') {
    return $respond(['success'=>true, 'blocks'=>LoadBlockedUsersDetailed($uid)]);
}

if ($action === 'add') {
    $targetId = $lookupUser($_POST['username'] ?? '');
    if ($targetId <= 0)     return $respond(['success'=>false,'error'=>'user_not_found','blocks'=>LoadBlockedUsersDetailed($uid)]);
    if ($targetId === $uid) return $respond(['success'=>false,'error'=>'cannot_block_self','blocks'=>LoadBlockedUsersDetailed($uid)]);
    AddBlock($uid, $targetId);
    return $respond(['success'=>true, 'blocks'=>LoadBlockedUsersDetailed($uid)]);
}

if ($action === 'remove') {
    $blockedId = (int)($_POST['blockedId'] ?? 0);
    if ($blockedId > 0) RemoveBlock($uid, $blockedId);
    return $respond(['success'=>true, 'blocks'=>LoadBlockedUsersDetailed($uid)]);
}

if ($action === 'blockOpponent') {
    require_once __DIR__ . '/MatchFlow.php';
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', (string)($_POST['gameName'] ?? ''));
    $info = SWUResolveOpponent($gameName, $uid);
    if (!$info || empty($info['oppUserId'])) {
        return $respond(['success'=>false, 'error'=>'no_opponent', 'forfeited'=>false]);
    }
    AddBlock($uid, (int)$info['oppUserId']);
    // Forfeit only an in-progress Bo3 set.
    $forfeited = false;
    if ($info['bestOf'] === 3 && !$info['seriesOver']) {
        SWUConcedeMatch($info['matchId'], (int)$info['mySeat']);
        $forfeited = true;
    }
    return $respond(['success'=>true, 'forfeited'=>$forfeited]);
}

return $respond(['success'=>false,'error'=>'unknown_action']);
