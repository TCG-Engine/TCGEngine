<?php
// GameLayoutShared.php — behaviour shared by GameLayout.php (desktop/tablet) and
// GameLayoutMobile.php (phones). Pure JS that targets engine slot IDs, so both
// layouts reuse it verbatim. Included within InitialLayout.php scope so the PHP
// interpolations below ($playerID, pilot-leader list) resolve.
?>
<style>
/* ── Per-unit action glows ─────────────────────────────────────────────────────
   Applied by refreshUnitActionGlows() in this file's JS to ANY unit element with
   data-mzid (both layouts), so the CSS must live here in shared — it previously sat
   in desktop GameLayout.php only, so on mobile the class was added but never styled
   (token attackers in particular showed no green glow). */
.unit-action {   /* ready unit with an available Action ability (cyan) */
    box-shadow: 0 0 9px 3px #5fd0ff, inset 0 0 4px #5fd0ff;
    border-radius: 4px; cursor: pointer;
}
.can-attack {    /* ready unit with at least one valid attack target (green) */
    box-shadow: 0 0 9px 3px rgba(60,220,90,0.70), inset 0 0 4px rgba(60,220,90,0.55);
    border-radius: 4px; cursor: pointer;
}

/* ── Initiative token palette = turn-indicator palette ───────────────────────────
   Green when the initiative sits on MY side, red on the opponent's — matching the
   turn-edge glow (green rgba(64,214,110) / red rgba(222,72,72)). updateInitiative()
   adds .is-mine / .is-theirs; one --init-rgb recolors the cyan token in BOTH layouts.
   No class yet (state unset) → falls back to the layout's base cyan. */
.swu-init-control.is-mine   { --init-rgb: 64,214,110; }
.swu-init-control.is-theirs { --init-rgb: 222,72,72; }
.swu-init-control.is-mine .swu-init-hex,
.swu-init-control.is-theirs .swu-init-hex { background: rgba(var(--init-rgb), 0.50) !important; }
.swu-init-control.is-mine #swuInitHexText,
.swu-init-control.is-theirs #swuInitHexText {       /* the faded unclaimed rays */
    background:
        repeating-linear-gradient(0deg, transparent 0 0.3px, rgba(var(--init-rgb), 0.55) 1px, transparent 1.7px 3.5px),
        rgba(14,27,42,0.92) !important;
}
.swu-init-control.is-mine .swu-init-fill,
.swu-init-control.is-theirs .swu-init-fill {        /* the claimed fill that rises bottom→top */
    background: linear-gradient(160deg, rgba(var(--init-rgb), 0.96), rgba(var(--init-rgb), 0.78)) !important;
}
.swu-init-control.is-mine.is-takeable .swu-init-hex,
.swu-init-control.is-theirs.is-takeable .swu-init-hex,
.swu-init-control.is-mine.is-claimed .swu-init-hex,
.swu-init-control.is-theirs.is-claimed .swu-init-hex {   /* brighter rim + glow */
    background: rgba(var(--init-rgb), 0.93) !important;
    filter: drop-shadow(0 0 9px rgba(var(--init-rgb), 0.68)) !important;
}

/* ── Leader + base art fill (2P) ────────────────────────────────────────────────
   Goal: the leader/base art FILLS its cell, and an EXHAUSTED card may overflow (the engine
   tilts exhausted cards with transform:rotate on the card container, GameLayout notes the
   ~9° tilt). The earlier mask/blow-up approach was the wrong tool: any clip that contains
   the fill also clips that tilt. So instead we fill with object-fit:cover — it crops to
   fill the box INTERNALLY, so while the card is ready there is no overflow to clip — and we
   leave the wrappers overflow:visible (desktop already requires this for the wide card), so
   the tilt spills out freely instead of being cut. width:100% fills the cell horizontally;
   the box height stays the engine's cardSize. object-position is top-biased toward the art.
   Bases keep their damage counter (a separate centered element). 2P layouts; revisit for 4P. */
#myLeaderWrapper, #theirLeaderWrapper,
#myBaseWrapper,   #theirBaseWrapper { overflow: visible !important; }
/* Stretch + clip the card container (the data-mzid span). It's shrink-wrapped by default,
   so width:100% on the img alone does nothing — stretch it to fill the cell. We also clip
   HERE (overflow:hidden), not on the wrapper above: the span is the element the engine
   tilts (transform:rotate when exhausted), so the clip rotates WITH the card and never cuts
   the tilt, while the wrapper stays overflow:visible so the tilted card spills out freely.
   border-radius rounds the trimmed corners. */
#myLeaderWrapper [data-mzid], #theirLeaderWrapper [data-mzid],
#myBaseWrapper [data-mzid],   #theirBaseWrapper [data-mzid] {
    display: block !important; width: 100% !important;
    overflow: hidden !important; border-radius: 7px;
}
/* selectable-card draws its green highlight as a border on the IMG, which the span clip
   above would eat — re-emit it as the span's own box-shadow (not clipped by the span's own
   overflow), using the engine's dynamic --highlight-color (falls back to green). */
#myLeaderWrapper [data-mzid].selectable-card, #theirLeaderWrapper [data-mzid].selectable-card,
#myBaseWrapper [data-mzid].selectable-card,   #theirBaseWrapper [data-mzid].selectable-card {
    box-shadow: 0 0 12px 2px var(--highlight-color, #3cdc5a),
                inset 0 0 0 2px var(--highlight-color, #3cdc5a) !important;
}
#myLeaderWrapper img[data-orientation='landscape'],
#theirLeaderWrapper img[data-orientation='landscape'],
#myBaseWrapper img[data-orientation='landscape'],
#theirBaseWrapper img[data-orientation='landscape'] {
    width: 100% !important; height: auto !important; display: block !important;
    object-fit: cover !important;
    object-position: 50% 38% !important;
    /* Scale the art up so the black print edge is pushed past the span and clipped by it.
       object-view-box would trim at the source, but it's not honored in this browser.
       --border-trim is the zoom (1 = none); raise it if any black still peeks through. */
    --border-trim: 1.10;
    transform: scale(var(--border-trim));
    transform-origin: center;
    /* Shorten the container from the BOTTOM: a negative bottom margin pulls the span's
       bottom edge up by --bottom-chop, so the span clip trims that much off the bottom
       (object-position keeps the top art). */
    --bottom-chop: 8px;
    margin-bottom: calc(-1 * var(--bottom-chop)) !important;
}
/* The engine's exhausted darkening layer sits 2px inset (its -ovr parent is calc(100%-4px),
   top/left 2px). That was hidden by the card's own dark print border before — but now that
   we trim the border and fill edge-to-edge, the inset leaves a bright ring of art uncovered,
   obvious on a tilted exhausted leader. Expand the layer to cover the full card (it rotates
   with the card already) and match our corner radius. */
#myLeaderWrapper .exhausted-status-overlay-layer,
#theirLeaderWrapper .exhausted-status-overlay-layer,
#myBaseWrapper .exhausted-status-overlay-layer,
#theirBaseWrapper .exhausted-status-overlay-layer {
    inset: -3px !important; width: auto !important; height: auto !important;
    border-radius: 7px !important;
}

/* ── SWUSim HUD button sweep ───────────────────────────────────────────────────
   Restyle the engine's decision-queue buttons (MZChoosee / MZMultiChoose /
   MZSplitAssign) to the SWUSim chamfered cyan HUD look. These classes are in shared
   Core/*.js, so we override here (this file loads only for SWUSim → SWUSim-scoped),
   with !important to win the runtime cascade. Core buttons have no <span> wrapper,
   so the CLOSED chamfered border is drawn by two negative-z pseudos: ::before = cyan
   rim (full chamfer), ::after = flat fill (inset, slightly smaller chamfer). The
   button's text is normal content (z 0) so it stays above; `filter` gives the edge
   glow that follows the chamfered silhouette. */
.mzmodal-submit-btn, .mzmulti-btn, .mzsplit-submit-btn,
.mzsplit-btn-minus, .mzsplit-btn-plus {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important; clip-path: none !important;
    text-transform: uppercase !important; letter-spacing: 0.10em !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
.mzmodal-submit-btn::before, .mzmulti-btn::before,
.mzsplit-submit-btn::before, .mzsplit-btn-minus::before, .mzsplit-btn-plus::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
    background: rgba(140,210,255,0.85) !important;   /* the closed cyan rim */
}
.mzmodal-submit-btn::after, .mzmulti-btn::after,
.mzsplit-submit-btn::after, .mzsplit-btn-minus::after, .mzsplit-btn-plus::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
    background: rgba(20,42,70,0.95) !important;       /* flat fill */
}
/* Primary / confirm — bright text + cyan glow */
.mzmodal-submit-btn:not(:disabled), .mzmulti-btn-primary:not(:disabled), .mzsplit-submit-btn:not(:disabled) {
    color: rgba(205,238,255,0.98) !important; text-shadow: 0 0 6px rgba(120,200,255,0.5) !important;
    filter: drop-shadow(0 0 5px rgba(110,190,255,0.45)) !important;
}
.mzmodal-submit-btn:not(:disabled):hover, .mzmulti-btn-primary:not(:disabled):hover, .mzsplit-submit-btn:not(:disabled):hover {
    color: #fff !important; filter: drop-shadow(0 0 10px rgba(125,205,255,0.65)) !important; transform: translateY(-1px) !important;
}
.mzmodal-submit-btn:not(:disabled):hover::before, .mzmulti-btn-primary:not(:disabled):hover::before, .mzsplit-submit-btn:not(:disabled):hover::before {
    background: rgba(180,228,255,1) !important;
}
/* Secondary / cancel-pass — dimmer rim + darker fill */
.mzmulti-btn-secondary { color: rgba(175,212,242,0.92) !important; filter: drop-shadow(0 0 3px rgba(90,170,255,0.22)) !important; }
.mzmulti-btn-secondary::before { background: rgba(120,200,255,0.50) !important; }
.mzmulti-btn-secondary::after  { background: rgba(14,26,44,0.92) !important; }
.mzmulti-btn-secondary:hover:not(:disabled) { color: #eaf6ff !important; }
.mzmulti-btn-secondary:hover:not(:disabled)::before { background: rgba(150,215,255,0.80) !important; }
/* Disabled */
.mzmodal-submit-btn:disabled, .mzmulti-btn:disabled, .mzsplit-submit-btn:disabled {
    opacity: 0.4 !important; filter: none !important; transform: none !important; color: rgba(200,225,245,0.7) !important;
}
.mzmodal-submit-btn:disabled::before, .mzmulti-btn:disabled::before, .mzsplit-submit-btn:disabled::before {
    background: rgba(120,200,255,0.30) !important;
}
/* +/- steppers — closed cyan rim, faint red/green glow + fill to keep the +/- semantics */
.mzsplit-btn-minus::before, .mzsplit-btn-plus::before {
    clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px) !important;
}
.mzsplit-btn-minus::after, .mzsplit-btn-plus::after {
    clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px) !important;
}
.mzsplit-btn-minus { color: #eaf6ff !important; filter: drop-shadow(0 0 4px rgba(230,95,95,0.45)) !important; }
.mzsplit-btn-plus  { color: #eaf6ff !important; filter: drop-shadow(0 0 4px rgba(95,210,120,0.45)) !important; }
.mzsplit-btn-minus::after { background: rgba(58,24,30,0.92) !important; }
.mzsplit-btn-plus::after  { background: rgba(20,48,30,0.92) !important; }
.mzsplit-btn-minus:hover { filter: drop-shadow(0 0 8px rgba(230,95,95,0.65)) !important; }
.mzsplit-btn-plus:hover  { filter: drop-shadow(0 0 8px rgba(95,210,120,0.65)) !important; }

/* Inline MultiChoose bar (UILibraries StyleInlineMultiActionButton) — Select All /
   Deselect All / Confirm. These are ID'd buttons with INLINE JS styles (no class),
   so override by ID with !important (beats inline non-important). Same closed-chamfer
   two-pseudo HUD treatment as the MZ buttons above. */
#inline-multi-select-all, #inline-multi-clear-all, #inline-multi-confirm {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important;
    text-transform: uppercase !important; letter-spacing: 0.10em !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
#inline-multi-select-all::before, #inline-multi-clear-all::before, #inline-multi-confirm::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
    background: rgba(140,210,255,0.85) !important;
}
#inline-multi-select-all::after, #inline-multi-clear-all::after, #inline-multi-confirm::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
    background: rgba(20,42,70,0.95) !important;
}
/* Confirm = primary (bright rim + glow) */
#inline-multi-confirm {
    color: rgba(205,238,255,0.98) !important; text-shadow: 0 0 6px rgba(120,200,255,0.5) !important;
    filter: drop-shadow(0 0 5px rgba(110,190,255,0.45)) !important;
}
#inline-multi-confirm:hover { color: #fff !important; filter: drop-shadow(0 0 10px rgba(125,205,255,0.65)) !important; transform: translateY(-1px) !important; }
#inline-multi-confirm:hover::before { background: rgba(180,228,255,1) !important; }
/* Select All / Deselect All = secondary (dimmer rim) */
#inline-multi-select-all, #inline-multi-clear-all { color: rgba(190,222,248,0.95) !important; filter: drop-shadow(0 0 3px rgba(90,170,255,0.25)) !important; }
#inline-multi-select-all::before, #inline-multi-clear-all::before { background: rgba(120,200,255,0.55) !important; }
#inline-multi-select-all:hover, #inline-multi-clear-all:hover { color: #eaf6ff !important; transform: translateY(-1px) !important; }
#inline-multi-select-all:hover::before, #inline-multi-clear-all:hover::before { background: rgba(150,215,255,0.82) !important; }
/* Disabled */
#inline-multi-confirm:disabled, #inline-multi-select-all:disabled, #inline-multi-clear-all:disabled {
    opacity: 0.4 !important; filter: none !important; transform: none !important;
}
/* Message gets its own first line; the "N selected / M max" counter + controls drop
   to a second line (msgSpan is the panel's first child). */
