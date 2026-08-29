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

/* ── Twin Suns table shell (pair-switcher + home strips) — all hidden at ≤2 seats ── */
/* The fixed top-centre ORDER STRIP (a row of P1/P2/P3/P4 chips with a green ring on the turn player)
   lived here and was REMOVED: the home strips now carry the active-turn highlight themselves
   (.swu-home-strip.is-active-turn), so the bar was a second answer to the same question in a
   different visual language. See swuTwHighlightActiveSeat(). */

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
/* Twin Suns two-level nav: a Go-back button shown on a matchup view (3-player). Replaces the ◀ arrow
   for the home-bearing view set; the carousel arrows are hidden there. */
/* Threaded into the gap BETWEEN the initiative hex and the arena frame's top-left corner bracket.
   Two things to clear, and the gap between them is only about 50px:
     • the hex above  — at top:56px the button sat right across it (measured: button y 56-80 over a
       hex at y 28-90);
     • the frame below — at hand-h + 8px it cleared the hex but its bottom edge (y 150) then cut
       through the corner bracket, which starts at hand-h + --swu-arena-margin (y 142).
   So it is anchored to the FRAME's top and pulled back by its own height plus a margin, which keeps
   it clear of both at any board size rather than hardcoding a number a band-height change would
   silently break. Harmless when the token is on the other side — nothing sits above it then. */
