<?php
// GameLayoutMobile.php — SWUSim phone layout: a single vertical scroll of the
// board, opponent at the top → me at the bottom. Routed to from GameLayout.php
// for phone user-agents (or ?swuLayout=mobile).
//
// Reuses the SAME engine slot IDs as the desktop layout, so GameLayoutShared.php
// (initiative reparenting, resource counts, log/chat, animations) drives it with
// zero changes. Only the markup order and CSS differ.
//
// Stack order:
//   [Init · Their Resources · Their Deck/Discard]   ← their control band
//   [Their Hand] [Their Space] [Their Ground]
//   [Their Leader · Their Base]
//   [My Leader · My Base]
//   [My Ground] [My Space] [My Hand]
//   [Init · My Resources · My Deck/Discard · Pass]  ← my control band
?>
<style>
    #swuMobileRoot {
        /* design tokens (desktop :root isn't loaded on this layout) */
        --swu-gold:       var(--accent-gold, #c8971e);   /* theme-driven (design-system token) */
        --swu-border:     rgba(255,255,255,0.10);
        --swu-border-hi:  rgba(255,255,255,0.22);
        --swu-font-ui:    "Aptos","Segoe UI Variable","Trebuchet MS",sans-serif;
        --swu-font-label: "Bahnschrift","Aptos Display","Franklin Gothic Medium",sans-serif;
        --swu-m-gap:      8px;
        --swu-m-arena-h:  252px;   /* visible height per arena ≈ 2 card rows; scrolls beyond */

        position: fixed; inset: 0; z-index: 20;
        overflow-y: auto; overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        display: flex; flex-direction: column;
        /* Board art (mobile variant) under a darkening overlay so UI stays legible.
           Follows the <base>.webp / <base>-mobile.webp convention — see SWUBoardBackground(). */
        background:
            linear-gradient(180deg, rgba(6,10,16,0.62), rgba(6,10,16,0.82)),
            var(--swu-cos-board, url('<?= SWUBoardBackground(true) ?>')) center center / cover no-repeat;
        color: rgba(255,255,255,0.92);
        font-family: var(--swu-font-ui);
    }

    /* ── Section wrapper + label ─────────────────────────────────────────────── */
    .swu-m-section { padding: 4px 6px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .swu-m-section.is-mine   { background: rgba(var(--turn-mine-rgb),0.06); }
    .swu-m-section.is-theirs { background: rgba(var(--turn-theirs-rgb),0.05); }
    /* Hand/arena labels removed to reclaim vertical space (zones are self-evident). */
    .swu-m-label { display: none; }

    /* ── Horizontal-scroll card rows (arenas + hands) ────────────────────────── */
    .swu-m-scroll {
        display: flex; align-items: center; gap: 4px;
        overflow-x: auto; overflow-y: hidden;
        min-height: 132px; padding: 2px;
        scrollbar-width: thin; scrollbar-color: var(--glow) transparent;
    }
    /* Opponent's hand is face-down — give it a shorter row to save vertical space. */
    #theirHandSlot.swu-m-scroll { min-height: 74px; }
    /* My hand sits in the sticky footer; keep it compact too. */
    #myHandSlot.swu-m-scroll { min-height: 104px; }
    .swu-m-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .swu-m-scroll::-webkit-scrollbar-thumb { background: var(--glow); border-radius: 99px; }
    /* Arenas: cards fill left→right, then wrap to the next line (row-major). The slot
       is a fixed ~2-row height and scrolls VERTICALLY for any overflow (not sideways). */
    #theirSpaceArenaSlot, #mySpaceArenaSlot,
    #theirGroundArenaSlot, #myGroundArenaSlot {
        display: block;                    /* override .swu-m-scroll flex */
        height: var(--swu-m-arena-h); min-height: 0;
        overflow-x: hidden; overflow-y: auto;
    }
    #swuMobileRoot #theirSpaceArena, #swuMobileRoot #mySpaceArena,
    #swuMobileRoot #theirGroundArena, #swuMobileRoot #myGroundArena {
        display: flex !important; flex-wrap: wrap !important;
        justify-content: safe center !important;
        align-content: flex-start !important; align-items: flex-start !important;
        gap: 20px; width: 100%;   /* 2× the desktop arena gap (10px) */
    }
    /* Hands stay a single horizontally-scrolling row. */
    #swuMobileRoot #myHand, #swuMobileRoot #theirHand {
        display: flex !important; flex-wrap: nowrap !important;
        justify-content: safe center !important; align-items: center !important;
        gap: 4px; height: 100%; min-width: 100%;
    }
    /* No hand-collapse affordance on mobile — hide the shared ▼ button that
       setupHandCollapse() injects as #myHandSlot's first child. It also stole flex
       space at the row's left edge, overflowing the slot so `safe center` fell back to
       left-aligned — hiding it lets the hand actually center. */
    #swuMobileRoot .swu-hand-collapse-btn { display: none !important; }
    /* The engine wraps hand cards in a shrink-to-fit #myHandWrapper (NextTurnRender.php),
       which is a flex child pinned to the slot's left edge — so centering #myHand alone
       isn't enough. Center the wrapper at the SLOT level; `safe` falls back to start when
       the hand overflows so horizontal scroll still reaches the leading cards. */
    #swuMobileRoot #myHandSlot, #swuMobileRoot #theirHandSlot { justify-content: safe center; }
    /* Hide the empty-zone placeholder text label the engine injects (zone name, e.g.
       "Hand"/"Discard"/"SpaceArena"). :not(:has(img)) keeps it text-only so a real
       card pile (deck card back, single discard card) is never hidden. */
    #swuMobileRoot [id$="Arena"]   > span:only-child:not([id]):not(:has(img)),
    #swuMobileRoot [id$="Hand"]    > span:only-child:not([id]):not(:has(img)),
    #swuMobileRoot [id$="Discard"] > span:only-child:not([id]):not(:has(img)),
    #swuMobileRoot [id$="Deck"]    > span:only-child:not([id]):not(:has(img)) { display: none; }

    /* On-card badges/icons shrunk to ~70% on mobile (power/HP/damage/keywords + shield
       orb). Uses `zoom` rather than `transform: scale()`: CounterRendering injects an
       INLINE transform: translate(...) on any badge with an OffsetX/OffsetY (Power & HP
       both do — see CounterRules) and on the centered Damage badge, and an inline
       transform beats a stylesheet one — so scale() was silently ignored. `zoom` is a
       separate property, so it composes with the inline translate instead of fighting it. */
    #swuMobileRoot [data-counter-field] { zoom: 0.64; }
    #swuMobileRoot img.counter-image-icon[title="Shield"] { zoom: 0.64; }

    /* ── Side-by-side arena row (Space | Ground) ─────────────────────────────── */
    .swu-m-arena-row {
        position: relative;
        display: flex; gap: 6px; padding: 5px 30px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        /* Playmat (mobile): ApplyCosmeticPlaymats() sets the per-side mat image on
           this element; `cover`/center shows just its inner vertical slice — the
           piece that fits the phone's tall-narrow arena band. */
        background-position: center; background-size: cover; background-repeat: no-repeat;
    }
    /* Faint side tint — the base look when no playmat is set. */
    .swu-m-arena-row.is-mine   { background-color: rgba(var(--turn-mine-rgb),0.06); }
    .swu-m-arena-row.is-theirs { background-color: rgba(var(--turn-theirs-rgb),0.05); }
    /* The blue HUD darkening overlay lives on each arena BOX (.swu-m-arena-col ::after)
       so it's clipped to the cyan-bracketed boundary rather than the whole row. */
    /* Light-blue tech-HUD frame per arena — faint full border + glow plus bright
       cyan L-brackets at the corners (matches desktop .swu-arena-bg). Lives on the
       non-scrolling col wrapper so the brackets stay put while the slot scrolls. */
    .swu-m-arena-col {
        position: relative; isolation: isolate; flex: 1 1 0; min-width: 0; padding: 4px;
        border: 1px solid rgba(var(--accent-rgb),0.22); border-radius: 4px;
        box-shadow: 0 0 6px rgba(var(--accent-rgb),0.10), inset 0 0 14px rgba(var(--accent-rgb),0.08);
        animation: swuMArenaPulse 3.2s ease-in-out infinite;
    }
    @keyframes swuMArenaPulse {
        0%, 100% { border-color: rgba(var(--accent-rgb),0.18);
                   box-shadow: 0 0 5px rgba(var(--accent-rgb),0.08), inset 0 0 12px rgba(var(--accent-rgb),0.05); }
        50%      { border-color: rgba(var(--accent-rgb),0.42);
                   box-shadow: 0 0 13px rgba(var(--accent-rgb),0.28), inset 0 0 18px rgba(var(--accent-rgb),0.13); }
    }
    /* The blue HUD darkening (.swu-m-arena-col::after) is a SHARED style, defined in
       GameLayoutShared.php so desktop (.swu-arena-bg) and mobile use one definition.
       Card content sits above it; brackets stay on top of everything. */
    .swu-m-arena-col > * { position: relative; z-index: 1; }
    .swu-m-arena-col::before {
        content: ''; position: absolute; inset: -1px; z-index: 3; pointer-events: none;
        --c: var(--accent-strong);   /* bracket color (theme accent) */
        --len: 18px;                   /* arm length    */
        --th: 3px;                     /* arm thickness */
        filter: drop-shadow(0 0 4px rgba(var(--accent-rgb),0.75));   /* light glow on the brackets */
        animation: swuMArenaBracketPulse 3.2s ease-in-out infinite;
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
    @keyframes swuMArenaBracketPulse {
        0%, 100% { filter: drop-shadow(0 0 3px rgba(var(--accent-rgb),0.45)); }
        50%      { filter: drop-shadow(0 0 8px rgba(var(--accent-rgb),0.95)); }
    }

    /* ── Leader / Base row ───────────────────────────────────────────────────── */
    .swu-m-centers {
        display: flex; gap: var(--swu-m-gap); justify-content: center;
        padding: 6px; border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .swu-m-centers.is-mine   { background: rgba(var(--turn-mine-rgb),0.06); }
    .swu-m-centers.is-theirs { background: rgba(var(--turn-theirs-rgb),0.05); }
    .swu-m-center { flex: 1 1 0; max-width: 48%; display: flex; justify-content: center; }
    .swu-m-center > div { width: 100%; display: flex; justify-content: center; }
    /* Consolidated single row: 4 zones, mine (left half) | theirs (right half).
       Extra side padding squishes the cards inward so the turn-indicator bars at the
       screen edges have room. */
    .swu-m-centers-row {
        gap: 4px; padding: 6px 30px;
        background: linear-gradient(90deg,
            rgba(var(--turn-mine-rgb),0.06) 0 50%, rgba(var(--turn-theirs-rgb),0.05) 50% 100%);
    }
    .swu-m-centers-row .swu-m-center { max-width: none; }

    /* ── Control bands (their header / my footer) ────────────────────────────── */
    .swu-m-band {
        display: flex; align-items: center; gap: 6px;
        padding: 3px 8px; min-height: 54px;
        background: rgba(8,12,18,0.75);
        border-top: 1px solid var(--swu-border); border-bottom: 1px solid var(--swu-border);
    }
    /* My footer sticks to the bottom; the Take/Keep prompt rides directly above the band as
       one sticky stack (so it pins above the busy footer rather than scrolling away). The
       opponent's band scrolls normally at the top. */
    .swu-m-footer-stack { position: sticky; bottom: 0; z-index: 25; margin-top: auto;
        display: flex; flex-direction: column; }
    .swu-m-footer-stack .swu-m-band.is-mine { position: static; margin-top: 0; }
    .swu-m-band.is-mine   { position: sticky; bottom: 0; z-index: 25; margin-top: auto; }

    /* Take/Keep Initiative — lives INSIDE the Pass control (#swuPassControl), stacked directly
       above the Pass button and sharing its width (the column stretches both). Hidden until
       updateInitiative() shows it (canTake). */
    .swu-m-takeinit { display: flex; padding: 0; background: transparent; }
    .swu-m-takeinit[hidden] { display: none; }
    .swu-m-takeinit-btn {
        flex: 1 1 auto; pointer-events: auto; position: relative; cursor: pointer; border: 0;
        padding: 5px 10px; background: var(--accent); color: var(--btn-text, var(--text));
        font: 700 9px/1 var(--swu-font-label); letter-spacing: 0.16em; text-transform: uppercase;
        text-shadow: 0 0 5px var(--glow);
        clip-path: polygon(11px 0, 100% 0, 100% calc(100% - 11px), calc(100% - 11px) 100%, 0 100%, 0 11px);
        filter: drop-shadow(0 0 5px rgba(var(--accent-rgb),0.4));
    }
    .swu-m-takeinit-btn::before { content: ''; position: absolute; inset: 2px; z-index: 0;
        background: var(--btn-fill);
        clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px); }
    .swu-m-takeinit-btn > span { position: relative; z-index: 1; }
    .swu-m-takeinit-btn:active { transform: translateY(0.5px); }
    /* Initiative already claimed this round — greyed & inert ("Initiative Claimed") instead of hidden. */
    .swu-take-init.is-taken .swu-m-takeinit-btn {
        background: rgba(255,255,255,0.14); color: rgba(255,255,255,0.42);
        text-shadow: none; filter: none; cursor: default; pointer-events: none;
    }
    .swu-take-init.is-taken .swu-m-takeinit-btn::before { background: rgba(20,24,30,0.92); }

    /* Piles inside the bands */
    .swu-m-pile { display: flex; flex-direction: column; align-items: center; gap: 2px; }
    .swu-m-pile-label { font: 700 7px/1 var(--swu-font-label); letter-spacing: 0.12em;
        text-transform: uppercase; color: rgba(255,255,255,0.45); }
    /* The pile SLOT only (not the label, which is also a direct div child). */
    .swu-m-pile > div:not(.swu-m-pile-label) {
        min-width: 30px; min-height: 42px; max-height: 46px;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    /* Clamp the deck/discard card to a compact thumbnail (engine renders it at size 96). */
    .swu-m-pile > div:not(.swu-m-pile-label) img:not(.counter-image-icon) {
        height: 42px !important; width: auto !important; border-radius: 3px;
    }
    /* The stack-count bubble (.counter-bubble, e.g. discard "2") is sized for a full card,
       so it's oversized on the 42px pile thumbnail. Override the inline anchor transform
       (translate(-50%,-50%)) with !important to BOTH shrink it (scale 0.6) and lift it 8px.
       The -8px sits in the translate so it's true screen pixels (unscaled by the scale()). */
    #swuMobileRoot .swu-m-pile .counter-bubble {
        transform: translate(-50%, calc(-50% - 8px)) scale(0.6) !important;
    }

    /* ── Initiative hex (reused id; mobile-sized) ────────────────────────────── */
    .swu-init-control { flex: 0 0 auto; pointer-events: none; }
    /* Reserved slot for the initiative token — keeps each band's layout stable when the
       single token reparents to the OTHER side (mirrors desktop's reserved init gap, where
       the res badge is margin-left:auto'd). Width matches .swu-init-hex; it collapses in
       whichever band currently holds the real token, so it never doubles the footprint. */
    .swu-init-placeholder { flex: 0 0 auto; width: 44px; pointer-events: none; }
    .swu-m-band:has(#swuInitControl) .swu-init-placeholder { display: none; }
    /* Octagonal HUD initiative token (see desktop). Unclaimed = faded blue lines,
       claimed = solid cyan, takeable = brighter rim + glow. */
    .swu-init-hex {
        position: relative; width: 44px; height: 40px; box-sizing: border-box; padding: 1.5px;
        clip-path: polygon(9px 0, calc(100% - 9px) 0, 100% 9px, 100% calc(100% - 9px), calc(100% - 9px) 100%, 9px 100%, 0 calc(100% - 9px), 0 9px);
        background: rgba(120,200,255,0.50);
        pointer-events: auto; cursor: help;            /* status badge: hover shows the tooltip */
        filter: drop-shadow(0 1px 4px rgba(0,0,0,0.55));
    }
    #swuInitHexText {
        position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;
        box-sizing: border-box; padding: 0 1px; text-align: center;
        clip-path: polygon(9px 0, calc(100% - 9px) 0, 100% 9px, 100% calc(100% - 9px), calc(100% - 9px) 100%, 9px 100%, 0 calc(100% - 9px), 0 9px);
        background:
            repeating-linear-gradient(0deg, transparent 0 0.2px, rgba(150,215,255,0.50) 0.8px, transparent 1.4px 2.5px),
            rgba(14,27,42,0.92);
    }
    .swu-init-fill {
        position: absolute; inset: 0; z-index: 0; pointer-events: none;
        transform: scaleY(0); transform-origin: bottom;
        background: linear-gradient(160deg, rgba(120,215,255,0.96), rgba(70,180,240,0.96));
        transition: transform 430ms cubic-bezier(0.32,0,0.2,1);
    }
    .swu-init-control.is-claimed .swu-init-fill { transform: scaleY(1); }
    .swu-init-word {
        position: relative; z-index: 1;
        color: rgba(185,222,255,0.85); font: 700 6.5px/1 var(--swu-font-label);
        letter-spacing: 0; text-transform: uppercase; white-space: nowrap;
        text-shadow: 0 1px 2px rgba(0,0,0,0.45);
        transition: color 360ms, text-shadow 360ms;
    }
    .swu-init-control.is-claimed .swu-init-word { color: rgba(8,22,38,0.95); text-shadow: none; }
    .swu-init-control.is-takeable .swu-init-hex {   /* status hint that the Take/Keep button is live */
        background: rgba(150,215,255,0.92); filter: drop-shadow(0 0 9px rgba(120,200,255,0.70));
    }
    .swu-init-control.is-takeable .swu-init-word { color: rgba(225,242,255,0.95); }
    .swu-init-control.is-claimed .swu-init-hex {
        background: rgba(150,215,255,0.95); filter: drop-shadow(0 0 8px rgba(120,200,255,0.55));
    }
    .swu-init-control.is-leaving  { animation: swuInitLeave 200ms ease forwards; }
    .swu-init-control.is-entering { animation: swuInitEnter 340ms ease; }
    @keyframes swuInitLeave  { to   { opacity: 0; } }
    @keyframes swuInitEnter  { from { opacity: 0; } }

    /* ── Resource badge (reused ids) ─────────────────────────────────────────── */
    .swu-res-badge { flex: 0 0 auto; pointer-events: auto; }
    .swu-res-badge-btn {
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        padding: 3px 6px; border: 1px solid transparent; border-radius: 8px;
        color: rgba(255,255,255,0.92); font: 700 11px/1 var(--swu-font-ui);
        text-shadow: 0 1px 4px rgba(0,0,0,0.85); white-space: nowrap;
    }
    .swu-res-badge-btn.is-clickable { cursor: pointer; }
    .swu-res-img { width: 30px; height: 30px; object-fit: contain;
        filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6)); }

    /* ── Pass button ─────────────────────────────────────────────────────────── */
    .swu-init-pass { flex: 0 0 auto; margin-left: auto; pointer-events: none;
        display: flex; flex-direction: column; align-items: stretch; gap: 4px; }
    /* Flat HUD panel — chamfered corners, thin cyan edge, flat fill (sci-fi interface).
       Cyan body = thin rim; ::before = flat fill inset inside it; label <span> on top. */
    .swu-init-pass-btn {
        position: relative; pointer-events: auto; cursor: pointer;
        padding: 5px 14px; border: 0; background: var(--accent);   /* rim */
        color: var(--btn-text, var(--text)); font: 700 9px/1 var(--swu-font-label);
        letter-spacing: 0.16em; text-transform: uppercase;
        text-shadow: 0 0 5px var(--glow);
        clip-path: polygon(9px 0, 100% 0, 100% calc(100% - 9px), calc(100% - 9px) 100%, 0 100%, 0 9px);
        filter: drop-shadow(0 0 4px var(--glow));
        transition: filter 150ms, color 150ms, transform 110ms;
    }
    .swu-init-pass-btn::before {
        content: ''; position: absolute; inset: 1.5px; z-index: 0;
        background: var(--btn-fill);   /* flat fill */
        clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px);
        transition: background 150ms;
    }
    .swu-init-pass-btn > span { position: relative; z-index: 1; }
    .swu-init-pass-btn:active { transform: translateY(0.5px); }
    /* Active: accent-tinted themed fill (was hardcoded navy). */
    .swu-init-pass-btn:active::before { background: linear-gradient(rgba(var(--accent-rgb),0.60), rgba(var(--accent-rgb),0.60)), var(--btn-fill); }
    .swu-init-pass.is-idle .swu-init-pass-btn { opacity: 0.35; pointer-events: none; }
    .swu-init-pass-hint { display: none; }

    /* ── Resource panel (slide-up overlay; reuses .is-open from shared JS) ────── */
    .swu-resource-panel {
        position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
        max-height: 55vh; overflow-y: auto; display: none;
        background: rgba(11,15,20,0.97); border-top: 1px solid var(--swu-border-hi);
        border-radius: 14px 14px 0 0; padding: 30px 10px 16px;
        backdrop-filter: blur(14px);
    }
    .swu-resource-panel.is-open { display: block; }
    .swu-resource-panel::before {
        content: "RESOURCES"; position: absolute; top: 10px; left: 14px;
        color: rgba(255,255,255,0.55); font: 700 10px/1 var(--swu-font-label);
        letter-spacing: 0.20em; text-transform: uppercase;
    }

    /* ── Log / chat drawer (collapsible, reuses #swuSidebar ids) ──────────────── */
    #swuSidebar {
        order: 99; margin-top: 0; border-top: 1px solid var(--swu-border);
        background: rgba(8,12,18,0.85); padding: 8px 10px;
    }
    #swuSidebarHeader { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .swu-round-label { font: 700 8px/1 var(--swu-font-label); letter-spacing: 0.14em;
        text-transform: uppercase; color: rgba(255,255,255,0.45); }
    #swuRoundNumber { font: 700 16px/1 var(--swu-font-ui); }
    #swuUndoBtn {
        padding: 6px 14px; border: 1px solid var(--swu-border-hi); border-radius: 7px;
        background: var(--panel-scrim); color: #fff; font: 700 11px/1 var(--swu-font-label);
        text-transform: uppercase; letter-spacing: 0.08em; cursor: pointer;
    }
    #swuLastPlayedSection { display: none; }
    #swuTabBar { display: flex; gap: 6px; margin: 8px 0 6px; }
    .swu-tab-btn {
        flex: 1; padding: 7px; border: 1px solid var(--swu-border); border-radius: 7px;
        background: transparent; color: rgba(255,255,255,0.6);
        font: 700 10px/1 var(--swu-font-label); text-transform: uppercase; cursor: pointer;
    }
    .swu-tab-btn.is-active { background: rgba(120,200,255,0.14); color: #fff; border-color: var(--accent); }
    #swuLogPanel { max-height: 28vh; overflow-y: auto; font-size: 12px; }

    /* Chat — integrate the core #chatWidget into the Chat tab drawer (same as desktop)
       instead of letting it float as the GA-style #chatToggleBtn launcher. mountChat()
       (shared) reparents #chatWidget into #swuChatMount; these overrides un-float it. */
    #chatToggleBtn { display: none !important; }   /* kill the floating "💬 Chat" launcher */
    #chatWidget {
        position: static !important; left: auto !important; right: auto !important;
        top: auto !important; bottom: auto !important;
        width: 100% !important; flex: none !important; min-height: 0 !important;
        display: flex !important; flex-direction: column !important;
        align-items: stretch !important; background: transparent !important;
        z-index: auto !important; padding: 0 !important;
    }
    #chatExpanded {
        display: flex !important; flex-direction: column !important;
        width: 100% !important; min-height: 0 !important;
    }
    #chatLog {
        max-height: 28vh !important; height: auto !important; min-height: 0 !important;
        overflow-y: auto !important; background: transparent !important;
        border: none !important; border-radius: 0 !important;
        color: rgba(255,255,255,0.82) !important; font: 12px/1.5 var(--swu-font-ui) !important;
        padding: 6px 4px !important; box-sizing: border-box !important;
    }
    #chatWidget input#chatText {
        background: rgba(255,255,255,0.05) !important; border: none !important;
        border-top: 1px solid var(--swu-border) !important;
        color: rgba(255,255,255,0.88) !important; font: 13px var(--swu-font-ui) !important;
        padding: 7px 8px !important; height: auto !important;
        border-radius: 0 !important; outline: none !important;
    }
    #chatWidget button:not(#chatToggleBtn) {
        background: rgba(200,151,30,0.15) !important; border: none !important;
        border-top: 1px solid var(--swu-border) !important;
        border-radius: 0 !important; color: rgba(200,151,30,0.90) !important;
        font: 600 12px var(--swu-font-label) !important;
        padding: 7px 12px !important; height: auto !important; cursor: pointer !important;
    }

    #EffectStackSlot { position: fixed; top: 8px; right: 8px; z-index: 55; pointer-events: auto; }
