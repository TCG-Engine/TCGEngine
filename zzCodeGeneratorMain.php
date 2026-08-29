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

function GeneratorAdminHasValidCardCache($rootName)
{
    $cachePath = __DIR__ . '/' . $rootName . '/GeneratedCode/cardArrayCache.json';
    if (!is_file($cachePath)) return false;
    $cache = json_decode((string)file_get_contents($cachePath), true);
    return is_array($cache) && is_array($cache['cardArray'] ?? null) && count($cache['cardArray']) > 0;
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

    if ($rootName === 'HellbreakSim') {
        $actions[] = GeneratorAdminAction(
            'hellbreak-workbook',
            'Import Hellbreak workbook',
            'Download and normalize the shared community workbook. A local .xlsx can optionally override the public source.',
            'DevTools/Hellbreak/AdminWorkbookImport.php',
            'DevTools/Hellbreak/import-workbook.php',
            'workbook'
        );
    }
    if (is_file($schemaDirectory . '/ImportSchema.txt') && $rootName !== 'HellbreakDeck') {
        $actions[] = GeneratorAdminAction(
            'cards',
            'Card data & images',
            $rootName === 'HellbreakSim'
                ? 'Rebuild card dictionaries from the normalized Hellbreak workbook cache.'
                : 'Rebuild card dictionaries from cache or fetch current source data.',
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
    if ($rootName === 'HellbreakSim' && is_file($schemaRoot . '/HellbreakDeck/GameSchema.txt')) {
        $actions[] = GeneratorAdminAction(
            'hellbreak-deck',
            'Hellbreak deck runtime',
            'Regenerate the deck editor after the shared Hellbreak card data and simulator runtime.',
            'zzGameCodeGenerator.php?rootName=HellbreakDeck',
            'Schemas/HellbreakDeck/GameSchema.txt'
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
        'hasCropTester' => is_dir(__DIR__ . '/' . $rootName . '/WebpImages'),
        'usesWorkbookImport' => $rootName === 'HellbreakSim',
        'hasValidCardCache' => $rootName === 'HellbreakSim' && GeneratorAdminHasValidCardCache('HellbreakSim'),
    ];
}

// Environment-level, not per-app: every site reaches only its own MySQL server, and shared-database
// apps (HellbreakDeck runs on hellbreaksim) have no database of their own.
$configuredDatabase = getenv('MYSQL_DATABASE_NAME') ?: 'swuonline';
$configuredDatabaseHost = getenv('MYSQL_SERVER_NAME') ?: 'localhost';

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
        button, input, select { font: inherit; }
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
        .button-link { display: inline-flex; align-items: center; text-decoration: none; }

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
        /* appearance:none and an explicit height are both required: WebKit renders a native select
           at its own intrinsic height and ignores min-height, which leaves the control ~20px next
           to the 34px buttons beside it. Dropping the native chrome costs us the arrow, so one is
           drawn back in as a background chevron. */
        .select-input {
            appearance: none;
            -webkit-appearance: none;
            max-width: 320px;
            height: 34px;
            padding: 0 26px 0 8px;
            border: 1px solid var(--line-strong);
            border-radius: 8px;
            background-color: var(--panel-raised);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' fill='none' stroke='%238d9aae' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 9px center;
            background-size: 10px 6px;
            color: inherit;
            font: inherit;
            font-size: 12px;
        }
        .select-input:disabled { cursor: not-allowed; opacity: .45; }
        .file-choice { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .file-choice-name { max-width: 360px; color: var(--muted); font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .transfer-controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .transfer-status { width: 100%; margin: -5px 0 20px; color: var(--muted); font-size: 12px; }
        .transfer-status[data-kind="success"] { color: var(--green); }
        .transfer-status[data-kind="error"] { color: var(--red); }
        .token-panel { display: block; }
        .token-panel-head { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .token-panel-head .options-title { flex: 1; min-width: 240px; }
        .token-list { width: 100%; margin-top: 14px; display: grid; gap: 8px; }
        .token-row { display: grid; grid-template-columns: minmax(180px, 1.4fr) minmax(150px, 1fr) minmax(130px, 1fr) auto; gap: 12px; align-items: center; padding: 11px 12px; border: 1px solid var(--line); border-radius: 9px; background: var(--panel); }
        .token-row strong, .token-row small { display: block; }
        .token-row small { margin-top: 3px; color: var(--muted); font-size: 10px; }
        .token-prefix { font-family: "Cascadia Code", Consolas, monospace; color: #b9dafb; }
        .token-status { display: inline-flex; width: fit-content; padding: 3px 7px; border-radius: 999px; background: #1a3a2a; color: var(--green); font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .token-status[data-status="expired"], .token-status[data-status="revoked"] { background: #382326; color: #ffaaaa; }
        .token-actions { display: flex; gap: 6px; justify-content: flex-end; }
        .token-empty { padding: 18px; border: 1px dashed var(--line); border-radius: 9px; color: var(--muted); font-size: 12px; text-align: center; }
        .modal-overlay { position: fixed; inset: 0; z-index: 9000; display: flex; align-items: center; justify-content: center; padding: 20px; background: rgba(0,0,0,.66); }
        .modal-card { width: min(480px, 100%); padding: 20px; border: 1px solid var(--line-strong); border-radius: 12px; background: #12202f; box-shadow: var(--shadow); }
        .modal-card h3 { margin: 0 0 7px; font-size: 18px; }
        .modal-card > p { margin: 0 0 18px; color: var(--muted); font-size: 12px; line-height: 1.5; }
        .modal-field { display: grid; gap: 6px; margin: 13px 0; color: #c7d0dc; font-size: 12px; }
        .modal-input { width: 100%; min-height: 40px; padding: 8px 10px; border: 1px solid var(--line-strong); border-radius: 8px; background: var(--panel-soft); color: var(--text); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; }
        .token-secret { font-family: "Cascadia Code", Consolas, monospace; font-size: 12px; }

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
            .token-panel-head .options-title { width: 100%; }
            .token-row { grid-template-columns: 1fr; }
            .token-actions { justify-content: flex-start; }
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
                    <a class="button button-link" id="crop-tester-link" href="zzCropTester.php" target="_blank" rel="noopener">Crop tester</a>
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
                <label class="switch" id="with-preview-option"><input type="checkbox" id="with-preview"> Fetch current source data</label>
                <label class="switch"><input type="checkbox" id="overwrite-images"> Replace existing images</label>
            </section>

            <section class="options" id="hellbreak-workbook-options" hidden>
                <div class="options-title">
                    <strong>Hellbreak source workbook</strong>
                    <small>The public OneDrive workbook is used automatically. Choose a local .xlsx only to override it.</small>
                </div>
                <div class="file-choice">
                    <button type="button" class="button button-small" id="choose-hellbreak-workbook">Choose workbook</button>
                    <span class="file-choice-name" id="hellbreak-workbook-name">No workbook selected</span>
                    <input type="file" id="hellbreak-workbook-file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" hidden>
                </div>
            </section>

            <section class="options" id="database-options">
                <div class="options-title">
                    <strong>Database</strong>
                    <small>Create <?= htmlspecialchars($configuredDatabase, ENT_QUOTES, 'UTF-8') ?> and its card tables if missing.</small>
                </div>
                <div class="transfer-controls">
                    <button type="button" class="button button-small" id="ensure-database-button">Set up database</button>
                </div>
            </section>
            <p class="transfer-status" id="database-status" role="status" aria-live="polite"></p>

            <section class="options token-panel" id="card-code-token-options">
                <div class="token-panel-head">
                    <div class="options-title">
                        <strong>Hosted Card Code access</strong>
                        <small>Issue expiring credentials for developers and integrations. Secrets are shown once.</small>
                    </div>
                    <button type="button" class="button button-small" id="refresh-token-button">Refresh</button>
                    <button type="button" class="button button-small button-primary" id="create-token-button">Generate token</button>
                </div>
                <p class="transfer-status" id="token-status" role="status" aria-live="polite"></p>
                <div class="token-list" id="token-list" aria-live="polite"></div>
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

            <section class="options" id="card-data-transfer-options">
                <div class="options-title">
                    <strong>Generated card data</strong>
                    <small>Move this app's card cache between machines; dictionaries and art crops are rebuilt here.</small>
                </div>
                <label class="switch" id="include-art-option"><input type="checkbox" id="include-art"> Include card art</label>
                <div class="transfer-controls">
                    <button type="button" class="button button-small" id="export-card-data-button">Export archive</button>
                    <button type="button" class="button button-small" id="import-card-data-button">Import archive</button>
                    <input type="file" id="import-card-data-file" accept=".zip,.tar,.gz,.tgz,.json" hidden>
                </div>
            </section>
            <p class="transfer-status" id="card-data-transfer-status" role="status" aria-live="polite"></p>

            <section class="options" id="card-editor-transfer-options">
                <div class="options-title">
                    <strong>CardEditor game</strong>
                    <small>Bundle one authored game's ce_* tables. Games are picked here, not in the sidebar — CardEditor data is keyed by game, not by app.</small>
                </div>
                <label class="switch">
                    <span>Game</span>
                    <select id="card-editor-game" class="select-input"><option value="">Loading…</option></select>
                </label>
                <label class="switch" id="include-assets-option"><input type="checkbox" id="include-assets"> Include asset files</label>
                <div class="transfer-controls">
                    <button type="button" class="button button-small" id="export-card-editor-button">Export bundle</button>
                    <button type="button" class="button button-small" id="import-card-editor-button">Import bundle</button>
                    <input type="file" id="import-card-editor-file" accept=".zip,application/zip" hidden>
                </div>
            </section>
            <p class="transfer-status" id="card-editor-transfer-status" role="status" aria-live="polite"></p>

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
const withPreviewOption = document.getElementById('with-preview-option');
const overwriteImages = document.getElementById('overwrite-images');
const hellbreakWorkbookOptions = document.getElementById('hellbreak-workbook-options');
const chooseHellbreakWorkbookButton = document.getElementById('choose-hellbreak-workbook');
const hellbreakWorkbookFile = document.getElementById('hellbreak-workbook-file');
const hellbreakWorkbookName = document.getElementById('hellbreak-workbook-name');
const exportAbilitiesButton = document.getElementById('export-abilities-button');
const importAbilitiesButton = document.getElementById('import-abilities-button');
const importAbilitiesFile = document.getElementById('import-abilities-file');
const abilityTransferStatus = document.getElementById('ability-transfer-status');
const cardDataTransferOptions = document.getElementById('card-data-transfer-options');
const exportCardDataButton = document.getElementById('export-card-data-button');
const importCardDataButton = document.getElementById('import-card-data-button');
const importCardDataFile = document.getElementById('import-card-data-file');
const includeArt = document.getElementById('include-art');
const cardDataTransferStatus = document.getElementById('card-data-transfer-status');
const cardEditorGameSelect = document.getElementById('card-editor-game');
const includeAssets = document.getElementById('include-assets');
const exportCardEditorButton = document.getElementById('export-card-editor-button');
const importCardEditorButton = document.getElementById('import-card-editor-button');
const importCardEditorFile = document.getElementById('import-card-editor-file');
const cardEditorTransferStatus = document.getElementById('card-editor-transfer-status');
const ensureDatabaseButton = document.getElementById('ensure-database-button');
const databaseStatus = document.getElementById('database-status');
const createTokenButton = document.getElementById('create-token-button');
const refreshTokenButton = document.getElementById('refresh-token-button');
const tokenStatus = document.getElementById('token-status');
const tokenList = document.getElementById('token-list');
const cropTesterLink = document.getElementById('crop-tester-link');
const outputs = new Map();
const runStates = new Map();
let selectedApp = apps.find(app => app.rootName === initialApp) || apps[0] || null;
let activeController = null;
let pipelineRunning = false;
let transferRunning = false;
let importApp = null;
let cardDataImportApp = null;
let tokenRequestRoot = null;
// CardEditor games are environment-level, not per-app, so they are loaded once rather than on
// every sidebar selection. null means "not loaded yet"; [] means the database has none.
let cardEditorGames = null;

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
    withPreview.checked = false;
    const url = new URL(window.location.href);
    url.searchParams.set('app', rootName);
    window.history.replaceState(null, '', url);
    try { localStorage.setItem('tcgengine:generator-admin:app', rootName); } catch (_) {}
    render();
    loadTokens();
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
    cropTesterLink.hidden = !selectedApp.hasCropTester;
    cropTesterLink.href = `zzCropTester.php?app=${encodeURIComponent(selectedApp.rootName)}`;
    document.getElementById('step-count').textContent = selectedApp.actions.length;
    document.getElementById('schema-count').textContent = selectedApp.actions.filter(action => ['cards', 'game', 'turn'].includes(action.id)).length;
    document.getElementById('card-options').hidden = selectedApp.usesWorkbookImport || !selectedApp.actions.some(action => action.id === 'cards');
    withPreviewOption.hidden = selectedApp.usesWorkbookImport;
    if (selectedApp.usesWorkbookImport) withPreview.checked = false;
    hellbreakWorkbookOptions.hidden = !selectedApp.usesWorkbookImport;
    hellbreakWorkbookName.textContent = hellbreakWorkbookFile.files && hellbreakWorkbookFile.files[0]
        ? hellbreakWorkbookFile.files[0].name
        : (selectedApp.hasValidCardCache ? 'Public source ready — valid cache will be reused' : 'Public OneDrive source will be downloaded');
    chooseHellbreakWorkbookButton.disabled = pipelineRunning;
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
    // Only apps with a card-data step have a cardArrayCache.json to move.
    const hasCardDataStep = selectedApp.actions.some(action => action.id === 'cards');
    cardDataTransferOptions.hidden = !hasCardDataStep;
    cardDataTransferStatus.hidden = !hasCardDataStep;
    exportCardDataButton.disabled = pipelineRunning || transferRunning;
    importCardDataButton.disabled = pipelineRunning || transferRunning;
    includeArt.disabled = pipelineRunning || transferRunning;
    // Import stays available with no games listed — importing is how the first one arrives.
    const hasCardEditorGame = Array.isArray(cardEditorGames) && cardEditorGames.length > 0;
    cardEditorGameSelect.disabled = pipelineRunning || transferRunning || !hasCardEditorGame;
    includeAssets.disabled = cardEditorGameSelect.disabled;
    exportCardEditorButton.disabled = cardEditorGameSelect.disabled;
    importCardEditorButton.disabled = pipelineRunning || transferRunning;
    ensureDatabaseButton.disabled = pipelineRunning || transferRunning;
    createTokenButton.disabled = pipelineRunning || transferRunning;
    refreshTokenButton.disabled = pipelineRunning || transferRunning;
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

function tokenModal(title, description, fields, submitLabel) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        const dialog = document.createElement('form');
        dialog.className = 'modal-card';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        const heading = document.createElement('h3');
        heading.textContent = title;
        const copy = document.createElement('p');
        copy.textContent = description;
        dialog.append(heading, copy);
        const controls = {};
        for (const field of fields) {
            const label = document.createElement('label');
            label.className = 'modal-field';
            label.append(document.createTextNode(field.label));
            const control = field.options ? document.createElement('select') : document.createElement('input');
            control.className = 'modal-input';
            control.name = field.name;
            if (field.options) {
                for (const option of field.options) {
                    const element = document.createElement('option');
                    element.value = option.value;
                    element.textContent = option.label;
                    control.append(element);
                }
            } else {
                control.type = field.type || 'text';
                if (field.min !== undefined) control.min = field.min;
                if (field.max !== undefined) control.max = field.max;
                if (field.maxLength !== undefined) control.maxLength = field.maxLength;
            }
            control.value = field.value;
            control.required = true;
            controls[field.name] = control;
            label.append(control);
            dialog.append(label);
        }
        const actions = document.createElement('div');
        actions.className = 'modal-actions';
        const cancel = document.createElement('button');
        cancel.type = 'button'; cancel.className = 'button button-small'; cancel.textContent = 'Cancel';
        const submit = document.createElement('button');
        submit.type = 'submit'; submit.className = 'button button-small button-primary'; submit.textContent = submitLabel;
        actions.append(cancel, submit); dialog.append(actions); overlay.append(dialog); document.body.append(overlay);
        function done(value) {
            overlay.remove();
            document.removeEventListener('keydown', onKey);
            resolve(value);
        }
        function onKey(event) { if (event.key === 'Escape') done(null); }
        cancel.addEventListener('click', () => done(null));
        overlay.addEventListener('click', event => { if (event.target === overlay) done(null); });
        dialog.addEventListener('submit', event => {
            event.preventDefault();
            const result = {};
            for (const [name, control] of Object.entries(controls)) result[name] = control.value;
            done(result);
        });
        document.addEventListener('keydown', onKey);
        (Object.values(controls)[0] || submit).focus();
    });
}

function showTokenSecret(created) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        const dialog = document.createElement('div');
        dialog.className = 'modal-card'; dialog.setAttribute('role', 'dialog'); dialog.setAttribute('aria-modal', 'true');
        const title = document.createElement('h3'); title.textContent = 'Save this token now';
        const copy = document.createElement('p'); copy.textContent = 'This secret cannot be viewed again. Store it in the developer’s secret manager, then close this window.';
        const input = document.createElement('input');
        input.className = 'modal-input token-secret'; input.readOnly = true; input.value = created.token;
        const actions = document.createElement('div'); actions.className = 'modal-actions';
        const copyButton = document.createElement('button'); copyButton.type = 'button'; copyButton.className = 'button button-small button-primary'; copyButton.textContent = 'Copy token';
        const doneButton = document.createElement('button'); doneButton.type = 'button'; doneButton.className = 'button button-small'; doneButton.textContent = 'Done';
        function finish() { overlay.remove(); document.removeEventListener('keydown', onKey); resolve(); }
        function onKey(event) { if (event.key === 'Escape') finish(); }
        copyButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(created.token);
                copyButton.textContent = 'Copied';
            } catch (_) {
                input.focus(); input.select();
                copyButton.textContent = 'Select and copy';
            }
        });
        doneButton.addEventListener('click', finish);
        document.addEventListener('keydown', onKey);
        actions.append(copyButton, doneButton); dialog.append(title, copy, input, actions); overlay.append(dialog); document.body.append(overlay);
        input.focus(); input.select();
    });
}

function formatTokenDate(value) {
    if (!value) return 'Never';
    const parsed = new Date(value.replace(' ', 'T') + 'Z');
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString();
}

function setTokenStatus(message, kind = '') {
    tokenStatus.textContent = message;
    tokenStatus.dataset.kind = kind;
}

function renderTokens(tokens) {
    tokenList.replaceChildren();
    if (!tokens.length) {
        const empty = document.createElement('div'); empty.className = 'token-empty'; empty.textContent = 'No tokens have been issued for this app.';
        tokenList.append(empty); return;
    }
    for (const token of tokens) {
        const row = document.createElement('article'); row.className = 'token-row';
        const identity = document.createElement('div');
        const name = document.createElement('strong'); name.textContent = token.token_name;
        const prefix = document.createElement('small'); prefix.className = 'token-prefix'; prefix.textContent = token.token_prefix ? `${token.token_prefix}…` : 'Legacy token';
        identity.append(name, prefix);
        const permission = document.createElement('div');
        const role = document.createElement('strong'); role.textContent = token.role;
        const creator = document.createElement('small'); creator.textContent = `Created by ${token.created_by_name || 'legacy admin'}`;
        permission.append(role, creator);
        const activity = document.createElement('div');
        const badge = document.createElement('span'); badge.className = 'token-status'; badge.dataset.status = token.status; badge.textContent = token.status;
        const timing = document.createElement('small');
        timing.textContent = token.status === 'active' ? `Expires ${formatTokenDate(token.expires_at)}` : `${token.status === 'revoked' ? 'Revoked' : 'Expired'} ${formatTokenDate(token.revoked_at || token.expires_at)}`;
        const used = document.createElement('small'); used.textContent = `Last used: ${formatTokenDate(token.last_used_at)}`;
        activity.append(badge, timing, used);
        const actions = document.createElement('div'); actions.className = 'token-actions';
        if (token.status === 'active') {
            const rotate = document.createElement('button'); rotate.type = 'button'; rotate.className = 'button button-small'; rotate.textContent = 'Rotate';
            const revoke = document.createElement('button'); revoke.type = 'button'; revoke.className = 'button button-small button-danger'; revoke.textContent = 'Revoke';
            rotate.addEventListener('click', () => rotateToken(token));
            revoke.addEventListener('click', () => revokeToken(token));
            actions.append(rotate, revoke);
        }
        row.append(identity, permission, activity, actions); tokenList.append(row);
    }
}

async function cardCodeTokenRequest(action, body = null) {
    const root = selectedApp && selectedApp.rootName;
    if (!root) throw new Error('Select an app first');
    const url = new URL('CardEditor/API/AdminCardCodeTokens.php', window.location.href);
    if (!body) { url.searchParams.set('action', action); url.searchParams.set('root', root); }
    const response = await fetch(url, body ? {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, root, csrf: generatorAdminCsrf, ...body })
    } : { headers: { 'Accept': 'application/json' } });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || !payload.success) throw new Error(payload.error || `Request failed (${response.status})`);
    return payload;
}

