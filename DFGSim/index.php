<?php
$reactionManifest = __DIR__ . '/assets/fighter-reactions.json';
$fighterReactionAssets = is_file($reactionManifest) ? json_decode(file_get_contents($reactionManifest), true) : [];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Deterministic Fighting Game · Simulator</title><link rel="stylesheet" href="app.css?v=<?= filemtime(__DIR__ . '/app.css') ?>"><link rel="stylesheet" href="compact.css?v=<?= filemtime(__DIR__ . '/compact.css') ?>"><link rel="stylesheet" href="arena.css?v=<?= filemtime(__DIR__ . '/arena.css') ?>"><link rel="stylesheet" href="menu.css?v=<?= filemtime(__DIR__ . '/menu.css') ?>"></head>
<body class="fight-screen">
<main class="shell">
<div id="error" class="error" role="alert" hidden></div>
<section id="home" class="home-screen" aria-labelledby="homeTitle">
<header class="home-header">
<a class="home-brand" href="./" aria-label="DFG main menu"><span class="home-brand-mark" aria-hidden="true"><svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="8" y="8" width="19" height="25" rx="2" transform="rotate(-15 17.5 20.5)"/><rect x="15" y="6" width="19" height="25" rx="2" fill="#0b131b"/><path d="m26 11-6 9h8l-6 7"/></svg></span><span>DFG<span class="home-brand-caption">DETERMINISTIC FIGHTING GAME</span></span></a>
<span class="home-mode"><span aria-hidden="true"></span> SOLO ARENA</span>
</header>
<div class="home-stage">
<div class="home-scene" aria-hidden="true"><div class="home-scene-light"></div><img class="home-fighter home-fighter-blue" src="assets/fighter-blue-transparent.webp" alt="" draggable="false"><img class="home-fighter home-fighter-red" src="assets/fighter-red-transparent.webp" alt="" draggable="false"><span class="home-stage-caption">THE COURTYARD</span></div>
<div class="home-hero"><span class="eyebrow home-kicker"><span aria-hidden="true"></span> A DUEL OF DECISIONS</span><h1 id="homeTitle" tabindex="-1">Read the move.<br><em>Win the exchange.</em></h1><p class="home-description">Choose your move in secret. Reveal together.<br> Burn cards for power. Every sacrifice is permanent.</p>
<div class="home-actions"><button id="resumeMatch" hidden>Resume match</button><button id="homePlay" class="primary" disabled>Play vs bot</button></div>
<p id="savedMatch" class="home-save" role="status">Loading your local match…</p>
<div class="home-principles" aria-label="Game features"><span>Simultaneous reveals</span><span>36 cards · 6 hands</span><span>No random combat</span></div>
</div>
</div>
<nav class="home-resources" aria-label="Explore the game">
<a class="home-resource home-learn" href="learn.php"><span class="home-resource-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m9 5 11 7-11 7V5Z"/></svg></span><span class="home-resource-copy"><strong>Learn to play <span class="home-resource-tag">5 min</span></strong><span class="home-resource-description">Find your footing with guided practice.</span></span><span class="home-resource-arrow" aria-hidden="true">→</span></a>
<a class="home-resource" href="gallery.php"><span class="home-resource-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="7" y="3" width="13" height="17" rx="2"/><path d="M4 7v13a3 3 0 0 0 3 3M11 8h5m-5 4h5"/></svg></span><span class="home-resource-copy"><strong>Explore the cards</strong><span class="home-resource-description">Six colors. Every move and every route.</span></span><span class="home-resource-arrow" aria-hidden="true">→</span></a>
<button id="homeRules" class="home-resource" aria-haspopup="dialog" aria-expanded="false"><span class="home-resource-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 5C9 3 5 3 2 5v15c3-2 7-2 10 0 3-2 7-2 10 0V5c-3-2-7-2-10 0Zm0 0v15"/></svg></span><span class="home-resource-copy"><strong>Rules reference</strong><span class="home-resource-description">Combat, burns, and advantage.</span></span><span class="home-resource-arrow" aria-hidden="true">+</span></button>
</nav>
<footer class="home-footer"><span class="home-palette" aria-hidden="true"><i class="red"></i><i class="orange"></i><i class="yellow"></i><i class="green"></i><i class="blue"></i><i class="purple"></i></span><p>Local solo play <span aria-hidden="true">/</span> Match saved in this browser’s session</p></footer>
</section>
<div id="gameView" hidden>
<section class="arena" aria-label="Fight arena">
<details id="matchMenu" class="match-menu"><summary aria-label="Match menu" title="Match menu"><span aria-hidden="true">☰</span></summary><div class="match-actions"><button id="goHome">Main menu</button><a class="text-button gallery-link" href="learn.php">Learn to play</a><a class="text-button gallery-link" href="gallery.php">Card gallery</a><button id="showRules" class="text-button" aria-haspopup="dialog" aria-expanded="false">Rules</button><button id="showLog" class="text-button" aria-haspopup="dialog" aria-expanded="false">Fight log</button><button id="resign" class="text-button" hidden>Resign match</button><button id="newMatch">New match</button></div></details>

