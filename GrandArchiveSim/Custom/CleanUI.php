<?php // GA-only presentation. Keep engine-owned controls and their handlers intact. ?>
<style>
.ga-clean-menu { color:#f4ecdb; background:#15252b; border:1px solid #88734480; border-radius:12px; font:13px system-ui; padding:8px; }
.ga-clean-menu > summary { cursor:pointer; padding:6px; }
.ga-clean-menu > .ga-clean-panel { max-height:65dvh; overflow:auto; width:300px; max-width:calc(100vw - 48px); padding-top:8px; }
#ga-settings.ga-floating { position:fixed; top:48px; left:12px; z-index:9500; }
#ga-history { position:fixed; top:42px; right:12px; z-index:9500; }
#ga-settings #ga-field-display { position:static; border:0; background:transparent; padding:0; }
#ga-settings #gaShortcutDock { position:static; display:block; width:100%; margin:8px 0; }
#ga-settings #gaShortcutPanel { position:static; width:100%; max-width:100%; box-sizing:border-box; box-shadow:none; }
#ga-settings .ga-theme-label { display:flex; align-items:center; justify-content:space-between; padding:10px 0; }
#ga-settings select { background:#15252b; color:#f4ecdb; border:1px solid #887344; border-radius:8px; padding:6px; }
#ga-history #ga-reveals { position:static; max-width:100%; font-size:13px; }
#ga-history #ga-reveals-panel { width:auto; max-width:100%; box-shadow:none; border:0; padding:8px 0; }
#ga-history #chatWidget { position:static !important; width:100% !important; max-width:100% !important; }
#ga-history #chatWidgetControls { flex-wrap:wrap; }
#ga-history #chatComposer { max-width:100%; }
#ga-history #chatText { min-width:0; width:160px; }
#ga-history #chatLog { border-radius:8px !important; }
.ga-field-row { background:transparent; border-color:transparent; border-radius:8px; }
.ga-field-row h3 { color:#d6c9ac; letter-spacing:.02em; }
#ga-action-area { position:fixed; top:calc(50% + 24px); right:16px; width:220px; max-height:calc(50vh - 148px); overflow:auto; z-index:45; display:flex; flex-direction:column; gap:8px; }
#ga-action-area #myHealthSlot { position:static !important; width:100%; min-height:0; transform:none !important; }
#ga-action-area #selection-message { position:static !important; width:100% !important; max-width:100% !important; transform:none !important; margin:0 !important; box-shadow:none !important; border-radius:12px !important; box-sizing:border-box; }
#ga-action-area:has(#selection-message button) #myHealthSlot { display:none; }
#selection-message > button:not(:first-of-type) { background:#25343d !important; color:#f4ecdb !important; border-color:#88734480 !important; box-shadow:none !important; }
/* Keep counters and legal/combat indicators visible. Only soften decorative framing. */
body .ga-zone.ga-glass { box-shadow:0 4px 14px #0003; border-radius:12px; }
body .ga-zone.ga-pile, body .ga-zone.ga-token-bank { border-radius:10px; width:96px; }
body #theirMemorySlot { width:96px; }
#chatToastHost { position:fixed !important; top:auto !important; bottom:52px !important; left:16px; z-index:9600; max-width:calc(100vw - 32px); }

@media (max-width:1024px) { #ga-action-area { width:170px; right:8px; } }
</style>
<script><?php readfile(__DIR__ . '/CleanUI.js'); ?></script>