.swu-go-back { position: fixed; top: calc(var(--swu-hand-h, 56px) + var(--swu-arena-margin, 0px) - 34px);
    left: calc(var(--swu-arena-margin, 0px) + 8px); z-index: 42; cursor: pointer;
    padding: 5px 12px; border-radius: 8px; font: 700 12px/1 var(--swu-font-label, sans-serif);
    background: var(--swu-surface, rgba(10,20,30,0.85)); border: 1px solid var(--swu-border, #2a3a4a);
    color: var(--accent-strong, #f0c040); }
.swu-go-back:hover { border-color: var(--accent-strong, #f0c040); background: rgba(10,20,30,0.95); }
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
/* Two-level cross-view target cue: on the home view the legal-target mini cards glow (.mini-selectable);
   on a matchup the Go-back button pulses when a legal target sits on another board. */
@keyframes swuGoBackPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(46,204,113,0.0); } 50% { box-shadow: 0 0 10px 2px rgba(46,204,113,0.85); } }
.swu-go-back.is-pulsing { border-color: #2ecc71; animation: swuGoBackPulse 1.1s ease-in-out infinite; }

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
/* NO KEYART (seat has no playmat, or the viewer turned "Show playmats" off) — knock the WHOLE tile
   back with the SAME wash the mini-board arena boxes use (.swu-mb-arena, rgba(0,0,0,0.35)), expanded
   from just those boxes to the entire tile.
   ⚠ Why this is needed at all: .swu-home-strip's own background is --swu-surface, which is
   rgba(255,255,255,0.04) — an almost-transparent WHITE wash. Over a dark board that reads as a panel,
   but over a BRIGHT board cosmetic (the snow keyart) the header row, the chips and the gaps between
   the arena boxes let the board through at full brightness while the arena boxes stayed knocked back,
   so the tile read as half-scrimmed. Layered as a background-IMAGE so it composes over the surface
   colour and the class alone switches it, exactly like the playmat path.
   Desktop only by construction: mobile renders .swu-seat-row, not .swu-home-strip.
   .has-playmat is stamped by swuPaintHomeStripPlaymats() — the single painter — so this can never
   disagree with whether art is actually showing. */
.swu-home-strip:not(.has-playmat) {
    background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35));
}
/* ...and the arena boxes do NOT stack a second copy on top. The tile wash IS the arena background now,
   so the whole tile reads as one surface and SPACE/GROUND are marked out by their BORDERS alone (see
   the silver/sand pair below) rather than by a darker fill. Without this the boxes composite to ~0.58
   against the tile's 0.35 and the tile is two-tone — the same half-scrimmed look the wash exists to fix.
   ⚠ Scoped to the no-keyart state only: WITH a playmat the tile's 0.72 art tint under 0.35 boxes is the
   established look and is deliberately untouched. */
.swu-home-strip:not(.has-playmat) .swu-mb-arena { background: transparent; }
/* No hover highlight on the tile itself — the tile is not a button. Its interactive parts (the cards,
   the Zoom-in button, the Discard chip) carry their own affordances, and a whole-tile glow on hover
   competed with the active-turn ring, which is the one border state that means something here. */
/* ACTIVE SEAT — whose turn it is right now. The home strips all carry a dimmed playmat behind them
   and read as equally "asleep", so the turn player is called out with a warm ring + lift rather than
   a colour swap on text that a dark keyart would swallow.
   ⚠ Deliberately NOT colour-only: .is-active-turn also prints a "TURN" pill next to the seat number,
   so the state survives a colourblind viewer and a screenshot. The ring uses box-shadow, not a border
   width change, so the strip does not reflow by a pixel when the turn passes. */
.swu-home-strip.is-active-turn {
    border-color: rgba(240,192,64,0.55);
    /* ⚠ Two BLURRED layers and no hard ring — the same idiom as 2P's .has-action glow
       (0 0 14px 3px + 0 0 4px 1px of the accent), scaled up for a tile-sized target. The first
       version used `0 0 0 2px`, a zero-blur spread, which paints a crisp solid outline: a different
       visual language from everything else on the board. Keep both layers blurred if you retune it. */
    box-shadow: 0 0 26px 6px rgba(240,192,64,0.50), 0 0 8px 2px rgba(240,192,64,0.42);
    filter: brightness(1.18) saturate(1.12);
    transition: box-shadow 0.3s ease, border-color 0.3s ease;
}
.swu-home-strip.is-active-turn .swu-mb-seat { color: #ffd970; }
.swu-mb-turnpill {
    margin-left: 4px; padding: 1px 5px; border-radius: 999px;
    background: var(--accent-strong, #f0c040); color: #1a1206;
    font: 800 8px/1.35 var(--swu-font-label, sans-serif); letter-spacing: 0.1em;
    text-transform: uppercase; vertical-align: middle; white-space: nowrap;
}
/* Initiative holder. Cyan, matching the initiative token's own palette and deliberately NOT the
   amber of the turn pill — "acts next" and "holds the initiative" are different facts and a seat can
   hold both at once. Outlined while unclaimed, filled once claimed for the round. */
.swu-mb-initpill {
    margin-left: 4px; padding: 1px 5px; border-radius: 999px;
    background: transparent; color: #7fe3ef; border: 1px solid rgba(127,227,239,0.85);
    font: 800 8px/1.35 var(--swu-font-label, sans-serif); letter-spacing: 0.1em;
    text-transform: uppercase; vertical-align: middle; white-space: nowrap;
}
.swu-mb-initpill.is-claimed { background: rgba(127,227,239,0.92); color: #062227; border-color: transparent; }
/* The pills only exist on the strips they belong to, so no :not() is needed to hide them elsewhere. */
.swu-home-strip .hs-seat { font-weight: 800; color: #eef; }
.swu-home-strip .hs-base { color: #f0c040; }
.swu-home-strip .hs-leaders { display: flex; gap: 4px; }
.swu-home-strip .hs-leader { padding: 1px 5px; border-radius: 4px; background: rgba(255,255,255,0.06); }
.swu-home-strip .hs-leader.is-exhausted { opacity: 0.5; }
.swu-home-strip .hs-leader.is-deployed  { color: #7fd; }

/* Mini-board tile (replaces the text strip content). Class stays .swu-home-strip; its rows use .swu-mb-*
   so the tile is a shrunk board: row 1 [leaders … base … Zoom-in], then full-width [Space] and [Ground]
   arena rows. Cards are clickable targets during a decision; the Zoom-in button opens the matchup. */
.swu-home-strip { position: relative; flex-direction: column; align-items: stretch; gap: 6px; padding: 8px; max-width: 46%; cursor: default; }
.swu-mb-r1 { display: flex; align-items: center; gap: 5px; }
.swu-mb-seat { font-weight: 800; color: #eef; margin-right: 2px; }
.swu-mb-spacer { flex: 1 1 auto; }
.swu-mb-card { position: relative; border-radius: 3px; border: 1px solid #10151f;
    background-size: cover; background-position: center; box-shadow: 0 1px 2px rgba(0,0,0,0.5); }
/* LANDSCAPE, ~628:450 — the real proportions of a leader card. This was 26x36 (portrait), which is a
   unit's shape, not a leader's; the art was cropped to fit it. Keep the ratio if you retune the size. */
.swu-mb-leader { width: 40px; height: 29px; }
.swu-mb-base   { width: 44px; height: 30px; display: flex; align-items: center; justify-content: center; }
.swu-mb-unit   { width: 22px; height: 31px; }
/* Base thumbnail corners — Force top-right, Epic-Action-Used bottom-right, matching where the full
   board puts them. Absolute, so they add no width to row 1. The centre belongs to the damage token. */
/* Shared corner icon for ANY mini-board card — the base (Force / epic-used) and each leader
   (epic-used). Named for the card, not the base, because both use it. */
.swu-mb-cardicon { position: absolute; right: 2px; z-index: 4; width: 12px; height: 12px;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.8)); pointer-events: none; }
.swu-mb-ic-tr { top: 2px; }
.swu-mb-ic-br { bottom: 2px; opacity: 0.94; }
body.swu-home .swu-mb-cardicon { width: 16px; height: 16px; }
/* A leader thumbnail is smaller than the base, so its icon steps down to stay a corner marker rather
   than a lid — the 75x75-on-an-84px-card mistake this file already carries a warning about. */
body.swu-home .swu-mb-leader .swu-mb-cardicon { width: 13px; height: 13px; }

/* Effects column between the base and Zoom-in: THREE fixed rows, filled in arrival order.
   ⚠ Fixed width and height ALWAYS, empty or not — row 1 must stay identical across tiles. */
.swu-mb-fx { display: flex; flex-direction: column; justify-content: space-between;
    flex: 0 0 auto; width: 20px; height: 30px; margin-left: 4px; }
.swu-mb-fxrow, .swu-mb-fxchip { height: 8px; border-radius: 4px; }
.swu-mb-fxrow { background: rgba(255,255,255,0.05); }
.swu-mb-fxchip { display: flex; align-items: center; justify-content: center;
    font: 900 8px/1 var(--swu-font-label, sans-serif); box-shadow: 0 1px 2px rgba(0,0,0,0.6); }
.swu-mb-fxchip-fort   { background: #bec4ce; color: #14181f; cursor: pointer; }
.swu-mb-fxchip-arrest { background: #daa520; color: #1a1206; cursor: pointer; }
body.swu-home .swu-mb-fx { width: 26px; height: 46px; margin-left: 6px; }
body.swu-home .swu-mb-fxrow, body.swu-home .swu-mb-fxchip { height: 13px; border-radius: 6px; }
body.swu-home .swu-mb-fxchip { font-size: 11px; }
.swu-mb-card.is-exhausted { transform: rotate(8deg); filter: brightness(0.55) saturate(0.6); }
.swu-mb-leader.is-deployed { outline: 1px dashed #cc8; opacity: 0.6; }
/* Damage counter — matches the 2-player board (schema: Damage=Image(swusim-damage.png, Position=Center,
   TextColor=White)): the damage token centered on the card with the white number over it. */
.swu-mb-dmgcounter { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    display: flex; align-items: center; justify-content: center; pointer-events: none;
    background: url('./Assets/Icons/swusim-damage.png') center / contain no-repeat;
    color: #fff; font-weight: 800; text-shadow: 0 1px 2px #000;
    width: 20px; height: 20px; font-size: 11px; }
.swu-mb-base .swu-mb-dmgcounter { width: 24px; height: 24px; font-size: 12px; }
body.swu-home .swu-mb-unit .swu-mb-dmgcounter { width: 26px; height: 26px; font-size: 14px; }
body.swu-home .swu-mb-base .swu-mb-dmgcounter { width: 34px; height: 34px; font-size: 17px; }
/* Zoom-in button (row 1, right) → opens the you-vs-P{seat} matchup. Height matches the base card in the
   preview so row 1 reads as one aligned strip. */
.swu-mb-zoom { flex: 0 0 auto; cursor: pointer; white-space: nowrap; box-sizing: border-box;
    height: 30px; display: inline-flex; align-items: center;
    padding: 0 10px; border-radius: 7px; font: 700 11px/1 var(--swu-font-label, sans-serif);
    background: var(--swu-surface, rgba(10,20,30,0.9)); border: 1px solid var(--swu-border, #2a3a4a);
    color: var(--accent-strong, #f0c040); }
.swu-mb-zoom:hover { border-color: var(--accent-strong, #f0c040); background: rgba(10,20,30,1); }
body.swu-home .swu-mb-zoom { height: 46px; font-size: 13px; padding: 0 14px; }
/* The two arenas SPLIT the tile's leftover height evenly and keep it whether or not they hold units —
   an empty Space arena collapsing to a label-height sliver while Ground stood full height made two
   equivalent zones look like different kinds of thing, and the tile's lower half was dead space.
   flex:1 1 0 (not min-height) so they stay equal to each other at any tile height. */
/* Stat row: ready/total resources (+credits), deck size, discard size. flex:0 0 auto so it keeps its
   own height and the two arenas below still split everything that is left. */
.swu-mb-r2 { display: flex; align-items: center; gap: 10px; flex: 0 0 auto;
    font: 700 10px/1 var(--swu-font-label, sans-serif); color: #dde; }
.swu-mb-stat { display: inline-flex; align-items: baseline; gap: 4px; padding: 2px 6px;
    border-radius: 4px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.07); }
.swu-mb-statlbl { font-size: 8px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
    opacity: 0.5; }
/* Only the chips that DO something get a hover affordance, now that the tile itself has none. */
/* RES is STATIC — reserved for "NN/NN +NN" and never resized by its contents. A chip that grew when a
   seat gained credits shifted everything after it, so the same fact sat in a different place on every
   tile. inline-block + min-width rather than a fixed width so an outlier can still overflow legibly
   instead of being clipped. */
.swu-mb-statval { display: inline-block; min-width: 4.9em; text-align: left; }
.swu-mb-pills { display: inline-flex; align-items: center; gap: 6px; margin-left: 2px; }
/* Row-2 pills: the seat-label margin no longer applies now that they sit in their own flex slot. */
.swu-mb-pills .swu-mb-turnpill, .swu-mb-pills .swu-mb-initpill { margin-left: 0; }
.swu-mb-stat-btn { cursor: pointer; }
.swu-mb-stat-btn:hover, .swu-mb-stat-btn:focus-visible {
    border-color: var(--accent-strong, #f0c040); background: rgba(240,192,64,0.12); outline: none; }
/* Gold, matching the main board's "+ N" credit chip (.swu-credit-count) — same fact, same colour. */
/* ⚠ 5px, and the RES value box reserves 4.9em (64px at the home font size) for it. The widest real
   value, "10/12 +12", measures 60px with this gap — inside the reserve, so the chip still never grows.
   Widen this and re-measure, or a 2-digit seat silently pushes the chip out again. */
.swu-mb-statcred { color: #f2c14e; margin-left: 5px; }
body.swu-home .swu-mb-r2 { font-size: 13px; gap: 12px; }
body.swu-home .swu-mb-statlbl { font-size: 9px; }

.swu-mb-arena { background: rgba(0,0,0,0.35); border: 1px solid #1c2438; border-radius: 5px; padding: 4px; min-width: 0;
    display: flex; flex-direction: column; flex: 1 1 0; }
/* SPACE = silver, GROUND = sandy brown. On a playmat-less tile the fill is uniform, so the border is
   the ONLY thing telling the two arenas apart at preview size — the colour carries real information
   there, not decoration. Kept at ~0.7 alpha so it reads as a material edge rather than a UI accent
   competing with the amber active-turn ring.
   ⚠ NOT colour-only: each box still prints its "SPACE"/"GROUND" tag, so the distinction survives a
   colourblind viewer — same rule the active-turn pill follows. */
.swu-mb-arena--space  { border-color: rgba(202, 212, 224, 0.70); }   /* silver */
.swu-mb-arena--ground { border-color: rgba(198, 160,  99, 0.70); }   /* sandy brown */
/* flex-start, not the default stretch: the row now has height to spare, and stretch would pull a
   thumbnail that has no explicit height out of shape.
   ⚠ min-height = one unit thumbnail, and the arena above must NOT carry `min-height: 0`. Together
   those are what stop the arenas collapsing: flex:1 1 0 alone lets a flex item shrink past its
   content, and on the short mobile tile both arenas duly shrank to 10px — padding and border, no
   cards — while looking fine on the roomy desktop tile. Leaving min-height at its `auto` default
   floors each arena at label + one card row, so the split stays even AND nothing disappears. */
.swu-mb-row { display: flex; gap: 4px; overflow-x: auto; padding-bottom: 2px;
    flex: 1 1 auto; align-items: flex-start; min-height: 33px; }
body.swu-home .swu-mb-row { min-height: 50px; }
/* ⚠ 33 / 50 = the unit thumbnail's height PLUS its 1px border each side (.swu-mb-card is content-box,
   so a 48px card occupies 50px). Using the bare 31/48 left an empty arena 2px shorter than a populated
   one — invisible on desktop, where surplus height lets flex-grow even them out anyway, but plainly
   uneven on the mobile tile where both sit on the floor. */
.swu-mb-row::-webkit-scrollbar { height: 4px; }
.swu-mb-row::-webkit-scrollbar-thumb { background: #334; border-radius: 2px; }
/* A legal-target mini card during a decision: green glow + clickable (mirrors the main board's
   .selectable-card). Overrides an exhausted card's dim so a valid target still reads clearly. */
.swu-mb-card.mini-selectable { cursor: pointer; outline: 2px solid #2ecc71; outline-offset: 0;
    box-shadow: 0 0 9px 3px rgba(46,204,113,0.9); filter: none; z-index: 3;
    animation: swuGoBackPulse 1.2s ease-in-out infinite; }
.swu-mb-card.mini-selectable:hover { outline-color: #6cff9a; box-shadow: 0 0 13px 4px rgba(46,204,113,1); }

/* Home "replace" mode (shared bits): the preview windows take over the opponent's board region — cards
   scale up to suit the larger area. The container-fill + opponent-zone hiding are per-layout (each
   layout's own zone geometry). Only applies on the home view (body.swu-home). */
body.swu-home .swu-home-strip { max-width: none; justify-content: center; }
body.swu-home .swu-mb-leader { width: 61px; height: 44px; }   /* landscape, ~628:450 (see .swu-mb-leader) */
body.swu-home .swu-mb-base   { width: 66px; height: 46px; }
body.swu-home .swu-mb-unit   { width: 34px; height: 48px; }
body.swu-home .swu-mb-basedmg { font-size: 18px; }
body.swu-home .swu-mb-dmg { font-size: 10px; }

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
/* ⚠ img[id$="-img"] — the CARD ART ONLY, not every <img> in the card. Card() gives the art element
   id="<mzID>-img"; the overlays it stacks on top (Epic-Action-Used, the Force token, counter icons)
   are plain <img> with no id. A bare `[data-mzid] img` selector caught those too and, being
   width:100% !important, beat their inline 22px: on an 84px two-leader card the Epic-Action-Used
   icon rendered 75x75 and all but covered the leader. 2P never showed it because this whole block
   only matches a wrapper holding a SECOND leader. */
#myLeaderWrapper:has([data-mzid="myLeader-1"]) [data-mzid] img[id$="-img"],
#theirLeaderWrapper:has([data-mzid="theirLeader-1"]) [data-mzid] img[id$="-img"] {
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
    padding: 46px 22px 20px !important;   /* top strip reserved for #swuEndGameToggle (absolute) */
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

/* ⚠ CATCH-ALL PLACEMENT for stray children — this is what made "Return to Menu" collide with the
   minimise control, and it is NOT a spacing problem, which is why two rounds of clearance did not fix
   it. The overlay is a NAMED grid, and a direct child with no grid-area gets AUTO-PLACED into an
   IMPLICIT cell — top-right, on top of the control. The same trap the .has-subtitle rule below already
   warns about. It shows as the button "sliding" because implicit placement is recomputed as async code
   adds children: MatchReplayClient.addGameOverButton falls back to `target = overlay` whenever
   #game-over-stats is absent (hotseat), inserting a bare child; hotseat's own panel does the same.
   Forcing every unplaced child into the buttons area means no future end-game path can land one in the
   control's corner, whatever it builds. Keep this LAST-but-one so the named children still win. */
#game-over-overlay > *:not(#game-over-title):not(#game-over-subtitle):not(#game-over-buttons):not(#game-over-stats):not(#swuEndGameToggle) {
    grid-area: buttons;
}

/* ── Minimise / restore ───────────────────────────────────────────────────────────────────────
   The panel covers 80% of the board, and the whole reason it is a panel rather than a full-screen
   takeover is that the board and chat stay usable around it — but reviewing the board UNDER it still
   meant having no way out. Minimised it collapses to a title bar in the bottom-left, leaving the
   board clear, and the same button restores it.
   ⚠ The overlay is a NAMED grid, so minimising is not just "hide the children": an unplaced child in
   a grid with named areas gets AUTO-placed into an implicit cell, so the collapsed state has to
   declare its own areas too, exactly like .has-subtitle above. */
/* ⚠ ABSOLUTE, not a grid item. Placing it in a grid area only works if you know which container the
   panel's buttons live in — and that varies by end-game path (hotseat's panel does not use
   #game-over-buttons, which is why clearing THAT container did not fix it). Taking the control out of
   flow and reserving its strip with padding on the OVERLAY clears every child of every container at
   once, whatever the path builds. */
#swuEndGameToggle {
    position: absolute; top: 10px; right: 12px;
    pointer-events: auto; cursor: pointer; z-index: 3;
    width: 26px; height: 26px; padding: 0; line-height: 1;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 6px; border: 1px solid var(--border, #2a3a4a);
    background: var(--swu-surface, rgba(10,20,30,0.85));
    color: var(--accent-strong, #f0c040); font: 700 15px/1 var(--swu-font-label, sans-serif);
}
#swuEndGameToggle:hover { border-color: var(--accent-strong, #f0c040); background: rgba(10,20,30,0.98); }
#game-over-overlay.is-minimized {
    inset: auto auto 16px 16px !important;
    width: auto !important; height: auto !important;
    max-width: min(360px, 60vw) !important;
    padding: 8px 44px 8px 14px !important;   /* right strip reserved for the absolute control */
    grid-template-columns: minmax(0, 1fr) !important;
    grid-template-rows: auto !important;
    grid-template-areas: "title" !important;
    column-gap: 0 !important; row-gap: 0 !important;
    align-items: center !important;
    overflow: hidden !important;
}
/* Every other child is hidden — including any the shared markup adds later, hence the child selector
   rather than a list of ids that would silently miss a new one. */
#game-over-overlay.is-minimized > *:not(#game-over-title):not(#swuEndGameToggle) { display: none !important; }
#game-over-overlay.is-minimized #game-over-title {
    grid-area: title !important; align-self: center !important; justify-self: start !important;
    margin: 0 !important; font-size: 15px !important; letter-spacing: 0.5px !important;
    white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important;
}
#game-over-overlay.is-minimized #swuEndGameToggle { top: 50%; transform: translateY(-50%); }

/* "Winner(s): …" line under the title. It only exists when the caller names winners (Twin Suns,
   where three of four seats read "You Lost" and nothing else would say who took it), so the extra
   grid row is scoped to .has-subtitle — otherwise every ordinary game would pay a row-gap of dead
   space for an empty area. An unplaced child in a grid with named areas gets AUTO-placed into an
   implicit cell, which is why this needs its own area rather than just appearing in the DOM. */
#game-over-overlay.has-subtitle {
    grid-template-rows: auto auto minmax(0, 1fr) !important;
    grid-template-areas: "title title" "subtitle subtitle" "buttons stats" !important;
}
#game-over-subtitle {
    grid-area: subtitle !important;
    margin: 0 !important; align-self: center !important;   /* the grid row-gap does the spacing */
    font-size: clamp(14px, 1.5vw, 20px) !important;
    letter-spacing: 1px !important;
    color: var(--text) !important; opacity: 0.9 !important;
}

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
    gap: 10px !important; align-self: stretch !important;
    /* No clearance needed here: the minimise control is absolute and the overlay's own top padding
       reserves its strip, so every child of every container starts below it. */
    margin: 0 !important;
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
    #game-over-overlay.has-subtitle {
        grid-template-rows: auto auto auto minmax(0, 1fr) !important;
        grid-template-areas: "title" "subtitle" "buttons" "stats" !important;
    }
    #game-over-subtitle { font-size: clamp(12px, 2.6vw, 16px) !important; letter-spacing: 0.5px !important; }
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
    /* Wide enough for 6 cards in one row (the square 124px concat tiles: 6 x 124 + 5 x 6px gap = 774px),
       but never wider than the game area (board = 100vw - sidebar) so it can't spill under the chat
       sidebar on a narrow window (there it falls back to wrapping). */
    max-width: min(786px, calc(var(--swu-board-w, 92vw) - 40px)) !important;
    max-height: 46vh !important; overflow-y: auto !important; overflow-x: hidden !important;
}
/* Center the mulligan modal on the GAME AREA, not the whole viewport — i.e. excluding the right
   chat/log sidebar — matching the board's other centered elements (translateX(... - sidebar/2)).
   The 0px fallback means no shift on mobile / when no sidebar var is defined. Marker set by the
   ShowYesNoDecisionPopup wrapper so this only affects the mulligan prompt, not every YES/NO modal. */
#yesno-decision-modal[data-swu-mulligan] > .yesno-decision-panel {
    transform: translateX(calc(-1 * var(--swu-sidebar-w, 0px) / 2)) !important;
}
.swu-mulligan-hand .swu-mulligan-card {
    display: inline-flex !important; cursor: pointer !important; line-height: 0 !important;
    border-radius: 5px !important; transition: transform 0.12s ease !important;
}
.swu-mulligan-hand .swu-mulligan-card:hover { transform: translateY(-3px) !important; }
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
/* max-height is the third trigger on purpose: a 780x438 laptop clears the 760px width
   breakpoint by 20px AND is landscape, so it kept the full 124px tiles — 6 of them
   (774px) no longer fit, the 6th wrapped to a second row, and that row collided with
   the prompt text while pushing YES/NO down onto the hand band. At 92px the six tiles
   are 572px and stay on one row. */
@media (orientation: portrait), (max-width: 760px), (max-height: 560px) {
    .swu-mulligan-hand img { height: 92px !important; }
    .swu-mulligan-hand { gap: 4px !important; margin-bottom: 12px !important; }
}

/* ── Short viewports — keep decision panels inside the window ──────────────────
   Every panel above is positioned for a tall window. At 780x438 the mulligan modal
   filled the entire viewport and its buttons landed on top of the hand band. Cap the
   panels to the viewport and let them scroll instead of overlapping the board.
   Height-gated, so nothing above 560px tall is affected. */
@media (max-height: 560px) {
    #topdecksearch-panel > div, #scry-panel > div, #revealarrange-panel > div,
    #yesno-decision-modal > div, .optchoose-banner {
        max-height: calc(100vh - 12px) !important;
        max-width:  calc(100vw - 12px) !important;
        overflow-y: auto !important; overflow-x: hidden !important;
    }
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
/* MZSplitAssign +/- steppers → chamfered HUD, red (minus) / green (plus). The design-system migration
   (7468247d) dropped the .mzsplit-* selectors from the decision-UI chamfer sweep, so these steppers fell
   back to the bespoke ROUND .mzsplit-btn styling in Core/MZSplitAssignUI.js. Re-skin them here like the
   sibling .numchoose steppers, keeping the +/- red/green affordance — all theme-driven (danger/success),
   so they follow petranaki-hud (and every other theme). !important beats the non-!important base rules. */
.mzsplit-btn-minus, .mzsplit-btn-plus {
    position: relative !important; z-index: 0 !important; isolation: isolate !important;
    border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important;
    transition: filter 150ms, color 150ms, transform 110ms !important;
}
.mzsplit-btn-minus::before, .mzsplit-btn-plus::before {
    content: '' !important; position: absolute !important; inset: 0 !important; z-index: -2 !important;
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px) !important;
}
.mzsplit-btn-minus::after, .mzsplit-btn-plus::after {
    content: '' !important; position: absolute !important; inset: 1.5px !important; z-index: -1 !important;
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px) !important;
}
.mzsplit-btn-minus            { color: var(--on-danger) !important; text-shadow: 0 0 6px rgba(0,0,0,0.5) !important; filter: drop-shadow(0 0 4px var(--danger)) !important; }
.mzsplit-btn-minus::before    { background: var(--danger) !important; }
.mzsplit-btn-minus::after     { background: var(--danger-surface) !important; }
.mzsplit-btn-minus:hover:not(:disabled) { color: #fff !important; filter: drop-shadow(0 0 9px var(--danger)) !important; transform: translateY(-1px) !important; }
.mzsplit-btn-plus             { color: var(--on-success) !important; text-shadow: 0 0 6px rgba(0,0,0,0.5) !important; filter: drop-shadow(0 0 4px var(--success)) !important; }
.mzsplit-btn-plus::before     { background: var(--success) !important; }
.mzsplit-btn-plus::after      { background: var(--success-surface) !important; }
.mzsplit-btn-plus:hover:not(:disabled)  { color: #fff !important; filter: drop-shadow(0 0 9px var(--success)) !important; transform: translateY(-1px) !important; }
.mzsplit-btn-minus:disabled, .mzsplit-btn-plus:disabled { color: var(--text-muted) !important; filter: none !important; }
.mzsplit-btn-minus:disabled::before, .mzsplit-btn-plus:disabled::before { background: var(--border) !important; }
.mzsplit-btn-minus:disabled::after,  .mzsplit-btn-plus:disabled::after  { background: var(--surface-sunken) !important; }
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

        // The home tiles name the initiative holder per seat; repaint them from here, since this is
        // the one place that already runs on every initiative change.
        if (typeof swuTwHighlightActiveSeat === 'function') swuTwHighlightActiveSeat();

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

    // Team Suns (2v2). Orthogonal to SWUGameMode() — that answers goldfish/hotseat/normal and returns
    // '' here — so it gets its own flag, exactly as the server keeps SWU_MODE_TEAMS separate.
    window.SWUIsTeamGame = <?php echo (function_exists('SWUIsTeamGame') && SWUIsTeamGame()) ? 'true' : 'false'; ?>;
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

    // ── Mount the chat COMPOSER in the sidebar ────────────────────────────────
    // Only the input row is wanted here now: the messages themselves go into the game log via
    // TCGChatMessageSink below, so #chatExpanded/#chatLog are hidden by CSS in merged mode.
    function mountChat() {
        var chatWidget = document.getElementById('chatWidget');
        var mount      = document.getElementById('swuChatMount');
        if (!chatWidget || !mount) return;
        mount.appendChild(chatWidget);
    }

    // ── Chat merged INTO the game log ─────────────────────────────────────────
    // ONE stream, no Log/Chat tabs. Players were not discovering that the panel could be switched,
    // so chat arrived in a tab nobody had open. Core's _AppendChatMessage builds the element and
    // hands it here; we drop it into #swuLogPanel, where it interleaves with game events in
    // ARRIVAL order (log entries carry no timestamp — "TYPE|VISIBILITY|text" — so true chronological
    // interleaving is not available; on first load the chat backlog therefore lands after the log
    // backlog, and everything after that is genuinely in order because both ride the same poll).
    // ⚠ Returns FALSE in floating-chat mode — the <800px desktop breakpoint hides #swuSidebar (and
    //   with it the log panel) and restores the "💬 Chat" launcher, so #chatLog + its toast are the
    //   only visible surface there. Core then falls back to them. Detected via a VISIBLE
    //   #chatToggleBtn rather than a width test: the mobile layout kills that button outright, so
    //   mobile always takes the merged path even while its drawer is closed.
    window.TCGChatMessageSink = function(el) {
        var toggle = document.getElementById('chatToggleBtn');
        if (toggle && toggle.getClientRects().length > 0) return false;
        var panel = document.getElementById('swuLogPanel');
        if (!panel) return false;
        el.style.cssText = '';                       // drop Core's inline sizing; SWUSim styles it
        el.classList.add('swu-log-entry', 'swu-log-CHAT');
        var nearBottom = (panel.scrollHeight - panel.scrollTop - panel.clientHeight) < 60;
        panel.appendChild(el);
        if (nearBottom) panel.scrollTop = panel.scrollHeight;
        return true;
    };

    // ── Card link hover ───────────────────────────────────────────────────────
    window.swuLogCardHover = function(event, cardId) {
        ShowDetail(event, '/TCGEngine/AppCore/SWU/Images/concat/' + (typeof resolveCardImageID === 'function' ? resolveCardImageID(cardId) : cardId) + '.webp');
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
        // Unitless twin. CSS can't divide two lengths to get a ratio, and the counter/badge
        // overlays are sized in px by the engine (Core/CounterRendering.js writes inline
        // width/height from the schema's Size=), so scaling them needs a plain number.
        // 80 is the reference card size those schema sizes were chosen against.
        document.documentElement.style.setProperty('--swu-cardsize-n', String(cs));
        syncPassReserveVar();
    }

    // Publish how much vertical room the initiative/pass cluster needs, so the centre lane can
    // keep clear of it (GameLayout's min-height:681px cap on --swu-center-w). Measured rather
    // than hardcoded: the button font is a vw clamp, so the cluster is taller on wide screens,
    // and any constant here would rot the next time the buttons are restyled.
    //
    // The reserve is the WORST case — two rows — even while Take/Keep Initiative is `hidden`.
    // Reserving only what is currently on screen would re-widen the lane whenever the button
    // is not offered, so every claim/pass would visibly re-flow the board.
    function syncPassReserveVar() {
        var btn = document.getElementById('swuPassBtn');
        if (!btn) return;
        var rowH = btn.getBoundingClientRect().height;
        if (rowH <= 0) return;                       // not laid out yet; the CSS fallback stands
        // Take/Keep sits above Pass with the same class, so two rows plus the 4px gap between them,
        // plus the clearance above the hand.
        var reserve = rowH * 2 + 4 + 10;
        document.documentElement.style.setProperty('--swu-pass-reserve-h', Math.round(reserve) + 'px');
    }
    function pollGlobals() {
        syncCardSizeVar();
        swuInitPairSwitcher();   // sets window.swuSpectating BEFORE the glows read it
        updatePhaseTrack(); updateInitiative(); updateRound(); refreshActionGlows();
        swuRenderBaseTabs('my'); swuRenderBaseTabs('their');
        swuRenderHomeStrips();
        refreshResourceSelectionPanel();
        swuUpdateUndoUI(MY_PLAYER_ID);
    }
    function watchGlobalData() {
        pollGlobals();
        // ⚠ The end-game toggle is NOT driven from pollGlobals: that runs on data change, not on a
        // timer, so an overlay that appears while the board is idle would never get its control. The
        // overlay is also torn down and rebuilt by the replay client, so a one-shot hook is not enough
        // either. A light poll covers both — swuEnsureEndGameToggle early-returns unless the overlay
        // is up and the button is missing, so the steady-state cost is one getElementById.
        setInterval(swuEnsureEndGameToggle, 700);
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
                    // Twin Suns: the rendered mzID already names the pile ("p3Discard-4"), so the seat
                    // is read off the zone prefix rather than needing a new data attribute. 2-player
                    // renders "theirDiscard-4" and keeps the seatless token byte-identical.
                    var seatM = /^p(\d+)Discard$/.exec(parts[0] || '');
                    e.stopPropagation();
                    e.preventDefault();
                    if (owner === 'opp') {
                        var tok = seatM ? ('PlayFromOpponentDiscard-' + seatM[1] + '-' + idx)
                                        : ('PlayFromOpponentDiscard-' + idx);
                        SubmitInput('10001', '&cardID=' + encodeURIComponent(tok + '!CustomInput!'));
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

    // The Twin Suns ORDER STRIP (swuRenderOrderStrip) was removed here. It drew a fixed top-centre row
    // of seat chips whose only live signal was a green ring on the turn player — the same thing the
    // home strips now show in place, on the board the player is already looking at. Two indicators for
    // one fact, in two colour languages, is worse than one.
    // ⚠ It also rendered `myActionsData.roundState`'s third value, 'took-counter'
    // (_SWUSeatTookCounterThisRound), which nothing displays now. The server still computes and sends
    // it, so re-surfacing it is a client-only change if it turns out to be wanted.

    // ── Twin Suns pair-switcher ───────────────────────────────────────────────
    // Build the ordered list of views for THIS viewer from SeatOrder/LiveSeats. For 3+ players it's the
    // two-level model: a 'home' view (you vs everyone — one mini-board preview per opponent) followed by
    // one 'matchup' view per opponent (you vs that one seat). Returns [] at ≤2 seats (no switcher shown).
    // Egocentric by design: every view's viewSeat is YOU (no opp-vs-opp spectate view).
    // Team Suns: mirrors the server's SWUTeamOf() — seat parity, seats 1,3 = Red and 2,4 = Blue.
    // Outside a team game every seat is its own team, so swuIsTeammate() is always false and every
    // downstream branch degenerates to Twin Suns behaviour.
    // ⚠ Keep the parity rule HERE ONLY, as it is server-side. Do not inline `% 2` anywhere else.
    function swuTeamOf(seat) { return window.SWUIsTeamGame ? (seat % 2) : seat; }
    function swuIsTeammate(seat) {
        return !!window.SWUIsTeamGame && seat !== MY_PLAYER_ID && swuTeamOf(seat) === swuTeamOf(MY_PLAYER_ID);
    }

    function swuBuildViews() {
        var order = String(window.LiveSeatsData || window.SeatOrderData || '').trim();
        var seats = order.length ? order.split('').map(function (c) { return parseInt(c, 10); }) : [];
        var me = MY_PLAYER_ID;
        if (seats.length <= 2) return [];
        var opps = seats.filter(function (s) { return s !== me; });
        var views = [{ viewSeat: me, oppSeat: opps[0], mode: 'home', opps: opps, label: 'Home' }];
        opps.forEach(function (o) {
            // Team Suns: your teammate is not an opponent, so "vs P3" is wrong for them. ONLY the label
            // changes — the view list, its order, viewSeat/oppSeat/mode and the home view's `opps` array
            // are all untouched, so the board and preview tiles render exactly as in Twin Suns.
            // ⚠ Do NOT filter the teammate out of `opps`. That would drop their preview tile from the
            // home view and their entry from the carousel, breaking Zoom In on your own teammate — the
            // opposite of USER RULING 2026-08-25 (the home view stays as it is; Zoom In is how you look
            // at your ally's board).
            views.push({ viewSeat: me, oppSeat: o, mode: 'matchup',
                         label: (swuIsTeammate(o) ? 'P' : 'vs P') + o });
        });
        return views;
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
        // The optional '.uN' tail addresses a SUBCARD (an upgrade/token on the unit at index N).
        // It must take part in the match, or a subcard click would remap onto whichever spec merely
        // shares its host and submit the wrong target.
        var m = /^(.+)-(\d+)(?:\.u(\d+))?$/.exec(cardId || ''); if (!m) return cardId;
        var zone = m[1], idx = parseInt(m[2], 10);
        var sub = (m[3] !== undefined) ? parseInt(m[3], 10) : null;
        var sp = sm.allowedZones.find(function (s) {
            return s && s.isSpecificCard && s.zone === zone && s.specificIndex === idx
                && (((s.subIndex === null || s.subIndex === undefined) ? null : s.subIndex) === sub);
        });
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
        // Home "replace" mode: on the home view the preview windows take over the opponent's board
        // region entirely (opponent zones hidden via body.swu-home CSS).
        document.body.classList.toggle('swu-home', !!(window.swuView && window.swuView.mode === 'home'));
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

    // A view set is "two-level" (3-player home ⇄ matchup) iff it contains a 'home' view. 4-player has
    // no home view and keeps the carousel arrows.
    function swuIsTwoLevel() {
        return (window.swuViews || []).some(function (v) { return v.mode === 'home'; });
    }

    // Indices of the matchup ("you vs one seat") views, in view order.
    function swuMatchupIndices() {
        var out = [];
        (window.swuViews || []).forEach(function (v, i) { if (v.mode === 'matchup') out.push(i); });
        return out;
    }

    // One step of the ◀ / ▶ arrows. In the two-level model a matchup CYCLES within the matchup views
    // and wraps, so paging past the last opponent lands on the first rather than falling into the home
    // view (index 0) — home has its own button, and a wrap that silently changes what KIND of view you
    // are on is the sort of thing that makes a carousel feel broken. Flat carousels keep the old
    // clamped linear step.
    function swuStepView(delta) {
        var views = window.swuViews || [];
        var idx = (window.swuView && typeof window.swuView.index === 'number') ? window.swuView.index : 0;
        if (swuIsTwoLevel() && window.swuView && window.swuView.mode === 'matchup') {
            var m = swuMatchupIndices();
            var at = m.indexOf(idx);
            if (at === -1) { swuSetView(m.length ? m[0] : idx); return; }
            swuSetView(m[(at + delta + m.length) % m.length]);
            return;
        }
        if (idx + delta < 0 || idx + delta >= views.length) return;
        swuSetView(idx + delta);
    }

    function swuRenderPairNav() {
        var nav = document.getElementById('swuPairNav'); if (!nav) return;
        var views = window.swuViews || [];
        var prev = document.getElementById('swuPairPrev'), next = document.getElementById('swuPairNext');
        var back = document.getElementById('swuGoBack');
        if (views.length <= 1) {                        // 2-player: no switcher
            if (prev) prev.style.display = 'none';
            if (next) next.style.display = 'none';
            if (back) back.style.display = 'none';
            nav.style.display = 'none';
            return;
        }
        var idx = (window.swuView && typeof window.swuView.index === 'number') ? window.swuView.index : 0;
        if (swuIsTwoLevel()) {
            // Two-level (3+ player). HOME shows neither arrows nor Go-back — every opponent is already
            // on screen as a tile, so there is nothing to page through.
            // ZOOMED IN (a matchup) shows Go-back → home AND the ◀ ▶ arrows, which CYCLE between the
            // other opponents without going back out to home first. The arrows were built for the flat
            // 4-player carousel and were switched off wholesale when the two-level model landed; this
            // brings them back for the only place they still make sense.
            var onMatchup = !!(window.swuView && window.swuView.mode === 'matchup');
            var matchups = swuMatchupIndices();
            var showArrows = onMatchup && matchups.length > 1;
            if (prev) prev.style.display = showArrows ? 'flex' : 'none';
            if (next) next.style.display = showArrows ? 'flex' : 'none';
            nav.style.display = showArrows ? 'contents' : 'none';
            if (back) back.style.display = onMatchup ? 'block' : 'none';
        } else {
            // Carousel (4-player): arrows as before; no Go-back.
            if (back) back.style.display = 'none';
            if (prev) prev.style.display = (idx > 0) ? 'flex' : 'none';
            if (next) next.style.display = (idx < views.length - 1) ? 'flex' : 'none';
            nav.style.display = 'contents';   // wrapper is display:contents; children are fixed to the edges
        }
        swuTwRenderTargetBadges();
    }

    // Cross-view targeting: render a glowing count pill on ◀ / ▶ for legal targets on OFF-view seats
    // during an active decision. Bucket each off-view target by direction (a view index below the
    // current = ◀, above = ▶). Clears when no decision / no off-view targets. Exposed for the UILibraries
    // MZCHOOSE hook + swuSetView re-apply.
    // MOBILE: the seat ROWS carry the targeting cue, because a summary row has no thumbnail to glow.
    // ⚠ Buckets _twAllSpecs, NOT _twOffView. "Off view" excludes the CURRENT view's own opponent, so on
    // the home view one seat's targets are treated as on-board and its row would never light up —
    // measured: a 3-target Arrest reported off=[p3,p4] with p2 missing. On mobile every opponent is
    // off-board by construction, so the full candidate list is the right source.
    function swuTwRenderRowTargets() {
        var rows = document.querySelectorAll('#swuHomeStrips .swu-seat-row');
        if (!rows.length) return;
        var sm = window.SelectionMode || {};
        var specs = (sm.active && (sm._twAllSpecs || sm._twOffView)) || [];
        var bySeat = {};
        specs.forEach(function (spec) {
            var seat = parseInt((String(spec.zone || '').match(/^p(\d+)/) || [])[1], 10);
            if (seat) bySeat[seat] = (bySeat[seat] || 0) + 1;
        });
        var anyTargets = Object.keys(bySeat).length > 0;
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i], seat = parseInt(row.getAttribute('data-seat'), 10);
            var n = bySeat[seat] || 0;
            row.classList.toggle('is-target-source', n > 0);
            row.classList.toggle('is-target-dimmed', anyTargets && n === 0);
            var pills = row.querySelector('.swu-sr-pills');
            var badge = row.querySelector('.swu-sr-count');
            if (n > 0 && pills) {
                if (!badge) { badge = document.createElement('span'); badge.className = 'swu-sr-count'; pills.appendChild(badge); }
                badge.textContent = String(n);
            } else if (badge) { badge.remove(); }
        }
    }
    window.swuTwRenderRowTargets = swuTwRenderRowTargets;

    function swuTwRenderTargetBadges() {
        var off = (window.SelectionMode && window.SelectionMode.active && window.SelectionMode._twOffView) || [];

        if (swuIsTwoLevel()) {
            // Highlight the actual legal-target mini cards in each preview (like the main board), so you
            // can click a target directly on the home view.
            swuHighlightPreviewTargets();
            // Pulse Go-back on a matchup view if there's an off-view target to go back for.
            var back = document.getElementById('swuGoBack');
            if (back) back.classList.toggle('is-pulsing',
                !!(window.swuView && window.swuView.mode === 'matchup' && off.length > 0));
            return;
        }

        // Carousel (4-player): ◀/▶ count badges (original behavior).
        var prev = document.getElementById('swuPairPrev'), next = document.getElementById('swuPairNext');
        function setBadge(arrow, n) {
            if (!arrow) return;
            var bb = arrow.querySelector('.swu-target-badge');
            if (!n) { if (bb) bb.remove(); return; }
            if (!bb) { bb = document.createElement('span'); bb.className = 'swu-target-badge'; arrow.appendChild(bb); }
            bb.textContent = String(n);
        }
        swuTwRenderRowTargets();   // mobile rows carry their own cue
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

    // Highlight (and make clickable) each mini-board card that is a legal target of the active decision.
    // Reads the RAW decision specs (SelectionMode._twAllSpecs — seat-tagged p{n}… for Twin Suns, or
    // my/their in the degenerate case) and marks matching preview cards by their data-mz. A card is a
    // target if a spec covers its whole zone, or names its exact index. Clears when no decision.
    function swuHighlightPreviewTargets() {
        var cards = document.querySelectorAll('#swuHomeStrips .swu-mb-card[data-mz]');
        var sm = window.SelectionMode;
        var specs = (sm && sm.active && sm._twAllSpecs) ? sm._twAllSpecs : [];
        function seatOfZone(z) {
            var m = String(z).match(/^p(\d+)/); if (m) return parseInt(m[1], 10);
            if (String(z).indexOf('my') === 0)    return (window.swuView && window.swuView.viewSeat) || 0;
            if (String(z).indexOf('their') === 0) return (window.swuView && window.swuView.oppSeat) || 0;
            return 0;
        }
        var legal = {};   // "p{seat}{Suffix}-{idx}" (exact) or "p{seat}{Suffix}-*" (whole zone)
        specs.forEach(function (sp) {
            if (!sp || !sp.zone || sp.actionPayload) return;
            var seat = seatOfZone(sp.zone); if (!seat) return;
            var suffix = String(sp.zone).replace(/^(p\d+|my|their)/, '');   // GroundArena / SpaceArena / Base
            if (sp.isSpecificCard) legal['p' + seat + suffix + '-' + sp.specificIndex] = true;
            else                   legal['p' + seat + suffix + '-*'] = true;
        });
        cards.forEach(function (el) {
            var mz = el.getAttribute('data-mz');                 // p{seat}{Suffix}-{idx}
            var m = /^(p\d+[A-Za-z]+)-(\d+)$/.exec(mz);
            var hit = !!m && (!!legal[m[1] + '-' + m[2]] || !!legal[m[1] + '-*']);
            el.classList.toggle('mini-selectable', hit);
        });
    }
    window.swuHighlightPreviewTargets = swuHighlightPreviewTargets;

    // Submit a clicked preview target through the active decision. The mini card's data-mz IS the engine's
    // seat-tagged mzID (p{seat}{Zone}-{idx}), which is exactly what the decision expects — the callback's
    // swuTwRemapCardId leaves an already-seat-tagged id untouched.
    function swuPreviewTargetClick(cardEl) {
        var sm = window.SelectionMode;
        if (!(sm && sm.active && typeof sm.callback === 'function')) return;
        var mz = cardEl.getAttribute('data-mz'); if (!mz) return;
        var m = /^(.+)-(\d+)$/.exec(mz);
        sm.callback(m ? m[1] : mz, mz, sm.decisionIndex);
    }

    function swuInitPairSwitcher() {
        window.swuViews = swuBuildViews();
        if (!window.swuViews.length) {                                        // 2-player: no switcher
            window.swuView = undefined; window.swuSpectating = false;
            document.body.classList.remove('swu-spectating');
            document.body.classList.remove('swu-home');
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
        if (prev && !prev._swuWired) { prev._swuWired = 1; prev.addEventListener('click', function () { swuStepView(-1); }); }
        if (next && !next._swuWired) { next._swuWired = 1; next.addEventListener('click', function () { swuStepView(1); }); }
        var back = document.getElementById('swuGoBack');
        if (back && !back._swuWired) { back._swuWired = 1; back.addEventListener('click', function () {
            var views = window.swuViews || [];
            var home = 0;
            for (var i = 0; i < views.length; i++) if (views[i].mode === 'home') { home = i; break; }
            swuSetView(home);
        }); }
        var strips = document.getElementById('swuHomeStrips');
        if (strips && !strips._swuWired) { strips._swuWired = 1; strips.addEventListener('click', function (e) {
            var t = e.target;
            // Zoom-in button → open that opponent's you-vs-1 matchup.
            var zoom = t.closest && t.closest('.swu-mb-zoom');
            if (zoom) { var tile = zoom.closest('.swu-home-strip'); if (tile) swuSetView(parseInt(tile.getAttribute('data-view'), 10)); return; }
            // Discard chip → open that seat's discard pile.
            var zoneBtn = t.closest && t.closest('#swuHomeStrips [data-zone]');
            if (zoneBtn) {
                e.stopPropagation();
                if (typeof ShowZonePopup === 'function') ShowZonePopup(zoneBtn.getAttribute('data-zone'));
                return;
            }
            // A highlighted legal-target card → select it (cross-view targeting straight from the preview).
            var card = t.closest && t.closest('#swuHomeStrips .mini-selectable');
            if (card) swuPreviewTargetClick(card);
        }); }
        swuRenderPairNav();
    }

    // Read a seat's zones from the cached responseArr (stride-31 blocks). Offsets: Leader=5, Base=6,
    // GroundArena=7, SpaceArena=8 (per NextTurnRender's window.*Data bindings). Units are parsed to
    // {CardID, Status, Damage} so the mini-board can render art + exhaust + damage; counts derive from
    // the parsed arrays.
    function swuReadSeatBlock(seat) {
        var arr = window.swuLastResponseArr; if (!arr) return null;
        var b = (seat - 1) * 31;
        function zone(off) { return String(arr[off + b] || '').trim(); }
        function units(s) {
            if (!s.length) return [];
            return s.split('<|>').map(function (p) {
                var o = swuParseZoneCard(p) || {};
                return {
                    CardID: String(p).trim().split(' ')[0],   // raw part keeps the underscore (SOR_032)
                    Status: o.Status,
                    Damage: parseInt(o.Damage, 10) || 0
                };
            });
        }
        var leaderData = zone(5);
        var ground = units(zone(7)), space = units(zone(8));
        // Offsets, for the next person counting on their fingers: the payload's first field is the
        // update number, so a seat's zones start at 1 — 1 Deck, 2 Hand, 3 Discard, 4 Resources,
        // 5 Leader, 6 Base, 7 GroundArena, 8 SpaceArena.
        var deckRaw = zone(1), discardRaw = zone(3);
        return {
            baseObj: swuParseZoneCard(zone(6)),
            leaders: leaderData.length ? leaderData.split('<|>') : [],
            groundUnits: ground,
            spaceUnits: space,
            groundCount: ground.length,
            spaceCount: space.length,
            // THE SAME parser the main board's resource badge uses, deliberately: the preview must not
            // be able to disagree with the board you get when you zoom in. It works unchanged on an
            // opponent's masked data — real resources arrive as "CardBack 0 {Status:N}" (identity
            // hidden, ready/exhausted intact, which is all ready/total needs), while Credit tokens
            // arrive with their real LAW_T01 id because credits are public information.
            res: (typeof parseResCountFromData === 'function')
                ? parseResCountFromData(zone(4)) : { ready: 0, total: 0, credits: 0 },
            // A private zone renders as one "CardBack <count>" entry, so the COUNT is field 1.
            // ⚠ An empty deck now emits nothing at all (it renders as the empty pile frame), so the
            // whole piece is '' — hence the guard rather than trusting a split.
            deckCount: (function () {
                if (!deckRaw) return 0;
                var n = parseInt(deckRaw.split(' ')[1], 10);
                return isFinite(n) ? n : 0;
            }()),
            // The discard is PUBLIC, so every card is listed — count the entries.
            discardCount: discardRaw
                ? discardRaw.split('<|>').filter(function (x) { return x.trim().length; }).length : 0
        };
    }

    // One opponent's miniature board: row 1 = [Leader1] [Leader2] … [Base]; row 2 = [Space | Ground]
    // with tiny real card-art thumbnails. Statuses mirror the live board: leaders ghost when deployed
    // and dim/tilt when exhausted; base shows a centered damage number; units tilt/dim when exhausted
    // and carry a corner damage badge. Arena rows scroll horizontally on overflow.
    // Hover attributes for a mini-board thumbnail: blow the card up the way the full board does.
    // These are background-image spans, not <img>, so the preview is driven from the CardID —
    // ShowMiniCardDetail (Core/jsInclude.js) resolves the art path and reuses the normal preview.
    // ⚠ The RESOLVED id (resolveCardImageID) is what goes in, so preview/mock cards preview correctly.
    function swuMbHoverAttrs(resolvedCardID) {
        if (!resolvedCardID) return '';
        return "data-card-id='" + String(resolvedCardID).replace(/'/g, '&#39;') + "'" +
               " onmouseover='ShowMiniCardDetail(event, this)' onmouseout='HideCardDetail()'";
    }

    // Fortify / arrest pips on a preview tile's base — the tile-sized echo of the FORTIFIED / ARRESTED
    // tabs on the full board. Overlaid on the thumbnail's corners rather than placed beside it, so a
    // fortified seat's row 1 stays exactly as wide as everyone else's (the tiles are a comparison view;
    // anything that changes width per seat breaks the comparison). The centre is left to the damage
    // counter. Counts only — no popup here: the tile is a glance, and a captive is face down anyway.
    // Overlays ON the base thumbnail: the Force top-right and Epic-Action-Used bottom-right, mirroring
    // where the full board puts them on the base card. The CENTRE stays reserved for the damage token.
    // ⚠ Effect COUNTS are deliberately not here any more — they live in the fixed three-row column
    // beside the base (swuMbFxColumn), so the base's corners are free for these two states and the
    // counts have somewhere to grow.
    function swuMbBaseOverlays(baseObj) {
        if (!baseObj) return '';
        var truthy = function (v) { return v === true || v === 'true' || v === 1 || v === '1'; };
        var html = '';
        if (truthy(baseObj.HasForce))
            html += "<img class='swu-mb-cardicon swu-mb-ic-tr' title='The Force is with you'"
                 +  " src='./Assets/Icons/force-token.webp' alt='' />";
        if (truthy(baseObj.EpicActionUsed))
            html += "<img class='swu-mb-cardicon swu-mb-ic-br' title='Epic Action used'"
                 +  " src='./Assets/Icons/action-used.svg' alt='' />";
        return html;
    }

    // The effects column between the base and the Zoom-in button: THREE fixed rows that fill in the
    // order the effects arrived (Fortify or Arrest, whichever came first), leaving a spare row for a
    // third effect later.
    // ⚠ The column is ALWAYS rendered at a fixed size, even when empty. Row 1 of every tile has to stay
    // the same width — the tiles are a comparison view — so this must not grow when a seat gains an
    // effect, exactly like the static RES chip on row 2.
    function swuMbFxColumn(baseObj) {
        var rows = [];
        if (baseObj) {
            var fort   = parseInt(baseObj.UpgradeCount, 10) || 0;
            var arrest = parseInt(baseObj.CaptiveCount, 10)  || 0;
            // Order is arrival order. Nothing records which landed first, so this is the stable
            // fallback: Fortify (a persistent upgrade) above Arrest (which clears every regroup).
            if (fort > 0)   rows.push({cls: 'fort',   n: fort,   t: 'Fortify upgrades on this base',
                                       ids: String(baseObj.UpgradeCardIDs || '')});
            if (arrest > 0) rows.push({cls: 'arrest', n: arrest, t: 'Units arrested and held under this base',
                                       ids: String(baseObj.CaptiveCardIDs || ''), title: 'Captured Units'});
        }
        var html = "<span class='swu-mb-fx'>";
        for (var i = 0; i < 3; i++) {
            if (!rows[i]) { html += "<span class='swu-mb-fxrow'></span>"; continue; }
            var extra = '';
            if (rows[i].ids) {
                // ⚠ Put the UNDERSCORES BACK — swuParseZoneCard rewrites every '_' in the JSON to a
                // space, so "HMW_081" arrives as "HMW 081" and every popup image 404s. Same trap the
                // 2P FORTIFIED tab documents.
                var ids = rows[i].ids.split(',').map(function (t) { return t.trim().replace(/ /g, '_'); })
                                     .filter(function (t) { return t !== ''; });
                if (ids.length) {
                    // Same contract as the full board's tab: a data-lineage-subcards payload driven by
                    // showLineageOverflowPopup, so the tile and the zoomed board show the same panel.
                    var payload = encodeURIComponent(JSON.stringify({
                        subcards: ids, folder: swuBaseArtRoot(), size: 150,
                        title: rows[i].title || 'Attached Upgrades'
                    }));
                    extra = " data-lineage-subcards='" + payload + "' tabindex='0'"
                          + " onclick='event.stopPropagation(); swuClickPanel(this);'"
                          + "";
                }
            }
            html += "<span class='swu-mb-fxchip swu-mb-fxchip-" + rows[i].cls + "'" + extra
                 +  " title='" + rows[i].t + "'>" + rows[i].n + "</span>";
        }
        return html + "</span>";
    }

    function swuRenderMiniBoard(seat) {
        var b = swuReadSeatBlock(seat) || { leaders: [], baseObj: null, groundUnits: [], spaceUnits: [] };
        // Leaders
        var leadHtml = b.leaders.map(function (ld) {
            var o = swuParseZoneCard(ld) || {};
            var cid = String(ld).trim().split(' ')[0];
            // TWI_017 "Flipatine" flips in place — never treat its Deployed face as a unit deploy.
            var isFlipatine = String(ld).indexOf('TWI_017') !== -1;
            var cls = 'swu-mb-card swu-mb-leader';
            if (String(o.Ready) === 'false' || o.Ready === false) cls += ' is-exhausted';
            if (!isFlipatine && (o.Deployed === true || String(o.Deployed) === 'true')) cls += ' is-deployed';
            // WebpImages, not concat: a LEADER is a 628x450 LANDSCAPE card, while concat holds a
            // 450x450 SQUARE crop of it. Rendered through the landscape .swu-mb-leader box the square
            // crop lost ~28% of the card's width and then got cropped again to fit — you saw a vertical
            // slice of the middle. WebpImages is the whole card at the box's own aspect ratio.
            var rid = (typeof resolveCardImageID === 'function') ? resolveCardImageID(cid) : cid;
            // Epic Action used, bottom-right — the same corner the full board's leader card uses.
            // ⚠ These thumbnails are background-image spans, not Card() renders, so the icon Card()
            // would have stacked does not exist here; it has to be emitted explicitly.
            var epic = (o.EpicActionUsed === true || o.EpicActionUsed === 'true'
                        || o.EpicActionUsed === 1 || o.EpicActionUsed === '1')
                ? "<img class='swu-mb-cardicon swu-mb-ic-br' title='Epic Action used'"
                  + " src='./Assets/Icons/action-used.svg' alt='' />" : '';
            return '<span class="' + cls + '" ' + swuMbHoverAttrs(rid) +
                ' style="background-image:url(/TCGEngine/AppCore/SWU/Images/WebpImages/' + rid + '.webp)">'
                + epic + '</span>';
        }).join('');
        // Base (centered damage, tint when hit)
        var dmg = b.baseObj ? (parseInt(b.baseObj.Damage, 10) || 0) : 0;
        var baseCid = b.baseObj ? String(b.baseObj.CardID || '').replace(/ /g, '_') : '';
        var baseRid = baseCid ? ((typeof resolveCardImageID === 'function') ? resolveCardImageID(baseCid) : baseCid) : '';
        var baseHtml = '<span class="swu-mb-card swu-mb-base" data-mz="p' + seat + 'Base-0" ' + swuMbHoverAttrs(baseRid) +
            (baseCid ? ' style="background-image:url(/TCGEngine/AppCore/SWU/Images/concat/' + baseRid + '.webp)"' : '') +
            '>' + (dmg > 0 ? '<span class="swu-mb-dmgcounter">' + dmg + '</span>' : '')
                + swuMbBaseOverlays(b.baseObj) + '</span>' + swuMbFxColumn(b.baseObj);
        // A single unit thumbnail, tagged with its engine mzID (p{seat}{arena}Arena-{idx}) so it can be
        // highlighted + clicked as a cross-view attack/ability target (matches the seat-tagged targets
        // SWUGetAllValidAttackTargets emits for Twin Suns).
        function unitHtml(arena) {
            return function (u, i) {
                var cls = 'swu-mb-card swu-mb-unit';
                if (String(u.Status) === '0') cls += ' is-exhausted';
                var badge = (u.Damage > 0) ? '<span class="swu-mb-dmgcounter">' + u.Damage + '</span>' : '';
                var urid = (typeof resolveCardImageID === 'function') ? resolveCardImageID(u.CardID) : u.CardID;
                return '<span class="' + cls + '" data-mz="p' + seat + arena + 'Arena-' + i + '" ' + swuMbHoverAttrs(urid) + ' ' +
                    'style="background-image:url(/TCGEngine/AppCore/SWU/Images/WebpImages/' + urid + '.webp)">' + badge + '</span>';
            };
        }
        var spaceHtml  = b.spaceUnits.map(unitHtml('Space')).join('');
        var groundHtml = b.groundUnits.map(unitHtml('Ground')).join('');
        // Layout: [Leader1 Leader2 Base … Zoom in] / [Space arena] / [Ground arena]. The Zoom-in button
        // opens the you-vs-P{seat} matchup; the cards are clickable targets during a decision.
        return '' +
            '<div class="swu-mb-r1">' +
                '<span class="swu-mb-seat">P' + seat + '</span>' + leadHtml + baseHtml +
                '<span class="swu-mb-spacer"></span>' +
                '<button type="button" class="swu-mb-zoom" title="Open the you-vs-P' + seat + ' board">🔍 Zoom in</button>' +
            '</div>' +
            // Row 2 — the numbers you would otherwise have to zoom in to read: resources as
            // ready/total (+credits when they hold any), deck size, discard size.
            '<div class="swu-mb-r2">' +
                '<span class="swu-mb-stat swu-mb-stat-res"><span class="swu-mb-statlbl">Res</span>' +
                    // The value sits in a FIXED-WIDTH span so the chip never resizes: it is reserved
                    // for the widest case ("NN/NN +NN") whether or not this seat has credits.
                    '<span class="swu-mb-statval">' + b.res.ready + '/' + b.res.total +
                    (b.res.credits > 0 ? '<span class="swu-mb-statcred">+' + b.res.credits + '</span>' : '') +
                    '</span>' +
                '</span>' +
                '<span class="swu-mb-stat"><span class="swu-mb-statlbl">Deck</span>' + b.deckCount + '</span>' +
                // Clickable: opens that seat's discard pile, the same popup the full board opens from
                // its discard counter. Safe for ANY seat because the Discard is a PUBLIC zone — this
                // deliberately does not open Deck/Hand/Resources, which are not.
                '<span class="swu-mb-stat swu-mb-stat-btn" role="button" tabindex="0"' +
                    ' title="View P' + seat + '\u2019s discard pile" data-zone="p' + seat + 'Discard">' +
                    '<span class="swu-mb-statlbl">Discard</span>' + b.discardCount + '</span>' +
                // Turn / Initiative pills live HERE, not on the seat label. On row 1 they pushed that
                // seat's leaders and base to the right, so a tile with pills no longer lined up with a
                // tile without them — the tiles are a comparison view, and mismatched rows break it.
                '<span class="swu-mb-pills"></span>' +
            '</div>' +
            // --space / --ground carry the arena's IDENTITY, not just its label: the two boxes were
            // previously indistinguishable in CSS (same classes, differing only by their text tag), so
            // nothing could style them apart. The border colours below key off these.
            // No "SPACE"/"GROUND" text tags: at preview size they cost a line of height in each box and
            // repeat what the layout already says. The arenas keep a NON-COLOUR distinction — their
            // fixed vertical ORDER, space above ground, exactly as on the full board — so the silver /
            // sand borders reinforce that reading rather than being the only carrier of it.
            '<div class="swu-mb-arena swu-mb-arena-full swu-mb-arena--space"><div class="swu-mb-row">' + spaceHtml + '</div></div>' +
            '<div class="swu-mb-arena swu-mb-arena-full swu-mb-arena--ground"><div class="swu-mb-row">' + groundHtml + '</div></div>';
    }

    // 3-player home view: one mini board per opponent, each a gateway button into that opponent's
    // matchup view. Shown only on the 'home' view. Tile class stays .swu-home-strip so the existing
    // click delegate (data-view → swuSetView) keeps working.
    // MOBILE seat row: one compact threat-summary line per opponent. Deliberately NO arena
    // thumbnails — at phone width three tiles across ~430px gave ~140px each, overlapping and
    // unreadable, and a card small enough to fit is too small to tap. Targeting drills in instead
    // (spec D1/D2). Reads the SAME seat block the desktop tiles read, so the two views cannot
    // disagree about what a seat holds.
    function swuRenderSeatRow(seat) {
        var b = swuReadSeatBlock(seat) || {leaders:[], baseObj:null, groundCount:0, spaceCount:0,
                                           res:{ready:0,total:0,credits:0}, deckCount:0, discardCount:0};
        var lead = b.leaders.map(function (ld) {
            var cid = String(ld).trim().split(' ')[0];
            var rid = (typeof resolveCardImageID === 'function') ? resolveCardImageID(cid) : cid;
            return "<span class='swu-sr-lead swu-mb-card' " + swuMbHoverAttrs(rid) +
                   " style=\"background-image:url(/TCGEngine/AppCore/SWU/Images/WebpImages/" + rid + ".webp)\"></span>";
        }).join('');
        var baseCid = b.baseObj ? String(b.baseObj.CardID || '').replace(/ /g, '_') : '';
        var brid = baseCid ? ((typeof resolveCardImageID === 'function') ? resolveCardImageID(baseCid) : baseCid) : '';
        var dmg = b.baseObj ? (parseInt(b.baseObj.Damage, 10) || 0) : 0;
        var base = "<span class='swu-sr-base swu-mb-card' " + swuMbHoverAttrs(brid) +
                   (brid ? " style=\"background-image:url(/TCGEngine/AppCore/SWU/Images/concat/" + brid + ".webp)\"" : "") +
                   ">" + (dmg > 0 ? "<span class='swu-mb-dmgcounter'>" + dmg + "</span>" : "") +
                   swuMbBaseOverlays(b.baseObj) + "</span>";
        // ⚠ Fixed-width value box, same reason as desktop: rows are a COMPARISON view, so a chip that
        // grows when a seat gains credits moves everything after it and the same fact sits in a
        // different place on every row.
        var res = "<span class='swu-sr-stat'><span class='swu-sr-lbl'>Res</span>" +
                  "<span class='swu-mb-statval'>" + b.res.ready + "/" + b.res.total +
                  (b.res.credits > 0 ? "<span class='swu-mb-statcred'>+" + b.res.credits + "</span>" : "") +
                  "</span></span>";
        // TWO lines per tile (revised from the spec's single line — there is vertical room on a phone):
        //   A: seat · leaders · base · other info (fortify / arrest) · zoom
        //   B: the counts, as LABELLED chips — res · deck · discard · ground · space
        // Labelled beats the compact glyph form: at this width the row is a scoreboard, and "GROUND 2"
        // cannot be misread the way an unlabelled icon can.
        return "<div class='swu-seat-row' data-seat='" + seat + "'>" +
                 "<div class='swu-sr-a'>" +
                   "<span class='swu-sr-seat'>P" + seat + "</span>" + lead + base +
                   swuMbFxColumn(b.baseObj) +
                   "<span class='swu-sr-pills'></span>" +
                   "<button type='button' class='swu-sr-zoom' title='Open the you-vs-P" + seat + " board'>&#128269;</button>" +
                 "</div>" +
                 "<div class='swu-sr-b'>" +
                   res +
                   "<span class='swu-sr-stat'><span class='swu-sr-lbl'>Deck</span>" + b.deckCount + "</span>" +
                   "<span class='swu-sr-stat swu-mb-stat-btn' role='button' tabindex='0'" +
                     " data-zone='p" + seat + "Discard'><span class='swu-sr-lbl'>Disc</span>" + b.discardCount + "</span>" +
                   "<span class='swu-sr-stat'><span class='swu-sr-lbl'>Ground</span>" + b.groundCount + "</span>" +
                   "<span class='swu-sr-stat'><span class='swu-sr-lbl'>Space</span>" + b.spaceCount + "</span>" +
                 "</div>" +
               "</div>";
    }

    function swuRenderHomeStrips() {
        var box = document.getElementById('swuHomeStrips'); if (!box) return;
        var v = window.swuView;
        if (!v || v.mode !== 'home' || !v.opps) { box.style.display = 'none'; return; }
        var views = window.swuViews || [];
        var html = '';
        v.opps.forEach(function (opp) {
            var mi = 0;
            for (var i = 0; i < views.length; i++) if (views[i].mode === 'matchup' && views[i].oppSeat === opp) { mi = i; break; }
            // ⚠ The tile's PLAYMAT is deliberately NOT written here. It used to be emitted as an
            // inline style at this point, which made this function a SECOND writer of the same visual
            // concept — bypassing ApplyCosmeticPlaymats(), the one place that reads the viewer's
            // "Show playmats" setting. Two consequences, both reported as bugs: the toggle did nothing
            // to these tiles, and because this renderer fires on BOARD data changes while cosmetics
            // fire on the 6s poller / MutationObserver, the two could disagree about which seats had
            // art. swuPaintHomeStripPlaymats() below is now the single painter for both triggers.
            // data-seat is what the turn highlight keys off — swuTwHighlightActiveSeat() toggles the
            // class in place rather than re-rendering, so a turn change never wipes the target chips.
            // Mobile gets stacked summary ROWS; desktop keeps the side-by-side tiles unchanged.
            html += (window.SWU_MOBILE_LAYOUT === true)
                ? swuRenderSeatRow(opp)
                : ('<div class="swu-home-strip" data-seat="' + opp + '" data-view="' + mi + '">' + swuRenderMiniBoard(opp) + '</div>');
        });
        box.innerHTML = html;
        box.style.display = 'flex';
        // MOBILE: publish the rows' real height so the board can sit immediately beneath them. The
        // band is position:fixed, so it contributes nothing to flow — without this the root has to
        // guess a reserve, and any guess is either a gap or an overlap.
        if (window.SWU_MOBILE_LAYOUT === true) {
            var h = Math.ceil(box.getBoundingClientRect().height);
            document.documentElement.style.setProperty('--swu-m-rows-h', (h + 8) + 'px');
        }
        // Rebuilding the tiles wiped any target chips — re-stamp them for an active decision.
        if (typeof swuTwRenderTargetBadges === 'function') swuTwRenderTargetBadges();
        swuTwHighlightActiveSeat();
        // ⚠ AFTER the innerHTML rebuild, not before: this function stamps CLASSES onto the rows, and a
        // rebuild replaces the elements that carry them. Measured — the glow was being applied and then
        // wiped in the same tick, so a manual call lit all three rows while the live view showed none.
        // Same trap the target-chip re-stamp above already guards.
        if (typeof swuTwRenderRowTargets === 'function') swuTwRenderRowTargets();
        // Same reason as the two re-stamps above: the rebuild replaced the elements the playmat was
        // painted onto, so repaint AFTER innerHTML — never before.
        if (typeof window.swuPaintHomeStripPlaymats === 'function') window.swuPaintHomeStripPlaymats();
    }

    // Mark the home strip belonging to the seat whose turn it is. Called on every strip rebuild AND
    // from the TurnPlayerData setter, because the turn can pass without the strips being re-rendered
    // (nothing on an opponent's mini-board has to change for the turn to move on).
    // Only OPPONENT seats have a strip: when the turn is YOURS, nothing highlights here, which is
    // correct — your own board is the whole bottom half of the screen and has its own indicators.
    function swuTwHighlightActiveSeat() {
        var turn = parseInt(String(window.TurnPlayerData || '').trim(), 10);
        // InitiativeCounterData is "P<seat>_CLAIMED" / "P<seat>_UNCLAIMED" — the seat is on the wire,
        // it was just never surfaced per-seat. The bottom-left token only knows mine-vs-theirs, which
        // answers nothing when there are THREE opponents, so name the holder on its own tile.
        var initState = String(window.InitiativeCounterData || '').trim();
        var initSeat  = parseInt(initState.charAt(1), 10);
        var initClaimed = /_CLAIMED$/.test(initState);
        var strips = document.querySelectorAll('#swuHomeStrips .swu-home-strip');
        for (var i = 0; i < strips.length; i++) {
            var strip  = strips[i];
            var seat   = parseInt(strip.getAttribute('data-seat'), 10);
            var active = !!seat && seat === turn;
            strip.classList.toggle('is-active-turn', active);
            var seatEl = strip.querySelector('.swu-mb-pills');
            if (!seatEl) continue;
            swuTwSeatPill(seatEl, 'swu-mb-turnpill', active, 'Turn', '');
            swuTwSeatPill(seatEl, 'swu-mb-initpill', !!seat && seat === initSeat,
                'Initiative',
                initClaimed ? 'Has the initiative (claimed this round)'
                            : 'Has the initiative (not yet claimed)');
            var ip = seatEl.querySelector('.swu-mb-initpill');
            if (ip) ip.classList.toggle('is-claimed', initClaimed);
        }
    }

    // Add/remove/update one pill on a tile's seat label. Kept as a helper so the two pills cannot
    // drift apart, and so a pill is only rebuilt when it has to be (a rebuild every poll would
    // restart its transition and make it flicker).
    function swuTwSeatPill(seatEl, cls, wanted, text, title) {
        var pill = seatEl.querySelector('.' + cls);
        if (wanted && !pill) {
            pill = document.createElement('span');
            pill.className = cls;
            pill.textContent = text;
            seatEl.appendChild(pill);
        } else if (!wanted && pill) {
            pill.remove();
            return;
        }
        if (pill && title && pill.title !== title) pill.title = title;
    }
    window.swuTwHighlightActiveSeat = swuTwHighlightActiveSeat;

    // ── Base status tabs — FORTIFIED (n) / ARRESTED (n) under each base ───────────────────────────
    // Replaces the base's old bottom-left corner badge: same count, same click/hover popup of the
    // attached Fortify upgrades, but labelled, so "this base is fortified" reads without decoding a
    // bare number in a corner.
    // ⚠ The popup is NOT reimplemented — it reuses Core/CounterRendering.js's contract exactly: an
    // element carrying a data-lineage-subcards JSON payload plus showLineageOverflowPopup /
    // hideLineageOverflowPopup. The art folder derivation is the fiddly part of that payload (bug #970
    // — deriving it from the app root 404s every image), so it is copied from there rather than
    // re-derived: window.assetImageFolder trimmed back to the shared SWU corpus root.
    // Click to open, click anywhere to dismiss — the same interaction as the discard pile, so every
    // panel on the board behaves one way. Replaced hover-to-open: a panel that appears while the
    // pointer is merely crossing a chip is easy to trigger by accident and impossible to dismiss
    // deliberately.
    function swuClickPanel(el) {
        if (typeof showLineageOverflowPopup !== 'function') return;
        showLineageOverflowPopup(el);
        // ⚠ Deferred by a tick: the click that OPENED the panel is still propagating, and a listener
        // registered synchronously would catch that very event and close it again immediately.
        setTimeout(function () {
            var off = function () {
                if (typeof hideLineageOverflowPopup === 'function') hideLineageOverflowPopup();
                document.removeEventListener('click', off, true);
            };
            // Capture phase, so a click on ANOTHER chip closes this panel before that chip opens its
            // own — otherwise the two would race and the second could be closed by the first's handler.
            document.addEventListener('click', off, true);
        }, 0);
    }
    window.swuClickPanel = swuClickPanel;

    function swuBaseArtRoot() {
        var f = (typeof window !== 'undefined' && window.assetImageFolder) ? String(window.assetImageFolder) : '';
        if (f.indexOf('AppCore/SWU/Images') !== -1) {
            return f.substring(f.indexOf('AppCore/SWU/Images')).replace(/\/(concat|WebpImages)\/?$/, '');
        }
        return (typeof AssetReflectionPath === 'function' && AssetReflectionPath())
            ? AssetReflectionPath()
            : ((typeof window !== 'undefined' && window.rootPath) ? String(window.rootPath).replace(/^\.\//, '') : '');
    }

    function swuRenderBaseTabs(which) {
        var box = document.getElementById(which + 'BaseTabs'); if (!box) return;
        var o = (typeof swuParseZoneCard === 'function') ? (swuParseZoneCard(window[which + 'BaseData'] || '') || {}) : {};
        var fort   = parseInt(o.UpgradeCount, 10) || 0;
        var arrest = parseInt(o.CaptiveCount, 10)  || 0;
        var html = '';
        if (fort > 0) {
            // ⚠ Put the UNDERSCORES BACK. swuParseZoneCard runs .replace(/_/g,' ') over the whole JSON
            // string — underscores are the transport's stand-in for spaces — so "HMW_081" arrives as
            // "HMW 081" and every popup image 404s (measured: naturalWidth 0 on all three). The main
            // card path (createCardHTML) parses WITHOUT that replace, which is why the old corner
            // badge never hit this. A CardID is SET_NNN, so the space is unambiguously the underscore.
            var ids = String(o.UpgradeCardIDs || '').split(',')
                        .map(function (t) { return t.trim().replace(/ /g, '_'); })
                        .filter(function (t) { return t !== ''; });
            var payload = encodeURIComponent(JSON.stringify({
                subcards: ids, folder: swuBaseArtRoot(), size: 150, title: 'Attached Upgrades'
            }));
            html += "<span class='swu-base-tab swu-base-tab-fort' tabindex='0'"
                 +  " title='Fortify upgrades attached to this base \u2014 click to see them'"
                 +  " data-lineage-subcards='" + payload + "'"
                 +  " onclick='event.stopPropagation(); swuClickPanel(this);'"
                 +  ">"
                 +  "Fortified <span class='swu-base-tab-n'>" + fort + "</span></span>";
        }
        if (arrest > 0) {
            // ⚠ The identities ARE shown: captured cards are OPEN INFORMATION to every player
            // (CR 1077.1 / 207.1). Facedown under the base is a placement, not secrecy.
            var cids = String(o.CaptiveCardIDs || '').split(',')
                        .map(function (t) { return t.trim().replace(/ /g, '_'); })
                        .filter(function (t) { return t !== ''; });
            var cAttrs = '';
            if (cids.length) {
                var cPayload = encodeURIComponent(JSON.stringify({
                    subcards: cids, folder: swuBaseArtRoot(), size: 150, title: 'Captured Units'
                }));
                cAttrs = " tabindex='0' data-lineage-subcards='" + cPayload + "'"
                       + " onclick='event.stopPropagation(); swuClickPanel(this);'"
                       + "";
            }
            html += "<span class='swu-base-tab swu-base-tab-arrest'" + cAttrs
                 +  " title='Units arrested and held under this base until the regroup phase'>"
                 +  "Arrested <span class='swu-base-tab-n'>" + arrest + "</span></span>";
        }
        if (box.innerHTML !== html) box.innerHTML = html;   // avoid restarting hover state every poll
    }

    // The end-game panel's minimise/restore control. Injected rather than shipped in markup because
    // #game-over-overlay is SHARED (Core), and this collapse behaviour is SWUSim's panel-not-takeover
    // treatment. Idempotent: called from the poll, so it survives the overlay being torn down and
    // rebuilt (replay Reset does exactly that — Core/MatchReplayClient.js).
    function swuEnsureEndGameToggle() {
        var ov = document.getElementById('game-over-overlay');
        if (!ov || !ov.classList.contains('active')) return;
        var btn = document.getElementById('swuEndGameToggle');
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'swuEndGameToggle';
            btn.type = 'button';
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var o = document.getElementById('game-over-overlay'); if (!o) return;
                o.classList.toggle('is-minimized');
                swuSyncEndGameToggle();
            });
            ov.appendChild(btn);
        }
        swuSyncEndGameToggle();
    }
    function swuSyncEndGameToggle() {
        var ov = document.getElementById('game-over-overlay');
        var btn = document.getElementById('swuEndGameToggle');
        if (!ov || !btn) return;
        var min = ov.classList.contains('is-minimized');
        btn.textContent = min ? '\u25A1' : '\u2013';                 // □ restore / – minimise
        btn.title       = min ? 'Restore the results panel' : 'Minimise the results panel';
        btn.setAttribute('aria-label', btn.title);
    }
    window.swuEnsureEndGameToggle = swuEnsureEndGameToggle;

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
                // Twin Suns: the entry names its pile's seat, so glow p{n}Discard-N. Without an owner
                // (2-player, and any pre-sweep payload) fall back to theirDiscard-N unchanged.
                var el = (entry.owner ? document.getElementById('p' + entry.owner + 'Discard-' + entry.idx) : null)
                      || document.getElementById('theirDiscard-' + entry.idx);
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

    // ── Your-turn tab indicators: favicon dot + chime ──────────────────────────
    // Ported from SWUOnline, which swaps a whole ready.png/notReady.png favicon and plays
    // Assets/prioritySound.wav. Two deliberate differences:
    //   • NO idle state. SWUOnline shows a grey "not ready" dot; we leave the plain site favicon
    //     alone and only ADD a green dot on your turn, so the tab is quiet by default.
    //   • The dot is COMPOSITED at runtime onto whatever favicon the page already has, rather than
    //     shipping a second pre-dotted PNG. One asset to maintain, and it follows the site favicon
    //     automatically (it picked up the Petranaki icon with no extra work).

    function swuIsMyTurnValue(v) {
        return String(v == null ? '' : v).trim() === String(MY_PLAYER_ID);
    }

    // Effective mute = the per-BROWSER choice when this browser has made one, else the ACCOUNT
    // default, else off. The browser layer is what lets a logged-OUT player mute at all.
    function swuSoundsMuted() {
        try {
            if (window.TCGSettings && typeof window.TCGSettings.get === 'function') {
                var local = window.TCGSettings.get('MuteSounds', { rootName: 'SWUSim', type: 'boolean', defaultValue: null });
                if (local === true || local === false) return local;
            }
        } catch (e) {}
        return window.SWU_ACCOUNT_MUTE === true;
    }
    window.swuSoundsMuted = swuSoundsMuted;

    // Write both layers. The browser layer always wins locally and is written first so the UI is
    // instant; the account write is fire-and-forget (a failed POST must not desync the checkbox).
    function swuSetSoundsMuted(muted) {
        try { if (window.TCGSettings) window.TCGSettings.set('MuteSounds', !!muted, { rootName: 'SWUSim', type: 'boolean' }); } catch (e) {}
        if (window.SWU_LOGGED_IN) {
            window.SWU_ACCOUNT_MUTE = !!muted;
            try {
                var body = 'action=set&mute=' + (muted ? '1' : '0');
                var x = new XMLHttpRequest();
                x.open('POST', swuAppBase() + 'SWUSim/PlayerSettingsApi.php', true);
                x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                x.send(body);
            } catch (e) {}
        }
    }
    window.swuSetSoundsMuted = swuSetSoundsMuted;

    function swuAppBase() {
        var p = location.pathname, i = p.indexOf('/TCGEngine/');
        return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/';
    }

    // "Set it in the browser, then log in, and it carries onto your profile." Only fires when the
    // ACCOUNT has no stored value (SWU_ACCOUNT_MUTE === null): an account that already has an
    // opinion must not be overwritten by whatever a shared or borrowed browser had set. The server
    // re-checks the same condition, so a racing second tab cannot double-promote.
    (function swuPromoteBrowserMuteOnLogin() {
        if (!window.SWU_LOGGED_IN || window.SWU_ACCOUNT_MUTE !== null) return;
        var local = null;
        try {
            if (window.TCGSettings) local = window.TCGSettings.get('MuteSounds', { rootName: 'SWUSim', type: 'boolean', defaultValue: null });
        } catch (e) {}
        if (local !== true && local !== false) return;   // nothing to carry over
        window.SWU_ACCOUNT_MUTE = local;
        try {
            var x = new XMLHttpRequest();
            x.open('POST', swuAppBase() + 'SWUSim/PlayerSettingsApi.php', true);
            x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            x.send('action=promote&mute=' + (local ? '1' : '0'));
        } catch (e) {}
    })();

    // Composite a green dot onto the page's own favicon. Cached after the first successful draw:
    // the source image is same-origin, so the canvas stays untainted and toDataURL works.
    var _swuFavBase = null, _swuFavDotted = null, _swuFavCurrent = null;
    function swuFaviconLink() {
        var l = document.querySelector('link[rel="icon"], link[rel="shortcut icon"]');
        if (!l) { l = document.createElement('link'); l.rel = 'icon'; document.head.appendChild(l); }
        return l;
    }
    function swuBuildDottedFavicon(cb) {
        if (_swuFavDotted) return cb(_swuFavDotted);
        var link = swuFaviconLink();
        var src  = link.getAttribute('href');
        if (!src) return cb(null);
        if (_swuFavBase === null) _swuFavBase = src;
        var img = new Image();
        img.onload = function () {
            try {
                var S = 64, c = document.createElement('canvas');
                c.width = S; c.height = S;
                var g = c.getContext('2d');
                g.drawImage(img, 0, 0, S, S);
                // CENTRED and large: a tab favicon is painted at 16px, where a small corner badge is
                // easy to miss in a row of pinned tabs. Centring it and taking ~2/3 of the width makes
                // "it's your turn" readable at a glance without the player hunting for a corner — the
                // same call SWUOnline makes by swapping the whole icon, but keeping our artwork
                // visible around the ring instead of replacing it.
                // The dark ring is what keeps the green legible over a LIGHT favicon; the Petranaki
                // icon is dark, but this composites onto whatever the site favicon happens to be.
                var r = S * 0.33, cx = S / 2, cy = S / 2;
                g.beginPath(); g.arc(cx, cy, r + S * 0.055, 0, Math.PI * 2);
                g.fillStyle = 'rgba(0,0,0,0.65)'; g.fill();
                g.beginPath(); g.arc(cx, cy, r, 0, Math.PI * 2);
                g.fillStyle = '#3ad12a'; g.fill();
                _swuFavDotted = c.toDataURL('image/png');
                cb(_swuFavDotted);
            } catch (e) { cb(null); }   // tainted canvas / no 2d context: leave the favicon alone
        };
        img.onerror = function () { cb(null); };
        img.src = src;
    }
    function swuSetFaviconTurnDot(on) {
        if (on === _swuFavCurrent) return;                 // idempotent: the poller calls this a lot
        if (on) {
            swuBuildDottedFavicon(function (url) {
                if (!url) return;
                _swuFavCurrent = true;
                swuFaviconLink().setAttribute('href', url);
            });
        } else {
            if (_swuFavBase) swuFaviconLink().setAttribute('href', _swuFavBase);
            _swuFavCurrent = false;
        }
    }

    function swuPlayTurnChime() {
        if (swuSoundsMuted()) return;
        try {
            var a = document.getElementById('yourTurnSound');
            if (!a) return;
            a.currentTime = 0;
            var pr = a.play();
            // Browsers reject autoplay until the page has been interacted with. In a live game the
            // player has clicked long before their turn comes round, but swallow it either way —
            // an unhandled rejection here would surface as a console error every turn.
            if (pr && typeof pr.catch === 'function') pr.catch(function () {});
        } catch (e) {}
    }

    // Edge-triggered by the TurnPlayerData setter. `wasMine`/`isMine` is what keeps the chime to the
    // MOMENT the turn arrives rather than every poll tick while it stays yours.
    function swuOnTurnMaybeChanged(wasMine, isMine) {
        swuSetFaviconTurnDot(isMine);
        if (isMine && !wasMine) swuPlayTurnChime();
    }

    // Initial paint: the setter only fires on CHANGE, so a page that loads already on your turn
    // needs the dot stamped once here. No chime on load — arriving at a page is not the turn
    // arriving, and a sound the player did not cause is startling.
    if (document.readyState !== 'loading') swuSetFaviconTurnDot(swuIsMyTurnValue(window.TurnPlayerData));
    else document.addEventListener('DOMContentLoaded', function () {
        swuSetFaviconTurnDot(swuIsMyTurnValue(window.TurnPlayerData));
    });

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
        // The OPPONENT resource count badge (swuTheirResCount) otherwise refreshes ONLY via a
        // MutationObserver on the hidden theirResourcesSlot. But the opponent's resources are masked
        // (face-down '-') and collapsed by CardID, so their rendered markup is frequently byte-identical
        // across turns — ReplaceRenderedZoneHTML then skips the DOM update, the observer never fires, and
        // the count goes stale ("opponent resources not updating", while their OWN client — which is
        // data-driven — looks fine). Recompute the count straight from the data on every write, the way
        // parseResCountFromData already counts masked entries. (The observer stays as a harmless backup.)
        var _theirResourcesInternal = window.theirResourcesData || '';
        Object.defineProperty(window, 'theirResourcesData', {
            configurable: true,
            get: function () { return _theirResourcesInternal; },
            set: function (v) {
                _theirResourcesInternal = v;
                if (typeof updateResCounterFromData === 'function') {
                    updateResCounterFromData('theirResourcesData', 'swuTheirResCount');
                }
            }
        });
        Object.defineProperty(window, 'TurnPlayerData', {
            configurable: true,
            get: function () { return _turnPlayerInternal; },
            set: function (v) {
                var _wasMine = swuIsMyTurnValue(_turnPlayerInternal);
                _turnPlayerInternal = v;
                // The turn can move without any opponent mini-board changing, so the strips are not
                // necessarily re-rendered — repaint the active-seat marker from here too.
                if (typeof swuTwHighlightActiveSeat === 'function') swuTwHighlightActiveSeat();
                // ...and the tab indicators, for the same reason. swuOnTurnMaybeChanged is edge-
                // triggered on the was/is pair: the board polls continuously and re-assigns this
                // property with an UNCHANGED value on most ticks, so a level check would re-fire the
                // chime every few seconds for as long as it stayed your turn.
                swuOnTurnMaybeChanged(_wasMine, swuIsMyTurnValue(v));
            }
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
    function SWUReportBug() {
      // Close the gear settings overlay first — it sits at a much higher z-index than the shared bug
      // report modal (z-index 3001), so leaving it open renders the modal BEHIND it. Harmless no-op
      // for the end-of-game callers (the gear menu isn't open there).
      if (typeof swuCloseSettings === 'function') swuCloseSettings();
      if (typeof openBugReportModal === 'function') openBugReportModal();
    }
    // These are defined inside this IIFE but are also invoked from a LATER top-level <script> block
    // (SWUGearConcede) and from inline gear-menu onclick handlers — both resolve against global scope.
    // Without these window exports, `onclick="SWUReportBug()"` throws "SWUReportBug is not defined" and
    // SWUGearConcede's `typeof SWUGoMainMenu === 'function'` is false (so Return-to-Main-Menu never navigates).
    window.SWUGoMainMenu = SWUGoMainMenu;
    window.SWUReportBug  = SWUReportBug;
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
        var multiSeat = !!(info && info.seatCount > 2);
        if (spectator || !mid) {
            b.push({label:'Return to Main Menu', onClick: SWUGoMainMenu});
            if (!mid && !spectator && !multiSeat) b.push({label:'Quick Rematch', onClick:function(){ SubmitInput('10013','&inputText=1'); }});
            b.push({label:'Report Bug', onClick: SWUReportBug});
            return b;
        }
        // Twin Suns (>2 seats): every rematch/sideboard/convert flow below is built for a PAIR of
        // seats, so none of them apply. Every seat still gets Main Menu + Report Bug, in the usual
        // place — losing seats need the exit and the bug button just as much as the winner does.
        if (multiSeat) {
            b.push({label:'Return to Main Menu', onClick: SWUGoMainMenu});
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
    // The full winner SET from the gamestate (GAMEOVER_WINNERS, a concat of seats like "24"). Twin
    // Suns can end in a shared victory (CR 12.7.3), so "who won" is a list, not a seat.
    function SWULocalGameWinners() {
        var out = [];
        try {
            var v = JSON.parse(window.DecisionQueueVariablesData || '{}');
            var raw = (v && v.GAMEOVER_WINNERS) ? String(v.GAMEOVER_WINNERS) : '';
            for (var i = 0; i < raw.length; i++) {
                var s = parseInt(raw.charAt(i), 10);
                if (s >= 1 && s <= 4 && out.indexOf(s) === -1) out.push(s);
            }
        } catch (e) { /* fall through to the scalar */ }
        if (!out.length) { var w = SWULocalGameWinner(); if (w > 0) out.push(w); }
        return out.sort(function (a, b) { return a - b; });
    }
    // "Winner(s): Drixx, Player 3" — names the winning seats under the You Won / You Lost title.
    // A logged-in player shows their public username (from EndGameInfo); anyone else is "Player N".
    // Returned as plain text: the caller must NOT inject it as HTML.
    //
    // Only shown when naming the winner adds information: more than two seats (Twin Suns, where
    // "You Lost" leaves three players with no idea who took it) or a shared victory. In a normal
    // 1v1 the title already says everything, so the line would be pure noise.
    function SWUWinnersLine(seats, nameMap, seatCount) {
        if (!seats || !seats.length) return '';
        if (seats.length < 2 && !(seatCount > 2)) return '';
        var names = seats.map(function (s) {
            var n = nameMap && nameMap[String(s)];
            return (typeof n === 'string' && n !== '') ? n : ('Player ' + s);
        });
        return 'Winner(s): ' + names.join(', ');
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
                    var ws = SWULocalGameWinners();
                    // No match layer ⇒ goldfish / harness game, always 2 seats: no winners line.
                    ShowGameOver(ws.indexOf(parseInt(pid, 10)) !== -1, window.SWUMainMenuUrl || null, '',
                                 null, SWUWinnersLine(ws, null, 2));
                    return;
                }
                // Prefer the match record's winner set; fall back to the gamestate's if this record
                // predates it. Naming the winners matters most in Twin Suns, where three of the four
                // seats see "You Lost" and nothing else would say who took it.
                var winners = (info.winners && info.winners.length) ? info.winners : SWULocalGameWinners();
                ShowGameOver(!!info.didWin, window.SWUMainMenuUrl || null, info.statsHtml || '',
                             SWUBuildEndGameButtons(info),
                             SWUWinnersLine(winners, info.winnerNames, info.seatCount));
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
                    // Gamestate bookmarks, below the Block Player widget. Same dedupe discipline as the
                    // widgets around it: this block runs even when ShowGameOver early-returned on an
                    // existing overlay, so two concurrent rebuilds (the convert poll removing the
                    // overlay + NextTurn.php's GAMEOVER_WINNER detector firing in that gap) would
                    // otherwise stack a second panel in the same stats box.
                    var existingBm = goStats.querySelector('#swuEndGameBookmarks');
                    if (existingBm) existingBm.remove();
                    // Heading + capped scroller, mirroring the settings pane. The heading is a SIBLING
                    // of the mount, not a child: the renderer replaces the mount's contents wholesale.
                    var bmBox = document.createElement('div');
                    bmBox.id = 'swuEndGameBookmarks';
                    bmBox.style.marginTop = '10px';
                    var bmHead = document.createElement('div');
                    bmHead.className = 'swu-settings-section-title';
                    bmHead.textContent = 'Gamestate Bookmarks';
                    var bmWrap = document.createElement('div');
                    bmWrap.id = 'swuEndGameBookmarksMount';
                    bmWrap.className = 'swu-bm-scroll';
                    bmBox.appendChild(bmHead); bmBox.appendChild(bmWrap);
                    goStats.appendChild(bmBox);
                    // Async: BookmarksInfo.php hides the panel itself in a public game (isPrivate:false),
                    // and this mount is not #swuBookmarksMount, so it never touches the settings section.
                    swuRenderBookmarksPanel(bmWrap);
                    // SWUStats submission banner — shown once the match completes (SWUSubmitMatchResults ran).
                    var existingSt = goStats.querySelector('.swu-stats-status');
                    if (existingSt) existingSt.remove();
                    var stMap = {
                        success:       ['Game sent to SWUStats successfully!', '#7CFC9E'],
                        skipped_early: ['Game not sent to SWUStats due to ending before Round 2', '#F0B429'],
                        skipped_multiplayer: ['Multiplayer games are not sent to SWUStats', '#F0B429'],
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
                var ws = SWULocalGameWinners();
                ShowGameOver(ws.indexOf(parseInt(pid, 10)) !== -1, window.SWUMainMenuUrl || null, '',
                             null, SWUWinnersLine(ws, null, 2));
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
    // Each thumbnail is wrapped in a hover target wired to the SAME zoomed preview the board
    // uses (ShowCardDetail reads the inner IMG's src) — so a mulligan card can be previewed
    // instead of being stuck as a small thumbnail. This row is shown IN the bright modal panel
    // (desktop + mobile), which also sidesteps the board hand being dimmed/blocked by the modal's
    // full-screen backdrop (the "cards too dark / can't hover during mulligan" report).
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
            var cell = document.createElement('span');   // hover target (ShowCardDetail looks up its inner IMG)
            cell.className = 'swu-mulligan-card';
            var img = document.createElement('img');
            img.loading = 'lazy';
            img.alt = cardID;
            // Mock (preview) cards store their art as concat/mock_<CardID>.webp — resolve the image id the
            // same way the board does (jsInclude.js), else unreleased-set mock cards 404 and don't render.
            img.src = '/TCGEngine/AppCore/SWU/Images/concat/' + (typeof resolveCardImageID === 'function' ? resolveCardImageID(cardID) : cardID) + '.webp';
            cell.appendChild(img);
            cell.onmouseover = function (e) { if (typeof window.ShowCardDetail === 'function') ShowCardDetail(e, this); };
            cell.onmouseout  = function ()  { if (typeof window.HideCardDetail === 'function') HideCardDetail(); };
            row.appendChild(cell);
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
        // Mark the overlay so the CSS centers the panel on the game area (excluding the chat sidebar).
        var mulOverlay = document.getElementById('yesno-decision-modal');
        if (mulOverlay) mulOverlay.setAttribute('data-swu-mulligan', '1');
        // Show the hoverable hand row IN the modal panel on BOTH desktop and mobile. On desktop the board
        // hand is technically present behind the modal, but the modal's full-screen rgba(0,0,0,0.5) backdrop
        // dims it AND intercepts its hover events — so the in-panel row is what makes the hand bright and
        // previewable during mulligan (the reported "cards too dark / can't hover" fix).
        var modal = document.querySelector('#yesno-decision-modal > div');
        if (!modal) return;
        var row = buildHandRow();
        if (row) modal.insertBefore(row, modal.firstChild);
    };
})();
</script>

<script>
// ── SWU Undo UI helpers ───────────────────────────────────────────────────────
// Mirrors the SERVER decoder _parseSWUVars() (GameLogic.php) — the DQ-variable map has TWO encodings
// and a reader that understands only one silently returns nothing.
//
// SetSWUVar json_encode()s the whole map, and GetNextTurn echoes $gDecisionQueueVariables verbatim, so
// in any live game this payload is JSON. This function previously parsed ONLY the legacy pipe form
// ("k=v|k=v"), so every lookup returned '' — which silently disabled the entire undo consent UI: the
// "Request Undo" label, the opponent's undo-request popup, and the block prompt were all unreachable.
// Try JSON first, then fall back to the legacy pipe form for old gamestates.
window.GetSWUDQVar = window.GetSWUDQVar || function(key, def) {
    var fallback = (def !== undefined ? def : '');
    var d = typeof window.DecisionQueueVariablesData === 'string'
        ? window.DecisionQueueVariablesData.trim() : '';
    if (d === '') return fallback;
    if (d.charAt(0) === '{') {
        try {
            var o = JSON.parse(d);
            if (o && Object.prototype.hasOwnProperty.call(o, key)) {
                var v = o[key];
                // StoreVariable() shares this map and can park arrays/objects in it — never hand one back.
                return (v === null || typeof v === 'object') ? fallback : String(v);
            }
            return fallback;
        } catch (e) { /* malformed JSON — fall through to the legacy parse */ }
    }
    var pairs = d.split('|');
    for (var i = 0; i < pairs.length; i++) {
        var eq = pairs[i].indexOf('=');
        if (eq !== -1 && pairs[i].slice(0, eq) === key) return pairs[i].slice(eq + 1); // first occurrence wins
    }
    return fallback;
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

// ── Undo split-button menu ────────────────────────────────────────────────────
// Menu contents by lobby type:
//   private (any seat count) : Undo Phase + Bookmark Gamestate
//   public, 2 seats          : Undo Phase only (fires the existing request/approve flow)
//   public, >2 seats         : no caret at all
// That last row is deliberate. The undo CONSENT layer is hardcoded to 2 seats in three remaining
// places (swuUpdateUndoUI's otherPlayer, SWUApproveUndo, and EngineActionRunner case 10010), so a
// seat-3 request in a public Twin Suns game can never be approved by anyone. Undo Phase ALWAYS
// requests in public, so offering it there would turn that latent gap into a visible dead end.
// Gating the caret keeps it unreachable.
function swuToggleUndoMenu(ev) {
    if (ev) ev.stopPropagation();
    var menu = document.getElementById('swuUndoMenu');
    var btn = document.getElementById('swuUndoMenuBtn');
    if (!menu) return;
    // Mount to <body> before showing. #swuSidebar sets overflow:hidden, which CLIPS an
    // absolutely-positioned descendant — the menu rendered with its left edge cut off. Same escape
    // hatch swuOpenSettings uses for the settings overlay. Idempotent across re-opens.
    if (menu.parentNode !== document.body) document.body.appendChild(menu);
    var open = menu.classList.toggle('is-open');
    if (open && btn) {
        // Right-align to the caret, clamped into the viewport so it can never render off-screen.
        var r = btn.getBoundingClientRect();
        menu.style.top = Math.round(r.bottom + 4) + 'px';
        var w = menu.offsetWidth || 200;
        menu.style.left = Math.round(Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8))) + 'px';
    }
    if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}

function swuCloseUndoMenu() {
    var menu = document.getElementById('swuUndoMenu');
    var btn = document.getElementById('swuUndoMenuBtn');
    if (menu) menu.classList.remove('is-open');
    if (btn) btn.setAttribute('aria-expanded', 'false');
}
document.addEventListener('click', function (e) {
    var split = document.getElementById('swuUndoSplit');
    var menu = document.getElementById('swuUndoMenu');
    // The menu lives on <body> once opened, so it is NOT inside #swuUndoSplit any more — it needs its
    // own containment test or a click on the menu's own padding would dismiss it.
    if (split && !split.contains(e.target) && !(menu && menu.contains(e.target))) swuCloseUndoMenu();
});
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') swuCloseUndoMenu(); });

function swuUndoPhase() {
    swuCloseUndoMenu();
    // undoKind rides the QUERY STRING (SubmitEngineInput appends params to the URL) and ProcessInput
    // reads $_GET — an earlier $_POST read here is why Undo Phase was unreachable.
    SubmitInput(10004, 'undoKind=phase');
}

function swuPromptBookmark() {
    swuCloseUndoMenu();
    // StyledPrompt is PROMISE-based (see Core/UILibraries*.js and SharedUI/Render/DeckLibrary.php):
    // StyledPrompt(message, {title, initial, confirmLabel}).then(value). It resolves to null on cancel.
    // Native window.prompt is forbidden by this repo's native-dialog lint.
    StyledPrompt('Name this bookmark (optional):',
                 { title: 'Bookmark Gamestate', initial: '', confirmLabel: 'Save' })
        .then(function (label) {
            if (label === null || label === undefined) return;   // cancelled
            SubmitInput(10018, 'inputText=' + encodeURIComponent(label));
        });
}

// ── Gamestate bookmarks panel ─────────────────────────────────────────────────
// ONE renderer, two mounts: the gear settings overlay and the end-game menu. Bookmarks must stay
// reachable after the game ends — that is when players most want to try a different line.
function swuRenderBookmarksPanel(mount) {
    if (!mount) return;
    var gn = FormInputValue('gameName');
    var pid = FormInputValue('playerID');
    var ak = FormInputValue('authKey');
    fetch('./SWUSim/BookmarksInfo.php?gameName=' + encodeURIComponent(gn)
        + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(ak))
        .then(function (r) { return r.json(); })
        .then(function (info) {
            // The heading-bearing box for whichever mount we were handed. Both must be hidden wholesale
            // in a public game — otherwise a "Gamestate Bookmarks" heading sits there with nothing
            // under it.
            var hostBox = (mount.id === 'swuBookmarksMount')
                ? document.getElementById('swuBookmarksSection')
                : document.getElementById('swuEndGameBookmarks');
            // Show by CLEARING the inline display, not by setting 'block': the settings pane is
            // `display:flex` in the stylesheet (fixed title, scrolling body), and an inline
            // display:block outranks that — which un-flexed the pane and let the list grow past the
            // column instead of scrolling inside it.
            if (!info || !info.isPrivate) { if (hostBox) hostBox.style.display = 'none'; return; }
            if (hostBox) hostBox.style.display = '';

            var list = Array.isArray(info.bookmarks) ? info.bookmarks : [];
            // No collapsible: each mount already carries its own "Gamestate Bookmarks" heading, and a
            // nested summary repeating it read as duplication. The list is rendered flat and its
            // container scrolls.
            var d = document.createDocumentFragment();

            // The count lives on the heading now that the summary is gone. Absent on the end-game
            // mount, which builds its own heading — guarded rather than assumed.
            var countEl = document.getElementById('swuBookmarksCount');
            if (countEl) countEl.textContent = list.length ? '(' + list.length + ')' : '';

            if (list.length === 0) {
                var e = document.createElement('div');
                e.className = 'swu-bm-empty';
                e.textContent = 'No bookmarks yet. Use Undo ▾ → Bookmark Gamestate.';
                d.appendChild(e);
            }
            list.forEach(function (bm) {
                var row = document.createElement('div'); row.className = 'swu-bm-row';
                var meta = document.createElement('div'); meta.className = 'swu-bm-meta';
                // textContent, never innerHTML — the label is player-supplied free text.
                meta.textContent = 'Round ' + bm.round + ' · ' + bm.phase + ' · Player ' + bm.seat;
                if (bm.label) {
                    var lab = document.createElement('span'); lab.className = 'swu-bm-label';
                    lab.textContent = bm.label; lab.title = bm.label;
                    meta.appendChild(lab);
                }
                var btn = document.createElement('button');
                btn.className = 'swu-bm-load'; btn.type = 'button'; btn.textContent = 'Load Gamestate';
                btn.onclick = function () { SubmitInput(10019, 'buttonInput=' + encodeURIComponent(bm.id)); };
                row.appendChild(meta); row.appendChild(btn);
                d.appendChild(row);
            });

            mount.innerHTML = '';
            mount.appendChild(d);
        })
        .catch(function (err) { if (window.console && console.error) console.error(err); });
}

// Called from swuUpdateUndoUI on every render, so the menu tracks the live lobby facts.
function swuUpdateUndoMenuVisibility() {
    var split = document.getElementById('swuUndoSplit');
    if (!split) return;
    // Stamped from SWUGameIsPrivate, which counts one-player modes (goldfish/hotseat) as private —
    // they are private lobbies by construction and have no opponent to coordinate with.
    var isPrivate = GetSWUDQVar('GAME_IS_PRIVATE') === 'true';
    var seats = parseInt(GetSWUDQVar('GAME_SEAT_COUNT') || '2', 10);
    if (isNaN(seats)) seats = 2;

    var showPhase = isPrivate || seats <= 2;
    var showBookmark = isPrivate;
    var showCaret = showPhase || showBookmark;

    // Toggle a CLASS, not an inline style. The menu-item rule carries `display: block !important` (to
    // beat components.css's `button:not(.btn):not(.switch)`), and !important also beats a
    // non-important inline style — so `el.style.display='none'` here silently did nothing and the
    // Bookmark item stayed visible in public lobbies.
    var pi = document.getElementById('swuUndoPhaseItem');
    var bi = document.getElementById('swuBookmarkItem');
    if (pi) pi.classList.toggle('is-hidden', !showPhase);
    if (bi) bi.classList.toggle('is-hidden', !showBookmark);

    split.classList.toggle('is-split', showCaret);
    if (!showCaret) swuCloseUndoMenu();
}

function swuUpdateUndoUI(myPlayerID) {
    var btn = document.getElementById('swuUndoBtn');
    if (!btn) return;

    var hasVersion = typeof window.myVersionsData === 'string' && window.myVersionsData.trim() !== '';
    // Mirror the SERVER rule: the reveal flag alone does not mean consent is needed. In a private game
    // the server grants every undo outright, so labelling the button "Request Undo" there told players
    // to expect an approval step that never happens. GAME_IS_PRIVATE is stamped from SWUGameIsPrivate,
    // so it already counts one-player modes as private.
    var undoIsFree = GetSWUDQVar('GAME_IS_PRIVATE') === 'true';
    var requiresConsent = !undoIsFree && GetSWUDQVar('UNDO_REQUIRES_CONSENT') === 'true';
    var isBlocked = GetSWUDQVar('UNDO_BLOCKED_' + myPlayerID) === 'true';

    btn.style.display = hasVersion ? 'inline-block' : 'none';
    // The split wrapper follows the Undo button's own gate, or an empty control floats in the header.
    // '' (not 'inline-flex') so the .is-split rule stays in charge of whether the caret shows at all.
    var undoSplit = document.getElementById('swuUndoSplit');
    if (undoSplit) undoSplit.style.display = hasVersion ? '' : 'none';
    swuUpdateUndoMenuVisibility();
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
  // Twin Suns: card backs are per-seat and viewable by all — resolve each side to the CURRENT view's
  // seat. 2-player (no swuView) falls back to the legacy my/their fields → byte-identical.
  var myBack = c.myCardBack, theirBack = c.theirCardBack;
  if (c.seats && window.swuView) {
    myBack    = (c.seats[window.swuView.viewSeat] || {}).cardback || myBack;
    theirBack = (c.seats[window.swuView.oppSeat]  || {}).cardback || theirBack;
  }
  var imgs = document.querySelectorAll("img[src*='/concat/CardBack.webp'], img[src$='CardBack.webp']");
  for (var i = 0; i < imgs.length; i++) {
    var img = imgs[i];
    var owner = img.closest("[id^='my']") ? 'my' : (img.closest("[id^='their']") ? 'their' : null);
    if (!owner) continue;
    var back = owner === 'my' ? myBack : theirBack;
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

// THE one reader of the viewer's "Show playmats" setting. Every surface that paints a mat must go
// through this — a second inline read is how the Twin Suns preview tiles ended up ignoring the toggle
// entirely (they were painted by swuRenderHomeStrips, which never consulted it).
function swuShowPlaymats() {
  if (!window.TCGSettings || typeof window.TCGSettings.get !== 'function') return true;
  return window.TCGSettings.get('ShowPlaymats', { rootName: 'SWUSim', type: 'boolean', defaultValue: true }) !== false;
}
window.swuShowPlaymats = swuShowPlaymats;

// Twin Suns home preview TILES: paint each opponent tile's per-seat mat IN PLACE (no innerHTML), so
// this can be driven from both triggers without wiping the target chips / active-turn classes the
// tiles carry. Seats are read off data-seat, so it works for any live seat set.
// Clearing falls back to the tile's own CSS panel background (--swu-surface), which is the correct
// "playmats off" look — the tile is still a readable panel, just without keyart.
function swuPaintHomeStripPlaymats() {
  try {
    var c = window.SWU_COSMETICS || {};
    var show = swuShowPlaymats();
    var TINT = 'rgba(10,10,10,0.72)';
    var strips = document.querySelectorAll('#swuHomeStrips .swu-home-strip[data-seat]');
    for (var i = 0; i < strips.length; i++) {
      var el   = strips[i];
      var seat = el.getAttribute('data-seat');
      var pm   = (c.seats && (c.seats[seat] || {}).playmat) || '';
      if (show && pm) {
        el.style.backgroundImage    = 'linear-gradient(' + TINT + ',' + TINT + "), url('" + pm + "')";
        el.style.backgroundSize     = 'cover';
        el.style.backgroundPosition = 'center';
        el.classList.add('has-playmat');
      } else {
        // Clear the INLINE declaration so the .swu-home-strip:not(.has-playmat) stylesheet rule takes
        // over and washes the whole tile — an inline 'none' would beat it.
        el.style.backgroundImage    = '';
        el.style.backgroundSize     = '';
        el.style.backgroundPosition = '';
        el.classList.remove('has-playmat');
      }
    }
  } catch (e) {}
}
window.swuPaintHomeStripPlaymats = swuPaintHomeStripPlaymats;

// Per-side playmats (desktop): paint each side's mat, honoring the viewer's Show-playmats toggle.
function ApplyCosmeticPlaymats() {
  try {
    var c = window.SWU_COSMETICS; if (!c) return;
    var show = swuShowPlaymats();
    // Transparent-black tint layered OVER the mat art (and under the arena HUD wash),
    // so the mat reads darker/uniform behind the cards. Layered into the same
    // background as the mat image → it only shows where a mat is set. Tune the alpha.
    var TINT = 'rgba(10,10,10,0.67)';
    var matBg = function (asset) { return "linear-gradient(" + TINT + "," + TINT + "), url('" + asset + "')"; };
    // Twin Suns: playmats are per-seat and viewable by all — the two board sides reflect the CURRENT
    // view's seats (bottom = viewSeat, top = oppSeat), so switching views shows the right seats' mats.
    // 2-player (no swuView) falls back to the legacy my/their fields → byte-identical.
    var myPlaymat = c.myPlaymat, theirPlaymat = c.theirPlaymat;
    if (c.seats && window.swuView) {
      myPlaymat    = (c.seats[window.swuView.viewSeat] || {}).playmat || myPlaymat;
      theirPlaymat = (c.seats[window.swuView.oppSeat]  || {}).playmat || theirPlaymat;
    }
    var top = document.querySelector('.swu-playmat-top');   // opponent side (desktop)
    var bot = document.querySelector('.swu-playmat-bot');   // my side (desktop)
    function paint(el, asset) {
      if (!el) return;
      if (show && asset) { el.style.backgroundImage = matBg(asset); el.style.display = 'block'; }
      else { el.style.display = 'none'; }
    }
    paint(bot, myPlaymat);
    paint(top, theirPlaymat);

    // Mobile: no dedicated playmat divs — the per-side mat backs each player's arena
    // row directly (cover/center = inner slice). Toggle .has-playmat for the overlay.
    var mMine   = document.querySelector('.swu-m-arena-row.is-mine');
    var mTheirs = document.querySelector('.swu-m-arena-row.is-theirs');
    function paintRow(el, asset) {
      if (!el) return;
      if (show && asset) { el.style.backgroundImage = matBg(asset); el.classList.add('has-playmat'); }
      else { el.style.backgroundImage = ''; el.classList.remove('has-playmat'); }
    }
    paintRow(mMine, myPlaymat);
    paintRow(mTheirs, theirPlaymat);

    // Twin Suns preview tiles ride the SAME apply path as the board halves. Without this a cosmetics
    // change (the 6s poller) or a toggle flip repainted the board and left the tiles stale until the
    // next unrelated board mutation happened to re-render them.
    swuPaintHomeStripPlaymats();
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
  /* The shared StyledConfirm/StyledAlert overlay (.sd-overlay, z-index 10000 in Core/StyledDialog.js) must
     sit ABOVE the gear settings overlay (10001) so a Concede / Return-to-Main-Menu confirmation opened FROM
     the settings menu appears on top of it, not behind. Both mount at <body>, so a plain z-index bump orders
     them (same stacking context — consistent across Chromium/Firefox/WebKit). */
  .sd-overlay { z-index: 10010 !important; }
  /* The shared bug-report modal (#bugReportOverlay, z-index 3001 in UILibraries) sits far below SWUSim's
     HUD overlays — the "Waiting for the other player" turn-miasma pill (4999), the mulligan modal (5000),
     the settings overlay (10001) — so it opened BEHIND them (e.g. the waiting pill covered it during a
     mulligan). Lift it above everything so Report Bug is always usable. */
  #bugReportOverlay { z-index: 10011 !important; }
  .swu-settings-panel { width: min(94vw, 640px); background: var(--surface-raised);
    border: 1px solid var(--border); border-radius: 12px;
    box-shadow: 0 18px 50px rgba(0,0,0,0.6); color: var(--text); overflow: hidden;
    display: flex; flex-direction: column; max-height: 88vh; }
  /* Two columns — settings left, hotkeys right — so the hotkey list can grow downward without
     pushing Cosmetics/Match/Report off the bottom. Single column by default and widened by the
     media query below: that way the narrow case needs no override, so a phone (where this same
     overlay is the mobile layout's menu too) gets the stacked panel by default rather than by
     undoing a desktop rule. Column order follows DOM order when stacked: settings, then hotkeys. */
  .swu-settings-body { display: grid; grid-template-columns: minmax(0, 1fr); overflow-y: auto; }
  .swu-settings-col { min-width: 0; }
  .swu-settings-col--keys { border-top: 1px solid var(--border); }
  @media (min-width: 620px) {
    /* Slightly wider left column: it holds the cosmetic <select>s, whose option text is far longer
       than a hotkey label. */
    .swu-settings-body { grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); }
    /* Side by side, the divider between them is vertical, not horizontal. */
    .swu-settings-col--keys { border-top: 0; border-left: 1px solid var(--border); }
    /* Right column splits into two independently-scrolling halves: Hotkeys on top, Gamestate
       Bookmarks below, so a long bookmark list never pushes the hotkey reference off the panel.
       max-height is an EXPLICIT vh, not a percentage: a % height does not resolve against a
       flex/grid-STRETCHED parent in Firefox and WebKit, so a 50%/50% split silently collapses
       there. 88vh is the panel cap; the head is ~46px, hence ~76vh of body to divide.
       min-height:0 on the panes is what actually lets a flex child scroll instead of growing. */
    .swu-settings-col--keys { display: flex; flex-direction: column; max-height: 76vh; }
    /* The pane is the flex track; its BODY is the scroller, so the title stays put. min-height:0 on
       both is what lets a flex child shrink and scroll rather than growing to fit its content. */
    .swu-settings-pane { flex: 1 1 0; min-height: 0; display: flex; flex-direction: column; }
    .swu-settings-pane > .swu-settings-pane-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
  }
  /* Stacked (narrow / mobile) keeps both panes in normal flow — the whole body already scrolls, and
     nesting scrollers inside it on a phone makes the list hard to reach. */
  .swu-settings-pane + .swu-settings-pane { border-top: 1px solid var(--border); }
  /* ── Fonts: ONE family for the whole menu ────────────────────────────────────────────────
     Nothing on the board sets a font-family on <body> — every element opts in explicitly — and this
     panel never did. So its rows, section titles and head fell through to the UA default, which is a
     SERIF, while the <select>s took the UA widget font and the .btn/<kbd> took --font-display /
     --swu-font-label. Four faces in one 640px panel.
     The panel now declares the board's UI face and everything inside inherits it; hierarchy is carried
     by size, weight, tracking and case, which the titles and key chips already have. Every rule below
     that used to name its own family now says `inherit` so there is exactly one place to change it.
     ⚠ Form controls do NOT inherit font in ANY engine — Chromium, Firefox and WebKit all reset
     <select>/<button>/<input> to a UA font — so the explicit opt-in is required, not belt-and-braces.
     ⚠ The button selector is deliberately (0,3,1): components.css's `button:not(.btn):not(.switch)`
     is (0,2,1) and button.css's `.btn { font-family: var(--font-display) }` is (0,1,0); a plain
     `.swu-settings-panel button` would only TIE the first and win on source order. */
  .swu-settings-panel { font-family: var(--swu-font-ui, "Aptos","Segoe UI Variable","Trebuchet MS",sans-serif); }
  .swu-settings-panel select, .swu-settings-panel input { font: inherit; }
  .swu-settings-panel .btn,
  .swu-settings-panel button:not(.btn):not(.switch) { font-family: inherit; }
  .swu-settings-head { display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; border-bottom: 1px solid var(--border);
    /* Longhands, not the `font` shorthand: `inherit` is not a legal shorthand COMPONENT, so
       `font: 700 16px/1 inherit` is dropped whole — taking the size and weight with it. */
    font-weight: 700; font-size: 16px; line-height: 1;
    color: var(--accent-strong); letter-spacing: 0.02em; }
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
  /* Hotkeys list — a reference row, not a control, so no pointer affordance. The key chip keeps the
     same look the on-board <kbd> hints had, just relocated into the menu. */
  .swu-hotkey-row { cursor: default; }
  .swu-hotkey-key { flex: 0 0 auto; display: inline-block; min-width: 22px; text-align: center;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); border-radius: 4px;
    padding: 2px 7px; color: var(--text, #e8d5a8);
    /* font-family is NOT optional here: <kbd> carries a UA default of `monospace` in Chromium,
       Firefox AND WebKit, so simply omitting the family leaves the key caps in a third face. */
    font-family: inherit; font-weight: 600; font-size: 12px; line-height: 1.35; letter-spacing: 0.06em; }
  /* Gamestate bookmarks. No collapsible — every mount carries its own heading, so a nested summary
     just repeated it. Every <button> here carries !important: components.css's
     `button:not(.btn):not(.switch)` is (0,2,1) and outranks a plain class. */
  /* End-game mount only. The settings pane gets its height from the split column instead.
     Carries the family for that mount: inside the panel these rows inherit it, but the game-over
     overlay sets no font-family either, so without this they render in the UA serif there. */
  .swu-bm-scroll { max-height: 220px; overflow-y: auto;
    font-family: var(--swu-font-ui, "Aptos","Segoe UI Variable","Trebuchet MS",sans-serif); }
  .swu-bm-row { display: flex; align-items: center; justify-content: space-between; gap: 8px;
    padding: 6px 0; border-top: 1px solid var(--border); }
  .swu-bm-meta { min-width: 0; font-size: 12px; color: rgba(255,255,255,0.82); }
  .swu-bm-label { display: block; font-size: 11px; color: var(--text-muted);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .swu-bm-load { flex: 0 0 auto; font-family: inherit !important; background: rgba(200,151,30,0.15) !important;
    color: rgba(200,151,30,0.95) !important; border: 1px solid rgba(200,151,30,0.35) !important;
    border-radius: 4px !important; font-size: 11px !important; padding: 5px 9px !important;
    cursor: pointer !important; }
  .swu-bm-load:hover { background: rgba(200,151,30,0.28) !important; }
  .swu-bm-empty { font-size: 12px; color: var(--text-muted); padding: 6px 0; }
  .swu-settings-link { display: inline-block; margin-top: 8px; color: var(--accent); font-size: 13px; text-decoration: none; }
  .swu-settings-link:hover { text-decoration: underline; }
  /* font-size is explicit because .btn sets none, leaving the UA <button> default — which is
     13.33px in Chromium/Firefox but 11px in WebKit, so Concede / Return / Report Bug rendered two
     sizes across engines inside the same menu. */
  .swu-settings-action { display: block; width: 100%; margin: 6px 0 0; font-size: 13px; }
  /* Collapsible Block Player widget (shared by the gear menu + game-over overlay) */
  .swu-blockplayer { margin: 8px auto 0; max-width: 360px; text-align: left;
    font-family: var(--swu-font-ui, "Aptos","Segoe UI Variable","Trebuchet MS",sans-serif); }
  .swu-blockplayer-head { display: block; width: 100%; padding: 6px 0; background: transparent; border: 0;
    color: rgba(140,210,255,0.7); font-family: inherit; font-weight: 700; font-size: 12px; line-height: 1;
    text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; text-align: left; }
  .swu-blockplayer-head:hover { color: #cfe6fb; }
  .swu-blockplayer-body { display: flex; align-items: center; justify-content: space-between; gap: 12px;
    margin-top: 6px; padding: 8px 12px; background: rgba(8,15,25,0.5);
    border: 1px solid rgba(255,255,255,0.12); border-radius: 7px; }
  .swu-blockplayer-name { color: #f0e6c8; font-size: 14px; font-weight: 600; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap; }
  .swu-blockplayer-btn { flex: 0 0 auto; padding: 7px 16px; background: rgba(180,40,55,0.22);
    border: 1px solid rgba(220,80,95,0.6); border-radius: 6px; color: #ffd7dc;
    font-family: inherit; font-weight: 700; font-size: 13px; line-height: 1; cursor: pointer; }
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
  // Mute: the ACCOUNT-level answer, or null when there is none (guest, or a logged-in account that
  // has never set it). null is load-bearing — it is what tells the client a browser-side choice is
  // eligible for promotion onto the account. See PlayerSettings.php.
  require_once __DIR__ . '/../PlayerSettings.php';
  $swuGearMute   = SWUSimAccountMuted($swuGearUid);
  $swuGearLogged = ($swuGearUid !== '' && $swuGearUid !== null && intval($swuGearUid) > 0);

  // Seat -> public username, for every seat whose player is LOGGED IN.
  // ⚠ THIS IS A PRODUCER FOR TWO EXISTING CONSUMERS THAT NEVER HAD ONE: chat labels
  // (Core/jsInclude.js _ChatPlayerLabel) and SWUBuildBlockPlayerWidget above. Until now nothing in
  // the repo assigned window.SWU_SEAT_USERNAMES, so chat always fell back to "P1"/"P2" and the
  // Block Player widget returned null for everybody (it bails when the viewer has no name).
  // ⚠ ONLY REAL USERNAMES GO IN. MatchSeatDisplayNames substitutes "Player N" for an anonymous seat,
  // and both consumers read a MISSING entry as "this seat is not logged in" — publishing the
  // fallback would label a guest as an account and offer a Block button that cannot work. So the
  // seat list is gated on userId > 0 rather than on the display string.
  $swuSeatNames = [];
  require_once __DIR__ . '/../MatchFlow.php';
  if (isset($gameName) && function_exists('SWUReadMatchRef') && function_exists('MatchSeatDisplayNames')) {
      $swuRef = SWUReadMatchRef($gameName);
      $swuMatch = ($swuRef !== null && !empty($swuRef['matchId'])) ? SWUReadMatch($swuRef['matchId']) : null;
      if (is_array($swuMatch) && !empty($swuMatch['players'])) {
          $swuNames = MatchSeatDisplayNames($swuMatch);
          foreach ($swuMatch['players'] as $swuSeatKey => $swuPlayer) {
              $swuSeat = intval($swuSeatKey);
              if ($swuSeat < 1 || intval($swuPlayer['userId'] ?? 0) <= 0) continue;   // guest seat
              $swuName = strval($swuNames[$swuSeat] ?? '');
              if ($swuName !== '') $swuSeatNames[strval($swuSeat)] = $swuName;
          }
      }
  }
  // MATCHLESS games (goldfish / hotseat) never create a match record — SWUReadMatchRef returns null
  // above, so the loop cannot name anyone and chat would read "P1" for a logged-in player asking why
  // their own name is missing. Same fallback shape SWUBuildCosmeticsPayload already uses for these
  // modes: prefer the match snapshot, else resolve the LOGGED-IN VIEWER from their own session.
  // ⚠ Only the viewer's OWN seat can be filled this way. In goldfish seat 2 is a dummy and in hotseat
  //   it is the same physical person on the same browser — neither is a known account, so they stay
  //   anonymous and keep reading "P2". That also keeps the Block Player widget correctly hidden in
  //   these modes: it needs BOTH names, and there is nobody to block.
  if (empty($swuSeatNames) && $swuGearLogged && intval($playerID) >= 1) {
      require_once __DIR__ . '/../../Core/MatchHistory.php';
      $swuSelfConn = function_exists('GetLocalMySQLConnection') ? GetLocalMySQLConnection() : null;
      if ($swuSelfConn) {
          $swuSelfName = MatchHistoryUsername($swuSelfConn, $swuGearUid);
          $swuSelfConn->close();
          if ($swuSelfName !== null && $swuSelfName !== '') $swuSeatNames[strval(intval($playerID))] = $swuSelfName;
      }
  }
?>
<script>
  window.SWU_LOGGED_IN   = <?= $swuGearLogged ? 'true' : 'false' ?>;
  window.SWU_ACCOUNT_MUTE = <?= $swuGearMute === null ? 'null' : ($swuGearMute ? 'true' : 'false') ?>;
  // Always an object (never undefined) so a consumer can index it without a guard. Empty = a game
  // in which nobody is logged in, or one played outside the match system.
  window.SWU_SEAT_USERNAMES = <?= json_encode((object)$swuSeatNames, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.SWU_VIEWER_SEAT = <?= intval($playerID) ?>;   // 0 for a spectator ('S')
</script>
<div id="swuSettingsOverlay" class="swu-settings-overlay" style="display:none;" onclick="if(event.target===this)swuCloseSettings()">
  <div class="swu-settings-panel" role="dialog" aria-modal="true">
    <div class="swu-settings-head"><span>Settings</span>
      <button class="swu-settings-close" onclick="swuCloseSettings()" aria-label="Close">&#10005;</button></div>
    <?php
      // Hotkeys — the board used to carry a <kbd> chip beside Pass / Take Initiative and a hint strip
      // at the midline. Those were permanent furniture for something you learn once, so they were
      // removed and the shortcuts live here instead.
      //
      // Keep this list in step with the handlers: Space / I / W are the keydown listeners further up
      // this file, U is Core/UILibraries' shared Hotkeys(), Esc is the overlay's own listener.
      // Deliberately NOT listed: S (SubmitInput 10005) is a legacy dev save-snapshot that answers
      // "Versions are created automatically when a game result is recorded" once the asset-versioning
      // adapter is on — a live key, but not one to advertise to players.
      $swuHotkeys = [
        // Keep labels short: the column is ~half the panel, so a long one wraps to two lines.
        ['Space', 'Pass / decline a prompt'],
        ['I',     'Take / keep the initiative'],
        ['U',     'Undo'],
      ];
      if (function_exists('SWUGameMode') && SWUGameMode() === 'hotseat') {
        $swuHotkeys[] = ['W', 'Switch player'];
      }
      $swuHotkeys[] = ['Esc', 'Close this menu'];
    ?>
    <!-- Two columns: settings on the left, the (growing) hotkey reference on the right. Collapses to
         a single column on a narrow viewport — see .swu-settings-body. -->
    <div class="swu-settings-body">
    <div class="swu-settings-col swu-settings-col--main">
    <div class="swu-settings-section">
      <div class="swu-settings-section-title">Cosmetics</div>
      <label class="swu-settings-row"><span>Show playmats</span>
        <input type="checkbox" id="swuSetShowPlaymats"></label>
      <label class="swu-settings-row"><span>Mute sounds</span>
        <input type="checkbox" id="swuSetMuteSounds"></label>
      <label class="swu-settings-row"><span>Card motion</span>
        <input type="checkbox" id="swuSetCardMotion"></label>
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
    <div class="swu-settings-section" style="border-top:1px solid var(--border);">
      <div class="swu-settings-section-title">Report</div>
      <button class="btn swu-settings-action" onclick="SWUReportBug()">Report Bug</button>
    </div>
    </div><!-- /col--main -->
    <div class="swu-settings-col swu-settings-col--keys">
    <!-- Each pane keeps its title FIXED and scrolls only its body: the panes are short (they split the
         column), so a title inside the scroller would slide out of view and leave an unlabelled list. -->
    <div class="swu-settings-section swu-settings-pane">
      <div class="swu-settings-section-title">Hotkeys</div>
      <div class="swu-settings-pane-body">
      <?php foreach ($swuHotkeys as [$swuKey, $swuWhat]): ?>
        <div class="swu-settings-row swu-hotkey-row"><span><?= htmlspecialchars($swuWhat, ENT_QUOTES) ?></span>
          <kbd class="swu-hotkey-key"><?= htmlspecialchars($swuKey, ENT_QUOTES) ?></kbd></div>
      <?php endforeach; ?>
      </div>
    </div>
    <!-- Bottom half of the right column. Hidden entirely in a public lobby, in which case Hotkeys
         takes the whole column back (a display:none flex item claims no space). -->
    <div class="swu-settings-section swu-settings-pane" id="swuBookmarksSection" style="display:none;">
      <div class="swu-settings-section-title">Gamestate Bookmarks <span id="swuBookmarksCount"></span></div>
      <div class="swu-settings-pane-body" id="swuBookmarksMount"></div>
    </div>
    </div><!-- /col--keys -->
    </div><!-- /swu-settings-body -->
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
    if (t && typeof window.swuShowPlaymats === 'function') t.checked = window.swuShowPlaymats();
    // The one checkbox shows the EFFECTIVE answer (browser choice, else account default) — there is
    // deliberately no "following your profile" hint: the box itself is the mute status.
    var m = document.getElementById('swuSetMuteSounds');
    if (m && typeof window.swuSoundsMuted === 'function') m.checked = window.swuSoundsMuted();
    // Card motion (zone slides + attack lunge). Read through TCGCardMotion.isEnabled rather than
    // TCGSettings directly: its default honours prefers-reduced-motion, so a player who has asked the
    // OS for reduced motion sees this unchecked without ever having touched it.
    var cm = document.getElementById('swuSetCardMotion');
    if (cm && window.TCGCardMotion) cm.checked = window.TCGCardMotion.isEnabled('SWUSim');
    // Match actions are player-only (hidden for spectators / non-players).
    var ms = document.getElementById('swuSettingsMatchSection');
    if (ms) {
      var pf = document.getElementById('playerID');
      var pid = pf ? parseInt(pf.value || '', 10) : NaN;
      var isPlayer = swuViewerIsSeatedPlayer(pid);
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
    // Gamestate bookmarks. Rendered on every open so the list is current; the renderer hides its own
    // section in a public game (BookmarksInfo.php answers isPrivate:false there).
    swuRenderBookmarksPanel(document.getElementById('swuBookmarksMount'));
    ov.style.display = 'flex';
  }
  function swuCloseSettings() { var ov = document.getElementById('swuSettingsOverlay'); if (ov) ov.style.display = 'none'; }
  // SWUConfirm is now a thin shim over the shared StyledDialog primitive — the bespoke modal
  // and its CSS were removed. Callback-style signature preserved so existing call sites are unchanged.
  function SWUConfirm(message, onConfirm, opts) {
    StyledConfirm(message, opts || {}).then(function(ok) { if (ok && typeof onConfirm === 'function') onConfirm(); });
  }
  window.SWUConfirm = SWUConfirm;
  // Does the viewer hold a SEAT in this game (as opposed to spectating)?
  //
  // ⚠ This deliberately does NOT ask "is it seat 1 or 2". A SPECTATOR is seat 0 — window.SWU_VIEWER_SEAT
  // is 0 for 'S' — so the seat-1-or-2 test never distinguished spectators from players in the first
  // place; it encoded "this game has two seats", which stopped being true with Twin Suns. It hid the
  // whole gear-menu Match section from seats 3 and 4, so they had no Concede and no Return to Main Menu.
  //
  // SeatOrderData/LiveSeatsData are digit strings ("1234"). A game that ships neither predates Twin Suns
  // and is two-seat, hence the fallback.
  function swuViewerIsSeatedPlayer(pid) {
    if (!(pid >= 1)) return false;                       // 0 / NaN = spectator
    var order = String(window.SeatOrderData || window.LiveSeatsData || '').trim();
    if (!order.length) return pid <= 2;
    return order.indexOf(String(pid)) !== -1;
  }
  window.swuViewerIsSeatedPlayer = swuViewerIsSeatedPlayer;

  // Concede from the gear menu. Live Bo3 forfeits the whole match (10007); otherwise the game (10006).
  function SWUGearConcede(goHome) {
    var pf = document.getElementById('playerID');
    var pid = pf ? parseInt(pf.value || '', 10) : NaN;
    if (!swuViewerIsSeatedPlayer(pid)) return; // spectators can't concede
    // 1P practice (goldfish): there is no opponent to concede to, so "Return to Main Menu" (goHome)
    // just closes the solo game and leaves — no concede confirm prompt. "Concede" (goHome=false) still
    // prompts. The server re-checks the mode, so this is inert outside goldfish.
    if (goHome && window.SWUIsGoldfish) {
      SubmitInput('10006', '');       // end the solo game
      swuCloseSettings();
      if (typeof SWUGoMainMenu === 'function') SWUGoMainMenu();
      return;
    }
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
    if (e.target && e.target.id === 'swuSetMuteSounds') {
      // Writes BOTH layers: this browser always, and the account too when signed in, so the gear
      // menu and the Profile toggle can never disagree after a change.
      if (typeof window.swuSetSoundsMuted === 'function') window.swuSetSoundsMuted(e.target.checked);
      return;
    }
    if (e.target && e.target.id === 'swuSetShowPlaymats') {
      if (window.TCGSettings) window.TCGSettings.set('ShowPlaymats', e.target.checked, { rootName:'SWUSim', type:'boolean' });
      if (typeof window.ApplyCosmeticPlaymats === 'function') window.ApplyCosmeticPlaymats();
      return;
    }
    if (e.target && e.target.id === 'swuSetCardMotion') {
      if (window.TCGSettings) window.TCGSettings.set('EnableCardMotion', e.target.checked, { rootName:'SWUSim', type:'boolean' });
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