<div class="combat-layout">
<div class="arena-visual" aria-hidden="true"><div class="arena-art">
<?php if (is_file(__DIR__ . '/assets/arena-courtyard-ambient.mp4')): ?>
<video id="arenaBackdrop" class="arena-backdrop" data-src="assets/arena-courtyard-ambient.mp4?v=<?= filemtime(__DIR__ . '/assets/arena-courtyard-ambient.mp4') ?>" muted loop playsinline preload="none" tabindex="-1" hidden></video>
<?php endif; ?>
<picture><source media="(prefers-reduced-motion: reduce)" srcset="assets/fighter-blue-transparent.webp"><img class="arena-fighter arena-fighter-player" src="assets/fighter-blue-idle-v2.webp?v=<?= filemtime(__DIR__ . '/assets/fighter-blue-idle-v2.webp') ?>" alt="" draggable="false"></picture>
<picture><source media="(prefers-reduced-motion: reduce)" srcset="assets/fighter-red-transparent.webp"><img class="arena-fighter arena-fighter-opponent" src="assets/fighter-red-idle.webp?v=<?= filemtime(__DIR__ . '/assets/fighter-red-idle.webp') ?>" alt="" draggable="false"></picture>
</div></div>
<div id="damageEffects" class="damage-effects" aria-hidden="true"></div>
<div id="fighters" class="fighters"></div>
<div class="arena-round" aria-label="Current round"><span>ROUND</span><strong id="roundNumber">—</strong></div>
<div id="timeline" class="timeline" aria-label="Round sequence"></div>
<div id="reveals" class="reveals"></div>
<section id="matchControls" class="action-panel" aria-label="Match actions"><span id="phaseTag" class="eyebrow" hidden>WELCOME</span><h2 id="phaseTitle" role="status" aria-live="polite">Your first fight</h2><p id="phaseHint"></p><div id="controls"></div></section>
</div>
<p class="muted advantage-help">Earn advantage by completing a main card’s objective. Spend it through a main card’s payoff; unused tokens carry into later rounds.</p>
</section>
<div class="play-layout">
<section class="hand-panel"><div id="yourHands"><div id="cards" class="card-grid"></div></div></section>
</div>

