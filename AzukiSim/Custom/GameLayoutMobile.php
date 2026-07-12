<?php
// GameLayoutMobile.php - phone layout for AzukiSim.
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
        background: #10172a;
    }

    :root {
        --azuki-gold: #d4af37;
        --azuki-teal: #20b4a8;
        --azuki-light: #e8dcc8;
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
        color: rgba(232, 220, 200, 0.96);
        font-family: var(--azuki-font-ui);
        background:
            linear-gradient(180deg, rgba(13, 18, 38, 0.78), rgba(13, 28, 42, 0.88)),
            linear-gradient(135deg, rgba(26, 31, 58, 0.96), rgba(32, 180, 168, 0.18));
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
        border-bottom: 1px solid rgba(232, 220, 200, 0.07);
    }

    .azuki-m-band {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 3px 6px;
        background: rgba(8, 12, 24, 0.78);
    }

    .azuki-m-band.is-mine,
    .azuki-m-section.is-mine,
    .azuki-m-lanes.is-mine {
        background-color: rgba(32, 180, 168, 0.055);
    }

    .azuki-m-band.is-theirs,
    .azuki-m-section.is-theirs,
    .azuki-m-lanes.is-theirs {
        background-color: rgba(200, 76, 60, 0.045);
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
        color: rgba(212, 175, 55, 0.82);
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
        border: 1px solid rgba(212, 175, 55, 0.24);
        border-radius: 8px;
        background: rgba(18, 26, 50, 0.58);
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
        background: rgba(8, 12, 24, 0.8);
        border: 1px solid rgba(232, 220, 200, 0.12);
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
        border: 1px solid rgba(212, 175, 55, 0.30);
        border-radius: 999px;
        background: rgba(26, 31, 58, 0.78);
    }

    .azuki-m-token.has-token {
        display: inline-flex;
    }

    .azuki-m-token::before {
        content: "IKZ";
        color: rgba(212, 175, 55, 0.86);
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
        border-radius: 5px;
        background: rgba(8, 12, 24, 0.38);
        box-shadow: inset 0 0 0 1px rgba(212, 175, 55, 0.12);
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
    #myGate > span[id],
    #theirGate > span[id],
    #myDeck a,
    #theirDeck a,
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
    #theirDeck .counter-bubble {
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

    #myLeaderHealth > span:first-child,
    #theirLeaderHealthSlot {
        display: none !important;
    }

    #myLeaderHealthSlot .widget-button-pass {
        min-width: 66px;
        border: 1px solid rgba(212, 175, 55, 0.72);
        border-radius: 8px;
        padding: 8px 10px;
        background: linear-gradient(180deg, rgba(212, 175, 55, 0.28), rgba(212, 175, 55, 0.13));
        color: #f3e8d0;
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
        border: 1px solid rgba(212, 175, 55, 0.16);
        border-radius: 8px;
        background: rgba(10, 16, 32, 0.38);
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
</style>

<div id="azukiMobileRoot">
    <div id="azukiAdminMenu" class="azuki-admin-menu">
        <button id="azukiAdminMenuBtn" class="azuki-admin-menu-btn" type="button" aria-label="Game menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <div id="azukiAdminMenuPanel" class="azuki-admin-menu-panel">
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
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Deck</div><div id="theirDeckSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Discard</div><div id="theirDiscardSlot" class="azuki-zone"></div></div>
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
        <div class="azuki-m-label">My Hand</div>
        <div id="myHandSlot" class="azuki-zone"></div>
    </div>

    <div class="azuki-m-band is-mine">
        <div id="myIKZSummary" class="azuki-m-ikz-summary" aria-label="My IKZ summary"></div>
        <div id="myIKZTokenSlot" class="azuki-zone azuki-m-token" data-label="IKZ Token"></div>
        <div class="azuki-m-pass"><div id="myLeaderHealthSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-gate"><div class="azuki-m-pile-label">Gate</div><div id="myGateSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Deck</div><div id="myDeckSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Discard</div><div id="myDiscardSlot" class="azuki-zone"></div></div>
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
        }

        btn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            updateAvailability();
            setOpen(!menu.classList.contains('is-open'));
        });

        panel.addEventListener('click', function(event) {
            var action = event.target && event.target.closest ? event.target.closest('[data-azuki-admin-target]') : null;
            if(!action) return;
            event.preventDefault();
            event.stopPropagation();
            var target = document.getElementById(action.getAttribute('data-azuki-admin-target'));
            if(target && typeof target.click === 'function') target.click();
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
    updateIKZSummaries();
    renderOpponentHandSummary();
    observeZone('theirHandSlot', renderOpponentHandSummary);
    observeZone('myIKZAreaSlot', updateIKZSummaries);
    observeZone('theirIKZAreaSlot', updateIKZSummaries);
    observeZone('myIKZTokenSlot', window.UpdateAzukiMobileIKZTokens);
    observeZone('theirIKZTokenSlot', window.UpdateAzukiMobileIKZTokens);
    window.setInterval(function() {
        updateIKZSummaries();
        renderOpponentHandSummary();
    }, 400);
    window.UpdateAzukiResponseOpportunity();
})();
</script>
