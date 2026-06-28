<?php
// Small shared renderers for the remaining standard pages.

function RenderMobileViewport(): string {
    return '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
}

// Patreon status block (identical across sites). Moved verbatim from the per-site
// Patreons.php; returns a string instead of echoing + exit().
function RenderPatreons(): string {
    if (!function_exists('LoggedInUserName')) return '';
    $userName = LoggedInUserName();
    if (empty($userName)) return '';   // not logged in -> no patreon block

    $conn = GetLocalMySQLConnection();
    $sql = "SELECT * FROM users where usersUid='$userName'";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt, $sql)) {
        return "ERROR";
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    if (!$row || empty($row["patreonAccessToken"])) return '';
    $access_token = $row["patreonAccessToken"];

    ob_start();
    try {
        PatreonLogin($access_token, false, false);
    } catch (\Exception $e) { }
    return ob_get_clean();
}
