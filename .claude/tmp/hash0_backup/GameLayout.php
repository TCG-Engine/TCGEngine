<?php
// GameLayout.php — SWUSim board layout v3: horizontal arena columns.
// LEFT = Space Arena, CENTER = Leader/Base/Piles, RIGHT = Ground Arena.
// Zone slot IDs must match BindTo values in GameSchema.txt.
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
        --swu-space-bg:     rgba(30,60,120,0.20);
        --swu-ground-bg:    rgba(120,80,30,0.20);
        --swu-font-ui:      "Aptos","Segoe UI Variable","Trebuchet MS",sans-serif;
        --swu-font-label:   "Bahnschrift","Aptos Display","Franklin Gothic Medium",sans-serif;

        /* ── Layout ── */
        --swu-sidebar-w:    clamp(160px, 14vw, 200px);
        --swu-board-w:      calc(100vw - var(--swu-sidebar-w));
        --swu-center-w:     196px;   /* leader + base column */
        --swu-hand-h:       118px;
        --swu-pile-w:       88px;

        --swu-log-phase:     #9b59b6;
        --swu-log-overwhelm: #e05050;
        --swu-log-reveal:    #f0c040;

        /* 110px reserved on the left for the resource badge */
        --swu-res-badge-w:  110px;

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

    /* ── Initiative badge ────────────────────────────────────────────────────── */
    #swuInitiativeDisplay {
        position: fixed; z-index: 20; pointer-events: none;
        left: 50%; transform: translateX(calc(-50% - var(--swu-sidebar-w)/2 + var(--swu-res-badge-w)/2));
        display: flex; align-items: center; gap: 7px;
        padding: 5px 14px 5px 10px;
        border: 1px solid var(--swu-border); border-radius: 99px;
        background: rgba(11,15,20,0.82); backdrop-filter: blur(8px);
        color: rgba(255,255,255,0.72);
        font: 600 11px/1 var(--swu-font-label); letter-spacing: 0.14em; text-transform: uppercase;
    }
    #swuTakeInitBtn, #swuPassBtn {
        pointer-events: auto;
    }
    #swuInitiativeDisplay .swu-init-dot {
        width: 9px; height: 9px; border-radius: 50%;
        background: var(--swu-gold); box-shadow: 0 0 8px rgba(200,151,30,0.7); }
    #swuInitiativeDisplay.is-claimed .swu-init-dot {
        background: rgba(200,151,30,0.30); box-shadow: none; }

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

    /* ── Arena columns ───────────────────────────────────────────────────────── */
    .swu-arena-col {
        position: fixed; z-index: 30; pointer-events: auto;
        width: var(--swu-col-w);
        overflow: hidden; border-radius: 0;
    }
    .swu-arena-col-space  { background: var(--swu-space-bg);  left: var(--swu-space-left);  }
    .swu-arena-col-ground { background: var(--swu-ground-bg); left: var(--swu-ground-left); }
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
    #myGroundArena,   #theirGroundArena { justify-content: flex-start !important; }
    #theirSpaceArena > span, #theirGroundArena > span,
    #mySpaceArena    > span, #myGroundArena    > span { flex: 0 0 auto; }

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
    }

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

    /* Action-available glow for Leader, Base, and Resource counter */
    #myLeaderSlot.has-action,
    #myBaseSlot.has-action,
    #swuMyResCount.has-action {
        box-shadow: 0 0 14px 3px rgba(60,220,90,0.70), 0 0 4px 1px rgba(60,220,90,0.40);
        border-color: rgba(60,220,90,0.75) !important;
        transition: box-shadow 0.3s ease, border-color 0.3s ease;
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

    /* Per-unit "Action available" glow — clicking the unit uses its Action ability */
    .unit-action {
        box-shadow: 0 0 9px 3px #5fd0ff, inset 0 0 4px #5fd0ff;
        border-radius: 4px;
        cursor: pointer;
    }

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

    /* ── Resource badge — anchored to hand edge (bottom-left / top-left) ──────── */
    .swu-res-badge {
        position: fixed; z-index: 37; pointer-events: auto;
        width: 110px;
        display: flex; flex-direction: column; align-items: stretch; gap: 0;
        background: rgba(8,12,18,0.90);
        border: 1px solid var(--swu-border); border-radius: 10px;
        overflow: hidden;
    }
    #swuMyResBadge    { bottom: 0; left: 0; border-bottom: none; border-left: none; border-radius: 0 10px 0 0; }
    #swuTheirResBadge { top: 0;    left: 0; border-top: none; border-left: none; border-radius: 0 0 10px 0; }

    .swu-res-badge-label {
        padding: 5px 8px 2px;
        font: 700 8px/1 var(--swu-font-label); letter-spacing: 0.18em;
        text-transform: uppercase; color: rgba(255,255,255,0.35); }

    .swu-res-badge-btn {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        padding: 6px 10px 7px;
        background: transparent; border: none;
        color: rgba(255,255,255,0.85); cursor: default;
        font: 700 14px/1 var(--swu-font-ui);
        transition: background 120ms; width: 100%;
    }
    .swu-res-badge-btn.is-clickable { cursor: pointer; }
    .swu-res-badge-btn.is-clickable:hover { background: rgba(200,151,30,0.12); }
    .swu-res-icon { font-size: 11px; opacity: 0.6; }

    /* ── Resource zone panel (expandable, my side only) ──────────────────────── */
    .swu-resource-panel {
        position: fixed; z-index: 50;
        left: 0; width: clamp(220px, 22vw, 320px);
        max-height: 40vh; overflow-y: auto;
        background: rgba(11,15,20,0.96);
        border: 1px solid var(--swu-border-hi);
        border-radius: 0 12px 0 0;
        padding: 28px 8px 8px; display: none;
        backdrop-filter: blur(14px);
    }
    #myResourcesSlot { bottom: var(--swu-hand-h); }

    /* Lift the selection prompt above the hand so it doesn't overlap cards */
    #selection-message {
        bottom: calc(var(--swu-hand-h) + 12px) !important;
    }
    .swu-resource-panel.is-open { display: block; }
    .swu-resource-panel::before {
        content: "RESOURCES"; position: absolute; top: 9px; left: 10px;
        color: rgba(255,255,255,0.55); font: 700 9px/1 var(--swu-font-label);
        letter-spacing: 0.20em; text-transform: uppercase; }

    /* ── Hand panels ─────────────────────────────────────────────────────────── */
    .swu-hand-panel {
        position: fixed; z-index: 36; pointer-events: auto;
        left: var(--swu-res-badge-w); width: calc(100vw - var(--swu-sidebar-w) - var(--swu-res-badge-w));
        min-height: var(--swu-hand-h);
        background:
            linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02)),
            linear-gradient(160deg, rgba(11,15,20,0.82), rgba(11,15,20,0.60));
        border: 1px solid var(--swu-border);
        overflow: visible;
        transition: transform 250ms cubic-bezier(0.4,0,0.2,1);
    }
    #theirHandSlot { top: 0; border-radius: 0 0 8px 8px; border-top: none; }
    #myHandSlot    { bottom: 0; border-radius: 8px 8px 0 0; border-bottom: none; }

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
        color: rgba(255,255,255,0.78);
        padding: 1px 0;
        word-break: break-word;
    }
    .swu-log-PHASE {
        color: var(--swu-log-phase);
        font-style: italic;
        padding: 4px 0 2px;
    }
    .swu-log-OVERWHELM { color: var(--swu-log-overwhelm); }
    .swu-log-REVEAL    { color: var(--swu-log-reveal); }
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
        :root { --swu-sidebar-w: 220px; --swu-center-w: 160px; --swu-res-badge-w: 96px; }
    }
    @media (max-width: 800px) {
        :root { --swu-sidebar-w: 0px; --swu-center-w: 140px; --swu-res-badge-w: 88px; }
        #swuSidebar { display: none !important; }
        #chatWidget {
            position: fixed !important; bottom: 20px !important; left: 10px !important;
            width: 260px !important; flex: none !important;
        }
        #chatExpanded { display: none !important; flex: none !important; }
        #chatToggleBtn { display: block !important; }
    }
