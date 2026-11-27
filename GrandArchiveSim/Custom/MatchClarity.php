<?php // Grand Archive-only presentation of engine-provided legal actions. ?>
<style>
.ga-zone .selectable-card {
    outline: 2px solid var(--highlight-color, #b7eb85);
    outline-offset: -2px;
    border-radius: 8px;
}
.ga-zone a:focus-visible, .ga-zone button:focus-visible {
    outline: 3px solid #fff0ae;
    outline-offset: 2px;
}
.ga-zone .combat-targeted-card { outline: 3px dashed #ffba87; }
.ga-zone .combat-attacker-card { outline: 3px solid #f4d279; }
/* Expressive controls retain the Classic ink, parchment and antique-gold palette.
   This include is GA-only; !important overrides shared engine inline presentation. */
:root {
    --ga-control-ink: #15252b;
    --ga-control-gold: #e4c783;
    --ga-control-text: #f4ecdb;
}
#selection-message:not(.ga-mobile-phase-prompt) {
    line-height: 1.4 !important;
    background: var(--ga-control-ink) !important;
    color: var(--ga-control-text) !important;
    border: 1px solid #887344 !important;
    border-radius: 20px !important;
    box-shadow: 0 8px 24px #0006 !important;
    padding: 12px 16px !important;
    max-width: calc(100vw - 24px) !important;
    box-sizing: border-box !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
}
#selection-message > span { min-width: 0; overflow-wrap: anywhere; }
#myHealth .widget-button-pass,
#selection-message > button {
    background: var(--ga-control-gold) !important;
    color: #211c12 !important;
    border: 1px solid #f5dda4 !important;
    border-radius: 18px !important;
    min-height: 44px;
    box-sizing: border-box;
    padding: 10px 18px !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    letter-spacing: .02em !important;
    text-transform: none !important;
    box-shadow: 0 3px 10px #0004 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    transition: border-radius 160ms ease, filter 160ms ease !important;
}
#myHealth .widget-button-pass:active,
#selection-message > button:active { border-radius: 10px !important; }
#myHealth .widget-button-pass:focus-visible,
#selection-message > button:focus-visible,
#ga-reveals > summary:focus-visible {
    outline: 3px solid #fff0ae;
    outline-offset: 3px;
}
#ga-reveals > summary {
    background: var(--ga-control-ink);
    color: var(--ga-control-text);
    border-color: #887344;
    border-radius: 16px;
    min-height: 44px;
    box-sizing: border-box;
}
#ga-reveals > #ga-reveals-panel {
    background: var(--ga-control-ink);
    border-color: #887344;
    border-radius: 20px;
    margin-top: 6px;
}
.ga-m-pass #myHealth .widget-button-pass { min-height: 40px; padding: 8px 12px !important; }
@media (max-width: 600px) {
    #selection-message:not(.ga-mobile-phase-prompt) { padding: 10px 12px !important; }
    #myHealth .widget-button-pass { padding: 8px 12px !important; min-height: 40px; }
}
@media (prefers-reduced-motion: reduce) {
    #myHealth .widget-button-pass, #selection-message > button {
        transition: none !important;
        transform: none !important;
    }
}
@media (prefers-reduced-motion: reduce) {
    .ga-zone .selectable-card, .ga-zone .selectable-card img {
        animation: none !important;
        transition: none !important;
    }
}
</style>
