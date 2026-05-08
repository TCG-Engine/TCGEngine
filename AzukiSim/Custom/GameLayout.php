<?php
// GameLayout.php — Container divs for all BindTo zones in AzukiSim.
// Included from InitialLayout.php after the main split-screen structure.
?>
<style>
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
        width: 88px;
        min-height: 92px;
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

    /* IKZ display — resource pool counter */
    #myIKZSlot,
    #theirIKZSlot {
        border: 1px solid rgba(212, 175, 55, 0.28);
        border-radius: 8px;
        background:
            linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(255, 255, 255, 0.02)),
            linear-gradient(160deg, rgba(26, 31, 58, 0.88), rgba(26, 31, 58, 0.80));
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.06);
        width: 72px;
        min-height: 72px;
        padding: 12px;
        text-align: center;
    }

    #myIKZSlot::before,
    #theirIKZSlot::before {
        content: "IKZ";
        display: block;
        color: rgba(212, 175, 55, 0.82);
        font: 700 10px/1 var(--azuki-font-label);
        text-transform: uppercase;
        letter-spacing: 0.16em;
        margin-bottom: 8px;
    }

    #myIKZSlot #myIKZ,
    #theirIKZSlot #theirIKZ {
        font: 700 28px/1 var(--azuki-font-ui);
        color: rgba(32, 180, 168, 0.92);
    }

    /* Leader slot positioning */
    #myLeaderSlot,
    #theirLeaderSlot {
        left: 50%;
        transform: translateX(-50%);
    }

    #myLeaderSlot {
        bottom: 20px;
    }

    #theirLeaderSlot {
        top: 20px;
    }

    /* Garden (Front Row) positioning */
    #myGardenSlot,
    #theirGardenSlot {
        left: 50%;
        transform: translateX(-50%);
    }

    #myGardenSlot {
        bottom: calc(20px + 180px + 12px);
    }

    #theirGardenSlot {
        top: calc(20px + 180px + 12px);
    }

    /* Alley (Back Row) positioning */
    #myAlleySlot,
    #theirAlleySlot {
        left: 50%;
        transform: translateX(-50%);
    }

    #myAlleySlot {
        bottom: calc(20px + 180px + 12px + 148px + 12px);
    }

    #theirAlleySlot {
        top: calc(20px + 180px + 12px + 148px + 12px);
    }

    /* Gate positioning (side near leader health) */
    #myGateSlot,
    #theirGateSlot {
        right: 24px;
        width: 100px;
        min-height: 140px;
    }

    #myGateSlot {
        bottom: 20px;
    }

    #theirGateSlot {
        top: 20px;
    }

    /* Health and IKZ resource pools */
    #myLeaderHealthSlot,
    #theirLeaderHealthSlot {
        width: 120px;
        min-height: 76px;
        right: 24px;
    }

    #myLeaderHealthSlot {
        bottom: calc(20px + 72px + 12px);
    }

    #theirLeaderHealthSlot {
        top: calc(20px + 72px + 12px);
    }

    #myIKZSlot,
    #theirIKZSlot {
        right: 24px;
    }

    #myIKZSlot {
        bottom: calc(20px + 72px + 12px + 76px + 12px);
    }

    #theirIKZSlot {
        top: calc(20px + 72px + 12px + 76px + 12px);
    }

    /* Discard pile (bottom-left) */
    #myDiscardSlot,
    #theirDiscardSlot {
        left: 24px;
    }

    #myDiscardSlot {
        bottom: 20px;
    }

    #theirDiscardSlot {
        top: 20px;
    }

    /* Deck and other zones (bottom-right/top-right) */
    #myDeckSlot,
    #theirDeckSlot {
        right: 24px;
        width: 88px;
    }

    #myDeckSlot {
        bottom: calc(20px + 72px + 12px + 76px + 12px + 72px + 12px);
    }

    #theirDeckSlot {
        top: calc(20px + 72px + 12px + 76px + 12px + 72px + 12px);
    }

    /* TempZone hidden */
    #myTempZoneSlot,
    #theirTempZoneSlot,
    #myGlobalEffectsSlot,
    #theirGlobalEffectsSlot {
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
</style>

<!-- Background layers -->
<div class="azuki-board-bg"></div>

<!-- =================== MY ZONES (bottom half) =================== -->

<div id="myLeaderSlot" class="azuki-zone azuki-leader" data-label="Leader"
    style="width:148px; min-height:180px;">
</div>

<div id="myGardenSlot" class="azuki-zone azuki-field" data-label="Garden (Front)">
</div>

<div id="myAlleySlot" class="azuki-zone azuki-field" data-label="Alley (Back)">
</div>

<div id="myGateSlot" class="azuki-zone" data-label="Gate"
    style="right:24px; bottom:20px; width:100px; min-height:140px;">
</div>

<div id="myLeaderHealthSlot" class="azuki-zone azuki-stat" data-label="Health">
</div>

<div id="myIKZSlot" class="azuki-zone" data-label="">
</div>

<div id="myDiscardSlot" class="azuki-zone azuki-pile" data-label="Discard">
</div>

<div id="myDeckSlot" class="azuki-zone azuki-pile" data-label="Deck">
</div>

<div id="myHandSlot" class="azuki-zone azuki-glass azuki-hand" data-label="">
</div>

<!-- =================== THEIR ZONES (top half) =================== -->

<div id="theirLeaderSlot" class="azuki-zone azuki-leader" data-label="Leader"
    style="width:148px; min-height:180px;">
</div>

<div id="theirGardenSlot" class="azuki-zone azuki-field" data-label="Garden (Front)">
</div>

<div id="theirAlleySlot" class="azuki-zone azuki-field" data-label="Alley (Back)">
</div>

<div id="theirGateSlot" class="azuki-zone" data-label="Gate"
    style="right:24px; top:20px; width:100px; min-height:140px;">
</div>

<div id="theirLeaderHealthSlot" class="azuki-zone azuki-stat" data-label="Health">
</div>

<div id="theirIKZSlot" class="azuki-zone" data-label="">
</div>

<div id="theirDiscardSlot" class="azuki-zone azuki-pile" data-label="Discard">
</div>

<div id="theirDeckSlot" class="azuki-zone azuki-pile" data-label="Deck">
</div>

<div id="theirHandSlot" class="azuki-zone azuki-glass azuki-hand" data-label="">
</div>