#selection-message:has(#inline-multi-confirm) > span:first-child { flex-basis: 100% !important; }
#selection-message:has(#inline-multi-confirm) #inline-multi-counter { margin-top: 2px !important; }

/* ── End-game menu buttons → SWUSim chamfered HUD look ──────────────────────────
   ShowGameOver() (shared Core JS) renders the end-game buttons as plain <button>s in
   #game-over-buttons, plus the corner-fallback #game-over-menu-btn. Both are class-less,
   so re-skin them here (SWUSim-scoped file) with the same closed-chamfer two-pseudo HUD
   treatment as the MZ buttons above: ::before = cyan rim, ::after = flat fill, text on top. */
#game-over-buttons button, #game-over-menu-btn {
    z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important;
    box-shadow: none !important; clip-path: none !important;
    padding: 10px 22px !important; font-weight: 700 !important; font-size: 13px !important;
    text-transform: uppercase !important; letter-spacing: 0.10em !important; cursor: pointer !important;
    color: rgba(205,238,255,0.98) !important; text-shadow: 0 0 6px rgba(120,200,255,0.5) !important;
    filter: drop-shadow(0 0 5px rgba(110,190,255,0.45)) !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
/* Row buttons are static by default → make them a containing block for their pseudos.
   #game-over-menu-btn keeps its shared position:absolute (already a containing block). */
#game-over-buttons button { position: relative !important; }
#game-over-buttons button::before, #game-over-menu-btn::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
    background: rgba(140,210,255,0.85) !important;   /* closed cyan rim */
}
#game-over-buttons button::after, #game-over-menu-btn::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
    background: rgba(20,42,70,0.95) !important;       /* flat fill */
}
#game-over-buttons button:not(:disabled):hover, #game-over-menu-btn:not(:disabled):hover {
    color: #fff !important; filter: drop-shadow(0 0 10px rgba(125,205,255,0.65)) !important; transform: translateY(-1px) !important;
}
#game-over-buttons button:not(:disabled):hover::before, #game-over-menu-btn:not(:disabled):hover::before {
    background: rgba(180,228,255,1) !important;
}
#game-over-buttons button:not(:disabled):active, #game-over-menu-btn:not(:disabled):active {
    transform: translateY(1px) !important; filter: drop-shadow(0 0 4px rgba(110,190,255,0.4)) !important;
}
/* Disabled (e.g. the "Waiting on opponent to confirm…" convert button) — dimmed, inert. */
#game-over-buttons button:disabled, #game-over-menu-btn:disabled {
    opacity: 0.4 !important; filter: none !important; transform: none !important; cursor: default !important;
    color: rgba(200,225,245,0.7) !important;
}
#game-over-buttons button:disabled::before, #game-over-menu-btn:disabled::before {
    background: rgba(120,200,255,0.30) !important;
}

/* ── End-game overlay → 80% floating split panel (SWUSim only) ──────────────────
   Shrink the shared full-screen game-over overlay to an 80%×80% centered panel so
   the game board + chat stay visible AND interactive in the surrounding margin
   (post-game review/chat). Inside, lay it out as a grid: the big "YOU WON!" title
   spans the top, the action buttons stack vertically on the LEFT, and the stats
   tables fill the RIGHT half (scrolling within their cell). All ID overrides need
   !important to beat the shared ScreenAnimations.css #game-over-* rules. */
#game-over-overlay {
    inset: 10vh 20vw 10vh 10vw !important;  /* desktop: 80% tall, shifted left — 10% left / 30% right margin */
    width: auto !important; height: auto !important;
    padding: 16px 22px 20px !important;
    background: rgba(8,15,25,0.77) !important;
    border: 1px solid rgba(140,210,255,0.35) !important;
    border-radius: 16px !important;
    box-shadow: 0 24px 80px rgba(0,0,0,0.65), 0 0 0 1px rgba(0,0,0,0.4) !important;
    backdrop-filter: blur(4px) !important; -webkit-backdrop-filter: blur(4px) !important;
    overflow: hidden !important;           /* the stats pane scrolls, not the panel */
    grid-template-columns: minmax(190px, 290px) minmax(0, 1fr) !important;
    grid-template-rows: auto minmax(0, 1fr) !important;
    grid-template-areas: "title title" "buttons stats" !important;
    column-gap: 24px !important; row-gap: 12px !important;
    align-items: stretch !important; justify-items: stretch !important;
}
#game-over-overlay.active { display: grid !important; }  /* shared sets display:flex */

#game-over-title {
    grid-area: title !important;
    font-size: clamp(30px, 4.4vw, 64px) !important;
    letter-spacing: 4px !important;
    margin: 0 0 4px !important;
    align-self: center !important;
}
/* SWUSim win/lose title colors (override the shared gold/red). "You Won!" uses the primary
   button text color + a cyan HUD glow; "You Lost" is a darker, less-saturated muted red. */
#game-over-overlay.won #game-over-title {
    color: rgba(205,238,255,0.98) !important;
    text-shadow: 0 0 30px rgba(140,210,255,0.85), 0 0 80px rgba(120,200,255,0.50), 0 4px 12px rgba(0,0,0,0.8) !important;
}
#game-over-overlay.lost #game-over-title {
    color: #9b3e3e !important;
    text-shadow: 0 0 26px rgba(150,62,62,0.58), 0 0 60px rgba(120,42,42,0.30), 0 4px 12px rgba(0,0,0,0.8) !important;
}

#game-over-stats {
    grid-area: stats !important;
    width: auto !important; max-width: none !important;
    height: 100% !important; min-height: 0 !important; max-height: 100% !important;
    margin: 0 !important; overflow-y: auto !important;
}

/* Buttons: vertical stack on the left. Full-width by default (each on its own row);
   the Rematch + Best-of toggle are flex:1 so they SHARE one row (Best-of sits to the
   right of Rematch). */
#game-over-buttons {
    grid-area: buttons !important;
    flex-direction: row !important; flex-wrap: wrap !important;
    align-content: flex-start !important; justify-content: flex-start !important;
    gap: 10px !important; margin: 0 !important; align-self: stretch !important;
}
#game-over-buttons button { flex: 0 0 100% !important; }
/* Rematch fills the row; the short Bo1/Bo3 toggle sits compact to its right. */
#game-over-buttons #swu-rematch-btn { flex: 1 1 auto !important; }
#game-over-buttons #swu-bestof-btn  { flex: 0 0 auto !important; }

/* Best-of toggle → WHITE button, black text (SWUSim secondary-button style). MUST be
   scoped under #game-over-buttons: the general `#game-over-buttons button::before/::after`
   rules carry an extra element term, so a bare `#swu-bestof-btn::before` (fewer specificity
   terms) loses even with !important. The double-id selector here outranks them. */
#game-over-buttons #swu-bestof-btn {
    color: #3a3a3a !important; text-shadow: none !important; text-transform: none !important;
    filter: drop-shadow(0 0 6px rgba(120,200,255,0.55)) !important;   /* cyan HUD glow */
}
/* Cool sci-fi border: a glowing cyan chamfered rim around the off-white fill (the off-white
   ::after is inset a touch more so the cyan edge reads as a crisp ~2.5px HUD keyline). */
#game-over-buttons #swu-bestof-btn::before { background: rgba(130,205,255,0.95) !important; }   /* cyan border */
#game-over-buttons #swu-bestof-btn::after  {
    background: #dde2e9 !important; inset: 2.5px !important;                                     /* off-white fill */
    clip-path: polygon(5.5px 0, 100% 0, 100% calc(100% - 5.5px), calc(100% - 5.5px) 100%, 0 100%, 0 5.5px) !important;
}
#game-over-buttons #swu-bestof-btn:not(:disabled):hover {
    color: #1f1f1f !important; filter: drop-shadow(0 0 12px rgba(150,215,255,0.85)) !important;
}
#game-over-buttons #swu-bestof-btn:not(:disabled):hover::before { background: rgba(180,228,255,1) !important; }
#game-over-buttons #swu-bestof-btn:not(:disabled):hover::after  { background: #e9edf2 !important; }

/* Mobile / portrait → the 2-column split squeezes the stats into an unreadable sliver,
   so collapse to ONE column: title, then the buttons, then the stats below (scrolling). */
@media (orientation: portrait), (max-width: 760px) {
    #game-over-overlay {
        inset: 10vh 10vw !important;          /* mobile: keep the panel centered (80% wide) */
        grid-template-columns: minmax(0, 1fr) !important;
        grid-template-rows: auto auto minmax(0, 1fr) !important;
        grid-template-areas: "title" "buttons" "stats" !important;
        row-gap: 10px !important;
    }
    #game-over-buttons { align-self: start !important; }
    /* Compact the win-screen content to ~75% on phones (zoom on the fixed panel breaks
       its inset sizing, so scale the content metrics down instead — panel stays 80%).
       The stats tables carry INLINE font-size/padding (StatsSubmit.php), so target the
       table/cells directly with !important to beat them. */
    #game-over-title { font-size: clamp(22px, 3.3vw, 48px) !important; letter-spacing: 3px !important; }
    #game-over-buttons button { font-size: 10px !important; padding: 7px 16px !important; }
    #game-over-stats { font-size: 11px !important; padding: 12px 13px !important; }
    #game-over-stats table { font-size: 10px !important; }
    #game-over-stats th, #game-over-stats td { padding: 2px 5px !important; }
    /* Truncate long card names (first column) to ~12 chars + ellipsis so the table stays compact;
       tapping a truncated cell reveals the full name in a .swu-stat-tip bubble (wired in JS below). */
    #game-over-stats td:first-child, #game-over-stats th:first-child {
        max-width: 12ch !important; white-space: nowrap !important;
        overflow: hidden !important; text-overflow: ellipsis !important;
    }
    #game-over-stats td:first-child { cursor: pointer !important; }
}

/* Tap-to-reveal bubble for a truncated card name (mobile end-game stats). HUD look:
   navy fill + cyan border. Dismissed by the next tap anywhere (handled in JS). */
.swu-stat-tip {
    position: fixed; z-index: 10001; max-width: 70vw;
    padding: 8px 11px; border-radius: 8px;
    background: rgba(8,15,25,0.97);
    border: 1px solid rgba(130,205,255,0.85);
    box-shadow: 0 6px 22px rgba(0,0,0,0.55), 0 0 10px rgba(120,200,255,0.35);
    color: #e8f4ff; font-size: 13px; font-weight: 600; line-height: 1.25;
    animation: swuStatTipIn 120ms ease-out;
}
@keyframes swuStatTipIn { from { opacity: 0; transform: translateY(-3px); } to { opacity: 1; transform: none; } }

/* ── Decision-queue picker sweep → SWUSim HUD treatment ─────────────────────────
   The live decision UIs are UILibraries/OptionChooseUI overlays whose buttons are
   styled INLINE (no class) or by their own class. Target each by overlay id (the
   panel is the only child div; cards are <img>, so the only <button>s are actions)
   or by class (.optchoose-*). External !important beats the inline styles. Covers:
     • #topdecksearch-panel  — TOPDECKSEARCH "Take N cards" (SOR_042 Search Your Feelings)
     • #scry-panel           — SCRY "LOOK AT THE TOP" Top/Bottom
     • #revealarrange-panel  — REVEALARRANGE Top/Discard
     • #yesno-decision-modal — YESNO Yes/No
     • .optchoose-*          — OPTIONCHOOSE (Annihilator JTL_041 deck/hand reveal OK,
                               SOR_221 Ground/Space, etc.)
   #mzchoose-popup is intentionally EXCLUDED — it has a minimize button + card buttons
   that the dark fill would cover; it needs scoped handling. */
/* Panels → cyan HUD frame + corner brackets (matches the arena frames). */
#topdecksearch-panel > div, #scry-panel > div, #revealarrange-panel > div,
#yesno-decision-modal > div, .optchoose-banner {
    position: relative !important;
    border: 1px solid rgba(120,200,255,0.30) !important; border-radius: 6px !important;
    box-shadow: 0 0 10px rgba(80,170,255,0.18), inset 0 0 26px rgba(80,170,255,0.06), 0 14px 44px rgba(0,0,0,0.6) !important;
}
#topdecksearch-panel > div::before, #scry-panel > div::before, #revealarrange-panel > div::before,
#yesno-decision-modal > div::before, .optchoose-banner::before {
    content: '' !important; position: absolute !important; inset: -1px !important; pointer-events: none !important;
    background:
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  top    / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  top    / 2px  22px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right top    / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right top    / 2px  22px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  bottom / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  bottom / 2px  22px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right bottom / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right bottom / 2px  22px no-repeat !important;
    filter: drop-shadow(0 0 4px rgba(150,215,255,0.55)) !important;
}
/* The OPTIONCHOOSE banner (peek hand / peek deck reveal) is itself the fixed element
   (no overlay wrapper, unlike the other panels), so the combined rule's
   position:relative would break it. Keep it fixed and CENTER it on screen — both
   horizontally and vertically — instead of bottom-anchored. fixed also establishes
   the containing block its ::before corner brackets need. */
