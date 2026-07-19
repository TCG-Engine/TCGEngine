<?php
include_once __DIR__ . '/AccountFiles/AccountSessionAPI.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    http_response_code(403);
    echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
    exit;
}
CheckSession();
if (empty($_SESSION['generator_admin_csrf'])) {
    $_SESSION['generator_admin_csrf'] = bin2hex(random_bytes(32));
}
$generatorAdminCsrf = (string)$_SESSION['generator_admin_csrf'];

function GeneratorAdminAppLabel($rootName)
{
    $siteDefPath = __DIR__ . '/SharedUI/Sites/' . $rootName . '/SiteDef.php';
    if (is_file($siteDefPath)) {
        $siteDef = require $siteDefPath;
        $appName = isset($siteDef['identity']['appName']) ? trim((string)$siteDef['identity']['appName']) : '';
        if ($appName !== '') return $appName;
    }
    $fallbackLabels = [
        'RBDeck' => 'Riftbound Deck',
        'RBSim' => 'Riftbound Simulator',
        'SoulMastersSim' => 'Soul Masters Simulator',
        'SWUCardList' => 'SWU Card List',
    ];
    if (isset($fallbackLabels[$rootName])) return $fallbackLabels[$rootName];
    return preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $rootName);
}

function GeneratorAdminAction($id, $label, $description, $endpoint, $source, $kind = 'build')
{
    return [
        'id' => $id,
        'label' => $label,
        'description' => $description,
        'endpoint' => $endpoint,
        'source' => $source,
        'kind' => $kind,
    ];
}

$schemaRoot = __DIR__ . '/Schemas';
$appRoots = [];
foreach (glob($schemaRoot . '/*', GLOB_ONLYDIR) ?: [] as $schemaDirectory) {
    $rootName = basename($schemaDirectory);
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $rootName)) continue;
    $appRoots[] = $rootName;
}
sort($appRoots, SORT_NATURAL | SORT_FLAG_CASE);

$keywordActions = [
    'AzukiSim' => GeneratorAdminAction(
        'keywords',
        'Keyword code',
        'Refresh the generated keyword compatibility file.',
        'Data/ProcessKeywordsGA.php?rootName={app}',
        'Data/ProcessKeywordsGA.php'
    ),
    'GrandArchiveSim' => GeneratorAdminAction(
        'keywords',
        'Keyword code',
        'Parse card text and rebuild generated keyword helpers.',
        'Data/ProcessKeywordsGA.php?rootName={app}',
        'Data/ProcessKeywordsGA.php'
    ),
    'SWUSim' => GeneratorAdminAction(
        'keywords',
        'Keyword code',
        'Parse the cached card data and rebuild innate keyword helpers.',
        'Data/ProcessKeywordsSWU.php?rootName={app}',
        'Data/ProcessKeywordsSWU.php'
    ),
];

$apps = [];
foreach ($appRoots as $rootName) {
    $schemaDirectory = $schemaRoot . '/' . $rootName;
    $actions = [];

    if (is_file($schemaDirectory . '/ImportSchema.txt')) {
        $actions[] = GeneratorAdminAction(
            'cards',
            'Card data & images',
            'Rebuild card dictionaries from cache or fetch current source data.',
            'zzCardCodeGenerator.php?rootName={app}',
            'zzCardCodeGenerator.php'
        );
    }
    if (is_file($schemaDirectory . '/GameSchema.txt')) {
        $actions[] = GeneratorAdminAction(
            'game',
            'Game runtime',
            'Generate zone accessors, layouts, runtime files, UI data, and macro code.',
            'zzGameCodeGenerator.php?rootName={app}',
            'zzGameCodeGenerator.php'
        );
    }
    if (is_file($schemaDirectory . '/TurnSchema.txt')) {
        $actions[] = GeneratorAdminAction(
            'turn',
            'Turn controller',
            'Generate turn states and the schema-driven turn controller.',
            'zzTurnGenerator.php?rootName={app}',
            'zzTurnGenerator.php'
        );
    }
    if (isset($keywordActions[$rootName]) && is_file(__DIR__ . '/' . $keywordActions[$rootName]['source'])) {
        $actions[] = $keywordActions[$rootName];
    }
    if (is_file(__DIR__ . '/SharedUI/Sites/' . $rootName . '/SiteDef.php')) {
        $actions[] = GeneratorAdminAction(
            'site',
            'Shared UI entries',
            'Regenerate the standard entry files defined by this app\'s SiteDef.',
            'SharedUI/Render/GenerateSites.php?rootName={app}',
            'SharedUI/Render/GenerateSites.php'
        );
    }

    $apps[] = [
        'rootName' => $rootName,
        'label' => GeneratorAdminAppLabel($rootName),
        'actions' => $actions,
    ];
}

