<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_username_rules.php
// Username character rule (owner, 2026-10-09): letters, digits, "." "-" "_" — no spaces, nothing else.
// One definition in AccountFiles/UsernameRules.php; this checks the rule AND that both signup endpoints
// enforce it. ⚠ WRITES NOTHING: every HTTP case sends MISMATCHED passwords, so a name that passes the
// username check stops at "passwords do not match" (the next gate) before any account is created —
// that error IS the proof the name was accepted.
header('Content-Type: text/plain');
require_once __DIR__ . '/../../AccountFiles/UsernameRules.php';

$fails = 0;
$check = function ($cond, $msg) use (&$fails) { echo ($cond ? "PASS: " : "FAIL: ") . $msg . "\n"; if (!$cond) $fails++; };

$valid   = ['OotTheMonk', 'abc123', 'dark_lord', 'han.solo', 'boba-fett', 'a.b-c_d', '_x_', '.', '-', 'A1'];
$invalid = ['', 'han solo', ' leading', 'trailing ', "new\nline", "trail\n", 'tab	bed', 'bang!', 'at@sign', "quo'te",
            'dou"ble', 'back\\slash', 'semi;colon', 'sla/sh', 'per%cent', 'plus+', 'ü-umlaut', 'emoji😀', '<b>'];
foreach ($valid as $u)   $check(UsernameIsValid($u) === true,  'valid:   ' . json_encode($u));
foreach ($invalid as $u) $check(UsernameIsValid($u) === false, 'invalid: ' . json_encode($u));
$check(UsernameIsValid(null) === false && UsernameIsValid(42) === false, 'non-strings are invalid');
// Length cap = the usersUid column (varchar(128)). Boundary PAIR: exactly 128 passes, 129 does not.
$check(USERNAME_MAX_LENGTH === 128, 'the cap matches the varchar(128) usersUid column');
$at = str_repeat('a', USERNAME_MAX_LENGTH); $over = $at . 'a';
$check(UsernameIsValid($at) === true,    'a ' . USERNAME_MAX_LENGTH . '-character name is valid');
$check(UsernameIsValid($over) === false, 'a ' . (USERNAME_MAX_LENGTH + 1) . '-character name is invalid');
$check(UsernameStripInvalidChars('Han Solo!.the_best-1') === 'HanSolo.the_best-1', 'the Discord suggestion keeps . - _ and drops the rest');
$check(UsernameIsValid(UsernameStripInvalidChars('a b!c.d')), 'a stripped suggestion always passes the rule');

// ── Both signup endpoints enforce it ─────────────────────────────────────────────────────────────
$base = 'http://localhost/TCGEngine/';
$api = function ($u) use ($base) {   // AccountFiles/SignupAPI.php (JSON body)
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'ignore_errors' => true,
        'content' => json_encode(['userId' => $u, 'email' => 'nobody@example.com', 'password' => 'aaaaaaaa', 'passwordRepeat' => 'bbbbbbbb'])]]);
    $r = json_decode(strval(@file_get_contents($base . 'AccountFiles/SignupAPI.php', false, $ctx)), true);
    return is_array($r) ? strval($r['error'] ?? '') : 'NO JSON';
};
$form = function ($u) use ($base) {  // Database/signup.inc.php (the signup page's form) — answers with a redirect
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'ignore_errors' => true, 'follow_location' => 0,
        'content' => http_build_query(['submit' => '1', 'uid' => $u, 'email' => 'nobody@example.com', 'pwd' => 'aaaaaaaa', 'pwdrepeat' => 'bbbbbbbb'])]]);
    @file_get_contents($base . 'Database/signup.inc.php', false, $ctx);
    foreach (($http_response_header ?? []) as $h) if (stripos($h, 'Location:') === 0) return $h;
    return 'NO REDIRECT';
};
foreach (['dark_lord', 'han.solo', 'boba-fett', $at] as $u) {
    $e = $api($u);  $check(stripos($e, 'passwords do not match') !== false, "SignupAPI accepts " . json_encode($u) . " (got: $e)");
    $l = $form($u); $check(stripos($l, 'passwordsdontmatch') !== false,     "signup form accepts " . json_encode($u) . " (got: $l)");
}
foreach (['han solo', 'bang!', "quo'te", $over] as $u) {
    $e = $api($u);  $check($e === UsernameRuleMessage(),             "SignupAPI rejects " . json_encode($u) . " (got: $e)");
    $l = $form($u); $check(stripos($l, 'invaliduid') !== false,       "signup form rejects " . json_encode($u) . " (got: $l)");
}

echo $fails ? "\n$fails FAILED\n" : "\nALL PASS\n";
