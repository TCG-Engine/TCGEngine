<?php

include_once "./APIKeys/APIKeys.php";

$botToken = $discordBotToken;  // Replace with your bot token
$applicationId = isset($discordClientID) ? $discordClientID : '1338995198730043432';
$guildId = 'YOUR_GUILD_ID_HERE';  // For guild-specific commands (optional)

$url = "https://discord.com/api/v10/applications/$applicationId/commands";  // Use /guilds/$guildId/commands for guild commands

//Soul Masters
/*
$commands = [
    [
        "name" => "card",
        "description" => "Fetches details for a given card",
        "type" => 1,
        "options" => [
            [
                "name" => "name",
                "type" => 3, // 3 = STRING
                "description" => "The name of the card",
                "required" => true
            ]
        ]
    ],
    [
        "name" => "me",
        "description" => "Fetches info about the user",
        "type" => 1,
        "options" => [
        ]
    ]
];
*/
//SWU
$commands = [
    [
        "name" => "ping",
        "description" => "Replies with Pong!",
        "type" => 1 // 1 = CHAT_INPUT (Slash Command)
    ],
    [
        "name" => "card",
        "description" => "Fetches details for a given card",
        "type" => 1,
        "options" => [
            [
                "name" => "name",
                "type" => 3, // 3 = STRING
                "description" => "The name of the card",
                "required" => true
            ]
        ]
    ],
    [
        "name" => "cardstats",
        "description" => "Fetches stats for a given card",
        "type" => 1,
        "options" => [
            [
                "name" => "name",
                "type" => 3, // 3 = STRING
                "description" => "The name of the card",
                "required" => true
            ]
        ]
    ],
    [
        "name" => "me",
        "description" => "Fetches info about the user",
        "type" => 1,
        "options" => [
        ]
    ],
    [
        "name" => "deck",
        "description" => "Fetches info about the user's deck",
        "type" => 1,
        "options" => [
            [
                "name" => "name",
                "type" => 3, // 3 = STRING
                "description" => "The name of the deck",
                "required" => true
            ]
        ]
    ],
    [
        "name" => "rules",
        "description" => "<BETA> Uses AI to search relevant rules and rulings",
        "type" => 1,
        "options" => [
            [
                "name" => "query",
                "type" => 3, // 3 = STRING
                "description" => "The search query",
                "required" => true
            ]
        ]
    ]
];

$jsonData = json_encode($commands);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bot $botToken",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

$response = curl_exec($ch);
curl_close($ch);

echo "Response: $response\n";
?>