async function loadTokens({ quiet = false } = {}) {
    if (!selectedApp) return;
    const root = selectedApp.rootName;
    tokenRequestRoot = root;
    if (!quiet) setTokenStatus(`Loading access for ${root}…`);
    try {
        const payload = await cardCodeTokenRequest('list');
        if (!selectedApp || selectedApp.rootName !== root || tokenRequestRoot !== root) return;
        renderTokens(payload.tokens || []);
        setTokenStatus(`${payload.tokens.length} token${payload.tokens.length === 1 ? '' : 's'} for ${root}.`, 'success');
    } catch (error) {
        if (selectedApp && selectedApp.rootName === root) {
            tokenList.replaceChildren();
            setTokenStatus(error.message, 'error');
        }
    }
}

async function createToken() {
    if (!selectedApp || pipelineRunning || transferRunning) return;
    const values = await tokenModal('Generate developer token', `Create an expiring credential for ${selectedApp.rootName}.`, [
        { name: 'name', label: 'Developer or integration name', value: '', maxLength: 128 },
        { name: 'role', label: 'Role', value: 'developer', options: [
            { value: 'reader', label: 'Reader — view code only' },
            { value: 'developer', label: 'Developer — view and edit code' },
            { value: 'maintainer', label: 'Maintainer — edit and create checkpoints' },
            { value: 'owner', label: 'Owner — full access, including restore' }
        ] },
        { name: 'expiresDays', label: 'Expires after days (1–365)', type: 'number', value: '90', min: 1, max: 365 }
    ], 'Generate');
    if (!values) return;
    try {
        setTokenStatus('Generating token…');
        const payload = await cardCodeTokenRequest('create', { name: values.name, role: values.role, expiresDays: Number(values.expiresDays) });
        await showTokenSecret(payload.created);
        await loadTokens({ quiet: true });
    } catch (error) { setTokenStatus(error.message, 'error'); }
}