.optchoose-banner {
    position: fixed !important;
    top: 50% !important; bottom: auto !important; left: 50% !important;
    transform: translate(-50%, -50%) !important;
}
/* Action buttons → chamfered cyan HUD (closed two-pseudo border). */
#topdecksearch-panel button, #scry-panel button, #revealarrange-panel button,
#yesno-decision-modal button, .optchoose-btn {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important;
    color: rgba(205,238,255,0.98) !important; text-transform: uppercase !important; letter-spacing: 0.12em !important;
    text-shadow: 0 0 6px rgba(120,200,255,0.5) !important;
    filter: drop-shadow(0 0 5px rgba(110,190,255,0.45)) !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
#topdecksearch-panel button::before, #scry-panel button::before, #revealarrange-panel button::before,
#yesno-decision-modal button::before, .optchoose-btn::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
    background: rgba(140,210,255,0.85) !important;
}
#topdecksearch-panel button::after, #scry-panel button::after, #revealarrange-panel button::after,
#yesno-decision-modal button::after, .optchoose-btn::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
    background: rgba(20,42,70,0.95) !important;
}
#topdecksearch-panel button:hover, #scry-panel button:hover, #revealarrange-panel button:hover,
#yesno-decision-modal button:hover, .optchoose-btn:hover {
    color: #fff !important; filter: drop-shadow(0 0 10px rgba(125,205,255,0.65)) !important; transform: translateY(-1px) !important;
}
#topdecksearch-panel button:hover::before, #scry-panel button:hover::before, #revealarrange-panel button:hover::before,
#yesno-decision-modal button:hover::before, .optchoose-btn:hover::before {
    background: rgba(180,228,255,1) !important;
}

/* ── Mulligan opening-hand preview ─────────────────────────────────────────────
   The mulligan YESNO modal (#yesno-decision-modal) is a fixed full-screen overlay
   that blocks scrolling to the real board, so on mobile the player can't see the
   hand they're deciding whether to mulligan. The wrapper in JS (below) injects the
   freshly-drawn hand as a thumbnail row ABOVE the prompt. The row wraps and is
   scroll-capped so it never pushes the YES/NO buttons off a short mobile viewport. */
.swu-mulligan-hand {
    display: flex !important; flex-wrap: wrap !important;
    justify-content: center !important; align-items: center !important;
    gap: 6px !important; margin: 0 0 18px 0 !important;
    max-width: min(86vw, 560px) !important;
    max-height: 46vh !important; overflow-y: auto !important; overflow-x: hidden !important;
}
.swu-mulligan-hand img {
    height: 124px !important; width: auto !important; border-radius: 5px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.6) !important;
}
@media (orientation: portrait), (max-width: 760px) {
    .swu-mulligan-hand img { height: 92px !important; }
    .swu-mulligan-hand { gap: 4px !important; margin-bottom: 12px !important; }
}

/* ── Decision-queue sweep, part 2 — the remaining modules ──────────────────────
   NUMBERCHOOSE (.numchoose-*), TWOSIDEDSLIDER (.twosided-slider-*), MZMODAL panel
   (.mzmodal-panel; its .mzmodal-submit-btn is styled above), MZREARRANGE
   (.mzrearrange-*), NAMECARD (.namecard-modal), and the single-target MZCHOOSE
   "Pass" button (#selection-message's id-less <button>). Deferred: IconChoice icon
   buttons and #mzchoose-popup — they contain <img>s the dark fill would cover. */
/* Panels in flex-centered overlays (relative-safe). */
.twosided-slider-panel, .mzmodal-panel, .mzrearrange-modal, .namecard-modal {
    position: relative !important;
    border: 1px solid rgba(120,200,255,0.30) !important; border-radius: 6px !important;
    box-shadow: 0 0 10px rgba(80,170,255,0.18), inset 0 0 26px rgba(80,170,255,0.06), 0 14px 44px rgba(0,0,0,0.6) !important;
}
/* NUMBERCHOOSE is a fixed bottom bar (like the OPTIONCHOOSE banner) — frame it in
   place; do NOT force position:relative (that would drop it out of fixed). */
.numchoose-banner {
    border: 1px solid rgba(120,200,255,0.30) !important; border-radius: 6px !important;
    box-shadow: 0 0 10px rgba(80,170,255,0.18), inset 0 0 26px rgba(80,170,255,0.06), 0 4px 24px rgba(0,0,0,0.5) !important;
}
.twosided-slider-panel::before, .mzmodal-panel::before, .mzrearrange-modal::before,
.namecard-modal::before, .numchoose-banner::before {
    content: '' !important; position: absolute !important; inset: -1px !important; pointer-events: none !important;
    background:
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  top    / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  top    / 2px  22px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right top    / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right top    / 2px  22px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  bottom / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) left  bottom / 2px  22px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right bottom / 22px 2px no-repeat,
        linear-gradient(rgba(150,215,255,0.85),rgba(150,215,255,0.85)) right bottom / 2px  22px no-repeat !important;
    filter: drop-shadow(0 0 4px rgba(150,215,255,0.55)) !important;
}
/* Action / confirm / stepper buttons → chamfered cyan HUD (closed two-pseudo). */
.numchoose-confirm, .numchoose-btn-minus, .numchoose-btn-plus, .twosided-slider-confirm,
.mzrearrange-btn-submit, .mzrearrange-btn-reset, .namecard-modal button,
#selection-message > button:not([id]) {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important;
    color: rgba(205,238,255,0.98) !important; text-transform: uppercase !important; letter-spacing: 0.12em !important;
    text-shadow: 0 0 6px rgba(120,200,255,0.5) !important;
    filter: drop-shadow(0 0 5px rgba(110,190,255,0.45)) !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
