<?php

require_once __DIR__ . '/DiscordOAuth.php';

$pending = DiscordOAuthPending();
if (!$pending) {
    header('Location: /TCGEngine/SharedUI/Sites/SWUDeck/Signup.php?oauth_error=' . rawurlencode('The Discord signup session expired. Please start again.'));
    exit();
}

$error = trim((string)($_GET['oauth_error'] ?? ''));
$conn = GetLocalMySQLConnection();
$suggestedUsername = DiscordOAuthSuggestedUsername($conn, $pending['username'] ?? '');
$existingEmailUser = DiscordOAuthFindUserByEmail($conn, $pending['email'] ?? '');
$conn->close();

$username = trim((string)($_POST['username'] ?? $suggestedUsername));
$email = trim((string)($_POST['email'] ?? ($pending['email'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals((string)$pending['csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('The signup form expired. Please start again.');
        }
        $mode = (string)($_POST['mode'] ?? 'create');
        if ($mode === 'link') {
            $loginUsername = trim((string)($_POST['loginUsername'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $user = LoadUserData($loginUsername);
            $passwordHash = $user['usersPwd'] ?? null;
            if (!$user || !is_string($passwordHash) || $passwordHash === '' || !password_verify($password, $passwordHash)) {
                throw new RuntimeException('Login failed; check your username and password.');
            }
            $redirect = DiscordOAuthSafeReturn($pending['redirect'] ?? '', $pending['site'] ?? 'SWUDeck');
            $conn = GetLocalMySQLConnection();
            DiscordOAuthLinkIdentity($conn, (int)$user['usersId'], (string)$pending['subject']);
            $conn->close();
            $user['discordID'] = $pending['subject'];
            DiscordOAuthClearPending();
            EstablishUserSessionFromData($user, true);
            header('Location: ' . $redirect);
            exit();
        }
        if ($username === '' || !ctype_alnum($username)) {
            throw new RuntimeException('The username must contain only letters or numbers.');
        }
        if (strlen($username) > 128) throw new RuntimeException('The username is too long.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
        if (empty($_POST['acceptTerms'])) throw new RuntimeException('Accept the Terms of Use and Privacy Policy to continue.');
        $redirect = DiscordOAuthSafeReturn($pending['redirect'] ?? '', $pending['site'] ?? 'SWUDeck');
        DiscordOAuthCreateUserFromPending($username, $email, true);
        header('Location: ' . $redirect);
        exit();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$esc = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$discordEmailLocked = !empty($pending['emailVerified']) && !empty($pending['email']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finish creating your account</title>
    <style>
        :root { color-scheme: dark; font-family: Arial, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #111827; color: #f8fafc; }
        main { width: min(440px, calc(100% - 32px)); padding: 24px; border: 1px solid #374151; border-radius: 12px; background: #1f2937; box-shadow: 0 20px 60px rgba(0,0,0,.35); }
        h1 { margin: 0 0 8px; font-size: 24px; }
        p { line-height: 1.45; color: #cbd5e1; }
        form { display: grid; gap: 14px; margin-top: 18px; }
        label { display: grid; gap: 6px; font-weight: 600; }
        input { box-sizing: border-box; width: 100%; padding: 11px 12px; border: 1px solid #4b5563; border-radius: 7px; background: #111827; color: #fff; }
        input[readonly] { color: #cbd5e1; }
        .terms { display: flex; align-items: flex-start; gap: 8px; font-weight: 400; }
        .terms input { width: auto; margin-top: 3px; }
        button, .button { padding: 11px 14px; border: 0; border-radius: 7px; background: #5865f2; color: #fff; font-weight: 700; text-align: center; text-decoration: none; cursor: pointer; }
        .error { padding: 10px 12px; border: 1px solid #b91c1c; border-radius: 7px; background: #450a0a; color: #fecaca; }
        .notice { padding: 12px; border: 1px solid #475569; border-radius: 7px; background: #0f172a; }
        hr { margin: 24px 0; border: 0; border-top: 1px solid #374151; }
        a { color: #a5b4fc; }
    </style>
</head>
<body>
<main>
    <h1>Finish creating your account</h1>
    <p>Discord verified your identity. Choose the username you’ll use across TCGEngine apps.</p>
    <?php if ($error !== ''): ?><div class="error"><?= $esc($error) ?></div><?php endif; ?>

    <?php if ($existingEmailUser): ?>
        <div class="notice">An account already uses the email Discord returned. Log in below to link Discord without creating a duplicate account.</div>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $esc($pending['csrf']) ?>">
            <input type="hidden" name="mode" value="create">
            <label>Username
                <input name="username" value="<?= $esc($username) ?>" maxlength="128" autocomplete="username" required autofocus>
            </label>
            <label>Email
                <input name="email" type="email" value="<?= $esc($email) ?>" autocomplete="email" required <?= $discordEmailLocked ? 'readonly' : '' ?>>
            </label>
            <label class="terms">
                <input name="acceptTerms" type="checkbox" value="1" required>
                <span>I agree to the <a href="/TCGEngine/SharedUI/TermsOfUse.php" target="_blank">Terms of Use</a> and acknowledge the <a href="/TCGEngine/SharedUI/PrivacyPolicy.php" target="_blank">Privacy Policy</a>.</span>
            </label>
            <button type="submit">Create account</button>
        </form>
        <hr>
    <?php endif; ?>

    <h2>Already have an account?</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $esc($pending['csrf']) ?>">
        <input type="hidden" name="mode" value="link">
        <label>Username<input name="loginUsername" autocomplete="username" required></label>
        <label>Password<input name="password" type="password" autocomplete="current-password" required></label>
        <button type="submit">Log in and link Discord</button>
    </form>
</main>
</body>
</html>
