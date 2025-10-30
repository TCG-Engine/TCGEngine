<?php
// DevTools/test_edit_deck_card.php
// Simple CLI test harness for APIs/EditDeckCard.php

require_once "../APIKeys/APIKeys.php";

if(isset($isProduction) && $isProduction) {
    exit(1);
}

$apiUrl = 'http://localhost/TCGEngine/APIs/EditDeckCard.php';

// Fill these placeholders with real tokens from your DB for testing
$validToken = 'e12cb1722a0cdf7ad22adf9578904235ef6ac720';
$expiredToken = 'REPLACE_WITH_EXPIRED_TOKEN';
$notOwnerToken = 'abc123notownertoken';

function httpPostJson($url, $data, $token = null) {
    $ch = curl_init($url);
    // If a token is provided, also include it in the JSON body for reliable local testing
    if ($token && is_array($data)) {
        $data['access_token'] = $token;
    }
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    if ($token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ], ['Authorization: Bearer ' . $token]));
    }
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $err = curl_error($ch);
    curl_close($ch);
    return [
        'http_code' => $info['http_code'],
        'body' => $resp,
        'error' => $err
    ];
}

// Test scenarios (adjust deckID and cardID to valid values in your environment)
$deckID = 196;
$cardID = '2524528997';

$tests = [
    [
        'name' => 'Add card with valid token',
        'token' => $validToken,
        'payload' => array('deckID' => $deckID, 'action' => 'add', 'cardID' => $cardID, 'count' => 1, 'zone' => 'main')
    ],
    [
        'name' => 'Remove card with valid token',
        'token' => $validToken,
        'payload' => array('deckID' => $deckID, 'action' => 'remove', 'cardID' => $cardID, 'count' => 1, 'zone' => 'main')
    ],
    [
        'name' => 'Remove card that does not exist with valid token',
        'token' => $validToken,
        'payload' => array('deckID' => $deckID, 'action' => 'remove', 'cardID' => $cardID, 'count' => 1, 'zone' => 'main')
    ],
    [
        'name' => 'Add with expired token (expect 401)',
        'token' => $expiredToken,
        'payload' => array('deckID' => $deckID, 'action' => 'add', 'cardID' => $cardID, 'count' => 1, 'zone' => 'main')
    ],
    [
        'name' => 'Add with not-owner token (expect 403)',
        'token' => $notOwnerToken,
        'payload' => array('deckID' => $deckID, 'action' => 'add', 'cardID' => $cardID, 'count' => 1, 'zone' => 'main')
    ],
    [
        'name' => 'Missing token (expect 401)',
        'token' => null,
        'payload' => array('deckID' => $deckID, 'action' => 'add', 'cardID' => $cardID, 'count' => 1, 'zone' => 'main')
    ]
];
header('Content-Type: text/html; charset=utf-8');

echo "Running EditDeckCard tests against: " . htmlspecialchars($apiUrl) . "<br><br>";
foreach ($tests as $t) {
    echo "== " . htmlspecialchars($t['name']) . " ==<br>";
    $res = httpPostJson($apiUrl, $t['payload'], $t['token']);
    echo "HTTP: " . htmlspecialchars((string)$res['http_code']) . "<br>";
    if ($res['error']) echo "cURL error: " . htmlspecialchars($res['error']) . "<br>";
    echo "Body:<br>" . nl2br(htmlspecialchars((string)$res['body'])) . "<br><br>";
}

echo "Tests complete.<br>";

// Usage: php DevTools/test_edit_deck_card.php