.numchoose-confirm::before, .numchoose-btn-minus::before, .numchoose-btn-plus::before, .twosided-slider-confirm::before,
.mzrearrange-btn-submit::before, .mzrearrange-btn-reset::before, .namecard-modal button::before,
#selection-message > button:not([id])::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
    background: rgba(140,210,255,0.85) !important;
}
.numchoose-confirm::after, .numchoose-btn-minus::after, .numchoose-btn-plus::after, .twosided-slider-confirm::after,
.mzrearrange-btn-submit::after, .mzrearrange-btn-reset::after, .namecard-modal button::after,
#selection-message > button:not([id])::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
    background: rgba(20,42,70,0.95) !important;
}
.numchoose-confirm:hover, .numchoose-btn-minus:hover, .numchoose-btn-plus:hover, .twosided-slider-confirm:hover,
.mzrearrange-btn-submit:hover, .mzrearrange-btn-reset:hover, .namecard-modal button:hover,
#selection-message > button:not([id]):hover {
    color: #fff !important; filter: drop-shadow(0 0 10px rgba(125,205,255,0.65)) !important; transform: translateY(-1px) !important;
}
.numchoose-confirm:hover::before, .numchoose-btn-minus:hover::before, .numchoose-btn-plus:hover::before, .twosided-slider-confirm:hover::before,
.mzrearrange-btn-submit:hover::before, .mzrearrange-btn-reset:hover::before, .namecard-modal button:hover::before,
#selection-message > button:not([id]):hover::before {
    background: rgba(180,228,255,1) !important;
}
</style>
<script>
window.SWU_PILOT_LEADERS = <?php echo json_encode([
    'JTL_001','JTL_003','JTL_006','JTL_008','JTL_009',
    'JTL_011','JTL_012','JTL_015','JTL_017','JTL_018'
]); ?>;
</script>
<script>
(function (MY_PLAYER_ID) {
    'use strict';

    // ── Phase track ────────────────────────────────────────────────────────────
    var PHASE_ALIASES = {
        APS:'APS', ACTIONPHASESTART:'APS', MAIN:'MAIN',
        RGS:'RGS', REGROUPSTART:'RGS', DRAW:'DRAW', DRAWPHASE:'DRAW',
        RES:'RES', RESOURCEPHASE:'RES', READY:'READY', READYPHASE:'READY'
    };
    function normalizePhase(raw) {
        return PHASE_ALIASES[(raw||'').toString().trim().toUpperCase().replace(/[^A-Z0-9]/g,'')] || '';
    }
    function updatePhaseTrack() {
        var bar = document.getElementById('swuMidbar'); if (!bar) return;
        var norm = normalizePhase(typeof window.CurrentPhaseData === 'string' ? window.CurrentPhaseData : '');
        bar.querySelectorAll('[data-phase-step]').forEach(function(el) {
            el.classList.toggle('is-active', el.getAttribute('data-phase-step') === norm);
        });
    }

    // ── Round counter ─────────────────────────────────────────────────────────
    function updateRound() {
        var el = document.getElementById('swuRoundNumber'); if (!el) return;
        var n = parseInt(window.TurnNumberData, 10);
        el.textContent = isNaN(n) ? '—' : n;
    }

    // ── Initiative ────────────────────────────────────────────────────────────
    // The hex button lives on whichever side currently controls the initiative
    // counter. InitiativeCounterData encodes that as P<n>_UNCLAIMED (the holder
    // still has it "available" this round) or P<n>_CLAIMED (that player took it
    // this round — it has moved to its "taken" side). CR 2.2.2 / 4.7.
    function updateInitiative() {
        var ctrl = document.getElementById('swuInitControl');
        var txt  = document.getElementById('swuInitHexText');
        if (!ctrl || !txt) return;

        var state = typeof window.InitiativeCounterData === 'string'
            ? window.InitiativeCounterData.trim() : '';
        var pn      = parseInt(state.charAt(1), 10);          // 1 or 2 (NaN if unset)
        var claimed = /_CLAIMED$/.test(state);
        var sideIsMe = (pn === MY_PLAYER_ID);                 // controller's side = the button's side

        // Palette like the turn indicator: green on my side, red on theirs (cyan if unset).
        var hasSide = !isNaN(pn);
        ctrl.classList.toggle('is-mine',   hasSide && sideIsMe);
        ctrl.classList.toggle('is-theirs', hasSide && !sideIsMe);

        var isMyTurn    = (String(window.TurnPlayerData||'').trim() === String(MY_PLAYER_ID));
        var isMainPhase = (String(window.CurrentPhaseData||'').trim() === 'MAIN');
        var canAct      = isMyTurn && isMainPhase;
        var canTake     = canAct && !claimed;                 // "Take the Initiative" action legal

        // The word is static ("Initiative", in the markup); state is shown by the fill
        // (claimed = cyan, rising bottom→top) and a fade when initiative changes sides.
        var targetBand = document.getElementById(sideIsMe ? 'swuMyControlBand' : 'swuTheirControlBand');
        var prevState  = ctrl.dataset.initState || '';
        var firstRun   = (prevState === '');
        ctrl.dataset.initState = state;

        function applyInitState() {
            ctrl.classList.toggle('is-claimed', claimed);   // fill scaleY(0→1) via CSS transition
            ctrl.classList.toggle('is-takeable', canTake);
        }

        var inBand    = ctrl.parentNode && (ctrl.parentNode.id === 'swuMyControlBand' || ctrl.parentNode.id === 'swuTheirControlBand');
        var sideMoved = !firstRun && inBand && ctrl.parentNode !== targetBand;

        if (sideMoved && targetBand && ctrl.dataset.initAnimating !== '1') {
            // TAKE — initiative switched sides: fade out on the old side, reparent while
            // invisible, fade IN UNFILLED on the new side, THEN let the fill rise (adding
            // is-claimed in the same frame as the reparent skips the fill transition).
            ctrl.dataset.initAnimating = '1';
            ctrl.classList.add('is-leaving');
            setTimeout(function () {
                targetBand.insertBefore(ctrl, targetBand.firstChild);
                ctrl.classList.remove('is-leaving', 'is-claimed');   // settle on new side, unfilled
                ctrl.classList.add('is-entering');
                ctrl.classList.toggle('is-takeable', canTake);
                setTimeout(function () {
                    ctrl.classList.remove('is-entering');
                    ctrl.classList.toggle('is-claimed', claimed);    // fill rises after the fade-in
                    ctrl.dataset.initAnimating = '0';
                }, 340);
            }, 200);
        } else if (ctrl.dataset.initAnimating !== '1') {
            // KEEP (claim, same side → fill rises) or initial placement.
            if (targetBand && ctrl.parentNode !== targetBand) targetBand.insertBefore(ctrl, targetBand.firstChild);
            applyInitState();
        }

        // Pass button (my side only) — live whenever it's my turn in MAIN (CR 4.7).
        var passCtrl = document.getElementById('swuPassControl');
        if (passCtrl) passCtrl.classList.toggle('is-idle', !canAct);

        // Take/Keep Initiative button(s) live in MY controls (desktop: beside Pass; mobile:
        // a prompt bar above the footer). The action is the same engine input wherever the
        // token sits — so this decouples it from the badge's location. The BUTTON shows while
        // the action is live (my MAIN phase, unclaimed) OR once initiative is claimed this round
        // — staying greyed & inert ("Initiative Claimed") for the rest of the round on BOTH
        // sides, including the claimer (who passes out the round, so canAct goes false). Label
        // reflects intent: KEEP if I already hold the unclaimed token, TAKE if the opponent does.
        // The hotkey HINT stays visible alongside the button (even greyed/claimed) — the "I" key
        // is just gated to a no-op while it isn't live, so the hint stays as a reminder.
        var verb = sideIsMe ? 'Keep' : 'Take';
        document.querySelectorAll('.swu-take-init').forEach(function (el) {
            el.hidden = !(canAct || claimed);
            el.classList.toggle('is-taken', claimed);
            var lbl = el.querySelector('span');
            if (lbl) lbl.textContent = claimed ? 'Initiative Claimed' : (verb + ' Initiative');
        });

        // Token is a status badge now — hover tooltip explains who holds it / what taking does.
        var hex = document.getElementById('swuInitHex');
        if (hex) {
            var who = sideIsMe ? 'You have' : 'Opponent has';
            hex.title = 'Initiative: ' + who + ' it' + (claimed ? ' (claimed this round)' : '')
                + '. Taking the initiative means you act first next round and pass for the rest of this one.';
        }
    }

    window.swuTakeInitiative = function () {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('InitiativeCounter-0!CustomInput!TakeInitiative'));
    };

    window.swuPassAction = function () {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('myHealth-0!CustomInput!Pass'));
    };

    // ── Resource counters ─────────────────────────────────────────────────────
    // Resources collapse to one DOM element (CollapseGroupBy CardID), so DOM counting
    // is unreliable. Parse the raw data string set by NextTurnRender instead.
    // Format: "cardID count json_with_underscores" separated by "<|>".
    // SWUSim convention: Status=1 means ready; Status=0 means exhausted.
    // Opponent cards have no JSON ("-").
    // Credit tokens (CR 3.13) sit in the resource zone but are NOT resources — they're counted
    // separately and excluded from ready/total. The only Credit token is LAW_T01.
    var SWU_CREDIT_TOKEN_ID = 'LAW_T01';
    function parseResCountFromData(rawData) {
        if (!rawData || rawData === '' || rawData === '-') return {ready:0, total:0, credits:0};
        var entries = rawData.split('<|>');
        var total = 0, exhausted = 0, credits = 0;
        for (var i = 0; i < entries.length; i++) {
            var entry = entries[i].trim();
            if (!entry) continue;
            var spaceIdx = entry.indexOf(' ');
            var cardId = spaceIdx >= 0 ? entry.substring(0, spaceIdx) : entry;
            if (cardId === SWU_CREDIT_TOKEN_ID) { credits++; continue; } // Credit token, not a resource
            total++;
            var rest = spaceIdx >= 0 ? entry.substring(spaceIdx + 1) : '';
            var spaceIdx2 = rest.indexOf(' ');
            var jsonPart = spaceIdx2 >= 0 ? rest.substring(spaceIdx2 + 1) : '-';
            if (jsonPart && jsonPart !== '-') {
                try {
                    var obj = JSON.parse(jsonPart.replace(/_/g, ' '));
                    if (obj && parseInt(obj.Status) === 0) exhausted++;
                } catch(e) {}
            }
        }
        return {ready: total - exhausted, total: total, credits: credits};
    }

    function updateResCounterFromData(dataVar, countElId) {
        var el = document.getElementById(countElId); if (!el) return;
        var raw = window[dataVar] || '';
        var c = parseResCountFromData(raw);
        var html = c.ready + '/' + c.total;
        // "+ N" in gold for Credit tokens — only shown when the player has 1+. Hover shows the
        // Credit token card preview (so an opponent can read it).
        if (c.credits > 0) {
            html += ' <span class="swu-credit-count"' +
                ' onmousemove="swuLogCardHover(event,\'' + SWU_CREDIT_TOKEN_ID + '\')"' +
                ' onmouseout="HideCardDetail()">+ ' + c.credits + '</span>';
        }
        el.innerHTML = html;
    }

    function watchResZone(slotId, countElId, dataVar) {
        updateResCounterFromData(dataVar, countElId);
        var slot = document.getElementById(slotId); if (!slot) return;
        new MutationObserver(function() {
            updateResCounterFromData(dataVar, countElId);
        }).observe(slot, {childList: true, subtree: true, attributes: true});
    }

    // ── Resource panel toggle (mine only) ─────────────────────────────────────
    window.swuToggleMyResources = function() {
        var panel = document.getElementById('myResourcesSlot'); if (!panel) return;
        panel.classList.toggle('is-open');
    };

    // Close resource panel when clicking outside — EXCEPT while a decision is actively asking the
    // player to pick their own resource(s) (paying with Credit tokens, Han Solo's defeat-a-resource,
    // etc.). The Select All / Deselect All / Confirm controls live in the #selection-message bar
    // OUTSIDE the panel, so without this guard clicking them would dismiss the panel mid-selection.
    // The panel must persist until the selection completes; refreshResourceSelectionPanel then
    // auto-closes any panel it auto-opened.
    document.addEventListener('click', function(e) {
        var panel = document.getElementById('myResourcesSlot'); if (!panel) return;
        if (!panel.classList.contains('is-open')) return;
        var sm = window.SelectionMode;
        var selectingResource = !!(sm && sm.active && Array.isArray(sm.allowedZones) &&
            sm.allowedZones.some(function(z) { return z && z.zone === 'myResources'; }));
        if (selectingResource) return; // keep the panel open until the player confirms / picks
        var badge = document.getElementById('swuMyResBadge');
        if (!panel.contains(e.target) && e.target !== badge && !(badge && badge.contains(e.target))) {
            panel.classList.remove('is-open');
        }
    });

    // ── Effect Stack visibility ───────────────────────────────────────────────
    // Show only when it has entries AND the player isn't mid-resolving a trigger that needs a
    // board target (e.g. "choose a unit"). During such a selection the centered overlay covers the
    // board, so hide it; it reappears for the next "choose trigger to resolve" (an EffectStack MZCHOOSE).
    window.UpdateEffectStackVisibility = function() {
        var el = document.getElementById('EffectStackSlot'); if (!el) return;
        if (el.querySelector('[id$="-0"]') === null) { el.style.display = 'none'; return; }
        var sm = window.SelectionMode, boardTargeting = false;
        if (sm && sm.active && Array.isArray(sm.allowedZones) && sm.allowedZones.length) {
            boardTargeting = !sm.allowedZones.some(function(z){ return z && z.zone === 'EffectStack'; });
        }
        el.style.display = boardTargeting ? 'none' : '';
    };

    // ── Auto-hide Effect Stack when empty ─────────────────────────────────────
    function watchSlot(id) {
        var el = document.getElementById(id); if (!el) return;
        el.style.display = 'none';
        new MutationObserver(function() {
            window.UpdateEffectStackVisibility();
        }).observe(el, {childList:true, subtree:true});
    }

    // ── Effect Stack drag ─────────────────────────────────────────────────────
    function setupEffectStackDrag() {
        var slot = document.getElementById('EffectStackSlot'); if (!slot) return;
        var KEY = 'swu-effect-stack-pos-v2';
        var drag = null;
        slot.setAttribute('data-draggable', 'true');

        function sidebarW() {
            return parseFloat(getComputedStyle(document.documentElement)
                .getPropertyValue('--swu-sidebar-w')) || 280;
        }
        function clamp(v,lo,hi){ return Math.min(hi,Math.max(lo,v)); }
        function applyPos(left,top) {
            var r = slot.getBoundingClientRect();
            var l = clamp(left, 8, window.innerWidth - sidebarW() - r.width - 8);
            var t = clamp(top, 8, window.innerHeight - r.height - 8);
            slot.classList.add('is-custom-position');
            slot.style.left=l+'px'; slot.style.top=t+'px';
            slot.style.right='auto'; slot.style.bottom='auto';
        }
        function savePos(){ var l=parseFloat(slot.style.left),t=parseFloat(slot.style.top);
            if(isFinite(l)&&isFinite(t)) try{localStorage.setItem(KEY,JSON.stringify({left:l,top:t}));}catch(e){} }
        function loadPos(){ try{var d=JSON.parse(localStorage.getItem(KEY)||'null');
            if(d&&isFinite(d.left)&&isFinite(d.top)) applyPos(d.left,d.top);}catch(e){} }

        slot.addEventListener('mousedown', function(ev) {
            if (ev.button!==0) return;
            var r=slot.getBoundingClientRect();
            if(ev.clientY-r.top>28) return;
            drag={sx:ev.clientX,sy:ev.clientY,sl:r.left,st:r.top};
            slot.setAttribute('data-dragging','true'); slot.classList.add('is-custom-position');
            ev.preventDefault();
        });
        window.addEventListener('mousemove',function(ev){if(!drag)return; applyPos(drag.sl+(ev.clientX-drag.sx),drag.st+(ev.clientY-drag.sy));});
        window.addEventListener('mouseup',function(){if(!drag)return; drag=null; slot.removeAttribute('data-dragging'); savePos();});
        window.addEventListener('resize',function(){ if(slot.classList.contains('is-custom-position')){
            var l=parseFloat(slot.style.left),t=parseFloat(slot.style.top);
            if(isFinite(l)&&isFinite(t)) applyPos(l,t); }});
        loadPos();
    }

    // ── Hand collapse ─────────────────────────────────────────────────────────
    function setupHandCollapse() {
        var my=document.getElementById('myHandSlot');
        var their=document.getElementById('theirHandSlot');
        if(!my) return;
        var KEY='swu-hand-collapsed-v1';
        var collapsed=false;
        try{collapsed=localStorage.getItem(KEY)==='1';}catch(e){}

        function makeBtn(){
            var b=document.createElement('button'); b.className='swu-hand-collapse-btn';
            b.type='button'; b.title='Collapse / expand hand';
            b.textContent=collapsed?'▲':'▼';
            b.addEventListener('click',function(ev){ev.stopPropagation(); setCollapsed(!my.classList.contains('is-collapsed'));});
            return b;
        }
        function ensureBtn(){ if(!my.querySelector('.swu-hand-collapse-btn')) my.insertBefore(makeBtn(),my.firstChild); }
        function setCollapsed(c){
            collapsed=c; my.classList.toggle('is-collapsed',c);
            if(their) their.classList.toggle('is-collapsed',c);
            var b=my.querySelector('.swu-hand-collapse-btn'); if(b) b.textContent=c?'▲':'▼';
            try{localStorage.setItem(KEY,c?'1':'0');}catch(e){}
        }
        window.SWUHandCollapse={
            toggle:function(){setCollapsed(!my.classList.contains('is-collapsed'));},
            collapse:function(){setCollapsed(true);}, expand:function(){setCollapsed(false);}
        };
        new MutationObserver(ensureBtn).observe(my,{childList:true});
        ensureBtn();
        if(collapsed){my.classList.add('is-collapsed'); if(their) their.classList.add('is-collapsed');}
    }

    // ── Mount chat widget in sidebar ──────────────────────────────────────────
    function mountChat() {
        var chatWidget = document.getElementById('chatWidget');
        var mount      = document.getElementById('swuChatMount');
        if (!chatWidget || !mount) return;
        // Append inside mount so #swuChatMount stays in the DOM as the
        // show/hide wrapper used by swuShowTab and init().
        mount.appendChild(chatWidget);

        // Watch for new chat messages: scroll to bottom + show notification dot
        var chatLog = document.getElementById('chatLog');
        var chatDot = document.getElementById('swuChatDot');
        if (chatLog) {
            new MutationObserver(function() {
                chatLog.scrollTop = chatLog.scrollHeight;
                if (chatDot && mount.style.display === 'none') {
                    chatDot.style.display = 'inline-block';
                }
            }).observe(chatLog, { childList: true, subtree: true });
            // Scroll to bottom on initial mount too
            chatLog.scrollTop = chatLog.scrollHeight;
        }
    }

    // ── Log/Chat tab switching ─────────────────────────────────────────────────
    window.swuShowTab = function(tab) {
        var logPanel  = document.getElementById('swuLogPanel');
        var chatMount = document.getElementById('swuChatMount');
        var tabLog    = document.getElementById('swuTabLog');
        var tabChat   = document.getElementById('swuTabChat');
        var chatDot   = document.getElementById('swuChatDot');
        if (!logPanel || !chatMount || !tabLog || !tabChat) return;
        if (tab === 'log') {
            logPanel.style.display  = '';
            chatMount.style.display = 'none';
            tabLog.classList.add('is-active');
            tabChat.classList.remove('is-active');
        } else {
            logPanel.style.display  = 'none';
            chatMount.style.display = '';
            tabLog.classList.remove('is-active');
            tabChat.classList.add('is-active');
            if (chatDot) chatDot.style.display = 'none';
            var cl = document.getElementById('chatLog');
            if (cl) requestAnimationFrame(function() { cl.scrollTop = cl.scrollHeight; });
        }
    };

    // ── Card link hover ───────────────────────────────────────────────────────
    window.swuLogCardHover = function(event, cardId) {
        ShowDetail(event, './SWUSim/concat/' + cardId + '.webp');
    };

    // ── Log renderer ──────────────────────────────────────────────────────────
    var _swuLogRenderedCount = 0;

    function swuParseLogText(text) {
        return text.replace(/\[\[([^\]|]+)\|([^\]]+)\]\]/g, function(_, cardId, name) {
            return '<span class="swu-card-link"' +
                ' onmousemove="swuLogCardHover(event,\'' + cardId.replace(/'/g, '') + '\')"' +
                ' onmouseout="HideCardDetail()">' +
                name.replace(/</g, '&lt;') + '</span>';
        });
    }

    window.swuRenderGameLog = function() {
        var panel = document.getElementById('swuLogPanel');
        if (!panel) return;
        var raw = window.GameLogData || '';
        if (!raw || raw === '-') return;
        var entries = raw.split('<NL>');
        // Log shrank (undo) — rebuild from scratch instead of appending.
        if (entries.length < _swuLogRenderedCount) {
            panel.innerHTML = '';
            _swuLogRenderedCount = 0;
        }
        if (entries.length <= _swuLogRenderedCount) return;

        var wasNearBottom = (panel.scrollHeight - panel.scrollTop - panel.clientHeight) < 60;
        var frag = document.createDocumentFragment();

        for (var i = _swuLogRenderedCount; i < entries.length; i++) {
            var entry = entries[i].trim();
            if (!entry) continue;
            var parts = entry.split('|');
            if (parts.length < 3) continue;
            var type = parts[0];
            var text = parts.slice(2).join('|');
            var div  = document.createElement('div');
            div.className = 'swu-log-entry swu-log-' + type;
            div.innerHTML = swuParseLogText(text.replace(/</g, '&lt;'));
            frag.appendChild(div);
        }
        _swuLogRenderedCount = entries.length;
        panel.appendChild(frag);

        if (wasNearBottom) panel.scrollTop = panel.scrollHeight;
    };

    // ── Turn indicator settings ───────────────────────────────────────────────
    window.TurnIndicatorSettings = {
        showWaitingMessage: true,
        messageAnchorId: 'myHandSlot',
        waitingMessageBuilder: function(ctx) {
            return (ctx && typeof ctx.defaultBuilder==='function') ? ctx.defaultBuilder() : null;
        }
    };

    // Auto-open the resource panel while a decision is asking the player to pick one
    // of their own resources (e.g. Han Solo's "defeat a resource you control" trigger).
    // Resources live behind a collapsed badge, so a board-level MZCHOOSE would otherwise
    // have nothing visible to click. We only auto-close a panel we ourselves opened, so a
    // panel the player opened manually is left alone.
    function refreshResourceSelectionPanel() {
        var panel = document.getElementById('myResourcesSlot'); if (!panel) return;
        var sel = window.SelectionMode;
        var selectingResource = !!(sel && sel.active && Array.isArray(sel.allowedZones) &&
            sel.allowedZones.some(function(z) { return z && z.zone === 'myResources'; }));
        if (selectingResource) {
            if (!panel.classList.contains('is-open')) {
                panel.classList.add('is-open');
                window.__swuAutoOpenedResPanel = true;
            }
        } else if (window.__swuAutoOpenedResPanel) {
            panel.classList.remove('is-open');
            window.__swuAutoOpenedResPanel = false;
        }
    }

    // The Force token is rendered inside the base card by the core Card() renderer
    // (driven by the base's HasForce virtual), the same way the Epic-Action-Used
    // token is — no separate wrapper element or polling needed here.

    // ── Poll global data ──────────────────────────────────────────────────────
    // Mirror the engine's board card size (window.cardSize px) into a CSS var so the
    // leader/base center column can size itself to ~2x a unit card. Leader/base art
    // fills the column width (see GameLayoutShared object-fit block), so without this
    // their size would be divorced from the unit cards and look out of proportion.
    function syncCardSizeVar() {
        var cs = parseFloat(window.cardSize);
        if (!cs || isNaN(cs)) return;
        document.documentElement.style.setProperty('--swu-cardsize', cs + 'px');
    }
    function pollGlobals() {
        syncCardSizeVar();
        updatePhaseTrack(); updateInitiative(); updateRound(); refreshActionGlows();
        refreshResourceSelectionPanel();
        swuUpdateUndoUI(MY_PLAYER_ID);
    }
    function watchGlobalData() {
        pollGlobals();
        window.addEventListener('resize', syncCardSizeVar);
        var g=document.getElementById('globalStuff'); if(!g) return;
        new MutationObserver(pollGlobals).observe(g,{childList:true,subtree:true});
    }

    // ── Leader popup ──────────────────────────────────────────────────────────

    function swuParseZoneCard(dataStr) {
        if (!dataStr || dataStr === '-') return null;
        var first = dataStr.split('<|>')[0];
        var spaceIdx = first.indexOf(' ');
        if (spaceIdx === -1) return {CardID: first};
        var cardID    = first.slice(0, spaceIdx);
        var rest      = first.slice(spaceIdx + 1);
        var spaceIdx2 = rest.indexOf(' ');
        if (spaceIdx2 === -1) return {CardID: cardID};
        var jsonStr = rest.slice(spaceIdx2 + 1).replace(/_/g, ' ');
        try { var obj = JSON.parse(jsonStr); return obj; } catch (e) { return {CardID: cardID}; }
    }

    function showLeaderMenu(cardID, abilityAvail, deployAvail) {
        var existing = document.getElementById('swuLeaderMenu');
        if (existing) { existing.remove(); return; }
        var menu = document.createElement('div');
        menu.id = 'swuLeaderMenu';
        menu.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9998;' +
            'background:#0d1b2a;border:2px solid #c8971e;border-radius:10px;padding:24px 32px;' +
            'text-align:center;box-shadow:0 0 30px rgba(200,151,30,0.35);min-width:220px;' +
            'font-family:var(--swu-font-ui,sans-serif);';
        var isPilot = (window.SWU_PILOT_LEADERS || []).indexOf(cardID) !== -1;
        var btnStyle = 'width:100%;padding:8px 16px;background:#1e3a5f;border:1px solid #888;' +
            'border-radius:5px;color:#eee;cursor:pointer;font-size:13px;margin-bottom:2px;';
        var html = '<div style="font-size:15px;font-weight:bold;color:#f0c040;margin-bottom:16px;">Leader Actions</div>';
        if (abilityAvail) {
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'LeaderAbility\')">Leader Ability</button></div>';
        }
        if (deployAvail && !isPilot) {
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'DeployLeader:Unit\')">Deploy Leader</button></div>';
        }
        if (deployAvail && isPilot) {
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'DeployLeader:Unit\')">Deploy as Unit</button></div>';
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'DeployLeader:Pilot\')">Deploy as Pilot</button></div>';
        }
        html += '<div><button style="width:100%;padding:6px 16px;background:transparent;border:1px solid #555;' +
            'border-radius:5px;color:#aaa;cursor:pointer;font-size:12px;" ' +
            'onclick="document.getElementById(\'swuLeaderMenu\').remove()">Cancel</button></div>';
        menu.innerHTML = html;
        document.body.appendChild(menu);
        setTimeout(function () {
            document.addEventListener('click', function outsideClose(e) {
                var m = document.getElementById('swuLeaderMenu');
                if (!m || !m.contains(e.target)) {
                    if (m) m.remove();
                    document.removeEventListener('click', outsideClose);
                }
            });
        }, 0);
    }

    window.swuDoLeaderAction = function (action) {
        var existing = document.getElementById('swuLeaderMenu');
        if (existing) existing.remove();
        SubmitInput('10001', '&cardID=' + encodeURIComponent('myLeader-0!CustomInput!' + action));
    };

    function handleLeaderClick(e) {
        var d = window.myActionsData || {};
        if (!d.leaderAbility && !d.leaderDeploy) return;
        e.stopPropagation(); e.preventDefault();
        var obj    = swuParseZoneCard(window.myLeaderData || '');
        var cardID = (obj && obj.CardID) ? obj.CardID : '';
        var isPilot = (window.SWU_PILOT_LEADERS || []).indexOf(cardID) !== -1;
        if (d.leaderAbility && !d.leaderDeploy) {
            window.swuDoLeaderAction('LeaderAbility');
        } else if (!d.leaderAbility && d.leaderDeploy) {
            isPilot ? showLeaderMenu(cardID, false, true)
                    : window.swuDoLeaderAction('DeployLeader:Unit');
        } else {
            showLeaderMenu(cardID, d.leaderAbility, d.leaderDeploy);
        }
    }

    function setupLeaderClick() {
        var slot = document.getElementById('myLeaderSlot'); if (!slot) return;
        slot.addEventListener('click', handleLeaderClick, true);
    }

    // ── Base popup ────────────────────────────────────────────────────────────

    window.swuDoBaseAction = function () {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('myBase-0!CustomInput!EpicAction'));
    };

    function handleBaseClick(e) {
        var d = window.myActionsData || {};
        if (!d.baseEpic) return;
        e.stopPropagation(); e.preventDefault();
        window.swuDoBaseAction();
    }

    function setupBaseClick() {
        var slot = document.getElementById('myBaseSlot'); if (!slot) return;
        slot.addEventListener('click', handleBaseClick, true);
    }

    // ── Discard play click ────────────────────────────────────────────────────
    function handleDiscardClick(e, owner) {
        var el = e.target;
        while (el && el !== e.currentTarget) {
            if (el.classList && el.classList.contains('discard-playable')) {
                var mzid = el.getAttribute && el.getAttribute('data-mzid');
                if (mzid) {
                    var parts = mzid.split('-');
                    var idx = parts[parts.length - 1];
                    e.stopPropagation();
                    e.preventDefault();
                    if (owner === 'opp') {
                        SubmitInput('10001', '&cardID=' + encodeURIComponent('PlayFromOpponentDiscard-' + idx + '!CustomInput!'));
                    } else {
                        SubmitInput('10001', '&cardID=' + encodeURIComponent('PlayFromDiscard-' + idx + '!CustomInput!'));
                    }
                    return;
                }
            }
            el = el.parentElement;
        }
    }

    function setupDiscardClick() {
        var mySlot = document.getElementById('myDiscardSlot');
        if (mySlot) mySlot.addEventListener('click', function(e) { handleDiscardClick(e, 'mine'); }, true);
        var theirSlot = document.getElementById('theirDiscardSlot');
        if (theirSlot) theirSlot.addEventListener('click', function(e) { handleDiscardClick(e, 'opp'); }, true);
    }

    // Clicking a unit that has an available Action (glowing .unit-action) is ambiguous:
    // a ready unit can either Attack OR use its Action (both exhaust it). Present a small
    // menu so the player picks. Skips when a selection/targeting is active so attacks/
    // targets are unaffected.
    function removeUnitActionMenu() {
        var ex = document.getElementById('swuUnitActionMenu');
        if (ex && ex.parentNode) ex.parentNode.removeChild(ex);
    }
    function showUnitActionMenu(mzid, anchorEl) {
        removeUnitActionMenu();
        var menu = document.createElement('div');
        menu.id = 'swuUnitActionMenu';
        menu.className = 'swu-unit-action-menu';

        var atkBtn = document.createElement('button');
        atkBtn.className = 'swu-uam-btn';
        atkBtn.textContent = 'Attack';
        atkBtn.addEventListener('click', function(ev) {
            ev.stopPropagation();
            removeUnitActionMenu();
            // Delegate to the framework's normal unit-click attack flow. CardClick's
            // GetZoneClickActions switches on the BARE zone name (no my/their prefix),
            // while the mzid passed as cardId stays player-prefixed (e.g. myGroundArena-0).
            var zone = mzid.replace(/-\d+$/, '').replace('my', '').replace('their', '');
            if (typeof CardClick === 'function') {
                CardClick({ stopPropagation: function() {} }, zone, mzid);
            }
        });

        var abilityBtn = document.createElement('button');
        abilityBtn.className = 'swu-uam-btn';
        abilityBtn.textContent = 'Ability';
        abilityBtn.addEventListener('click', function(ev) {
            ev.stopPropagation();
            removeUnitActionMenu();
            SubmitInput('10001', '&cardID=' + encodeURIComponent(mzid + '!CustomInput!Activate'));
        });

        menu.appendChild(atkBtn);
        menu.appendChild(abilityBtn);
        document.body.appendChild(menu);

        // Position centered above the unit; flip below if it would clip the top.
        var rect = anchorEl.getBoundingClientRect();
        var left = rect.left + rect.width / 2 - menu.offsetWidth / 2;
        var top  = rect.top - menu.offsetHeight - 6;
        if (top < 4) top = rect.bottom + 6;
        left = Math.max(4, Math.min(left, window.innerWidth - menu.offsetWidth - 4));
        menu.style.left = left + 'px';
        menu.style.top  = top + 'px';

        // Dismiss on the next outside click (capture phase so it fires before card onclicks).
        setTimeout(function() {
            document.addEventListener('click', function dismiss(ev) {
                var m = document.getElementById('swuUnitActionMenu');
                if (m && !m.contains(ev.target)) removeUnitActionMenu();
                document.removeEventListener('click', dismiss, true);
            }, true);
        }, 0);
    }
    function handleUnitActionClick(e) {
        if (window.SelectionMode && window.SelectionMode.active) return;
        var el = e.target;
        while (el && el !== e.currentTarget) {
            if (el.classList && el.classList.contains('unit-action')) {
                var mzid = el.getAttribute && el.getAttribute('data-mzid');
                if (mzid) {
                    e.stopPropagation();
                    e.preventDefault();
                    showUnitActionMenu(mzid, el);
                    return;
                }
            }
            el = el.parentElement;
        }
    }
    function setupUnitActionClick() {
        ['myGroundArenaSlot', 'mySpaceArenaSlot'].forEach(function(id) {
            var slot = document.getElementById(id);
            if (slot) slot.addEventListener('click', handleUnitActionClick, true);
        });
    }
    function refreshUnitActionGlows() {
        var d = window.myActionsData || {};
        document.querySelectorAll('.unit-action').forEach(function(el) { el.classList.remove('unit-action'); });
        document.querySelectorAll('.can-attack').forEach(function(el) { el.classList.remove('can-attack'); });
        var abilityMz = {};
        (d.unitActions || []).forEach(function(mz) {
            abilityMz[mz] = true;
            var el = document.querySelector('[data-mzid="' + mz + '"]');
            if (el) el.classList.add('unit-action');
        });
        // "Can attack" green glow — skip units already showing the cyan ability glow (their click menu
        // already offers Attack), so a unit never carries both highlights.
        (d.attackers || []).forEach(function(mz) {
            if (abilityMz[mz]) return;
            var el = document.querySelector('[data-mzid="' + mz + '"]');
            if (el) el.classList.add('can-attack');
        });
    }

    // ── Resource Smuggle click ─────────────────────────────────────────────────
    // Intercept resource card clicks to trigger Smuggle when conditions are met.
    // Uses capture phase so it fires before the framework FSM onclick on the card.
    function handleResourceClick(e) {
        var isMyTurn    = (String(window.TurnPlayerData   || '').trim() === String(MY_PLAYER_ID));
        var isMainPhase = (String(window.CurrentPhaseData || '').trim() === 'MAIN');
        if (!isMyTurn || !isMainPhase) return;
        // Don't intercept when selection mode is active (MZCHOOSE picking a resource target).
        if (window.SelectionMode && window.SelectionMode.active) return;
        // Walk up from the click target to find the card element with data-mzid.
        var el = e.target;
        var mzid = null;
        while (el && el !== e.currentTarget) {
            var m = el.getAttribute && el.getAttribute('data-mzid');
            if (m && /^myResources-\d+$/.test(m)) { mzid = m; break; }
            el = el.parentElement;
        }
        if (!mzid) return;
        e.stopPropagation();
        e.preventDefault();
        SubmitInput('10001', '&cardID=' + encodeURIComponent(mzid + '!CustomInput!Smuggle'));
    }

    function setupResourceClick() {
        var slot = document.getElementById('myResourcesSlot'); if (!slot) return;
        slot.addEventListener('click', handleResourceClick, true);
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    function init() {
        watchSlot('EffectStackSlot');
        mountChat();
        watchGlobalData();
        setupEffectStackDrag();
        setupHandCollapse();
        watchResZone('myResourcesSlot',    'swuMyResCount',    'myResourcesData');
        watchResZone('theirResourcesSlot', 'swuTheirResCount', 'theirResourcesData');
        // (Initiative token is a status badge now — taking it lives on the Take/Keep button
        //  in the player's own controls, wired via inline onclick → window.swuTakeInitiative.)
        var passBtn = document.getElementById('swuPassBtn');
        if (passBtn) passBtn.addEventListener('click', function () {
            var isMyTurn    = (String(window.TurnPlayerData||'').trim() === String(MY_PLAYER_ID));
            var isMainPhase = (String(window.CurrentPhaseData||'').trim() === 'MAIN');
            if (isMyTurn && isMainPhase) window.swuPassAction();
        });
        setupLeaderClick();
        setupBaseClick();
        setupResourceClick();
        setupDiscardClick();
        setupUnitActionClick();
        // Space key = Pass (only fires when no input element is focused)
        document.addEventListener('keydown', function(e) {
            if (e.code !== 'Space' && e.keyCode !== 32) return;
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) return;
            e.preventDefault();
            // A visible decision prompt with its own Pass button (e.g. the Resource
            // step's "Resource up to N cards") takes priority — Space clicks it.
            // Match by label so we never click a Confirm/Select-All on a multi-select.
            var sel = document.getElementById('selection-message');
            if (sel && sel.style.display !== 'none') {
                var btns = sel.querySelectorAll('button');
                for (var i = 0; i < btns.length; i++) {
                    if ((btns[i].textContent || '').trim().toLowerCase() === 'pass') {
                        btns[i].click(); return;
                    }
                }
            }
            // Otherwise: the MAIN-phase action pass.
            var isMyTurn    = (String(window.TurnPlayerData||'').trim() === String(MY_PLAYER_ID));
            var isMainPhase = (String(window.CurrentPhaseData||'').trim() === 'MAIN');
            if (isMyTurn && isMainPhase) window.swuPassAction();
        });
        // "I" key = Take/Keep the Initiative. Gated on the Take/Keep button being live
        // (not hidden) — updateInitiative() only shows it when the action is legal (canTake),
        // so this reuses that same gate. No-op on layouts/states where it isn't shown.
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'i' && e.key !== 'I') return;
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA')) return;
            var btn = document.getElementById('swuTakeInitBtn');
            if (!btn || btn.hidden || btn.classList.contains('is-taken')) return;
            e.preventDefault();
            window.swuTakeInitiative();
        });
        // "W" swaps which player's board is shown — a schema-editor convenience that
        // normally only works when the OUTER page has focus. Extend it into the game
        // iframe so it also fires while focus is inside the board. Gated to the
        // shared-control test sandbox: only when this view's authKey is "testschema"
        // (the editor loads BOTH player views with that key, and the server's auth
        // only accepts it there — so a real game can never trigger the swap).
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'w' && e.key !== 'W') return;
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA')) return;
            var authKey = '';
            try { authKey = new URLSearchParams(window.location.search).get('authKey') || ''; } catch (err) {}
            if (authKey !== 'testschema') return;
            e.preventDefault();
            try {
                var btn = (window.parent && window.parent !== window)
                    ? window.parent.document.getElementById('swap-player-btn') : null;
                if (btn && !btn.disabled) btn.click();
            } catch (err) { /* no editor parent / cross-origin — nothing to swap */ }
        });
        // Start with Log tab active, chat hidden
        var chatMount = document.getElementById('swuChatMount');
        if (chatMount) chatMount.style.display = 'none';
    }

    // ── Game-over banner ──────────────────────────────────────────────────────
    function showGameOverBanner(msg) {
        var existing = document.getElementById('swuGameOverBanner');
        if (existing) return;
        var banner = document.createElement('div');
        banner.id = 'swuGameOverBanner';
        banner.style.cssText = [
            'position:fixed', 'top:50%', 'left:50%',
            'transform:translate(-50%,-50%)',
            'z-index:9999',
            'background:#0d1b2a',
            'border:2px solid #f0c040',
            'border-radius:10px',
            'padding:32px 48px',
            'text-align:center',
            'box-shadow:0 0 40px rgba(240,192,64,0.4)',
            'min-width:320px'
        ].join(';');
        banner.innerHTML =
            '<div style="font-size:22px;font-weight:bold;color:#f0c040;margin-bottom:8px;">Game Over</div>' +
            '<div style="font-size:14px;color:#d4d4d4;margin-bottom:20px;">' + msg.replace(/</g,'&lt;') + '</div>' +
            '<button onclick="document.getElementById(\'swuGameOverBanner\').remove()" ' +
            'style="padding:6px 18px;background:#1e3a5f;border:1px solid #888;border-radius:4px;' +
            'color:#eee;cursor:pointer;font-size:12px;">Dismiss ×</button>';
        document.body.appendChild(banner);
    }

    // ── Action-available glow ─────────────────────────────────────────────────

    // Applies/removes the .smuggle-available class on individual resource card elements.
    function refreshResourceCardGlows() {
        var slot = document.getElementById('myResourcesSlot'); if (!slot) return;
        slot.querySelectorAll('.smuggle-available').forEach(function(el) {
            el.classList.remove('smuggle-available');
        });
        var d = window.myActionsData || {};
        var indices = d.smugglableResources || [];
        for (var i = 0; i < indices.length; i++) {
            var cardEl = document.getElementById('myResources-' + indices[i]);
            if (cardEl) cardEl.classList.add('smuggle-available');
        }
    }

    function refreshActionGlows() {
        var d = window.myActionsData || {};

        var leaderSlot = document.getElementById('myLeaderSlot');
        if (leaderSlot) {
            leaderSlot.classList.toggle('has-action', !!(d.leaderAbility || d.leaderDeploy));
        }

        var baseSlot = document.getElementById('myBaseSlot');
        if (baseSlot) {
            baseSlot.classList.toggle('has-action', !!d.baseEpic);
        }

        var resCount = document.getElementById('swuMyResCount');
        if (resCount) {
            resCount.classList.toggle('has-action', !!(d.smugglableResources && d.smugglableResources.length > 0));
        }

        var myDiscardSlot = document.getElementById('myDiscardSlot');
        if (myDiscardSlot) myDiscardSlot.classList.toggle('has-action',
            !!(d.playableDiscards && d.playableDiscards.length > 0));
        var theirDiscardSlot = document.getElementById('theirDiscardSlot');
        if (theirDiscardSlot) theirDiscardSlot.classList.toggle('has-action',
            !!(d.opponentPlayableDiscards && d.opponentPlayableDiscards.length > 0));
    }

    function refreshDiscardCardGlows() {
        var d = window.myActionsData || {};
        var mySlot = document.getElementById('myDiscardSlot');
        if (mySlot) {
            mySlot.querySelectorAll('.discard-playable').forEach(function(el) {
                el.classList.remove('discard-playable');
            });
            (d.playableDiscards || []).forEach(function(entry) {
                var el = document.getElementById('myDiscard-' + entry.idx);
                if (el) el.classList.add('discard-playable');
            });
        }
        var theirSlot = document.getElementById('theirDiscardSlot');
        if (theirSlot) {
            theirSlot.querySelectorAll('.discard-playable').forEach(function(el) {
                el.classList.remove('discard-playable');
            });
            (d.opponentPlayableDiscards || []).forEach(function(entry) {
                var el = document.getElementById('theirDiscard-' + entry.idx);
                if (el) el.classList.add('discard-playable');
            });
        }
    }

    // ── Leader deployed state intercept ───────────────────────────────────────
    // Intercept myLeaderData / theirLeaderData assignments to toggle .is-deployed
    // on the leader slots so the card ghosts when the leader is in the arena.
    function applyLeaderDeployedClass(slotId, dataStr) {
        var slot = document.getElementById(slotId); if (!slot) return;
        var obj  = swuParseZoneCard(dataStr || '');
        var dep  = obj && (obj.Deployed === true || obj.Deployed === 'true' || parseInt(obj.Deployed, 10) === 1);
        slot.classList.toggle('is-deployed', !!dep);
    }

    (function () {
        var _myLeaderInternal    = window.myLeaderData    || '';
        var _theirLeaderInternal = window.theirLeaderData || '';
        var _myBaseInternal      = window.myBaseData      || '';
        var _myResourcesInternal = window.myResourcesData || '';
        var _turnPlayerInternal  = window.TurnPlayerData  || '';
        var _phaseInternal       = window.CurrentPhaseData || '';
        Object.defineProperty(window, 'myLeaderData', {
            configurable: true,
            get: function () { return _myLeaderInternal; },
            set: function (v) { _myLeaderInternal = v; applyLeaderDeployedClass('myLeaderSlot', v); }
        });
        Object.defineProperty(window, 'theirLeaderData', {
            configurable: true,
            get: function () { return _theirLeaderInternal; },
            set: function (v) { _theirLeaderInternal = v; applyLeaderDeployedClass('theirLeaderSlot', v); }
        });
        Object.defineProperty(window, 'myBaseData', {
            configurable: true,
            get: function () { return _myBaseInternal; },
            set: function (v) { _myBaseInternal = v; }
        });
        Object.defineProperty(window, 'myResourcesData', {
            configurable: true,
            get: function () { return _myResourcesInternal; },
            set: function (v) { _myResourcesInternal = v; }
        });
        Object.defineProperty(window, 'TurnPlayerData', {
            configurable: true,
            get: function () { return _turnPlayerInternal; },
            set: function (v) { _turnPlayerInternal = v; }
        });
        Object.defineProperty(window, 'CurrentPhaseData', {
            configurable: true,
            get: function () { return _phaseInternal; },
            set: function (v) { _phaseInternal = v; }
        });
        var _resGlowRafPending = false;
        var _myActionsInternal = {};
        Object.defineProperty(window, 'myActionsData', {
            configurable: true,
            get: function () { return _myActionsInternal; },
            set: function (v) {
                _myActionsInternal = (typeof v === 'string') ? (function(){ try { return JSON.parse(v); } catch(e) { return {}; } }()) : (v || {});
                refreshActionGlows();
                if (!_resGlowRafPending) {
                    _resGlowRafPending = true;
                    requestAnimationFrame(function() {
                        _resGlowRafPending = false;
                        refreshResourceCardGlows();
                        refreshDiscardCardGlows();
                        refreshUnitActionGlows();
                    });
                }
            }
        });
    })();

    // Re-render the game log whenever NextTurnRender assigns GameLogData.
    (function () {
        var _gameLogInternal = '';
        Object.defineProperty(window, 'GameLogData', {
            configurable: true,
            get: function () { return _gameLogInternal; },
            set: function (v) {
                _gameLogInternal = v || '';
                if (window.swuRenderGameLog) window.swuRenderGameLog();
            }
        });
    })();

    // ── Match-aware end-game menu ─────────────────────────────────────────────
    // The root SharedUI/MainMenu.php pointer renders whatever Sites/<ActiveSite>/ is set (SWUSim here);
    // there is NO MainMenu.php at the TCGEngine root, so the old './MainMenu.php' fallback 404'd.
    function SWUGoMainMenu() { window.location.href = window.SWUMainMenuUrl || './SharedUI/MainMenu.php'; }
    function SWUReportBug() { if (typeof openBugReportModal === 'function') openBugReportModal(); }
    // Block the current opponent. Server resolves who the opponent is and whether to forfeit
    // (an in-progress Bo3 set). The blocked player is never told — privacy invariant.
    function SWUBlockOpponent(opts) {
        opts = opts || {};
        var msg = opts.liveBo3
            ? "Block this player? You won't be able to play the next game in this set with them, and you'll be granted the loss."
            : "Block this player? You won't be matched with them again.";
        if (!window.confirm(msg)) return;
        var gnEl = document.getElementById('gameName');
        var gn = gnEl ? gnEl.value : '';
        var x = new XMLHttpRequest();
        x.open('POST', '/TCGEngine/SWUSim/BlockedUsers.php', true);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.onreadystatechange = function() {
            if (x.readyState === 4) {
                var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {}
                if (r && r.forfeited) { SWUGoMainMenu(); }
            }
        };
        x.send('action=blockOpponent&gameName=' + encodeURIComponent(gn));
    }
    function SWUGoSideboard(info) {
        var pid = document.getElementById('playerID').value;
        var ak = document.getElementById('authKey').value;
        if (!info || !info.matchId) { window.location.reload(); return; }
        var u = new URL(window.location.origin + window.location.pathname.replace(/NextTurn\.php$/, 'SWUSim/Sideboard.php'));
        u.searchParams.set('matchId', info.matchId);
        u.searchParams.set('playerID', pid);
        u.searchParams.set('authKey', ak);
        window.location.replace(u.toString());
    }
    // Convert-to-Bo3 button label/disabled for the current mutual-confirmation state.
    // Same click handler in every enabled state (input 10012 both requests and accepts).
    function SWUConvertButtonState(info) {
        var mine = info && info.convertRequestedByMe, opp = info && info.convertRequestedByOpp;
        if (mine && !opp) return { label:'Waiting on opponent to confirm…', disabled:true };
        if (opp && !mine) return { label:'Confirm Convert to Best of 3', disabled:false };
        return { label:'Convert to Best of 3', disabled:false };
    }
    function SWUUpdateConvertButton(info) {
        var btn = document.getElementById('swu-convert-btn');
        if (!btn) return;
        var cv = SWUConvertButtonState(info);
        btn.textContent = cv.label;
        btn.disabled = !!cv.disabled;
    }
    // While the end-game menu is open and the match is still convertible, poll for the opponent's
    // convert request so the button updates without an alert. Once both confirm, the match leaves
    // the convertible state (→ Bo3 sideboarding): rebuild the menu so it shows the Bo3 options.
    function SWUStartEndGamePoll(gn, pid, ak) {
        if (window._swuEndGamePollTimer) return;
        window._swuEndGamePollTimer = setInterval(function () {
            if (!document.getElementById('game-over-overlay')) {
                clearInterval(window._swuEndGamePollTimer); window._swuEndGamePollTimer = null; return;
            }
            fetch('./SWUSim/EndGameInfo.php?gameName=' + encodeURIComponent(gn) + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(ak))
                .then(function(r){ return r.json(); }).then(function(info){
                    if (!info || !info.isMatch) return;
                    if (!info.convertible) { // both confirmed → rebuild for the new (Bo3) match shape
                        clearInterval(window._swuEndGamePollTimer); window._swuEndGamePollTimer = null;
                        var ov = document.getElementById('game-over-overlay'); if (ov) ov.remove();
                        if (typeof SWUShowEndGameMenu === 'function') SWUShowEndGameMenu();
                        return;
                    }
                    SWUUpdateConvertButton(info);
                }).catch(function(){});
        }, 2500);
    }
    function SWUBuildEndGameButtons(info) {
        var b = [];
        var mid = info && info.isMatch;
        var bestOf = info ? info.bestOf : 1;
        var seriesOver = info ? info.seriesOver : true;
        var spectator = info ? info.isSpectator : false;
        if (spectator || !mid) {
            b.push({label:'Return to Main Menu', onClick: SWUGoMainMenu});
            if (!mid && !spectator) b.push({label:'Quick Rematch', onClick:function(){ SubmitInput('10013','&inputText=1'); }});
            b.push({label:'Report Bug', onClick: SWUReportBug});
            return b;
        }
        // Real match, not a spectator: allow blocking the opponent. Between Bo3 games this
        // forfeits the set (server decides); post-game / Bo1 it just blocks.
        b.push({label:'Block Player', onClick:function(){ SWUBlockOpponent({liveBo3: (bestOf === 3 && !seriesOver)}); }});
        if (bestOf === 3 && !seriesOver) {
            b.push({label:'Return to Main Menu', onClick:function(){ if(window.confirm('Leave now? This forfeits the best-of-3.')) { SubmitInput('10007',''); SWUGoMainMenu(); } }});
            b.push({label:'Go to Next Game', onClick:function(){ SWUGoSideboard(info); }});
            b.push({label:'Forfeit Best of 3', onClick:function(){ if(typeof confirmConcedeMatch==='function') confirmConcedeMatch(); }});
        } else if (bestOf === 1 && seriesOver) {
            b.push({label:'Return to Main Menu', onClick: SWUGoMainMenu});
            b.push({label:'Quick Rematch', onClick:function(){ SubmitInput('10013','&inputText=1'); }});
            b.push({label:'Rematch', onClick:function(){ SubmitInput('10016','&inputText=1'); }});
            if (info.convertible) {
                var cv = SWUConvertButtonState(info); // {label, disabled}
                b.push({id:'swu-convert-btn', label:cv.label, disabled:cv.disabled,
                        onClick:function(){ if(typeof confirmConvertToBo3==='function') confirmConvertToBo3(); }});
            }
        } else if (seriesOver) {
            // Bo3 finished — rematch with a Bo1/Bo3 toggle.
            var fmt = { v: 3 };
            b.push({label:'Return to Main Menu', onClick: SWUGoMainMenu});
            b.push({label:'Quick Rematch', onClick:function(){ SubmitInput('10013','&inputText=' + fmt.v); }});
            b.push({id:'swu-rematch-btn', label:'Rematch', onClick:function(){ SubmitInput('10016','&inputText=' + fmt.v); }});
            b.push({id:'swu-bestof-btn', label:'Bo3', onClick:function(ev){ fmt.v = (fmt.v===3?1:3); ev.target.textContent = 'Bo' + fmt.v; }});
        } else {
            b.push({label:'Return to Main Menu', onClick: SWUGoMainMenu});
        }
        b.push({label:'Report Bug', onClick: SWUReportBug});
        return b;
    }
    // The winner the client already knows locally (GAMEOVER_WINNER), independent of the match layer.
    function SWULocalGameWinner() {
        try { var v = JSON.parse(window.DecisionQueueVariablesData || '{}');
              return (v && v.GAMEOVER_WINNER) ? parseInt(v.GAMEOVER_WINNER, 10) : 0; }
        catch (e) { return 0; }
    }
    function SWUShowEndGameMenu() {
        if (document.getElementById('game-over-overlay')) return;
        var gn = document.getElementById('gameName').value;
        var pid = document.getElementById('playerID').value;
        var ak = document.getElementById('authKey').value;
        fetch('./SWUSim/EndGameInfo.php?gameName=' + encodeURIComponent(gn) + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(ak))
            .then(function(r){ return r.json(); }).then(function(info){
                // Non-match game (schema-test harness / goldfish): no match layer behind it, so
                // EndGameInfo returns {isMatch:false} with no didWin. Show the plain overlay using the
                // locally-known winner — not a match menu whose buttons (Rematch / Next Game) don't apply.
                if (!info || !info.isMatch) {
                    var w = SWULocalGameWinner();
                    ShowGameOver(w > 0 && parseInt(pid, 10) === w, window.SWUMainMenuUrl || null, '');
                    return;
                }
                ShowGameOver(!!info.didWin, window.SWUMainMenuUrl || null, info.statsHtml || '', SWUBuildEndGameButtons(info));
                if (info.convertible) SWUStartEndGamePoll(gn, pid, ak);
            }).catch(function(){
                var w = SWULocalGameWinner();
                ShowGameOver(w > 0 && parseInt(pid, 10) === w, window.SWUMainMenuUrl || null, '');
            });
    }
    window.SWUShowEndGameMenu = SWUShowEndGameMenu;

    // Intercept FlashMessageData before NextTurnRender consumes it.
    // A "GAMEOVER:"/"MATCHOVER:" flash opens the match-aware end-game menu (falls back to the banner).
    var _flashInternal = '';
    Object.defineProperty(window, 'FlashMessageData', {
        configurable: true,
        get: function () { return _flashInternal; },
        set: function (v) {
            if (typeof v === 'string' && (v.indexOf('MATCHOVER:') === 0 || v.indexOf('GAMEOVER:') === 0)) {
                if (typeof SWUShowEndGameMenu === 'function') SWUShowEndGameMenu();
                else showGameOverBanner(v.indexOf('MATCHOVER:') === 0 ? v.slice(10) : v.slice(9));
                _flashInternal = '';
            } else {
                _flashInternal = v;
            }
        }
    });

    // End-game stats (mobile): tap a TRUNCATED card-name cell to reveal its full name in a
    // bubble; the next tap ANYWHERE (the bubble or outside it) dismisses it. Delegated on the
    // document so it works whenever the overlay exists, and inert on desktop (cells aren't
    // truncated there, so the scrollWidth check short-circuits).
    (function () {
        var tip = null;
        function closeTip() { if (tip) { if (tip.parentNode) tip.parentNode.removeChild(tip); tip = null; } }
        document.addEventListener('click', function (ev) {
            if (tip) { closeTip(); return; }   // a bubble is open → ANY tap dismisses it
            var t = ev.target;
            var cell = (t && t.closest) ? t.closest('#game-over-stats td:first-child') : null;
            if (!cell) return;
            if (cell.scrollWidth <= cell.clientWidth + 1) return;   // not actually truncated
            var full = (cell.textContent || '').trim();
            if (!full) return;
            tip = document.createElement('div');
            tip.className = 'swu-stat-tip';
            tip.textContent = full;
            document.body.appendChild(tip);
            var r = cell.getBoundingClientRect();
            var tw = tip.offsetWidth, th = tip.offsetHeight, m = 6;
            var left = Math.min(Math.max(m, r.left), window.innerWidth - tw - m);
            var top = r.bottom + m;
            if (top + th > window.innerHeight - m) top = Math.max(m, r.top - th - m);
            tip.style.left = left + 'px';
            tip.style.top = top + 'px';
        }, false);
        window.addEventListener('resize', closeTip);
    })();

    if (document.readyState==='loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(<?php echo intval($playerID); ?>);
</script>

<script>
// ── SWU mulligan hand preview ─────────────────────────────────────────────────
// The shared ShowYesNoDecisionPopup (Core/UILibraries) renders the mulligan prompt
// as a fixed full-screen overlay that blocks scrolling to the board, so on mobile
// the player can't see the hand they're deciding whether to mulligan. Wrap it to
// inject the freshly-drawn hand thumbnails at the top of the modal panel, above the
// prompt. Gated to the mulligan decision (Param 'mulligan'); every other YESNO is
// untouched. UILibraries loads in NextTurn.php's <head>, so the original is defined
// by the time this inline body script runs.
(function () {
    var _origShowYesNo = window.ShowYesNoDecisionPopup;
    if (typeof _origShowYesNo !== 'function') return;

    function isMulligan(decision) {
        if (!decision) return false;
        if (decision.Param === 'mulligan') return true;
        return !!(decision.Tooltip && /mulligan/i.test(decision.Tooltip));
    }

    // Build a thumbnail row from the current hand (window.myHandData: "<|>"-joined
    // entries, each a space-separated token list whose first token is the CardID).
    function buildHandRow() {
        var raw = (typeof window.myHandData === 'string') ? window.myHandData.trim() : '';
        if (!raw) return null;
        var row = document.createElement('div');
        row.className = 'swu-mulligan-hand';
        var entries = raw.split('<|>');
        var rendered = 0;
        for (var i = 0; i < entries.length; i++) {
            var cardID = (entries[i] || '').trim().split(' ')[0];
            if (!cardID || cardID === '-') continue;
            var img = document.createElement('img');
            img.loading = 'lazy';
            img.alt = cardID;
            img.src = './SWUSim/concat/' + cardID + '.webp';
            row.appendChild(img);
            rendered++;
        }
        return rendered > 0 ? row : null;
    }

    window.ShowYesNoDecisionPopup = function (decision, onSubmit) {
        _origShowYesNo(decision, onSubmit);
        if (!isMulligan(decision)) return;
        var modal = document.querySelector('#yesno-decision-modal > div');
        if (!modal) return;
        var row = buildHandRow();
        if (row) modal.insertBefore(row, modal.firstChild);
    };
})();
</script>

<script>
// ── SWU Undo UI helpers ───────────────────────────────────────────────────────
window.GetSWUDQVar = window.GetSWUDQVar || function(key, def) {
    var d = typeof window.DecisionQueueVariablesData === 'string'
        ? window.DecisionQueueVariablesData : '';
    var pairs = d.split('|');
    for (var i = 0; i < pairs.length; i++) {
        var eq = pairs[i].indexOf('=');
        if (eq !== -1 && pairs[i].slice(0, eq) === key) return pairs[i].slice(eq + 1); // first occurrence wins
    }
    return (def !== undefined ? def : '');
};

function swuShowUndoRequestPopup(fromPlayerID) {
    var existing = document.getElementById('swu-undo-request-modal');
    if (existing) return; // already showing
    var overlay = document.createElement('div');
    overlay.id = 'swu-undo-request-modal';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.55);z-index:9000;display:flex;align-items:center;justify-content:center;';
    var modal = document.createElement('div');
    modal.style.cssText = 'background:#0D1B2A;padding:32px 28px;border-radius:10px;box-shadow:0 0 24px #0009;text-align:center;min-width:320px;font-family:\'Orbitron\',sans-serif;';
    var msg = document.createElement('div');
    msg.style.cssText = 'font-size:16px;color:#fff;margin-bottom:8px;';
    msg.textContent = 'Player ' + fromPlayerID + ' requested to undo their last action.';
    var sub = document.createElement('div');
    sub.style.cssText = 'font-size:12px;color:rgba(255,255,255,0.55);margin-bottom:24px;';
    sub.textContent = '(They revealed hidden card information.)';
    var allowBtn = document.createElement('button');
    allowBtn.textContent = 'Allow';
    allowBtn.style.cssText = 'margin:0 12px 0 0;padding:8px 24px;font-size:16px;background:#28a745;color:#fff;border:none;border-radius:5px;cursor:pointer;';
    allowBtn.onclick = function() { overlay.remove(); SubmitInput(10008, ''); };
    var denyBtn = document.createElement('button');
    denyBtn.textContent = 'Deny';
    denyBtn.style.cssText = 'padding:8px 24px;font-size:16px;background:#dc3545;color:#fff;border:none;border-radius:5px;cursor:pointer;';
    denyBtn.onclick = function() { overlay.remove(); SubmitInput(10009, ''); };
    modal.appendChild(msg);
    modal.appendChild(sub);
    modal.appendChild(allowBtn);
    modal.appendChild(denyBtn);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
}

