<?php
include_once "../SharedUI/MenuBar.php";
include_once "../SharedUI/Header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWU Stats APIs</title>
    <style>
        body {
            font-family: 'Barlow', sans-serif;
            line-height: 1.6;
            color: #000; /* Darkened from #333 to black */
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        h1, h2, h3 {
            color: #1a2535; /* Darkened from #2c3e50 */
        }
        h1 {
            color: white; /* Setting the main header "SWU STATS APIs" to white */
        }
        .api-section {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .api-section p {
            color: #000; /* Making all paragraphs in API sections black */
            font-weight: 500;
        }
        .api-endpoint {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .api-endpoint p {
            color: #000; /* Ensuring paragraph text is black */
            font-weight: 500; /* Making it slightly bolder */
        }
        .api-endpoint h4 {
            color: #000; /* Making Query Parameters and Example Response headers black */
            font-weight: 600;
        }
        .api-endpoint h5 {
            color: #1a2535; /* Dark color for h5 headings inside API endpoint boxes */
            font-weight: 600;
        }
        code {
            background-color: #272822;
            color: #f8f8f2;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background-color: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .method {
            font-weight: bold;
            display: inline-block;
            width: 60px;
        }
        .get {
            color: #28a745;
        }
        .post {
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            color: #000; /* Ensuring table text is black */
        }
        th {
            background-color: #f2f2f2;
            font-weight: 600; /* Making headers more distinct */
        }
        a {
            color: #ffffff; /* Changed from blue to white */
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            text-decoration: underline;
        }
        /* Make parameter descriptions more readable */
        table td:nth-child(3) {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <h1>SWU Stats APIs</h1>
    <p>Welcome to the SWU Stats API documentation. This page provides information about available APIs. Please note that some APIs may have rate limits or require authentication. For high-volume usage, please contact us to discuss your needs. There is no charge to use SWU Stats APIs, but we ask that you visibly credit SWU Stats on your site if you use them. If you have any questions or need assistance with our APIs, please join our <a href="https://discord.gg/5ZHXyVvVFC" target="_blank">Discord server</a>.</p>

    <div class="api-section">
        <h2>Deck Statistics API</h2>
        <p>Access statistical information about decks, including win rates, matchups, and more.</p>
        
        <div class="api-endpoint">
            <h3>Get Deck Statistics</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/Stats/DeckMetaStatsAPI.php</code></p>
            <p>Retrieve statistics for decks based on different parameters.</p>
            
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>deckId</td>
                    <td>integer</td>
                    <td>The ID of the deck to retrieve stats for</td>
                </tr>
                <tr>
                    <td>startWeek</td>
                    <td>integer</td>
                    <td>(Optional) Start week (inclusive). If omitted and endWeek omitted, defaults to week = 0 (historical/current week depending on server config).</td>
                </tr>
                <tr>
                    <td>endWeek</td>
                    <td>integer</td>
                    <td>(Optional) End week (inclusive). If provided together with startWeek, returns the inclusive range between them.</td>
                </tr>
                <tr>
                    <td>format</td>
                    <td>string</td>
                    <td>(Optional) Filter by game format</td>
                </tr>
            </table>
            
            <h4>Example Response:</h4>
            <pre><code>{
    "leaderID": "0524529055",
    "leaderTitle": "Snap Wexley",
    "leaderSubtitle": "Resistance Recon Expert",
    "baseID": "1029978899",
    "baseTitle": "Colossus",
    "baseSubtitle": "",
    "numPlays": 42,
    "winRate": "66.67",
    "avgTurnsInWins": "7.76",
    "avgTurnsInLosses": "8.36",
    "avgCardsResourcedInWins": "8.02",
    "avgRemainingHealthInWins": "12.28"
}</code></pre>
        </div>
    </div>

    <div class="api-section">
        <h2>Deck Edit API</h2>
        <p>Modify a deck you own (add or remove cards). This endpoint requires OAuth authentication with the 'decks' scope and the caller must be the deck owner.</p>
        <div class="api-endpoint">
            <h3>Edit Deck Card</h3>
            <p><span class="method post">POST</span> <code>/TCGEngine/APIs/EditDeckCard.php</code></p>

            <h4>Authentication:</h4>
            <p>This endpoint requires an OAuth access token. Provide the token via one of the following:</p>
            <ul style="color: #000; font-weight: 500;">
                <li><strong>Authorization header:</strong> <code>Authorization: Bearer {access_token}</code></li>
                <li><strong>JSON body:</strong> <code>{"access_token": "{access_token}"}</code> (recommended for local testing where Authorization headers may be stripped)</li>
            </ul>
            <p><strong>Required scope:</strong> <code>decks</code> — the token must include the <code>decks</code> scope or the API will return HTTP 403 (insufficient_scope).</p>

            <h4>Request Body (JSON):</h4>
            <pre><code>{
    "access_token": "your_oauth_token",    // or provide in Authorization header
    "deckID": 123,                          // integer deck id
    "action": "add|remove",               // add or remove
    "cardID": "CARD_UID",                 // card id used in gamestate
    "count": 1,                             // number to add/remove (optional, default 1)
    "zone": "main|side"                   // zone to change (optional, default "main")
}</code></pre>

            <h4>Example Responses:</h4>
            <h5>Success (HTTP 200)</h5>
            <pre><code>POST /TCGEngine/APIs/EditDeckCard.php
{
    "success": true,
    "deckID": 123,
    "action": "remove",
    "cardID": "CARD_UID",
    "zone": "main",
    "removed": 1
}
</code></pre>

            <h5>Missing or Invalid Token (HTTP 401)</h5>
            <pre><code>{
    "success": false,
    "errors": {
        "access_token": "Missing access token"
    }
}
// or
{
    "success": false,
    "errors": {
        "access_token": "Invalid or expired"
    }
}
</code></pre>

            <h5>Not Owner (HTTP 403)</h5>
            <pre><code>{
    "success": false,
    "error": "Not deck owner"
}
</code></pre>

            <h5>Insufficient Scope (HTTP 403)</h5>
            <p>If the provided access token does not include the required scope the API returns HTTP 403 with the following body:</p>
            <pre><code>{
    "success": false,
    "error": "insufficient_scope",
    "required": "decks"
}
</code></pre>

            <h5>Card Not Present (HTTP 404)</h5>
            <pre><code>{
    "success": false,
    "error": "Card not found in specified zone"
}
</code></pre>
        </div>
    </div>

    <div class="api-section">
        <h2>All Matchup Statistics API</h2>
        <p>Retrieve all matchup statistics across all leader/base combinations. Useful for bulk analysis or building aggregated views.</p>
        <div class="api-endpoint">
            <h3>Get All Matchup Statistics</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/APIs/MetaMatchupStatsAPI.php</code></p>
            <p>Returns every row from the <code>deckmetamatchupstats</code> table for the current week (week = 0).</p>
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>None</td>
                    <td>—</td>
                    <td>This endpoint does not accept query parameters; it returns all rows for week = 0.</td>
                </tr>
            </table>
            <h4>Example Response:</h4>
            <pre><code>[
    {
        "leaderID": "0524529055",
        "baseID": "1029978899",
        "opponentLeaderID": "1234567890",
        "opponentBaseID": "0987654321",
        "numWins": 12,
        "numPlays": 30,
        "playsGoingFirst": 15,
        "turnsInWins": 90,
        "totalTurns": 210,
        "cardsResourcedInWins": 80,
        "totalCardsResourced": 200,
        "remainingHealthInWins": 45,
        "winsGoingFirst": 7,
        "winsGoingSecond": 5
    }
    // More matchup objects...
]</code></pre>
        </div>
    </div>

    <div class="api-section">
        <h2>Deck Matchup Statistics API</h2>
        <p>Access detailed matchup statistics for a specific deck (leader + base combination) against all other decks.</p>
        <div class="api-endpoint">
            <h3>Get Deck Matchup Statistics</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/APIs/DeckMetaMatchupStatsAPI.php</code></p>
            <p>Retrieve matchup statistics for a deck, showing how it performs against other leader/base combinations.</p>
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>leaderID</td>
                    <td>string</td>
                    <td>The ID of the leader for the deck</td>
                </tr>
                <tr>
                    <td>baseID</td>
                    <td>string</td>
                    <td>The ID of the base for the deck</td>
                </tr>
            </table>
            <h4>Example Response:</h4>
            <pre><code>[
    {
        "opponentLeaderID": "1234567890",
        "opponentBaseID": "0987654321",
        "numWins": 12,
        "numPlays": 30,
        "playsGoingFirst": 15,
        "turnsInWins": 90,
        "totalTurns": 210,
        "cardsResourcedInWins": 80,
        "totalCardsResourced": 200,
        "remainingHealthInWins": 45,
        "winsGoingFirst": 7,
        "winsGoingSecond": 5
    }
    // More matchup objects...
]</code></pre>
        </div>
    </div>

    <div class="api-section">
        <h2>Card Statistics API</h2>
        <p>Access statistical information about individual cards, including play rates, resource rates, and more.</p>
        
        <div class="api-endpoint">
            <h3>Get Card Statistics</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/Stats/CardMetaStatsAPI.php</code></p>
            <p>Retrieve statistics for individual cards based on different parameters.</p>
            
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>cardId</td>
                    <td>string</td>
                    <td>The ID of the card to retrieve stats for</td>
                </tr>
                <tr>
                    <td>startWeek</td>
                    <td>integer</td>
                    <td>(Optional) Start week (inclusive). If omitted and endWeek omitted, defaults to week = 0.</td>
                </tr>
                <tr>
                    <td>endWeek</td>
                    <td>integer</td>
                    <td>(Optional) End week (inclusive). If provided together with startWeek, returns the inclusive range between them.</td>
                </tr>
                <tr>
                    <td>format</td>
                    <td>string</td>
                    <td>(Optional) Filter by game format</td>
                </tr>
            </table>
            
            <h4>Example Response:</h4>
            <pre><code>{
    "cardId": "CARD123",
    "name": "Example Card",
    "timesIncluded": 1500,
    "timesPlayed": 850,
    "timesResourced": 420,
    "playRate": 56.7,
    "resourceRate": 28.0,
    "winRateWhenPlayed": 62.3
}</code></pre>
        </div>
    </div>

    <div class="api-section">
        <h2>Game Submission API</h2>
        <p>Submit game results to be included in our statistics database. An API key is required to use this endpoint. Reach out on Discord to get an API key.</p>
        
        <div class="api-endpoint">
            <h3>Submit Game Result</h3>
            <p><span class="method post">POST</span> <code>/TCGEngine/APIs/SubmitGameResult.php</code></p>
            <h4>Request Body:</h4>
            <pre><code>{
    "apiKey": "your_api_key",  // Your API key
    "winner": 1,               // 1 for player 1, 2 for player 2, 0 for draw
    "firstPlayer": 1,          // 1 for player 1, 2 for player 2
    "round": 10,               // Number of turns/rounds played
    "winnerHealth": 5,         // Health remaining for winner
    "winHero": "Red Hero",     // Winner's hero/leader
    "loseHero": "Blue Hero",   // Loser's hero/leader
    "winnerDeck": "Deck List", // Winner's deck contents
    "loserDeck": "Deck List",  // Loser's deck contents
    "player1": "{...}",        // Player 1 stats JSON
    "player2": "{...}",        // Player 2 stats JSON
    "p1DeckLink": "https://swustats.net/path/to/deck?gameName=1234",
    "p2DeckLink": "https://swustats.net/path/to/deck?gameName=5678",
    "p1id": "player1_id",      // Optional player IDs
    "p2id": "player2_id",
    "format": "premier",       // Optional: game format (default: "premier"). Examples: "premier", "preview"
    "disableMetaStats": false, // Optional: set to true if one or more players opts out of meta stats collection
    "gameName": "12345",       // Game identifier
    "sequenceNumber": 1        // Optional sequence number for BO3 matches
}</code></pre>

<h4>Player Stats JSON Format:</h4>
<p>The player stats JSON contains detailed information about a player's performance in the game, including card usage and turn-by-turn statistics.</p>
<pre><code>{
    "gameId": "12345",              // Unique game identifier (optional)
    "gameName": "match_12345",      // Custom game name (optional)
    "deckId": "NextTurn.php?gameName=1787&playerID=1&folderPath=SWUDeck", // The last part of the deck link
    "leader": "12345",              // Leader id (FFG UID format)
    "base": "12345",                // Base id (FFG UID format)
    "turns": 8,                     // Number of turns played
    "result": 1,                    // 1 if this player won, 0 for loss
    "firstPlayer": 1,               // 1 if this player went first, 0 otherwise
    "opposingHero": "12345",        // Opponent's leader id (FFG UID format)
    "opposingBaseColor": "Red",     // Opponent's base color (Red, Blue, Yellow, Green, Colorless)
    "deckbuilderID": "user_42",     // Deckbuilder user ID
    "cardResults": [
        {
            "cardId": "12345",      // Card id (FFG UID format)
            "played": 1,            // Times played
            "resourced": 1,         // Times used as resource
            "activated": 2,         // Times ability activated
            "drawn": 2,             // Times drawn
            "discarded": 0          // Times discarded
        },
        // More cards...
    ],
    "turnResults": [
        {
            "cardsUsed": 3,         // Cards played this turn
            "resourcesUsed": 4,     // Resources spent
            "resourcesLeft": 0,     // Resources remaining
            "cardsLeft": 2,         // Cards left in hand
            "damageDealt": 5,       // Damage dealt this turn
            "damageTaken": 3        // Damage received this turn
        },
        // More turn results...
    ]
}</code></pre>
            
            <h4>Example Response:</h4>
            <h5>Success (HTTP 200)</h5>
            <pre><code>{
    "success": true,
    "gameResultID": 12345
}</code></pre>

            <h5>Invalid/Expired Tokens (HTTP 401)</h5>
            <p>If one or more provided SWU OAuth tokens are invalid or expired the API returns a 401 with a structured <code>errors</code> object describing which token(s) failed.</p>
            <pre><code>{
    "success": false,
    "errors": {
        "p1SWUStatsToken": "Invalid or expired",
        "p2SWUStatsToken": "Invalid or expired"
    }
}</code></pre>
        </div>
    </div>

    <div class="api-section">
        <h2>Melee Tournaments API</h2>
        <p>Access information about Melee tournaments including tournament details, links, and dates.</p>
        
        <div class="api-endpoint">
            <h3>Get Melee Tournaments</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/APIs/GetMeleeTournaments.php</code></p>
            <p>Retrieve a list of Melee tournaments with optional filtering and pagination.</p>
            
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>id</td>
                    <td>integer</td>
                    <td>(Optional) Specific tournament ID to retrieve</td>
                </tr>
                <tr>
                    <td>limit</td>
                    <td>integer</td>
                    <td>(Optional) Maximum number of tournaments to return (default: 50)</td>
                </tr>
                <tr>
                    <td>offset</td>
                    <td>integer</td>
                    <td>(Optional) Number of tournaments to skip for pagination (default: 0)</td>
                </tr>
                <tr>
                    <td>date_from</td>
                    <td>string</td>
                    <td>(Optional) Filter tournaments after this date (YYYY-MM-DD)</td>
                </tr>
                <tr>
                    <td>date_to</td>
                    <td>string</td>
                    <td>(Optional) Filter tournaments before this date (YYYY-MM-DD)</td>
                </tr>
                <tr>
                    <td>sort</td>
                    <td>string</td>
                    <td>(Optional) Sort tournaments by field and direction (e.g., "tournamentDate DESC", default: "tournamentDate DESC")</td>
                </tr>
                <tr>
                    <td>format</td>
                    <td>string</td>
                    <td>(Optional) Response format: "json" or "html" (default: "json")</td>
                </tr>
            </table>
            
            <h4>Example Response:</h4>
            <pre><code>{
    "success": true,
    "total": 42,
    "count": 20,
    "offset": 0,
    "limit": 20,
    "tournaments": [
        {
            "id": 1,
            "name": "Star Wars: Unlimited Sector Qualifier - Milan, Italy | Melee",
            "date": "2025-03-30",
            "link": 270771,
            "melee_url": "https://melee.gg/tournament/270771"
        },
        // More tournaments...
    ]
}</code></pre>
        </div>
        
        <div class="api-endpoint">
            <h3>Get Melee Tournament Details</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/APIs/GetMeleeTournament.php</code></p>
            <p>Retrieve detailed information for a specific Melee tournament, including standings, matchups, and player statistics.</p>
            
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>id</td>
                    <td>integer</td>
                    <td>(Required) The ID of the tournament to retrieve details for</td>
                </tr>
                <tr>
                    <td>include_matchups</td>
                    <td>integer</td>
                    <td>(Optional) Set to 1 to include detailed matchup data (default: 0)</td>
                </tr>
            </table>
            
            <h4>Example Response:</h4>
            <pre><code>{
    "success": true,
    "tournament": {
        "id": 1,
        "name": "Star Wars: Unlimited Sector Qualifier - Milan, Italy | Melee",
        "date": "2025-03-30",
        "link": 270771,
        "round_id": 977630,
        "melee_url": "https://melee.gg/tournament/270771"
    },
    "decks_count": 32,
    "decks": [
        {
            "id": 1,
            "player": "PlayerName",
            "meleeId": 123456,
            "rank": 1,
            "standings": {
                "match_record": "7-0-0",
                "match_wins": 7,
                "match_losses": 0,
                "match_draws": 0,
                "match_win_rate": 100,
                "game_record": "14-2-0",
                "game_wins": 14,
                "game_losses": 2,
                "game_draws": 0,
                "game_win_rate": 87.5
            },
            "points": 21,
            "tiebreakers": {
                "omwp": 68.57,
                "tgwp": 87.5,
                "ogwp": 65.24
            },
            "matchups": [
                {
                    "opponent_id": 5,
                    "opponent_name": "OpponentName",
                    "wins": 2,
                    "losses": 0,
                    "draws": 0,
                    "result": "2-0-0"
                },
                // More matchups...
            ]
        },
        // More decks/players...
    ]
}</code></pre>
        </div>
    </div>

    <div class="api-section">
        <h2>Deck API</h2>
        <p>Access and manage deck information.</p>
        
        <div class="api-endpoint">
            <h3>Get User Decks</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/APIs/UserAPIs/GetUserDecks.php</code></p>
            <p>Retrieve a list of decks owned by the authenticated user, including those marked as favorites. This API requires OAuth authentication with the 'decks' scope.</p>
            
            <h4>Authentication:</h4>
            <p>This endpoint requires OAuth 2.0 authentication with the 'decks' scope. The access token must be provided in one of the following ways:</p>
            <ul style="color: #000; font-weight: 500;">
                <li><strong>Authorization header:</strong> <code>Authorization: Bearer {access_token}</code></li>
                <li><strong>Query parameter:</strong> <code>access_token={access_token}</code></li>
            </ul>
            
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>limit</td>
                    <td>integer</td>
                    <td>(Optional) Maximum number of decks to return (default: 100)</td>
                </tr>
                <tr>
                    <td>offset</td>
                    <td>integer</td>
                    <td>(Optional) Number of decks to skip for pagination (default: 0)</td>
                </tr>
                <tr>
                    <td>sort</td>
                    <td>string</td>
                    <td>(Optional) Sort field: 'name' or 'date' (default: 'name')</td>
                </tr>
                <tr>
                    <td>order</td>
                    <td>string</td>
                    <td>(Optional) Sort order: 'asc' or 'desc' (default: 'asc')</td>
                </tr>
                <tr>
                    <td>favorites</td>
                    <td>string</td>
                    <td>(Optional) Set to 'true' to only return favorite decks</td>
                </tr>
            </table>
            
            <h4>Example Response:</h4>
            <pre><code>{
    "decks": [
        {
            "id": 123,
            "name": "My Favorite Deck",
            "description": "A powerful deck for tournaments",
            "visibility": 1,
            "created_at": "2025-01-01 12:00:00",
            "updated_at": "2025-04-20 15:30:00",
            "is_favorite": true
        },
        {
            "id": 456,
            "name": "Another Deck",
            "description": "Casual play deck",
            "visibility": 2,
            "created_at": "2025-02-15 09:30:00",
            "updated_at": "2025-04-18 14:15:00",
            "is_favorite": false
        }
    ],
    "pagination": {
        "total": 42,
        "limit": 100,
        "offset": 0,
        "has_more": false
    }
}</code></pre>
        </div>
        
        <div class="api-endpoint">
            <h3>Load Deck</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/APIs/LoadDeck.php</code></p>
            <p>Load a deck by its ID.</p>
            
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>deckID</td>
                    <td>integer</td>
                    <td>The ID of the deck to load</td>
                </tr>
            </table>
            
            <h4>Example Response:</h4>
            <pre><code>{
    "deckId": 12345,
    "name": "Example Deck",
    "hero": "Red Hero",
    "cards": [
        {
            "cardId": "CARD123",
            "count": 3
        },
        // More cards...
    ]
}</code></pre>
        </div>
    </div>

    <div class="api-section">
        <div class="api-endpoint">
            <h3>OAuth-based Semantic Search</h3>
            <p><span class="method get">GET</span> <code>/TCGEngine/AIEndpoints/FullElasticSearchOAuth.php</code></p>
            <p>Search for cards using OAuth authentication. Requires a valid OAuth access token with the 'search' scope. User must be a current Patreon subscriber.</p>
            
            <h4>Query Parameters:</h4>
            <table>
                <tr>
                    <th>Parameter</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
                <tr>
                    <td>request</td>
                    <td>string</td>
                    <td>The natural language card search query (URL encoded)</td>
                </tr>
                <tr>
                    <td>access_token</td>
                    <td>string</td>
                    <td>OAuth access token with 'search' scope (or provide via Authorization header)</td>
                </tr>
            </table>
            
            <h4>Example Response:</h4>
            <pre><code>{
    "message": "specificCards=uuid1,uuid2,uuid3,uuid4,uuid5"
}</code></pre>
        </div>
    </div>

</body>
</html>