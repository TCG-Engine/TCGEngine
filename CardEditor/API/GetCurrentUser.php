<?php
include_once('AuthoringEndpoint.php');
include_once('../../AccountFiles/AccountSessionAPI.php');
include_once('../../Assets/patreon-php-master/src/PatreonLibraries.php');
include_once('../../Assets/patreon-php-master/src/API.php');
include_once('../../Assets/patreon-php-master/src/PatreonDictionary.php');
include_once('../../Database/functions.inc.php');

ce_json_headers();

try {
    ce_require_method('GET');
    if (!IsUserLoggedIn() && isset($_COOKIE['rememberMeToken'])) {
        loginFromCookie();
    }
    if (!IsUserLoggedIn()) {
        ce_success([
            'loggedIn' => false,
            'userId' => null,
            'userName' => null,
            'teamId' => null
        ]);
        exit;
    }

    $conn = GetLocalMySQLConnection();
    $userId = (int)LoggedInUser();
    $teamId = null;
    $stmt = mysqli_prepare($conn, "SELECT teamID FROM users WHERE usersId = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        if ($row && $row['teamID'] !== null) $teamId = (int)$row['teamID'];
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);

    ce_success([
        'loggedIn' => true,
        'userId' => $userId,
        'userName' => LoggedInUserName(),
        'teamId' => $teamId
    ]);
} catch (Exception $e) {
    ce_error($e->getMessage());
}

?>
