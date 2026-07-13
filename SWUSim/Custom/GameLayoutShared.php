<?php
// GameLayoutShared.php — behaviour shared by GameLayout.php (desktop/tablet) and
// GameLayoutMobile.php (phones). Pure JS that targets engine slot IDs, so both
// layouts reuse it verbatim. Included within InitialLayout.php scope so the PHP
// interpolations below ($playerID, pilot-leader list) resolve.
?>
<!-- SWUSim in-game uses the cyan HUD theme. The design-system board stack (tokens + button +
     switch + hud.tokens overlay) is now emitted centrally by NextTurn.php from the SiteDef
     `theme` key, so no per-board <link> is needed here (removed to avoid a duplicate load). -->
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

/* ── Arena HUD darkening overlay (shared: desktop + mobile) ─────────────────────
   Blue cyan-interface wash inside each arena box, clipped to the box (behind the
   cards, below the corner brackets). Kept here so both layouts share one definition.
     • Desktop box = .swu-arena-bg — a z-index:29 fixed frame; the card columns are
       separate z-index:30 elements, so this ::after lands behind them.
     • Mobile box  = .swu-m-arena-col — an isolated stacking context whose card
       content is lifted to z-index:1 (see GameLayoutMobile.php). */
.swu-arena-bg::after,
.swu-m-arena-col::after {
    content: ''; position: absolute; inset: 0; z-index: 0; pointer-events: none;
    border-radius: 4px;
    /* Theme-driven arena wash: faint accent tint over near-black (was hardcoded cyan-HUD blue). */
    background: linear-gradient(180deg, rgba(var(--accent-rgb),0.12), rgba(0,0,0,0.33));
}
/* Action-available glows for the Leader / Base / Resource / Discard slots + per-card Smuggle /
   discard highlights. Applied by refreshActionGlows / refreshResourceCardGlows /
   refreshDiscardCardGlows in this file's JS — kept HERE (not desktop-only GameLayout.php) so the
   mobile layout styles them too (the class was added but never styled → leader/base/etc. showed
   no glow on phones). */
#myBaseSlot.has-action,
#swuMyResCount.has-action {
    box-shadow: 0 0 14px 3px rgba(60,220,90,0.70), 0 0 4px 1px rgba(60,220,90,0.40);
    border-color: rgba(60,220,90,0.75) !important;
    transition: box-shadow 0.3s ease, border-color 0.3s ease;
}
/* Leader action glow: on the specific leader CARD span (per-index in Twin Suns), so only the leader
   with an available action glows — not both twins. The slot no longer carries .has-action. */
#myLeaderSlot [data-mzid].has-action {
    box-shadow: 0 0 14px 3px rgba(60,220,90,0.70), 0 0 4px 1px rgba(60,220,90,0.40);
    border-radius: 7px;
    transition: box-shadow 0.3s ease;
}
#myResourcesSlot .smuggle-available {
    box-shadow: 0 0 10px 2px rgba(60,220,90,0.65), 0 0 3px 1px rgba(60,220,90,0.35);
    border-radius: 4px;
    transition: box-shadow 0.3s ease;
}
#myDiscardSlot .discard-playable,
#theirDiscardSlot .discard-playable {
    box-shadow: 0 0 8px 3px var(--accent-strong, #f0c040), inset 0 0 4px var(--accent-strong, #f0c040);
    border-radius: 4px;
}
#myDiscardSlot.has-action,
#theirDiscardSlot.has-action {
    box-shadow: 0 0 14px 3px rgba(240,192,64,0.70), 0 0 4px 1px rgba(240,192,64,0.40);
    border-color: rgba(240,192,64,0.75) !important;
    transition: box-shadow 0.3s ease, border-color 0.3s ease;
}