</style>

<!-- Decorative -->
<div class="swu-starfield"></div>

<!-- Column separators -->
<div id="swuSepLeft"  class="swu-col-sep" style="left:var(--swu-center-left);"></div>
<div id="swuSepRight" class="swu-col-sep" style="left:var(--swu-ground-left);"></div>

<!-- Midline -->
<div class="swu-midline-bar"></div>

<!-- Arena labels -->
<div class="swu-arena-label"
     style="left:calc(var(--swu-space-left) + var(--swu-col-w)/2); top:calc(var(--swu-midline) - 11px);
            color:rgba(59,127,196,0.45); text-shadow:0 0 12px rgba(59,127,196,0.30);">SPACE</div>
<div class="swu-arena-label"
     style="left:calc(var(--swu-ground-left) + var(--swu-col-w)/2); top:calc(var(--swu-midline) - 11px);
            color:rgba(200,151,30,0.40); text-shadow:none;">GROUND</div>

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
<div id="swuInitiativeDisplay" style="top:calc(var(--swu-midline) - 8px);">
    <span class="swu-init-dot"></span>
    <span id="swuInitiativeText">Initiative: —</span>
    <button id="swuTakeInitBtn"
            style="display:none; margin-left:6px; padding:1px 7px; font-size:11px;
                   background:var(--swu-surface); border:1px solid #888; border-radius:3px;
                   color:#eee; cursor:pointer;">Take Initiative <kbd style="font-size:9px;background:rgba(255,255,255,0.1);border:1px solid #888;border-radius:2px;padding:0 3px;">I</kbd></button>
    <button id="swuPassBtn"
            style="display:none; margin-left:4px; padding:1px 7px; font-size:11px;
                   background:var(--swu-surface); border:1px solid #666; border-radius:3px;
                   color:#bbb; cursor:pointer;">Pass <kbd style="font-size:9px;background:rgba(255,255,255,0.1);border:1px solid #666;border-radius:2px;padding:0 3px;">Space</kbd></button>
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
<div id="theirSpaceArenaSlot" class="swu-arena-col swu-arena-col-space swu-arena-col-top">
</div>
<div id="mySpaceArenaSlot" class="swu-arena-col swu-arena-col-space swu-arena-col-bot">
</div>

