<?php
// GameLayout.php — Container divs for all BindTo zones in AzukiSim.
// Included from InitialLayout.php after the main split-screen structure.
?>
<style>
    html,
    body {
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    :root {
        --azuki-navy: #1a1f3a;
        --azuki-gold: #d4af37;
        --azuki-teal: #20b4a8;
        --azuki-red: #c84c3c;
        --azuki-light: #e8dcc8;
        --azuki-shadow: 0 16px 40px rgba(0, 0, 0, 0.32);
        --azuki-font-ui: "Segoe UI Variable Display", "Aptos", sans-serif;
        --azuki-font-label: "Franklin Gothic Medium", "Bahnschrift", sans-serif;
    }

    #myStuff {
        border: 0 !important;
    }

    .azuki-board-bg {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 10;
        background: linear-gradient(135deg, rgba(26, 31, 58, 0.95), rgba(32, 180, 168, 0.12));
    }

    .azuki-zone {
        position: fixed;
        z-index: 30;
        pointer-events: auto;
    }

    .azuki-glass {
        border: 1px solid rgba(212, 175, 55, 0.24);
        border-radius: 12px;
        background:
            linear-gradient(180deg, rgba(232, 220, 200, 0.10), rgba(255, 255, 255, 0.02)),
            linear-gradient(160deg, rgba(26, 31, 58, 0.84), rgba(26, 31, 58, 0.72));
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(12px) saturate(130%);
        -webkit-backdrop-filter: blur(12px) saturate(130%);
        padding: 24px 12px 10px;
        transition: transform 140ms ease, border-color 140ms ease;
    }

    .azuki-glass::before {
        content: attr(data-label);
        position: absolute;
        top: 8px;
        left: 12px;
        right: 12px;
        color: rgba(212, 175, 55, 0.88);
        text-transform: uppercase;
        letter-spacing: 0.18em;
        font: 700 10px/1 var(--azuki-font-label);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        pointer-events: none;
    }

    .azuki-glass::after {
        content: "";
        position: absolute;
        left: 12px;
        right: 12px;
        top: 21px;
        height: 1px;
        background: linear-gradient(90deg, rgba(212, 175, 55, 0.16), rgba(232, 220, 200, 0.04));
        pointer-events: none;
    }

    .azuki-glass:hover {
        transform: translateY(-2px);
        border-color: rgba(212, 175, 55, 0.42);
        box-shadow: 0 20px 48px rgba(0, 0, 0, 0.40), inset 0 1px 0 rgba(255, 255, 255, 0.12);
    }

    .azuki-pile {
        width: 104px;
        min-height: 92px;
        overflow-x: hidden;
    }

    .azuki-stat {
        width: 120px;
        min-height: 76px;
    }

    .azuki-hand {
        width: min(58vw, 1040px);
        min-height: 112px;
    }

    .azuki-field {
        width: min(54vw, 980px);
        min-height: 148px;
    }

    .azuki-leader {
        width: 148px;
        min-height: 180px;
    }

    /* IKZ Area display — simple vertical stack with wrapping */
    #myIKZArea {
        width: 280px !important;
        max-width: 280px !important;
        height: 340px !important;
        min-height: auto !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        display: flex !important;
        flex-direction: column !important;
        flex-wrap: wrap !important;
        gap: 0 !important;
        align-content: flex-start !important;
        justify-content: flex-end !important;
        overflow: visible !important;
        background: none !important;
        border: none !important;
        box-shadow: none !important;
    }

    #theirIKZArea {
        width: 280px !important;
        max-width: 280px !important;
        height: 340px !important;
        min-height: auto !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        display: flex !important;
        flex-direction: column !important;
        flex-wrap: wrap !important;
        gap: 0 !important;
        align-content: flex-start !important;
        overflow: visible !important;
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        justify-content: flex-start !important;
    }

    #myIKZAreaSlot::before,
    #theirIKZAreaSlot::before {
        display: none;
    }

    #myIKZArea > *,
    #theirIKZArea > * {
        width: 140px !important;
        height: auto !important;
        min-height: auto !important;
        padding: 0 !important;
        border-radius: 0 !important;
        background: none !important;
        border: none !important;
        display: block !important;
        font: inherit;
        color: inherit;
        box-shadow: none !important;
        flex-shrink: 0;
        position: relative !important;
    }

    /* my cards: justify-content flex-end pushes stack to bottom, negative margin creates overlap going up */
    #myIKZArea > * {
        margin: 0 0 -55px 0 !important;
    }

    #myIKZArea > *:last-child {
        margin-bottom: 0 !important;
    }

    /* their cards grow downward: negative margin on top */
    #theirIKZArea > * {
        margin: -55px 0 0 0 !important;
    }

    #theirIKZArea > *:first-child {
        margin-top: 0 !important;
    }

    /* Tapped IKZ (Status=1) card styling */
    #myIKZArea > *[class*="exhausted"],
    #theirIKZArea > *[class*="exhausted"] {
        transform: rotate(9deg);
        opacity: 0.65;
    }

    /* IKZ Token display — glowing orb */
    #myIKZTokenSlot,
    #theirIKZTokenSlot {
        width: 48px;
        height: 48px;
        min-height: 48px;
        padding: 0;
        border: none;
        background: none;
        box-shadow: none;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        position: relative;
        overflow: visible;
    }

    #myIKZTokenSlot > *:not(:last-child),
    #theirIKZTokenSlot > *:not(:last-child) {
        display: none;
    }

    #myIKZTokenSlot::after,
    #theirIKZTokenSlot::after {
        content: "";
        position: absolute;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 30%, rgba(32, 180, 168, 0.9), rgba(20, 140, 130, 0.5));
        box-shadow: 0 0 20px rgba(32, 180, 168, 0.8), inset 0 1px 3px rgba(255, 255, 255, 0.3);
        border: 2px solid rgba(32, 180, 168, 0.9);
        z-index: 1;
    }

    #myIKZTokenSlot > span,
    #theirIKZTokenSlot > span {
        font: 700 20px/1 var(--azuki-font-ui);
        color: rgba(255, 255, 255, 0.95);
        margin: 0 !important;
        padding: 0 !important;
        position: relative;
        z-index: 2;
    }

    /* Leader slot positioning */
    /* Alley (Back Row) positioning - immediately above hand */
    #myAlleySlot,
    #theirAlleySlot {
        left: 50%;
        transform: translateX(-50%);
        min-height: 92px !important;
        z-index: 35;
    }

    #myAlleySlot {
        bottom: 124px;
    }

    #theirAlleySlot {
        top: 124px;
    }

    /* Garden (Front Row) positioning - above Alley */
    #myGardenSlot,
    #theirGardenSlot {
        left: 50%;
        transform: translateX(-50%);
        min-height: 76px !important;
        z-index: 35;
    }

    #myGardenSlot {
        bottom: 232px;
    }

    #theirGardenSlot {
        top: 232px;
    }

    /* Gate positioning (right side) - top of stack */
    #myGateSlot,
    #theirGateSlot {
        right: 24px;
        width: 100px;
        min-height: 140px;
    }

    #myGateSlot {
        bottom: calc(92px + 12px + 92px + 12px);
    }

    #theirGateSlot {
        top: calc(20px + 92px + 12px + 104px + 12px);
    }

    /* Health and IKZ resource pools */
    #myLeaderHealthSlot,
    #theirLeaderHealthSlot {
        width: 120px;
        min-height: 76px;
        right: 132px;
    }

    #myLeaderHealthSlot {
        bottom: calc(50% - 120px);
    }

    #theirLeaderHealthSlot {
        top: calc(50% - 120px);
    }

    #theirLeaderHealthSlot {
        display: none;
    }

    #myIKZAreaSlot,
    #theirIKZAreaSlot {
        left: 24px;
    }

    #myIKZAreaSlot {
        bottom: calc(20px + 48px + 12px + 16px);
    }

    #theirIKZAreaSlot {
        top: calc(20px + 48px + 12px + 16px);
    }

    #myIKZTokenSlot,
    #theirIKZTokenSlot {
        left: 24px;
    }

    #myIKZTokenSlot {
        bottom: 20px;
    }

    #theirIKZTokenSlot {
        top: 20px;
    }

    /* Discard pile (bottom-right / top-right) - bottom of stack */
    #myDiscardSlot,
    #theirDiscardSlot {
        right: 24px;
    }

    #myDiscardSlot {
        bottom: 20px;
    }

    #theirDiscardSlot {
        top: 20px;
    }

    /* Deck directly above/below discard */
    #myDeckSlot,
    #theirDeckSlot {
        right: 24px;
        width: 104px;
    }

    #myDeckSlot {
        bottom: calc(20px + 92px + 12px);
    }

    #theirDeckSlot {
        top: calc(20px + 92px + 12px);
    }

    /* TempZone and IKZPile hidden */
    #myTempZoneSlot,
    #theirTempZoneSlot,
    #myGlobalEffectsSlot,
    #theirGlobalEffectsSlot,
    #myIKZPileWrapper,
    #theirIKZPileWrapper,
    #myIKZTokenWrapper,
    #theirIKZTokenWrapper {
        display: none !important;
    }

    /* Hand slots (bottom/top center) */
    #myHandSlot.azuki-glass,
    #theirHandSlot.azuki-glass {
        padding: 6px 8px;
    }

    #myHandSlot {
        left: 50%;
        transform: translateX(-50%);
        bottom: 0;
    }

    #theirHandSlot {
        left: 50%;
        transform: translateX(-50%);
        top: 0;
    }

    #myHandSlot.azuki-glass::before,
    #theirHandSlot.azuki-glass::before,
    #myHandSlot.azuki-glass::after,
    #theirHandSlot.azuki-glass::after {
        display: none;
    }

    #myHand > span:not([id]),
    #theirHand > span:not([id]) {
        display: none;
    }

    #myGarden > span:not([id]),
    #theirGarden > span:not([id]),
    #myAlley > span:not([id]),
    #theirAlley > span:not([id]),
    #myDiscard > span:not([id]),
    #theirDiscard > span:not([id]),
    #myDeck > span:not([id]),
    #theirDeck > span:not([id]) {
        display: none;
    }

    #azukiResponseOpportunity {
        position: fixed;
        bottom: 120px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 12000;
        display: none;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 12px;
        border: 1px solid rgba(212, 175, 55, 0.65);
        background: linear-gradient(180deg, rgba(19, 31, 52, 0.98), rgba(14, 23, 39, 0.98));
        color: #f3e8d0;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        pointer-events: auto;
        max-width: min(92vw, 560px);
    }

    #azukiResponseOpportunity .azuki-opportunity-text {
        display: flex;
        flex-direction: column;
        line-height: 1.25;
    }

    #azukiResponseOpportunity .azuki-opportunity-title {
        font: 700 12px/1 var(--azuki-font-label);
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgba(212, 175, 55, 0.98);
    }

    #azukiResponseOpportunity .azuki-opportunity-subtitle {
        font: 600 13px/1.35 var(--azuki-font-ui);
        color: rgba(232, 220, 200, 0.95);
    }

    #azukiResponsePassBtn {
        border: 1px solid rgba(212, 175, 55, 0.7);
        border-radius: 9px;
        background: linear-gradient(180deg, rgba(212, 175, 55, 0.28), rgba(212, 175, 55, 0.14));
        color: #f3e8d0;
        font: 700 12px/1 var(--azuki-font-label);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 8px 14px;
        cursor: pointer;
    }

    #azukiResponsePassBtn:hover {
        background: linear-gradient(180deg, rgba(212, 175, 55, 0.4), rgba(212, 175, 55, 0.2));
    }

    @media (max-width: 1000px) {
        #azukiResponseOpportunity {
            bottom: 110px;
            width: calc(100vw - 20px);
            justify-content: space-between;
            gap: 8px;
            padding: 9px 10px;
        }

        #azukiResponseOpportunity .azuki-opportunity-subtitle {
            font-size: 12px;
        }

        #azukiResponsePassBtn {
            padding: 7px 10px;
            font-size: 11px;
        }
    }
