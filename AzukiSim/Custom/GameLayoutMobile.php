<?php
// GameLayoutMobile.php - portrait and landscape phone layouts for AzukiSim.
//
// Reuses the same generated slot IDs as desktop, but compacts the phone board
// into one fixed viewport. IKZ zones are hidden as bind targets and summarized
// into small ready/total controls.
?>
<style>
    html,
    body {
        margin: 0;
        padding: 0;
        overflow: hidden;
        background: #0d0e0f;
    }

    :root {
        --azuki-gold: #f3f3f3;
        --azuki-teal: #b9c1c6;
        --azuki-light: #f4f4f4;
        --azuki-font-ui: "Segoe UI Variable Display", "Aptos", sans-serif;
        --azuki-font-label: "Franklin Gothic Medium", "Bahnschrift", sans-serif;
    }

    #myStuff,
    #theirStuff {
        border: 0 !important;
        background: transparent !important;
    }

    #azukiMobileRoot {
        position: fixed;
        inset: 0;
        z-index: 40;
        height: 100dvh;
        padding-top: 36px;
        overflow: hidden;
        display: grid;
        grid-template-rows: 58px minmax(0, 1fr) minmax(0, 1fr) 100px 58px;
        color: rgba(244, 244, 244, 0.96);
        font-family: var(--azuki-font-ui);
        background:
            radial-gradient(circle at 50% 48%, rgba(255, 255, 255, 0.025), transparent 48%),
            #0d0e0f;
    }

    #azukiMobileRoot,
    #azukiMobileRoot * {
        box-sizing: border-box;
    }

    #azukiMobileRoot .azuki-zone {
        position: static;
        z-index: 1;
        pointer-events: auto;
    }

    .azuki-m-band,
    .azuki-m-section,
    .azuki-m-lanes {
        min-height: 0;
        overflow: hidden;
        border-bottom: 1px solid rgba(255, 255, 255, 0.075);
    }

    .azuki-m-band {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 3px 6px;
        background: rgba(8, 9, 10, 0.94);
    }

    .azuki-m-band.is-mine,
    .azuki-m-section.is-mine,
    .azuki-m-lanes.is-mine {
        background-color: rgba(255, 255, 255, 0.012);
    }

    .azuki-m-band.is-theirs,
    .azuki-m-section.is-theirs,
    .azuki-m-lanes.is-theirs {
        background-color: rgba(255, 255, 255, 0.006);
    }

    .azuki-m-section {
        display: flex;
        flex-direction: column;
        padding: 3px 6px 4px;
    }

    .azuki-m-lanes {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 5px;
        padding: 4px 6px 5px;
    }

    .azuki-m-lane {
        min-width: 0;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .azuki-m-label,
    .azuki-m-pile-label {
        color: rgba(244, 244, 244, 0.62);
        font: 700 7px/1 var(--azuki-font-label);
        letter-spacing: 0.1em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .azuki-m-label {
        flex: 0 0 auto;
        margin: 0 0 3px;
    }

    .azuki-m-pile,
    .azuki-m-gate,
    .azuki-m-pass,
    .azuki-m-ikz-summary,
    .azuki-m-hand-summary,
    .azuki-m-token {
        flex: 0 0 auto;
        min-width: 42px;
        height: 48px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        overflow: hidden;
    }

    .azuki-m-pile,
    .azuki-m-gate {
        width: 40px;
        min-width: 40px;
    }

    .azuki-m-band .azuki-m-gate {
        margin-left: auto;
    }

    .azuki-m-pass {
        min-width: 70px;
    }

    .azuki-m-ikz-summary,
    .azuki-m-hand-summary {
        min-width: 0;
        max-width: 96px;
        flex: 1 1 86px;
        flex-direction: row;
        justify-content: flex-start;
        gap: 4px;
        padding: 3px 4px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 4px;
        background: rgba(18, 19, 20, 0.88);
    }

    .azuki-m-hand-summary {
        max-width: 86px;
        flex-basis: 78px;
    }

    .azuki-m-ikz-thumb {
        width: 24px;
        height: 34px;
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        overflow: hidden;
        background: rgba(8, 9, 10, 0.92);
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .azuki-m-ikz-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .azuki-m-ikz-empty {
        color: rgba(232, 220, 200, 0.46);
        font: 800 12px/1 var(--azuki-font-label);
    }

    .azuki-m-ikz-meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .azuki-m-ikz-count {
        color: rgba(232, 220, 200, 0.94);
        font: 800 11px/1 var(--azuki-font-ui);
        white-space: nowrap;
    }

    .azuki-m-hand-count {
        color: rgba(232, 220, 200, 0.94);
        font: 800 11px/1 var(--azuki-font-ui);
        white-space: nowrap;
    }

    .azuki-m-token {
        display: none;
        width: 32px;
        min-width: 32px;
        height: 30px;
        padding: 0 4px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        background: rgba(18, 19, 20, 0.94);
    }

    .azuki-m-token.has-token {
        display: inline-flex;
    }

    .azuki-m-token::before {
        content: "IKZ";
        color: rgba(244, 244, 244, 0.78);
        font: 700 8px/1 var(--azuki-font-label);
        letter-spacing: 0.06em;
    }

    .azuki-m-pile > div:not(.azuki-m-pile-label),
    .azuki-m-gate > div:not(.azuki-m-pile-label),
    .azuki-m-pass > div {
        width: 100%;
        min-width: 0;
        min-height: 36px;
        max-height: 40px;
        max-width: 100%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .azuki-m-pile > div:not(.azuki-m-pile-label),
    .azuki-m-gate > div:not(.azuki-m-pile-label) {
        border-radius: 3px;
        background: rgba(8, 9, 10, 0.58);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.09);
    }

    #myDeckWrapper,
    #theirDeckWrapper,
    #myDiscardWrapper,
    #theirDiscardWrapper,
    #myGateWrapper,
    #theirGateWrapper {
        width: 100% !important;
        height: 100% !important;
        min-width: 0 !important;
        min-height: 0 !important;
        overflow: hidden !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        scrollbar-width: none;
    }

    #myDeckWrapper::-webkit-scrollbar,
    #theirDeckWrapper::-webkit-scrollbar,
    #myDiscardWrapper::-webkit-scrollbar,
    #theirDiscardWrapper::-webkit-scrollbar,
    #myGateWrapper::-webkit-scrollbar,
    #theirGateWrapper::-webkit-scrollbar {
        width: 0;
        height: 0;
        display: none;
    }

    #myDeck,
    #theirDeck,
    #myDiscard,
    #theirDiscard,
    #myGate,
    #theirGate {
        width: 100% !important;
        height: 100% !important;
        min-width: 0 !important;
        min-height: 0 !important;
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden !important;
    }

    #myDeck > span[id],
    #theirDeck > span[id],
    #myDiscard > span[id],
    #theirDiscard > span[id],
    #myGate > span[id],
    #theirGate > span[id],
    #myDeck a,
    #theirDeck a,
    #myDiscard a,
    #theirDiscard a,
    #myGate a,
    #theirGate a {
        max-width: 100% !important;
        max-height: 100% !important;
        margin: 0 !important;
    }

    .azuki-m-pile img:not(.counter-image-icon) {
        height: 36px !important;
        max-width: 38px !important;
        width: auto !important;
        border-radius: 4px;
    }

    .azuki-m-gate img:not(.counter-image-icon) {
        height: 38px !important;
        max-width: 38px !important;
        width: auto !important;
        border-radius: 4px;
    }

    #myDeck .counter-bubble,
    #theirDeck .counter-bubble,
    #myDiscard .counter-bubble,
    #theirDiscard .counter-bubble {
        top: auto !important;
        bottom: -1px !important;
        left: 50% !important;
        width: 20px !important;
        height: 20px !important;
        border: 2px solid rgba(15, 17, 24, 0.92) !important;
        line-height: 16px !important;
        background: rgba(30, 31, 38, 0.92) !important;
        color: rgba(245, 238, 220, 0.96) !important;
        font: 800 11px/16px var(--azuki-font-ui) !important;
        text-shadow: none !important;
        transform: translateX(-50%) !important;
        -ms-transform: translateX(-50%) !important;
        box-shadow:
            0 0 0 1px rgba(212, 175, 55, 0.35),
            0 4px 8px rgba(0, 0, 0, 0.42) !important;
    }

    #turn-miasma-overlay .turn-edge-glyph {
        width: 18px;
        height: 420px;
    }

    #turn-edge-glyph-left {
        left: -7px;
    }

    #turn-edge-glyph-right {
        right: -7px;
    }

    #turn-miasma-overlay .turn-edge-glyph::before,
    #turn-miasma-overlay .turn-edge-glyph::after {
        width: 2px;
        box-shadow: 0 0 8px rgba(64, 214, 110, 0.28);
    }

    #turn-miasma-overlay .turn-edge-core {
        width: 13px;
        height: 13px;
    }

    #myLeaderHealthSlot {
        min-width: 72px;
        max-width: 84px;
        min-height: 38px;
        padding: 0;
    }

    #myLeaderHealth,
    #theirLeaderHealth {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-wrap: nowrap !important;
    }

    #myLeaderHealthWrapper,
    #theirLeaderHealthWrapper {
        width: 100%;
        height: 100%;
        overflow: visible !important;
        scrollbar-width: none;
    }

    #myLeaderHealthWrapper::-webkit-scrollbar,
    #theirLeaderHealthWrapper::-webkit-scrollbar {
        display: none;
    }

    #theirLeaderHealthSlot {
        display: none !important;
    }

    #myLeaderHealth > span:first-child,
    #theirLeaderHealth > span:first-child {
        display: none !important;
    }

    #myLeaderHealthSlot .widget-button-pass {
        width: 100%;
        box-sizing: border-box;
        min-width: 46px;
        border: 1px solid rgba(255, 255, 255, 0.34);
        border-radius: 4px;
        padding: 7px 8px;
        background: linear-gradient(180deg, rgba(52, 54, 56, 0.98), rgba(25, 26, 27, 0.98));
        color: #f4f4f4;
        font: 700 11px/1 var(--azuki-font-label);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    #myGardenSlot,
    #theirGardenSlot,
    #myAlleySlot,
    #theirAlleySlot,
    #myHandSlot,
    #theirHandSlot {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden !important;
        padding: 2px;
        border: 1px solid rgba(255, 255, 255, 0.075);
        border-radius: 4px;
        background: rgba(18, 19, 20, 0.72);
    }

    #myGardenWrapper,
    #theirGardenWrapper,
    #myAlleyWrapper,
    #theirAlleyWrapper,
    #myHandWrapper,
    #theirHandWrapper {
        width: 100%;
        height: 100%;
        min-width: 0;
        overflow: hidden !important;
    }

    #myGarden,
    #theirGarden,
    #myAlley,
    #theirAlley {
        width: 100%;
        height: 100%;
        display: flex !important;
        flex-wrap: wrap !important;
        align-content: center !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 2px;
        overflow: hidden;
    }

    #myHand,
    #theirHand {
        width: 100%;
        height: 100%;
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden;
    }

    #myHand > *,
    #theirHand > * {
        flex: 0 0 auto;
    }

    #myHand > * + * {
        margin-left: -15px !important;
    }

    #theirHand > * + * {
        margin-left: -18px !important;
    }

    .azuki-hand-collapse-btn,
    .azuki-lane-scroll-btn {
        display: none !important;
    }

    #myGarden > span:not([id]),
    #theirGarden > span:not([id]),
    #myAlley > span:not([id]),
    #theirAlley > span:not([id]),
    #myHand > span:not([id]),
    #theirHand > span:not([id]),
    #myDiscard > span:not([id]),
    #theirDiscard > span:not([id]),
    #myDeck > span:not([id]),
    #theirDeck > span:not([id]) {
        display: none !important;
    }

    #azukiMobileRoot [data-counter-field],
    #azukiMobileRoot img.counter-image-icon {
        zoom: 0.72;
    }

    #azukiMobileRoot [data-counter-field] img.counter-image-icon {
        zoom: 1;
    }

    .azuki-m-hidden-bindings {
        position: fixed;
        left: -10000px;
        top: -10000px;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }

    #myIKZAreaSlot,
    #theirIKZAreaSlot,
    #theirHandSlot,
    #theirLeaderHealthSlot,
    #myIKZPileWrapper,
    #theirIKZPileWrapper,
    #myDecisionQueueWrapper,
    #theirDecisionQueueWrapper,
    #myVersionsWrapper,
    #theirVersionsWrapper {
        width: 1px !important;
        height: 1px !important;
        min-width: 0 !important;
        min-height: 0 !important;
        overflow: hidden !important;
    }

    #myIKZToken,
    #theirIKZToken {
        display: none !important;
    }

    #EffectStackSlot {
        position: fixed;
        top: 38px;
        right: 8px;
        z-index: 12020;
        max-width: 74px;
        pointer-events: auto;
    }

    #EffectStackSlot span:not([id]) {
        display: none !important;
    }

    #azukiResponseOpportunity {
        position: fixed;
        left: 8px;
        right: 8px;
        bottom: 62px;
        z-index: 12010;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 10px;
        border: 1px solid rgba(212, 175, 55, 0.62);
        border-radius: 10px;
        background: linear-gradient(180deg, rgba(18, 29, 50, 0.98), rgba(12, 20, 36, 0.98));
        color: #f3e8d0;
        box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.34);
    }

    #azukiResponseOpportunity .azuki-opportunity-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        line-height: 1.25;
    }

    #azukiResponseOpportunity .azuki-opportunity-title {
        color: rgba(212, 175, 55, 0.98);
        font: 700 10px/1 var(--azuki-font-label);
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    #azukiResponseOpportunity .azuki-opportunity-subtitle {
        color: rgba(232, 220, 200, 0.96);
        font: 600 12px/1.3 var(--azuki-font-ui);
    }

    #azukiResponsePassBtn {
        flex: 0 0 auto;
        border: 1px solid rgba(212, 175, 55, 0.7);
        border-radius: 8px;
        padding: 8px 11px;
        background: linear-gradient(180deg, rgba(212, 175, 55, 0.28), rgba(212, 175, 55, 0.14));
        color: #f3e8d0;
        cursor: pointer;
        font: 700 11px/1 var(--azuki-font-label);
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    #regressionControls,
    #manualControls {
        display: none !important;
    }

    #grand-archive-utility-button-bar {
        display: none !important;
    }

    .azuki-admin-menu {
        position: fixed;
        top: 4px;
        right: 8px;
        z-index: 12050;
    }

    .azuki-admin-menu-btn {
        width: 32px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        border: 1px solid rgba(212, 175, 55, 0.58);
        border-radius: 8px;
        background: rgba(8, 13, 22, 0.94);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.32);
        cursor: pointer;
    }

    .azuki-admin-menu-btn span {
        width: 15px;
        height: 2px;
        border-radius: 99px;
        background: rgba(255, 244, 207, 0.94);
    }

    .azuki-admin-menu-panel {
        position: absolute;
        top: 36px;
        right: 0;
        min-width: 154px;
        display: none;
        padding: 6px;
        border: 1px solid rgba(212, 175, 55, 0.28);
        border-radius: 10px;
        background: rgba(8, 13, 22, 0.98);
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.42);
    }

    .azuki-admin-menu.is-open .azuki-admin-menu-panel {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .azuki-admin-menu-panel button {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid rgba(212, 175, 55, 0.28);
        border-radius: 7px;
        background: rgba(18, 26, 50, 0.92);
        color: rgba(255, 244, 207, 0.96);
        font: 800 11px/1 var(--azuki-font-label);
        text-align: left;
        cursor: pointer;
    }

    #chatWidget,
    #bug-report-button,
    #copy-spectate-link-button,
    #concede-button {
        z-index: 12000 !important;
    }

    #chatWidget {
        position: fixed !important;
        top: 4px !important;
        left: 112px !important;
        bottom: auto !important;
        width: auto !important;
        max-width: calc(100vw - 156px) !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: flex-start !important;
    }

    #chatToggleBtn {
        margin-top: 0 !important;
        height: 28px !important;
    }

    #macro-card-toast-host {
        top: 4px !important;
        left: 8px !important;
        z-index: 12000 !important;
    }

    #macro-card-toast-toggle {
        height: 30px !important;
    }

    #macro-card-toast-log {
        max-height: 48vh !important;
    }

    /*
     * Landscape phone board
     *
     * The reference layout reads like a tabletop: player information sits on
     * the outside rails, while each battlefield zone gets a full horizontal
     * lane.  display:contents lets us reuse the portrait markup and, more
     * importantly, every generated BindTo slot ID.
     */
    @media (orientation: landscape) and (max-height: 600px) {
        :root {
            --azuki-l-card: clamp(56px, 18.5vh, 78px);
            --azuki-l-hand-card: clamp(72px, 23vh, 92px);
            --azuki-l-rail: clamp(150px, 19vw, 200px);
            --azuki-l-hand: clamp(126px, 17vw, 180px);
        }

        #azukiMobileRoot {
            padding:
                max(6px, env(safe-area-inset-top))
                max(6px, env(safe-area-inset-right))
                max(6px, env(safe-area-inset-bottom))
                max(6px, env(safe-area-inset-left));
            grid-template-columns: var(--azuki-l-rail) minmax(0, 1fr) var(--azuki-l-hand);
            grid-template-rows: repeat(4, minmax(0, 1fr));
            gap: 5px;
            background:
                linear-gradient(90deg, #0a0b0c 0 var(--azuki-l-rail), transparent var(--azuki-l-rail)),
                radial-gradient(circle at 52% 48%, rgba(255, 255, 255, 0.026), transparent 46%),
                #0d0e0f;
        }

        .azuki-m-band,
        .azuki-m-section,
        .azuki-m-lanes {
            border: 0;
        }

        .azuki-m-band {
            position: relative;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            grid-auto-rows: minmax(48px, 1fr);
            align-content: center;
            gap: 4px;
            padding: 18px 5px 5px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.075);
            border-radius: 3px;
            background: #0b0c0d;
        }

        .azuki-m-band::before {
            position: absolute;
            top: 7px;
            left: 7px;
            right: 7px;
            color: rgba(255, 255, 255, 0.74);
            font: 800 7px/1 var(--azuki-font-label);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .azuki-m-band.is-theirs {
            grid-column: 1;
            grid-row: 1 / span 2;
        }

        .azuki-m-band.is-theirs::before {
            content: "Opponent";
        }

        .azuki-m-band.is-mine {
            grid-column: 1;
            grid-row: 3 / span 2;
            overflow: visible;
        }

        .azuki-m-band.is-mine::before {
            content: "You";
        }

        .azuki-m-band .azuki-m-gate {
            margin-left: 0;
            grid-column: 3;
            grid-row: 1;
        }

        .azuki-m-band.is-mine .azuki-m-pass {
            position: fixed;
            top: 50%;
            right: max(20px, env(safe-area-inset-right));
            z-index: 12005;
            width: 100px;
            min-width: 100px;
            height: 60px;
            min-height: 60px;
            transform: translateY(-50%);
            overflow: visible !important;
        }

        .azuki-m-band.is-mine .azuki-m-pass #myLeaderHealthSlot,
        .azuki-m-band.is-mine .azuki-m-pass #myLeaderHealth {
            width: 100px !important;
            min-width: 100px !important;
            max-width: 100px !important;
            height: 60px !important;
            min-height: 60px !important;
            max-height: none !important;
            box-sizing: border-box;
            overflow: visible !important;
        }

        .azuki-m-band.is-mine .azuki-m-pass #myLeaderHealth {
            display: flex !important;
            align-items: center;
            justify-content: center;
            font-size: 0;
        }

        .azuki-m-band.is-mine .azuki-m-pass #myLeaderHealthWrapper {
            overflow: visible !important;
        }

        .azuki-m-band.is-mine .azuki-m-pass .widget-button-pass {
            width: 84px !important;
            min-width: 84px !important;
            max-width: 84px !important;
            height: 44px !important;
            min-height: 44px !important;
            max-height: 44px !important;
            box-sizing: border-box !important;
            flex: 0 0 84px;
            margin: 0 !important;
            border-radius: 7px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .azuki-m-band .azuki-m-pile.is-deck {
            grid-column: 1;
            grid-row: 2;
        }

        .azuki-m-band .azuki-m-pile.is-discard {
            grid-column: 2;
            grid-row: 2;
        }

        .azuki-m-band .azuki-m-token {
            grid-column: 3;
            grid-row: 2;
        }

        .azuki-m-pile,
        .azuki-m-gate,
        .azuki-m-pass,
        .azuki-m-ikz-summary,
        .azuki-m-hand-summary,
        .azuki-m-token {
            width: 100%;
            min-width: 0;
            max-width: none;
            height: auto;
            min-height: 44px;
        }

        .azuki-m-ikz-summary,
        .azuki-m-hand-summary {
            grid-column: span 2;
            padding: 3px;
        }

        .azuki-m-band .azuki-m-ikz-summary {
            grid-column: 1 / span 2;
            grid-row: 1;
        }

        .azuki-m-band .azuki-m-ikz-meta {
            overflow: hidden;
        }

        .azuki-m-band .azuki-m-ikz-count {
            font-size: 9px;
        }

        .azuki-m-band.is-mine #myLeaderHealthSlot {
            width: 100%;
            min-width: 0;
            max-width: none;
        }

        #theirHandSummary {
            position: fixed;
            top: max(6px, env(safe-area-inset-top));
            right: max(6px, env(safe-area-inset-right));
            z-index: 44;
            width: calc(var(--azuki-l-hand) - 12px);
            height: 54px;
            min-height: 54px;
            max-width: none;
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(11, 12, 13, 0.96);
        }

        .azuki-m-lanes {
            display: contents;
        }

        .azuki-m-lane {
            position: relative;
            grid-column: 2;
            display: block;
            min-width: 0;
            min-height: 0;
            padding: 11px 3px 3px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.055);
            border-radius: 3px;
            background: #121314;
        }

        .azuki-m-lanes.is-theirs > .azuki-m-lane:nth-child(2) { grid-row: 1; }
        .azuki-m-lanes.is-theirs > .azuki-m-lane:nth-child(1) { grid-row: 2; }
        .azuki-m-lanes.is-mine > .azuki-m-lane:nth-child(1) { grid-row: 3; }
        .azuki-m-lanes.is-mine > .azuki-m-lane:nth-child(2) { grid-row: 4; }

        .azuki-m-label {
            position: absolute;
            top: 4px;
            left: 6px;
            z-index: 2;
            margin: 0;
            color: rgba(255, 255, 255, 0.38);
            font-size: 6px;
        }

        #myGardenSlot,
        #theirGardenSlot,
        #myAlleySlot,
        #theirAlleySlot {
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        #myGarden,
        #theirGarden,
        #myAlley,
        #theirAlley {
            flex-wrap: nowrap !important;
            align-content: center !important;
            justify-content: flex-start !important;
            gap: 4px;
            padding-left: 3px;
        }

        #myGarden > span[id] > a > img,
        #theirGarden > span[id] > a > img,
        #myAlley > span[id] > a > img,
        #theirAlley > span[id] > a > img {
            width: var(--azuki-l-card) !important;
            height: var(--azuki-l-card) !important;
        }

        .azuki-m-section.is-mine {
            position: fixed;
            left: calc(var(--azuki-l-rail) + 12px);
            right: max(10px, env(safe-area-inset-right));
            bottom: -24px;
            z-index: 48;
            width: auto;
            height: calc(var(--azuki-l-hand-card) + 38px);
            padding: 14px 4px 0;
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: transparent;
            pointer-events: none;
            transform: translateY(0);
            transition: transform 220ms cubic-bezier(0.32, 0.72, 0, 1);
            will-change: transform;
        }

        .azuki-m-section.is-mine.azuki-hand-away {
            transform: translateY(calc(100% - 84px));
        }

        .azuki-m-section.is-mine > .azuki-m-label {
            top: 5px;
        }

        #myHandSlot {
            width: 100%;
            height: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            overflow: visible !important;
            pointer-events: auto;
        }

        #myHandWrapper {
            width: 100%;
            height: 100%;
            overflow: visible !important;
        }

        #myHand {
            height: 100%;
            align-items: flex-end !important;
            justify-content: center !important;
            overflow: visible !important;
            padding-bottom: 2px;
        }

        #myHand > span[id] {
            position: relative;
            flex: 0 0 auto;
            margin-left: -10px !important;
            transform-origin: 50% 100%;
            transform: translateY(var(--azuki-hand-drop, 0px)) rotate(var(--azuki-hand-angle, 0deg));
            transition: transform 130ms ease, filter 130ms ease;
        }

        #myHand:not(.azuki-hand-fan-ready) > span[id] {
            transition: none !important;
        }

        #myHand > span[id]:first-child {
            margin-left: 0 !important;
        }

        #myHand > span[id]:hover,
        #myHand > span[id]:focus-within {
            z-index: 90 !important;
            transform: translateY(-24px) rotate(0deg) scale(1.08);
            filter: drop-shadow(0 9px 10px rgba(0, 0, 0, 0.72));
        }

        #myHand > span[id] > a > img {
            width: var(--azuki-l-hand-card) !important;
            height: var(--azuki-l-hand-card) !important;
        }

        #theirLeaderHealth,
        #myLeaderHealth {
            color: #f7f7f7;
            font-weight: 900;
        }

        #EffectStackSlot {
            top: calc(50% - 28px);
            right: calc(var(--azuki-l-hand) + 10px);
        }

        #azukiResponseOpportunity {
            left: calc(var(--azuki-l-rail) + 12px);
            right: calc(var(--azuki-l-hand) + 12px);
            bottom: 8px;
            padding: 6px 8px;
            border-radius: 4px;
            border-color: rgba(255, 255, 255, 0.34);
            background: rgba(11, 12, 13, 0.98);
        }

        .azuki-admin-menu {
            top: max(6px, env(safe-area-inset-top));
            right: calc(var(--azuki-l-hand) + 10px);
        }

        #chatWidget {
            top: auto !important;
            left: auto !important;
            right: max(8px, env(safe-area-inset-right)) !important;
            bottom: calc(var(--azuki-l-hand-card) + 8px) !important;
            max-width: 220px !important;
        }

        #chatExpanded[style*="display: flex"] + #chatToggleBtn {
            position: absolute !important;
            top: 0 !important;
            right: 0 !important;
            margin: 0 !important;
            z-index: 1;
        }

        /*
         * Landscape status rail: selection prompts, opponent-waiting text,
         * and response opportunities share one compact row beneath Events.
         * Multi-select prompts retain their larger interactive presentation.
         */
        #selection-message:not(:has(#inline-multi-confirm)),
        #turn-miasma-message,
        #azukiResponseOpportunity {
            position: fixed !important;
            top: max(50px, calc(env(safe-area-inset-top) + 44px)) !important;
            left: calc(var(--azuki-l-rail) + 10px) !important;
            right: calc(var(--azuki-l-hand) + 10px) !important;
            bottom: auto !important;
            width: auto !important;
            max-width: none !important;
            min-height: 38px;
            box-sizing: border-box !important;
            transform: none !important;
            margin: 0 !important;
            padding: 6px 10px !important;
            gap: 7px !important;
            align-items: center !important;
            justify-content: center !important;
            flex-wrap: nowrap !important;
            overflow: hidden;
            border: 1px solid rgba(212, 175, 55, 0.42) !important;
            border-radius: 8px !important;
            background: rgba(11, 12, 13, 0.96) !important;
            color: #f4ead0 !important;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.34) !important;
            font: 700 10px/1.15 var(--azuki-font-ui) !important;
            text-align: center;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        #selection-message:not(:has(#inline-multi-confirm)) {
            z-index: 12030 !important;
        }

        #azukiResponseOpportunity {
            z-index: 12020 !important;
        }

        /* Only the highest-priority status occupies the shared row. */
        body:has(#selection-message[style*="display: flex"]) #azukiResponseOpportunity,
        body:has(#selection-message[style*="display: flex"]) #turn-miasma-message,
        body:has(#azukiResponseOpportunity[style*="display: flex"]) #turn-miasma-message {
            display: none !important;
        }

        #selection-message:not(:has(#inline-multi-confirm)) > span:first-child {
            min-width: 0 !important;
            overflow: hidden;
            white-space: nowrap !important;
            text-overflow: ellipsis;
        }

        #selection-message:not(:has(#inline-multi-confirm)) > button {
            flex: 0 0 auto !important;
            margin: 0 !important;
            padding: 5px 8px !important;
            border-radius: 6px !important;
            font-size: 9px !important;
        }

        #azukiResponseOpportunity .azuki-opportunity-text {
            min-width: 0;
            flex: 0 1 auto;
            flex-direction: row;
            align-items: center;
            gap: 6px;
            overflow: hidden;
        }

        #azukiResponseOpportunity .azuki-opportunity-title {
            flex: 0 0 auto;
            font-size: 8px;
            letter-spacing: 0.08em;
        }

        #azukiResponseOpportunity .azuki-opportunity-subtitle {
            min-width: 0;
            overflow: hidden;
            font-size: 10px;
            line-height: 1.15;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        #azukiResponsePassBtn {
            margin: 0;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 9px;
        }

        #macro-card-toast-host {
            top: 6px !important;
            left: calc(var(--azuki-l-rail) + 10px) !important;
            right: calc(var(--azuki-l-hand) + 50px) !important;
            width: auto !important;
            flex-direction: row !important;
            align-items: flex-start !important;
        }

        #macro-card-toast-host > .macro-card-toast {
            flex: 0 1 260px;
            min-width: 178px;
        }

        #macro-card-toast-host > .macro-card-toast.azuki-landscape-compact-toast {
            flex: 0 0 auto;
            min-width: 0;
            max-width: 132px;
            min-height: 0;
            gap: 5px;
            padding: 3px 5px 3px 3px;
            cursor: help;
        }

        .macro-card-toast.azuki-landscape-compact-toast img,
        .macro-card-toast-log-entry.azuki-landscape-compact-log-entry img {
            width: 45px;
            height: 30px;
            box-sizing: border-box;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 4px;
        }

        .macro-card-toast.azuki-landscape-compact-toast.azuki-toast-mine img,
        .macro-card-toast-log-entry.azuki-landscape-compact-log-entry.azuki-toast-mine img {
            border-color: #42df7b;
            box-shadow: 0 0 9px rgba(66, 223, 123, 0.34);
        }

        .macro-card-toast.azuki-landscape-compact-toast.azuki-toast-opponent img,
        .macro-card-toast-log-entry.azuki-landscape-compact-log-entry.azuki-toast-opponent img {
            border-color: #f05a62;
            box-shadow: 0 0 9px rgba(240, 90, 98, 0.34);
        }

        .macro-card-toast.azuki-landscape-compact-toast .macro-card-toast-name,
        .macro-card-toast-log-entry.azuki-landscape-compact-log-entry .macro-card-toast-name {
            display: none;
        }

        .macro-card-toast.azuki-landscape-compact-toast .macro-card-toast-title,
        .macro-card-toast-log-entry.azuki-landscape-compact-log-entry .macro-card-toast-title {
            margin-bottom: 2px;
            font-size: 7px;
        }

        .macro-card-toast.azuki-landscape-compact-toast .macro-card-toast-meta,
        .macro-card-toast-log-entry.azuki-landscape-compact-log-entry .macro-card-toast-meta {
            margin-top: 0;
            color: rgba(244, 244, 244, 0.82);
            font-size: 7px;
            text-transform: uppercase;
        }

        #macro-card-toast-host.is-expanded #macro-card-toast-toggle {
            flex: 0 0 auto;
        }

        #macro-card-toast-host.is-expanded #macro-card-toast-log {
            flex: 1 1 auto;
            min-width: 0;
            width: auto !important;
            max-height: 52px !important;
            box-sizing: border-box;
            flex-direction: row !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow-x: hidden;
            overflow-y: hidden;
            scrollbar-width: none;
            -webkit-mask-image: linear-gradient(to right, #000 0, #000 calc(100% - 32px), transparent 100%);
            mask-image: linear-gradient(to right, #000 0, #000 calc(100% - 32px), transparent 100%);
        }

        #macro-card-toast-host.is-expanded #macro-card-toast-log::-webkit-scrollbar {
            display: none;
        }

        #macro-card-toast-host.is-expanded .macro-card-toast-log-entry.azuki-landscape-compact-log-entry {
            flex: 0 0 119px;
            min-width: 119px;
            max-width: 119px;
            min-height: 0;
            box-sizing: border-box;
            gap: 5px;
            padding: 3px 5px 3px 3px;
        }

        #macro-card-toast-host.is-expanded > .macro-card-toast {
            display: none !important;
        }

        /* Compact end-game presentation for short landscape viewports. */
        body:has(#game-over-overlay.active) #macro-card-toast-host {
            display: none !important;
        }

        #game-over-overlay {
            padding: 8px 12px 10px !important;
            overflow: hidden !important;
        }

        #game-over-title {
            flex: 0 0 auto;
            margin-top: 50px !important;
            font-size: clamp(30px, 10vh, 48px) !important;
            line-height: 0.95 !important;
            letter-spacing: 4px !important;
        }

        #game-over-menu-btn {
            top: 8px !important;
            right: 10px !important;
            max-width: 132px;
            padding: 7px 10px !important;
            border-radius: 8px !important;
            font-size: 10px !important;
            line-height: 1 !important;
            letter-spacing: 1px !important;
            white-space: nowrap;
        }

        #game-over-overlay > .match-replay-stats-actions {
            position: absolute;
            top: 8px;
            left: 50%;
            right: auto;
            width: fit-content;
            max-width: calc(100vw - 174px);
            transform: translateX(-50%);
            min-height: 34px;
            margin: 0 !important;
            padding: 4px 6px !important;
            justify-content: flex-start !important;
            flex-wrap: nowrap !important;
            gap: 6px !important;
        }

        #game-over-overlay > .match-replay-stats-actions .match-replay-muted {
            min-width: 0;
            overflow: hidden;
            font-size: 10px;
            line-height: 1.15;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        #match-replay-game-over-save-btn {
            flex: 0 0 auto;
            padding: 6px 9px !important;
            font-size: 10px !important;
            line-height: 1 !important;
            white-space: nowrap;
        }

        #game-over-stats {
            flex: 1 1 auto;
            width: calc(100vw - 24px) !important;
            max-height: calc(100dvh - 112px) !important;
            margin: 6px auto 0 !important;
            padding: 8px 10px !important;
            border-radius: 9px !important;
            font-size: 12px !important;
            line-height: 1.2 !important;
            overscroll-behavior: contain;
        }

        #game-over-stats > div:first-child {
            margin-bottom: 6px !important;
        }

        #game-over-stats > div:first-child > div {
            font-size: 15px !important;
        }

        #game-over-stats .macro-game-stats-grid {
            gap: 8px !important;
        }

        #game-over-stats section {
            padding: 8px !important;
            border-radius: 10px !important;
        }

        #game-over-stats [data-macro-game-chart] {
            padding: 5px !important;
            border-radius: 9px !important;
        }

        #game-over-stats [data-macro-game-chart] > div {
            height: 132px !important;
        }
    }