/* ── Twin Suns table shell (order strip + pair-switcher + home strips) — all hidden at ≤2 seats ── */
/* Order strip — fixed top-center row of seat chips (clockwise SeatOrder). */
.swu-order-strip {
    position: fixed; top: 4px; left: 50%; transform: translateX(-50%);
    z-index: 40; display: flex; gap: 6px; padding: 4px 8px;
    background: var(--swu-surface, rgba(10,20,30,0.82)); border: 1px solid var(--swu-border, #2a3a4a);
    border-radius: 8px; font: 600 11px/1 var(--swu-font-label, sans-serif); pointer-events: none;
}
.swu-order-chip {
    display: flex; align-items: center; gap: 4px; padding: 3px 7px; border-radius: 5px;
    color: var(--text-muted, #aab); background: rgba(255,255,255,0.05); border: 1px solid transparent;
    letter-spacing: 0.04em;
}
.swu-order-chip.is-you             { font-weight: 800; }
.swu-order-chip.state-active       { color: #eef; border-color: rgba(60,220,90,0.9); box-shadow: 0 0 10px 1px rgba(60,220,90,0.55); }
.swu-order-chip.state-took-counter { color: #f0c040; border-color: rgba(240,192,64,0.7); }
.swu-order-chip.state-waiting      { opacity: 0.7; }
.swu-order-chip .swu-order-dot     { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* Pair-switcher — carousel side arrows, vertically centered: ▶ on the RIGHT edge advances to the next
   view; ◀ on the LEFT edge (shown only when there IS a view to the left) goes back. Each is shown/hidden
   by swuRenderPairNav based on the current index. .swu-pair-nav is a display:contents wrapper used only
   for the 2-player hide (its children are individually fixed to the board edges). */
.swu-pair-nav { display: contents; }
.swu-pair-arrow { position: fixed; top: 50%; transform: translateY(-50%); z-index: 42;
    width: 46px; height: 78px; border-radius: 10px; cursor: pointer;
    background: var(--swu-surface, rgba(10,20,30,0.82)); border: 1px solid var(--swu-border, #2a3a4a);
    color: var(--accent-strong, #f0c040); font-size: 30px; line-height: 1;
    align-items: center; justify-content: center; }
.swu-pair-arrow:hover { border-color: var(--accent-strong, #f0c040); background: rgba(10,20,30,0.95); }
/* Sit inside the board, nudged in from the edges — the right arrow clears the chat/log sidebar (var from
   GameLayout; 0 on mobile) and sits just inside the right turn-indicator spike. */
#swuPairPrev { left: 40px; }
#swuPairNext { right: calc(var(--swu-sidebar-w, 0px) + 40px); }
/* Cross-view targeting: a glowing count pill on an arrow = that many legal targets on view(s) that
   way during an active targeting decision. Anchored to the fixed arrow (its own containing block). */
.swu-target-badge { position: absolute; top: -7px; right: -7px; min-width: 20px; height: 20px;
    padding: 0 5px; border-radius: 10px; background: #2ecc71; color: #06210f;
    font: 700 13px/20px var(--swu-font-label, system-ui, sans-serif); text-align: center;
    box-shadow: 0 0 9px 2px rgba(46,204,113,0.85); pointer-events: none; }

/* Read-only / spectating a board that isn't yours (4-player "other pair"): block board clicks (via a
   capture-phase swallow in JS — this CSS just dims the action affordances), hide your action HUD, and
   show a badge. Hover-to-inspect still works. */
body.swu-spectating #swuPassControl { display: none !important; }
.swu-spectate-badge { position: fixed; top: 48px; left: 50%; transform: translateX(-50%); z-index: 43;
    display: none; padding: 3px 12px; border-radius: 999px;
    background: rgba(222,72,72,0.85); color: #fff; font: 700 11px/1 var(--swu-font-label, sans-serif);
    letter-spacing: 0.06em; pointer-events: none; }
body.swu-spectating .swu-spectate-badge { display: block; }

/* 3-player home view — two minimal opponent status strips across the top (gateways into their matchup). */
.swu-home-strips { position: fixed; top: 52px; left: 0; right: 0; z-index: 39;
    display: flex; gap: 8px; justify-content: center; padding: 0 12px; pointer-events: none; }
.swu-home-strip { flex: 1 1 0; max-width: 46%; pointer-events: auto; cursor: pointer;
    display: flex; align-items: center; gap: 10px; padding: 6px 10px;
    background: var(--swu-surface, rgba(10,20,30,0.85)); border: 1px solid var(--swu-border, #2a3a4a);
    border-radius: 8px; font: 600 11px/1.2 var(--swu-font-label, sans-serif); color: var(--text-muted,#aab); }
.swu-home-strip:hover { border-color: var(--accent-strong, #f0c040); }
.swu-home-strip .hs-seat { font-weight: 800; color: #eef; }
.swu-home-strip .hs-base { color: #f0c040; }
.swu-home-strip .hs-leaders { display: flex; gap: 4px; }
.swu-home-strip .hs-leader { padding: 1px 5px; border-radius: 4px; background: rgba(255,255,255,0.06); }
.swu-home-strip .hs-leader.is-exhausted { opacity: 0.5; }
.swu-home-strip .hs-leader.is-deployed  { color: #7fd; }

/* ── Initiative token palette = turn-indicator palette ───────────────────────────
   Green when the initiative sits on MY side, red on the opponent's — matching the
   turn-edge glow (green rgba(64,214,110) / red rgba(222,72,72)). updateInitiative()
   adds .is-mine / .is-theirs; one --init-rgb recolors the cyan token in BOTH layouts.
   No class yet (state unset) → falls back to the layout's base cyan. */
.swu-init-control.is-mine   { --init-rgb: var(--turn-mine-rgb); }
.swu-init-control.is-theirs { --init-rgb: var(--turn-theirs-rgb); }
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
/* ── Twin Suns: two leaders share the slot width (side-by-side square concat crops) ──────
   The single-leader rules above force each leader card to width:100%, so a SECOND leader
   wraps to a new row (stacks). When the wrapper holds a second leader (myLeader-1 /
   theirLeader-1), lay both in one nowrap row and split the width so the square concat crops
   sit side-by-side. A single-leader wrapper never matches :has(), so 2P is unchanged. */
#myLeaderWrapper:has([data-mzid="myLeader-1"]),
#theirLeaderWrapper:has([data-mzid="theirLeader-1"]) { display: flex !important; }
#myLeaderWrapper:has([data-mzid="myLeader-1"]) > span,
#theirLeaderWrapper:has([data-mzid="theirLeader-1"]) > span {
    display: flex !important; flex-wrap: nowrap !important; gap: 4px; width: 100%;
}
#myLeaderWrapper:has([data-mzid="myLeader-1"]) [data-mzid],
#theirLeaderWrapper:has([data-mzid="theirLeader-1"]) [data-mzid] {
    width: 50% !important; flex: 1 1 0 !important; min-width: 0 !important;
}
#myLeaderWrapper:has([data-mzid="myLeader-1"]) [data-mzid] img,
#theirLeaderWrapper:has([data-mzid="theirLeader-1"]) [data-mzid] img {
    width: 100% !important; height: auto !important; object-fit: cover !important;
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

/* ── SWUSim decision-button sweep REMOVED (Tier 2 / Phase 2) ────────────────────
   The MZChoose/MZMultiChoose/MZSplitAssign + inline-multi buttons now carry the shared
   .btn/.btn-primary/.btn-secondary/.btn-danger/.btn-success classes (Tier 1), so their
   HUD skin comes from button.css + hud.tokens.css (loaded in-game by NextTurn). The old
   !important overrides here were redundant and have been deleted (see spec §4.6). Only the
   selection-message LAYOUT below is kept (not button skin). */

/* (inline-multi button skin removed — now .btn/.btn-primary/.btn-secondary from Tier 1.)
   Message gets its own first line; the "N selected / M max" counter + controls drop
   to a second line (msgSpan is the panel's first child). */
#selection-message:has(#inline-multi-confirm) > span:first-child { flex-basis: 100% !important; }
#selection-message:has(#inline-multi-confirm) #inline-multi-counter { margin-top: 2px !important; }
/* Override the engine's hardcoded inline navy gradient on the selection prompt so the panel
   fill follows the theme scrim (sandy dark-brown under petranaki-hud, cyan-navy under hud). */
#selection-message { background: var(--panel-scrim) !important; }
/* Same for the mz-choose popup modal's navy backdrop — recolor ONLY the modal's own background
   layer (keeping its parchment sheen). Its card buttons/images are children that render above
   this, so unlike the blanket panel fill this never covers content (hence the earlier exclusion
   doesn't apply to a plain background swap). */
#mzchoose-popup > div {
  background: linear-gradient(180deg, rgba(244,236,219,0.12), rgba(255,255,255,0.02)), var(--panel-scrim) !important;
}
/* Game-over inner panels (stats container + the Save-Replay box) carry their own hardcoded
   navy from the engine/replay stylesheets. Recolor them to a subtle dark inset so they read as
   recessed regions over the now-themed #game-over-overlay, on any theme. */
#game-over-stats,
.match-replay-stats-actions { background: rgba(0,0,0,0.20) !important; }

/* (End-game button skin removed — #game-over buttons now carry .btn.btn-primary from Tier 1,
   so their HUD look comes from button.css + hud.tokens.) */

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
    background: var(--panel-scrim) !important;
    border: 1px solid var(--border) !important;
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
    color: var(--text) !important;
    text-shadow: 0 0 30px rgba(var(--accent-rgb),0.85), 0 0 80px rgba(var(--accent-rgb),0.50), 0 4px 12px rgba(0,0,0,0.8) !important;
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
    filter: drop-shadow(0 0 6px rgba(var(--accent-rgb),0.55)) !important;   /* theme-accent HUD glow */
}
/* Cool sci-fi border: a glowing accent chamfered rim around the off-white fill (the off-white
   ::after is inset a touch more so the edge reads as a crisp ~2.5px HUD keyline). The white fill
   is the deliberate Bo1/Bo3 TOGGLE look (distinct from the primary Rematch); only cyan → tokens. */
#game-over-buttons #swu-bestof-btn::before { background: var(--accent); }   /* accent border */
#game-over-buttons #swu-bestof-btn::after  {
    background: #dde2e9 !important; inset: 2.5px !important;                                     /* off-white fill */
    clip-path: polygon(5.5px 0, 100% 0, 100% calc(100% - 5.5px), calc(100% - 5.5px) 100%, 0 100%, 0 5.5px) !important;
}
#game-over-buttons #swu-bestof-btn:not(:disabled):hover {
    color: #1f1f1f !important; filter: drop-shadow(0 0 12px rgba(var(--accent-rgb),0.85)) !important;
}
#game-over-buttons #swu-bestof-btn:not(:disabled):hover::before { background: var(--accent-strong); }
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
    border: 1px solid var(--accent);
    box-shadow: 0 6px 22px rgba(0,0,0,0.55), 0 0 10px rgba(var(--accent-rgb),0.35);
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
    /* Override the engine's hardcoded inline navy (#0D1B2A) so the panel fill follows the
       theme's panel scrim — cyan-navy under hud, sandy dark-brown under petranaki-hud. */
    background: var(--panel-scrim) !important;
    border: 1px solid rgba(var(--accent-rgb),0.30) !important; border-radius: 6px !important;
    box-shadow: 0 0 10px rgba(var(--accent-rgb),0.18), inset 0 0 26px rgba(var(--accent-rgb),0.06), 0 14px 44px rgba(0,0,0,0.6) !important;
}
#topdecksearch-panel > div::before, #scry-panel > div::before, #revealarrange-panel > div::before,
#yesno-decision-modal > div::before, .optchoose-banner::before {
    content: '' !important; position: absolute !important; inset: -1px !important; pointer-events: none !important;
    background:
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  top    / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  top    / 2px  22px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right top    / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right top    / 2px  22px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  bottom / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  bottom / 2px  22px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right bottom / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right bottom / 2px  22px no-repeat !important;
    filter: drop-shadow(0 0 4px rgba(var(--accent-rgb),0.55)) !important;
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
    color: var(--text) !important; text-transform: uppercase !important; letter-spacing: 0.12em !important;
    text-shadow: 0 0 6px rgba(var(--accent-rgb),0.5) !important;
    filter: drop-shadow(0 0 5px rgba(var(--accent-rgb),0.45)) !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
#topdecksearch-panel button::before, #scry-panel button::before, #revealarrange-panel button::before,
#yesno-decision-modal button::before, .optchoose-btn::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
    background: var(--accent) !important;
}
#topdecksearch-panel button::after, #scry-panel button::after, #revealarrange-panel button::after,
#yesno-decision-modal button::after, .optchoose-btn::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
    background: var(--btn-fill) !important;
}
#topdecksearch-panel button:hover, #scry-panel button:hover, #revealarrange-panel button:hover,
#yesno-decision-modal button:hover, .optchoose-btn:hover {
    color: #fff !important; filter: drop-shadow(0 0 10px rgba(var(--accent-rgb),0.65)) !important; transform: translateY(-1px) !important;
}
#topdecksearch-panel button:hover::before, #scry-panel button:hover::before, #revealarrange-panel button:hover::before,
#yesno-decision-modal button:hover::before, .optchoose-btn:hover::before {
    background: var(--accent-strong) !important;
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
/* While the "Resolve whose abilities first?" prompt is up, lift the effect stack ABOVE the modal's
   dark backdrop (z 5000) and pin it just above the centered prompt, so the two trigger cards are
   bright and uncovered while the rest of the board stays dimmed. Toggled by the ShowYesNoDecisionPopup
   wrapper. id+class + !important beats the drag's .is-custom-position and its inline position. */
#EffectStackSlot.swu-es-order-front {
    z-index: 5001 !important;
    top: 20vh !important; bottom: auto !important; left: 50% !important;
    transform: translateX(calc(-50% - var(--swu-sidebar-w)/2)) !important;
    pointer-events: none !important; /* context only during the choice — no card clicks/zoom */
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
    border: 1px solid rgba(var(--accent-rgb),0.30) !important; border-radius: 6px !important;
    box-shadow: 0 0 10px rgba(var(--accent-rgb),0.18), inset 0 0 26px rgba(var(--accent-rgb),0.06), 0 14px 44px rgba(0,0,0,0.6) !important;
}
/* NUMBERCHOOSE is a fixed bottom bar (like the OPTIONCHOOSE banner) — frame it in
   place; do NOT force position:relative (that would drop it out of fixed). */
.numchoose-banner {
    border: 1px solid rgba(var(--accent-rgb),0.30) !important; border-radius: 6px !important;
    box-shadow: 0 0 10px rgba(var(--accent-rgb),0.18), inset 0 0 26px rgba(var(--accent-rgb),0.06), 0 4px 24px rgba(0,0,0,0.5) !important;
}
.twosided-slider-panel::before, .mzmodal-panel::before, .mzrearrange-modal::before,
.namecard-modal::before, .numchoose-banner::before {
    content: '' !important; position: absolute !important; inset: -1px !important; pointer-events: none !important;
    background:
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  top    / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  top    / 2px  22px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right top    / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right top    / 2px  22px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  bottom / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) left  bottom / 2px  22px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right bottom / 22px 2px no-repeat,
        linear-gradient(var(--accent-strong),var(--accent-strong)) right bottom / 2px  22px no-repeat !important;
    filter: drop-shadow(0 0 4px rgba(var(--accent-rgb),0.55)) !important;
}
/* Action / confirm / stepper buttons → chamfered cyan HUD (closed two-pseudo). */
.numchoose-confirm, .numchoose-btn-minus, .numchoose-btn-plus, .twosided-slider-confirm,
.mzrearrange-btn-submit, .mzrearrange-btn-reset, .namecard-modal button,
#selection-message > button:not([id]) {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important;
    color: var(--text) !important; text-transform: uppercase !important; letter-spacing: 0.12em !important;
    text-shadow: 0 0 6px rgba(var(--accent-rgb),0.5) !important;
    filter: drop-shadow(0 0 5px rgba(var(--accent-rgb),0.45)) !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
.numchoose-confirm::before, .numchoose-btn-minus::before, .numchoose-btn-plus::before, .twosided-slider-confirm::before,
.mzrearrange-btn-submit::before, .mzrearrange-btn-reset::before, .namecard-modal button::before,
#selection-message > button:not([id])::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
    background: var(--accent) !important;
}
.numchoose-confirm::after, .numchoose-btn-minus::after, .numchoose-btn-plus::after, .twosided-slider-confirm::after,
.mzrearrange-btn-submit::after, .mzrearrange-btn-reset::after, .namecard-modal button::after,
#selection-message > button:not([id])::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
    background: var(--btn-fill) !important;
}
.numchoose-confirm:hover, .numchoose-btn-minus:hover, .numchoose-btn-plus:hover, .twosided-slider-confirm:hover,
.mzrearrange-btn-submit:hover, .mzrearrange-btn-reset:hover, .namecard-modal button:hover,
#selection-message > button:not([id]):hover {
    color: #fff !important; filter: drop-shadow(0 0 10px rgba(var(--accent-rgb),0.65)) !important; transform: translateY(-1px) !important;
}
.numchoose-confirm:hover::before, .numchoose-btn-minus:hover::before, .numchoose-btn-plus:hover::before, .twosided-slider-confirm:hover::before,
.mzrearrange-btn-submit:hover::before, .mzrearrange-btn-reset:hover::before, .namecard-modal button:hover::before,
#selection-message > button:not([id]):hover::before {
    background: var(--accent-strong) !important;
}
/* "Waiting for the other player…" — center it over the board (both bases), not pinned above the
   hand. !important beats the shared JS's per-frame inline top/bottom (_positionMessageNearAnchor).
   left:50% comes from the base rule; the -sidebar/2 X-shift matches how the bases/midbar center over
   the BOARD (which is inset by the right sidebar) — see GameLayout.php's base-slot rule. Fallback 0px
   keeps it viewport-centered on mobile (no sidebar). */
#turn-miasma-message {
    top: 50% !important;
    bottom: auto !important;
    transform: translate(calc(-50% - var(--swu-sidebar-w, 0px) / 2), -50%) !important;
}
/* The turn-miasma overlay (ambient turn indicator + the "Waiting for the other player" pill)
   must sit BELOW decision modals like the mulligan #yesno-decision-modal (z-index 5000), so the
   waiting pill renders behind the prompt instead of over its YES/NO buttons. Shared default is
   9998 (Core/Styles/ScreenAnimations.css); lowered here (SWUSim-scoped) to just under the modal
   tier. Still well above the board, so the turn glyphs stay visible during normal play. */