async function revokeToken(token) {
    if (!await styledConfirm(`Revoke “${token.token_name}”? The developer will immediately lose access.`, { confirmLabel: 'Revoke', danger: true })) return;
    try {
        await cardCodeTokenRequest('revoke', { tokenId: token.id });
        await loadTokens({ quiet: true });
    } catch (error) { setTokenStatus(error.message, 'error'); }
}

async function rotateToken(token) {
    const values = await tokenModal('Rotate token', `The current token for ${token.token_name} will stop working immediately.`, [
        { name: 'expiresDays', label: 'New token expires after days (1–365)', type: 'number', value: '90', min: 1, max: 365 }
    ], 'Rotate');
    if (!values) return;
    try {
        const payload = await cardCodeTokenRequest('rotate', { tokenId: token.id, expiresDays: Number(values.expiresDays) });
        await showTokenSecret(payload.created);
        await loadTokens({ quiet: true });
    } catch (error) { setTokenStatus(error.message, 'error'); }
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

async function ensureDatabase() {
    if (pipelineRunning || transferRunning) return;
    transferRunning = true;
    render();
    databaseStatus.textContent = 'Checking database…';
    databaseStatus.dataset.kind = '';
    try {
        const form = new FormData();
        form.set('csrf', generatorAdminCsrf);
        const response = await fetch('DevTools/AdminEnsureDatabase.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: form,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(payload.error || `Database setup failed with HTTP ${response.status}`);

        const made = [];
        if (payload.databaseCreated) made.push(`created database ${payload.database}`);
        if (payload.tableCreated) made.push('created card_abilities');
        const summary = made.length ? made.join(', ') : 'already up to date';
        databaseStatus.textContent = `${payload.database} on ${payload.host}: ${summary} (${payload.abilityRows} ability rows).`;
        databaseStatus.dataset.kind = 'success';
    } catch (error) {
        databaseStatus.textContent = error.message || 'Database setup failed.';
        databaseStatus.dataset.kind = 'error';
    } finally {
        transferRunning = false;
        render();
    }
}

function setCardDataStatus(message, kind = '') {
    cardDataTransferStatus.textContent = message;
    cardDataTransferStatus.dataset.kind = kind;
}

function exportCardData() {
    if (!selectedApp || pipelineRunning || transferRunning) return;
    const url = new URL('DevTools/AdminGeneratedCardDataTransfer.php', window.location.href);
    url.searchParams.set('action', 'export');
    url.searchParams.set('app', selectedApp.rootName);
    if (includeArt.checked) url.searchParams.set('includeArt', '1');
    window.location.assign(url);
    setCardDataStatus(`Preparing ${selectedApp.rootName} card data${includeArt.checked ? ' and art' : ''} for download…`);
}

function chooseCardDataImport() {
    if (!selectedApp || pipelineRunning || transferRunning) return;
    cardDataImportApp = selectedApp;
    importCardDataFile.value = '';
    importCardDataFile.click();
}

async function importCardData() {
    const file = importCardDataFile.files && importCardDataFile.files[0];
    const app = cardDataImportApp;
    if (!file || !app) return;
    const confirmed = await styledConfirm(`Replace ${app.rootName}'s card cache with the contents of ${file.name}, then rebuild its card dictionaries? The previous cache is kept as cardArrayCache.json.bak.`, { confirmLabel: 'Replace', danger: true });
    if (!confirmed) return;

    transferRunning = true;
    render();
    setCardDataStatus(`Importing ${app.rootName} card data…`);
    try {
        const form = new FormData();
        form.set('action', 'import');
        form.set('app', app.rootName);
        form.set('csrf', generatorAdminCsrf);
        form.set('archiveFile', file);
        const response = await fetch('DevTools/AdminGeneratedCardDataTransfer.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: form,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(payload.error || `Import failed with HTTP ${response.status}`);

        const artNote = payload.artWritten ? ` and ${payload.artWritten} art files` : '';
        setCardDataStatus(`Imported ${payload.cardCount} cards${artNote} for ${app.rootName}. Rebuilding card dictionaries…`);
        // The archive carries only the cache, so the dictionaries must be regenerated by THIS
        // checkout's generator before the imported cards are usable.
        const cardsAction = app.actions.find(action => action.id === 'cards');
        if (cardsAction) {
            pipelineRunning = true;
            showRunBanner(cardsAction.label, 'Rebuilding after card data import');
            const generated = await executeAction(cardsAction);
            pipelineRunning = false;
            hideRunBanner();
            if (!generated) throw new Error('Card data was imported, but the rebuild failed; inspect the Card data & images output.');
        }
        const derivativeNote = payload.derivativesFailed
            ? ` ${payload.derivativesFailed} art files could not have their crops rebuilt.`
            : '';
        setCardDataStatus(`Imported ${payload.cardCount} cards${artNote} for ${app.rootName} and rebuilt its dictionaries.${derivativeNote} Run the build pipeline to refresh the rest of the runtime.`, 'success');
    } catch (error) {
        setCardDataStatus(error.message || 'Card data import failed.', 'error');
    } finally {
        transferRunning = false;
        cardDataImportApp = null;
        importCardDataFile.value = '';
        render();
    }
}

function setCardEditorStatus(message, kind = '') {
    cardEditorTransferStatus.textContent = message;
    cardEditorTransferStatus.dataset.kind = kind;
}

// Repopulates the game dropdown, preserving the current selection where it survives — an import
// reloads the list, and losing the operator's pick mid-panel would be jarring.
function renderCardEditorGames() {
    const previous = cardEditorGameSelect.value;
    cardEditorGameSelect.replaceChildren();

    if (!Array.isArray(cardEditorGames)) {
        cardEditorGameSelect.append(new Option('Loading…', ''));
        return;
    }
    if (cardEditorGames.length === 0) {
        cardEditorGameSelect.append(new Option('No authored games', ''));
        return;
    }
    for (const game of cardEditorGames) {
        const assetNote = game.assetCount ? `, ${game.assetCount} asset${game.assetCount === 1 ? '' : 's'}` : '';
        cardEditorGameSelect.append(new Option(`${game.name} (${game.cardCount} card${game.cardCount === 1 ? '' : 's'}${assetNote})`, String(game.id)));
    }
    if (cardEditorGames.some(game => String(game.id) === previous)) cardEditorGameSelect.value = previous;
}

async function loadCardEditorGames({ quiet = false } = {}) {
    try {
        const url = new URL('CardEditor/API/AdminCardEditorGameTransfer.php', window.location.href);
        url.searchParams.set('action', 'games');
        const response = await fetch(url, { credentials: 'same-origin' });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.error || `Could not list games (HTTP ${response.status})`);
        cardEditorGames = payload.games || [];
        renderCardEditorGames();
        if (!quiet && cardEditorGames.length === 0) {
            setCardEditorStatus('No authored games in this database yet. Import a bundle to add one.');
        }
    } catch (error) {
        cardEditorGames = [];
        renderCardEditorGames();
        setCardEditorStatus(error.message || 'Could not list CardEditor games.', 'error');
    }
    render();
}

function exportCardEditorGame() {
    if (pipelineRunning || transferRunning) return;
    const gameId = cardEditorGameSelect.value;
    if (!gameId) return;
    const game = (cardEditorGames || []).find(candidate => String(candidate.id) === gameId);
    const url = new URL('CardEditor/API/AdminCardEditorGameTransfer.php', window.location.href);
    url.searchParams.set('action', 'export');
    url.searchParams.set('gameId', gameId);
    if (includeAssets.checked) url.searchParams.set('includeAssets', '1');
    window.location.assign(url);
    setCardEditorStatus(`Preparing ${game ? game.name : 'game'}${includeAssets.checked ? ' and its assets' : ''} for download…`);
}

function chooseCardEditorImport() {
    if (pipelineRunning || transferRunning) return;
    importCardEditorFile.value = '';
    importCardEditorFile.click();
}

async function importCardEditorGame() {
    const file = importCardEditorFile.files && importCardEditorFile.files[0];
    if (!file) return;
    const confirmed = await styledConfirm(`Import ${file.name}? If this database already holds the same game it is REPLACED — its sets, templates, cards, tags, and enums are deleted first. Other games are not touched.`, { confirmLabel: 'Import', danger: true });
    if (!confirmed) return;

    transferRunning = true;
    render();
    setCardEditorStatus('Importing CardEditor game…');
    try {
        const form = new FormData();
        form.set('action', 'import');
        form.set('csrf', generatorAdminCsrf);
        form.set('bundleFile', file);
        const response = await fetch('CardEditor/API/AdminCardEditorGameTransfer.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: form,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(payload.error || `Import failed with HTTP ${response.status}`);

        await loadCardEditorGames({ quiet: true });
        const verb = payload.replacedExisting ? 'Replaced' : 'Imported';
        const assetNote = payload.assetsWritten ? ` and ${payload.assetsWritten} asset file${payload.assetsWritten === 1 ? '' : 's'}` : '';
        // A failed file write leaves a row pointing at a missing image; re-importing repairs it, so
        // this is a warning rather than a silent success.
        const failedNote = (payload.assetsFailed || []).length
            ? ` ${payload.assetsFailed.length} asset file(s) could not be written — re-run the import.`
            : '';
        setCardEditorStatus(
            `${verb} ${payload.gameName}: ${payload.cardCount} card${payload.cardCount === 1 ? '' : 's'}${assetNote}.${failedNote}`,
            failedNote ? 'error' : 'success'
        );
    } catch (error) {
        setCardEditorStatus(error.message || 'CardEditor game import failed.', 'error');
    } finally {
        transferRunning = false;
        importCardEditorFile.value = '';
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

    if (action.kind === 'workbook' && !(hellbreakWorkbookFile.files && hellbreakWorkbookFile.files[0])) {
        if (appAtStart.hasValidCardCache) {
            outputs.set(key, 'Skipped workbook import: HellbreakSim/GeneratedCode/cardArrayCache.json already contains card data. Choose a local workbook above to replace it.');
            runStates.set(key, { status: 'success', duration: 0, completedAt: Date.now() });
            render();
            return true;
        }
    }

    runStates.set(key, { status: 'running' });
    render();

    activeController = new AbortController();
    try {
        const requestOptions = {
            credentials: 'same-origin',
            cache: 'no-store',
            signal: activeController.signal,
            headers: { 'X-Generator-Admin': '1' },
        };
        if (action.kind === 'workbook') {
            const form = new FormData();
            form.set('csrf', generatorAdminCsrf);
            if (hellbreakWorkbookFile.files && hellbreakWorkbookFile.files[0]) {
                form.set('workbook', hellbreakWorkbookFile.files[0]);
            }
            requestOptions.method = 'POST';
            requestOptions.body = form;
        }
        const response = await fetch(actionUrl(action), requestOptions);
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
        if (action.kind === 'workbook') {
            appAtStart.hasValidCardCache = true;
            hellbreakWorkbookFile.value = '';
        }
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
ensureDatabaseButton.addEventListener('click', ensureDatabase);
createTokenButton.addEventListener('click', createToken);
refreshTokenButton.addEventListener('click', () => loadTokens());
exportCardDataButton.addEventListener('click', exportCardData);
importCardDataButton.addEventListener('click', chooseCardDataImport);
importCardDataFile.addEventListener('change', importCardData);
exportCardEditorButton.addEventListener('click', exportCardEditorGame);
importCardEditorButton.addEventListener('click', chooseCardEditorImport);
importCardEditorFile.addEventListener('change', importCardEditorGame);
chooseHellbreakWorkbookButton.addEventListener('click', () => {
    if (!pipelineRunning) hellbreakWorkbookFile.click();
});
hellbreakWorkbookFile.addEventListener('change', render);

if (!hasRequestedApp) {
    try {
        const savedApp = localStorage.getItem('tcgengine:generator-admin:app');
        selectedApp = apps.find(app => app.rootName === savedApp) || selectedApp;
    } catch (_) {}
}
render();
loadTokens();
loadCardEditorGames({ quiet: true });
</script>
</body>
</html>