</style>

<div id="azukiMobileRoot">
    <div id="azukiAdminMenu" class="azuki-admin-menu">
        <button id="azukiAdminMenuBtn" class="azuki-admin-menu-btn" type="button" aria-label="Game menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <div id="azukiAdminMenuPanel" class="azuki-admin-menu-panel">
            <button type="button" data-azuki-mobile-action="undo" data-azuki-mobile-player-action="true">Undo</button>
            <button type="button" data-azuki-admin-target="bug-report-button">Report Bug</button>
            <button type="button" data-azuki-admin-target="copy-spectate-link-button">Copy Spectate Link</button>
            <button type="button" data-azuki-admin-target="concede-button">Concede</button>
        </div>
    </div>

    <div id="azukiResponseOpportunity" aria-live="polite" aria-label="Response opportunity">
        <div class="azuki-opportunity-text">
            <span class="azuki-opportunity-title">Response Opportunity</span>
            <span id="azukiResponseOpportunityText" class="azuki-opportunity-subtitle">You may play a [Response] card.</span>
        </div>
        <button id="azukiResponsePassBtn" type="button" onclick="AzukiResponsePass()">Pass</button>
    </div>

    <div class="azuki-m-band is-theirs">
        <div id="theirIKZSummary" class="azuki-m-ikz-summary" aria-label="Their IKZ summary"></div>
        <div id="theirHandSummary" class="azuki-m-hand-summary" aria-label="Their hand summary"></div>
        <div id="theirIKZTokenSlot" class="azuki-zone azuki-m-token" data-label="IKZ Token"></div>
        <div class="azuki-m-gate"><div class="azuki-m-pile-label">Gate</div><div id="theirGateSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile is-deck"><div class="azuki-m-pile-label">Deck</div><div id="theirDeckSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile is-discard"><div class="azuki-m-pile-label">Discard</div><div id="theirDiscardSlot" class="azuki-zone"></div></div>
    </div>

    <div class="azuki-m-lanes is-theirs">
        <div class="azuki-m-lane">
            <div class="azuki-m-label">Their Garden</div>
            <div id="theirGardenSlot" class="azuki-zone"></div>
        </div>
        <div class="azuki-m-lane">
            <div class="azuki-m-label">Their Alley</div>
            <div id="theirAlleySlot" class="azuki-zone"></div>
        </div>
    </div>

    <div class="azuki-m-lanes is-mine">
        <div class="azuki-m-lane">
            <div class="azuki-m-label">My Garden</div>
            <div id="myGardenSlot" class="azuki-zone"></div>
        </div>
        <div class="azuki-m-lane">
            <div class="azuki-m-label">My Alley</div>
            <div id="myAlleySlot" class="azuki-zone"></div>
        </div>
    </div>

    <div class="azuki-m-section is-mine">
        <div id="myHandSlot" class="azuki-zone"></div>
    </div>

    <div class="azuki-m-band is-mine">
        <div id="myIKZSummary" class="azuki-m-ikz-summary" aria-label="My IKZ summary"></div>
        <div id="myIKZTokenSlot" class="azuki-zone azuki-m-token" data-label="IKZ Token"></div>
        <div class="azuki-m-pass"><div id="myLeaderHealthSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-gate"><div class="azuki-m-pile-label">Gate</div><div id="myGateSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile is-deck"><div class="azuki-m-pile-label">Deck</div><div id="myDeckSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile is-discard"><div class="azuki-m-pile-label">Discard</div><div id="myDiscardSlot" class="azuki-zone"></div></div>
    </div>

    <div id="EffectStackSlot" class="azuki-zone"></div>

    <div class="azuki-m-hidden-bindings" aria-hidden="true">
        <div id="theirHandSlot" class="azuki-zone"></div>
        <div id="theirIKZAreaSlot" class="azuki-zone"></div>
        <div id="myIKZAreaSlot" class="azuki-zone"></div>
        <div id="theirLeaderHealthSlot" class="azuki-zone"></div>
    </div>
