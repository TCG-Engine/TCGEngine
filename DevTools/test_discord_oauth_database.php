<?php

error_reporting(E_ALL);
ini_set('session.save_path', sys_get_temp_dir());
session_id('discord-oauth-db-test-' . bin2hex(random_bytes(4)));

require_once __DIR__ . '/../AccountFiles/DiscordOAuth.php';

$suffix = bin2hex(random_bytes(5));
$username = 'DiscordTest' . $suffix;
$email = 'discord-test-' . $suffix . '@example.invalid';
$subject = 'test-' . $suffix;
$userId = 0;
$failures = [];

function dbCheck(string $name, bool $condition): void {
    global $failures;
    if (!$condition) $failures[] = $name;
}

try {
    DiscordOAuthRememberPending([
        'id' => $subject,
        'email' => $email,
        'verified' => true,
        'username' => $username,
    ], [
        'site' => 'SWUDeck',
        'redirect' => '/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php',
    ]);

    $user = DiscordOAuthCreateUserFromPending($username, $email, false);
    $userId = (int)($user['usersId'] ?? 0);
    dbCheck('passwordless user was created', $userId > 0);
    dbCheck('password is null', array_key_exists('usersPwd', $user) && $user['usersPwd'] === null);
    dbCheck('legacy Discord ID is synchronized', ($user['discordID'] ?? '') === $subject);

    $conn = GetLocalMySQLConnection();
    $identity = DiscordOAuthFindUserBySubject($conn, $subject);
    dbCheck('Discord identity resolves through users.discordID', $identity && (int)$identity['usersId'] === $userId);
    DiscordOAuthLinkIdentity($conn, $userId, $subject);
    $linked = DiscordOAuthFindUserBySubject($conn, $subject);
    dbCheck('existing identity can be safely relinked', $linked && (int)$linked['usersId'] === $userId);
    $conn->close();

    CheckSession();
    dbCheck('new account is logged in', (int)($_SESSION['userid'] ?? 0) === $userId);
} catch (Throwable $e) {
    $failures[] = $e->getMessage();
} finally {
    if ($userId > 0) {
        $conn = GetLocalMySQLConnection();
        $stmt = $conn->prepare('DELETE FROM users WHERE usersId = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
}

foreach ($failures as $failure) echo "FAIL: $failure\n";
echo 'PASS=' . (5 - count($failures)) . ' FAIL=' . count($failures) . "\n";
exit($failures ? 1 : 0);
