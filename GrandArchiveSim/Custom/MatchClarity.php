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
#selection-message { line-height: 1.35; }
@media (prefers-reduced-motion: reduce) {
    .ga-zone .selectable-card, .ga-zone .selectable-card img {
        animation: none !important;
        transition: none !important;
    }
}
</style>
