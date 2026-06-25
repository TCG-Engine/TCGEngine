<?php
// GameLayout.php — SWUSim board layout v3: horizontal arena columns (desktop/tablet).
// LEFT = Space Arena, CENTER = Leader/Base/Piles, RIGHT = Ground Arena.
// Zone slot IDs must match BindTo values in GameSchema.txt.
//
// Phones are routed to the vertical-stack layout in GameLayoutMobile.php. Shared
// behaviour lives in GameLayoutShared.php and targets slot IDs, so both reuse it.
require_once __DIR__ . '/GameLayoutDevice.php';
if (SWUSimIsMobileRequest()) { include __DIR__ . '/GameLayoutMobile.php'; return; }
?>
<style>
    :root {
        /* ── Palette ── */
        --swu-bg:           #0b0f14;
        --swu-surface:      rgba(255,255,255,0.04);
        --swu-border:       rgba(255,255,255,0.10);
        --swu-border-hi:    rgba(255,255,255,0.22);
        --swu-gold:         #c8971e;
        --swu-gold-soft:    rgba(200,151,30,0.22);
        --swu-font-ui:      "Aptos","Segoe UI Variable","Trebuchet MS",sans-serif;
        --swu-font-label:   "Bahnschrift","Aptos Display","Franklin Gothic Medium",sans-serif;

        /* ── Layout ── */
        --swu-sidebar-w:    clamp(160px, 14vw, 200px);
        --swu-board-w:      calc(100vw - var(--swu-sidebar-w));
        /* Leader + base column width. Leader/base art FILLS this column
           (width:100% of the center col, via the object-fit rework), so this var
           IS their on-screen card size. The engine renders a LANDSCAPE card at
           width = cardSize * 1.35 (see UILibraries Card(): rotate/landscape branch),
           which is exactly a unit card's height — i.e. naturally proportional. So we
           match that constant: card width = cardSize * 1.35 (+18px for the col's
           padding/border). --swu-cardsize is window.cardSize, set by GameLayoutShared
           JS; the 80px fallback only applies for the first paint before the JS runs.
           The <=1100px / <=800px media queries below can still override. */
        --swu-center-w:     calc(var(--swu-cardsize, 80px) * 1.35 + 18px);
        --swu-hand-h:       118px;
        --swu-pile-w:       88px;
        /* Width the deck+discard pile rows occupy on the right (2 piles + gap +
           breathing room). Hand panels stop before this so they never bleed
           under the piles. */
        --swu-pile-zone-w:  calc(var(--swu-pile-w) * 2 + 20px);

        /* ── Game-log palette — ONE var per log type (single source of truth). ──
           Tune a type's color here; every .swu-log-<TYPE> rule below reads its var.
           Types pointing at --swu-log-default are currently un-tinted (no visual
           change) but are wired so they can be given a color later in one place. */
        --swu-log-default:    rgba(255,255,255,0.78);
        --swu-log-phase:      #9b59b6;
        --swu-log-overwhelm:  #e05050;
        --swu-log-reveal:     #f0c040;
        --swu-log-disclose:   var(--swu-log-reveal); /* disclose IS a reveal — share its color */
        --swu-log-ability:    var(--swu-log-default);
        --swu-log-play:       var(--swu-log-default);
        --swu-log-draw:       var(--swu-log-default);
        --swu-log-discard:    var(--swu-log-default);
        --swu-log-defeat:     var(--swu-log-default);
        --swu-log-attack:     var(--swu-log-default);
        --swu-log-resource:   var(--swu-log-default);
        --swu-log-namecard:   var(--swu-log-default);
        --swu-log-pass:       var(--swu-log-default);
        --swu-log-initiative: var(--swu-log-default);

        /* Left bar eliminated — initiative + resources now live in the hand band
           (bottom-left / top-left), so the arenas reclaim the full left edge.
           --swu-res-badge-w kept at 0 so the arena/center offset calcs still work. */
        --swu-res-badge-w:  0px;
        /* Hand-band control slots, left of the hand: initiative hex, then resources.
           --swu-hud-pad pushes the whole cluster in from the left edge; --swu-res-w
           is a nominal reserve for the hand-width calc (the badge itself is now
           content-sized, not fixed). */
        --swu-hud-pad:      40px;
        --swu-init-w:       80px;
        --swu-res-w:        80px;
        /* Hand panel width (right-anchored). Factored out so the control band can
           span exactly up to the hand's left edge and balance its outer gaps. */
        --swu-hand-w: calc((100vw - var(--swu-sidebar-w) - var(--swu-init-w) - var(--swu-res-w) - var(--swu-pile-zone-w)) * 0.9);

        /* Arena column width: (board - res-badge - center - 2×gap) / 2 */
        --swu-col-w: calc((var(--swu-board-w) - var(--swu-res-badge-w) - var(--swu-center-w) - 20px) / 2);

        /* Column left edges (all offset by res-badge-w) */
        --swu-space-left:   var(--swu-res-badge-w);
        --swu-center-left:  calc(var(--swu-res-badge-w) + var(--swu-col-w) + 8px);
        --swu-ground-left:  calc(var(--swu-res-badge-w) + var(--swu-col-w) + var(--swu-center-w) + 16px);

        /* Midline vertical offset */
        --swu-midline: 50%;
    }

    /* ── Global ─────────────────────────────────────────────────────────────── */
    body, #gameContainer { background: var(--swu-bg) !important; }
    #myStuff { border: 0 !important; }

    /* ── Board background ────────────────────────────────────────────────────────
       The framework's opaque .myStuff/.theirStuff/.stuff containers paint over
       <body>, so a body background can't show. Render the board art as its own
       fixed layer that sits ABOVE those containers (like .swu-starfield does) but
       below the starfield, arenas, and all game content. */
    .swu-board-bg {
        position: fixed; inset: 0; z-index: 9; pointer-events: none;
        background:
            linear-gradient(to right,
                rgba(0,0,0,0.05) var(--swu-center-left),
                rgba(0,0,0,0.35) calc(var(--swu-center-left) + var(--swu-center-w))),
            var(--swu-bg) url('<?= SWUBoardBackground(false) ?>') center center / cover no-repeat;
    }

    /* ── Starfield ───────────────────────────────────────────────────────────── */
    .swu-starfield {
        position: fixed; inset: 0; pointer-events: none; z-index: 10;
        background:
            radial-gradient(ellipse at 20% 30%, rgba(30,60,120,0.10) 0%, transparent 50%),
            radial-gradient(ellipse at 80% 70%, rgba(200,151,30,0.06) 0%, transparent 50%);
    }

    /* ── Column separators ───────────────────────────────────────────────────── */
    .swu-col-sep {
        position: fixed; top: 0; bottom: 0; width: 1px; z-index: 14; pointer-events: none;
        background: linear-gradient(180deg,
            transparent 0%,
            var(--swu-border) 15%,
            rgba(255,255,255,0.14) 50%,
            var(--swu-border) 85%,
            transparent 100%);
    }

    /* ── Midline ─────────────────────────────────────────────────────────────── */
    .swu-midline-bar {
        position: fixed; left: 0; right: var(--swu-sidebar-w);
        top: calc(var(--swu-midline) - 1px); height: 2px;
        pointer-events: none; z-index: 15;
        background: linear-gradient(90deg,
            transparent,
            rgba(255,255,255,0.10) 10%,
            rgba(255,255,255,0.22) 50%,
            rgba(255,255,255,0.10) 90%,
            transparent);
    }

    /* ── Arena labels ────────────────────────────────────────────────────────── */
    .swu-arena-label {
        position: fixed; z-index: 16; pointer-events: none;
        font: 700 9px/1 var(--swu-font-label);
        letter-spacing: 0.30em; text-transform: uppercase; white-space: nowrap;
    }

    /* ── Phase track / midbar ────────────────────────────────────────────────── */
    .swu-midbar {
        position: fixed; z-index: 20; pointer-events: none;
        left: 50%; transform: translateX(calc(-50% - var(--swu-sidebar-w)/2));
        display: flex; align-items: center; gap: 18px;
        color: rgba(255,255,255,0.50);
        font: 700 10px/1 var(--swu-font-label);
        letter-spacing: 0.18em; text-transform: uppercase; white-space: nowrap;
    }
    .swu-phase-step { position: relative; padding: 0 2px; opacity: 0.7;
        transition: color 140ms, opacity 140ms, text-shadow 140ms; }
    .swu-phase-step::before {
        content: ""; position: absolute; left: -10px; top: 50%;
        transform: translateY(-50%); width: 3px; height: 3px;
        border-radius: 50%; background: rgba(255,255,255,0.28); }
    .swu-phase-step:first-child::before { display: none; }
    .swu-phase-step.is-active {
        color: rgba(255,220,120,0.98); opacity: 1;
        text-shadow: 0 0 14px rgba(200,151,30,0.7); }

    /* ── Initiative hex button (hand band, far-left slot) ─────────────────────────
       Leftmost slot of the hand band, left of the resources. Sits on whichever side
       currently controls the initiative — my band (bottom) or their band (top) —
       and moves to the claimer's side once taken (CR 2.2.2 / 4.7).
       updateInitiative() drives the side class, label, and state classes. */
    /* ── Hand-band control cluster — narrow per-side strip pinned to the left edge
       of each hand band. Holds the initiative hex (left) and the resource badge
       (right), spread apart with space-between; the hand itself is unaffected. The
       single init hex is reparented into the controlling band by updateInitiative(). */
    .swu-control-band {
        position: fixed; z-index: 38;
        /* Span the whole free zone — left edge to the hand's left edge — with equal
           --swu-hud-pad on both sides, so the gap (edge → init) matches (res → hand). */
        left: 0; right: calc(var(--swu-sidebar-w) + var(--swu-pile-zone-w) + var(--swu-hand-w));
        height: var(--swu-hand-h);
        padding: 0 var(--swu-hud-pad);
        display: flex; align-items: center; justify-content: space-between;
        pointer-events: none;                 /* children opt back in */
    }
    .swu-control-band.is-bottom { bottom: 0; top: auto; }
    .swu-control-band.is-top    { top: 0; bottom: auto; }

    .swu-init-control {
        position: relative; z-index: 38;
        width: var(--swu-init-w); height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
        pointer-events: none;                 /* the hex itself opts back in */
    }

    /* Octagonal HUD initiative token. A cyan rim (.swu-init-hex bg) wraps the inner
       fill (#swuInitHexText): UNCLAIMED = faded blue horizontal lines; CLAIMED = solid
       cyan; TAKEABLE (your turn) = brighter rim + cyan glow, clickable. The single
       word "Initiative" is the only label; the fill conveys the state. */
    .swu-init-hex {
        position: relative;
        width: 72px; height: 62px;
        box-sizing: border-box; padding: 2px;          /* rim thickness */
        clip-path: polygon(15px 0, calc(100% - 15px) 0, 100% 15px, 100% calc(100% - 15px), calc(100% - 15px) 100%, 15px 100%, 0 calc(100% - 15px), 0 15px);
        background: rgba(120,200,255,0.50);            /* cyan rim */
        filter: drop-shadow(0 1px 4px rgba(0,0,0,0.55));
        pointer-events: auto; cursor: help;            /* status badge: hover shows the tooltip, no click action */
        transition: background 180ms, filter 180ms, transform 120ms;
    }
    #swuInitHexText {
        position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        width: 100%; height: 100%;
        box-sizing: border-box; padding: 0 2px; text-align: center;
        clip-path: polygon(15px 0, calc(100% - 15px) 0, 100% 15px, 100% calc(100% - 15px), calc(100% - 15px) 100%, 15px 100%, 0 calc(100% - 15px), 0 15px);
        /* Unclaimed look: faded blue glowing horizontal rays over a dark base */
        background:
            repeating-linear-gradient(0deg, transparent 0 0.3px, rgba(150,215,255,0.50) 1px, transparent 1.7px 3.5px),
            rgba(14,27,42,0.92);
    }
    /* Cyan claimed fill — rises bottom→top whenever .is-claimed toggles on (keep), and
       on the new side after a take. clip-path on #swuInitHexText keeps it octagonal. */
    .swu-init-fill {
        position: absolute; inset: 0; z-index: 0; pointer-events: none;
        transform: scaleY(0); transform-origin: bottom;
        background: linear-gradient(160deg, rgba(120,215,255,0.96), rgba(70,180,240,0.96));
        transition: transform 430ms cubic-bezier(0.32,0,0.2,1);
    }
    .swu-init-control.is-claimed .swu-init-fill { transform: scaleY(1); }
    .swu-init-word {
        position: relative; z-index: 1;
        color: rgba(185,222,255,0.85);
        font: 700 10px/1 var(--swu-font-label);
        letter-spacing: 0; text-transform: uppercase; white-space: nowrap;
        text-shadow: 0 1px 2px rgba(0,0,0,0.45);
        transition: color 360ms, text-shadow 360ms;
    }
    .swu-init-control.is-claimed .swu-init-word { color: rgba(8,22,38,0.95); text-shadow: none; }
    /* Takeable (your turn, MAIN, still unclaimed) — brighter cyan rim + glow as a STATUS
       hint that the Take/Keep button is live; the token itself is no longer the action. */
    .swu-init-control.is-takeable .swu-init-hex {
        background: rgba(150,215,255,0.92);
        filter: drop-shadow(0 0 9px rgba(120,200,255,0.70));
    }
    .swu-init-control.is-takeable .swu-init-word { color: rgba(225,242,255,0.95); }
    /* Claimed — brighter cyan rim (the fill above carries the body) */
    .swu-init-control.is-claimed .swu-init-hex {
        background: rgba(150,215,255,0.95);
        filter: drop-shadow(0 0 8px rgba(120,200,255,0.55));
    }
    /* Take = initiative switched sides: fade out (old side) → fade in (new side) */
    .swu-init-control.is-leaving  { animation: swuInitLeave 200ms ease forwards; }
    .swu-init-control.is-entering { animation: swuInitEnter 340ms ease; }
    @keyframes swuInitLeave  { to   { opacity: 0; } }
    @keyframes swuInitEnter  { from { opacity: 0; } }

    /* ── Pass button — my side only, at the bottom of the center control cluster,
          just above my hand. Carries the "Space" helper. ───────────────────────── */
    .swu-init-pass {
        position: fixed; z-index: 38;
        left: var(--swu-center-left); width: var(--swu-center-w);
        /* Sit just above the hand. The Take/Keep button stacked on Pass made the cluster
           taller, so anchored higher (was +48px) its top butted into the leader/base column
           — which reaches down near the hand on wide/4K screens. Drop it toward the hand so
           the top clears the leader; --swu-pass-gap is the clearance above the hand. */
        bottom: calc(var(--swu-hand-h) + var(--swu-pass-gap, 10px));
        display: flex; flex-direction: column; align-items: center; gap: 4px;
        pointer-events: none;                  /* the button opts back in */
        transition: opacity 150ms;
    }
    /* Flat HUD panel — chamfered corners, thin cyan edge, flat fill (sci-fi interface).
       The cyan body shows as a thin rim; ::before is the flat fill inset inside it;
       the label sits in a <span> above the fill. drop-shadow gives the crisp edge glow. */
    .swu-init-pass-btn {
        position: relative; pointer-events: auto; cursor: pointer;
        /* Both buttons (Take/Keep + Pass) share this class, so one width makes them equal.
           Width + font scale with the center column (--swu-center-w grows with the board),
           so they stay proportional to the cards at any resolution — a fixed px looked tiny
           on wide/4K screens. --swu-init-btn-scale is the 80%-of-column knob. */
        box-sizing: border-box; width: calc(var(--swu-center-w) * var(--swu-init-btn-scale, 0.8));
        padding: 0.62em 0.9em; border: 0; background: rgba(130,205,255,0.80);
        color: rgba(198,233,255,0.95);
        font: 700 10px/1 var(--swu-font-label); font-size: clamp(9px, 0.5vw, 15px);
        letter-spacing: 0.16em; text-transform: uppercase;
        text-shadow: 0 0 5px rgba(120,200,255,0.45);
        clip-path: polygon(9px 0, 100% 0, 100% calc(100% - 9px), calc(100% - 9px) 100%, 0 100%, 0 9px);
        filter: drop-shadow(0 0 4px rgba(110,190,255,0.35));
        transition: filter 150ms, color 150ms, transform 110ms;
    }
    .swu-init-pass-btn::before {
        content: ''; position: absolute; inset: 1.5px; z-index: 0;
        background: rgba(16,34,58,0.92);
        clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
        transition: background 150ms;
    }
    .swu-init-pass-btn > span { position: relative; z-index: 1; }
    .swu-init-pass-btn:hover { color: #fff; filter: drop-shadow(0 0 9px rgba(125,205,255,0.6)); }
    .swu-init-pass-btn:hover::before  { background: rgba(28,56,90,0.92); }
    .swu-init-pass-btn:active { transform: translateY(0.5px); }
    .swu-init-pass-btn:active::before { background: rgba(42,78,118,0.95); }
    /* Dimmed + inert when it isn't your turn to act */
    .swu-init-pass.is-idle .swu-init-pass-btn { opacity: 0.35; pointer-events: none; cursor: default; }
    .swu-init-pass.is-idle .swu-init-pass-hint { visibility: hidden; }

    /* Take/Keep Initiative — same chamfered cyan HUD as Pass (inherits .swu-init-pass-btn);
       only difference is it's hidden until updateInitiative() shows it (canTake). */
    .swu-take-init[hidden] { display: none; }
    /* Initiative already claimed this round — greyed & inert ("Initiative Claimed") instead of hidden. */
    .swu-init-pass-btn.swu-take-init.is-taken {
        background: rgba(255,255,255,0.14); color: rgba(255,255,255,0.42);
        text-shadow: none; filter: none; cursor: default; pointer-events: none;
    }
    .swu-init-pass-btn.swu-take-init.is-taken::before { background: rgba(20,24,30,0.92); }

    .swu-init-pass-hint {
        font: 700 7px/1 var(--swu-font-label); letter-spacing: 0.10em;
        text-transform: uppercase; color: rgba(255,255,255,0.30);
        pointer-events: none;
    }
    .swu-init-pass-hint kbd {
        display: inline-block; background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.18); border-radius: 3px;
        padding: 1px 5px; color: rgba(255,255,255,0.55); font: inherit; }

    /* ── Keyboard hints ──────────────────────────────────────────────────────── */
    .swu-kb-hints {
        position: fixed; z-index: 16; pointer-events: none;
        left: 50%; transform: translateX(calc(-50% - var(--swu-sidebar-w)/2));
        display: flex; gap: 14px;
        color: rgba(255,255,255,0.20);
        font: 600 8px/1 var(--swu-font-label); letter-spacing: 0.07em;
        text-transform: uppercase; white-space: nowrap;
    }
    .swu-kb-hints kbd {
        display: inline-block; background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14); border-radius: 3px;
        padding: 1px 3px; font: inherit; margin-right: 2px; }

    /* ── Base zone slot (generic) ────────────────────────────────────────────── */
    .swu-zone {
        position: fixed; z-index: 30; pointer-events: auto;
    }

    /* ── Arena shared frames ─────────────────────────────────────────────────── */
    /* One frame per arena, spanning both halves (their + mine). The arena-col
       children are position:fixed so they ignore this wrapper for layout; it only
       draws the HUD frame around both halves, letting the page background show through. */
    .swu-arena-bg {
        position: fixed; z-index: 29; pointer-events: none;
        width: var(--swu-col-w);
        top: var(--swu-hand-h); bottom: var(--swu-hand-h);
        background: transparent;
        /* Faint light-blue full frame + soft glow — the sci-fi targeting-HUD look. */
        border: 1px solid rgba(120,200,255,0.22);
        border-radius: 4px;
        box-shadow: 0 0 6px rgba(80,170,255,0.10),
                    inset 0 0 14px rgba(80,170,255,0.08);
        animation: swuArenaPulse 3.2s ease-in-out infinite;
    }
    @keyframes swuArenaPulse {
        0%, 100% { border-color: rgba(120,200,255,0.18);
                   box-shadow: 0 0 5px rgba(80,170,255,0.08), inset 0 0 12px rgba(80,170,255,0.05); }
        50%      { border-color: rgba(150,215,255,0.42);
                   box-shadow: 0 0 13px rgba(95,185,255,0.28), inset 0 0 18px rgba(95,185,255,0.13); }
    }
    /* Bright cyan L-brackets at all four corners. Eight gradient slices (one
       horizontal + one vertical arm per corner) painted on a single pseudo. */
    .swu-arena-bg::before {
        content: ''; position: absolute; inset: -1px; pointer-events: none;
        --c:   rgba(150,215,255,0.92);   /* bracket color */
        --len: 26px;                     /* arm length    */
        --th:  3px;                      /* arm thickness */
        filter: drop-shadow(0 0 4px rgba(150,215,255,0.75));   /* light glow on the brackets */
        animation: swuArenaBracketPulse 3.2s ease-in-out infinite;
        background:
            linear-gradient(var(--c),var(--c)) left  top    / var(--len) var(--th) no-repeat,
            linear-gradient(var(--c),var(--c)) left  top    / var(--th)  var(--len) no-repeat,
            linear-gradient(var(--c),var(--c)) right top    / var(--len) var(--th) no-repeat,
            linear-gradient(var(--c),var(--c)) right top    / var(--th)  var(--len) no-repeat,
            linear-gradient(var(--c),var(--c)) left  bottom / var(--len) var(--th) no-repeat,
            linear-gradient(var(--c),var(--c)) left  bottom / var(--th)  var(--len) no-repeat,
            linear-gradient(var(--c),var(--c)) right bottom / var(--len) var(--th) no-repeat,
            linear-gradient(var(--c),var(--c)) right bottom / var(--th)  var(--len) no-repeat;
    }
    .swu-arena-bg-space  { left: var(--swu-space-left);  }
    .swu-arena-bg-ground { left: var(--swu-ground-left); }
    @keyframes swuArenaBracketPulse {
        0%, 100% { filter: drop-shadow(0 0 3px rgba(150,215,255,0.45)); }
        50%      { filter: drop-shadow(0 0 8px rgba(150,215,255,0.95)); }
    }

    /* ── Arena columns ───────────────────────────────────────────────────────── */
    .swu-arena-col {
        position: fixed; z-index: 30; pointer-events: auto;
        width: var(--swu-col-w);
        overflow: hidden; border-radius: 0;
    }
    .swu-arena-col-space  { background: transparent; left: var(--swu-space-left);  }
    .swu-arena-col-ground { background: transparent; left: var(--swu-ground-left); }
    .swu-arena-col-top    { top: var(--swu-hand-h); bottom: calc(var(--swu-midline) + 4px); }
    .swu-arena-col-bot    { top: calc(var(--swu-midline) + 4px); bottom: var(--swu-hand-h); }

    /* Card flow inside arena cols — wrap, fill from edge nearest midline */
    #theirSpaceArena, #theirGroundArena { flex-wrap: wrap !important; align-content: flex-end !important; }
    #mySpaceArena,    #myGroundArena    { flex-wrap: wrap !important; align-content: flex-start !important; }
    /* Horizontal: space builds from right (center-facing); ground builds from left (center-facing) */
    #mySpaceArena,    #theirSpaceArena  { justify-content: flex-end !important; }
    /* Hide the generic "GroundArena"/"SpaceArena" label that UILibraries injects when a zone is empty */
    #myGroundArena > span:only-child:not([id]), #theirGroundArena > span:only-child:not([id]),
    #mySpaceArena  > span:only-child:not([id]), #theirSpaceArena  > span:only-child:not([id]) { display: none; }
    /* Same for the deck/discard/hand zones — the grey .swu-pile-label is the real label,
       so hide the engine's black placeholder text. :not(:has(img)) spares real card piles. */
    #myDeck    > span:only-child:not([id]):not(:has(img)), #theirDeck    > span:only-child:not([id]):not(:has(img)),
    #myDiscard > span:only-child:not([id]):not(:has(img)), #theirDiscard > span:only-child:not([id]):not(:has(img)),
    #myHand    > span:only-child:not([id]):not(:has(img)), #theirHand    > span:only-child:not([id]):not(:has(img)) { display: none; }
    #myGroundArena,   #theirGroundArena { justify-content: flex-start !important; }
    #theirSpaceArena > span, #theirGroundArena > span,
    #mySpaceArena    > span, #myGroundArena    > span { flex: 0 0 auto; }
    /* Spacing between units in an arena */
    #theirSpaceArena, #theirGroundArena,
    #mySpaceArena,    #myGroundArena    { gap: 10px; }

    /* ── Center column ───────────────────────────────────────────────────────── */
    .swu-center-col {
        position: fixed; z-index: 31; pointer-events: auto;
        left: var(--swu-center-left); width: var(--swu-center-w);
    }
    .swu-center-col-top {
        top: var(--swu-hand-h); bottom: calc(var(--swu-midline) + 4px);
        display: flex; flex-direction: column; align-items: center;
        justify-content: flex-end;   /* P2: piles → Base → Leader (closest to mid) */
        gap: 6px; padding: 8px 6px;
    }
    .swu-center-col-bot {
        top: calc(var(--swu-midline) + 4px); bottom: var(--swu-hand-h);
        display: flex; flex-direction: column; align-items: center;
        justify-content: flex-start; /* P1: Leader → Base → piles */
        gap: 6px; padding: 8px 6px;
    }

    /* Inner slots inside center columns are relative, not fixed */
    .swu-center-inner {
        position: relative !important;
        top: auto !important; left: auto !important;
        right: auto !important; bottom: auto !important;
        width: 100%;
        text-align: center; /* center the landscape card so the tilt is symmetric
                               and the corner tokens (Force / Epic-used) sit on it */
    }
    /* Don't let the per-render card wrappers clip the card horizontally (their
       overflow-y forces overflow-x to auto, which would crop the wide card). */
    #myLeaderWrapper, #theirLeaderWrapper,
    #myBaseWrapper,   #theirBaseWrapper { overflow: visible !important; }

    /* Pile rows — right end of the hand strip */
    .swu-pile-row {
        position: fixed; z-index: 37; pointer-events: auto;
        display: flex; gap: 6px; align-items: center;
        right: var(--swu-sidebar-w);
    }
    #myPileRow    { bottom: 0; height: var(--swu-hand-h); }
    #theirPileRow { top: 0;    height: var(--swu-hand-h); }

    .swu-pile {
        width: var(--swu-pile-w); min-height: 96px;
        border: 1px solid var(--swu-border); border-radius: 10px;
        background: var(--swu-surface); overflow: visible; position: relative;
    }
    .swu-pile-label {
        position: absolute; top: 4px; left: 0; right: 0;
        text-align: center; font: 700 8px/1 var(--swu-font-label);
        letter-spacing: 0.16em; text-transform: uppercase;
        color: rgba(255,255,255,0.38); pointer-events: none;
    }

    /* Action-available glow for Base and Resource counter — on the slot (neither tilts). */
    #myBaseSlot.has-action,
    #swuMyResCount.has-action {
        box-shadow: 0 0 14px 3px rgba(60,220,90,0.70), 0 0 4px 1px rgba(60,220,90,0.40);
        border-color: rgba(60,220,90,0.75) !important;
        transition: box-shadow 0.3s ease, border-color 0.3s ease;
    }
    /* Leader action glow: put it on the CARD (the data-mzid span), not the slot. The span
       carries the exhaust tilt (transform:rotate), so the glow rotates WITH the card. On the
       slot it stayed axis-aligned and looked detached around a tilted exhausted leader. */
    #myLeaderSlot.has-action [data-mzid] {
        box-shadow: 0 0 14px 3px rgba(60,220,90,0.70), 0 0 4px 1px rgba(60,220,90,0.40);
        border-radius: 7px;
        transition: box-shadow 0.3s ease;
    }

    /* Resource count line — full-width block so both it and the credit line center. */
    #swuMyResCount, #swuTheirResCount {
        display: block; width: 100%; text-align: center;
    }

    /* Credit-token count ("+ N") in the resource badge — gold, CR 3.13 */
    .swu-credit-count {
        display: block;
        color: #f2c14e;
        font-weight: 700;
        text-shadow: 0 0 4px rgba(242,193,78,0.55);
        cursor: help;
        margin-top: 2px;
    }

    /* Per-card Smuggle-available glow inside the resource slot */
    #myResourcesSlot .smuggle-available {
        box-shadow: 0 0 10px 2px rgba(60,220,90,0.65), 0 0 3px 1px rgba(60,220,90,0.35);
        border-radius: 4px;
        transition: box-shadow 0.3s ease;
    }


    /* Per-card discard-playable glow inside the discard slots */
    #myDiscardSlot .discard-playable,
    #theirDiscardSlot .discard-playable {
        box-shadow: 0 0 8px 3px #f0c040, inset 0 0 4px #f0c040;
        border-radius: 4px;
    }

    /* Per-unit "Action available" (.unit-action, cyan) and "can attack" (.can-attack,
       green) glows now live in GameLayoutShared.php next to the JS that applies them, so
       both desktop and mobile pick them up. */

    /* Attack / Ability chooser popup for a glowing .unit-action unit */
    .swu-unit-action-menu {
        position: fixed;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 4px;
        background: rgba(20,28,38,0.97);
        border: 1px solid #5fd0ff;
        border-radius: 6px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.6);
    }
    .swu-uam-btn {
        background: #1b2a3a;
        color: #e6f3ff;
        border: 1px solid #3a5168;
        border-radius: 4px;
        padding: 5px 16px;
        font-size: 13px;
        cursor: pointer;
        white-space: nowrap;
    }
    .swu-uam-btn:hover {
        background: #28455f;
        border-color: #5fd0ff;
    }

    /* Action-available glow for discard slot badges */
    #myDiscardSlot.has-action,
    #theirDiscardSlot.has-action {
        box-shadow: 0 0 14px 3px rgba(240,192,64,0.70), 0 0 4px 1px rgba(240,192,64,0.40);
        border-color: rgba(240,192,64,0.75) !important;
        transition: box-shadow 0.3s ease, border-color 0.3s ease;
    }

    /* Leader deployed state: ghost the leader card to simulate it moving to arena */
    #myLeaderSlot.is-deployed     > * { opacity: 0; pointer-events: none; }
    #theirLeaderSlot.is-deployed  > * { opacity: 0; pointer-events: none; }
    .swu-leader-slot-wrap { position: relative; }
    #myLeaderSlot.is-deployed::after,
    #theirLeaderSlot.is-deployed::after {
        content: "DEPLOYED";
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        font: 700 9px/1 var(--swu-font-label); letter-spacing: 0.22em;
        color: rgba(200,151,30,0.55); pointer-events: none; z-index: 2;
    }

    /* Leader and Base slot wrappers inside center column */
    .swu-leader-slot-wrap, .swu-base-slot-wrap {
        width: 100%; flex-shrink: 0;
        min-height: 96px;
        border: 1px solid var(--swu-border); border-radius: 10px;
        background: var(--swu-surface); overflow: visible; position: relative;
    }

    /* The Force token is rendered INSIDE the base card (top-right corner) by the
       core Card() renderer, driven by the base's HasForce virtual — same path as the
       Epic-Action-Used token. See Core/UILibraries20260415.js. */

    /* ── Counter badges below the frame animations ───────────────────────────────
       The shared CreateCountersHTML hardcodes z-index:1100 on every counter badge,
       which is above the frame animations (ScreenAnimations.css, z-index 1000-1002).
       SWUSim centers the damage badge right where the damage/heal animation plays, so
       the animation was hidden behind it. Drop the counters below the animation layer
       (still well above the card art) so damage/heal/shield pops over the badge.
       Scoped to this page; other sims keep the default ordering. */
    [data-counter-field] { z-index: 950 !important; }

    /* ── Resource badge — hand-band slot, right of initiative / left of the hand ──
       Transparent centering shell; the bordered box (the btn) hugs the contents
       (+4px padding) and lights up light-blue on hover. */
    .swu-res-badge {
        position: relative; z-index: 37; pointer-events: auto;
        margin-left: auto;                    /* pin to the right end of the cluster */
        height: 100%;
        display: flex; align-items: center; justify-content: flex-end;
        background: transparent;
    }

    .swu-res-badge-btn {
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
        padding: 8px;
        text-align: center;
        background: transparent;
        border: 1px solid transparent; border-radius: 8px;
        color: rgba(255,255,255,0.92); cursor: default;
        font: 700 14px/1 var(--swu-font-ui);
        text-shadow: 0 1px 4px rgba(0,0,0,0.85);   /* legible over the board bg */
        white-space: nowrap;
        transition: box-shadow 150ms ease, border-color 150ms ease;
    }
    .swu-res-badge-btn.is-clickable { cursor: pointer; }
    .swu-res-badge-btn:hover {
        border-color: rgba(120,200,255,0.40);
        box-shadow: 0 0 16px 2px rgba(120,200,255,0.40),
                    inset 0 0 10px rgba(120,200,255,0.12);
    }
    .swu-res-img {
        width: 34px; height: 34px; object-fit: contain;
        filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6));
    }

    /* ── Resource zone panel (expandable, my side only) ──────────────────────── */
    .swu-resource-panel {
        position: fixed; z-index: 50;
        left: calc(var(--swu-hud-pad) + var(--swu-init-w)); width: clamp(220px, 22vw, 320px);
        max-height: 40vh; overflow-y: auto;
        background: rgba(11,15,20,0.96);
        border: 1px solid var(--swu-border-hi);
        border-radius: 0 12px 12px 0;
        padding: 28px 8px 8px; display: none;
        backdrop-filter: blur(14px);
    }
    /* Opens upward from the resources slot in the hand band. */
    #myResourcesSlot { bottom: var(--swu-hand-h); }

    /* Lift the bottom-anchored selection prompt above the hand so it doesn't overlap cards */
    #selection-message {
        bottom: calc(var(--swu-hand-h) + 12px) !important;
    }
    /* …but the inline MultiChoose panel is CENTERED (Core sets top:50%; bottom:auto).
       The lift above would override that bottom:auto and stretch the panel tall, so
       restore bottom:auto for that mode (detected by its Confirm button). */
    #selection-message:has(#inline-multi-confirm) {
        bottom: auto !important;
    }
    .swu-resource-panel.is-open { display: block; }
    .swu-resource-panel::before {
        content: "RESOURCES"; position: absolute; top: 9px; left: 10px;
        color: rgba(255,255,255,0.55); font: 700 9px/1 var(--swu-font-label);
        letter-spacing: 0.20em; text-transform: uppercase; }

    /* ── Hand panels ─────────────────────────────────────────────────────────── */
    .swu-hand-panel {
        position: fixed; z-index: 36; pointer-events: auto;
        /* Right-anchored just before the deck/discard pile zone; sized to ~84% of
           the span between the resources slot and the piles. The freed space opens
           up on the left, between the resources slot and the hand. */
        right: calc(var(--swu-sidebar-w) + var(--swu-pile-zone-w));
        width: var(--swu-hand-w);
        height: var(--swu-hand-h);
        background:
            linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02)),
            linear-gradient(160deg, rgba(11,15,20,0.82), rgba(11,15,20,0.60));
        border: 1px solid var(--swu-border);
        overflow: visible;
        transition: transform 250ms cubic-bezier(0.4,0,0.2,1);
    }
    #theirHandSlot { top: 0; border-radius: 0 0 8px 8px; border-top: none; }
    #myHandSlot    { bottom: 0; border-radius: 8px 8px 0 0; border-bottom: none; }

    /* Card wrapper (rendered by NextTurnRender) = the horizontal scroll viewport.
       overflow-y hidden so the hand never grows into the deck/pile band; previews
       use the separate ShowDetail popup, not in-place growth. */
    #myHandWrapper, #theirHandWrapper {
        width: 100%; height: 100%;
        overflow-x: auto !important; overflow-y: hidden !important;
        scrollbar-width: thin;
        scrollbar-color: rgba(120,200,255,0.45) transparent;
    }
    #myHandWrapper::-webkit-scrollbar, #theirHandWrapper::-webkit-scrollbar { height: 7px; }
    #myHandWrapper::-webkit-scrollbar-thumb, #theirHandWrapper::-webkit-scrollbar-thumb {
        background: rgba(120,200,255,0.45); border-radius: 99px; }

    /* Card row (PopulateZone's #myHand span) — single nowrap line. Centered while
       it fits; once it overflows, `safe center` falls back to flex-start so the
       leading cards stay reachable by scroll instead of being clipped. */
    #myHand, #theirHand {
        flex-wrap: nowrap !important;
        justify-content: safe center !important;
        align-items: center !important;
        min-width: 100%; height: 100%; gap: 4px;
    }
    #myHand > span, #theirHand > span { flex: 0 0 auto; }

    /* Suppress empty-state text */
    #myHand > span:not([id]), #theirHand > span:not([id]) { display: none; }

    /* Hand collapse */
    .swu-hand-collapse-btn {
        position: absolute; top: 0; left: 50%;
        transform: translateX(-50%) translateY(-50%);
        width: 46px; height: 16px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(11,15,20,0.90); border: 1px solid var(--swu-border-hi);
        border-radius: 99px; cursor: pointer;
        color: rgba(255,255,255,0.55); font-size: 8px; line-height: 1;
        pointer-events: auto; z-index: 2;
        transition: color 120ms, background 120ms, border-color 120ms;
        user-select: none; -webkit-user-select: none;
    }
    .swu-hand-collapse-btn:hover {
        color: rgba(255,255,255,0.95); background: rgba(30,45,65,0.96);
        border-color: var(--swu-gold); }
    #myHandSlot.is-collapsed    { transform: translateY(calc(100% - 16px)); }
    #myHandSlot.is-collapsed:hover { transform: translateY(calc(100% - 16px)) !important; }
    #theirHandSlot.is-collapsed { transform: translateY(calc(-100% + 16px)); }
    #theirHandSlot.is-collapsed:hover { transform: translateY(calc(-100% + 16px)) !important; }

    /* ── Effect Stack ────────────────────────────────────────────────────────── */
    .swu-stack {
        width: clamp(200px, 24vw, 420px); min-height: 80px;
        border: 1px solid rgba(100,140,220,0.35); border-radius: 16px;
        background: rgba(8,13,22,0.96);
        box-shadow: 0 0 0 1px rgba(255,255,255,0.06), 0 8px 32px rgba(0,0,0,0.7);
        backdrop-filter: blur(16px); padding: 28px 10px 10px;
    }
    .swu-stack::before {
        content: "Effect Stack ('U' to undo)"; position: absolute; top: 9px; left: 12px;
        color: rgba(100,160,255,0.70); font: 700 9px/1 var(--swu-font-label);
        letter-spacing: 0.20em; text-transform: uppercase; pointer-events: none; }
    #EffectStackSlot {
        position: fixed; z-index: 200; pointer-events: auto;
        left: 50%; transform: translateX(calc(-50% - var(--swu-sidebar-w)/2));
        max-width: calc(100vw - var(--swu-sidebar-w) - 32px);
    }
    #EffectStackSlot[data-draggable="true"] { cursor: grab; }
    #EffectStackSlot[data-dragging="true"]  { cursor: grabbing; user-select: none; }
    #EffectStackSlot.is-custom-position     { transform: none !important; }
    #EffectStackWrapper { overflow-x: auto !important; overflow-y: auto !important;
        max-height: min(25vh, 200px); scrollbar-width: thin; }
    #EffectStack { flex-wrap: wrap !important; min-width: 100%; gap: 6px; justify-content: center; }
    #EffectStack > span { flex: 0 0 auto; }

    /* ── Right sidebar ───────────────────────────────────────────────────────── */
    #swuSidebar {
        position: fixed; right: 0; top: 0; bottom: 0; width: var(--swu-sidebar-w);
        z-index: 38; pointer-events: auto;
        background: rgba(8,12,18,0.96); border-left: 1px solid var(--swu-border);
        backdrop-filter: blur(16px) saturate(130%);
        display: flex; flex-direction: column; overflow: hidden;
    }
    #swuSidebarHeader {
        flex: 0 0 auto; display: flex; align-items: center;
        justify-content: space-between; padding: 12px 14px 10px;
        border-bottom: 1px solid var(--swu-border);
    }
    #swuUndoBtn {
        display: none;
        font: 600 11px/1 var(--swu-font-label);
        letter-spacing: 0.10em; text-transform: uppercase;
        background: rgba(200,151,30,0.15); color: rgba(200,151,30,0.90);
        border: 1px solid rgba(200,151,30,0.35); border-radius: 5px;
        padding: 6px 10px; cursor: pointer; transition: background 120ms;
    }
    #swuUndoBtn:hover:not(:disabled) { background: rgba(200,151,30,0.28) !important; }
    #swuUndoBtn:disabled { opacity: 0.35; cursor: not-allowed; }
    .swu-round-label {
        font: 700 9px/1 var(--swu-font-label); letter-spacing: 0.18em;
        text-transform: uppercase; color: rgba(255,255,255,0.40); }
    #swuRoundNumber {
        font: 700 20px/1 var(--swu-font-label); color: rgba(255,255,255,0.90); }
    #swuLastPlayedSection {
        flex: 0 0 auto; padding: 8px 14px; border-bottom: 1px solid var(--swu-border); }
    .swu-sidebar-section-label {
        font: 700 8px/1 var(--swu-font-label); letter-spacing: 0.18em;
        text-transform: uppercase; color: rgba(255,255,255,0.35); margin-bottom: 6px; }
    #swuLastPlayedCard {
        min-height: 40px; display: flex; align-items: center; justify-content: center;
        color: rgba(255,255,255,0.20); font: 11px var(--swu-font-ui); font-style: italic; }

    /* ── GameLog tab bar ─────────────────────────────────────────────────────── */
    #swuTabBar {
        flex: 0 0 auto;
        display: flex;
        border-bottom: 1px solid var(--swu-border);
    }
    .swu-tab-btn {
        flex: 1 1 0; padding: 6px 0;
        background: transparent; border: none;
        font: 700 9px/1 var(--swu-font-label);
        letter-spacing: 0.16em; text-transform: uppercase;
        color: rgba(255,255,255,0.45); cursor: pointer;
        position: relative;
        border-bottom: 2px solid transparent;
        transition: color 120ms, border-color 120ms;
    }
    .swu-tab-btn.is-active {
        color: rgba(255,255,255,0.92);
        border-bottom-color: var(--swu-gold);
    }
    #swuChatDot {
        display: none;
        position: absolute; top: 4px; right: 12px;
        width: 7px; height: 7px; border-radius: 50%;
        background: #e05050;
    }
    /* ── Log panel ───────────────────────────────────────────────────────────── */
    #swuLogPanel {
        flex: 1 1 0; min-height: 0;
        overflow-y: auto; padding: 8px 10px;
        scrollbar-width: thin;
    }
    #swuChatMount {
        flex: 1 1 0; min-height: 0;
        display: flex; flex-direction: column;
        overflow: hidden;
    }
    /* Keep the input+send row from being squeezed by the log */
    #chatExpanded > div:last-child {
        flex: 0 0 auto !important;
    }
    .swu-log-entry {
        font: 11px/1.55 var(--swu-font-ui);
        color: var(--swu-log-default);
        padding: 1px 0;
        word-break: break-word;
    }
    .swu-log-PHASE {
        color: var(--swu-log-phase);
        font-style: italic;
        padding: 4px 0 2px;
    }
    /* One rule per log type → its var (single source of truth in :root above). */
    .swu-log-OVERWHELM  { color: var(--swu-log-overwhelm); }
    .swu-log-REVEAL     { color: var(--swu-log-reveal); }
    .swu-log-DISCLOSE   { color: var(--swu-log-disclose); }
    .swu-log-ABILITY    { color: var(--swu-log-ability); }
    .swu-log-PLAY       { color: var(--swu-log-play); }
    .swu-log-DRAW       { color: var(--swu-log-draw); }
    .swu-log-DISCARD    { color: var(--swu-log-discard); }
    .swu-log-DEFEAT     { color: var(--swu-log-defeat); }
    .swu-log-ATTACK     { color: var(--swu-log-attack); }
    .swu-log-RESOURCE   { color: var(--swu-log-resource); }
    .swu-log-NAMECARD   { color: var(--swu-log-namecard); }
    .swu-log-PASS       { color: var(--swu-log-pass); }
    .swu-log-INITIATIVE { color: var(--swu-log-initiative); }
    .swu-card-link {
        text-decoration: underline;
        text-decoration-style: dotted;
        cursor: pointer;
        color: inherit;
    }
    .swu-card-link:hover { opacity: 0.8; }
    /* ── Condensed Last Played ───────────────────────────────────────────────── */
    #swuLastPlayedSection {
        flex: 0 0 auto;
        padding: 4px 14px;
        border-bottom: 1px solid var(--swu-border);
        display: flex; align-items: center; gap: 8px;
    }
    .swu-sidebar-section-label { margin-bottom: 0 !important; }
    #swuLastPlayedCard {
        min-height: 0 !important;
        font-size: 10px !important;
        font-style: italic;
        flex: 1 1 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* Chat widget override */
    #chatWidget {
        position: static !important; left: auto !important; right: auto !important;
        bottom: auto !important; top: auto !important;
        width: 100% !important; flex: 1 1 0 !important; min-height: 0 !important;
        display: flex !important; flex-direction: column !important;
        align-items: stretch !important; background: transparent !important;
        z-index: auto !important; padding: 0 !important;
    }
    #chatExpanded {
        display: flex !important; flex-direction: column !important;
        flex: 1 1 0 !important; min-height: 0 !important; width: 100% !important;
    }
    #chatLog {
        flex: 1 1 0 !important; height: auto !important; min-height: 0 !important;
        max-height: none !important; background: transparent !important;
        border: none !important; border-bottom: 1px solid var(--swu-border) !important;
        border-radius: 0 !important; color: rgba(255,255,255,0.78) !important;
        font: 12px/1.55 var(--swu-font-ui) !important;
        padding: 8px 10px !important; overflow-y: auto !important;
        scrollbar-width: thin !important;
        display: flex !important; flex-direction: column !important;
        box-sizing: border-box !important;
    }
    /* Spacer that eats unused vertical space, pushing messages to the bottom */
    #chatLog::before {
        content: '' !important; display: block !important; flex: 1 1 auto !important;
    }
    #chatWidget input#chatText {
        background: rgba(255,255,255,0.05) !important; border: none !important;
        border-top: 1px solid var(--swu-border) !important;
        color: rgba(255,255,255,0.88) !important; font: 13px var(--swu-font-ui) !important;
        padding: 8px 10px !important; height: auto !important;
        border-radius: 0 !important; outline: none !important;
    }
    #chatWidget input#chatText:focus {
        background: rgba(255,255,255,0.08) !important;
        border-top-color: var(--swu-border-hi) !important;
    }
    #chatWidget button:not(#chatToggleBtn) {
        background: rgba(200,151,30,0.15) !important; border: none !important;
        border-top: 1px solid var(--swu-border) !important;
        border-left: 1px solid var(--swu-border) !important;
        border-radius: 0 !important; color: rgba(200,151,30,0.90) !important;
        font: 600 12px var(--swu-font-label) !important;
        padding: 8px 14px !important; height: auto !important;
        cursor: pointer !important; box-shadow: none !important;
        transition: background 120ms !important;
    }
    #chatWidget button:not(#chatToggleBtn):hover {
        background: rgba(200,151,30,0.28) !important; }
    #chatToggleBtn { display: none !important; }

    /* ── Turn miasma ─────────────────────────────────────────────────────────── */
    #turn-miasma-overlay .turn-edge-glyph { width: 32px; height: min(60vh,500px); }
    #turn-miasma-overlay .turn-edge-glyph::before,
    #turn-miasma-overlay .turn-edge-glyph::after {
        width: 9px; transform: translateX(-50%); border-radius: 0; }
    #turn-miasma-overlay .turn-edge-glyph::before { clip-path: polygon(50% 0,100% 100%,0 100%); }
    #turn-miasma-overlay .turn-edge-glyph::after  { clip-path: polygon(0 0,100% 0,50% 100%); }

    /* ── Responsive ──────────────────────────────────────────────────────────── */
    @media (max-width: 1100px) {
        :root { --swu-sidebar-w: 220px; --swu-center-w: 160px; --swu-res-badge-w: 0px; }
    }
    @media (max-width: 800px) {
        :root { --swu-sidebar-w: 0px; --swu-center-w: 140px; --swu-res-badge-w: 0px; }
        #swuSidebar { display: none !important; }
        #chatWidget {
            position: fixed !important; bottom: 20px !important; left: 10px !important;
            width: 260px !important; flex: none !important;
        }
        #chatExpanded { display: none !important; flex: none !important; }
        #chatToggleBtn { display: block !important; }
    }