#turn-miasma-overlay { z-index: 4999 !important; }
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

        // Twin Suns (Phase 4) counter HUD. Show the Blast/Plan buttons only when this seat may take each
        // counter this round; enforce "a player may only pass if no counter is available to take" (CR §12.5)
        // by disabling the Pass button while a counter is available. The keys are absent/false in a 2-player
        // game (SeatCountForGame() <= 2 → SWUComputeActionsData leaves them false), so both buttons stay
        // hidden and Pass is never disabled — premier is unchanged.
        var _ad = window.myActionsData || {};
        var _blastBtn = document.getElementById('swuBlastBtn');
        var _planBtn  = document.getElementById('swuPlanBtn');
        if (_blastBtn) _blastBtn.hidden = !(canAct && _ad.blastAvailable);
        if (_planBtn)  _planBtn.hidden  = !(canAct && _ad.planAvailable);
        var _passBtn = document.getElementById('swuPassBtn');
        if (_passBtn) {
            var _mustCounter = canAct && (_ad.blastAvailable || _ad.planAvailable);
            _passBtn.disabled = !!_mustCounter;
            _passBtn.title = _mustCounter ? 'You must take a counter before passing' : 'Pass (Space)';
        }
    }

    window.swuTakeInitiative = function () {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('InitiativeCounter-0!CustomInput!TakeInitiative'));
    };

    window.swuPassAction = function () {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('myHealth-0!CustomInput!Pass'));
    };

    // Twin Suns (Phase 4): take the blast / plan counter. Routes to CustomInput's BlastCounter/PlanCounter
    // case → SWUTakeCounter (1 dmg to each enemy base / draw-1-bottom-1); taking a counter is a pass.
    window.swuTakeBlastCounter = function () {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('BlastCounter-0!CustomInput!TakeCounter'));
    };
    window.swuTakePlanCounter = function () {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('PlanCounter-0!CustomInput!TakeCounter'));
    };
    // Twin Suns pass rule (CR §12.5): a seat may only pass if no counter is available to take. False in
    // 2-player (the keys are absent) so premier passing is unchanged.
    window.swuMustTakeCounter = function () {
        var ad = window.myActionsData || {};
        return !!(ad.blastAvailable || ad.planAvailable);
    };

    // Hotseat: one person plays both seats from one browser (shared authKey). Switch reloads the
    // page as the OTHER seat. No-op in non-hotseat games.
    window.SWUIsHotseat = <?php echo (function_exists('SWUGameMode') && SWUGameMode() === 'hotseat') ? 'true' : 'false'; ?>;
    window.swuSwitchPlayer = function () {
        if (!window.SWUIsHotseat) return;
        var url = new URL(window.location.href);
        var cur = parseInt(url.searchParams.get('playerID') || '1', 10);
        url.searchParams.set('playerID', cur === 1 ? '2' : '1');
        window.location.href = url.toString();
    };

    // ── Goldfish ⚗ Practice menu — god-mode helpers acting on YOUR (P1) board. Goldfish only;
    // the server re-checks the mode, so these are inert (and the UI absent) in real games. ──
    window.SWUIsGoldfish = <?php echo (function_exists('SWUGameMode') && SWUGameMode() === 'goldfish') ? 'true' : 'false'; ?>;
    (function () {
        if (!window.SWUIsGoldfish) return;
        function send(action) {
            SubmitInput('10001', '&cardID=' + encodeURIComponent('GfPractice-0!CustomInput!' + action));
        }
        function posInt(id) {
            var el = document.getElementById(id);
            var n = el ? parseInt(el.value, 10) : 0;
            return (isFinite(n) && n > 0) ? n : 0;
        }
        window.swuGfToggle = function () {
            var p = document.getElementById('swuGfPanel');
            if (p) p.classList.toggle('is-open');
        };
        window.swuGfBaseDamage = function () { var n = posInt('swuGfBaseDmgInput'); if (n > 0) send('BaseDamage:' + n); };
        window.swuGfDamageUnits = function () { var n = posInt('swuGfUnitDmgInput'); if (n > 0) send('DamageUnits:' + n); };
        window.swuGfDefeatUnit = function () { send('DefeatUnit'); };
        window.swuGfBounceUnit = function () { send('BounceUnit'); };
        // Close the panel on an outside click.
        document.addEventListener('click', function (e) {
            var panel = document.getElementById('swuGfPanel'), btn = document.getElementById('swuGfBtn');
            if (!panel || !panel.classList.contains('is-open')) return;
            if (panel.contains(e.target) || (btn && btn.contains(e.target))) return;
            panel.classList.remove('is-open');
        });
    })();

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
    // The centered popup is shown ONLY when the player must actively PICK a trigger from it — i.e.
    // there's an active selection whose allowed zones include EffectStack (a "choose trigger to
    // resolve" MZCHOOSE, which only happens when you control 2+ simultaneous triggers and must order
    // them). In every other case it stays HIDDEN: a lone trigger auto-resolves now, board-target
    // pings cover the board, and while the OPPONENT resolves their trigger you have nothing to click.
    // Previously it showed whenever the stack had entries, so it flashed the trigger cards on/off as
    // they auto-resolved. Updates are coalesced through a short settle so a transient
    // populate→clear (or a full re-render's empty→refill) never flickers.
    var ES_SETTLE_MS = 130;
    var _esTimer = null;
    function _esShouldShow(el) {
        if (!el || el.querySelector('[id$="-0"]') === null) return false; // no entries → nothing to show
        // Show while the "Resolve whose abilities first?" prompt is open, so the two trigger cards are
        // visible context for the Yours/Theirs choice (the prompt is dropped below the stack, see the
        // ShowYesNoDecisionPopup wrapper). Self-clears when that modal is removed.
        if (document.querySelector('#yesno-decision-modal[data-swu-order]')) return true;
        // Otherwise only when the player must PICK a trigger from it (an EffectStack MZCHOOSE).
        var sm = window.SelectionMode;
        return !!(sm && sm.active && Array.isArray(sm.allowedZones) && sm.allowedZones.length
            && sm.allowedZones.some(function(z){ return z && z.zone === 'EffectStack'; }));
    }
    // Restore a slot we lifted to <body> for the order choice back onto the board.
    function _esRestoreParent(el) {
        if (!el || !el._swuOrigParent) return;
        el.classList.remove('swu-es-order-front');
        if (el._swuOrigNext && el._swuOrigNext.parentNode === el._swuOrigParent) el._swuOrigParent.insertBefore(el, el._swuOrigNext);
        else el._swuOrigParent.appendChild(el);
        el._swuOrigParent = null; el._swuOrigNext = null;
    }
    window.UpdateEffectStackVisibility = function() {
        if (_esTimer) return; // a settle is already scheduled — it will read the final state
        _esTimer = setTimeout(function() {
            _esTimer = null;
            var el = document.getElementById('EffectStackSlot'); if (!el) return;
            // Defensive: if the slot is still lifted but the order prompt is gone (e.g. an undo removed
            // it without going through the button handler), put it back before applying visibility.
            if (el._swuOrigParent && !document.querySelector('#yesno-decision-modal[data-swu-order]')) _esRestoreParent(el);
            el.style.display = _esShouldShow(el) ? '' : 'none';
        }, ES_SETTLE_MS);
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
        swuInitPairSwitcher();   // sets window.swuSpectating BEFORE the glows read it
        updatePhaseTrack(); updateInitiative(); updateRound(); refreshActionGlows();
        swuRenderOrderStrip();
        swuRenderHomeStrips();
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

    function showLeaderMenu(cardID, abilityAvail, deployAvail, leaderIndex) {
        var idx = (leaderIndex === undefined || leaderIndex === null) ? 0 : leaderIndex;
        var existing = document.getElementById('swuLeaderMenu');
        if (existing) { existing.remove(); return; }
        var menu = document.createElement('div');
        menu.id = 'swuLeaderMenu';
        menu.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9998;' +
            'background:var(--surface-raised,#0d1b2a);border:2px solid var(--border,#c8971e);border-radius:var(--radius,10px);padding:24px 32px;' +
            'text-align:center;box-shadow:0 0 30px var(--glow,rgba(200,151,30,0.35));min-width:220px;' +
            'backdrop-filter:blur(10px) saturate(110%);-webkit-backdrop-filter:blur(10px) saturate(110%);' +
            'font-family:var(--swu-font-ui,sans-serif);';
        var isPilot = (window.SWU_PILOT_LEADERS || []).indexOf(cardID) !== -1;
        var btnStyle = 'width:100%;padding:8px 16px;background:var(--btn-plain-fill,#1e3a5f);border:1px solid var(--border,#888);' +
            'border-radius:var(--radius,5px);color:var(--btn-text,#eee);cursor:pointer;font-size:13px;margin-bottom:2px;';
        var html = '<div style="font-size:15px;font-weight:bold;color:var(--accent-strong,#f0c040);margin-bottom:16px;">Leader Actions</div>';
        if (abilityAvail) {
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'LeaderAbility\',' + idx + ')">Leader Ability</button></div>';
        }
        if (deployAvail && !isPilot) {
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'DeployLeader:Unit\',' + idx + ')">Deploy Leader</button></div>';
        }
        if (deployAvail && isPilot) {
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'DeployLeader:Unit\',' + idx + ')">Deploy as Unit</button></div>';
            html += '<div style="margin-bottom:8px;"><button style="' + btnStyle + '" ' +
                'onclick="swuDoLeaderAction(\'DeployLeader:Pilot\',' + idx + ')">Deploy as Pilot</button></div>';
        }
        html += '<div><button style="width:100%;padding:6px 16px;background:transparent;border:1px solid var(--border,#555);' +
            'border-radius:var(--radius,5px);color:var(--text-muted,#aaa);cursor:pointer;font-size:12px;" ' +
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

    window.swuDoLeaderAction = function (action, leaderIndex) {
        var existing = document.getElementById('swuLeaderMenu');
        if (existing) existing.remove();
        var idx = (leaderIndex === undefined || leaderIndex === null) ? 0 : leaderIndex;
        SubmitInput('10001', '&cardID=' + encodeURIComponent('myLeader-' + idx + '!CustomInput!' + action));
    };

    function handleLeaderClick(e) {
        var d = window.myActionsData || {};
        var abilityByIdx = d.leaderAbilityByIndex || {0: d.leaderAbility};
        var deployByIdx  = d.leaderDeployByIndex  || {0: d.leaderDeploy};
        // Which leader was clicked? Walk up to the nearest myLeader-{i} span; default to 0 (single leader).
        var idx = 0;
        var el = e.target;
        while (el && el !== e.currentTarget) {
            if (el.getAttribute && /^myLeader-\d+$/.test(el.getAttribute('data-mzid') || '')) {
                idx = parseInt(el.getAttribute('data-mzid').split('-')[1], 10); break;
            }
            el = el.parentNode;
        }
        var ability = !!abilityByIdx[idx];
        var deploy  = !!deployByIdx[idx];
        if (!ability && !deploy) return;
        e.stopPropagation(); e.preventDefault();
        // Parse the i-th card from the split leader data.
        var raw = String(window.myLeaderData || '').trim();
        var parts = raw.length ? raw.split('<|>') : [];
        var obj = swuParseZoneCard(parts[idx] || parts[0] || '');
        var cardID = (obj && obj.CardID) ? obj.CardID : '';
        var isPilot = (window.SWU_PILOT_LEADERS || []).indexOf(cardID) !== -1;
        if (ability && !deploy) {
            window.swuDoLeaderAction('LeaderAbility', idx);
        } else if (!ability && deploy) {
            isPilot ? showLeaderMenu(cardID, false, true, idx)
                    : window.swuDoLeaderAction('DeployLeader:Unit', idx);
        } else {
            showLeaderMenu(cardID, ability, deploy, idx);
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
        // Read-only: a board that isn't yours (4-player other-pair) shows no "you can act" glows — the
        // actions data is computed for YOU, not the seat on screen, so its mzIDs don't apply here.
        if (window.swuSpectating) return;
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
            if (window.swuMustTakeCounter()) return;   // Twin Suns: must take a counter before passing
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
            if (window.swuMustTakeCounter()) return;   // Twin Suns: must take a counter before passing
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
            // Hotseat: W hands the device to the other seat (reload as the other playerID).
            if (window.SWUIsHotseat) { e.preventDefault(); window.swuSwitchPlayer(); return; }
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
            'background:var(--surface-raised,#0d1b2a)',
            'border:2px solid var(--border,#f0c040)',
            'border-radius:var(--radius,10px)',
            'padding:32px 48px',
            'text-align:center',
            'box-shadow:0 0 40px var(--glow,rgba(240,192,64,0.4))',
            'backdrop-filter:blur(10px) saturate(110%)',
            '-webkit-backdrop-filter:blur(10px) saturate(110%)',
            'min-width:320px'
        ].join(';');
        banner.innerHTML =
            '<div style="font-size:22px;font-weight:bold;color:var(--accent-strong,#f0c040);margin-bottom:8px;">Game Over</div>' +
            '<div style="font-size:14px;color:var(--text,#d4d4d4);margin-bottom:20px;">' + msg.replace(/</g,'&lt;') + '</div>' +
            '<button onclick="document.getElementById(\'swuGameOverBanner\').remove()" ' +
            'style="padding:6px 18px;background:var(--btn-plain-fill,#1e3a5f);border:1px solid var(--border,#888);border-radius:var(--radius,4px);' +
            'color:var(--btn-text,#eee);cursor:pointer;font-size:12px;">Dismiss ×</button>';
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

    // Twin Suns order strip: clockwise seat chips (SeatOrder) each showing its round-state from
    // window.myActionsData.roundState (active / waiting / took-counter). Hidden at ≤2 seats.
    function swuRenderOrderStrip() {
        var strip = document.getElementById('swuOrderStrip'); if (!strip) return;
        var order = String(window.SeatOrderData || '').trim();          // e.g. "123"
        var seats = order.length ? order.split('').map(function (c) { return parseInt(c, 10); }) : [];
        if (seats.length <= 2) { strip.style.display = 'none'; return; } // 2-player: never shown
        var rs = (window.myActionsData && window.myActionsData.roundState) || {};
        var html = '';
        for (var i = 0; i < seats.length; i++) {
            var s = seats[i];
            var state = rs[s] || rs[String(s)] || 'waiting';             // JSON keys may be strings
            var youCls = (s === MY_PLAYER_ID) ? ' is-you' : '';
            html += '<span class="swu-order-chip state-' + state + youCls + '">' +
                    '<span class="swu-order-dot"></span>P' + s + (s === MY_PLAYER_ID ? ' (you)' : '') + '</span>';
        }
        strip.innerHTML = html;
        strip.style.display = 'flex';
    }

    // ── Twin Suns pair-switcher ───────────────────────────────────────────────
    // Build the ordered list of views for THIS viewer from SeatOrder/LiveSeats. 3-player: [you-vs-A,
    // you-vs-B] (a 'home' split-top view is prepended in Phase 3). 4-player: [your-pair, other-pair] with
    // fixed display pairs (1,2) and (3,4). Returns [] at ≤2 seats (no switcher shown).
    function swuBuildViews() {
        var order = String(window.LiveSeatsData || window.SeatOrderData || '').trim();
        var seats = order.length ? order.split('').map(function (c) { return parseInt(c, 10); }) : [];
        var me = MY_PLAYER_ID;
        if (seats.length <= 2) return [];
        if (seats.length === 3) {
            var opps = seats.filter(function (s) { return s !== me; });
            return [
                { viewSeat: me, oppSeat: opps[0], mode: 'home', opps: opps, label: 'Home' },
                { viewSeat: me, oppSeat: opps[0], mode: 'matchup', label: 'vs P' + opps[0] },
                { viewSeat: me, oppSeat: opps[1], mode: 'matchup', label: 'vs P' + opps[1] },
            ];
        }
        // 4-player: fixed display pairs (1,2) and (3,4). Your pair = the one containing you.
        var pairs = [[1, 2], [3, 4]];
        var myPair = pairs[0].indexOf(me) !== -1 ? pairs[0] : pairs[1];
        var otherPair = (myPair === pairs[0]) ? pairs[1] : pairs[0];
        var across = myPair[0] === me ? myPair[1] : myPair[0];
        return [
            { viewSeat: me, oppSeat: across, mode: 'matchup', label: 'Your pair' },
            { viewSeat: otherPair[0], oppSeat: otherPair[1], mode: 'matchup', label: 'Other pair' },
        ];
    }

    // ── Twin Suns cross-view targeting: mzID ↔ seat / view mapping ──────────────
    // A target mzID is seat-tagged server-side as `p{n}<Zone>-{i}`; on the client only the two seats on
    // the CURRENT view render (as `my…`/`their…`). These map an mzID to its owning seat and to the frame
    // the current view would render it in.
    function swuSeatOfMzid(mzid) {
        var s = String(mzid || '');
        var pm = s.match(/^p(\d+)/);
        if (pm) return parseInt(pm[1], 10);
        if (s.indexOf('my') === 0)    return (window.swuView && window.swuView.viewSeat) || MY_PLAYER_ID;
        if (s.indexOf('their') === 0) return (window.swuView && window.swuView.oppSeat) || 0;
        return 0;
    }
    function swuZoneSuffixOfMzid(mzid) {
        // strip a leading p{n}/my/their and a trailing -{index}
        return String(mzid || '').replace(/^(p\d+|my|their)/, '').replace(/-\d+$/, '');
    }
    function swuRenderedZoneForSeat(seat) {
        var v = window.swuView; if (!v) return null;
        if (seat === v.viewSeat) return 'my';
        if (seat === v.oppSeat)  return 'their';
        return null; // off-view
    }
    function swuViewIndicesForSeat(seat) {
        var out = [], views = window.swuViews || [];
        for (var i = 0; i < views.length; i++) {
            if (views[i].viewSeat === seat || views[i].oppSeat === seat) out.push(i);
        }
        return out;
    }

    // Normalize an MZCHOOSE decision's parsed target specs to the CURRENT view. For each spec whose
    // seat is on-view, rewrite its `zone` to the rendered frame (`p3GroundArena`→`theirGroundArena`)
    // while PRESERVING `originalSpec` (the `p{n}…` string) so the existing submit-remap in
    // OnSelectableCardClick sends the real seat-tagged mzID. Off-view specs are held out (returned for
    // the arrow badge). 2-player (no swuViews) → identity passthrough → byte-identical.
    window.swuTwNormalizeSelection = function (parsedSpecs) {
        if (!window.swuViews || !window.swuViews.length) return { inlineNormalized: parsedSpecs, offViewSpecs: [] };
        var inlineNormalized = [], offViewSpecs = [];
        (parsedSpecs || []).forEach(function (spec) {
            if (!spec || !spec.zone || spec.actionPayload) { inlineNormalized.push(spec); return; }
            if (!/^p\d+/.test(spec.zone)) { inlineNormalized.push(spec); return; }   // already my/their
            var seat = parseInt((spec.zone.match(/^p(\d+)/) || [])[1], 10);
            var frame = swuRenderedZoneForSeat(seat);                                // 'my' | 'their' | null
            if (frame === null) { offViewSpecs.push(spec); return; }                 // off-view → badge only
            var suffix = spec.zone.replace(/^p\d+/, '');                             // 'GroundArena' | 'Base' | 'SpaceArena'
            inlineNormalized.push(Object.assign({}, spec, { zone: frame + suffix }));// originalSpec preserved
        });
        return { inlineNormalized: inlineNormalized, offViewSpecs: offViewSpecs };
    };

    // Map a rendered target mzID (e.g. 'theirBase-0' on the other-pair view) back to its original
    // seat-tagged spec ('p4Base-0') via the active decision's normalized allowedZones. The engine expects
    // the seat-tagged mzID. 2-player: originalSpec === the rendered id, so this is a no-op.
    window.swuTwRemapCardId = function (cardId) {
        var sm = window.SelectionMode;
        if (!(sm && sm.allowedZones)) return cardId;
        var m = /^(.+)-(\d+)$/.exec(cardId || ''); if (!m) return cardId;
        var zone = m[1], idx = parseInt(m[2], 10);
        var sp = sm.allowedZones.find(function (s) { return s && s.isSpecificCard && s.zone === zone && s.specificIndex === idx; });
        return (sp && sp.originalSpec) ? sp.originalSpec : cardId;
    };

    // Is the currently-viewed board someone else's (4-player "other pair")? Such a view is READ-ONLY:
    // you can read/inspect it but not act — you're not that seat. Only a view whose bottom board is YOU
    // (viewSeat === MY_PLAYER_ID) is interactive. Cross-view targeting (below) selectively re-enables
    // clicking a legal target on such a view.
    function swuApplySpectate() {
        var spectating = !!(window.swuView && window.swuView.viewSeat !== MY_PLAYER_ID);
        window.swuSpectating = spectating;
        document.body.classList.toggle('swu-spectating', spectating);
        var badge = document.getElementById('swuSpectateBadge');
        if (badge && window.swuView) {
            badge.textContent = '👁 Read-only — viewing P' + window.swuView.viewSeat + ' vs P' + window.swuView.oppSeat;
        }
    }

    window.swuSetView = function (index) {
        var views = window.swuViews || [];
        if (!views.length || index < 0 || index >= views.length) return;
        var v = views[index];
        window.swuView = { viewSeat: v.viewSeat, oppSeat: v.oppSeat, mode: v.mode, opps: v.opps, index: index };
        swuApplySpectate();
        // Cross-view targeting: RenderUpdate ALWAYS ClearSelectionMode()s and re-establishes selection only
        // from the response's decision data — but we repaint from the CACHED (pre-decision) responseArr, so
        // an active targeting decision would be lost. Re-normalize it for the NEW view NOW and tell
        // RenderUpdate to preserve the selection, so the repainted cards wire OnSelectableCardClick and glow.
        if (window.SelectionMode && window.SelectionMode.active && window.SelectionMode._twAllSpecs
            && typeof window.swuTwNormalizeSelection === 'function') {
            var _twn = window.swuTwNormalizeSelection(window.SelectionMode._twAllSpecs);
            window.SelectionMode.allowedZones = _twn.inlineNormalized;
            window.SelectionMode._twOffView   = _twn.offViewSpecs;
            window.SelectionMode.inlineSpecs  = (typeof CategorizeMZChooseSpecs === 'function')
                ? CategorizeMZChooseSpecs(_twn.inlineNormalized).inlineSpecs : _twn.inlineNormalized;
            window.__swuTwPreserveSelection = true;   // one-shot: RenderUpdate skips ClearSelectionMode
        }
        // Repaint from the cached responseArr WITHOUT a poll/animation replay (RenderUpdate is the repaint
        // entry point; frame animations live in ProcessRenderQueue, which we bypass here).
        if (window.swuLastResponseArr && typeof RenderUpdate === 'function') {
            RenderUpdate(window.swuLastResponseArr, window.__lastRenderedGameUpdate || 0);
        }
        swuRenderPairNav();
        swuRenderHomeStrips();
    };

    function swuRenderPairNav() {
        var nav = document.getElementById('swuPairNav'); if (!nav) return;
        var views = window.swuViews || [];
        var prev = document.getElementById('swuPairPrev'), next = document.getElementById('swuPairNext');
        if (views.length <= 1) {                        // 2-player: no switcher
            if (prev) prev.style.display = 'none';
            if (next) next.style.display = 'none';
            nav.style.display = 'none';
            return;
        }
        var idx = (window.swuView && typeof window.swuView.index === 'number') ? window.swuView.index : 0;
        // Carousel: left arrow only when there's a view to the left; right arrow only when there's one to
        // the right. Hidden (not disabled) so the edge is clear when you can't go that way.
        if (prev) prev.style.display = (idx > 0) ? 'flex' : 'none';
        if (next) next.style.display = (idx < views.length - 1) ? 'flex' : 'none';
        nav.style.display = 'contents';   // wrapper is display:contents; children are fixed to the edges
        swuTwRenderTargetBadges();
    }

    // Cross-view targeting: render a glowing count pill on ◀ / ▶ for legal targets on OFF-view seats
    // during an active decision. Bucket each off-view target by direction (a view index below the
    // current = ◀, above = ▶). Clears when no decision / no off-view targets. Exposed for the UILibraries
    // MZCHOOSE hook + swuSetView re-apply.
    function swuTwRenderTargetBadges() {
        var prev = document.getElementById('swuPairPrev'), next = document.getElementById('swuPairNext');
        function setBadge(arrow, n) {
            if (!arrow) return;
            var b = arrow.querySelector('.swu-target-badge');
            if (!n) { if (b) b.remove(); return; }
            if (!b) { b = document.createElement('span'); b.className = 'swu-target-badge'; arrow.appendChild(b); }
            b.textContent = String(n);
        }
        var off = (window.SelectionMode && window.SelectionMode.active && window.SelectionMode._twOffView) || [];
        var curIdx = (window.swuView && typeof window.swuView.index === 'number') ? window.swuView.index : 0;
        var left = 0, right = 0;
        off.forEach(function (spec) {
            var seat = parseInt((String(spec.zone).match(/^p(\d+)/) || [])[1], 10);
            var idxs = swuViewIndicesForSeat(seat);
            var goesRight = idxs.some(function (ix) { return ix > curIdx; });
            var goesLeft  = idxs.some(function (ix) { return ix < curIdx; });
            if (goesRight) right++; else if (goesLeft) left++;
        });
        setBadge(prev, left);
        setBadge(next, right);
    }
    window.swuTwRenderTargetBadges = swuTwRenderTargetBadges;

    function swuInitPairSwitcher() {
        window.swuViews = swuBuildViews();
        if (!window.swuViews.length) {                                        // 2-player: no switcher
            window.swuView = undefined; window.swuSpectating = false;
            document.body.classList.remove('swu-spectating');
            return;
        }
        if (!window.swuView) window.swuView = { viewSeat: window.swuViews[0].viewSeat,
            oppSeat: window.swuViews[0].oppSeat, mode: window.swuViews[0].mode, opps: window.swuViews[0].opps, index: 0 };
        swuApplySpectate();
        // Read-only guard: swallow clicks on the board zones (capture phase, before the framework's
        // attack/activate handlers) when spectating a board that isn't yours. Hover-to-inspect still
        // works (only click is blocked). Wired once. The pair-switcher arrows/dots/strips + order strip
        // sit OUTSIDE the "…Slot" zones, so they stay clickable.
        if (!document.body._swuSpectateGuard) {
            document.body._swuSpectateGuard = 1;
            document.addEventListener('click', function (e) {
                if (!window.swuSpectating) return;
                var t = e.target;
                // Cross-view targeting: a legal target (marked .selectable-card by an active decision)
                // stays clickable even on a spectated board; everything else is still read-only.
                if (t && t.closest && t.closest('.selectable-card')) return;
                if (t && t.closest && t.closest('[id$="Slot"]')) { e.stopPropagation(); e.preventDefault(); }
            }, true);
        }
        var prev = document.getElementById('swuPairPrev'), next = document.getElementById('swuPairNext');
        if (prev && !prev._swuWired) { prev._swuWired = 1; prev.addEventListener('click', function () { swuSetView((window.swuView.index || 0) - 1); }); }
        if (next && !next._swuWired) { next._swuWired = 1; next.addEventListener('click', function () { swuSetView((window.swuView.index || 0) + 1); }); }
        var strips = document.getElementById('swuHomeStrips');
        if (strips && !strips._swuWired) { strips._swuWired = 1; strips.addEventListener('click', function (e) {
            var b = e.target.closest && e.target.closest('.swu-home-strip'); if (b) swuSetView(parseInt(b.getAttribute('data-view'), 10));
        }); }
        swuRenderPairNav();
    }

    // Read a seat's zones from the cached responseArr (stride-31 blocks). Offsets: Leader=5, Base=6,
    // GroundArena=7, SpaceArena=8 (per NextTurnRender's window.*Data bindings).
    function swuReadSeatBlock(seat) {
        var arr = window.swuLastResponseArr; if (!arr) return null;
        var b = (seat - 1) * 31;
        function zone(off) { return String(arr[off + b] || '').trim(); }
        function count(s) { return s.length ? s.split('<|>').length : 0; }
        var leaderData = zone(5);
        return {
            baseObj: swuParseZoneCard(zone(6)),
            leaders: leaderData.length ? leaderData.split('<|>') : [],
            groundCount: count(zone(7)),
            spaceCount: count(zone(8)),
        };
    }

    // 3-player home view: two minimal opponent status strips (base damage, per-leader state, unit counts).
    // Each strip is a gateway button into that opponent's matchup view. Shown only on the 'home' view.
    // (Base DAMAGE is shown, not remaining HP: the base card JSON carries Damage but not printed HP, and
    // there's no client HP dictionary — a remaining-HP strip would need a base-HP transport emit, deferred.)
    function swuRenderHomeStrips() {
        var box = document.getElementById('swuHomeStrips'); if (!box) return;
        var v = window.swuView;
        if (!v || v.mode !== 'home' || !v.opps) { box.style.display = 'none'; return; }
        var views = window.swuViews || [];
        var html = '';
        v.opps.forEach(function (opp) {
            var b = swuReadSeatBlock(opp) || { leaders: [], groundCount: 0, spaceCount: 0, baseObj: null };
            var dmg = b.baseObj ? (parseInt(b.baseObj.Damage, 10) || 0) : 0;
            var leadHtml = b.leaders.map(function (ld) {
                var o = swuParseZoneCard(ld) || {};
                var cid = String(ld).trim().split(' ')[0];   // clean CardID (raw keeps underscore)
                var cls = (String(o.Ready) === 'false' || o.Ready === false) ? ' is-exhausted' : '';
                if (o.Deployed === true || String(o.Deployed) === 'true') cls += ' is-deployed';
                return '<span class="hs-leader' + cls + '">' + cid + '</span>';
            }).join('');
            var mi = 0;
            for (var i = 0; i < views.length; i++) if (views[i].mode === 'matchup' && views[i].oppSeat === opp) { mi = i; break; }
            html += '<button class="swu-home-strip" data-view="' + mi + '">' +
                    '<span class="hs-seat">P' + opp + '</span>' +
                    '<span class="hs-base">dmg ' + dmg + '</span>' +
                    '<span class="hs-leaders">' + leadHtml + '</span>' +
                    '<span class="hs-units">▮' + b.groundCount + ' ✦' + b.spaceCount + '</span></button>';
        });
        box.innerHTML = html;
        box.style.display = 'flex';
    }

    function refreshActionGlows() {
        // Read-only: on a board that isn't yours, apply NO action glows (blank the data). The deployed-
        // ghost reapply below reads the slot dataset (not this data), so it still runs correctly.
        var d = window.swuSpectating ? {} : (window.myActionsData || {});

        // Leader glow: per-index in Twin Suns (a seat can hold two leaders). Toggle .has-action on the
        // specific #myLeader-{i} card span using leaderAbilityByIndex/leaderDeployByIndex. Falls back to
        // the scalar keys (index 0) when the per-index arrays are absent (older payload / single leader).
        var abilityByIdx = d.leaderAbilityByIndex || {0: d.leaderAbility};
        var deployByIdx  = d.leaderDeployByIndex  || {0: d.leaderDeploy};
        var leaderSlot = document.getElementById('myLeaderSlot');
        if (leaderSlot) {
            var leaderCards = leaderSlot.querySelectorAll('[data-mzid^="myLeader-"]');
            for (var li = 0; li < leaderCards.length; li++) {
                var hasAct = !!(abilityByIdx[li] || deployByIdx[li]);
                leaderCards[li].classList.toggle('has-action', hasAct);
            }
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

        // Reapply cached deployed flags to freshly-rendered leader spans (Task A4). The data setter runs
        // before the innerHTML populate, so the spans didn't exist yet when .is-deployed was computed.
        ['myLeaderSlot', 'theirLeaderSlot'].forEach(function (sid) {
            var s = document.getElementById(sid); if (!s || !s.dataset.leaderDeployedFlags) return;
            var f = s.dataset.leaderDeployedFlags.split(',');
            var pfx = (sid === 'theirLeaderSlot') ? 'theirLeader' : 'myLeader';
            for (var i = 0; i < f.length; i++) {
                var sp = document.getElementById(pfx + '-' + i);
                if (sp) sp.classList.toggle('is-deployed', f[i] === '1');
            }
        });
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
        var prefix = (slotId === 'theirLeaderSlot') ? 'theirLeader' : 'myLeader';
        var raw = String(dataStr || '').trim();
        var parts = raw.length ? raw.split('<|>') : [];
        var flags = [];
        for (var i = 0; i < parts.length; i++) {
            var obj = swuParseZoneCard(parts[i] || '');
            // TWI_017 "Flipatine" flips IN PLACE (its "Deployed" is the flipped Villainy face, not a unit
            // deploy) — never ghost it. Match the RAW part, not obj.CardID — swuParseZoneCard runs
            // .replace(/_/g,' ') so obj.CardID reads "TWI 017"; the raw string keeps "TWI_017".
            var isFlipatine = (parts[i] || '').indexOf('TWI_017') !== -1;
            var dep = obj && (obj.Deployed === true || obj.Deployed === 'true' || parseInt(obj.Deployed, 10) === 1)
                      && !isFlipatine;
            flags.push(dep ? '1' : '0');
            var span = document.getElementById(prefix + '-' + i);
            if (span) span.classList.toggle('is-deployed', !!dep);
        }
        // Cache for a post-render reapply (the innerHTML populate runs after this setter fires).
        slot.dataset.leaderDeployedFlags = flags.join(',');
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
        SWUConfirm(msg, function() {
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
        }, { confirmLabel: 'Block', danger: true });
    }
    // Collapsible "Block Player" widget: collapsed header → expand shows the opponent's username
    // + a Block button. Returns null when there's no logged-in opponent to block.
    function SWUBuildBlockPlayerWidget(opts) {
        opts = opts || {};
        var viewerSeat = window.SWU_VIEWER_SEAT;
        if (viewerSeat !== 1 && viewerSeat !== 2) {
            var pf = document.getElementById('playerID');
            viewerSeat = pf ? parseInt(pf.value || '', 10) : NaN;
        }
        if (viewerSeat !== 1 && viewerSeat !== 2) return null; // spectator / not seated
        // Only logged-in users may block (the server also enforces this).
        var myName = window.SWU_SEAT_USERNAMES ? window.SWU_SEAT_USERNAMES[String(viewerSeat)] : null;
        if (!myName) return null; // viewer not logged in → can't block
        var oppSeat = (viewerSeat === 1) ? 2 : 1;
        var oppName = window.SWU_SEAT_USERNAMES ? window.SWU_SEAT_USERNAMES[String(oppSeat)] : null;
        if (!oppName) return null; // opponent anonymous / unknown → nothing to block
        var wrap = document.createElement('div'); wrap.className = 'swu-blockplayer';
        var head = document.createElement('button'); head.type = 'button';
        head.className = 'swu-blockplayer-head'; head.textContent = 'Block Player ▸';
        var body = document.createElement('div'); body.className = 'swu-blockplayer-body'; body.style.display = 'none';
        var nameEl = document.createElement('span'); nameEl.className = 'swu-blockplayer-name'; nameEl.textContent = oppName;
        var btn = document.createElement('button'); btn.type = 'button';
        btn.className = 'swu-blockplayer-btn'; btn.textContent = 'Block';
        btn.onclick = function() { SWUBlockOpponent({ liveBo3: !!opts.liveBo3 }); wrap.style.display = 'none'; };
        head.onclick = function() {
            var open = body.style.display !== 'none';
            body.style.display = open ? 'none' : 'flex';
            head.textContent = open ? 'Block Player ▸' : 'Block Player ▾';
        };
        body.appendChild(nameEl); body.appendChild(btn);
        wrap.appendChild(head); wrap.appendChild(body);
        return wrap;
    }
    window.SWUBuildBlockPlayerWidget = SWUBuildBlockPlayerWidget;
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
        // (Block Player moved to a collapsible widget below the game-over stats — see SWUShowEndGameMenu.)
        // Full-rematch (10016) both agreed: a NEW match is sideboarding (EndGameInfo followed the
        // Sideboard.json pointer and set info.matchId to it). Steer straight to its sideboard — the
        // completed-match buttons don't apply anymore.
        if (info.sideboardPending) {
            b.push({label:'Go to Next Game', onClick:function(){ SWUGoSideboard(info); }});
            b.push({label:'Return to Main Menu', onClick: SWUGoMainMenu});
            b.push({label:'Report Bug', onClick: SWUReportBug});
            return b;
        }
        if (bestOf === 3 && !seriesOver) {
            b.push({label:'Return to Main Menu', onClick:function(){ SWUConfirm('Leave now? This forfeits the best-of-3.', function(){ SubmitInput('10007',''); SWUGoMainMenu(); }, { confirmLabel: 'Leave', danger: true }); }});
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
                // Collapsible Block Player, placed below the stats panel.
                var goStats = document.getElementById('game-over-stats');
                if (goStats) {
                    // Dedupe first: this append runs even when ShowGameOver early-returned on an
                    // existing overlay, so two concurrent rebuilds (the convert poll removing the
                    // overlay + NextTurn.php's GAMEOVER_WINNER detector both firing in that gap)
                    // would otherwise stack a second widget in the same stats box.
                    var existingBw = goStats.querySelector('.swu-blockplayer');
                    if (existingBw) existingBw.remove();
                    var bw = SWUBuildBlockPlayerWidget({ liveBo3: (info.bestOf === 3 && info.matchState !== 'complete') });
                    // Inside the stats box, right below the stats table (not pushed to the panel bottom).
                    if (bw) goStats.appendChild(bw);
                    // SWUStats submission banner — shown once the match completes (SWUSubmitMatchResults ran).
                    var existingSt = goStats.querySelector('.swu-stats-status');
                    if (existingSt) existingSt.remove();
                    var stMap = {
                        success:       ['Game sent to SWUStats successfully!', '#7CFC9E'],
                        skipped_early: ['Game not sent to SWUStats due to ending before Round 2', '#F0B429'],
                        failed:        ['Game failed to send to SWUStats', '#E06666']
                    };
                    var st = stMap[info.statsStatus];
                    if (st) {
                        var sd = document.createElement('div');
                        sd.className = 'swu-stats-status';
                        sd.textContent = st[0];
                        sd.style.cssText = 'margin:0 0 10px;font-size:13px;font-weight:700;color:' + st[1] + ';';
                        goStats.insertBefore(sd, goStats.firstChild);
                    }
                }
                if (info.convertible) SWUStartEndGamePoll(gn, pid, ak);
            }).catch(function(){
                var w = SWULocalGameWinner();
                ShowGameOver(w > 0 && parseInt(pid, 10) === w, window.SWUMainMenuUrl || null, '');
            });
    }
    window.SWUShowEndGameMenu = SWUShowEndGameMenu;
    // Called on a 1236SIDEBOARD poll. Normally shows/keeps the end-game menu (its "Go to Next Game"
    // navigates to the sideboard). BUT after a FULL rematch (10016) both agreed and a NEW match is
    // sideboarding — the completed-match overlay is already up, so SWUShowEndGameMenu would no-op and
    // strand the player. EndGameInfo flags that case (sideboardPending, matchId = the new match); go
    // straight there, since both already opted in.
    function SWUEnterSideboardOrMenu() {
        var gnEl = document.getElementById('gameName');
        var pidEl = document.getElementById('playerID');
        var akEl = document.getElementById('authKey');
        var gn = gnEl ? gnEl.value : '', pid = pidEl ? pidEl.value : '', ak = akEl ? akEl.value : '';
        if (pid !== '1' && pid !== '2') { SWUShowEndGameMenu(); return; } // spectators just follow the menu
        fetch('./SWUSim/EndGameInfo.php?gameName=' + encodeURIComponent(gn) + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(ak))
            .then(function(r){ return r.json(); })
            .then(function(info){
                if (info && info.sideboardPending && info.matchId) { SWUGoSideboard(info); return; }
                SWUShowEndGameMenu();
            })
            .catch(function(){ SWUShowEndGameMenu(); });
    }
    window.SWUEnterSideboardOrMenu = SWUEnterSideboardOrMenu;

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

    // The cross-player trigger-order choice (CR 7.6.10 — the active player picks which player
    // resolves simultaneous triggered abilities first) is a plain YESNO whose "Yes/No" buttons say
    // nothing about WHICH player. Relabel them (YES = your abilities first, NO = opponent's first —
    // see SWU_TRIGGER_ORDER_CHOICE) and clarify the prompt. Submit values are untouched.
    function isTriggerOrder(decision) {
        return !!(decision && decision.Tooltip && /Resolve_Which_Player_First/i.test(decision.Tooltip));
    }

    window.ShowYesNoDecisionPopup = function (decision, onSubmit) {
        _origShowYesNo(decision, onSubmit);
        if (isTriggerOrder(decision)) {
            // Keep the prompt CENTERED. Mark the overlay so the effect stack stays visible while
            // choosing (see _esShouldShow), and lift the stack ABOVE the dark backdrop + pin it just
            // above the centered prompt (class .swu-es-order-front) so it's bright and uncovered while
            // the rest of the board stays dimmed behind the backdrop.
            var overlay = document.getElementById('yesno-decision-modal');
            if (overlay) overlay.setAttribute('data-swu-order', '1');
            // Lift the stack out of the board's (transformed) stacking context up to <body> so its
            // high z-index actually clears the body-level backdrop — otherwise it stays dimmed. No
            // re-render happens while waiting for the answer, so temporarily reparenting is safe.
            var slot = document.getElementById('EffectStackSlot');
            if (slot) {
                if (slot.parentNode && slot.parentNode !== document.body) {
                    slot._swuOrigParent = slot.parentNode;
                    slot._swuOrigNext = slot.nextSibling;
                    document.body.appendChild(slot);
                }
                slot.classList.add('swu-es-order-front');
            }
            var tm = document.querySelector('#yesno-decision-modal > div');
            if (tm) {
                var prompt = tm.firstElementChild;
                if (prompt) prompt.textContent = "Resolve whose abilities first?";
                var btns = tm.querySelectorAll('button');
                if (btns.length >= 2) {
                    btns[0].textContent = "Yours";  // YES → active player first
                    btns[1].textContent = "Theirs"; // NO  → opponent first
                    // On answer, the overlay is removed by the original handler; drop the stack back to
                    // its normal layer/position and re-run visibility so it hides (no pop-up during the
                    // auto-resolution that follows).
                    [btns[0], btns[1]].forEach(function(b) {
                        var orig = b.onclick;
                        b.onclick = function(ev) {
                            if (orig) orig.call(this, ev);
                            var s = document.getElementById('EffectStackSlot');
                            if (s) {
                                s.classList.remove('swu-es-order-front');
                                if (s._swuOrigParent) { // put it back where it lived on the board
                                    if (s._swuOrigNext && s._swuOrigNext.parentNode === s._swuOrigParent) s._swuOrigParent.insertBefore(s, s._swuOrigNext);
                                    else s._swuOrigParent.appendChild(s);
                                    s._swuOrigParent = null; s._swuOrigNext = null;
                                }
                            }
                            if (typeof window.UpdateEffectStackVisibility === 'function') window.UpdateEffectStackVisibility();
                        };
                    });
                }
            }
            if (typeof window.UpdateEffectStackVisibility === 'function') window.UpdateEffectStackVisibility();
            return;
        }
        if (!isMulligan(decision)) return;
        if (!window.SWU_MOBILE_LAYOUT) return; // desktop: the hand is already visible on the board
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
    modal.style.cssText = 'background:var(--surface-raised,#0D1B2A);border:1px solid var(--border,transparent);padding:32px 28px;border-radius:var(--radius,10px);box-shadow:0 0 24px #0009;backdrop-filter:blur(10px) saturate(110%);-webkit-backdrop-filter:blur(10px) saturate(110%);text-align:center;min-width:320px;font-family:\'Orbitron\',sans-serif;';
    var msg = document.createElement('div');
    msg.style.cssText = 'font-size:16px;color:var(--text,#fff);margin-bottom:8px;';
    msg.textContent = 'Player ' + fromPlayerID + ' requested to undo their last action.';
    var sub = document.createElement('div');
    sub.style.cssText = 'font-size:12px;color:var(--text-muted,rgba(255,255,255,0.55));margin-bottom:24px;';
    sub.textContent = '(They revealed hidden card information.)';
    var allowBtn = document.createElement('button');
    allowBtn.textContent = 'Allow';
    allowBtn.style.cssText = 'margin:0 12px 0 0;padding:8px 24px;font-size:16px;background:var(--success,#28a745);color:var(--on-success,#fff);border:none;border-radius:var(--radius,5px);cursor:pointer;';
    allowBtn.onclick = function() { overlay.remove(); SubmitInput(10008, ''); };
    var denyBtn = document.createElement('button');
    denyBtn.textContent = 'Deny';
    denyBtn.style.cssText = 'padding:8px 24px;font-size:16px;background:var(--danger,#dc3545);color:var(--on-danger,#fff);border:none;border-radius:var(--radius,5px);cursor:pointer;';
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
    modal.style.cssText = 'background:var(--surface-raised,#0D1B2A);border:1px solid var(--border,transparent);padding:32px 28px;border-radius:var(--radius,10px);box-shadow:0 0 24px #0009;backdrop-filter:blur(10px) saturate(110%);-webkit-backdrop-filter:blur(10px) saturate(110%);text-align:center;min-width:320px;font-family:\'Orbitron\',sans-serif;';
    var msg = document.createElement('div');
    msg.style.cssText = 'font-size:16px;color:var(--text,#fff);margin-bottom:8px;';
    msg.textContent = 'Player ' + targetPlayerID + ' has had undo requests denied multiple times.';
    var sub = document.createElement('div');
    sub.style.cssText = 'font-size:12px;color:var(--text-muted,rgba(255,255,255,0.55));margin-bottom:24px;';
    sub.textContent = 'Block all future undo requests from them?';
    var blockBtn = document.createElement('button');
    blockBtn.textContent = 'Block';
    blockBtn.style.cssText = 'margin:0 12px 0 0;padding:8px 24px;font-size:16px;background:var(--danger,#dc3545);color:var(--on-danger,#fff);border:none;border-radius:var(--radius,5px);cursor:pointer;';
    blockBtn.onclick = function() { overlay.remove(); SubmitInput(10010, ''); };
    var keepBtn = document.createElement('button');
    keepBtn.textContent = 'Keep Allowing';
    keepBtn.style.cssText = 'padding:8px 24px;font-size:16px;background:var(--surface-sunken,#6c757d);color:var(--text,#fff);border:none;border-radius:var(--radius,5px);cursor:pointer;';
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
    // Transparent-black tint layered OVER the mat art (and under the arena HUD wash),
    // so the mat reads darker/uniform behind the cards. Layered into the same
    // background as the mat image → it only shows where a mat is set. Tune the alpha.
    var TINT = 'rgba(10,10,10,0.67)';
    var matBg = function (asset) { return "linear-gradient(" + TINT + "," + TINT + "), url('" + asset + "')"; };
    var top = document.querySelector('.swu-playmat-top');   // opponent side (desktop)
    var bot = document.querySelector('.swu-playmat-bot');   // my side (desktop)
    function paint(el, asset) {
      if (!el) return;
      if (show && asset) { el.style.backgroundImage = matBg(asset); el.style.display = 'block'; }
      else { el.style.display = 'none'; }
    }
    paint(bot, c.myPlaymat);
    paint(top, c.theirPlaymat);

    // Mobile: no dedicated playmat divs — the per-side mat backs each player's arena
    // row directly (cover/center = inner slice). Toggle .has-playmat for the overlay.
    var mMine   = document.querySelector('.swu-m-arena-row.is-mine');
    var mTheirs = document.querySelector('.swu-m-arena-row.is-theirs');
    function paintRow(el, asset) {
      if (!el) return;
      if (show && asset) { el.style.backgroundImage = matBg(asset); el.classList.add('has-playmat'); }
      else { el.style.backgroundImage = ''; el.classList.remove('has-playmat'); }
    }
    paintRow(mMine, c.myPlaymat);
    paintRow(mTheirs, c.theirPlaymat);
  } catch (e) {}
}
if (document.readyState !== 'loading') ApplyCosmeticPlaymats();
else document.addEventListener('DOMContentLoaded', ApplyCosmeticPlaymats);
window.ApplyCosmeticPlaymats = ApplyCosmeticPlaymats;   // re-callable when the toggle changes

// ── Live cosmetics poller: pick up opponent (or cross-device) cosmetic changes without a
// reload. Polls CosmeticsLive.php every 6s (paused when the tab is hidden); on any diff it
// swaps window.SWU_COSMETICS and re-applies. Idempotent apply funcs make repeats free.
(function () {
  function appBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0?p.slice(0,i+11):'/TCGEngine/'; }
  function val(id){ var el=document.getElementById(id); return el ? el.value : ''; }
  var last = JSON.stringify(window.SWU_COSMETICS || {});
  function poll() {
    if (document.hidden) return;
    var gn = val('gameName'); if (!gn) return;
    var vp = val('viewerPerspective') || '1';
    var ak = val('authKey');   // carries the test sentinel so dev-tool seat overrides stay applied
    var url = appBase()+'SWUSim/CosmeticsLive.php?gameName='+encodeURIComponent(gn)+'&viewerPerspective='+encodeURIComponent(vp)+'&authKey='+encodeURIComponent(ak);
    var x = new XMLHttpRequest(); x.open('GET', url, true);
    x.onload = function () {
      if (x.status < 200 || x.status >= 300) return;
      var next; try { next = JSON.parse(x.responseText); } catch (e) { return; }
      if (!next || typeof next !== 'object' || Array.isArray(next)) return;
      // Empty payload ({}) = no session / no cosmetics — treat as "no change".
      if (!('background' in next) && !('myCardBack' in next) && !('theirCardBack' in next)) return;
      var s = JSON.stringify(next);
      if (s === last) return;
      last = s;
      window.SWU_COSMETICS = next;
      if (typeof ApplyAllCosmetics === 'function') ApplyAllCosmetics();
    };
    x.onerror = function () {};   // swallow blips; next tick retries
    x.send();
  }
  setInterval(poll, 6000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
})();
</script>

<!-- ── In-game Settings hub (gear menu) ─────────────────────────────────────── -->
<style>
  .swu-header-right { display: flex; align-items: center; gap: 8px; }
  .swu-gear-btn { background: transparent; border: 0; color: var(--accent); font-size: 40px;
    line-height: 1; cursor: pointer; padding: 2px 4px; filter: drop-shadow(0 0 4px var(--glow));
    transition: transform 140ms ease, color 140ms ease; }
  .swu-gear-btn:hover { color: #fff; transform: rotate(40deg); }
  .swu-settings-overlay { position: fixed; inset: 0; z-index: 10001; display: flex;
    align-items: center; justify-content: center; background: var(--overlay-scrim);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }
  .swu-settings-panel { width: min(92vw, 360px); background: var(--surface-raised);
    border: 1px solid var(--border); border-radius: 12px;
    box-shadow: 0 18px 50px rgba(0,0,0,0.6); color: var(--text); overflow: hidden; }
  .swu-settings-head { display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; border-bottom: 1px solid var(--border);
    font: 700 16px/1 var(--swu-font-label, sans-serif); color: var(--accent-strong); letter-spacing: 0.02em; }
  .swu-settings-close { background: transparent; border: 0; color: var(--text-muted); font-size: 16px; cursor: pointer; }
  .swu-settings-close:hover { color: #fff; }
  .swu-settings-section { padding: 14px 16px; }
  .swu-settings-section-title { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--accent); margin-bottom: 8px; }
  .swu-settings-row { display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 6px 0; font-size: 14px; cursor: pointer; }
  .swu-settings-row input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; }
  .swu-settings-row--stack { flex-direction: column; align-items: stretch; gap: 4px; cursor: default; }
  .swu-settings-row--stack > span { font-size: 12px; color: var(--accent); }
  .swu-gear-cos { width: 100%; padding: 7px 9px; border-radius: 7px; cursor: pointer;
    background: var(--surface-raised, rgba(8,15,25,0.6)); color: var(--text, #e8d5a8);
    border: 1px solid var(--border, rgba(255,255,255,0.14)); }
  .swu-settings-link { display: inline-block; margin-top: 8px; color: var(--accent); font-size: 13px; text-decoration: none; }
  .swu-settings-link:hover { text-decoration: underline; }
  .swu-settings-action { display: block; width: 100%; margin: 6px 0 0;}
  /* Collapsible Block Player widget (shared by the gear menu + game-over overlay) */
  .swu-blockplayer { margin: 8px auto 0; max-width: 360px; text-align: left; }
  .swu-blockplayer-head { display: block; width: 100%; padding: 6px 0; background: transparent; border: 0;
    color: rgba(140,210,255,0.7); font: 700 12px/1 var(--swu-font-label, sans-serif);
    text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; text-align: left; }
  .swu-blockplayer-head:hover { color: #cfe6fb; }
  .swu-blockplayer-body { display: flex; align-items: center; justify-content: space-between; gap: 12px;
    margin-top: 6px; padding: 8px 12px; background: rgba(8,15,25,0.5);
    border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; }
  .swu-blockplayer-name { color: #f0e6c8; font-size: 14px; font-weight: 600; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap; }
  .swu-blockplayer-btn { flex: 0 0 auto; padding: 7px 16px; background: rgba(180,40,55,0.22);
    border: 1px solid rgba(220,80,95,0.6); border-radius: 6px; color: #ffd7dc;
    font: 700 13px/1 var(--swu-font-label, sans-serif); cursor: pointer; }
  .swu-blockplayer-btn:hover { background: rgba(200,50,65,0.4); }
  /* Sits inside the stats box, directly under the stats table. */
  #game-over-stats .swu-blockplayer { margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.12); }
  /* SWUConfirm now delegates to the shared StyledDialog (which self-injects its themed CSS);
     its bespoke .swu-confirm-* styles were removed. */
</style>
<?php
  require_once __DIR__ . '/../../Database/ConnectionManager.php';   // GetLocalMySQLConnection (used by LoadUserCosmetics)
  require_once __DIR__ . '/../../Database/functions.inc.php';
  require_once __DIR__ . '/../Cosmetics/Catalog.php';
  $swuGearUid = function_exists('LoggedInUser') ? LoggedInUser() : '';
  $swuGearCos = ($swuGearUid !== '' && $swuGearUid !== null) ? LoadUserCosmetics($swuGearUid) : null;
?>
<div id="swuSettingsOverlay" class="swu-settings-overlay" style="display:none;" onclick="if(event.target===this)swuCloseSettings()">
  <div class="swu-settings-panel" role="dialog" aria-modal="true">
    <div class="swu-settings-head"><span>Settings</span>
      <button class="swu-settings-close" onclick="swuCloseSettings()" aria-label="Close">&#10005;</button></div>
    <div class="swu-settings-section">
      <div class="swu-settings-section-title">Cosmetics</div>
      <label class="swu-settings-row"><span>Show playmats</span>
        <input type="checkbox" id="swuSetShowPlaymats"></label>
      <?php if ($swuGearCos !== null): ?>
        <label class="swu-settings-row swu-settings-row--stack"><span>Background</span>
          <?= SWUCosmeticSelectHtml('background', $swuGearCos['background']['id'], 'swu-gear-cos') ?></label>
        <label class="swu-settings-row swu-settings-row--stack"><span>Card back</span>
          <?= SWUCosmeticSelectHtml('cardback', $swuGearCos['cardback']['id'], 'swu-gear-cos') ?></label>
        <label class="swu-settings-row swu-settings-row--stack"><span>Playmat</span>
          <?= SWUCosmeticSelectHtml('playmat', $swuGearCos['playmat']['id'], 'swu-gear-cos') ?></label>
      <?php endif; ?>
    </div>
    <div class="swu-settings-section" id="swuSettingsMatchSection" style="display:none; border-top:1px solid var(--border);">
      <div class="swu-settings-section-title">Match</div>
      <button class="btn btn-danger swu-settings-action" onclick="SWUGearConcede(false)">Concede</button>
      <button class="btn btn-primary swu-settings-action" onclick="SWUGearConcede(true)">Return to Main Menu</button>
      <div id="swuSettingsBlockMount"></div>
    </div>
  </div>
</div>
<script>
  function swuOpenSettings() {
    var ov = document.getElementById('swuSettingsOverlay'); if (!ov) return;
    // Move the overlay to <body> so its z-index (10001) competes in the top-level stacking
    // context — otherwise the board's transformed wrapper traps it below the body-level
    // turn-miasma "Waiting for the other player" pill (z 4999). Idempotent across re-opens.
    if (ov.parentNode !== document.body) document.body.appendChild(ov);
    var t = document.getElementById('swuSetShowPlaymats');
    if (t && window.TCGSettings) t.checked = window.TCGSettings.get('ShowPlaymats', { rootName:'SWUSim', type:'boolean', defaultValue:true }) !== false;
    // Match actions are player-only (hidden for spectators / non-players).
    var ms = document.getElementById('swuSettingsMatchSection');
    if (ms) {
      var pf = document.getElementById('playerID');
      var pid = pf ? parseInt(pf.value || '', 10) : NaN;
      var isPlayer = (pid === 1 || pid === 2);
      ms.style.display = isPlayer ? 'block' : 'none';
      // (Re)build the collapsible Block Player widget for the current opponent.
      var bm = document.getElementById('swuSettingsBlockMount');
      if (bm) {
        bm.innerHTML = '';
        if (isPlayer && typeof SWUBuildBlockPlayerWidget === 'function') {
          var w = SWUBuildBlockPlayerWidget({ liveBo3: (window.SWU_MATCH_BESTOF === 3) });
          if (w) bm.appendChild(w);
        }
      }
    }
    ov.style.display = 'flex';
  }
  function swuCloseSettings() { var ov = document.getElementById('swuSettingsOverlay'); if (ov) ov.style.display = 'none'; }
  // SWUConfirm is now a thin shim over the shared StyledDialog primitive — the bespoke modal
  // and its CSS were removed. Callback-style signature preserved so existing call sites are unchanged.
  function SWUConfirm(message, onConfirm, opts) {
    StyledConfirm(message, opts || {}).then(function(ok) { if (ok && typeof onConfirm === 'function') onConfirm(); });
  }
  window.SWUConfirm = SWUConfirm;
  // Concede from the gear menu. Live Bo3 forfeits the whole match (10007); otherwise the game (10006).
  function SWUGearConcede(goHome) {
    var pf = document.getElementById('playerID');
    var pid = pf ? parseInt(pf.value || '', 10) : NaN;
    if (pid !== 1 && pid !== 2) return; // spectators can't concede
    var gnEl = document.getElementById('gameName');
    var akEl = document.getElementById('authKey');
    var gn = gnEl ? gnEl.value : '';
    var ak = akEl ? akEl.value : '';
    function act(liveBo3) {
      var msg = liveBo3
        ? 'Concede the whole match? This forfeits the entire series.'
        : 'Concede this game? This will immediately count as a loss for you.';
      SWUConfirm(msg, function() {
        SubmitInput(liveBo3 ? '10007' : '10006', '');
        swuCloseSettings();
        if (goHome && typeof SWUGoMainMenu === 'function') SWUGoMainMenu();
      }, { confirmLabel: 'Concede', danger: true });
    }
    fetch('./SWUSim/EndGameInfo.php?gameName=' + encodeURIComponent(gn) + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(ak))
      .then(function(r){ return r.json(); })
      .then(function(info){ act(!!(info && info.isMatch && info.bestOf === 3 && info.matchState !== 'complete')); })
      .catch(function(){ act(false); }); // fall back to single-game concede on any error
  }
  window.SWUGearConcede = SWUGearConcede;
  document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'swuSetShowPlaymats') {
      if (window.TCGSettings) window.TCGSettings.set('ShowPlaymats', e.target.checked, { rootName:'SWUSim', type:'boolean' });
      if (typeof window.ApplyCosmeticPlaymats === 'function') window.ApplyCosmeticPlaymats();
      return;
    }
    var sel = e.target && e.target.closest ? e.target.closest('.swu-gear-cos') : null;
    if (sel) {
      var slot = sel.getAttribute('data-slot');
      var opt = sel.options[sel.selectedIndex];
      var asset = opt ? (opt.getAttribute('data-asset') || '') : '';
      // Instant local apply for the picker's own view.
      var c = window.SWU_COSMETICS = window.SWU_COSMETICS || {};
      if (slot === 'background') c.background = asset;
      else if (slot === 'cardback') c.myCardBack = asset;
      else if (slot === 'playmat') c.myPlaymat = asset;
      if (typeof ApplyAllCosmetics === 'function') ApplyAllCosmetics();
      // Persist to profile + patch the live match snapshot (opponent picks it up via the poller).
      function appBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0?p.slice(0,i+11):'/TCGEngine/'; }
      var gnEl = document.getElementById('gameName');
      var gn = gnEl ? gnEl.value : '';
      var x = new XMLHttpRequest();
      x.open('POST', appBase()+'SWUSim/Cosmetics.php', true);
      x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
      x.send('action=set&slot='+encodeURIComponent(slot)+'&choiceId='+encodeURIComponent(sel.value)+'&gameName='+encodeURIComponent(gn));
    }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') swuCloseSettings(); });
</script>