$requestedApp = isset($_GET['app']) ? (string)$_GET['app'] : '';
$hasRequestedApp = false;
$initialApp = $apps ? $apps[0]['rootName'] : '';
foreach ($apps as $app) {
    if ($app['rootName'] === $requestedApp) {
        $initialApp = $requestedApp;
        $hasRequestedApp = true;
        break;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generator Workspace</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #090d14;
            --panel: #101722;
            --panel-raised: #151f2d;
            --panel-soft: #0c121b;
            --line: #263244;
            --line-strong: #35465e;
            --text: #e7edf5;
            --muted: #8d9aae;
            --blue: #66b3ff;
            --blue-deep: #1473e6;
            --green: #54d68a;
            --red: #ff7474;
            --amber: #f4bd62;
            --radius: 14px;
            --shadow: 0 18px 60px rgba(0, 0, 0, .28);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--text); }
        button, input { font: inherit; }
        button { color: inherit; }

        .shell { min-height: 100vh; display: grid; grid-template-columns: 280px minmax(0, 1fr); }
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 26px 18px;
            background: linear-gradient(180deg, #111a27 0%, #0c121b 100%);
            border-right: 1px solid var(--line);
            overflow-y: auto;
        }
        .brand { padding: 0 10px 24px; }
        .eyebrow { margin: 0 0 6px; color: var(--blue); font-size: 11px; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; }
        .brand h1 { margin: 0; font-size: 22px; letter-spacing: -.02em; }
        .brand p { margin: 8px 0 0; color: var(--muted); font-size: 13px; line-height: 1.45; }
        .app-nav { display: grid; gap: 6px; }
        .app-button {
            width: 100%;
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 10px;
            background: transparent;
            text-align: left;
            cursor: pointer;
            transition: background .15s, border-color .15s, transform .15s;
        }
        .app-button:hover { background: rgba(255, 255, 255, .045); transform: translateX(2px); }
        .app-button.active { background: #182537; border-color: #31445d; }
        .app-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: linear-gradient(145deg, #253a55, #182638);
            color: #b9dafb;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: -.02em;
        }
        .app-label { min-width: 0; }
        .app-label strong, .app-label small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .app-label strong { font-size: 13px; }
        .app-label small { margin-top: 2px; color: var(--muted); font-size: 11px; }
        .action-count { color: var(--muted); font-size: 11px; font-variant-numeric: tabular-nums; }

        .main { min-width: 0; padding: 44px clamp(24px, 5vw, 74px) 80px; }
        .main-inner { width: min(1100px, 100%); margin: 0 auto; }
        .hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 28px; margin-bottom: 28px; }
        .hero-copy { min-width: 0; }
        .hero h2 { margin: 2px 0 7px; font-size: clamp(28px, 4vw, 40px); letter-spacing: -.045em; }
        .root-name { color: var(--muted); font-family: "Cascadia Code", Consolas, monospace; font-size: 13px; }
        .hero-actions { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }

        .button {
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid var(--line-strong);
            border-radius: 9px;
            background: var(--panel-raised);
            font-weight: 750;
            font-size: 13px;
            cursor: pointer;
            transition: transform .15s, border-color .15s, background .15s, opacity .15s;
        }
        .button:hover:not(:disabled) { transform: translateY(-1px); border-color: #57708e; }
        .button:disabled { cursor: not-allowed; opacity: .45; }
        .button-primary { border-color: #2582ec; background: linear-gradient(180deg, #2289f5, var(--blue-deep)); color: white; box-shadow: 0 8px 24px rgba(20, 115, 230, .24); }
        .button-danger { border-color: #704247; color: #ffb2b2; }
        .button-small { min-height: 34px; padding: 0 12px; }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 28px;
        }
        .summary-card { padding: 16px 18px; border: 1px solid var(--line); border-radius: 11px; background: var(--panel); }
        .summary-card span { display: block; color: var(--muted); font-size: 11px; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .summary-card strong { display: block; margin-top: 7px; font-size: 18px; }

        .options {
            display: flex;
            align-items: center;
            gap: 12px 26px;
            flex-wrap: wrap;
            padding: 16px 18px;
            margin-bottom: 20px;
            border: 1px solid var(--line);
            border-radius: 11px;
            background: var(--panel-soft);
        }
        .options-title { margin-right: auto; }
        .options-title strong, .options-title small { display: block; }
        .options-title strong { font-size: 13px; }
        .options-title small { margin-top: 3px; color: var(--muted); font-size: 11px; }
        .switch { display: inline-flex; align-items: center; gap: 9px; color: #c7d0dc; font-size: 12px; cursor: pointer; }
        .switch input { width: 16px; height: 16px; accent-color: var(--blue-deep); }
        .transfer-controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .transfer-status { width: 100%; margin: -5px 0 20px; color: var(--muted); font-size: 12px; }
        .transfer-status[data-kind="success"] { color: var(--green); }
        .transfer-status[data-kind="error"] { color: var(--red); }

        .section-heading { display: flex; align-items: baseline; justify-content: space-between; gap: 18px; margin: 28px 0 12px; }
        .section-heading h3 { margin: 0; font-size: 15px; }
        .section-heading p { margin: 0; color: var(--muted); font-size: 12px; }
        .action-list { display: grid; gap: 10px; }
        .action-card {
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--panel);
            box-shadow: 0 1px 0 rgba(255, 255, 255, .02);
        }
        .action-card[data-status="running"] { border-color: #316da6; box-shadow: 0 0 0 1px rgba(102, 179, 255, .12); }
        .action-card[data-status="success"] { border-color: #2e6448; }
        .action-card[data-status="error"] { border-color: #77404a; }
        .action-main { min-height: 88px; display: grid; grid-template-columns: 38px minmax(0, 1fr) auto; align-items: center; gap: 14px; padding: 15px 16px; }
        .status-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border: 1px solid #324055;
            border-radius: 50%;
            background: #131d2a;
            color: #8fa0b5;
            font-size: 14px;
            font-weight: 900;
        }
        [data-status="running"] .status-icon { color: var(--blue); border-color: #356d9f; animation: pulse 1.25s ease-in-out infinite; }
        [data-status="success"] .status-icon { color: var(--green); border-color: #34704f; }
        [data-status="error"] .status-icon { color: var(--red); border-color: #7b444d; }
        [data-status="cancelled"] .status-icon { color: var(--amber); }
        @keyframes pulse { 50% { box-shadow: 0 0 0 6px rgba(102, 179, 255, .08); } }
        .action-copy { min-width: 0; }
        .action-title-row { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
        .action-title { font-weight: 800; font-size: 14px; }
        .status-label { color: var(--muted); font-size: 11px; }
        .action-description { margin: 5px 0; color: #a7b1bf; font-size: 12px; line-height: 1.45; }
        .source { color: #65758a; font-family: "Cascadia Code", Consolas, monospace; font-size: 10px; }
        .action-controls { display: flex; gap: 8px; }
        .output { display: none; border-top: 1px solid var(--line); background: #070a0f; }
        .output.visible { display: block; }
        .output-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 8px 13px; border-bottom: 1px solid #18202c; color: #78889d; font-size: 10px; }
        .output pre { max-height: 340px; overflow: auto; margin: 0; padding: 14px; color: #b7c4d5; font: 11px/1.55 "Cascadia Code", Consolas, monospace; white-space: pre-wrap; overflow-wrap: anywhere; }
        .empty { padding: 40px; border: 1px dashed var(--line); border-radius: var(--radius); color: var(--muted); text-align: center; }

        .run-banner {
            display: none;
            align-items: center;
            gap: 12px;
            position: sticky;
            bottom: 18px;
            z-index: 5;
            margin-top: 22px;
            padding: 12px 14px;
            border: 1px solid #345272;
            border-radius: 11px;
            background: rgba(17, 29, 43, .96);
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
        }
        .run-banner.visible { display: flex; }
        .run-banner-copy { min-width: 0; flex: 1; }
        .run-banner strong, .run-banner span { display: block; }
        .run-banner strong { font-size: 12px; }
        .run-banner span { margin-top: 3px; color: var(--muted); font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        @media (max-width: 820px) {
            .shell { display: block; }
            .sidebar { position: static; width: 100%; height: auto; padding: 16px; border-right: 0; border-bottom: 1px solid var(--line); }
            .brand { padding: 2px 2px 14px; }
            .brand p { display: none; }
            .app-nav { display: flex; overflow-x: auto; padding-bottom: 4px; }
            .app-nav { scrollbar-width: none; }
            .app-nav::-webkit-scrollbar { display: none; }
            .app-button { min-width: 190px; }
            .main { padding: 28px 16px 60px; }
            .hero { display: block; }
            .hero-actions { margin-top: 18px; }
            .summary { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .hero-actions { display: grid; grid-template-columns: 1fr 1fr; }
            .action-main { grid-template-columns: 34px minmax(0, 1fr); }
            .action-controls { grid-column: 1 / -1; padding-left: 48px; }
            .action-controls .button { flex: 1; }
            .options { align-items: flex-start; }
            .options-title { width: 100%; }
            .section-heading { align-items: flex-start; }
            .section-heading h3 { flex: 0 0 auto; white-space: nowrap; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <p class="eyebrow">Admin tools</p>
            <h1>Generator Workspace</h1>
            <p>Select an app, run its complete build pipeline, and inspect every generator from one screen.</p>
        </div>
        <nav class="app-nav" id="app-nav" aria-label="Applications"></nav>
    </aside>

    <main class="main">
        <div class="main-inner">
            <header class="hero">
                <div class="hero-copy">
                    <p class="eyebrow">Selected application</p>
                    <h2 id="app-title"></h2>
                    <div class="root-name" id="root-name"></div>
                </div>
                <div class="hero-actions">
                    <button type="button" class="button button-danger" id="cancel-button" hidden>Cancel</button>
                    <button type="button" class="button button-primary" id="run-all-button">Run build pipeline</button>
                </div>
            </header>

            <section class="summary" aria-label="Application summary">
                <div class="summary-card"><span>Build steps</span><strong id="step-count">0</strong></div>
                <div class="summary-card"><span>Schema inputs</span><strong id="schema-count">0</strong></div>
                <div class="summary-card"><span>Last run</span><strong id="last-run">Not run</strong></div>
            </section>

            <section class="options" id="card-options">
                <div class="options-title">
                    <strong>Card generator options</strong>
                    <small>Applied whenever the card-data step runs.</small>
                </div>
                <label class="switch"><input type="checkbox" id="with-preview"> Fetch current source data</label>
                <label class="switch"><input type="checkbox" id="overwrite-images"> Replace existing images</label>
            </section>

            <section class="options" id="ability-transfer-options">
                <div class="options-title">
                    <strong>Card ability SQL</strong>
                    <small>Export or replace only the selected app's card_abilities rows.</small>
                </div>
                <div class="transfer-controls">
                    <button type="button" class="button button-small" id="export-abilities-button">Export SQL</button>
                    <button type="button" class="button button-small" id="import-abilities-button">Import SQL</button>
                    <input type="file" id="import-abilities-file" accept=".sql,application/sql,text/plain" hidden>
                </div>
            </section>
            <p class="transfer-status" id="ability-transfer-status" role="status" aria-live="polite"></p>

            <div class="section-heading">
                <h3>Build steps</h3>
                <p>Run individually or execute the pipeline in order.</p>
            </div>
            <section class="action-list" id="action-list" aria-live="polite"></section>

            <div class="run-banner" id="run-banner" role="status" aria-live="polite">
                <div class="status-icon">↻</div>
                <div class="run-banner-copy">
                    <strong id="run-banner-title">Pipeline running</strong>
                    <span id="run-banner-detail"></span>
                </div>
                <button type="button" class="button button-small button-danger" id="banner-cancel-button">Cancel</button>
            </div>
        </div>
    </main>
</div>

<script>
const apps = <?= json_encode($apps, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const initialApp = <?= json_encode($initialApp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const hasRequestedApp = <?= $hasRequestedApp ? 'true' : 'false' ?>;
const generatorAdminCsrf = <?= json_encode($generatorAdminCsrf, JSON_UNESCAPED_SLASHES) ?>;
const nav = document.getElementById('app-nav');
const actionList = document.getElementById('action-list');
const runAllButton = document.getElementById('run-all-button');
const cancelButton = document.getElementById('cancel-button');
const bannerCancelButton = document.getElementById('banner-cancel-button');
const runBanner = document.getElementById('run-banner');
const withPreview = document.getElementById('with-preview');
const overwriteImages = document.getElementById('overwrite-images');
const exportAbilitiesButton = document.getElementById('export-abilities-button');
const importAbilitiesButton = document.getElementById('import-abilities-button');
const importAbilitiesFile = document.getElementById('import-abilities-file');
const abilityTransferStatus = document.getElementById('ability-transfer-status');
const outputs = new Map();
const runStates = new Map();
let selectedApp = apps.find(app => app.rootName === initialApp) || apps[0] || null;
let activeController = null;
let pipelineRunning = false;
let transferRunning = false;
let importApp = null;

function appInitials(name) {
    const words = name.replace(/([a-z])([A-Z])/g, '$1 $2').split(/\s+/).filter(Boolean);
    return words.slice(0, 2).map(word => word[0]).join('').toUpperCase();
}

function formatDuration(milliseconds) {
    if (milliseconds < 1000) return `${milliseconds} ms`;
    const seconds = milliseconds / 1000;
    if (seconds < 60) return `${seconds.toFixed(seconds < 10 ? 1 : 0)} sec`;
    const minutes = Math.floor(seconds / 60);
    return `${minutes}m ${Math.round(seconds % 60)}s`;
}

function stateKey(app, action) { return `${app.rootName}:${action.id}`; }

function renderNav() {
    nav.replaceChildren();
    for (const app of apps) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'app-button' + (selectedApp && app.rootName === selectedApp.rootName ? ' active' : '');
        button.setAttribute('aria-current', selectedApp && app.rootName === selectedApp.rootName ? 'page' : 'false');

        const icon = document.createElement('span');
        icon.className = 'app-icon';
        icon.textContent = appInitials(app.label);

        const label = document.createElement('span');
        label.className = 'app-label';
        const strong = document.createElement('strong');
        strong.textContent = app.label;
        const small = document.createElement('small');
        small.textContent = app.rootName;
        label.append(strong, small);

        const count = document.createElement('span');
        count.className = 'action-count';
        count.textContent = app.actions.length;
        button.append(icon, label, count);
        button.addEventListener('click', () => selectApp(app.rootName));
        nav.append(button);
    }
}

function selectApp(rootName) {
    if (pipelineRunning) return;
    const next = apps.find(app => app.rootName === rootName);
    if (!next) return;
    selectedApp = next;
    const url = new URL(window.location.href);
    url.searchParams.set('app', rootName);
    window.history.replaceState(null, '', url);
    try { localStorage.setItem('tcgengine:generator-admin:app', rootName); } catch (_) {}
    render();
}

function makeStatusIcon(status) {
    if (status === 'running') return '↻';
    if (status === 'success') return '✓';
    if (status === 'error') return '!';
    if (status === 'cancelled') return '–';
    return '•';
}

function makeStatusLabel(state) {
    if (!state || state.status === 'idle') return 'Ready';
    if (state.status === 'running') return 'Running…';
    if (state.status === 'success') return `Completed in ${formatDuration(state.duration)}`;
    if (state.status === 'cancelled') return 'Cancelled';
    return state.message || 'Failed';
}

function renderActions() {
    actionList.replaceChildren();
    if (!selectedApp || selectedApp.actions.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'empty';
        empty.textContent = 'No generator actions were discovered for this app.';
        actionList.append(empty);
        return;
    }

    for (const action of selectedApp.actions) {
        const key = stateKey(selectedApp, action);
        const state = runStates.get(key) || { status: 'idle' };
        const card = document.createElement('article');
        card.className = 'action-card';
        card.dataset.status = state.status;
        card.dataset.action = action.id;

        const main = document.createElement('div');
        main.className = 'action-main';
        const icon = document.createElement('div');
        icon.className = 'status-icon';
        icon.textContent = makeStatusIcon(state.status);

        const copy = document.createElement('div');
        copy.className = 'action-copy';
        const titleRow = document.createElement('div');
        titleRow.className = 'action-title-row';
        const title = document.createElement('span');
        title.className = 'action-title';
        title.textContent = action.label;
        const status = document.createElement('span');
        status.className = 'status-label';
        status.textContent = makeStatusLabel(state);
        titleRow.append(title, status);
        const description = document.createElement('p');
        description.className = 'action-description';
        description.textContent = action.description;
        const source = document.createElement('div');
        source.className = 'source';
        source.textContent = action.source;
        copy.append(titleRow, description, source);

        const controls = document.createElement('div');
        controls.className = 'action-controls';
        const outputButton = document.createElement('button');
        outputButton.type = 'button';
        outputButton.className = 'button button-small';
        outputButton.textContent = 'Output';
        outputButton.disabled = !outputs.has(key);
        const runButton = document.createElement('button');
        runButton.type = 'button';
        runButton.className = 'button button-small';
        runButton.textContent = state.status === 'running' ? 'Running…' : 'Run';
        runButton.disabled = pipelineRunning;
        controls.append(outputButton, runButton);
        main.append(icon, copy, controls);

        const output = document.createElement('div');
        output.className = 'output';
        const toolbar = document.createElement('div');
        toolbar.className = 'output-toolbar';
        const outputLabel = document.createElement('span');
        outputLabel.textContent = 'Generator output';
        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'button button-small';
        clearButton.textContent = 'Clear';
        toolbar.append(outputLabel, clearButton);
        const pre = document.createElement('pre');
        pre.textContent = outputs.get(key) || '';
        output.append(toolbar, pre);

        outputButton.addEventListener('click', () => output.classList.toggle('visible'));
        clearButton.addEventListener('click', () => {
            outputs.delete(key);
            output.classList.remove('visible');
            outputButton.disabled = true;
            pre.textContent = '';
        });
        runButton.addEventListener('click', () => runSingleAction(action));
        card.append(main, output);
        actionList.append(card);
    }
}

function render() {
    renderNav();
    if (!selectedApp) return;
    document.getElementById('app-title').textContent = selectedApp.label;
    document.getElementById('root-name').textContent = selectedApp.rootName;
    document.getElementById('step-count').textContent = selectedApp.actions.length;
    document.getElementById('schema-count').textContent = selectedApp.actions.filter(action => ['cards', 'game', 'turn'].includes(action.id)).length;
    document.getElementById('card-options').hidden = !selectedApp.actions.some(action => action.id === 'cards');
    const lastRun = selectedApp.actions
        .map(action => runStates.get(stateKey(selectedApp, action)))
        .filter(Boolean)
        .sort((a, b) => (b.completedAt || 0) - (a.completedAt || 0))[0];
    document.getElementById('last-run').textContent = lastRun && lastRun.completedAt
        ? new Date(lastRun.completedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        : 'Not run';
    runAllButton.disabled = pipelineRunning || selectedApp.actions.length === 0;
    exportAbilitiesButton.disabled = pipelineRunning || transferRunning;
    importAbilitiesButton.disabled = pipelineRunning || transferRunning;
    cancelButton.hidden = !pipelineRunning;
    renderActions();
}

function setTransferStatus(message, kind = '') {
    abilityTransferStatus.textContent = message;
    abilityTransferStatus.dataset.kind = kind;
}

// Self-contained styled confirmation (this standalone mod page does not load the shared
// StyledConfirm bundle). Returns a Promise<boolean>.
function styledConfirm(message, { confirmLabel = 'Confirm', danger = false } = {}) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9000;display:flex;align-items:center;justify-content:center;padding:20px;';
        const box = document.createElement('div');
        box.style.cssText = 'background:#12202f;border:1px solid ' + (danger ? 'rgba(255,100,100,.4)' : 'rgba(255,255,255,.15)') + ';border-radius:10px;padding:20px;max-width:420px;color:#eef4ff;box-shadow:0 14px 40px rgba(0,0,0,.5);font-size:15px;';
        const msg = document.createElement('div');
        msg.textContent = message;
        msg.style.cssText = 'margin-bottom:18px;line-height:1.4;';
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;gap:10px;justify-content:flex-end;';
        const cancel = document.createElement('button');
        cancel.type = 'button'; cancel.textContent = 'Cancel';
        cancel.style.cssText = 'padding:8px 16px;border-radius:6px;border:1px solid #44576d;background:#25384c;color:#fff;cursor:pointer;';
        const ok = document.createElement('button');
        ok.type = 'button'; ok.textContent = confirmLabel;
        ok.style.cssText = 'padding:8px 16px;border-radius:6px;border:1px solid ' + (danger ? '#c0392b' : '#4ca7ff') + ';background:' + (danger ? '#c0392b' : '#1769aa') + ';color:#fff;cursor:pointer;';
        function done(result) {
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            document.removeEventListener('keydown', onKey);
            resolve(result);
        }
        function onKey(e) { if (e.key === 'Escape') done(false); }
        cancel.onclick = () => done(false);
        ok.onclick = () => done(true);
        overlay.onclick = (e) => { if (e.target === overlay) done(false); };
        document.addEventListener('keydown', onKey);
        row.appendChild(cancel); row.appendChild(ok);
        box.appendChild(msg); box.appendChild(row); overlay.appendChild(box);
        document.body.appendChild(overlay);
        ok.focus();
    });
}

function exportAbilities() {
    if (!selectedApp || pipelineRunning || transferRunning) return;
    const url = new URL('CardEditor/API/AdminCardAbilityTransfer.php', window.location.href);
    url.searchParams.set('action', 'export');
    url.searchParams.set('app', selectedApp.rootName);
    window.location.assign(url);
    setTransferStatus(`Preparing ${selectedApp.rootName} card abilities for download…`);
}

function chooseAbilityImport() {
    if (!selectedApp || pipelineRunning || transferRunning) return;
    importApp = selectedApp;
    importAbilitiesFile.value = '';
    importAbilitiesFile.click();
}

async function importAbilities() {
    const file = importAbilitiesFile.files && importAbilitiesFile.files[0];
    const app = importApp;
    if (!file || !app) return;
    const confirmed = await styledConfirm(`Replace all card abilities for ${app.rootName} with the contents of ${file.name}? Other apps will not be changed.`, { confirmLabel: 'Replace', danger: true });
    if (!confirmed) return;

    transferRunning = true;
    render();
    setTransferStatus(`Importing ${app.rootName} card abilities…`);
    try {
        const form = new FormData();
        form.set('action', 'import');
        form.set('app', app.rootName);
        form.set('csrf', generatorAdminCsrf);
        form.set('sqlFile', file);
        const response = await fetch('CardEditor/API/AdminCardAbilityTransfer.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: form,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(payload.error || `Import failed with HTTP ${response.status}`);

        setTransferStatus(`Imported ${payload.importedCount} ability rows for ${app.rootName}. Regenerating runtime code…`);
        const gameAction = app.actions.find(action => action.id === 'game');
        if (gameAction) {
            pipelineRunning = true;
            showRunBanner(gameAction.label, 'Regenerating after card ability import');
            const generated = await executeAction(gameAction);
            pipelineRunning = false;
            hideRunBanner();
            if (!generated) throw new Error('Abilities were imported, but runtime regeneration failed; inspect the Game runtime output.');
        }
        setTransferStatus(`Imported ${payload.importedCount} ability rows for ${app.rootName}; no other apps were changed.`, 'success');
    } catch (error) {
        setTransferStatus(error.message || 'Card ability import failed.', 'error');
    } finally {
        transferRunning = false;
        importApp = null;
        importAbilitiesFile.value = '';
        render();
    }
}

function actionUrl(action) {
    let endpoint = action.endpoint.replace('{app}', encodeURIComponent(selectedApp.rootName));
    const url = new URL(endpoint, window.location.href);
    if (action.id === 'cards') {
        if (withPreview.checked) url.searchParams.set('withPreview', '1');
        if (overwriteImages.checked) url.searchParams.set('overwriteImages', '1');
    }
    url.searchParams.set('_generatorAdminRun', Date.now().toString());
    return url;
}

function outputToText(raw) {
    const documentFragment = new DOMParser().parseFromString(raw.replace(/<br\s*\/?>/gi, '\n'), 'text/html');
    return (documentFragment.body.textContent || '').replace(/\n{3,}/g, '\n\n').trim();
}

function responseLooksFailed(response, text) {
    if (!response.ok) return `HTTP ${response.status}`;
    if (/\b(fatal error|uncaught (?:error|exception)|parse error)\b/i.test(text)) return 'PHP execution failed';
    if (/(^|\n)\s*ERROR(?:\s*:|\b)/i.test(text)) return 'Generator reported an error';
    const trimmed = text.trim();
    if (trimmed.startsWith('{')) {
        try {
            const payload = JSON.parse(trimmed);
            if (payload && payload.error) return String(payload.error);
        } catch (_) {}
    }
    return '';
}

async function executeAction(action) {
    const appAtStart = selectedApp;
    const key = stateKey(appAtStart, action);
    const startedAt = performance.now();
    runStates.set(key, { status: 'running' });
    render();

    activeController = new AbortController();
    try {
        const response = await fetch(actionUrl(action), {
            credentials: 'same-origin',
            cache: 'no-store',
            signal: activeController.signal,
            headers: { 'X-Generator-Admin': '1' },
        });
        const raw = await response.text();
        const text = outputToText(raw) || '(No output returned.)';
        outputs.set(key, text);
        const failure = responseLooksFailed(response, text);
        if (failure) throw new Error(failure);
        runStates.set(key, {
            status: 'success',
            duration: Math.round(performance.now() - startedAt),
            completedAt: Date.now(),
        });
        return true;
    } catch (error) {
        const cancelled = error && error.name === 'AbortError';
        if (!outputs.has(key)) outputs.set(key, cancelled ? 'Request cancelled in this browser. The server process may take a moment to stop.' : String(error));
        runStates.set(key, {
            status: cancelled ? 'cancelled' : 'error',
            message: cancelled ? 'Cancelled' : (error.message || 'Failed'),
            duration: Math.round(performance.now() - startedAt),
            completedAt: Date.now(),
        });
        return false;
    } finally {
        activeController = null;
        if (selectedApp === appAtStart) render();
    }
}

async function runSingleAction(action) {
    if (pipelineRunning) return;
    pipelineRunning = true;
    showRunBanner(action.label, 'Running one build step');
    render();
    await executeAction(action);
    pipelineRunning = false;
    hideRunBanner();
    render();
}

async function runPipeline() {
    if (pipelineRunning || !selectedApp) return;
    pipelineRunning = true;
    const pipelineApp = selectedApp;
    render();
    let completed = 0;
    for (const action of pipelineApp.actions) {
        showRunBanner(action.label, `Step ${completed + 1} of ${pipelineApp.actions.length}`);
        const succeeded = await executeAction(action);
        if (!succeeded) break;
        completed++;
    }
    pipelineRunning = false;
    hideRunBanner();
    render();
}

function showRunBanner(actionLabel, detail) {
    runBanner.classList.add('visible');
    document.getElementById('run-banner-title').textContent = actionLabel;
    document.getElementById('run-banner-detail').textContent = `${detail} · ${selectedApp.rootName}`;
}

function hideRunBanner() { runBanner.classList.remove('visible'); }
function cancelRun() { if (activeController) activeController.abort(); }

runAllButton.addEventListener('click', runPipeline);
cancelButton.addEventListener('click', cancelRun);
bannerCancelButton.addEventListener('click', cancelRun);
exportAbilitiesButton.addEventListener('click', exportAbilities);
importAbilitiesButton.addEventListener('click', chooseAbilityImport);
importAbilitiesFile.addEventListener('change', importAbilities);

if (!hasRequestedApp) {
    try {
        const savedApp = localStorage.getItem('tcgengine:generator-admin:app');
        selectedApp = apps.find(app => app.rootName === savedApp) || selectedApp;
    } catch (_) {}
}
render();
</script>
</body>
</html>