</div>
</main>
<dialog id="fightLog" aria-labelledby="fightLogTitle"><div class="fight-log-heading"><h2 id="fightLogTitle">Fight log</h2><button id="closeLog" aria-label="Close fight log">×</button></div><button id="refresh" class="text-button">Refresh</button><div id="log" class="log" aria-live="polite"></div></dialog>
<template id="rulesContent"><div class="rules-content"><ul><li>40 life each. Combat damage = max(0, attack − opponent block). Guarded Strike deals 2 direct damage after combat if it took no combat damage; direct damage bypasses block. Both retaliations resolve simultaneously without recursion. Total damage determines objectives, other damage triggers, and defeat before recovery.</li><li>Choose one main card and up to one other bonus burn from your active hand. Commit face down, then reveal together.</li><li>Normal cards return after damage. Burned cards are permanently discarded. A main cannot be a bonus or reaction burn, but a Purple-spending Charged Fist permanently burns the opposing main on hit after damage.</li><li>Players alternate one reaction burn or pass. Two consecutive passes close the window. First reaction priority alternates each round.</li><li>Reaction burns reveal immediately. Main abilities, bonuses, and reactions stack. Printed conditions use unmodified stats. On-hit and no-damage effects check final simultaneous damage.</li><li>Choose a listed bottom color, modified by card effects. Restrictions always leave a destination. Empty printed destinations remain legal; entering an empty hand loses the fight. There is no fallback to another hand. Driving Tackle can spend Orange to ignore 2 block and, on hit, choose one opposing legal next color to prohibit if more than one remains. After all route cuts, a Yellow-spending Driving Kick that hit chooses the opponent’s legal next hand. Resolve all forced destinations before normal hand selections; both normal next hands reveal together.</li><li>Eighteen main cards have objectives that earn advantage after final damage. Tokens persist across hands, capped at one per color. Select tokens through a spending main before committing; burns cannot spend them. Spent tokens are consumed on reveal even if their on-hit payoff fails. Combination Strike may spend any one color for +1 attack, or any two different colors instead for +4 attack, +2 block, and ignore 2 opposing block. Its token colors do not add destinations.</li><li>Rooted Guard sets up an independent objective: deal 4+ damage in the following round to earn Green advantage. It expires after that round; Counterpunch can deny the award. Outside Step gains +1 attack next round on hit, independently of its printed-block read. Rushdown and Glancing Step pressure adds 2 attack for the next round only. Glancing Step grants pressure and Yellow advantage after taking exactly 1 final damage; it does not require dealing damage. Driving Palm recovers at most 1 life per hit, capped at 40, and cannot prevent a knockout. Counterpunch can prevent this round’s advantage awards, including simultaneous objectives.</li><li>Darting Strike is 6/0. Declare its optional Yellow spend with the face-down commitment; the token is consumed on reveal even if you keep Darting Strike. Before reactions, keep it or permanently burn it to choose another available Yellow main. The committed bonus and carried effects stay. Replacement abilities and destinations apply, and printed-stat reads are recomputed. The replacement cannot spend advantage. If both players cancel, choices stay hidden until both decide. A Clinch Throw burn lock prevents cancelling.</li><li>Brace can spend Green to suppress the opponent’s carried attack bonus this round and prevent either fighter gaining attack bonuses for next round. It does not cancel delayed objectives. Mirror Step checks printed attack and block exactly 3 independently; spending Blue removes one held opposing token for dealing damage and another for taking no damage. Choose removals after damage and before new objective awards. Spent tokens cannot be removed. Clinch Throw can spend Orange to prevent opposing bonus and reaction burns in the following round on hit; mains, advantage spending, and involuntary main-card burns remain legal. Haymaker is 7/0; dealing no combat damage gives its owner -2 final block in the following round, to a minimum of 0. Carried effects are public and last for their stated round.</li><li>Bait and Slip is 3/2 and gains +4 block against a committed bonus burn. After damage, it may return the surviving opposing main to a different hand of your choice, before route cuts and destination choices. The new hand persists; printed color, stats, abilities, and destinations stay unchanged. Orbs show extra cards above the starting six.</li><li>Grip Break is 4/2. An opposing block-increasing bonus grants +2 attack and +1 block; an opposing attack bonus carried into this round independently grants +3 block, reaching 6/6 when both apply. Deal damage against the defensive bonus, or take no final damage against carried pressure, to earn one Blue advantage.</li><li>Quick Jab gains +2 attack and ignores opposing block gained from reaction burns when you commit no bonus burn. Printed, main-ability, bonus, and advantage block still apply. Opposing reaction attack still applies, and your own reaction burns do not disable this effect.</li><li>Iron Guard is 1/5 and gains +1 attack when you commit no bonus burn. Taking no final damage grants +1 attack next round; dealing damage independently earns Green advantage. This pressure follows you across hands, lasts one round, and does not stack with other attack pressure. Brace can suppress it.</li><li>Heavy Feint is 4/2 and gains +2 attack if the opponent spent advantage this round, regardless of token count. This is independent of ignoring their bonus-burn block. Tokens consumed on reveal still count, including Yellow spent before a Darting Strike replacement.</li><li>Zero life, no remaining cards, or entering an empty hand loses the fight; simultaneous losses are a draw. Bot choices vary with the seed; combat itself has no randomness.</li></ul><p>All 36 cards are a provisional draft. No balance claims yet.</p></div></template>
<aside id="cardPreview" class="card-preview" role="region" aria-label="Card details" hidden><button id="closePreview" class="preview-close" aria-label="Close card preview">×</button><div id="previewContent"></div><p class="preview-help">Hover or focus a tile to inspect · click to pin · Esc to close</p></aside>
<dialog id="setup" aria-labelledby="setupTitle" aria-describedby="setupIntro">
<form id="setupForm">
<span class="eyebrow">SOLO ARENA · VS BOT</span>
<h2 id="setupTitle">Choose your opening.</h2>
<p id="setupIntro">All six hands are yours. Only your active hand can play or burn cards.</p>
<div class="setup-grid">
<label>Your opening hand<select id="humanColor" name="humanColor"></select></label>
<label>Bot opening hand<select id="botColor" name="botColor"></select></label>
<label class="setup-style">Bot style<select name="difficulty" aria-describedby="botStyleHelp"><option value="tactical">Tactical · balanced challenge</option><option value="pro">Pro · deeper planning</option><option value="casual">Casual · relaxed play</option></select><span id="botStyleHelp" class="setup-field-help">Tactical is a good place to start. Pro takes longer to plan its moves.</span></label>
</div>
<details class="setup-advanced"><summary>Advanced options</summary><div class="setup-grid">
<label>First reaction priority<select name="first"><option value="0">You</option><option value="1">Bot</option></select></label>
<label>Bot seed <span class="setup-optional">(optional)</span><input name="seed" type="number" min="1" max="2147483646" placeholder="Random" aria-describedby="seedHelp"></label>
</div><p id="seedHelp" class="setup-field-help">Leave the seed blank for a fresh opponent. The same seed and actions reproduce the bot’s choices.</p></details>
<div class="dialog-actions"><button type="button" id="cancelSetup">Cancel</button><button type="submit" class="primary">Start fight <span aria-hidden="true">→</span></button></div>
</form>
</dialog>
<input type="hidden" id="folderPath" value="DFGSim"><div id="popupContainer"></div><aside id="cardDetail" class="card-preview" style="display:none" aria-label="Card details"></aside><script src="../Core/jsInclude.js?v=<?= filemtime(__DIR__ . '/../Core/jsInclude.js') ?>" defer></script><script src="../Core/CardMotion.js?v=<?= filemtime(__DIR__ . '/../Core/CardMotion.js') ?>" defer></script>
<script src="card-renderer.js?v=<?= filemtime(__DIR__ . '/card-renderer.js') ?>" defer></script>
<script id="fighterReactionAssets" type="application/json"><?= json_encode($fighterReactionAssets ?: (object)[], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="fighter-reactions.js?v=<?= filemtime(__DIR__ . '/fighter-reactions.js') ?>" defer></script>
<script src="arena-background.js?v=<?= filemtime(__DIR__ . '/arena-background.js') ?>" defer></script>
<script src="app.js?v=<?= filemtime(__DIR__ . '/app.js') ?>" defer></script>
</body></html>
