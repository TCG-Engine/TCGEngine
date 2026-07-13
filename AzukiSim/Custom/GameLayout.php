<?php
// GameLayout.php — Container divs for all BindTo zones in AzukiSim.
// Included from InitialLayout.php after the main split-screen structure.
require_once __DIR__ . '/GameLayoutDevice.php';
if (AzukiSimIsMobileRequest()) { include __DIR__ . '/GameLayoutMobile.php'; return; }
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

    #myGardenWrapper,
    #theirGardenWrapper,
    #myAlleyWrapper,
    #theirAlleyWrapper {
        position: relative;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        padding-top: 14px;
        padding-bottom: 14px;
        margin-top: -14px;
        margin-bottom: -14px;
        border-radius: 18px;
        scrollbar-width: none;
        -ms-overflow-style: none;
        -webkit-overflow-scrolling: touch;
    }

    #myGardenWrapper::-webkit-scrollbar,
    #theirGardenWrapper::-webkit-scrollbar,
    #myAlleyWrapper::-webkit-scrollbar,
    #theirAlleyWrapper::-webkit-scrollbar {
        display: none;
    }

    #myGarden,
    #theirGarden,
    #myAlley,
    #theirAlley {
        flex-wrap: nowrap !important;
        justify-content: flex-start !important;
        overflow: visible !important;
        min-width: 100%;
    }

    #myGarden > span,
    #theirGarden > span,
    #myAlley > span,
    #theirAlley > span {
        flex: 0 0 auto;
    }

    .azuki-lane-scroll-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(212, 175, 55, 0.26);
        border-radius: 999px;
        background: linear-gradient(180deg, rgba(26, 31, 58, 0.96), rgba(20, 24, 46, 0.86));
        color: rgba(232, 220, 200, 0.92);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
        cursor: pointer;
        z-index: 38;
        transition: opacity 120ms ease, transform 120ms ease, border-color 120ms ease, background 120ms ease;
    }

    .azuki-lane-scroll-btn:hover {
        background: linear-gradient(180deg, rgba(38, 48, 88, 0.98), rgba(24, 31, 58, 0.9));
        border-color: rgba(212, 175, 55, 0.5);
    }

    .azuki-lane-scroll-btn.is-hidden,
    .azuki-lane-scroll-btn.is-disabled {
        opacity: 0;
        pointer-events: none;
    }

    .azuki-lane-scroll-btn-left {
        left: -16px;
    }

    .azuki-lane-scroll-btn-right {
        right: -16px;
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
        display: none;
        min-width: 108px;
        height: 36px;
        min-height: 36px;
        padding: 0 12px;
        border: 1px solid rgba(212, 175, 55, 0.30);
        background:
            linear-gradient(180deg, rgba(232, 220, 200, 0.14), rgba(255, 255, 255, 0.04)),
            linear-gradient(160deg, rgba(26, 31, 58, 0.88), rgba(26, 31, 58, 0.78));
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        position: fixed;
        overflow: hidden;
        pointer-events: none;
        z-index: 12001;
    }

    #myIKZTokenSlot.has-token,
    #theirIKZTokenSlot.has-token {
        display: inline-flex;
    }

    #myIKZTokenSlot::before,
    #theirIKZTokenSlot::before {
        content: "IKZ Token";
        position: static;
        color: rgba(212, 175, 55, 0.88);
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font: 700 10px/1 var(--azuki-font-label);
        pointer-events: none;
        white-space: nowrap;
    }

    #myIKZToken,
    #theirIKZToken {
        display: none !important;
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
        left: 164px;
    }

    #myIKZTokenSlot {
        bottom: 56px;
    }

    #theirIKZTokenSlot {
        top: 56px;
        bottom: auto;
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
        z-index: 36;
        overflow: visible;
        transition: transform 260ms cubic-bezier(0.4, 0, 0.2, 1), border-color 140ms ease, box-shadow 140ms ease;
    }

    #theirHandSlot {
        left: 50%;
        transform: translateX(-50%);
        top: 0;
        z-index: 36;
        overflow: visible;
        transition: transform 260ms cubic-bezier(0.4, 0, 0.2, 1), border-color 140ms ease, box-shadow 140ms ease;
    }

    .azuki-hand-collapse-btn {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%) translateY(-50%);
        width: 48px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(26, 31, 58, 0.9);
        border: 1px solid rgba(212, 175, 55, 0.28);
        border-radius: 99px;
        cursor: pointer;
        color: rgba(232, 220, 200, 0.7);
        font-size: 9px;
        line-height: 1;
        padding: 0;
        z-index: 2;
        transition: color 120ms ease, background 120ms ease, border-color 120ms ease;
        user-select: none;
        -webkit-user-select: none;
    }

    .azuki-hand-collapse-btn:hover {
        color: rgba(232, 220, 200, 0.98);
        background: rgba(36, 44, 82, 0.96);
        border-color: rgba(212, 175, 55, 0.52);
    }

    #myHandSlot.is-collapsed {
        transform: translateX(-50%) translateY(calc(100% - 18px));
    }

    #myHandSlot.is-collapsed:hover {
        transform: translateX(-50%) translateY(calc(100% - 18px)) !important;
    }

    #theirHandSlot.is-collapsed {
        transform: translateX(-50%) translateY(calc(-100% + 18px));
    }

    #theirHandSlot.is-collapsed:hover {
        transform: translateX(-50%) translateY(calc(-100% + 18px)) !important;
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

    #chatWidget,
    #regressionControls,
    #manualControls,
    #bug-report-button,
    #concede-button {
        z-index: 12000 !important;
    }

    #chatWidget {
        left: 16px !important;
        bottom: 16px !important;
    }

    #regressionControls {
        top: 16px !important;
        right: 16px !important;
    }

    #manualControls {
        top: 16px !important;
        right: 268px !important;
    }

    /*
     * Desktop board
     *
     * The board deliberately has two broad, calm play areas.  The cards and
     * interactions still render through the generated slot IDs below; this is
     * only their desktop frame.  Keeping that distinction matters because
     * NextTurn replaces the contents of a slot after every action.
     */
    @media (min-width: 1001px) {
        :root {
            --azuki-field-w: 64vw;
            --azuki-field-half-w: 32vw;
            --azuki-field-shift: 4.6vw;
            --azuki-field-h: clamp(96px, 12.5vh, 140px);
            --azuki-lane-gap: clamp(6px, 0.7vh, 8px);
            --azuki-top-center-gap: clamp(12px, 1.4vh, 14px);
            --azuki-bottom-center-gap: clamp(18px, 2.2vh, 20px);
            --azuki-pile-w: 104px;
            --azuki-pile-gap: clamp(28px, 2vw, 40px);
            --azuki-lane-left: calc(50vw - var(--azuki-field-shift) - var(--azuki-field-half-w));
            --azuki-my-pile-left: calc(var(--azuki-lane-left) - var(--azuki-pile-w) - var(--azuki-pile-gap));
            --azuki-pile-right: calc(100vw - (var(--azuki-lane-left) + var(--azuki-field-w) + var(--azuki-pile-w) + var(--azuki-pile-gap)));
            --azuki-rail-left: var(--azuki-my-pile-left);
            --azuki-rail-card-w: var(--azuki-pile-w);
            --azuki-ikz-row-w: min(28vw, 340px);
            --azuki-field-card-size: clamp(88px, 5.2vw, 96px);
            --azuki-ikz-card-size: 68px;
        }

        #mainDiv,
        .stuffParent,
        .theirStuffWrapper,
        .myStuffWrapper,
        #myStuff,
        #theirStuff {
            background: #182b3e !important;
        }

        .azuki-board-bg {
            z-index: 10;
            background:
                radial-gradient(ellipse 62% 28% at 45% 50%, rgba(80, 132, 158, 0.14), transparent 72%),
                linear-gradient(180deg, #223b52 0%, #223b52 49.75%, #17293a 49.9%, #17293a 100%);
        }

        .azuki-board-bg::before,
        .azuki-board-bg::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            pointer-events: none;
        }

        .azuki-board-bg::before {
            top: calc(50% - 1px);
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, rgba(174, 207, 224, 0.2) 16%, rgba(174, 207, 224, 0.28) 50%, rgba(174, 207, 224, 0.2) 84%, transparent 100%);
            box-shadow: 0 1px 20px rgba(0, 0, 0, 0.22);
        }

        .azuki-board-bg::after {
            top: 0;
            bottom: 0;
            background: linear-gradient(90deg, rgba(9, 18, 29, 0.26), transparent 14%, transparent 78%, rgba(9, 18, 29, 0.18));
        }

        .azuki-zone {
            z-index: 30;
        }

        .azuki-field {
            box-sizing: border-box;
            width: var(--azuki-field-w);
            height: var(--azuki-field-h);
            min-height: var(--azuki-field-h);
            padding: 10px 18px 8px;
            overflow: visible;
            border: 1px solid rgba(137, 178, 199, 0.09);
            border-radius: 11px;
            background: linear-gradient(180deg, rgba(29, 51, 70, 0.96), rgba(25, 45, 63, 0.96));
            box-shadow: inset 0 1px 0 rgba(232, 247, 255, 0.025), 0 10px 24px rgba(7, 15, 25, 0.12);
        }

        .azuki-field::before {
            content: attr(data-label);
            position: absolute;
            top: 7px;
            left: 14px;
            color: rgba(205, 224, 235, 0.52);
            font: 700 9px/1 var(--azuki-font-label);
            letter-spacing: 0.13em;
            text-transform: uppercase;
            pointer-events: none;
        }

        #myGardenSlot,
        #theirGardenSlot,
        #myAlleySlot,
        #theirAlleySlot {
            left: calc(50% - var(--azuki-field-shift));
            transform: translateX(-50%);
        }

        #theirGardenSlot {
            top: calc(50% - var(--azuki-top-center-gap) - var(--azuki-field-h));
            z-index: 36;
        }

        #theirAlleySlot {
            top: calc(50% - var(--azuki-top-center-gap) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-lane-gap));
        }

        #myGardenSlot {
            bottom: auto;
            top: calc(50% + var(--azuki-bottom-center-gap));
            z-index: 36;
        }

        #myAlleySlot {
            bottom: auto;
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap));
        }

        #myGardenWrapper,
        #theirGardenWrapper,
        #myAlleyWrapper,
        #theirAlleyWrapper {
            margin: -10px -6px -12px;
            padding: 10px 6px 12px;
            border-radius: 8px;
        }

        #myGarden > span[id] > a > img,
        #theirGarden > span[id] > a > img,
        #myAlley > span[id] > a > img,
        #theirAlley > span[id] > a > img {
            width: var(--azuki-field-card-size) !important;
            height: var(--azuki-field-card-size) !important;
        }

        #myIKZAreaSlot,
        #theirIKZAreaSlot {
            left: calc(50% - var(--azuki-field-shift));
            box-sizing: border-box;
            width: var(--azuki-field-w);
            height: var(--azuki-field-h);
            min-height: var(--azuki-field-h);
            padding: 8px 18px;
            transform: translateX(-50%);
            z-index: 35;
            overflow: visible;
            border: 1px solid rgba(137, 178, 199, 0.09);
            border-radius: 11px;
            background: linear-gradient(180deg, rgba(29, 51, 70, 0.96), rgba(25, 45, 63, 0.96));
            box-shadow: inset 0 1px 0 rgba(232, 247, 255, 0.025), 0 10px 24px rgba(7, 15, 25, 0.12);
        }

        #myIKZAreaWrapper,
        #theirIKZAreaWrapper {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible !important;
        }

        #theirIKZAreaSlot {
            top: calc(50% - var(--azuki-top-center-gap) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-lane-gap) - var(--azuki-lane-gap));
        }

        #myIKZAreaSlot {
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap) + var(--azuki-field-h) + var(--azuki-lane-gap));
            bottom: auto;
        }

        #myIKZArea,
        #theirIKZArea {
            width: var(--azuki-ikz-row-w) !important;
            max-width: var(--azuki-ikz-row-w) !important;
            height: 68px !important;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-content: center !important;
            align-items: flex-start !important;
            justify-content: center !important;
        }

        #myIKZArea > *,
        #theirIKZArea > * {
            width: var(--azuki-ikz-card-size) !important;
            margin: 0 -22px 0 0 !important;
        }

        #myIKZArea > span[id] > a > img,
        #theirIKZArea > span[id] > a > img {
            width: var(--azuki-ikz-card-size) !important;
            height: var(--azuki-ikz-card-size) !important;
        }

        #myIKZArea > *:last-child,
        #theirIKZArea > *:last-child {
            margin-right: 0 !important;
        }

        #myIKZArea > span:not([id]):not(.azuki-ikz-token-card),
        #theirIKZArea > span:not([id]):not(.azuki-ikz-token-card),
        #myGate > span:not([id]),
        #theirGate > span:not([id]) {
            display: none !important;
        }

        .azuki-ikz-token-card {
            width: var(--azuki-ikz-card-size) !important;
            flex: 0 0 var(--azuki-ikz-card-size);
            position: relative;
            z-index: 2;
            cursor: help;
        }

        .azuki-ikz-token-card img {
            display: block;
            width: var(--azuki-ikz-card-size) !important;
            height: var(--azuki-ikz-card-size) !important;
            border-radius: 7px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.42);
        }

        #myIKZTokenSlot,
        #theirIKZTokenSlot {
            display: none !important;
        }

        #myGateSlot,
        #theirGateSlot,
        #myDeckSlot,
        #theirDeckSlot,
        #myDiscardSlot,
        #theirDiscardSlot {
            right: var(--azuki-pile-right);
        }

        #theirGateSlot { top: 86px; }
        #theirDeckSlot { top: 196px; }
        #theirDiscardSlot { top: 306px; }

        #myGateSlot,
        #myDeckSlot,
        #myDiscardSlot {
            right: auto;
            left: var(--azuki-my-pile-left);
            z-index: 38;
        }

        #myGateSlot {
            top: calc(50% + var(--azuki-bottom-center-gap));
            bottom: auto;
        }

        #myDeckSlot {
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap));
            bottom: auto;
        }

        #myDiscardSlot {
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap) + var(--azuki-field-h) + var(--azuki-lane-gap));
            bottom: auto;
        }

        #myLeaderHealthSlot,
        #theirLeaderHealthSlot {
            right: calc(var(--azuki-pile-right) + 120px);
            width: 96px;
            min-height: 58px;
            border: 1px solid rgba(137, 178, 199, 0.12);
            border-radius: 8px;
            background: rgba(17, 34, 49, 0.72);
        }

        #theirLeaderHealthSlot {
            display: none;
        }

        #myLeaderHealthSlot {
            right: auto;
            left: calc(var(--azuki-my-pile-left) + 4px);
            top: calc(50% + var(--azuki-bottom-center-gap) - 64px);
            bottom: auto;
            z-index: 40;
        }

        #myLeaderHealthWrapper {
            overflow: visible !important;
        }

        #myLeaderHealth {
            min-height: 56px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: nowrap !important;
        }

        #myLeaderHealth > span {
            display: none !important;
        }

        #myLeaderHealth > div {
            padding-left: 0 !important;
        }

        #myLeaderHealth .widget-button-pass {
            min-width: 92px;
            background: linear-gradient(180deg, rgba(39, 45, 49, 0.96) 0%, rgba(16, 20, 23, 0.98) 100%);
            border-color: rgba(244, 237, 219, 0.34);
            color: #f5f0e4;
            box-shadow: 0 7px 16px rgba(0, 0, 0, 0.42);
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }

        #myLeaderHealth .widget-button-pass:hover {
            background: linear-gradient(180deg, rgba(58, 64, 68, 0.98) 0%, rgba(24, 29, 33, 1) 100%);
            border-color: rgba(255, 248, 232, 0.68);
            color: #fffaf0;
            box-shadow: 0 9px 20px rgba(0, 0, 0, 0.5);
        }

        #myLeaderHealth .widget-button-pass.azuki-pass-idle {
            background: linear-gradient(180deg, rgba(31, 85, 49, 0.98) 0%, rgba(12, 42, 25, 1) 100%);
            border-color: rgba(106, 248, 150, 0.9);
            color: #effff3;
            box-shadow: 0 0 0 1px rgba(89, 244, 139, 0.28), 0 0 14px rgba(51, 231, 105, 0.62), 0 8px 18px rgba(0, 0, 0, 0.42);
            animation: azuki-pass-idle-glow 1.7s ease-in-out infinite alternate;
        }

        #myLeaderHealth .widget-button-pass.azuki-pass-idle:hover {
            background: linear-gradient(180deg, rgba(45, 111, 65, 1) 0%, rgba(16, 57, 32, 1) 100%);
            border-color: rgba(149, 255, 180, 1);
        }

        @keyframes azuki-pass-idle-glow {
            from { box-shadow: 0 0 0 1px rgba(89, 244, 139, 0.22), 0 0 10px rgba(51, 231, 105, 0.42), 0 8px 18px rgba(0, 0, 0, 0.42); }
            to { box-shadow: 0 0 0 2px rgba(106, 248, 150, 0.38), 0 0 22px rgba(51, 231, 105, 0.78), 0 8px 18px rgba(0, 0, 0, 0.42); }
        }

        #myHandSlot,
        #theirHandSlot {
            box-sizing: border-box;
            width: min(64vw, 1040px);
            min-height: 98px;
            padding: 0 8px;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        #myHandSlot {
            left: calc(50% - var(--azuki-field-shift));
            bottom: -48px;
            transform: translateX(-50%);
        }

        #theirHandSlot {
            left: calc(50% - var(--azuki-field-shift));
            top: -48px;
            transform: translateX(-50%);
        }

        #myHandSlot:hover,
        #theirHandSlot:hover {
            transform: translateX(-50%);
            border-color: transparent;
            box-shadow: none;
        }

        #myHandSlot.is-collapsed,
        #myHandSlot.is-collapsed:hover {
            transform: translateX(-50%) translateY(calc(100% - 66px)) !important;
        }

        #theirHandSlot.is-collapsed,
        #theirHandSlot.is-collapsed:hover {
            transform: translateX(-50%) translateY(calc(-100% + 18px)) !important;
        }

        .azuki-hand-collapse-btn {
            background: rgba(17, 34, 49, 0.86);
            border-color: rgba(165, 202, 220, 0.3);
        }

        #azukiResponseOpportunity {
            top: calc(50% - 24px);
            bottom: auto;
            border-color: rgba(165, 202, 220, 0.42);
            background: rgba(17, 34, 49, 0.96);
        }

        #chatWidget {
            right: 8px !important;
            left: auto !important;
            bottom: 8px !important;
            align-items: flex-end !important;
        }
    }

    @media (max-width: 1000px) {
        :root {
            --azuki-mobile-topbar-h: 34px;
            --azuki-mobile-gap: 6px;
            --azuki-mobile-hand-h: 74px;
        }

        #chatWidget {
            position: fixed !important;
            top: 4px !important;
            left: 8px !important;
            bottom: auto !important;
            width: auto !important;
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
        }

        #myIKZTokenSlot,
        #theirIKZTokenSlot {
            left: 164px !important;
            right: auto !important;
        }

        #theirIKZTokenSlot {
            top: 56px !important;
            bottom: auto !important;
        }

        #myIKZTokenSlot {
            top: auto !important;
            bottom: 56px !important;
        }

        #chatToggleBtn {
            margin-top: 0 !important;
            height: 28px !important;
        }

        #bug-report-button,
        #concede-button {
            position: fixed !important;
            top: 4px !important;
            bottom: auto !important;
            padding: 6px 12px !important;
            font-size: 12px !important;
        }

        #concede-button {
            right: 8px !important;
        }

        #bug-report-button {
            right: 96px !important;
        }

        #regressionControls,
        #manualControls {
            top: calc(var(--azuki-mobile-topbar-h) + var(--azuki-mobile-gap)) !important;
            right: 8px !important;
            left: auto !important;
            max-width: min(320px, calc(100vw - 16px));
        }

        #manualControls {
            top: calc(var(--azuki-mobile-topbar-h) + var(--azuki-mobile-gap) + 190px) !important;
        }

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
        <span id="azukiResponseOpportunityText" class="azuki-opportunity-subtitle">You may play a [Response] card.</span>
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

    var responsePassSubmitting = false;

    window.AzukiResponsePass = function() {
        if(responsePassSubmitting) return false;
        responsePassSubmitting = true;
        SubmitInput('10001', '&cardID=' + encodeURIComponent('myLeaderHealthSlot!CustomInput!Pass'));
        return true;
    };

    window.TryAzukiResponsePassHotkey = function() {
        var state = responseState();
        var passBtn = document.getElementById('azukiResponsePassBtn');
        if(!state.active || getViewerPlayer() !== state.responder || !passBtn) return false;
        if(passBtn.style.display === 'none' || window.getComputedStyle(passBtn).display === 'none') return false;
        return window.AzukiResponsePass();
    };

    function installResponsePassHotkey() {
        document.addEventListener('keydown', function(event) {
            if(event.code !== 'Space' && event.keyCode !== 32) return;
            if(event.repeat || event.ctrlKey || event.metaKey || event.altKey) return;

            var activeElement = document.activeElement;
            if(activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA' || activeElement.isContentEditable)) {
                return;
            }

            if(window.TryAzukiResponsePassHotkey()) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
    }

    window.UpdateAzukiResponseOpportunity = function() {
        var panel = document.getElementById('azukiResponseOpportunity');
        var subtitle = document.getElementById('azukiResponseOpportunityText');
        var passBtn = document.getElementById('azukiResponsePassBtn');
        if(!panel || !subtitle || !passBtn) return;

        var state = responseState();
        if(!state.active) {
            responsePassSubmitting = false;
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
            // If property interception is unavailable, fallback polling keeps the panel in sync.
            setInterval(function() {
                if(typeof window.UpdateAzukiResponseOpportunity === 'function') {
                    window.UpdateAzukiResponseOpportunity();
                }
            }, 200);
        }
    }

    function setupHandCollapse() {
        var slot = document.getElementById('myHandSlot');
        var theirSlot = document.getElementById('theirHandSlot');
        if(!slot) return;
        var storageKey = 'azuki-hand-collapsed-v1';
        var collapsed = false;
        try { collapsed = localStorage.getItem(storageKey) === '1'; } catch (e) {}

        function createBtn() {
            var btn = document.createElement('button');
            btn.className = 'azuki-hand-collapse-btn';
            btn.setAttribute('type', 'button');
            btn.setAttribute('title', 'Collapse/expand hand');
            btn.textContent = collapsed ? '\u25b2' : '\u25bc';
            btn.setAttribute('aria-label', collapsed ? 'Expand hand' : 'Collapse hand');
            btn.addEventListener('click', function(ev) {
                ev.stopPropagation();
                setCollapsed(!slot.classList.contains('is-collapsed'));
            });
            return btn;
        }

        function ensureButton() {
            if(!slot.querySelector('.azuki-hand-collapse-btn')) {
                slot.insertBefore(createBtn(), slot.firstChild);
            }
        }

        function setCollapsed(nextCollapsed) {
            collapsed = nextCollapsed;
            slot.classList.toggle('is-collapsed', nextCollapsed);
            if(theirSlot) theirSlot.classList.toggle('is-collapsed', nextCollapsed);
            var btn = slot.querySelector('.azuki-hand-collapse-btn');
            if(btn) {
                btn.textContent = nextCollapsed ? '\u25b2' : '\u25bc';
                btn.setAttribute('aria-label', nextCollapsed ? 'Expand hand' : 'Collapse hand');
            }
            try { localStorage.setItem(storageKey, nextCollapsed ? '1' : '0'); } catch (e) {}
        }

        window.GAHandCollapse = {
            toggle: function() { setCollapsed(!slot.classList.contains('is-collapsed')); },
            collapse: function() { setCollapsed(true); },
            expand: function() { setCollapsed(false); }
        };

        new MutationObserver(function() { ensureButton(); })
            .observe(slot, { childList: true });

        ensureButton();
        if(collapsed) {
            slot.classList.add('is-collapsed');
            if(theirSlot) theirSlot.classList.add('is-collapsed');
        }
    }

    function setupLaneScrollButtons() {
        function installForSlot(slotId, wrapperId) {
            var slot = document.getElementById(slotId);
            if(!slot) return;
            var leftBtn = null;
            var rightBtn = null;
            var storageKey = 'azuki-lane-scroll-v1-' + wrapperId;
            var lastKnownScrollLeft = 0;

            function ensureButton(side) {
                var existing = slot.querySelector('.azuki-lane-scroll-btn-' + side);
                if(existing) return existing;
                var btn = document.createElement('button');
                btn.className = 'azuki-lane-scroll-btn azuki-lane-scroll-btn-' + side + ' is-hidden is-disabled';
                btn.setAttribute('type', 'button');
                btn.setAttribute('aria-label', side === 'left' ? 'Scroll lane left' : 'Scroll lane right');
                btn.textContent = side === 'left' ? '\u2039' : '\u203a';
                slot.appendChild(btn);
                return btn;
            }

            function ensureButtons() {
                leftBtn = ensureButton('left');
                rightBtn = ensureButton('right');
            }

            function getWrapper() {
                return document.getElementById(wrapperId);
            }

            function readSavedScrollLeft() {
                try {
                    var raw = localStorage.getItem(storageKey);
                    var parsed = parseFloat(raw || '');
                    return Number.isFinite(parsed) ? Math.max(0, parsed) : 0;
                } catch (e) {
                    return 0;
                }
            }

            function saveScrollLeft(value) {
                lastKnownScrollLeft = Math.max(0, value);
                try {
                    localStorage.setItem(storageKey, String(lastKnownScrollLeft));
                } catch (e) {}
            }

            function restoreScrollLeft() {
                var wrapper = getWrapper();
                if(!wrapper) return;
                var maxScroll = Math.max(0, wrapper.scrollWidth - wrapper.clientWidth);
                var target = Math.min(maxScroll, Math.max(0, lastKnownScrollLeft));
                if(Math.abs(wrapper.scrollLeft - target) <= 1) return;
                wrapper.scrollLeft = target;
            }

            function updateButtons() {
                ensureButtons();
                var wrapper = getWrapper();
                if(!wrapper) {
                    leftBtn.classList.add('is-hidden');
                    rightBtn.classList.add('is-hidden');
                    return;
                }
                var maxScroll = Math.max(0, wrapper.scrollWidth - wrapper.clientWidth);
                var hasOverflow = maxScroll > 6;
                var canScrollLeft = hasOverflow && wrapper.scrollLeft > 4;
                var canScrollRight = hasOverflow && wrapper.scrollLeft < maxScroll - 4;
                leftBtn.classList.toggle('is-hidden', !hasOverflow);
                rightBtn.classList.toggle('is-hidden', !hasOverflow);
                leftBtn.classList.toggle('is-disabled', !canScrollLeft);
                rightBtn.classList.toggle('is-disabled', !canScrollRight);
            }

            function scrollByAmount(direction) {
                var wrapper = getWrapper();
                if(!wrapper) return;
                var amount = Math.max(180, Math.floor(wrapper.clientWidth * 0.72));
                wrapper.scrollBy({ left: direction * amount, behavior: 'smooth' });
                window.setTimeout(updateButtons, 220);
            }

            function bindButtonHandlers(btn, direction) {
                if(!btn || btn.dataset.azukiScrollBound === '1') return;
                btn.dataset.azukiScrollBound = '1';
                btn.addEventListener('click', function(ev) {
                    ev.preventDefault();
                    if(btn.classList.contains('is-disabled')) return;
                    scrollByAmount(direction);
                });
            }

            function bindWrapper() {
                var wrapper = getWrapper();
                if(!wrapper || wrapper.dataset.azukiScrollButtonsBound === '1') return;
                wrapper.dataset.azukiScrollButtonsBound = '1';
                wrapper.addEventListener('scroll', function() {
                    saveScrollLeft(wrapper.scrollLeft);
                    updateButtons();
                }, { passive: true });
                restoreScrollLeft();
            }

            new MutationObserver(function() {
                ensureButtons();
                bindButtonHandlers(leftBtn, -1);
                bindButtonHandlers(rightBtn, 1);
                bindWrapper();
                window.requestAnimationFrame(function() {
                    restoreScrollLeft();
                    updateButtons();
                });
                updateButtons();
            }).observe(slot, { childList: true, subtree: true });

            lastKnownScrollLeft = readSavedScrollLeft();
            ensureButtons();
            bindButtonHandlers(leftBtn, -1);
            bindButtonHandlers(rightBtn, 1);
            bindWrapper();
            restoreScrollLeft();
            updateButtons();
            window.addEventListener('resize', function() {
                restoreScrollLeft();
                updateButtons();
            });
        }

        installForSlot('myGardenSlot', 'myGardenWrapper');
        installForSlot('theirGardenSlot', 'theirGardenWrapper');
        installForSlot('myAlleySlot', 'myAlleyWrapper');
        installForSlot('theirAlleySlot', 'theirAlleyWrapper');
    }

    function setupIKZTokenIndicator() {
        var tokenCardID = 'IKZ-002_IKZ!_IKZ-Token_Die';

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

        function syncTokenCard(prefix, dataKey, zoneId) {
            var slot = document.getElementById(prefix + 'IKZTokenSlot');
            if(slot) slot.style.display = 'none';

            var hasToken = readTokenValue(dataKey, zoneId) > 0;
            var area = document.getElementById(prefix + 'IKZArea');
            if(!area) return;

            var tokenCard = area.querySelector('.azuki-ikz-token-card');
            if(!hasToken) {
                if(tokenCard) tokenCard.remove();
                return;
            }

            if(!tokenCard) {
                tokenCard = document.createElement('span');
                tokenCard.className = 'azuki-ikz-token-card';
                tokenCard.setAttribute('title', 'IKZ Token');
                tokenCard.setAttribute('aria-label', 'IKZ Token');
                tokenCard.innerHTML =
                    '<a onmouseover="ShowCardDetail(event, this)" onmouseout="HideCardDetail()">' +
                        '<img src="./AzukiSim/concat/' + tokenCardID + '.webp" alt="IKZ Token">' +
                    '</a>';
                tokenCard.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
                area.appendChild(tokenCard);
            } else if(tokenCard !== area.lastElementChild) {
                area.appendChild(tokenCard);
            }
        }

        function update() {
            syncTokenCard('my', 'myIKZTokenData', 'myIKZToken');
            syncTokenCard('their', 'theirIKZTokenData', 'theirIKZToken');
        }

        function observeZone(zoneId) {
            var zone = document.getElementById(zoneId);
            if(!zone) return;
            new MutationObserver(update).observe(zone, { childList: true, subtree: true, characterData: true });
        }

        observeZone('myIKZToken');
        observeZone('theirIKZToken');
        update();
        window.setInterval(update, 250);
    }

    function setupPassAvailabilityGlow() {
        var pending = false;

        function viewerHasPassPriority() {
            var viewer = getViewerPlayer();
            if(viewer !== 1 && viewer !== 2) return false;

            var state = responseState();
            if(state.active) return viewer === state.responder;

            var turnPlayer = parseInt(window.TurnPlayerData, 10);
            if(viewer !== turnPlayer) return false;

            if(typeof _firstPendingDecisionFromRaw === 'function'
                && _firstPendingDecisionFromRaw(window.myDecisionQueueData)) {
                return false;
            }

            if(typeof _shouldShowOpponentWaitingMessage === 'function') {
                return !_shouldShowOpponentWaitingMessage(true);
            }

            var theirQueue = window.theirDecisionQueueData;
            return !(typeof theirQueue === 'string' && theirQueue.trim() !== '');
        }

        function update() {
            pending = false;
            var passButton = document.querySelector('#myLeaderHealth .widget-button-pass');
            if(!passButton) return;
            passButton.classList.toggle('azuki-pass-idle', viewerHasPassPriority() && !document.querySelector('.selectable-card'));
        }

        function scheduleUpdate() {
            if(pending) return;
            pending = true;
            window.requestAnimationFrame(update);
        }

        new MutationObserver(scheduleUpdate).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class']
        });
        update();
        window.setInterval(update, 250);
    }

    installResponseWatcher();
    installResponsePassHotkey();
    setupHandCollapse();
    setupLaneScrollButtons();
    setupIKZTokenIndicator();
    setupPassAvailabilityGlow();
    window.UpdateAzukiResponseOpportunity();
})();
</script>
