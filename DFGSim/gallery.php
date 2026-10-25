<?php
declare(strict_types=1);
header('Cache-Control: no-store');
require_once __DIR__ . '/Custom/GameLogic.php';
$galleryData = ['cards' => array_values(array_map('DFGCardPublic', DFGCatalog())), 'colors' => DFG_STYLES];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Card gallery · Deterministic Fighting Game</title><link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-no-pips-16.png?v=1"><link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-no-pips-32.png?v=1"><link rel="icon" type="image/png" sizes="64x64" href="assets/favicon-no-pips-64.png?v=1">
<link rel="stylesheet" href="app.css?v=<?= filemtime(__DIR__ . '/app.css') ?>">
<link rel="stylesheet" href="compact.css?v=<?= filemtime(__DIR__ . '/compact.css') ?>">
<link rel="stylesheet" href="gallery.css?v=<?= filemtime(__DIR__ . '/gallery.css') ?>"><link rel="stylesheet" href="cards.css?v=<?= filemtime(__DIR__ . '/cards.css') ?>"></head>
<body><header class="topbar"><a class="brand" href="./"><span class="brand-mark">DFG</span><span>Deterministic Fighting Game</span></a><a class="text-button gallery-link" href="./">Back to game</a></header>
<main class="shell gallery-shell"><div class="section-heading"><h1>Card gallery</h1><span class="muted">Rules v<?= DFG_RULES_VERSION ?></span></div>
<div class="gallery-toolbar"><label for="cardSearch">Search cards<input id="cardSearch" type="search" placeholder="Name, ability, burn, or switch color" autocomplete="off"></label><nav id="galleryColors" class="gallery-colors" aria-label="Filter by hand"></nav></div>
<p id="galleryCount" class="muted" role="status" aria-live="polite"></p><div id="galleryCards" class="gallery-grid"></div><p id="galleryEmpty" class="muted" hidden>No cards match these filters.</p>
<noscript>Enable JavaScript to view the card gallery.</noscript></main>
<script id="galleryData" type="application/json"><?= json_encode($galleryData, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="card-renderer.js?v=<?= filemtime(__DIR__ . '/card-renderer.js') ?>" defer></script><script src="gallery.js?v=<?= filemtime(__DIR__ . '/gallery.js') ?>" defer></script></body></html>
