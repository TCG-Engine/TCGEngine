<?php
include_once "../SharedUI/MenuBar.php";
require_once "../SharedUI/Render/Head.php"; echo RenderSiteStyles("SWUDeck");
include_once "../SharedUI/Header.php";
include_once "../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
include_once "../AppCore/SWU/CardImagePath.php";   // SWUCardArtScript -> window.swuCardArtUrl

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
    <!-- Styles for the shared renderers in MeleeCharts.js; also linked by MeleeTournamentAggregate.php. -->
    <link rel="stylesheet" href="MeleeCharts.css?v=20260729">
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
    
    <!-- Shared chart/explorer renderers, also used by MeleeTournamentAggregate.php. -->
    <!-- UUID->SET_NNN art resolver. Card art is SET_NNN-keyed under AppCore/SWU/Images/, but
         the tournament APIs return FFG UIDs, so the client needs the map. -->
<?php echo SWUCardArtScript(); ?>
    <script src="MeleeCharts.js?v=20260729"></script>
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
                        ${deck.leader && deck.leader.uuid ? `<img src="../SWUDeck/jpg/concat/${deck.leader.uuid}.jpg" alt="${escapeHTML(deck.leader.name || '')}" title="${escapeHTML(deck.leader.name || '')}" style="width:28px; height:28px; object-fit:cover; border-radius:4px; margin-right:2px; vertical-align:middle;" onerror="this.onerror=null;this.src='${swuCardArtUrl(deck.leader.uuid, 'tile')}';" />` : ''}
                        ${deck.base && deck.base.uuid ? `<img src="../SWUDeck/jpg/concat/${deck.base.uuid}.jpg" alt="${escapeHTML(deck.base.name || '')}" title="${escapeHTML(deck.base.name || '')}" style="width:28px; height:28px; object-fit:cover; border-radius:4px; margin-right:4px; vertical-align:middle;" onerror="this.onerror=null;this.src='${swuCardArtUrl(deck.base.uuid, 'tile')}';" />` : ''}
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
            const leaderUuids = {};
            const totalDecks = decks.length;

            // Count leaders
            decks.forEach(deck => {
                // Use the leader name if available, otherwise use the UUID
                const leaderName = deck.leader && deck.leader.name ? deck.leader.name : (deck.leader && deck.leader.uuid ? deck.leader.uuid : 'Unknown');
                leaderCounts[leaderName] = (leaderCounts[leaderName] || 0) + 1;
                // Carried through so renderLeaderMetaChart can show card art without reaching
                // for window.decksData — a shared renderer must not depend on page globals.
                if (deck.leader && deck.leader.uuid && !leaderUuids[leaderName]) {
                    leaderUuids[leaderName] = deck.leader.uuid;
                }
            });

            // Calculate percentages and sort by popularity
            const leaderMetaShare = Object.keys(leaderCounts).map(leader => ({
                name: leader,
                uuid: leaderUuids[leader] || null,
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

        // Two-state view inside the Matchup Matrix tab: a gallery of every archetype, and a
        // per-archetype matchup list. Detail replaces the gallery in place; no URL state.

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
        
        // Render leader/base combo meta share chart
        
        // Render leader performance cards
        
        
        // Load tournament data on page load
        document.addEventListener('DOMContentLoaded', fetchTournamentData);
    </script>
</body>
</html>