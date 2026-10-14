<?php
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Local simulator only'); }
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Deterministic Fighting Game · Local simulator</title><link rel="stylesheet" href="app.css?v=<?= filemtime(__DIR__ . '/app.css') ?>"><link rel="stylesheet" href="compact.css?v=<?= filemtime(__DIR__ . '/compact.css') ?>"></head>
<body>
<header class="topbar"><a class="brand" href="./"><span class="brand-mark">DFG</span><span>Deterministic Fighting Game</span></a><div class="match-actions"><a class="text-button gallery-link" href="gallery.php">Card gallery</a><button id="showRules" class="text-button" aria-haspopup="dialog" aria-expanded="false">Rules</button><button id="resign" class="text-button" hidden>Resign match</button><button id="newMatch">New match</button></div></header>
<main class="shell">
<div id="error" class="error" role="alert" hidden></div>
<section class="arena" aria-label="Fight table">

<div id="timeline" class="timeline" aria-label="Round sequence"></div>
<div class="combat-layout"><div id="reveals" class="reveals"></div><div id="fighters" class="fighters"></div></div>
<div id="result" class="result" role="status" hidden></div>
<p class="muted advantage-help">Earn advantage by completing a main card’s objective. Spend it through a main card’s payoff; unused tokens carry into later rounds.</p>
</section>
<div class="play-layout">
<section class="hand-panel"><div id="yourHands"><div id="hands" class="hand-tabs" aria-label="Browse your hands"></div><div id="cards" class="card-grid"></div></div></section>
<aside class="sidebar"><section class="action-panel"><span id="phaseTag" class="eyebrow">WELCOME</span><h2 id="phaseTitle">Your first fight</h2><p id="phaseHint"></p><div id="controls"></div></section>
<details class="history-panel"><summary>Fight log</summary><button id="refresh" class="text-button">Refresh</button><div id="log" class="log" aria-live="polite"></div></details>

</aside>
</div>

</main>
<template id="rulesContent"><div class="rules-content"><ul><li>40 life each. Both players take damage simultaneously. Damage = max(0, attack − opponent block).</li><li>Choose one main card and up to one other bonus burn from your active hand. Commit face down, then reveal together.</li><li>Normal cards return after damage. Burned cards are permanently discarded. Your committed main card cannot also be burned.</li><li>Players alternate one reaction burn or pass. Two consecutive passes close the window. First reaction priority alternates each round.</li><li>Reaction burns reveal immediately. Main abilities, bonuses, and reactions stack. Conditions use printed stats and committed bonus effects.</li><li>Choose a listed bottom color every round. Staying is allowed only if your current color is listed. Both next hands reveal together.</li><li>Sixteen main cards have objectives that earn advantage after final damage. Tokens persist across hands, capped at one per color. Select tokens through a spending main before committing; burns cannot spend them.</li><li>Double knockout is a draw. Bot choices vary with the seed; combat itself has no randomness.</li></ul><p>All 36 cards are a provisional draft. No balance claims yet.</p></div></template>
<aside id="cardPreview" class="card-preview" role="region" aria-label="Card details" hidden><button id="closePreview" class="preview-close" aria-label="Close card preview">×</button><div id="previewContent"></div><p class="preview-help">Hover or focus a tile to inspect · click to pin · Esc to close</p></aside>
<dialog id="setup" aria-labelledby="setupTitle"><form id="setupForm"><span class="eyebrow">LOCAL MATCH · VS BOT</span><h2 id="setupTitle">Choose your opening.</h2><p>All six hands are yours. Only your active hand can play or burn cards.</p><div class="setup-grid"><label>Your opening hand<select id="humanColor" name="humanColor"></select></label><label>Bot opening hand<select id="botColor" name="botColor"></select></label><label>Bot style<select name="difficulty"><option value="tactical">Tactical · reads and resource management</option><option value="casual">Casual · more varied choices</option></select></label><label>First reaction priority<select name="first"><option value="0">You</option><option value="1">Bot</option></select></label></div><label>Bot seed <input name="seed" type="number" min="1" max="2147483646" placeholder="Leave blank for a fresh opponent"></label><p class="muted">Same seed and actions reproduce the bot’s choices.</p><div class="dialog-actions"><button type="button" id="cancelSetup">Cancel</button><button type="submit" class="primary">Start fight</button></div></form></dialog>
<input type="hidden" id="folderPath" value="DFGSim"><div id="popupContainer"></div><aside id="cardDetail" class="card-preview" style="display:none" aria-label="Card details"></aside><script src="../Core/jsInclude.js?v=<?= filemtime(__DIR__ . '/../Core/jsInclude.js') ?>" defer></script><script src="../Core/CardMotion.js?v=<?= filemtime(__DIR__ . '/../Core/CardMotion.js') ?>" defer></script>
<script src="card-renderer.js?v=<?= filemtime(__DIR__ . '/card-renderer.js') ?>" defer></script>
<script src="app.js?v=<?= filemtime(__DIR__ . '/app.js') ?>" defer></script>
</body></html>
