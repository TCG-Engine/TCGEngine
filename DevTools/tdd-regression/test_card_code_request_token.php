<?php

require_once __DIR__ . '/../../CardEditor/Database/CardCodeRequestToken.php';

$cases = [
    ['custom server header', ['HTTP_X_CARD_CODE_TOKEN' => 'test-custom'], [], 'test-custom'],
    ['custom request header', [], ['x-card-code-token' => 'test-custom'], 'test-custom'],
    ['legacy bearer header', ['HTTP_AUTHORIZATION' => 'Bearer test-bearer'], [], 'test-bearer'],
    ['redirected bearer header', ['REDIRECT_HTTP_AUTHORIZATION' => 'Bearer test-bearer'], [], 'test-bearer'],
    ['custom header takes precedence', ['HTTP_X_CARD_CODE_TOKEN' => 'test-custom', 'HTTP_AUTHORIZATION' => 'Bearer test-bearer'], [], 'test-custom'],
    ['missing token', [], [], ''],
];

foreach ($cases as [$name, $server, $headers, $expected]) {
    if (CardCodeRequestToken($server, $headers) !== $expected) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