function swuShowBlockPromptPopup(targetPlayerID) {
    var existing = document.getElementById('swu-block-prompt-modal');
    if (existing) return;
    var overlay = document.createElement('div');
    overlay.id = 'swu-block-prompt-modal';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.55);z-index:9001;display:flex;align-items:center;justify-content:center;';
    var modal = document.createElement('div');
    modal.style.cssText = 'background:#0D1B2A;padding:32px 28px;border-radius:10px;box-shadow:0 0 24px #0009;text-align:center;min-width:320px;font-family:\'Orbitron\',sans-serif;';
    var msg = document.createElement('div');
    msg.style.cssText = 'font-size:16px;color:#fff;margin-bottom:8px;';
    msg.textContent = 'Player ' + targetPlayerID + ' has had undo requests denied multiple times.';
    var sub = document.createElement('div');
    sub.style.cssText = 'font-size:12px;color:rgba(255,255,255,0.55);margin-bottom:24px;';
    sub.textContent = 'Block all future undo requests from them?';
    var blockBtn = document.createElement('button');
    blockBtn.textContent = 'Block';
    blockBtn.style.cssText = 'margin:0 12px 0 0;padding:8px 24px;font-size:16px;background:#dc3545;color:#fff;border:none;border-radius:5px;cursor:pointer;';
    blockBtn.onclick = function() { overlay.remove(); SubmitInput(10010, ''); };
    var keepBtn = document.createElement('button');
    keepBtn.textContent = 'Keep Allowing';
    keepBtn.style.cssText = 'padding:8px 24px;font-size:16px;background:#6c757d;color:#fff;border:none;border-radius:5px;cursor:pointer;';
    keepBtn.onclick = function() { overlay.remove(); SubmitInput(10011, ''); };
    modal.appendChild(msg);
    modal.appendChild(sub);
    modal.appendChild(blockBtn);
    modal.appendChild(keepBtn);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
}

