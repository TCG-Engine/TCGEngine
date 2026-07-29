<?php
include_once "../SharedUI/MenuBar.php";
require_once "../SharedUI/Render/Head.php"; echo RenderSiteStyles("SWUDeck");
include_once "../SharedUI/Header.php";

// Get tournament ID from URL parameter
$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if no tournament ID is provided
if ($tournamentId <= 0) {
    header("Location: MeleeTournaments.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Results - SWU Stats</title>
    <style>
        body {
            font-family: 'Barlow', sans-serif;
            line-height: 1.6;
            color: var(--text);
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        h1, h2, h3, h4 {
            color: white;
        }
        .tournament-header {
            margin-bottom: 30px;
        }
        .tournament-meta {
            background-color: var(--surface-raised);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: white;
        }
        .tournament-meta p {
            margin: 5px 0;
        }
        .tournament-meta a {
            color: var(--accent);
            text-decoration: none;
        }
        .tournament-meta a:hover {
            text-decoration: underline;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background-color: var(--surface-raised);
            color: white;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }
        .tab.active {
            background-color: var(--border);
            font-weight: bold;
        }
        .tab-content {
            display: none;
            background-color: var(--overlay-scrim);
            padding: 20px;
            border-radius: 0 5px 5px 5px;
        }
        .tab-content.active {
            display: block;
        }
        .hidden-tab {
            display: none; /* Hide specific tabs */
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            color: white;
        }
        th {
            background-color: var(--surface-raised);
            font-weight: 600;
        }
        tr:hover {
            background-color: var(--surface-raised);
        }
        .player-record {
            display: inline-block;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
            padding: 3px 8px;
            margin-right: 10px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .matchup-row {
            display: grid;
            grid-template-columns: 3fr 1fr 3fr;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
            padding: 8px;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
        }
        .player-name {
            text-align: right;
        }
        .opponent-name {
            text-align: left;
        }
        .match-result {
            text-align: center;
            font-weight: bold;
            background-color: var(--surface-raised);
            padding: 4px;
            border-radius: 4px;
        }
        .win {
            color: var(--success);
        }
        .loss {
            color: var(--danger);
        }
        .draw {
            color: var(--accent-gold);
        }
        .loading {
            text-align: center;
            padding: 50px;
            color: white;
            font-size: 18px;
        }
        .error {
            background-color: var(--danger);
            color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin-top: 30px;
        }
        .stat-box {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 8px 15px;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .stat-box span {
            font-weight: bold;
            color: white;
        }
        .tiebreaker {
            font-size: 0.9em;
            color: var(--text-muted);
        }
        /* Leader Analysis Styles */
        .chart-container {
            margin: 20px 0;
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
        }
        .chart-title {
            margin-bottom: 15px;
            font-weight: bold;
            color: white;
        }
        .meta-chart {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .meta-bar {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 10px;
        }
        .bar {
            width: 40px;
            background-color: var(--accent);
            margin-bottom: 5px;
            border-radius: 3px 3px 0 0;
            position: relative;
            transition: all 0.3s;
        }
        .bar:hover {
            background-color: var(--accent);
            filter: brightness(1.2);
        }
        .bar-label {
            text-align: center;
            font-size: 0.8em;
            color: white;
            word-break: break-word;
            max-width: 60px;
        }
        .bar-value {
            position: absolute;
            bottom: -20px;
            font-size: 0.8em;
            color: white;
        }
        .matchup-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 1px;
            margin-top: 20px;
        }
        .matchup-table th {
            font-size: 0.8em;
            padding: 5px;
            text-align: center;
            vertical-align: middle;
            min-width: 60px;
        }
        .matchup-table td {
            text-align: center;
            padding: 5px;
            font-size: 0.9em;
            position: relative;
        }
        .matchup-table td:hover {
            filter: brightness(1.2);
        }
        .matchup-cell {
            border-radius: 3px;
            padding: 5px;
        }
        .matchup-win {
            background-color: var(--success);
        }
        .matchup-loss {
            background-color: var(--danger);
        }
        .matchup-even {
            background-color: var(--accent-gold);
        }
        .matchup-na {
            background-color: var(--surface-sunken);
            color: var(--text-muted);
        }
        .leader-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid var(--surface-raised);
            transition: transform 0.2s;
        }
        .leader-img:hover {
            transform: scale(1.2);
            border-color: var(--accent);
            z-index: 5;
            cursor: pointer;
        }
        .archetype-grid {
            display: flex; flex-wrap: wrap; gap: 12px;
        }
        .archetype-tile {
            display: flex; flex-direction: column; align-items: center;
            padding: 8px; min-width: 96px; cursor: pointer;
            background: var(--surface-raised); border: 1px solid var(--border);
        }
        .archetype-tile:hover { box-shadow: 0 0 10px rgba(var(--accent-rgb), 0.35); }
        .archetype-tile .tile-imgs { display: flex; gap: 4px; }
        .archetype-tile img { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; }
        .archetype-tile .tile-meta { margin-top: 6px; font-size: 12px; text-align: center; }
        .archetype-detail-head { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .archetype-detail-head .tile-imgs { display: flex; gap: 4px; }
        .archetype-detail-head img { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; }
        .archetype-back { cursor: pointer; text-decoration: underline; }
        .archetype-rows { width: 100%; border-collapse: collapse; }
        .archetype-rows th, .archetype-rows td { padding: 6px 10px; text-align: left; }
        .archetype-rows tr.thin { opacity: 0.5; }
        .archetype-rows .opp-cell { display: inline-flex; align-items: center; gap: 8px; vertical-align: middle; }
        .archetype-rows .opp-imgs { display: flex; gap: 3px; flex: 0 0 auto; }
        .archetype-rows .opp-imgs img { width: 30px; height: 30px; object-fit: cover; border-radius: 3px; }
        .archetype-rows tr.mirror td:first-child::after { content: ' (mirror)'; opacity: 0.7; }
        .archetype-divider td { padding-top: 14px; font-size: 12px; opacity: 0.7; }
        .archetype-notice { margin: 8px 0; font-size: 13px; opacity: 0.8; }
        .leader-tooltip {
            position: absolute;
            background-color: var(--overlay-scrim);
            color: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            z-index: 10;
            text-align: center;
            min-width: 150px;
            display: none;
            pointer-events: none;
        }
        .leader-tooltip img {
            max-width: 150px;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        .leader-tooltip h4 {
            margin: 0 0 5px 0;
            font-weight: bold;
            color: white;
        }
        .leader-tooltip p {
            margin: 0;
            font-size: 0.85em;
            color: var(--text-muted);
        }
        .pie-chart {
            position: relative;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 20px auto;
        }
        .pie-segment {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            clip: rect(0, 200px, 200px, 100px);
        }
        .pie-label {
            text-align: center;
            margin-top: 10px;
            color: white;
            font-size: 0.9em;
        }
        .leader-card {
            display: inline-flex;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 5px;
            margin: 5px;
            padding: 10px;
            flex-direction: column;
            align-items: center;
            width: 120px;
        }
        .leader-card-name {
            font-weight: bold;
            margin-bottom: 5px;
            text-align: center;
            color: white;
        }
        .leader-card-stats {
            font-size: 0.9em;
            color: white;
        }
        .flex-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }
        .flex-column {
            flex: 1;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <div class="tournament-header">
        <h1>Tournament Results</h1>
        <div id="tournament-meta" class="tournament-meta">
            <div class="loading">Loading tournament data...</div>
        </div>
    </div>
    
    <div class="tabs">
        <div class="tab active" data-tab="standings">Standings</div>
        <div class="tab hidden-tab" data-tab="matchups">Matchups</div>
        <div class="tab" data-tab="stats">Statistics</div>
        <div class="tab" data-tab="meta-share">Meta Share</div>
        <div class="tab" data-tab="matchup-matrix">Matchup Matrix</div>
    </div>
    
    <div id="standings" class="tab-content active">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Player</th>
                    <th>Record</th>
                    <th>Points</th>
                    <th>Tiebreakers</th>
                </tr>
            </thead>
            <tbody id="standings-body">
                <!-- Standings will be inserted here -->
            </tbody>
        </table>
    </div>
    
    <div id="matchups" class="tab-content">
        <div id="matchups-container">
            <!-- Matchups will be inserted here -->
        </div>
    </div>
    
    <div id="stats" class="tab-content">
        <h3>Tournament Statistics</h3>
        <div id="tournament-stats">
            <!-- Tournament statistics will be inserted here -->
        </div>
        
        <h3>Player Statistics</h3>
        <table>
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Match Win Rate</th>
                    <th>Game Win Rate</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody id="stats-body">
                <!-- Player statistics will be inserted here -->
            </tbody>
        </table>
    </div>
    
    <div id="meta-share" class="tab-content">
        <h3>Meta Share Analysis</h3>
        
        <div class="flex-container">
            <div class="flex-column">
                <div class="chart-container">
                    <div class="chart-title">Leader Meta Share</div>
                    <div id="leader-meta-chart" class="meta-chart">
                        <!-- Leader meta share chart will be inserted here -->
                    </div>
                </div>
                
                <div class="chart-container">
                    <div class="chart-title">Leader/Base Combo Meta Share</div>
                    <div id="combo-meta-chart" class="meta-chart">
                        <!-- Leader/base combo meta share chart will be inserted here -->
                    </div>
                </div>
            </div>
            
            <div class="flex-column">
                <div class="chart-container">
                    <div class="chart-title">Top Performing Leaders</div>
                    <div id="leader-performance">
                        <!-- Top performing leaders will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="matchup-matrix" class="tab-content">
        <div class="chart-container">
            <div id="archetype-explorer">
                <!-- Archetype gallery / matchup detail will be inserted here -->
            </div>
        </div>
    </div>
    
    <script>
        // Configuration
        const tournamentId = <?php echo $tournamentId; ?>;
        const apiUrl = '../APIs/GetMeleeTournament.php';
        
        // DOM elements
        const tournamentMeta = document.getElementById('tournament-meta');
        const standingsBody = document.getElementById('standings-body');
        const matchupsContainer = document.getElementById('matchups-container');
        const tournamentStats = document.getElementById('tournament-stats');
        const statsBody = document.getElementById('stats-body');
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        // Tab functionality
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Update active content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) {
                        content.classList.add('active');
                    }
                });
            });
        });
        
        // Fetch tournament data
        async function fetchTournamentData() {
            try {
                const response = await fetch(`${apiUrl}?id=${tournamentId}&include_matchups=1`);
                if (!response.ok) {
                    throw new Error('Failed to fetch tournament data');
                }
                
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load tournament data');
                }
                
                // Render tournament data
                renderTournamentMeta(data.tournament);
                renderStandings(data.decks);
                renderMatchups(data.decks);
                renderStatistics(data.tournament, data.decks);
                renderLeaderAnalysis(data.decks); // Add new leader analysis function
                
            } catch (error) {
                console.error('Error:', error);
                showError(error.message);
            }
        }
        
        // Render tournament metadata
        function renderTournamentMeta(tournament) {
            const tournamentDate = new Date(tournament.date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            tournamentMeta.innerHTML = `
                <h2>${escapeHTML(tournament.name)}</h2>
                <p><strong>Date:</strong> ${tournamentDate}</p>
                <p><strong>Players:</strong> <span id="player-count">Loading...</span></p>
                <!--<p><a href="${tournament.melee_url}" target="_blank">View on Melee.gg</a></p>-->
            `;
        }
        
        // Render standings table
        function renderStandings(decks) {
            document.getElementById('player-count').textContent = decks.length;
            standingsBody.innerHTML = '';
            decks.forEach(deck => {
                // Exclude players with 0 points and all tiebreakers at exactly 33.33%
                if (
                    (!deck.points || deck.points === 0) &&
                    deck.tiebreakers &&
                    deck.tiebreakers.omwp === 33.33 &&
                    deck.tiebreakers.tgwp === 33.33 &&
                    deck.tiebreakers.ogwp === 33.33
                ) {
                    return;
                }
                const row = document.createElement('tr');
                let meleeButton = '';
                let playPvpButton = '';
                if (deck.meleeId) {
                    const meleeUrl = `https://melee.gg/Decklist/View/${deck.meleeId}`;
                    meleeButton = `
                        <a href="${meleeUrl}" target="_blank" class="melee-deck-btn" title="View on melee.gg" style="margin-left:6px; padding:2px 8px; background:var(--accent); color:#fff; border-radius:4px; text-decoration:none; font-size:0.9em;">Melee.gg</a>
                        <button class="copy-melee-link-btn" data-link="${meleeUrl}" title="Copy link to clipboard" style="margin-left:3px; background:transparent; border:none; cursor:pointer; vertical-align:middle;">
                            <img src="../Assets/Icons/clipboard-check.svg" width="16" height="16" alt="Copy Link" style="filter:invert(100%); vertical-align:middle;" />
                        </button>
                    `;
                    playPvpButton = `
                        <button class="play-pvp-btn" data-melee-link="${meleeUrl}" title="Play PvP" style="margin-left:3px; background:transparent; border:none; cursor:pointer; vertical-align:middle;">
                            <img src="../Assets/Icons/play.svg" width="18" height="18" alt="Play PvP" style="filter:invert(100%); vertical-align:middle;" />
                        </button>
                    `;
                }
                
                row.innerHTML = `
                    <td>${deck.rank}</td>
                    <td>
                        ${deck.leader && deck.leader.uuid ? `<img src="../SWUDeck/jpg/concat/${deck.leader.uuid}.jpg" alt="${escapeHTML(deck.leader.name || '')}" title="${escapeHTML(deck.leader.name || '')}" style="width:28px; height:28px; object-fit:cover; border-radius:4px; margin-right:2px; vertical-align:middle;" onerror="this.onerror=null;this.src='../SWUDeck/concat/${deck.leader.uuid}.webp';" />` : ''}
                        ${deck.base && deck.base.uuid ? `<img src="../SWUDeck/jpg/concat/${deck.base.uuid}.jpg" alt="${escapeHTML(deck.base.name || '')}" title="${escapeHTML(deck.base.name || '')}" style="width:28px; height:28px; object-fit:cover; border-radius:4px; margin-right:4px; vertical-align:middle;" onerror="this.onerror=null;this.src='../SWUDeck/concat/${deck.base.uuid}.webp';" />` : ''}
                        ${escapeHTML(deck.player)}${meleeButton}${playPvpButton}
                    </td>
                    <td>
                        <span class="player-record">${deck.standings.match_record}</span>
                        <span class="player-record">${deck.standings.game_record}</span>
                    </td>
                    <td>${deck.points}</td>
                    <td>
                        <div class="tiebreaker">OMWP: ${deck.tiebreakers.omwp}%</div>
                        <div class="tiebreaker">TGWP: ${deck.tiebreakers.tgwp}%</div>
                        <div class="tiebreaker">OGWP: ${deck.tiebreakers.ogwp}%</div>
                    </td>
                `;
                
                standingsBody.appendChild(row);
            });
            
            // Add event listener for copy buttons after rendering standings
            setTimeout(() => {
                document.querySelectorAll('.copy-melee-link-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const link = this.getAttribute('data-link');
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(link).then(() => {
                                this.title = 'Copied!';
                                this.style.opacity = '0.6';
                                setTimeout(() => {
                                    this.title = 'Copy link to clipboard';
                                    this.style.opacity = '1';
                                }, 1200);
                            });
                        } else {
                            // Fallback for older browsers
                            const tempInput = document.createElement('input');
                            tempInput.value = link;
                            document.body.appendChild(tempInput);
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);
                            this.title = 'Copied!';
                            this.style.opacity = '0.6';
                            setTimeout(() => {
                                this.title = 'Copy link to clipboard';
                                this.style.opacity = '1';
                            }, 1200);
                        }
                    });
                });
                // Add event listener for Play PvP buttons
                document.querySelectorAll('.play-pvp-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const meleeLink = this.getAttribute('data-melee-link');
                        // Open Petranaki.net in a new window and submit the form to join/create a game
                        const form = document.createElement('form');
                        form.method = 'GET';
                        form.action = 'https://petranaki.net/Arena/CreateGame.php';
                        form.target = '_blank';
                        // Add format (default to premierf)
                        const formatInput = document.createElement('input');
                        formatInput.type = 'hidden';
                        formatInput.name = 'format';
                        formatInput.value = 'premierf';
                        form.appendChild(formatInput);
                        // Add deck link
                        const fabdbInput = document.createElement('input');
                        fabdbInput.type = 'hidden';
                        fabdbInput.name = 'fabdb';
                        fabdbInput.value = meleeLink;
                        form.appendChild(fabdbInput);
                        // Add visibility (public for quick match)
                        const visibilityInput = document.createElement('input');
                        visibilityInput.type = 'hidden';
                        visibilityInput.name = 'visibility';
                        visibilityInput.value = 'public';
                        form.appendChild(visibilityInput);
                        // Add game description
                        const descriptionInput = document.createElement('input');
                        descriptionInput.type = 'hidden';
                        descriptionInput.name = 'gameDescription';
                        descriptionInput.value = 'Quick Match';
                        form.appendChild(descriptionInput);
                        // Submit form
                        document.body.appendChild(form);
                        form.submit();
                        setTimeout(() => document.body.removeChild(form), 1000);
                    });
                });
            }, 0);
        }
        
        // Render matchups
        function renderMatchups(decks) {
            matchupsContainer.innerHTML = '';
            
            // Group matchups by player
            decks.forEach(deck => {
                if (deck.matchups && deck.matchups.length > 0) {
                    const playerSection = document.createElement('div');
                    playerSection.classList.add('player-matchups');
                    
                    playerSection.innerHTML = `
                        <h3>${escapeHTML(deck.player)} (${deck.standings.match_record})</h3>
                    `;
                    
                    deck.matchups.forEach(matchup => {
                        const matchupRow = document.createElement('div');
                        matchupRow.classList.add('matchup-row');
                        
                        // Determine outcome for styling
                        let resultClass = 'draw';
                        if (matchup.wins > matchup.losses) {
                            resultClass = 'win';
                        } else if (matchup.wins < matchup.losses) {
                            resultClass = 'loss';
                        }
                        
                        matchupRow.innerHTML = `
                            <div class="player-name">${escapeHTML(deck.player)}</div>
                            <div class="match-result ${resultClass}">${matchup.result}</div>
                            <div class="opponent-name">${escapeHTML(matchup.opponent_name)}</div>
                        `;
                        
                        playerSection.appendChild(matchupRow);
                    });
                    
                    matchupsContainer.appendChild(playerSection);
                }
            });
            
            if (matchupsContainer.innerHTML === '') {
                matchupsContainer.innerHTML = '<p>No matchup data available for this tournament.</p>';
            }
        }
        
        // Render statistics
        function renderStatistics(tournament, decks) {
            // Calculate tournament-wide statistics
            let totalMatchWins = 0;
            let totalMatchLosses = 0;
            let totalMatchDraws = 0;
            let totalGameWins = 0;
            let totalGameLosses = 0;
            let totalGameDraws = 0;
            
            decks.forEach(deck => {
                totalMatchWins += deck.standings.match_wins;
                totalMatchLosses += deck.standings.match_losses;
                totalMatchDraws += deck.standings.match_draws;
                totalGameWins += deck.standings.game_wins;
                totalGameLosses += deck.standings.game_losses;
                totalGameDraws += deck.standings.game_draws;
            });
            
            const totalMatches = Math.round(totalMatchWins + totalMatchLosses + totalMatchDraws) / 2; // Divide by 2 because each match is counted twice
            const totalGames = Math.round(totalGameWins + totalGameLosses + totalGameDraws) / 2;
            const avgGamesPerMatch = totalMatches > 0 ? (totalGames / totalMatches).toFixed(2) : '0.00';
            
            tournamentStats.innerHTML = `
                <div class="stat-box">Total Players: <span>${decks.length}</span></div>
                <div class="stat-box">Total Matches: <span>${totalMatches}</span></div>
                <div class="stat-box">Total Games: <span>${totalGames}</span></div>
                <div class="stat-box">Avg Games/Match: <span>${avgGamesPerMatch}</span></div>
                <div class="stat-box">Draws: <span>${totalMatchDraws / 2}</span></div>
            `;
            
            // Player statistics table
            statsBody.innerHTML = '';
            decks.forEach(deck => {
                // Exclude players with 0 points and all tiebreakers at exactly 33.33%
                if (
                    (!deck.points || deck.points === 0) &&
                    deck.tiebreakers &&
                    deck.tiebreakers.omwp === 33.33 &&
                    deck.tiebreakers.tgwp === 33.33 &&
                    deck.tiebreakers.ogwp === 33.33
                ) {
                    return;
                }
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHTML(deck.player)}</td>
                    <td>${deck.standings.match_win_rate}% (${deck.standings.match_record})</td>
                    <td>${deck.standings.game_win_rate}% (${deck.standings.game_record})</td>
                    <td>${deck.points}</td>
                `;
                
                statsBody.appendChild(row);
            });
        }
        
        // Show error message
        function showError(message) {
            tournamentMeta.innerHTML = `
                <div class="error">
                    <h2>Error</h2>
                    <p>${escapeHTML(message)}</p>
                    <p><a href="MeleeTournaments.php">Return to Tournament List</a></p>
                </div>
            `;
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.innerHTML = '';
            });
        }
        
        // Security helper
        function escapeHTML(str) {
            if (!str) return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
        
        // Leader Analysis Functions
        function renderLeaderAnalysis(decks) {
            // Store decks data globally for access in chart rendering
            window.decksData = decks;
            
            // Extract leader data
            const leaderMetaShare = calculateLeaderMetaShare(decks);
            const leaderComboMetaShare = calculateLeaderComboMetaShare(decks);
            const leaderPerformance = calculateLeaderPerformance(decks);
            const archetypeMatchups = calculateArchetypeMatchups(decks);
            window.archetypeMatchups = archetypeMatchups;   // read by the harness checks
            
            // Render charts and tables
            renderLeaderMetaChart(leaderMetaShare);
            renderLeaderComboChart(leaderComboMetaShare);
            renderLeaderPerformanceCards(leaderPerformance);
            renderArchetypeExplorer(archetypeMatchups);
        }
        
        // Calculate leader meta share
        function calculateLeaderMetaShare(decks) {
            const leaderCounts = {};
            const totalDecks = decks.length;
            
            // Count leaders
            decks.forEach(deck => {
                // Use the leader name if available, otherwise use the UUID
                const leaderName = deck.leader && deck.leader.name ? deck.leader.name : (deck.leader && deck.leader.uuid ? deck.leader.uuid : 'Unknown');
                leaderCounts[leaderName] = (leaderCounts[leaderName] || 0) + 1;
            });
            
            // Calculate percentages and sort by popularity
            const leaderMetaShare = Object.keys(leaderCounts).map(leader => ({
                name: leader,
                count: leaderCounts[leader],
                percentage: (leaderCounts[leader] / totalDecks * 100).toFixed(1)
            }));
            
            // Sort by count descending
            leaderMetaShare.sort((a, b) => b.count - a.count);
            
            return leaderMetaShare;
        }
        
        // Calculate leader/base combo meta share.
        // Bases are bucketed server-side (see APIs/GetMeleeTournament.php): functionally
        // identical common bases share a groupKey, so they aggregate into one entry.
        function calculateLeaderComboMetaShare(decks) {
            const comboCounts = {};
            const totalDecks = decks.length;

            decks.forEach(deck => {
                const leaderName = deck.leader && deck.leader.name
                    ? deck.leader.name
                    : (deck.leader && deck.leader.uuid ? deck.leader.uuid : 'Unknown');
                const leaderUuid = deck.leader && deck.leader.uuid ? deck.leader.uuid : null;

                // Fall back to the raw base so an older/cached API response still renders.
                const base = deck.base || {};
                const baseKey = base.groupKey || base.uuid || 'Unknown';
                const baseLabel = base.groupLabel || base.name || base.uuid || 'Unknown';
                const baseUuid = base.groupUuid || base.uuid || null;

                // Structured key: never re-parsed, so a leader title containing '/' is safe.
                const key = `${leaderUuid || leaderName}||${baseKey}`;
                if (!comboCounts[key]) {
                    comboCounts[key] = { leaderName, leaderUuid, baseLabel, baseUuid, count: 0 };
                }
                comboCounts[key].count++;
            });

            const comboMetaShare = Object.keys(comboCounts).map(key => {
                const c = comboCounts[key];
                return {
                    key: key,
                    name: `${c.leaderName} / ${c.baseLabel}`,  // display + bar-colour hash only
                    leaderName: c.leaderName,
                    leaderUuid: c.leaderUuid,
                    baseLabel: c.baseLabel,
                    baseUuid: c.baseUuid,
                    count: c.count,
                    percentage: (c.count / totalDecks * 100).toFixed(1)
                };
            });

            // Sort by count descending
            comboMetaShare.sort((a, b) => b.count - a.count);

            // Limit to top 10 for readability
            return comboMetaShare.slice(0, 10);
        }

        // ---- Archetype matchup explorer -------------------------------------------------
        // Archetype identity. MUST stay byte-identical to the key scheme used by
        // calculateLeaderComboMetaShare(), or the two views disagree about what an
        // archetype is (notably for decks with no leader UUID, i.e. no-shows).
        function archetypeIdentity(deck) {
            const l = (deck && deck.leader) || {};
            const b = (deck && deck.base) || {};
            const leaderName = l.name || l.uuid || 'Unknown';
            const leaderUuid = l.uuid || null;
            const baseKey = b.groupKey || b.uuid || 'Unknown';
            return {
                key: `${leaderUuid || leaderName}||${baseKey}`,
                leaderName: leaderName,
                leaderUuid: leaderUuid,
                baseLabel: b.groupLabel || b.name || b.uuid || 'Unknown',
                baseUuid: b.groupUuid || b.uuid || null
            };
        }

        // Same identity, derived from a matchup's opponent_* fields.
        function opponentIdentity(matchup) {
            return archetypeIdentity({
                leader: matchup.opponent_leader,
                base: matchup.opponent_base
            });
        }

        // One entry per leader/base archetype in this tournament, each carrying its full
        // opponent list. Unit of measure is MATCHES: a matchup row is one match whose
        // wins/losses are games within it, so a match is won when wins > losses.
        function calculateArchetypeMatchups(decks) {
            const archetypes = {};

            decks.forEach(deck => {
                const me = archetypeIdentity(deck);
                if (!archetypes[me.key]) {
                    archetypes[me.key] = Object.assign({}, me, {
                        deckCount: 0, totalMatches: 0, opponentMap: {}
                    });
                }
                const a = archetypes[me.key];
                a.deckCount++;

                (deck.matchups || []).forEach(m => {
                    const opp = opponentIdentity(m);
                    if (!a.opponentMap[opp.key]) {
                        a.opponentMap[opp.key] = Object.assign({}, opp, {
                            matchWins: 0, matchLosses: 0, matchDraws: 0, matches: 0,
                            isMirror: opp.key === me.key
                        });
                    }
                    const o = a.opponentMap[opp.key];
                    // Own perspective ONLY. The old calculateLeaderMatchups() also wrote the
                    // opponent's side of each pairing, which double-counted every match.
                    const w = m.wins || 0, l = m.losses || 0;
                    if (w > l) o.matchWins++;
                    else if (w < l) o.matchLosses++;
                    else o.matchDraws++;
                    o.matches++;
                    a.totalMatches++;
                });
            });

            return Object.keys(archetypes).map(k => {
                const a = archetypes[k];
                const opponents = Object.keys(a.opponentMap)
                    .map(ok => a.opponentMap[ok])
                    .sort((x, y) => y.matches - x.matches
                                 || x.leaderName.localeCompare(y.leaderName));
                delete a.opponentMap;
                return Object.assign({}, a, { opponents: opponents });
            }).sort((x, y) => y.deckCount - x.deckCount
                           || x.leaderName.localeCompare(y.leaderName));
        }

        // Win rate over MATCHES; draws are shown in the record but excluded here.
        // Returns null below the display threshold so callers cannot print a percentage
        // for a sample too thin to support one.
        const ARCHETYPE_MIN_MATCHES = 4;
        function archetypeWinRate(opponent) {
            if (opponent.matches < ARCHETYPE_MIN_MATCHES) return null;
            const decided = opponent.matchWins + opponent.matchLosses;
            if (decided === 0) return null;
            return (opponent.matchWins / decided) * 100;
        }

        // Two-state view inside the Matchup Matrix tab: a gallery of every archetype, and a
        // per-archetype matchup list. Detail replaces the gallery in place; no URL state.
        function renderArchetypeExplorer(archetypes) {
            const container = document.getElementById('archetype-explorer');
            const totalDecks = archetypes.reduce((s, a) => s + a.deckCount, 0);

            function cardImg(uuid, alt) {
                if (!uuid) return '';
                return `<img src="../SWUDeck/jpg/concat/${uuid}.jpg" alt="${escapeHTML(alt)}" title="${escapeHTML(alt)}"
                             onerror="this.onerror=null;this.src='../SWUDeck/concat/${uuid}.webp';">`;
            }

            function renderGallery() {
                container.innerHTML = '';
                if (archetypes.length === 0) {
                    container.innerHTML = '<p>No archetype data available.</p>';
                    return;
                }
                const grid = document.createElement('div');
                grid.className = 'archetype-grid';
                archetypes.forEach(a => {
                    const share = totalDecks ? (a.deckCount / totalDecks * 100).toFixed(1) : '0.0';
                    const tile = document.createElement('div');
                    tile.className = 'archetype-tile';
                    tile.innerHTML =
                        `<div class="tile-imgs">${cardImg(a.leaderUuid, a.leaderName)}${cardImg(a.baseUuid, a.baseLabel)}</div>` +
                        `<div class="tile-meta">${share}% (${a.deckCount})</div>`;
                    tile.title = `${a.leaderName} / ${a.baseLabel}`;
                    tile.addEventListener('click', () => renderDetail(a));
                    grid.appendChild(tile);
                });
                container.appendChild(grid);
            }

            function renderDetail(a) {
                container.innerHTML = '';

                const head = document.createElement('div');
                head.className = 'archetype-detail-head';
                head.innerHTML =
                    `<span class="archetype-back">&larr; All archetypes</span>` +
                    `<div class="tile-imgs">${cardImg(a.leaderUuid, a.leaderName)}${cardImg(a.baseUuid, a.baseLabel)}</div>` +
                    `<strong>${escapeHTML(a.leaderName)} / ${escapeHTML(a.baseLabel)}</strong>` +
                    `<span>${a.deckCount} decks &middot; ${a.totalMatches} matches &middot; ${a.opponents.length} opponents</span>`;
                head.querySelector('.archetype-back').addEventListener('click', renderGallery);
                container.appendChild(head);

                if (a.totalMatches === 0) {
                    const p = document.createElement('p');
                    p.className = 'archetype-notice';
                    p.textContent = 'No recorded matches for this archetype.';
                    container.appendChild(p);
                    return;
                }

                const strong = a.opponents.filter(o => o.matches >= ARCHETYPE_MIN_MATCHES);
                if (strong.length === 0) {
                    const p = document.createElement('p');
                    p.className = 'archetype-notice';
                    p.textContent = `No opponent reaches ${ARCHETYPE_MIN_MATCHES} matches — not enough games for reliable rates.`;
                    container.appendChild(p);
                }

                const table = document.createElement('table');
                table.className = 'archetype-rows';
                table.innerHTML = '<thead><tr><th>Opponent</th><th>Matches</th><th>Win rate</th><th>Record</th></tr></thead>';
                const tbody = document.createElement('tbody');
                let dividerDone = false;

                a.opponents.forEach(o => {
                    const rate = archetypeWinRate(o);
                    if (rate === null && !dividerDone) {
                        dividerDone = true;
                        const d = document.createElement('tr');
                        d.className = 'archetype-divider';
                        d.innerHTML = `<td colspan="4">below ${ARCHETYPE_MIN_MATCHES} matches — win rates not shown</td>`;
                        tbody.appendChild(d);
                    }
                    const tr = document.createElement('tr');
                    if (rate === null) tr.classList.add('thin');
                    if (o.isMirror) tr.classList.add('mirror');
                    // rate === null renders an em dash: never print a % for a thin sample.
                    tr.innerHTML =
                        `<td><span class="opp-cell">` +
                            `<span class="opp-imgs">${cardImg(o.leaderUuid, o.leaderName)}${cardImg(o.baseUuid, o.baseLabel)}</span>` +
                            `<span>${escapeHTML(o.leaderName)} / ${escapeHTML(o.baseLabel)}</span>` +
                        `</span></td>` +
                        `<td>${o.matches}</td>` +
                        `<td>${rate === null ? '&mdash;' : rate.toFixed(1) + '%'}</td>` +
                        `<td>${o.matchWins}-${o.matchLosses}-${o.matchDraws}</td>`;
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);
                container.appendChild(table);
            }

            renderGallery();
        }

        // Calculate leader performance statistics
        function calculateLeaderPerformance(decks) {
            const leaderStats = {};
            
            // Group decks by leader
            decks.forEach(deck => {
                // Use leader name if available, otherwise fall back to UUID
                const leaderName = deck.leader && deck.leader.name ? deck.leader.name : 
                                  (deck.leader && deck.leader.uuid ? deck.leader.uuid : 'Unknown');
                
                if (!leaderStats[leaderName]) {
                    leaderStats[leaderName] = {
                        name: leaderName,
                        matchWins: 0,
                        matchLosses: 0,
                        matchDraws: 0,
                        gameWins: 0,
                        gameLosses: 0,
                        gameDraws: 0,
                        count: 0,
                        topCut: 0
                    };
                }
                
                // Increment deck count
                leaderStats[leaderName].count++;
                
                // Add match and game stats
                leaderStats[leaderName].matchWins += deck.standings.match_wins || 0;
                leaderStats[leaderName].matchLosses += deck.standings.match_losses || 0;
                leaderStats[leaderName].matchDraws += deck.standings.match_draws || 0;
                leaderStats[leaderName].gameWins += deck.standings.game_wins || 0;
                leaderStats[leaderName].gameLosses += deck.standings.game_losses || 0;
                leaderStats[leaderName].gameDraws += deck.standings.game_draws || 0;
                
                // Count top cuts (rank 8 or better)
                if (deck.rank && deck.rank <= 8) {
                    leaderStats[leaderName].topCut++;
                }
            });
            
            // Calculate win rates and other metrics
            const leaderPerformance = Object.values(leaderStats).map(stats => {
                const totalMatches = stats.matchWins + stats.matchLosses + stats.matchDraws;
                const totalGames = stats.gameWins + stats.gameLosses + stats.gameDraws;
                
                return {
                    ...stats,
                    matchWinRate: totalMatches > 0 ? ((stats.matchWins / totalMatches) * 100).toFixed(1) : '0.0',
                    gameWinRate: totalGames > 0 ? ((stats.gameWins / totalGames) * 100).toFixed(1) : '0.0',
                    topCutRate: ((stats.topCut / stats.count) * 100).toFixed(1)
                };
            });
            
            // Sort by match win rate descending
            leaderPerformance.sort((a, b) => {
                // First sort by match win rate
                const winRateDiff = parseFloat(b.matchWinRate) - parseFloat(a.matchWinRate);
                if (winRateDiff !== 0) return winRateDiff;
                
                // If same win rate, sort by count (more representation is better)
                return b.count - a.count;
            });
            
            return leaderPerformance;
        }
        
        
        // Render leader meta share chart
        function renderLeaderMetaChart(leaderMetaShare) {
            const chartContainer = document.getElementById('leader-meta-chart');
            chartContainer.innerHTML = '';
            
            // Store leader UUID mapping from global data
            const leaderUUIDs = {};
            
            // Create a mapping of leader names to their UUIDs (populated in renderLeaderAnalysis)
            window.decksData.forEach(deck => {
                if (deck.leader && deck.leader.uuid && deck.leader.name) {
                    leaderUUIDs[deck.leader.name] = deck.leader.uuid;
                }
            });
            
            // Find the maximum count for scaling
            const maxCount = Math.max(...leaderMetaShare.map(leader => leader.count));
            const maxHeight = 150; // Maximum bar height in pixels
            
            // Create a bar for each leader
            leaderMetaShare.forEach(leader => {
                const barHeight = Math.max((leader.count / maxCount) * maxHeight, 20); // Minimum height of 20px
                
                const barContainer = document.createElement('div');
                barContainer.classList.add('meta-bar');
                
                const bar = document.createElement('div');
                bar.classList.add('bar');
                bar.style.height = `${barHeight}px`;
                
                // Generate a distinct color based on leader name
                const hue = Math.abs(leader.name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % 360);
                bar.style.backgroundColor = `hsl(${hue}, 70%, 50%)`;
                
                const barValue = document.createElement('div');
                barValue.classList.add('bar-value');
                barValue.textContent = `${leader.percentage}% (${leader.count})`;
                bar.appendChild(barValue);
                
                // Create label with image if UUID available
                const barLabel = document.createElement('div');
                barLabel.classList.add('bar-label');
                
                const uuid = leaderUUIDs[leader.name];
                if (uuid) {
                    // Create image element
                    const img = document.createElement('img');
                    img.classList.add('leader-img');
                    img.style.marginBottom = '5px'; // Add spacing below image
                    
                    // Try to use JPG version first (faster loading)
                    img.src = `../SWUDeck/jpg/concat/${uuid}.jpg`;
                    img.alt = leader.name;
                    img.title = leader.name;
                    
                    // If JPG fails, fall back to WebP version
                    img.onerror = function() {
                        this.src = `../SWUDeck/concat/${uuid}.webp`;
                    };
                    
                    barLabel.appendChild(img);
                } else {
                    // Fall back to text if no UUID is available
                    barLabel.textContent = leader.name;
                }
                
                barContainer.appendChild(bar);
                barContainer.appendChild(barLabel);
                chartContainer.appendChild(barContainer);
            });
            
            // Show message if no data available
            if (leaderMetaShare.length === 0) {
                chartContainer.innerHTML = '<p>No leader data available.</p>';
            }
        }
        
        // Render leader/base combo meta share chart
        function renderLeaderComboChart(comboMetaShare) {
            const chartContainer = document.getElementById('combo-meta-chart');
            chartContainer.innerHTML = '';
            
            // Find the maximum count for scaling
            const maxCount = Math.max(...comboMetaShare.map(combo => combo.count));
            const maxHeight = 150; // Maximum bar height in pixels
            
            // Create a tooltip for hover effects
            const tooltip = document.createElement('div');
            tooltip.classList.add('leader-tooltip');
            document.body.appendChild(tooltip);
            
            // Create a bar for each combo
            comboMetaShare.forEach(combo => {
                const barHeight = Math.max((combo.count / maxCount) * maxHeight, 20); // Minimum height of 20px
                
                const barContainer = document.createElement('div');
                barContainer.classList.add('meta-bar');
                
                const bar = document.createElement('div');
                bar.classList.add('bar');
                bar.style.height = `${barHeight}px`;
                
                // Generate a distinct color based on combo name
                const hue = Math.abs(combo.name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % 360);
                bar.style.backgroundColor = `hsl(${hue}, 70%, 50%)`;
                
                const barValue = document.createElement('div');
                barValue.classList.add('bar-value');
                barValue.textContent = `${combo.percentage}% (${combo.count})`;
                bar.appendChild(barValue);
                
                // Create label with combo images if UUIDs available
                const barLabel = document.createElement('div');
                barLabel.classList.add('bar-label');
                barLabel.style.display = 'flex';
                barLabel.style.flexDirection = 'column';
                barLabel.style.alignItems = 'center';
                
                // Leader and base come straight off the combo object — no string parsing.
                const leaderName = combo.leaderName;
                const baseName = combo.baseLabel;

                // Create leader image
                const leaderUUID = combo.leaderUuid;
                if (leaderUUID) {
                    const leaderImg = document.createElement('img');
                    leaderImg.classList.add('leader-img');
                    leaderImg.style.marginBottom = '5px';
                    
                    // Try to use JPG version first (faster loading)
                    leaderImg.src = `../SWUDeck/jpg/concat/${leaderUUID}.jpg`;
                    leaderImg.alt = leaderName;
                    leaderImg.title = leaderName;
                    
                    // If JPG fails, fall back to WebP version
                    leaderImg.onerror = function() {
                        this.src = `../SWUDeck/concat/${leaderUUID}.webp`;
                    };
                    
                    // Add hover for tooltip
                    leaderImg.addEventListener('mouseenter', (e) => {
                        tooltip.innerHTML = `
                            <img src="../SWUDeck/jpg/concat/${leaderUUID}.jpg" onerror="this.src='../SWUDeck/concat/${leaderUUID}.webp';" alt="${leaderName}">
                            <h4>${leaderName}</h4>
                            <p>Leader</p>
                        `;
                        tooltip.style.display = 'block';
                        updateTooltipPosition(e);
                    });
                    
                    leaderImg.addEventListener('mousemove', updateTooltipPosition);
                    
                    leaderImg.addEventListener('mouseleave', () => {
                        tooltip.style.display = 'none';
                    });
                    
                    barLabel.appendChild(leaderImg);
                } else {
                    const leaderText = document.createElement('div');
                    leaderText.textContent = leaderName;
                    leaderText.style.marginBottom = '5px';
                    barLabel.appendChild(leaderText);
                }
                
                // Create base image
                const baseUUID = combo.baseUuid;
                if (baseUUID) {
                    const baseImg = document.createElement('img');
                    baseImg.classList.add('leader-img');
                    
                    // Try to use JPG version first
                    baseImg.src = `../SWUDeck/jpg/concat/${baseUUID}.jpg`;
                    baseImg.alt = baseName;
                    baseImg.title = baseName;
                    
                    // Fall back to WebP if JPG fails
                    baseImg.onerror = function() {
                        this.src = `../SWUDeck/concat/${baseUUID}.webp`;
                    };
                    
                    // Add hover for tooltip
                    baseImg.addEventListener('mouseenter', (e) => {
                        tooltip.innerHTML = `
                            <img src="../SWUDeck/jpg/concat/${baseUUID}.jpg" onerror="this.src='../SWUDeck/concat/${baseUUID}.webp';" alt="${baseName}">
                            <h4>${baseName}</h4>
                            <p>Base</p>
                        `;
                        tooltip.style.display = 'block';
                        updateTooltipPosition(e);
                    });
                    
                    baseImg.addEventListener('mousemove', updateTooltipPosition);
                    
                    baseImg.addEventListener('mouseleave', () => {
                        tooltip.style.display = 'none';
                    });
                    
                    barLabel.appendChild(baseImg);
                } else {
                    const baseText = document.createElement('div');
                    baseText.textContent = baseName;
                    barLabel.appendChild(baseText);
                }
                
                barContainer.appendChild(bar);
                barContainer.appendChild(barLabel);
                chartContainer.appendChild(barContainer);
            });
            
            // Show message if no data available
            if (comboMetaShare.length === 0) {
                chartContainer.innerHTML = '<p>No leader/base combo data available.</p>';
            }
            
            // Helper function to update tooltip position
            function updateTooltipPosition(e) {
                // Position tooltip relative to mouse cursor
                const x = e.pageX;
                const y = e.pageY;
                
                // Get viewport dimensions
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                
                // Get tooltip dimensions
                const tooltipWidth = tooltip.offsetWidth;
                const tooltipHeight = tooltip.offsetHeight;
                
                // Default position
                let posX = x + 15;
                let posY = y + 15;
                
                // Check if tooltip would go off-screen to the right
                if (posX + tooltipWidth > viewportWidth) {
                    posX = x - tooltipWidth - 15;
                }
                
                // Check if tooltip would go off-screen at the bottom
                if (posY + tooltipHeight > viewportHeight) {
                    posY = y - tooltipHeight - 15;
                }
                
                // Ensure tooltip doesn't go off-screen to the left or top
                posX = Math.max(10, posX);
                posY = Math.max(10, posY);
                
                // Apply the position
                tooltip.style.left = `${posX}px`;
                tooltip.style.top = `${posY}px`;
            }
        }
        
        // Render leader performance cards
        function renderLeaderPerformanceCards(leaderPerformance) {
            const container = document.getElementById('leader-performance');
            container.innerHTML = '';
            
            // Create a card for each leader, limit to top 8
            leaderPerformance.slice(0, 8).forEach(leader => {
                const card = document.createElement('div');
                card.classList.add('leader-card');
                
                // Calculate color based on win rate (green for high, red for low)
                const winRate = parseFloat(leader.matchWinRate);
                let color;
                if (winRate >= 60) {
                    color = 'var(--success)'; // Strong green
                } else if (winRate >= 50) {
                    color = 'var(--success)'; // Light green
                } else if (winRate >= 40) {
                    color = 'var(--accent-gold)'; // Orange
                } else {
                    color = 'var(--danger)'; // Red
                }
                
                card.style.borderLeft = `4px solid ${color}`;
                
                card.innerHTML = `
                    <div class="leader-card-name">${escapeHTML(leader.name)}</div>
                    <div class="leader-card-stats">
                        <div>Win rate: <strong>${leader.matchWinRate}%</strong></div>
                        <div>Count: <strong>${leader.count}</strong></div>
                        <div>Record: <strong>${leader.matchWins}-${leader.matchLosses}</strong></div>
                        <div>Top cut: <strong>${leader.topCut}/${leader.count}</strong></div>
                    </div>
                `;
                
                container.appendChild(card);
            });
            
            // Show message if no data available
            if (leaderPerformance.length === 0) {
                container.innerHTML = '<p>No leader performance data available.</p>';
            }
        }
        
        
        // Load tournament data on page load
        document.addEventListener('DOMContentLoaded', fetchTournamentData);
    </script>
</body>
</html>