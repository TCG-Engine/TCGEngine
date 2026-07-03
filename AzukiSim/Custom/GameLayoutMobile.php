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
        grid-template-rows: 54px 74px minmax(0, 1fr) minmax(0, 1fr) 98px 58px;
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
        gap: 6px;
        padding: 3px 8px;
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

    .azuki-m-ikz-summary {
        min-width: 72px;
        flex-direction: row;
        justify-content: flex-start;
        gap: 5px;
        padding: 3px 6px;
        border: 1px solid rgba(212, 175, 55, 0.24);
        border-radius: 8px;
        background: rgba(18, 26, 50, 0.58);
    }

    .azuki-m-ikz-thumb {
        width: 28px;
        height: 39px;
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
        font: 800 12px/1 var(--azuki-font-ui);
        white-space: nowrap;
    }

    .azuki-m-token {
        display: none;
        min-width: 42px;
        height: 34px;
        padding: 0 8px;
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
        font: 700 9px/1 var(--azuki-font-label);
        letter-spacing: 0.12em;
    }

    .azuki-m-pile > div:not(.azuki-m-pile-label),
    .azuki-m-gate > div,
    .azuki-m-pass > div {
        min-width: 42px;
        min-height: 36px;
        max-height: 44px;
        max-width: 62px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .azuki-m-pile img:not(.counter-image-icon) {
        height: 40px !important;
        width: auto !important;
        border-radius: 4px;
    }

    .azuki-m-gate img:not(.counter-image-icon) {
        height: 44px !important;
        width: auto !important;
        border-radius: 4px;
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

    #chatWidget,
    #bug-report-button,
    #copy-spectate-link-button,
    #concede-button {
        z-index: 12000 !important;
    }

    #chatWidget {
        position: fixed !important;
        top: 4px !important;
        left: 8px !important;
        bottom: auto !important;
        width: auto !important;
        max-width: calc(100vw - 16px) !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: flex-start !important;
    }

    #chatToggleBtn {
        margin-top: 0 !important;
        height: 28px !important;
    }

    #bug-report-button,
    #copy-spectate-link-button,
    #concede-button {
        position: fixed !important;
        top: 4px !important;
        bottom: auto !important;
        padding: 6px 10px !important;
        font-size: 11px !important;
        white-space: nowrap !important;
    }

    #concede-button {
        right: 8px !important;
    }

    #bug-report-button {
        right: 88px !important;
    }

    #copy-spectate-link-button {
        right: 176px !important;
    }
</style>

<div id="azukiMobileRoot">
    <div id="azukiResponseOpportunity" aria-live="polite" aria-label="Response opportunity">
        <div class="azuki-opportunity-text">
            <span class="azuki-opportunity-title">Response Opportunity</span>
            <span id="azukiResponseOpportunityText" class="azuki-opportunity-subtitle">You may play a [Response] card.</span>
        </div>
        <button id="azukiResponsePassBtn" type="button" onclick="AzukiResponsePass()">Pass</button>
    </div>

    <div class="azuki-m-band is-theirs">
        <div id="theirIKZSummary" class="azuki-m-ikz-summary" aria-label="Their IKZ summary"></div>
        <div id="theirIKZTokenSlot" class="azuki-zone azuki-m-token" data-label="IKZ Token"></div>
        <div class="azuki-m-gate"><div class="azuki-m-pile-label">Gate</div><div id="theirGateSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Deck</div><div id="theirDeckSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Discard</div><div id="theirDiscardSlot" class="azuki-zone"></div></div>
    </div>

    <div class="azuki-m-section is-theirs">
        <div class="azuki-m-label">Their Hand</div>
        <div id="theirHandSlot" class="azuki-zone"></div>
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
        <div class="azuki-m-pass"><div class="azuki-m-pile-label">Pass</div><div id="myLeaderHealthSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-gate"><div class="azuki-m-pile-label">Gate</div><div id="myGateSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Deck</div><div id="myDeckSlot" class="azuki-zone"></div></div>
        <div class="azuki-m-pile"><div class="azuki-m-pile-label">Discard</div><div id="myDiscardSlot" class="azuki-zone"></div></div>
    </div>

    <div id="EffectStackSlot" class="azuki-zone"></div>

    <div class="azuki-m-hidden-bindings" aria-hidden="true">
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

    installResponseWatcher();
    setupIKZTokenIndicator();
    updateIKZSummaries();
    observeZone('myIKZAreaSlot', updateIKZSummaries);
    observeZone('theirIKZAreaSlot', updateIKZSummaries);
    observeZone('myIKZTokenSlot', window.UpdateAzukiMobileIKZTokens);
    observeZone('theirIKZTokenSlot', window.UpdateAzukiMobileIKZTokens);
    window.setInterval(updateIKZSummaries, 400);
    window.UpdateAzukiResponseOpportunity();
})();
</script>