function swuUpdateUndoUI(myPlayerID) {
    var btn = document.getElementById('swuUndoBtn');
    if (!btn) return;

    var hasVersion = typeof window.myVersionsData === 'string' && window.myVersionsData.trim() !== '';
    var requiresConsent = GetSWUDQVar('UNDO_REQUIRES_CONSENT') === 'true';
    var isBlocked = GetSWUDQVar('UNDO_BLOCKED_' + myPlayerID) === 'true';

    btn.style.display = hasVersion ? 'inline-block' : 'none';
    btn.textContent = requiresConsent ? 'Request Undo' : 'Undo';
    btn.disabled = isBlocked;
    btn.title = isBlocked ? 'Your opponent has blocked undo requests.' : '';

    // Undo request popup: show to the opponent of PENDING_UNDO_FROM
    var pendingFrom = GetSWUDQVar('PENDING_UNDO_FROM');
    var otherPlayer = myPlayerID === 1 ? 2 : 1;
    if (pendingFrom !== '' && parseInt(pendingFrom, 10) === otherPlayer) {
        swuShowUndoRequestPopup(otherPlayer);
    } else {
        var reqModal = document.getElementById('swu-undo-request-modal');
        if (reqModal) reqModal.remove();
    }

    // Block prompt popup: show to the opponent of PENDING_BLOCK_PROMPT_FOR
    var pendingBlock = GetSWUDQVar('PENDING_BLOCK_PROMPT_FOR');
    if (pendingBlock !== '' && parseInt(pendingBlock, 10) === otherPlayer) {
        swuShowBlockPromptPopup(otherPlayer);
    } else {
        var blkModal = document.getElementById('swu-block-prompt-modal');
        if (blkModal) blkModal.remove();
    }
}
</script>