</style>

<div id="swuMobileRoot">

    <!-- ════════ THEIR control band: init (when they hold it) · resources · piles ════════ -->
    <div id="swuTheirControlBand" class="swu-m-band is-theirs">
        <!-- Reserved init-token slot; collapses when they actually hold the token -->
        <div class="swu-init-placeholder" aria-hidden="true"></div>
        <div id="swuTheirResBadge" class="swu-res-badge">
            <div class="swu-res-badge-btn" title="Opponent resources (hidden)">
                <img class="swu-res-img" src="./Assets/Icons/swusim-resource-badge.webp" alt="Resources">
                <span id="swuTheirResCount">0/0</span>
            </div>
        </div>
        <div class="swu-m-pile"><div class="swu-m-pile-label">Deck</div><div id="theirDeckSlot"></div></div>
        <div class="swu-m-pile"><div class="swu-m-pile-label">Discard</div><div id="theirDiscardSlot"></div></div>
    </div>

    <!-- ════════ THEIR hand / arenas ════════ -->
    <div class="swu-m-section is-theirs"><div class="swu-m-label">Their Hand</div>
        <div id="theirHandSlot" class="swu-m-scroll"></div></div>
    <div class="swu-m-arena-row is-theirs">
        <div class="swu-m-arena-col"><div class="swu-m-label">Their Space</div>
            <div id="theirSpaceArenaSlot" class="swu-m-scroll"></div></div>
        <div class="swu-m-arena-col"><div class="swu-m-label">Their Ground</div>
            <div id="theirGroundArenaSlot" class="swu-m-scroll"></div></div>
    </div>

    <!-- ════════ Leader/Base — one row: my leader·base | their base·leader (bases meet mid) ════════ -->
    <div class="swu-m-centers swu-m-centers-row">
        <div class="swu-m-center is-mine"><div id="myLeaderSlot"></div></div>
        <div class="swu-m-center is-mine"><div id="myBaseSlot"></div></div>
        <div class="swu-m-center is-theirs"><div id="theirBaseSlot"></div></div>
        <div class="swu-m-center is-theirs"><div id="theirLeaderSlot"></div></div>
    </div>

    <!-- ════════ MY arenas / hand ════════ -->
    <div class="swu-m-arena-row is-mine">
        <div class="swu-m-arena-col"><div class="swu-m-label">My Space</div>
            <div id="mySpaceArenaSlot" class="swu-m-scroll"></div></div>
        <div class="swu-m-arena-col"><div class="swu-m-label">My Ground</div>
            <div id="myGroundArenaSlot" class="swu-m-scroll"></div></div>
    </div>
    <div class="swu-m-section is-mine"><div class="swu-m-label">My Hand</div>
        <div id="myHandSlot" class="swu-m-scroll"></div></div>

    <!-- ════════ MY footer stack: Take/Keep prompt (when legal) sits above the control band ═══ -->
    <div class="swu-m-footer-stack">
    <!-- ════════ MY control band: init (default home) · resources · piles · pass ════════ -->
    <div id="swuMyControlBand" class="swu-m-band is-mine">
        <!-- Initiative hex default home; updateInitiative() moves it to the controlling side -->
        <div id="swuInitControl" class="swu-init-control">
            <div id="swuInitHex" class="swu-init-hex" title="Initiative">
                <span id="swuInitHexText"><span class="swu-init-fill"></span><span class="swu-init-word">Initiative</span></span>
            </div>
        </div>
        <!-- Reserved init-token slot; collapses while my band holds the token (default) -->
        <div class="swu-init-placeholder" aria-hidden="true"></div>
        <div id="swuMyResBadge" class="swu-res-badge">
            <div class="swu-res-badge-btn is-clickable"
                 title="View your resources" onclick="swuToggleMyResources()">
                <img class="swu-res-img" src="./Assets/Icons/swusim-resource-badge.webp" alt="Resources">
                <span id="swuMyResCount">0/0</span>
            </div>
        </div>
        <div class="swu-m-pile"><div class="swu-m-pile-label">Deck</div><div id="myDeckSlot"></div></div>
        <div class="swu-m-pile"><div class="swu-m-pile-label">Discard</div><div id="myDiscardSlot"></div></div>
        <div id="swuPassControl" class="swu-init-pass is-idle">
            <!-- Take/Keep the Initiative — stacks directly above Pass in the same control; updateInitiative() unhides it when legal -->
            <div id="swuMobileTakeInit" class="swu-m-takeinit swu-take-init" hidden>
                <button class="swu-m-takeinit-btn" onclick="event.stopPropagation(); window.swuTakeInitiative();"><span>Take Initiative</span></button>
            </div>
            <button id="swuPassBtn" class="swu-init-pass-btn" title="Pass"><span>Pass</span></button>
            <div class="swu-init-pass-hint"><kbd>Space</kbd></div>
        </div>
    </div>
    </div><!-- /swu-m-footer-stack -->

    <!-- ════════ Overlays / engine-managed zones ════════ -->
    <div id="myResourcesSlot" class="swu-resource-panel"></div>
    <div id="theirResourcesSlot"
         style="width:1px; height:1px; overflow:hidden; opacity:0; pointer-events:none; position:fixed; top:0; left:0;"></div>
    <div id="EffectStackSlot"></div>

    <!-- ════════ Log / chat drawer ════════ -->
    <div id="swuSidebar">
        <div id="swuSidebarHeader">
            <div>
                <div class="swu-round-label">Round</div>
                <div id="swuRoundNumber">—</div>
            </div>
            <div class="swu-header-right">
                <button id="swuUndoBtn" onclick="SubmitInput(10004, '')">Undo</button>
                <button id="swuGearBtn" class="swu-gear-btn" title="Settings" aria-label="Settings" onclick="swuOpenSettings()">&#9881;</button>
            </div>
        </div>
        <div id="swuLastPlayedSection">
            <div class="swu-sidebar-section-label">Last Played</div>
            <div id="swuLastPlayedCard">—</div>
        </div>
        <div id="swuTabBar">
            <button class="swu-tab-btn is-active" id="swuTabLog"  onclick="swuShowTab('log')">Log</button>
            <button class="swu-tab-btn"            id="swuTabChat" onclick="swuShowTab('chat')">Chat<span id="swuChatDot"></span></button>
        </div>
        <div id="swuLogPanel"></div>
        <div id="swuChatMount"></div>
    </div>

</div>

<?php include __DIR__ . '/GameLayoutShared.php'; ?>
