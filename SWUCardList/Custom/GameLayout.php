<?php
// Chamfered cyan-HUD skin for the standalone card browser (SWUCardList) — the page the
// SWUDeck MainMenu opens in an iframe. Mirrors the deck-builder's look (SWUDeck/Custom/
// GameLayout.php) so the "Cards" tab + "Filter cards..." bar match the rest of the site.
// Wired in via the schema's `Layout: /Custom/GameLayout.php` directive, which the code
// generator turns into an include in InitialLayout.php (regen-safe).
echo(<<<'HTML'
<style>
  /* Tabs (e.g. "Cards"): strip the rounded pill, draw the chamfer with two negative-z
     pseudos (::before = cyan rim, ::after = dark fill), matching the HUD buttons. */
  .panelTab {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important;
    padding: 4px 11px !important; margin: 2px 3px !important;
    color: rgba(205,238,255,0.96) !important; font-weight: 600 !important;
    text-transform: uppercase !important; letter-spacing: 0.05em !important;
    text-shadow: 0 0 5px rgba(120,200,255,0.35) !important;
    filter: drop-shadow(0 0 3px rgba(110,190,255,0.30)) !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
    cursor: pointer !important;
  }
  .panelTab::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px) !important;
    background: rgba(140,210,255,0.80) !important;
  }
  .panelTab::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px) !important;
    background: rgba(20,42,70,0.95) !important;
  }
  .panelTab:hover {
    color: #fff !important; filter: drop-shadow(0 0 8px rgba(125,205,255,0.6)) !important; transform: translateY(-1px) !important;
  }
  .panelTab:hover::before { background: rgba(180,228,255,1) !important; }
  .panelTab:active { transform: translateY(1px) !important; }
  .panelTab:active::after { background: rgba(12,26,46,0.98) !important; }

  /* Filter bar: flat cyan HUD field (was a rounded blue-bordered pill). */
  .filterBar {
    background: rgba(20,42,70,0.60) !important;
    color: rgba(222,240,255,0.95) !important;
    border: 1px solid rgba(140,210,255,0.45) !important;
    border-radius: 0 !important;
    font-family: Arial, Helvetica, sans-serif !important;
  }
  .filterBar::placeholder { color: rgba(160,195,225,0.60) !important; }
  .filterBar:focus {
    outline: none !important;
    border-color: rgba(160,215,255,0.85) !important;
    box-shadow: 0 0 6px rgba(120,200,255,0.35) !important;
  }

  /* Filter Legal / Filter Aspect — cyan HUD checkboxes + all-caps labels. */
  #legalFilterCheckbox, #customFilterCheckbox {
    -webkit-appearance: none !important; appearance: none !important;
    width: 16px !important; height: 16px !important; margin: 0 6px 0 0 !important; padding: 0 !important;
    background: rgba(20,42,70,0.9) !important; border: 1px solid rgba(140,210,255,0.6) !important;
    border-radius: 0 !important; cursor: pointer; position: relative; vertical-align: middle; flex-shrink: 0;
    transition: box-shadow 120ms, background 120ms;
  }
  #legalFilterCheckbox:hover, #customFilterCheckbox:hover { box-shadow: 0 0 6px rgba(120,200,255,0.45) !important; }
  #legalFilterCheckbox:checked, #customFilterCheckbox:checked {
    background: rgba(30,64,104,0.95) !important; box-shadow: 0 0 5px rgba(120,200,255,0.35) !important;
  }
  #legalFilterCheckbox:checked::after, #customFilterCheckbox:checked::after {
    content: '' !important; position: absolute; left: 4px; top: 1px; width: 5px; height: 9px;
    border: solid rgba(180,228,255,1); border-width: 0 2px 2px 0; transform: rotate(45deg);
  }
  label[for="legalFilterCheckbox"], label[for="customFilterCheckbox"] {
    color: rgba(205,238,255,0.92) !important; font-weight: 600 !important;
    font-family: Arial, Helvetica, sans-serif !important;
    text-transform: uppercase !important; letter-spacing: 0.04em !important;
    text-shadow: 0 0 5px rgba(120,200,255,0.30) !important;
  }

  /* Card grid — thin cyan HUD frame with a faint glow (matches the deck builder). */
  #my_CardPane_content {
    display: block !important; box-sizing: border-box !important; margin-top: 5px !important; padding: 5px !important;
    border: 2px solid rgba(150,215,255,0.75) !important;
    box-shadow: 0 0 16px rgba(120,200,255,0.5), inset 0 0 12px rgba(120,200,255,0.18) !important;
  }
</style>
HTML);
?>