<script>
// ── Leader / Base use landscape (wide) aspect ratio ───────────────────────────
window.RenderCardHTML = function(cardNumber, folder, maxHeight, action, showHover,
    overlay, borderColor, counters, actionDataOverride, id, rotate,
    lifeCounters, defCounters, atkCounters, controller, restriction,
    isBroken, onChain, isFrozen, gem, landscape, epicActionUsed,
    heatmapFunction, heatmapColorMap, mzId, overlayTypes, overlayDescriptorsJSON, hasForce) {
    // Force landscape ratio for leader and base zone cards
    if (mzId && /^(my|their)(Leader|Base)-/.test(mzId)) {
        landscape = 1;
    }
    return Card(cardNumber, folder, maxHeight, action, showHover,
        overlay, borderColor, counters, actionDataOverride, id, rotate,
        lifeCounters, defCounters, atkCounters, controller, restriction,
        isBroken, onChain, isFrozen, gem, landscape, epicActionUsed,
        heatmapFunction, heatmapColorMap, mzId, overlayTypes, overlayDescriptorsJSON, hasForce);
};
</script>

<script>
// ── Cosmetics (Feature C): apply the viewer's window.SWU_COSMETICS to the board ──────
function ApplyCosmeticBackground() {
  try {
    var c = window.SWU_COSMETICS; if (!c || !c.background) return;
    // Set a CSS var the board rules reference, preserving their gradient layers.
    // One var covers both layouts (desktop .swu-board-bg and mobile #swuMobileRoot);
    // SWU_COSMETICS.background is already the correct (mobile/desktop) variant.
    document.documentElement.style.setProperty('--swu-cos-board', "url('" + c.background + "')");
  } catch (e) {}
}
if (document.readyState !== 'loading') ApplyCosmeticBackground();
else document.addEventListener('DOMContentLoaded', ApplyCosmeticBackground);

