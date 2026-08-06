<?php
include_once "../SharedUI/MenuBar.php";
require_once "../SharedUI/Render/Head.php"; echo RenderSiteStyles("SWUDeck");
include_once "../SharedUI/Header.php";
include_once "../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
include_once "../AppCore/SWU/CardImagePath.php";   // SWUCardArtScript -> window.swuCardArtUrl
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggregate Tournament Stats - SWU Stats</title>
    <!-- Styles for the shared renderers in MeleeCharts.js; also linked by MeleeTournamentResults.php. -->
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
        h1, h2, h3, h4 { color: white; }
        .tabs { display: flex; margin-bottom: 20px; flex-wrap: wrap; }
        .tab {
            padding: 10px 20px;
            background-color: var(--surface-raised);
            color: white;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
        }
        .tab.active { background-color: var(--border); font-weight: bold; }
        .tab-content {
            display: none;
            background-color: var(--overlay-scrim);
            padding: 20px;
            border-radius: 0 5px 5px 5px;
        }
        .tab-content.active { display: block; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid var(--border); color: white; }
        th { background-color: var(--surface-raised); font-weight: 600; }
        tr:hover { background-color: var(--surface-raised); }
        .chart-container {
            margin: 20px 0; padding: 10px;
            background-color: rgba(0, 0, 0, 0.3); border-radius: 5px;
        }
        .chart-title { margin-bottom: 15px; font-weight: bold; color: white; }
        .loading { text-align: center; padding: 50px; color: white; font-size: 18px; }
        .error {
            background-color: var(--danger); color: white; padding: 20px;
            border-radius: 5px; text-align: center; margin-top: 30px;
        }
        .flex-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; }
        .flex-column { flex: 1; min-width: 300px; }

        /* Aggregate header */
        .agg-header {
            background-color: var(--surface-raised);
            padding: 16px; border-radius: 5px; margin-bottom: 20px;
        }
        .agg-list { display: flex; flex-wrap: wrap; gap: 10px 24px; margin: 10px 0 0; }
        .agg-event { min-width: 220px; }
        .agg-event .agg-name { color: white; font-weight: 600; }
        .agg-event .agg-sub { font-size: 0.85em; color: var(--text-muted); }
        .agg-totals {
            margin-top: 14px; padding-top: 12px;
            border-top: 1px solid var(--border);
            color: white; font-weight: 600;
        }
        .agg-totals span { color: var(--accent); }
        .agg-back { display: inline-block; margin-bottom: 10px; color: var(--accent); text-decoration: none; }
        .agg-back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <a class="agg-back" href="MeleeTournaments.php">&larr; Back to tournaments</a>
    <h1>Aggregate Tournament Stats</h1>

    <div id="aggregate-header" class="agg-header">
        <div class="loading">Loading aggregate data&hellip;</div>
    </div>

    <div class="tabs">
        <div class="tab active" data-tab="tournaments">Tournaments</div>
        <div class="tab" data-tab="stats">Statistics</div>
        <div class="tab" data-tab="meta-share">Meta Share</div>
        <div class="tab" data-tab="matchup-matrix">Matchup Matrix</div>
    </div>

    <div id="tournaments" class="tab-content active">
        <table>
            <thead>
                <tr><th>Tournament</th><th>Date</th><th>Players</th><th></th></tr>
            </thead>
            <tbody id="tournaments-body"></tbody>
        </table>
    </div>

    <div id="stats" class="tab-content">
        <div class="chart-container">
            <div class="chart-title">Top Performing Leaders</div>
            <div id="leader-performance" class="flex-container"></div>
        </div>
    </div>

    <div id="meta-share" class="tab-content">
        <div class="flex-container">
            <div class="flex-column">
                <div class="chart-container">
                    <div class="chart-title">Leader Meta Share</div>
                    <div id="leader-meta-chart" class="meta-chart"></div>
                </div>
            </div>
        </div>
        <div class="chart-container">
            <div class="chart-title">Leader/Base Combo Meta Share</div>
            <div id="combo-meta-chart" class="meta-chart"></div>
        </div>
    </div>

    <div id="matchup-matrix" class="tab-content">
        <div class="chart-container">
            <div id="archetype-explorer"></div>
        </div>
    </div>

    <!-- Shared chart/explorer renderers, also used by MeleeTournamentResults.php. -->
    <!-- UUID->SET_NNN art resolver. Card art is SET_NNN-keyed under AppCore/SWU/Images/, but
         the tournament APIs return FFG UIDs, so the client needs the map. -->
<?php echo SWUCardArtScript(); ?>
    <script src="MeleeCharts.js?v=20260729"></script>
    <script>
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) content.classList.add('active');
                });
            });
        });

        function showEmpty(message) {
            document.getElementById('aggregate-header').innerHTML =
                `<div class="error">${escapeHTML(message)}</div>` +
                `<p><a class="agg-back" href="MeleeTournaments.php">&larr; Pick some tournaments</a></p>`;
            document.querySelectorAll('.tab-content').forEach(c => { c.innerHTML = ''; });
        }

        function renderHeader(data) {
            const events = data.tournaments.map(t => {
                const d = t.date ? new Date(t.date + 'T00:00:00') : null;
                const when = d && !isNaN(d) ? d.toLocaleDateString('en-US',
                    { year: 'numeric', month: 'short', day: 'numeric' }) : (t.date || '—');
                return `<div class="agg-event">
                          <div class="agg-name">${escapeHTML(t.name || '')}</div>
                          <div class="agg-sub">${when} &middot; ${t.players} players</div>
                        </div>`;
            }).join('');
            document.getElementById('aggregate-header').innerHTML =
                `<div class="agg-list">${events}</div>` +
                `<div class="agg-totals">` +
                  `<span>${data.totals.players}</span> total players across ` +
                  `<span>${data.totals.tournaments}</span> tournaments ` +
                  `&middot; <span>${data.totals.matchups}</span> matches` +
                `</div>`;
        }

        function renderTournamentsTab(data) {
            const body = document.getElementById('tournaments-body');
            body.innerHTML = data.tournaments.map(t => {
                const d = t.date ? new Date(t.date + 'T00:00:00') : null;
                const when = d && !isNaN(d) ? d.toLocaleDateString('en-US',
                    { year: 'numeric', month: 'long', day: 'numeric' }) : (t.date || '—');
                return `<tr>
                          <td>${escapeHTML(t.name || '')}</td>
                          <td>${when}</td>
                          <td>${t.players}</td>
                          <td><a href="MeleeTournamentResults.php?id=${t.id}">View event</a></td>
                        </tr>`;
            }).join('');
        }

        async function load() {
            const ids = new URLSearchParams(location.search).get('ids') || '';
            if (!ids.trim()) { showEmpty('No tournaments selected.'); return; }
            try {
                const res = await fetch(`../APIs/GetMeleeAggregate.php?ids=${encodeURIComponent(ids)}`);
                const text = await res.text();
                let data;
                try { data = JSON.parse(text); }
                catch (e) { showEmpty('The aggregate API returned an unreadable response.'); return; }
                if (!data.success) { showEmpty(data.message || 'Could not load aggregate data.'); return; }

                renderHeader(data);
                renderTournamentsTab(data);
                renderLeaderPerformanceCards(data.leaderPerformance);
                renderLeaderMetaChart(data.leaderMetaShare);
                renderLeaderComboChart(data.comboMetaShare);
                renderArchetypeExplorer(data.archetypes);
            } catch (e) {
                showEmpty('Failed to load aggregate data: ' + e.message);
            }
        }

        load();
    </script>
</body>
</html>
