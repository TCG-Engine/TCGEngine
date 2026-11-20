<?php // Presentation only: retain engine card IDs, handlers, and nested attachments. ?>
<style>
#myField.ga-field-rows, #theirField.ga-field-rows {
    display: flex !important; flex-direction: column !important; gap: 8px !important;
    width: 100% !important; min-width: 0 !important; height: auto !important;
    align-items: stretch !important; padding: 4px !important; box-sizing: border-box;
}
#myFieldWrapper:has(.ga-field-rows), #theirFieldWrapper:has(.ga-field-rows) {
    overflow: auto !important; max-height: 100%; margin: 0 !important; padding: 0 !important;
}
/* Desktop fields share the space between the hands and the center strip.
   Explicit bounds prevent multi-row content from growing into the other board. */
#myFieldSlot.ga-field:has(.ga-field-rows), #theirFieldSlot.ga-field:has(.ga-field-rows) {
    min-height: 0 !important; height: auto !important; box-sizing: border-box;
    overflow: hidden !important; display: flex; flex-direction: column;
}
#theirFieldSlot.ga-field:has(.ga-field-rows) {
    top: 124px !important; bottom: calc(50% + 24px) !important;
}
#myFieldSlot.ga-field:has(.ga-field-rows) {
    top: calc(50% + 24px) !important; bottom: 124px !important;
}
#myFieldWrapper:has(.ga-field-rows), #theirFieldWrapper:has(.ga-field-rows) {
    flex: 1 1 auto; min-height: 0; scrollbar-width: thin;
    overscroll-behavior-y: contain;
}
#myFieldWrapper:has(.ga-field-rows)::-webkit-scrollbar,
#theirFieldWrapper:has(.ga-field-rows)::-webkit-scrollbar { display: block; width: 6px; }
.ga-field-row { flex-shrink: 0; min-width: 0; border-radius: 12px; background: #101c2466; border: 1px solid #c89b4626; }
.ga-field-row h3 { margin: 0; padding: 4px 10px; font: 600 10px/1.3 system-ui; color: #e4c783; letter-spacing: .06em; }
.ga-field-lane { display: flex; gap: 10px; overflow-x: auto; overflow-y: hidden; padding: 10px; scrollbar-width: thin; align-items: center; min-width: 0; }
.ga-field-lane:focus-visible { outline: 2px solid #e4c783; outline-offset: -2px; }
.ga-field-lane > [data-mzid], .ga-field-lane > span[id] {
    flex: 0 0 112px; width: 112px !important; height: 112px !important;
    position: relative !important; margin: 0 !important; display: flex !important; align-items: center; justify-content: center;
}
.ga-field-lane > span > a { display: block; width: 100% !important; height: 100% !important; margin: 0 !important; }
.ga-field-lane > span > a > img:not(.counter-image-icon) { width: 100% !important; height: 100% !important; object-fit: contain; }
#gaMobileRoot .ga-field-lane > span[id] { flex-basis: 88px; width: 88px !important; height: 88px !important; }
/* Two rows fit a typical laptop without reducing cards below 88px. */
#myField.ga-field-rows[data-rows="2"], #theirField.ga-field-rows[data-rows="2"] { gap: 4px !important; }
.ga-field-rows[data-rows="2"] h3 { padding: 2px 8px; }
.ga-field-rows[data-rows="2"] .ga-field-lane { padding: 2px; gap: 8px; }
.ga-field-rows[data-rows="2"] .ga-field-lane > span[id] {
    flex-basis: 88px; width: 88px !important; height: 88px !important;
}
.ga-field-lane:has(.ga-pinned-champion) { scroll-padding-left: 104px; }
.ga-field-lane > span.ga-pinned-champion {
    position: sticky !important; left: 0; z-index: 2; background: #13202b;
    border-radius: 8px;
}
.ga-field-lane > span.ga-group-start { margin-left: 14px !important; }
#theirField.ga-compact-opponent .ga-field-lane > span[id] {
    flex-basis: 64px; width: 64px !important; height: 64px !important;
}
#ga-field-display { color: #f4ecdb; background: #15252b; border: 1px solid #887344; border-radius: 12px; font: 13px system-ui; padding: 8px; }
#ga-field-display.ga-field-display-floating { position: fixed; left: 12px; top: 48px; z-index: 2800; }
#ga-field-display summary { cursor: pointer; min-height: 28px; }
#ga-field-display label { display: flex; align-items: center; gap: 8px; padding: 8px 0; }
#ga-field-display select { background: #15252b; color: #f4ecdb; padding: 6px; }
</style>
<script><?php readfile(__DIR__ . '/FieldRows.js'); ?></script>