<!-- ═══════════════════ GROUND ARENA — RIGHT COLUMN ═══════════════════════════ -->
<div id="theirGroundArenaSlot" class="swu-arena-col swu-arena-col-ground swu-arena-col-top">
</div>
<div id="myGroundArenaSlot" class="swu-arena-col swu-arena-col-ground swu-arena-col-bot">
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

<!-- ═══════════════════ RESOURCE BADGES (bottom-left / top-left) ══════════════ -->
<!-- My resources badge — bottom-left, above my hand -->
<div id="swuMyResBadge" class="swu-res-badge">
    <div class="swu-res-badge-label">Resources</div>
    <div class="swu-res-badge-btn is-clickable"
         title="Click to view your resources" onclick="swuToggleMyResources()">
        <span class="swu-res-icon">🃏</span>
        <span id="swuMyResCount">0 / 0</span>
    </div>
</div>

<!-- Opponent resources badge — top-left, below their hand -->
<div id="swuTheirResBadge" class="swu-res-badge">
    <div class="swu-res-badge-label">Resources</div>
    <div class="swu-res-badge-btn" title="Opponent resources (hidden)">
        <span class="swu-res-icon">🂠</span>
        <span id="swuTheirResCount">0 / 0</span>
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
    function updateInitiative() {
        var el      = document.getElementById('swuInitiativeDisplay');
        var txt     = document.getElementById('swuInitiativeText');
        var initBtn = document.getElementById('swuTakeInitBtn');
        var passBtn = document.getElementById('swuPassBtn');
        if (!el || !txt) return;

        var state = typeof window.InitiativeCounterData === 'string'
            ? window.InitiativeCounterData.trim() : '';
        var labels = {
            P1_UNCLAIMED:'Initiative: P1', P2_UNCLAIMED:'Initiative: P2',
            P1_CLAIMED:'Initiative taken (P1)', P2_CLAIMED:'Initiative taken (P2)'
        };
        txt.textContent = labels[state] || 'Initiative: —';
        el.classList.toggle('is-claimed', state==='P1_CLAIMED'||state==='P2_CLAIMED');

        var isClaimed   = (state==='P1_CLAIMED' || state==='P2_CLAIMED');
        var isMyTurn    = (String(window.TurnPlayerData||'').trim() === String(MY_PLAYER_ID));
        var isMainPhase = (String(window.CurrentPhaseData||'').trim() === 'MAIN');
        var canAct      = isMyTurn && isMainPhase;

        // "Take Initiative": only when initiative is still claimable this round
        if (initBtn) initBtn.style.display = (canAct && !isClaimed) ? 'inline-block' : 'none';

        // "Pass": any time it is your turn in MAIN phase (CR 1.15.6 — always legal)
        // Label shows "(end round)" hint when opponent already passed (consecutive-pass state)
        if (passBtn) {
            passBtn.style.display = canAct ? 'inline-block' : 'none';
            var opponentPassed = (parseInt(window.DecisionQueueVariablesData, 10) >= 1);
            passBtn.title = opponentPassed
                ? 'Pass — opponent already passed, this ends the action phase'
                : 'Pass';
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
    function parseResCountFromData(rawData) {
        if (!rawData || rawData === '' || rawData === '-') return {ready:0, total:0};
        var entries = rawData.split('<|>');
        var total = 0, exhausted = 0;
        for (var i = 0; i < entries.length; i++) {
            var entry = entries[i].trim();
            if (!entry) continue;
            total++;
            var spaceIdx = entry.indexOf(' ');
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
        return {ready: total - exhausted, total: total};
    }

    function updateResCounterFromData(dataVar, countElId) {
        var el = document.getElementById(countElId); if (!el) return;
        var raw = window[dataVar] || '';
        var c = parseResCountFromData(raw);
        el.textContent = c.ready + ' / ' + c.total;
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

    // Close resource panel when clicking outside
    document.addEventListener('click', function(e) {
        var panel = document.getElementById('myResourcesSlot');
        var badge = document.getElementById('swuMyResBadge');
        if (panel && panel.classList.contains('is-open') &&
            !panel.contains(e.target) && e.target !== badge && !badge.contains(e.target)) {
            panel.classList.remove('is-open');
        }
    });

    // ── Auto-hide Effect Stack when empty ─────────────────────────────────────
    function watchSlot(id) {
        var el = document.getElementById(id); if (!el) return;
        el.style.display = 'none';
        new MutationObserver(function() {
            el.style.display = (el.querySelector('[id$="-0"]') !== null) ? '' : 'none';
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

    // ── Poll global data ──────────────────────────────────────────────────────
    function pollGlobals() {
        updatePhaseTrack(); updateInitiative(); updateRound(); refreshActionGlows();
        refreshResourceSelectionPanel();
        swuUpdateUndoUI(MY_PLAYER_ID);
    }
    function watchGlobalData() {
        pollGlobals();
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
        (d.unitActions || []).forEach(function(mz) {
            var el = document.querySelector('[data-mzid="' + mz + '"]');
            if (el) el.classList.add('unit-action');
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
        var initBtn = document.getElementById('swuTakeInitBtn');
        if (initBtn) initBtn.addEventListener('click', window.swuTakeInitiative);
        var passBtn = document.getElementById('swuPassBtn');
        if (passBtn) passBtn.addEventListener('click', window.swuPassAction);
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
            var isMyTurn    = (String(window.TurnPlayerData||'').trim() === String(MY_PLAYER_ID));
            var isMainPhase = (String(window.CurrentPhaseData||'').trim() === 'MAIN');
            if (isMyTurn && isMainPhase) window.swuPassAction();
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

    // Intercept FlashMessageData before NextTurnRender consumes it.
    // If the value starts with "GAMEOVER:", show persistent banner and suppress normal flash.
    var _flashInternal = '';
    Object.defineProperty(window, 'FlashMessageData', {
        configurable: true,
        get: function () { return _flashInternal; },
        set: function (v) {
            if (typeof v === 'string' && v.indexOf('GAMEOVER:') === 0) {
                showGameOverBanner(v.slice(9));
                _flashInternal = '';
            } else {
                _flashInternal = v;
            }
        }
    });

    if (document.readyState==='loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(<?php echo intval($playerID); ?>);
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
    heatmapFunction, heatmapColorMap, mzId, overlayTypes, overlayDescriptorsJSON) {
    // Force landscape ratio for leader and base zone cards
    if (mzId && /^(my|their)(Leader|Base)-/.test(mzId)) {
        landscape = 1;
    }
    return Card(cardNumber, folder, maxHeight, action, showHover,
        overlay, borderColor, counters, actionDataOverride, id, rotate,
        lifeCounters, defCounters, atkCounters, controller, restriction,
        isBroken, onChain, isFrozen, gem, landscape, epicActionUsed,
        heatmapFunction, heatmapColorMap, mzId, overlayTypes, overlayDescriptorsJSON);
};
</script>
