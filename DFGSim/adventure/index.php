<!doctype html>
<html lang="en"><head><meta charset="utf-8"><base href="../"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>El’s Invitation · The First Courtyard</title><link rel="icon" href="assets/favicon-no-pips-32.png"><link rel="stylesheet" href="cards.css"><link rel="stylesheet" href="adventure/world.css?v=<?=filemtime(__DIR__.'/world.css')?>"><link rel="stylesheet" href="adventure/soundtrack.css?v=<?=filemtime(__DIR__.'/soundtrack.css')?>"></head>
<body class="adventure-shell">
<main aria-label="El’s Invitation adventure">
<h1 class="visually-hidden">El’s Invitation · The First Courtyard</h1>
<section class="world-stage" aria-label="Explorable First Courtyard"><canvas id="worldCanvas" tabindex="0" aria-label="World map. Move with arrow keys or W A S D. Press E or Space to interact. You can also click a person or destination."></canvas>
<div id="loading" class="loading"><span class="brand-sun">☀</span><h2>Following the rivers…</h2><p>Preparing your courtyard.</p></div>
<div class="map-top"><span class="location-plaque" id="areaName">Sanctuary of First Light</span></div>
<header class="world-header adventure-menu"><nav aria-label="Adventure menus"><button data-panel="journal">Journal <span id="journalCount"></span></button><button data-panel="deck">Techniques</button><button data-panel="equipment">Equipment</button><button data-panel="world">World</button></nav><button id="overview" title="Toggle whole-region overview" aria-pressed="false">Overview <span aria-hidden="true">↗</span></button></header>
<div class="objective-note" id="objectiveNote" aria-live="polite"><span class="eyebrow">YOUR NEXT STEP</span><strong id="objectiveTitle"></strong><span id="objectiveDetail"></span><button id="followQuest">Show me the way →</button></div>
<div class="interaction-bar" hidden><span id="nearbyText"></span><button id="interact" disabled><kbd>E</kbd> Interact</button></div>
<div class="touch-controls" aria-label="Movement controls"><button data-dir="up" aria-label="Walk north">▲</button><button data-dir="left" aria-label="Walk west">◀</button><button data-dir="down" aria-label="Walk south">▼</button><button data-dir="right" aria-label="Walk east">▶</button></div>
<span id="saveStatus" class="map-save-status" role="status">Arriving…</span>
<button id="resumeFight" class="map-resume" hidden>Resume your challenge →</button>
<div id="worldError" class="world-error" role="alert" hidden></div>
</section>
</main>
<iframe id="adventureBattle" class="adventure-battle" title="Champion challenge" allow="autoplay" hidden></iframe>
<dialog id="panel" aria-labelledby="panelTitle"><header class="panel-heading"><div><span class="eyebrow" id="panelKicker">THE FIRST COURTYARD</span><h2 id="panelTitle"></h2></div><button id="closePanel" aria-label="Close panel">✕</button></header><div id="panelContent"></div></dialog>
<div id="toast" class="toast" role="status" hidden></div>
<script src="adventure/soundtrack.js?v=<?=filemtime(__DIR__.'/soundtrack.js')?>" defer></script><script src="card-renderer.js?v=<?=filemtime(__DIR__.'/../card-renderer.js')?>" defer></script><script src="adventure/world.js?v=<?=filemtime(__DIR__.'/world.js')?>" defer></script>
</body></html>