// Card backs: rewrite each face-down CardBack image to its OWNING side's back.
function ApplyCosmeticCardBacks() {
  var c = window.SWU_COSMETICS; if (!c) return;
  var imgs = document.querySelectorAll("img[src*='/concat/CardBack.webp'], img[src$='CardBack.webp']");
  for (var i = 0; i < imgs.length; i++) {
    var img = imgs[i];
    var owner = img.closest("[id^='my']") ? 'my' : (img.closest("[id^='their']") ? 'their' : null);
    if (!owner) continue;
    var back = owner === 'my' ? c.myCardBack : c.theirCardBack;
    if (back && img.getAttribute('data-cos-back') !== back) {
      img.src = back;
      img.setAttribute('data-cos-back', back);   // idempotent guard (prevents observer loops)
    }
  }
}
// Re-apply ALL cosmetics together. Background + playmats are CSS-based and were applied once;
// the game re-renders the board via AJAX, so we re-apply all three on every board mutation
// (and on load) — same resilience the card backs already had. All three are idempotent.
function ApplyAllCosmetics() {
  ApplyCosmeticBackground();   // hoisted
  ApplyCosmeticPlaymats();     // hoisted
  ApplyCosmeticCardBacks();
}
(function () {
  function start() {
    ApplyAllCosmetics();
    if (!window.MutationObserver) return;
    var pending = false;
    var obs = new MutationObserver(function () {
      if (pending) return; pending = true;
      requestAnimationFrame(function () { pending = false; ApplyAllCosmetics(); });
    });
    obs.observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState !== 'loading') start();
  else document.addEventListener('DOMContentLoaded', start);
  window.addEventListener('load', ApplyAllCosmetics);
})();

// Per-side playmats (desktop): paint each side's mat, honoring the viewer's Show-playmats toggle.
function ApplyCosmeticPlaymats() {
  try {
    var c = window.SWU_COSMETICS; if (!c) return;
    var show = true;
    if (window.TCGSettings && typeof window.TCGSettings.get === 'function') {
      show = window.TCGSettings.get('ShowPlaymats', { rootName: 'SWUSim', type: 'boolean', defaultValue: true }) !== false;
    }
    var top = document.querySelector('.swu-playmat-top');   // opponent side
    var bot = document.querySelector('.swu-playmat-bot');   // my side
    function paint(el, asset) {
      if (!el) return;
      if (show && asset) { el.style.backgroundImage = "url('" + asset + "')"; el.style.display = 'block'; }
      else { el.style.display = 'none'; }
    }
    paint(bot, c.myPlaymat);
    paint(top, c.theirPlaymat);
  } catch (e) {}
}
if (document.readyState !== 'loading') ApplyCosmeticPlaymats();
else document.addEventListener('DOMContentLoaded', ApplyCosmeticPlaymats);
window.ApplyCosmeticPlaymats = ApplyCosmeticPlaymats;   // re-callable when the toggle changes
</script>

<!-- ── In-game Settings hub (gear menu) ─────────────────────────────────────── -->
<style>
  .swu-header-right { display: flex; align-items: center; gap: 8px; }
  .swu-gear-btn { background: transparent; border: 0; color: rgba(140,210,255,0.85); font-size: 20px;
    line-height: 1; cursor: pointer; padding: 2px 4px; filter: drop-shadow(0 0 4px rgba(140,210,255,0.4));
    transition: transform 140ms ease, color 140ms ease; }
  .swu-gear-btn:hover { color: #fff; transform: rotate(40deg); }
  .swu-settings-overlay { position: fixed; inset: 0; z-index: 10001; display: flex;
    align-items: center; justify-content: center; background: rgba(4,10,18,0.6);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
  .swu-settings-panel { width: min(92vw, 360px); background: rgba(10,22,36,0.97);
    border: 1px solid rgba(140,210,255,0.35); border-radius: 12px;
    box-shadow: 0 18px 50px rgba(0,0,0,0.6); color: #dceaf7; overflow: hidden; }
  .swu-settings-head { display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; border-bottom: 1px solid rgba(140,210,255,0.2);
    font: 700 16px/1 var(--swu-font-label, sans-serif); color: #bfe3ff; letter-spacing: 0.02em; }
  .swu-settings-close { background: transparent; border: 0; color: #9fc4e0; font-size: 16px; cursor: pointer; }
  .swu-settings-close:hover { color: #fff; }
  .swu-settings-section { padding: 14px 16px; }
  .swu-settings-section-title { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;
    color: rgba(140,210,255,0.7); margin-bottom: 8px; }
  .swu-settings-row { display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 6px 0; font-size: 14px; cursor: pointer; }
  .swu-settings-row input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; }
  .swu-settings-link { display: inline-block; margin-top: 8px; color: #8cd2ff; font-size: 13px; text-decoration: none; }
  .swu-settings-link:hover { text-decoration: underline; }
</style>
<div id="swuSettingsOverlay" class="swu-settings-overlay" style="display:none;" onclick="if(event.target===this)swuCloseSettings()">
  <div class="swu-settings-panel" role="dialog" aria-modal="true">
    <div class="swu-settings-head"><span>Settings</span>
      <button class="swu-settings-close" onclick="swuCloseSettings()" aria-label="Close">&#10005;</button></div>
    <div class="swu-settings-section">
      <div class="swu-settings-section-title">Cosmetics</div>
      <label class="swu-settings-row"><span>Show playmats</span>
        <input type="checkbox" id="swuSetShowPlaymats"></label>
      <a class="swu-settings-link" href="/TCGEngine/SharedUI/Sites/SWUSim/Profile.php" target="_blank">Change cosmetics on Profile &#8599;</a>
    </div>
  </div>
</div>
<script>
  function swuOpenSettings() {
    var ov = document.getElementById('swuSettingsOverlay'); if (!ov) return;
    var t = document.getElementById('swuSetShowPlaymats');
    if (t && window.TCGSettings) t.checked = window.TCGSettings.get('ShowPlaymats', { rootName:'SWUSim', type:'boolean', defaultValue:true }) !== false;
    ov.style.display = 'flex';
  }
  function swuCloseSettings() { var ov = document.getElementById('swuSettingsOverlay'); if (ov) ov.style.display = 'none'; }
  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'swuSetShowPlaymats') {
      if (window.TCGSettings) window.TCGSettings.set('ShowPlaymats', e.target.checked, { rootName:'SWUSim', type:'boolean' });
      if (typeof window.ApplyCosmeticPlaymats === 'function') window.ApplyCosmeticPlaymats();
    }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') swuCloseSettings(); });
</script>