</div>

<script>
(function() {
    function getViewerPlayer() {
        var el = document.getElementById('playerID');
        if(el && el.value !== '') return parseInt(el.value, 10);
        if(typeof window.currentPlayerIndex !== 'undefined') return parseInt(window.currentPlayerIndex, 10);
        return 0;
    }

    function parseDecisionVars() {
        var raw = window.DecisionQueueVariablesData;
        if(!raw || typeof raw !== 'string') return {};
        try {
            var parsed = JSON.parse(raw);
            return (parsed && typeof parsed === 'object') ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function responseState() {
        var vars = parseDecisionVars();
        var attackerMZ = typeof vars.PendingAttackAttackerMZ === 'string' ? vars.PendingAttackAttackerMZ : '';
        var targetMZ = typeof vars.PendingAttackTargetMZ === 'string' ? vars.PendingAttackTargetMZ : '';
        var attacker = parseInt(vars.PendingAttackAttackerPlayer, 10);
        if(isNaN(attacker) || (attacker !== 1 && attacker !== 2)) {
            attacker = parseInt(window.TurnPlayerData, 10);
        }
        var responder = attacker === 1 ? 2 : (attacker === 2 ? 1 : 0);
        return {
            active: attackerMZ !== '' && targetMZ !== '',
            responder: responder
        };
    }

    window.AzukiResponsePass = function() {
        SubmitInput('10001', '&cardID=' + encodeURIComponent('myLeaderHealthSlot!CustomInput!Pass'));
    };

    window.UpdateAzukiResponseOpportunity = function() {
        var panel = document.getElementById('azukiResponseOpportunity');
        var subtitle = document.getElementById('azukiResponseOpportunityText');
        var passBtn = document.getElementById('azukiResponsePassBtn');
        if(!panel || !subtitle || !passBtn) return;

        var state = responseState();
        if(!state.active) {
            panel.style.display = 'none';
            return;
        }

        var viewer = getViewerPlayer();
        var isResponder = viewer > 0 && viewer === state.responder;
        subtitle.textContent = isResponder
            ? 'Play a [Response] card or pass to resolve the attack.'
            : 'Waiting for defending player responses.';
        passBtn.style.display = isResponder ? 'inline-flex' : 'none';
        panel.style.display = 'flex';
    };

    function installResponseWatcher() {
        var initial = window.DecisionQueueVariablesData;
        var currentValue = (typeof initial === 'undefined') ? '' : initial;

        try {
            var existing = Object.getOwnPropertyDescriptor(window, 'DecisionQueueVariablesData');
            if(!existing || existing.configurable) {
                Object.defineProperty(window, 'DecisionQueueVariablesData', {
                    configurable: true,
                    enumerable: true,
                    get: function() {
                        return currentValue;
                    },
                    set: function(nextValue) {
                        currentValue = nextValue;
                        if(typeof window.UpdateAzukiResponseOpportunity === 'function') {
                            window.UpdateAzukiResponseOpportunity();
                        }
                    }
                });
            }
        } catch (e) {
            setInterval(function() {
                if(typeof window.UpdateAzukiResponseOpportunity === 'function') {
                    window.UpdateAzukiResponseOpportunity();
                }
            }, 250);
        }
    }

    function readTokenValue(dataKey, zoneId) {
        var raw = window[dataKey];
        if(typeof raw === 'undefined' || raw === null || raw === '') {
            var zone = document.getElementById(zoneId);
            raw = zone ? (zone.textContent || '').trim() : '';
        }
        var text = String(raw).trim();
        var parsed = parseInt(text, 10);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function setupIKZTokenIndicator() {
        function syncTokenSlot(slotId, dataKey, zoneId) {
            var slot = document.getElementById(slotId);
            if(!slot) return;
            var hasToken = readTokenValue(dataKey, zoneId) > 0;
            slot.classList.toggle('has-token', hasToken);
            slot.style.display = hasToken ? 'inline-flex' : 'none';
        }

        function update() {
            syncTokenSlot('myIKZTokenSlot', 'myIKZTokenData', 'myIKZToken');
            syncTokenSlot('theirIKZTokenSlot', 'theirIKZTokenData', 'theirIKZToken');
        }

        window.UpdateAzukiMobileIKZTokens = update;
        update();
    }

    function parseIKZEntries(dataKey) {
        var raw = window[dataKey];
        if(!raw || typeof raw !== 'string') return [];
        return raw.split('<|>').map(function(entry) {
            entry = entry.trim();
            if(entry === '') return null;
            var parts = entry.split(' ');
            var cardID = parts[0] || '';
            if(cardID === '' || cardID === '-') return null;
            var status = parseInt(parts[1], 10);
            var obj = null;
            if(parts.length > 2 && parts[2] && parts[2] !== '-') {
                try { obj = JSON.parse(parts.slice(2).join(' ')); } catch (e) {}
            }
            if(obj && obj.Status != null) {
                var parsedStatus = parseInt(obj.Status, 10);
                if(Number.isFinite(parsedStatus)) status = parsedStatus;
            }
            if(!Number.isFinite(status)) status = 2;
            return {
                cardID: cardID,
                ready: status !== 1
            };
        }).filter(Boolean);
    }

    function parseZoneCount(dataKey) {
        var raw = window[dataKey];
        if(!raw || typeof raw !== 'string') return 0;
        var trimmed = raw.trim();
        if(trimmed === '') return 0;
        return trimmed.split('<|>').filter(function(entry) {
            entry = entry.trim();
            if(entry === '' || entry === '-') return false;
            var cardID = entry.split(' ')[0] || '';
            return cardID !== '' && cardID !== '-';
        }).length;
    }

    function renderIKZSummary(prefix, label) {
        var summary = document.getElementById(prefix + 'IKZSummary');
        if(!summary) return;

        var entries = parseIKZEntries(prefix + 'IKZAreaData');
        var total = entries.length;
        var ready = entries.filter(function(entry) { return entry.ready; }).length;
        var preview = entries[total - 1] || entries[0] || null;

        var thumbHTML = preview
            ? '<img alt="" src="./AzukiSim/concat/' + encodeURIComponent(preview.cardID) + '.webp">'
            : '<span class="azuki-m-ikz-empty">IKZ</span>';
        summary.innerHTML =
            '<div class="azuki-m-ikz-thumb">' + thumbHTML + '</div>' +
            '<div class="azuki-m-ikz-meta">' +
                '<div class="azuki-m-pile-label">' + label + '</div>' +
                '<div class="azuki-m-ikz-count">' + ready + '/' + total + ' ready</div>' +
            '</div>';
    }

    function renderOpponentHandSummary() {
        var summary = document.getElementById('theirHandSummary');
        if(!summary) return;
        var count = parseZoneCount('theirHandData');
        summary.innerHTML =
            '<div class="azuki-m-ikz-thumb"><img alt="" src="./AzukiSim/concat/CardBack.webp"></div>' +
            '<div class="azuki-m-ikz-meta">' +
                '<div class="azuki-m-pile-label">Their Hand</div>' +
                '<div class="azuki-m-hand-count">' + count + ' cards</div>' +
            '</div>';
    }

    function updateIKZSummaries() {
        renderIKZSummary('their', 'Their IKZ');
        renderIKZSummary('my', 'My IKZ');
        if(typeof window.UpdateAzukiMobileIKZTokens === 'function') {
            window.UpdateAzukiMobileIKZTokens();
        }
    }

    function observeZone(zoneId, callback) {
        var zone = document.getElementById(zoneId);
        if(!zone || !window.MutationObserver) return;
        new MutationObserver(callback).observe(zone, { childList: true, subtree: true, characterData: true });
    }

    function updateLandscapeHandFan() {
        var hand = document.getElementById('myHand');
        if(!hand) return;

        hand.classList.remove('azuki-hand-fan-ready');
        var cards = Array.prototype.filter.call(hand.children, function(child) {
            return child && child.id;
        });
        var center = (cards.length - 1) / 2;

        cards.forEach(function(card, index) {
            var distance = index - center;
            var angle = Math.max(-10, Math.min(10, distance * 2.4));
            var drop = Math.abs(distance) * 3.25;
            card.style.setProperty('--azuki-hand-angle', angle.toFixed(2) + 'deg');
            card.style.setProperty('--azuki-hand-drop', drop.toFixed(2) + 'px');
            card.style.zIndex = String(70 - Math.round(Math.abs(distance) * 2));
        });

        // BindTo replaces the hand wholesale. Commit the final fan geometry
        // while transitions are disabled, then restore hover motion only after
        // that state has been laid out so an unfanned frame cannot animate in.
        void hand.offsetWidth;
        hand.classList.add('azuki-hand-fan-ready');
    }

    function updateLandscapeHandVisibility() {
        var section = document.querySelector('.azuki-m-section.is-mine');
        if(!section) return;

        var selection = window.SelectionMode;
        var specs = [];
        if(selection && selection.active) {
            ['allowedZones', 'allowedDecisionZones', 'inlineSpecs', 'popupCards'].forEach(function(key) {
                if(Array.isArray(selection[key])) specs = specs.concat(selection[key]);
            });
        }

        var targetsHand = specs.some(function(spec) {
            var zone = typeof spec === 'string' ? spec : (spec && spec.zone);
            return typeof zone === 'string' && (zone === 'myHand' || zone.indexOf('myHand-') === 0);
        });
        var selectingAwayFromHand = !!(selection && selection.active && specs.length > 0 && !targetsHand);
        section.classList.toggle('azuki-hand-away', selectingAwayFromHand);
    }

    function setupLandscapeToastCompaction() {
        function isLandscapeSideMode() {
            return !!(window.matchMedia && window.matchMedia('(orientation: landscape) and (max-height: 600px)').matches);
        }

        function compactEventCard(eventCard) {
            if(!eventCard || eventCard.classList.contains('azuki-landscape-compact-event') || !isLandscapeSideMode()) return;

            var image = eventCard.querySelector('img');
            if(image) {
                var originalSrc = image.src;
                try {
                    var filename = decodeURIComponent(new URL(originalSrc, window.location.href).pathname.split('/').pop() || '');
                    var cardID = filename.replace(/\.webp$/i, '');
                    if(cardID) {
                        image.onerror = function() {
                            image.onerror = null;
                            image.src = originalSrc;
                        };
                        image.src = './AzukiSim/crops/' + encodeURIComponent(cardID) + '_cropped.png';
                    }
                } catch(e) {}
            }

            var meta = eventCard.querySelector('.macro-card-toast-meta');
            var player = 0;
            if(meta) {
                var match = (meta.textContent || '').match(/Player\s+(\d+)(.*)$/i);
                if(match) {
                    player = parseInt(match[1], 10) || 0;
                    meta.textContent = 'P' + match[1] + (match[2] || '');
                }
            }

            var viewer = getViewerPlayer();
            if(viewer === 1 || viewer === 2) {
                eventCard.classList.add(player === viewer ? 'azuki-toast-mine' : 'azuki-toast-opponent');
            }
            eventCard.classList.add('azuki-landscape-compact-event');
            eventCard.classList.add(eventCard.classList.contains('macro-card-toast-log-entry')
                ? 'azuki-landscape-compact-log-entry'
                : 'azuki-landscape-compact-toast');
        }

        function compactAddedNode(node) {
            if(!node || node.nodeType !== 1) return;
            if(node.matches && node.matches('.macro-card-toast, .macro-card-toast-log-entry')) compactEventCard(node);
            if(node.querySelectorAll) {
                Array.prototype.forEach.call(node.querySelectorAll('.macro-card-toast, .macro-card-toast-log-entry'), compactEventCard);
            }
        }

        Array.prototype.forEach.call(document.querySelectorAll('.macro-card-toast, .macro-card-toast-log-entry'), compactEventCard);
        if(!window.MutationObserver || !document.body) return;
        new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], compactAddedNode);
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    function setupAdminMenu() {
        var menu = document.getElementById('azukiAdminMenu');
        var btn = document.getElementById('azukiAdminMenuBtn');
        var panel = document.getElementById('azukiAdminMenuPanel');
        if(!menu || !btn || !panel) return;

        function setOpen(open) {
            menu.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function updateAvailability() {
            var actions = panel.querySelectorAll('[data-azuki-admin-target]');
            for(var i = 0; i < actions.length; ++i) {
                var action = actions[i];
                var targetID = action.getAttribute('data-azuki-admin-target');
                action.style.display = document.getElementById(targetID) ? 'block' : 'none';
            }
            var playerActions = panel.querySelectorAll('[data-azuki-mobile-player-action]');
            var hidePlayerActions = (typeof IsSpectatorClient === 'function' && IsSpectatorClient());
            for(var j = 0; j < playerActions.length; ++j) {
                playerActions[j].style.display = hidePlayerActions ? 'none' : 'block';
            }
        }

        btn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            updateAvailability();
            setOpen(!menu.classList.contains('is-open'));
        });

        panel.addEventListener('click', function(event) {
            var action = event.target && event.target.closest ? event.target.closest('[data-azuki-admin-target]') : null;
            if(action) {
                event.preventDefault();
                event.stopPropagation();
                var target = document.getElementById(action.getAttribute('data-azuki-admin-target'));
                if(target && typeof target.click === 'function') target.click();
                setOpen(false);
                return;
            }

            var mobileAction = event.target && event.target.closest ? event.target.closest('[data-azuki-mobile-action]') : null;
            if(!mobileAction) return;
            event.preventDefault();
            event.stopPropagation();
            if(mobileAction.getAttribute('data-azuki-mobile-action') === 'undo' && typeof SubmitInput === 'function') {
                SubmitInput(10004, '');
            }
            setOpen(false);
        });

        document.addEventListener('click', function(event) {
            if(!menu.contains(event.target)) setOpen(false);
        });

        updateAvailability();
        window.setInterval(updateAvailability, 700);
    }

    installResponseWatcher();
    setupAdminMenu();
    setupIKZTokenIndicator();
    setupLandscapeToastCompaction();
    updateIKZSummaries();
    renderOpponentHandSummary();
    updateLandscapeHandFan();
    updateLandscapeHandVisibility();
    observeZone('theirHandSlot', renderOpponentHandSummary);
    observeZone('myHandSlot', updateLandscapeHandFan);
    observeZone('myIKZAreaSlot', updateIKZSummaries);
    observeZone('theirIKZAreaSlot', updateIKZSummaries);
    observeZone('myIKZTokenSlot', window.UpdateAzukiMobileIKZTokens);
    observeZone('theirIKZTokenSlot', window.UpdateAzukiMobileIKZTokens);
    window.setInterval(function() {
        updateIKZSummaries();
        renderOpponentHandSummary();
    }, 400);
    window.setInterval(updateLandscapeHandVisibility, 100);
    window.UpdateAzukiResponseOpportunity();
})();
</script>
