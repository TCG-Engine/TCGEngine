<?php // Classic-only, seat-scoped history of reveal notifications actually received. ?>
<style>
#ga-reveals {position:fixed;right:12px;top:42px;z-index:9500;color:#f4f1e8;font:14px system-ui;max-width:calc(100vw - 24px)}
#ga-reveals summary {cursor:pointer;background:#25302e;border:1px solid #71816e;border-radius:8px;padding:10px 14px;min-height:24px}
#ga-reveals-panel {background:#17201f;border:1px solid #71816e;border-radius:8px;padding:12px;width:340px;max-width:calc(100vw - 50px);max-height:65dvh;overflow:auto;box-shadow:0 8px 30px #0008}
#ga-reveals-panel p {font-size:12px;color:#c3cbc5;margin:0 0 10px}
#ga-reveals-list {display:grid;gap:12px}
#ga-reveals-list h4 {margin:0 0 6px;font-size:12px;color:#c3cbc5}
#ga-reveals-list .ga-reveal-cards {display:flex;flex-wrap:wrap;gap:8px}
#ga-reveals-list button {background:#26352f;color:inherit;border:1px solid #71816e;border-radius:6px;padding:4px;width:96px;cursor:pointer}
#ga-reveals-list button:focus-visible {outline:3px solid #ffe9a2}
#ga-reveals-list img {display:block;width:86px;height:120px;object-fit:contain}
</style>
<details id="ga-reveals">
<summary>Reveals <span id="ga-reveals-count"></span></summary>
<div id="ga-reveals-panel"><p id="ga-reveals-note">Reveals received in this browser for your seat. Select a card to view it again.</p><div id="ga-reveals-list"></div></div>
</details>
<script>
<?php readfile(__DIR__ . '/RevealHistory.js'); ?>
</script>