</style>

<!-- Background layers -->
<div class="azuki-board-bg"></div>

<div id="azukiResponseOpportunity" aria-live="polite" aria-label="Response opportunity">
    <div class="azuki-opportunity-text">
        <span class="azuki-opportunity-title">Response Opportunity</span>
        <span id="azukiResponseOpportunityText" class="azuki-opportunity-subtitle">You may play a [Response] spell.</span>
    </div>
    <button id="azukiResponsePassBtn" type="button" onclick="AzukiResponsePass()">Pass</button>
</div>

<!-- =================== MY ZONES (bottom half) =================== -->

<div id="myGardenSlot" class="azuki-zone azuki-field" data-label="Garden (Front)">
</div>

<div id="myAlleySlot" class="azuki-zone azuki-field" data-label="Alley (Back)">
</div>

<div id="myGateSlot" class="azuki-zone" data-label="Gate">
</div>

<div id="myLeaderHealthSlot" class="azuki-zone azuki-stat" data-label="Pass">
</div>

<div id="myIKZAreaSlot" class="azuki-zone" data-label="">
</div>

<div id="myIKZTokenSlot" class="azuki-zone" data-label="IKZ Token">
</div>

<div id="myDiscardSlot" class="azuki-zone azuki-pile" data-label="Discard">
</div>