</style>

<!-- Board background + decorative -->
<div class="swu-board-bg"></div>
<div class="swu-starfield"></div>

<!-- Column separators -->
<div id="swuSepLeft"  class="swu-col-sep" style="left:var(--swu-center-left);"></div>
<div id="swuSepRight" class="swu-col-sep" style="left:var(--swu-ground-left);"></div>

<!-- Midline -->
<div class="swu-midline-bar"></div>


<!-- Phase track + initiative -->
<!--
<div id="swuMidbar" class="swu-midbar" style="top:calc(var(--swu-midline) - 22px);">
    <span class="swu-phase-step" data-phase-step="APS">Action</span>
    <span class="swu-phase-step" data-phase-step="MAIN">Action</span>
    <span class="swu-phase-step" data-phase-step="RGS">Regroup</span>
    <span class="swu-phase-step" data-phase-step="DRAW">Draw</span>
    <span class="swu-phase-step" data-phase-step="RES">Resource</span>
    <span class="swu-phase-step" data-phase-step="READY">Ready</span>
</div>
-->
<!-- Initiative hex now lives inside the hand-band control clusters below
     (#swuMyControlBand / #swuTheirControlBand); updateInitiative() reparents it. -->

<!-- Pass button — my side only, at the bottom of the center control cluster -->
<div id="swuPassControl" class="swu-init-pass is-idle">
    <!-- Take/Keep the Initiative — the action lives in YOUR controls, not on the token.
         updateInitiative() unhides it when legal (canTake) and sets the label Take vs Keep. -->
    <button id="swuTakeInitBtn" class="swu-init-pass-btn swu-take-init" title="Take the Initiative (I)" hidden
            onclick="event.stopPropagation(); window.swuTakeInitiative();"><span>Take Initiative</span></button>
    <!-- "I" hotkey hint — carries .swu-take-init so updateInitiative() shows/hides it with the button -->
    <div id="swuTakeInitHint" class="swu-init-pass-hint swu-take-init" title="Press I to take/keep the initiative" hidden><kbd>I</kbd></div>
    <button id="swuPassBtn" class="swu-init-pass-btn" title="Pass (Space)"><span>Pass</span></button>
    <div class="swu-init-pass-hint" title="Press Space to pass"><kbd>Space</kbd></div>
</div>
<!--
<div class="swu-kb-hints" style="top:calc(var(--swu-midline) + 6px);">
    <span><kbd>U</kbd> Undo</span>
    <span><kbd>S</kbd> Save</span>
    <span><kbd>Space</kbd> Pass</span>
    <span><kbd>↓</kbd> Collapse hand</span>
</div>
-->

<!-- ═══════════════════ SPACE ARENA — LEFT COLUMN ══════════════════════════════ -->
<div id="spaceArenaBg" class="swu-arena-bg swu-arena-bg-space">
    <div id="theirSpaceArenaSlot" class="swu-arena-col swu-arena-col-space swu-arena-col-top">
    </div>
    <div id="mySpaceArenaSlot" class="swu-arena-col swu-arena-col-space swu-arena-col-bot">
    </div>
</div>

<!-- ═══════════════════ GROUND ARENA — RIGHT COLUMN ═══════════════════════════ -->
<div id="groundArenaBg" class="swu-arena-bg swu-arena-bg-ground">
    <div id="theirGroundArenaSlot" class="swu-arena-col swu-arena-col-ground swu-arena-col-top">
    </div>
    <div id="myGroundArenaSlot" class="swu-arena-col swu-arena-col-ground swu-arena-col-bot">
    </div>
</div>

<!-- ═══════════════════ CENTER — THEIR HALF (Leader top → Base nearest mid) ════ -->
<div class="swu-center-col swu-center-col-top">

    <!-- Their leader (further from midline) -->
    <div class="swu-leader-slot-wrap">
        <div id="theirLeaderSlot" class="swu-zone swu-center-inner"></div>
    </div>

    <!-- Their base (closest to midline) -->
    <div class="swu-base-slot-wrap">
        <div id="theirBaseSlot" class="swu-zone swu-center-inner"></div>
    </div>

</div>

<!-- ═══════════════════ CENTER — MY HALF (Base nearest mid → Leader bottom) ════ -->
<div class="swu-center-col swu-center-col-bot">

    <!-- My base (closest to midline) -->
    <div class="swu-base-slot-wrap">
        <div id="myBaseSlot" class="swu-zone swu-center-inner"></div>
    </div>

    <!-- My leader (further from midline) -->
    <div class="swu-leader-slot-wrap">
        <div id="myLeaderSlot" class="swu-zone swu-center-inner"></div>
    </div>

</div>

<!-- ═══════════════════ PILE ROWS — bottom-right (mine) / top-right (theirs) ═══ -->
<div id="theirPileRow" class="swu-pile-row">
    <div class="swu-pile">
        <div class="swu-pile-label">Deck</div>
        <div id="theirDeckSlot" style="min-height:96px;"></div>
    </div>
    <div class="swu-pile">
        <div class="swu-pile-label">Discard</div>
        <div id="theirDiscardSlot" style="min-height:96px;"></div>
    </div>
</div>
<div id="myPileRow" class="swu-pile-row">
    <div class="swu-pile">
        <div class="swu-pile-label">Deck</div>
        <div id="myDeckSlot" style="min-height:96px;"></div>
    </div>
    <div class="swu-pile">
        <div class="swu-pile-label">Discard</div>
        <div id="myDiscardSlot" style="min-height:96px;"></div>
    </div>
</div>

<!-- ═══════════════════ HAND-BAND CONTROL CLUSTERS (bottom-left / top-left) ═════
     Narrow per-side strip: initiative hex (left) ←space-between→ resource badge
     (right). The single init hex is reparented into the controlling band by
     updateInitiative(); the resource badge stays pinned right (margin-left:auto). -->
<!-- My control band — bottom-left, above my hand -->
<div id="swuMyControlBand" class="swu-control-band is-bottom">
    <!-- Initiative hex — default home; moves to the controlling side -->
    <div id="swuInitControl" class="swu-init-control">
        <div id="swuInitHex" class="swu-init-hex" title="Initiative">
            <span id="swuInitHexText"><span class="swu-init-fill"></span><span class="swu-init-word">Initiative</span></span>
        </div>
    </div>
    <!-- My resources badge -->
    <div id="swuMyResBadge" class="swu-res-badge">
        <div class="swu-res-badge-btn is-clickable"
             title="Click to view your resources" onclick="swuToggleMyResources()">
            <img class="swu-res-img" src="./Assets/Icons/swusim-resource-badge.webp" alt="Resources">
            <span id="swuMyResCount">0/0</span>
        </div>
    </div>
</div>

<!-- Their control band — top-left, below their hand -->
<div id="swuTheirControlBand" class="swu-control-band is-top">
    <!-- Opponent resources badge -->
    <div id="swuTheirResBadge" class="swu-res-badge">
        <div class="swu-res-badge-btn" title="Opponent resources (hidden)">
            <img class="swu-res-img" src="./Assets/Icons/swusim-resource-badge.webp" alt="Resources">
            <span id="swuTheirResCount">0/0</span>
        </div>
    </div>
</div>

<!-- ═══════════════════ RESOURCE ZONES (managed by badge) ════════════════════ -->
<!-- My resources: expandable panel, opens upward from bottom-left -->
<div id="myResourcesSlot" class="swu-resource-panel"></div>
<!-- Their resources: hidden from engine (face-down cards, not shown) -->
<div id="theirResourcesSlot" class="swu-zone"
     style="width:1px; height:1px; overflow:hidden; opacity:0; pointer-events:none; top:0; left:0;">
</div>

<!-- ═══════════════════ HANDS ══════════════════════════════════════════════════ -->
<div id="theirHandSlot" class="swu-hand-panel"></div>
<div id="myHandSlot"    class="swu-hand-panel"></div>

<!-- ═══════════════════ EFFECT STACK ══════════════════════════════════════════ -->
<div id="EffectStackSlot" class="swu-stack swu-zone"
     style="top:calc(var(--swu-midline) - 44px);">
</div>

<!-- ═══════════════════ RIGHT SIDEBAR ══════════════════════════════════════════ -->
<div id="swuSidebar">
    <div id="swuSidebarHeader">
        <div>
            <div class="swu-round-label">Round</div>
            <div id="swuRoundNumber">—</div>
        </div>
        <button id="swuUndoBtn" onclick="SubmitInput(10004, '')">Undo</button>
    </div>
    <div id="swuLastPlayedSection">
        <div class="swu-sidebar-section-label">Last Played</div>
        <div id="swuLastPlayedCard">—</div>
    </div>
    <div id="swuTabBar">
        <button class="swu-tab-btn is-active" id="swuTabLog"  onclick="swuShowTab('log')">Log</button>
        <button class="swu-tab-btn"            id="swuTabChat" onclick="swuShowTab('chat')">
            Chat<span id="swuChatDot"></span>
        </button>
    </div>
    <div id="swuLogPanel"></div>
    <div id="swuChatMount"></div>
</div>

<?php include __DIR__ . '/GameLayoutShared.php'; ?>