<div id="myDeckSlot" class="azuki-zone azuki-pile" data-label="Deck">
</div>

<div id="myHandSlot" class="azuki-zone azuki-glass azuki-hand" data-label="">
</div>

<!-- =================== THEIR ZONES (top half) =================== -->

<div id="theirGardenSlot" class="azuki-zone azuki-field" data-label="Garden (Front)">
</div>

<div id="theirAlleySlot" class="azuki-zone azuki-field" data-label="Alley (Back)">
</div>

<div id="theirGateSlot" class="azuki-zone" data-label="Gate">
</div>

<div id="theirLeaderHealthSlot" class="azuki-zone azuki-stat" data-label="">
</div>

<div id="theirIKZAreaSlot" class="azuki-zone" data-label="">
</div>

<div id="theirIKZTokenSlot" class="azuki-zone" data-label="IKZ Token">
</div>

<div id="theirDiscardSlot" class="azuki-zone azuki-pile" data-label="Discard">
</div>

<div id="theirDeckSlot" class="azuki-zone azuki-pile" data-label="Deck">
</div>

<div id="theirHandSlot" class="azuki-zone azuki-glass azuki-hand" data-label="">
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
            ? 'Play a [Response] spell or pass to resolve the attack.'
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
            // If property interception is unavailable, fallback polling keeps the panel in sync.
            setInterval(function() {
                if(typeof window.UpdateAzukiResponseOpportunity === 'function') {
                    window.UpdateAzukiResponseOpportunity();
                }
            }, 200);
        }
    }

    installResponseWatcher();
    window.UpdateAzukiResponseOpportunity();
})();
</script>
