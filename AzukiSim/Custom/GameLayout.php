<?php
// GameLayout.php — Container divs for all BindTo zones in AzukiSim.
// Included from InitialLayout.php after the main split-screen structure.
require_once __DIR__ . '/GameLayoutDevice.php';
?>
<script>window.AzukiSoundAssetVersion = <?php echo json_encode(strval(filemtime(dirname(__DIR__) . '/Assets/Sounds/generation-manifest.json'))); ?>;</script>
<script src="./AzukiSim/Custom/SoundDesign.js?v=<?php echo filemtime(__DIR__ . '/SoundDesign.js'); ?>"></script>
<style>
    :root {
        /* Shared Decision Queue popups can be skinned per app through these tokens. */
        --mz-rearrange-overlay-bg: rgba(3, 3, 4, 0.88);
        --mz-rearrange-overlay-filter: blur(7px);
        --mz-rearrange-font: var(--azuki-font-ui, "Segoe UI Variable Display", "Aptos", sans-serif);
        --mz-rearrange-modal-bg: linear-gradient(145deg, rgba(29, 28, 29, 0.995), rgba(12, 12, 14, 0.995));
        --mz-rearrange-modal-border: rgba(181, 55, 65, 0.56);
        --mz-rearrange-modal-shadow: 0 20px 60px rgba(0, 0, 0, 0.72), 0 0 34px rgba(126, 25, 35, 0.18), inset 0 1px 0 rgba(255, 248, 235, 0.04);
        --mz-rearrange-header-bg: linear-gradient(145deg, rgba(29, 28, 29, 0.99), rgba(16, 15, 17, 0.99));
        --mz-rearrange-header-text: rgba(244, 237, 224, 0.94);
        --mz-rearrange-control-border: rgba(226, 216, 198, 0.2);
        --mz-rearrange-control-bg: rgba(226, 216, 198, 0.07);
        --mz-rearrange-control-text: #f4ede0;
        --mz-rearrange-divider: linear-gradient(90deg, rgba(181, 55, 65, 0.52), rgba(226, 216, 198, 0.07));
        --mz-rearrange-title-text: rgba(247, 240, 228, 0.98);
        --mz-rearrange-title-shadow: 0 0 22px rgba(126, 25, 35, 0.3);
        --mz-rearrange-pile-bg: rgba(255, 248, 235, 0.018);
        --mz-rearrange-pile-border: rgba(226, 216, 198, 0.14);
        --mz-rearrange-pile-title: rgba(217, 93, 104, 0.96);
        --mz-rearrange-pile-divider: rgba(226, 216, 198, 0.1);
        --mz-rearrange-card-bg: rgba(255, 248, 235, 0.045);
        --mz-rearrange-accent: #b53741;
        --mz-rearrange-drag-bg: rgba(126, 25, 35, 0.2);
        --mz-rearrange-drag-shadow: 0 0 28px rgba(181, 55, 65, 0.28);
        --mz-rearrange-selectable-border: rgba(181, 55, 65, 0.72);
        --mz-rearrange-selected-border: #e5c36a;
        --mz-rearrange-selected-bg: rgba(105, 79, 24, 0.3);
        --mz-rearrange-selected-shadow: 0 0 18px rgba(229, 195, 106, 0.38);
        --mz-rearrange-select-button-border: rgba(181, 55, 65, 0.78);
        --mz-rearrange-select-button-bg: linear-gradient(180deg, #832530, #57171e);
        --mz-rearrange-select-button-text: #f7f0e4;
        --mz-rearrange-selected-button-bg: linear-gradient(180deg, #856b2d, #5f491c);
        --mz-rearrange-card-hover-shadow: 0 8px 20px rgba(126, 25, 35, 0.32);
        --mz-rearrange-placeholder-bg: rgba(181, 55, 65, 0.1);
        --mz-rearrange-placeholder-active-bg: rgba(181, 55, 65, 0.2);
        --mz-rearrange-order-bg: linear-gradient(145deg, #c74450, #7e1923);
        --mz-rearrange-order-text: #fff8eb;
        --mz-rearrange-order-shadow: 0 2px 8px rgba(126, 25, 35, 0.5);
        --mz-rearrange-instructions-text: rgba(226, 216, 198, 0.62);
        --mz-rearrange-empty-text: rgba(226, 216, 198, 0.38);

        --mz-choose-overlay-bg: rgba(3, 3, 4, 0.82);
        --mz-choose-font: var(--azuki-font-ui, "Segoe UI Variable Display", "Aptos", sans-serif);
        --mz-choose-panel-bg: linear-gradient(145deg, rgba(29, 28, 29, 0.985), rgba(12, 12, 14, 0.985));
        --mz-choose-panel-border: rgba(181, 55, 65, 0.48);
        --mz-choose-panel-radius: 18px;
        --mz-choose-panel-shadow: 0 24px 64px rgba(0, 0, 0, 0.66), 0 0 38px rgba(126, 25, 35, 0.15), inset 0 1px 0 rgba(255, 248, 235, 0.04);
        --mz-choose-panel-filter: blur(12px) saturate(110%);
        --mz-choose-header-text: rgba(244, 237, 224, 0.94);
        --mz-choose-control-border: rgba(226, 216, 198, 0.22);
        --mz-choose-control-bg: rgba(226, 216, 198, 0.07);
        --mz-choose-control-text: #f4ede0;
        --mz-choose-divider: linear-gradient(90deg, rgba(181, 55, 65, 0.56), rgba(226, 216, 198, 0.07));
        --mz-choose-title-text: rgba(247, 240, 228, 0.98);
        --mz-choose-card-hover-shadow: 0 0 0 2px rgba(181, 55, 65, 0.78), 0 10px 28px rgba(126, 25, 35, 0.42);
        --mz-choose-zone-label-bg: rgba(12, 12, 14, 0.9);
        --mz-choose-zone-label-text: #f7f0e4;
        --mz-choose-pass-bg: linear-gradient(180deg, #9d2d38, #661a23);
        --mz-choose-pass-text: #f7f0e4;
        --mz-choose-pass-border: rgba(217, 93, 104, 0.86);
        --mz-choose-pass-shadow: inset 0 1px 0 rgba(255, 224, 226, 0.16), 0 12px 28px rgba(92, 17, 25, 0.42);
        --mz-choose-pass-hover-border: rgba(239, 135, 144, 0.94);
        --mz-choose-pass-hover-shadow: inset 0 1px 0 rgba(255, 235, 237, 0.22), 0 16px 34px rgba(92, 17, 25, 0.54);
        --mz-choose-pass-active-shadow: inset 0 2px 5px rgba(44, 6, 11, 0.5), 0 7px 18px rgba(92, 17, 25, 0.4);
        --mz-choose-pass-focus: rgba(239, 135, 144, 0.94);
    }

    .mzrearrange-btn-reset {
        --btn-surface: linear-gradient(180deg, #292729, #171719);
        --btn-fill: linear-gradient(180deg, #292729, #171719);
        --btn-border: rgba(226, 216, 198, 0.3);
        --btn-text: #f4ede0;
        --btn-hover-surface: linear-gradient(180deg, #353235, #201e20);
        --btn-hover-border: rgba(226, 216, 198, 0.5);
        --btn-glow-color: rgba(226, 216, 198, 0.14);
    }

    .mzrearrange-btn-submit {
        --btn-surface: linear-gradient(180deg, #9d2d38, #661a23);
        --btn-fill: linear-gradient(180deg, #9d2d38, #661a23);
        --btn-border: rgba(217, 93, 104, 0.86);
        --btn-text: #f7f0e4;
        --btn-hover-surface: linear-gradient(180deg, #b53742, #7e1f29);
        --btn-hover-border: rgba(239, 135, 144, 0.94);
        --btn-glow-color: rgba(181, 55, 65, 0.3);
    }
</style>
<?php
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
        /* Three Above-flow weapon subcards can sit up to 30px above their entity.
         * A horizontal scroller necessarily clips on the other axis, so reserve
         * paint room inside the scrollport and cancel it with the outer margin. */
        padding-top: 34px;
        padding-bottom: 14px;
        margin-top: -34px;
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
            /* NextTurn.php updates card-size from both viewport axes. Keeping all
             * desktop geometry on this token prevents large displays from mixing
             * 160px rendered cards with laptop-sized 96/104px containers. */
            --azuki-card-size: 96px;
            --azuki-zone-gap: clamp(8px, 0.9vw, 14px);
            --azuki-side-w: var(--azuki-card-size);
            /* Seven cards remain visible on a large display. On narrower desktop
             * viewports the lane contracts and its existing scroller takes over. */
            --azuki-lane-w: min(72vw, 760px);
            --azuki-lane-half-w: min(36vw, 380px);
            --azuki-board-w: calc(var(--azuki-lane-w) + var(--azuki-side-w) + var(--azuki-side-w) + var(--azuki-zone-gap) + var(--azuki-zone-gap));
            --azuki-board-left: calc((100vw - var(--azuki-board-w)) / 2);
            --azuki-garden-w: calc(var(--azuki-board-w) - var(--azuki-side-w) - var(--azuki-zone-gap));
            --azuki-resource-w: var(--azuki-lane-w);
            --azuki-hand-w: min(84vw, 1160px, calc(var(--azuki-board-left) + var(--azuki-lane-w) - 24px));
            --azuki-hand-left: calc(var(--azuki-board-left) + var(--azuki-lane-w) - var(--azuki-hand-w));
            --azuki-field-w: var(--azuki-lane-w);
            --azuki-field-h: calc(var(--azuki-card-size) + 20px);
            --azuki-lane-gap: clamp(6px, 0.7vh, 8px);
            --azuki-top-center-gap: clamp(12px, 1.4vh, 14px);
            --azuki-bottom-center-gap: clamp(18px, 2.2vh, 20px);
            --azuki-pile-w: var(--azuki-side-w);
            --azuki-rail-left: var(--azuki-board-left);
            --azuki-rail-card-w: var(--azuki-side-w);
            --azuki-ikz-row-w: min(30vw, 460px);
            --azuki-field-card-size: var(--azuki-card-size);
            --azuki-ikz-card-size: clamp(68px, 8vh, 96px);
            --azuki-ink: #0b0b0d;
            --azuki-panel-top: rgba(27, 27, 29, 0.94);
            --azuki-panel-bottom: rgba(15, 15, 17, 0.96);
            --azuki-hairline: rgba(226, 216, 198, 0.09);
            --azuki-muted-ivory: rgba(226, 216, 198, 0.5);
        }

        #mainDiv {
            background: var(--azuki-ink) !important;
        }

        .stuffParent,
        .theirStuffWrapper,
        .myStuffWrapper,
        #myStuff,
        #theirStuff {
            background: transparent !important;
        }

        .azuki-board-bg {
            z-index: 10;
            background:
                radial-gradient(ellipse 58% 30% at 50% 50%, rgba(140, 24, 35, 0.09), transparent 72%),
                radial-gradient(circle at 82% 10%, rgba(173, 35, 45, 0.045), transparent 30%),
                linear-gradient(180deg, #19191b 0%, #141416 49.72%, #101012 49.9%, #0b0b0d 100%);
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
            background: linear-gradient(90deg, transparent 0%, rgba(126, 25, 35, 0.18) 18%, rgba(226, 216, 198, 0.16) 50%, rgba(126, 25, 35, 0.18) 82%, transparent 100%);
            box-shadow: 0 1px 22px rgba(0, 0, 0, 0.42);
        }

        .azuki-board-bg::after {
            top: 0;
            bottom: 0;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.42), transparent 16%, transparent 80%, rgba(0, 0, 0, 0.34));
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
            border: 1px solid var(--azuki-hairline);
            border-radius: 11px;
            background: linear-gradient(180deg, var(--azuki-panel-top), var(--azuki-panel-bottom));
            box-shadow: inset 0 1px 0 rgba(255, 248, 235, 0.025), 0 10px 26px rgba(0, 0, 0, 0.24);
        }

        .azuki-field::before {
            display: none;
        }

        #myGardenSlot,
        #theirGardenSlot,
        #myAlleySlot,
        #theirAlleySlot {
            transform: none;
        }

        #myGardenSlot {
            left: var(--azuki-board-left);
            width: var(--azuki-garden-w);
        }

        #theirGardenSlot {
            left: var(--azuki-board-left);
            width: var(--azuki-garden-w);
        }

        #myAlleySlot {
            left: var(--azuki-board-left);
            width: var(--azuki-lane-w);
        }

        #theirAlleySlot {
            left: var(--azuki-board-left);
            width: var(--azuki-lane-w);
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
            margin: -34px -6px -12px;
            padding: 34px 6px 12px;
            border-radius: 8px;
        }

        /* Keep the aligned Leader inside the lane while extending only the
         * scrollport edge used to paint its glow and action affordances. */
        #myGardenWrapper {
            margin-right: -58px;
            padding-right: 58px;
        }

        #theirGardenWrapper {
            margin-right: -58px;
            padding-right: 58px;
        }

        #myGarden > span[id] > a > img,
        #theirGarden > span[id] > a > img,
        #myAlley > span[id] > a > img,
        #theirAlley > span[id] > a > img {
            width: var(--azuki-field-card-size) !important;
            height: var(--azuki-field-card-size) !important;
        }

        /* The leader is the stable first Garden object. Put it in the dedicated
         * end-cap recommended by the tabletop mat while preserving its real
         * Garden zone identity and click behavior. */
        #myGarden > span[id="myGarden-0"] {
            order: 0;
            margin-left: 0 !important;
            margin-right: 0 !important;
            transform: translateX(21.6px);
        }

        #theirGarden > span[id="theirGarden-0"] {
            order: 0;
            margin-left: 0 !important;
            margin-right: 0 !important;
            transform: translateX(21.6px);
        }

        /* Mirror both entity flows around the shared right-side leader track.
         * Index 1 is nearest the leader; later cards continue toward the left. */
        #myGarden,
        #myAlley,
        #theirGarden,
        #theirAlley {
            flex-direction: row-reverse !important;
            justify-content: flex-start !important;
        }

        #myGardenSlot::after,
        #theirGardenSlot::after {
            display: none;
        }

        #myGardenSlot::after { right: 10px; }
        #theirGardenSlot::after { left: 10px; }

        #myIKZAreaSlot,
        #theirIKZAreaSlot {
            box-sizing: border-box;
            width: var(--azuki-resource-w);
            height: var(--azuki-field-h);
            min-height: var(--azuki-field-h);
            padding: 8px 18px;
            transform: none;
            z-index: 35;
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        #myIKZAreaSlot::before,
        #theirIKZAreaSlot::before {
            display: none;
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

        #myIKZAreaWrapper {
            transform: translateY(-16px);
        }

        #theirIKZAreaWrapper {
            transform: translateY(16px);
        }

        #theirIKZAreaSlot {
            top: calc(50% - var(--azuki-top-center-gap) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-lane-gap) - var(--azuki-lane-gap));
            left: var(--azuki-board-left);
        }

        #myIKZAreaSlot {
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap) + var(--azuki-field-h) + var(--azuki-lane-gap));
            bottom: auto;
            left: var(--azuki-board-left);
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
            right: auto;
            box-sizing: border-box;
            width: var(--azuki-pile-w);
            min-height: var(--azuki-card-size);
            padding-left: 0;
            padding-right: 0;
        }

        /* Deck and discard are generated with overflow-y:auto, but Single-mode
         * piles never need an internal scroller. At laptop widths the reserved
         * scrollbar gutter makes the square wrapper overflow horizontally too,
         * producing the paired scrollbars seen beside and below the card. */
        #myDeckSlot,
        #theirDeckSlot,
        #myDiscardSlot,
        #theirDiscardSlot,
        #myDeckWrapper,
        #theirDeckWrapper,
        #myDiscardWrapper,
        #theirDiscardWrapper,
        #myDeck,
        #theirDeck,
        #myDiscard,
        #theirDiscard {
            overflow: visible !important;
            scrollbar-width: none;
        }

        #myDeckWrapper,
        #theirDeckWrapper,
        #myDiscardWrapper,
        #theirDiscardWrapper {
            width: var(--azuki-pile-w);
            min-height: var(--azuki-card-size);
        }

        #myDeckWrapper::-webkit-scrollbar,
        #theirDeckWrapper::-webkit-scrollbar,
        #myDiscardWrapper::-webkit-scrollbar,
        #theirDiscardWrapper::-webkit-scrollbar {
            display: none;
        }

        #theirGateSlot {
            top: calc(50% - var(--azuki-top-center-gap) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-lane-gap) + 10px);
            left: calc(var(--azuki-board-left) + var(--azuki-lane-w) + var(--azuki-zone-gap));
        }

        #theirDeckSlot {
            top: calc(50% - var(--azuki-top-center-gap) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-lane-gap) - var(--azuki-lane-gap));
            left: calc(var(--azuki-board-left) + var(--azuki-resource-w) + var(--azuki-zone-gap));
        }

        #theirDiscardSlot {
            top: calc(50% - var(--azuki-top-center-gap) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-field-h) - var(--azuki-lane-gap) - var(--azuki-lane-gap));
            left: calc(var(--azuki-board-left) + var(--azuki-resource-w) + var(--azuki-zone-gap) + var(--azuki-side-w) + var(--azuki-zone-gap));
        }

        #myGateSlot,
        #myDeckSlot,
        #myDiscardSlot {
            right: auto;
            z-index: 38;
        }

        #myGateSlot {
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap) + 10px);
            bottom: auto;
            left: calc(var(--azuki-board-left) + var(--azuki-lane-w) + var(--azuki-zone-gap));
        }

        #myDeckSlot {
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap) + var(--azuki-field-h) + var(--azuki-lane-gap));
            bottom: auto;
            left: calc(var(--azuki-board-left) + var(--azuki-resource-w) + var(--azuki-zone-gap));
        }

        #myDiscardSlot {
            top: calc(50% + var(--azuki-bottom-center-gap) + var(--azuki-field-h) + var(--azuki-lane-gap) + var(--azuki-field-h) + var(--azuki-lane-gap));
            bottom: auto;
            left: calc(var(--azuki-board-left) + var(--azuki-resource-w) + var(--azuki-zone-gap) + var(--azuki-side-w) + var(--azuki-zone-gap));
        }

        #myGateSlot,
        #theirGateSlot,
        #myDeckSlot,
        #theirDeckSlot,
        #myDiscardSlot,
        #theirDiscardSlot {
            height: var(--azuki-field-h);
            min-height: var(--azuki-field-h);
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        #myGateSlot::before,
        #theirGateSlot::before,
        #myDeckSlot::before,
        #theirDeckSlot::before,
        #myDiscardSlot::before,
        #theirDiscardSlot::before {
            content: attr(data-label);
            position: absolute;
            top: 7px;
            left: 0;
            right: 0;
            z-index: 2;
            color: var(--azuki-muted-ivory);
            font: 700 9px/1 var(--azuki-font-label);
            letter-spacing: 0.13em;
            text-align: center;
            text-transform: uppercase;
            pointer-events: none;
        }

        #myGate > span[id] > a > img,
        #theirGate > span[id] > a > img,
        #myDeck > span[id] > a > img,
        #theirDeck > span[id] > a > img,
        #myDiscard > span[id] > a > img,
        #theirDiscard > span[id] > a > img {
            width: var(--azuki-card-size) !important;
            height: var(--azuki-card-size) !important;
        }

        #myLeaderHealthSlot,
        #theirLeaderHealthSlot {
            right: var(--azuki-board-left);
            width: 96px;
            min-height: 58px;
            border: 1px solid rgba(226, 216, 198, 0.12);
            border-radius: 8px;
            background: rgba(15, 15, 17, 0.78);
        }

        #theirLeaderHealthSlot {
            display: none;
        }

        #myLeaderHealthSlot {
            right: auto;
            left: calc(var(--azuki-board-left) + var(--azuki-board-w) - var(--azuki-side-w) - 5px);
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
            width: var(--azuki-hand-w);
            min-height: calc(var(--azuki-card-size) + 2px);
            padding: 0 8px;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        #myHand > span[id] > a > img,
        #theirHand > span[id] > a > img {
            width: var(--azuki-card-size) !important;
            height: var(--azuki-card-size) !important;
        }

        #myHandWrapper,
        #theirHandWrapper {
            overflow: visible !important;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        #myHandWrapper::-webkit-scrollbar,
        #theirHandWrapper::-webkit-scrollbar {
            display: none;
        }

        #myHandSlot {
            left: var(--azuki-hand-left);
            bottom: -48px;
            transform: none;
        }

        #theirHandSlot {
            left: var(--azuki-hand-left);
            top: -48px;
            transform: none;
        }

        #myHandSlot:hover,
        #theirHandSlot:hover {
            transform: none;
            border-color: transparent;
            box-shadow: none;
        }

        #myHandSlot.is-collapsed,
        #myHandSlot.is-collapsed:hover {
            transform: translateY(calc(100% - 66px)) !important;
        }

        #theirHandSlot.is-collapsed,
        #theirHandSlot.is-collapsed:hover {
            transform: translateY(calc(-100% + 18px)) !important;
        }

        .azuki-hand-collapse-btn {
            left: calc(100% - var(--azuki-lane-half-w));
            color: rgba(235, 226, 209, 0.78);
            background: rgba(17, 17, 19, 0.94);
            border-color: rgba(226, 216, 198, 0.24);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.32);
        }

        #azukiResponseOpportunity {
            top: calc(50% - 24px);
            bottom: auto;
            border-color: rgba(226, 216, 198, 0.22);
            background: rgba(15, 14, 15, 0.97);
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.46), inset 0 1px 0 rgba(255, 248, 235, 0.035);
        }

        .azuki-lane-scroll-btn {
            color: rgba(235, 226, 209, 0.82);
            border-color: rgba(226, 216, 198, 0.2);
            background: linear-gradient(180deg, rgba(30, 29, 30, 0.98), rgba(13, 13, 15, 0.96));
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.38);
        }

        #manualControls,
        #regressionControls {
            color: rgba(235, 226, 209, 0.9) !important;
            border-color: rgba(226, 216, 198, 0.2) !important;
            background: rgba(14, 14, 16, 0.95) !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.42) !important;
        }

        #bug-report-button {
            border-color: rgba(166, 49, 59, 0.58) !important;
            background: rgba(23, 14, 16, 0.96) !important;
            box-shadow: 0 0 14px rgba(139, 34, 44, 0.22) !important;
        }

        #copy-spectate-link-button {
            border-color: rgba(226, 216, 198, 0.24) !important;
            background: rgba(16, 16, 18, 0.96) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.34) !important;
        }

        #concede-button {
            border-color: rgba(166, 49, 59, 0.52) !important;
            background: rgba(28, 12, 15, 0.96) !important;
            box-shadow: 0 0 14px rgba(139, 34, 44, 0.2) !important;
        }

        #macro-card-toast-toggle {
            border-color: rgba(226, 216, 198, 0.18) !important;
            background: rgba(14, 14, 16, 0.94) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.34) !important;
        }

        .mzmodal-overlay {
            background: rgba(0, 0, 0, 0.72) !important;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
        }

        .mzmodal-panel {
            color: rgba(244, 237, 224, 0.94) !important;
            border-color: rgba(226, 216, 198, 0.2) !important;
            background: linear-gradient(145deg, rgba(29, 28, 29, 0.99), rgba(12, 12, 14, 0.99)) !important;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.62), 0 0 36px rgba(126, 25, 35, 0.08) !important;
        }

        .mzmodal-title {
            color: rgba(244, 237, 224, 0.94) !important;
        }

        .mzmodal-option {
            color: rgba(244, 237, 224, 0.9) !important;
            border-color: rgba(226, 216, 198, 0.12) !important;
            background: rgba(255, 248, 235, 0.025) !important;
        }

        .mzmodal-option:hover:not(.mzmodal-option-disabled) {
            border-color: rgba(226, 216, 198, 0.3) !important;
            background: rgba(255, 248, 235, 0.055) !important;
        }

        .mzmodal-check {
            border-color: rgba(226, 216, 198, 0.32) !important;
        }

        .mzmodal-option.mzmodal-option-selected {
            border-color: rgba(181, 55, 65, 0.82) !important;
            background: rgba(126, 25, 35, 0.22) !important;
            box-shadow: 0 0 14px rgba(139, 34, 44, 0.2) !important;
        }

        .mzmodal-option-selected .mzmodal-check {
            border-color: #9d2d38 !important;
            background: #9d2d38 !important;
        }

        .mzmodal-counter {
            color: rgba(226, 216, 198, 0.58) !important;
        }

        #mzmodal-submit {
            color: #f7f0e4 !important;
            border-color: rgba(181, 55, 65, 0.78) !important;
            background: linear-gradient(180deg, #9d2d38, #661a23) !important;
            box-shadow: 0 8px 20px rgba(92, 17, 25, 0.34) !important;
        }

        #mzmodal-submit:disabled {
            color: rgba(226, 216, 198, 0.42) !important;
            border-color: rgba(226, 216, 198, 0.1) !important;
            background: rgba(51, 49, 50, 0.88) !important;
            box-shadow: none !important;
        }

        #yesno-decision-modal.yesno-decision-has-references[data-review-zone="myHand"] {
            background: rgba(3, 3, 4, 0.78) !important;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        #yesno-decision-modal.yesno-decision-has-references[data-review-zone="myHand"] .yesno-decision-panel {
            color: rgba(244, 237, 224, 0.94) !important;
            border: 1px solid rgba(226, 216, 198, 0.18);
            border-top-color: rgba(181, 55, 65, 0.5);
            border-radius: 16px !important;
            background: linear-gradient(145deg, rgba(29, 28, 29, 0.995), rgba(12, 12, 14, 0.995)) !important;
            box-shadow: 0 26px 70px rgba(0, 0, 0, 0.66), 0 0 42px rgba(126, 25, 35, 0.12), inset 0 1px 0 rgba(255, 248, 235, 0.035) !important;
            font-family: var(--azuki-font-ui) !important;
        }

        #yesno-decision-modal.yesno-decision-has-references .yesno-decision-prompt {
            color: rgba(247, 240, 228, 0.96) !important;
            font-family: var(--azuki-font-ui) !important;
            font-weight: 700 !important;
        }

        #yesno-decision-modal.yesno-decision-has-references .yesno-decision-reference-label {
            color: rgba(217, 93, 104, 0.94) !important;
            font-family: var(--azuki-font-label) !important;
        }

        #yesno-decision-modal.yesno-decision-has-references .yesno-decision-reference-cards {
            padding: 14px 10px 12px !important;
            border: 1px solid rgba(226, 216, 198, 0.08);
            border-radius: 12px;
            background: rgba(255, 248, 235, 0.018);
            scrollbar-color: rgba(126, 25, 35, 0.78) rgba(12, 12, 14, 0.82);
        }

        #yesno-decision-modal.yesno-decision-has-references .namecard-preview-image {
            border: 1px solid rgba(226, 216, 198, 0.12);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.48) !important;
        }

        #yesno-decision-modal.yesno-decision-has-references .namecard-preview-label {
            color: rgba(235, 226, 209, 0.84) !important;
            font-family: var(--azuki-font-ui) !important;
        }

        #yesno-decision-modal.yesno-decision-has-references .yesno-decision-buttons button {
            border: 1px solid transparent !important;
            border-radius: 8px !important;
            font-family: var(--azuki-font-label) !important;
            font-weight: 800 !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.34);
        }

        #yesno-decision-modal.yesno-decision-has-references .yesno-decision-yes {
            color: #f7f0e4 !important;
            border-color: rgba(181, 55, 65, 0.78) !important;
            background: linear-gradient(180deg, #9d2d38, #661a23) !important;
        }

        #yesno-decision-modal.yesno-decision-has-references .yesno-decision-no {
            color: #171719 !important;
            border-color: rgba(244, 237, 224, 0.5) !important;
            background: linear-gradient(180deg, #e8dfd0, #bdb3a4) !important;
        }

        #yesno-decision-modal.yesno-decision-has-references .yesno-decision-buttons button:hover {
            filter: brightness(1.1);
        }

        #chatWidget {
            right: 8px !important;
            left: auto !important;
            bottom: 8px !important;
            width: 248px !important;
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

<div id="myGardenSlot" class="azuki-zone azuki-field" data-label="">
</div>

<div id="myAlleySlot" class="azuki-zone azuki-field" data-label="">
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

<div id="myHandSlot" class="azuki-zone azuki-hand" data-label="">
</div>

<!-- =================== THEIR ZONES (top half) =================== -->

<div id="theirGardenSlot" class="azuki-zone azuki-field" data-label="">
</div>

<div id="theirAlleySlot" class="azuki-zone azuki-field" data-label="">
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

<div id="theirHandSlot" class="azuki-zone azuki-hand" data-label="">
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

    function setupHandPlayAreaAlignment() {
        function install(prefix) {
            var slotId = prefix + 'HandSlot';
            var handId = prefix + 'Hand';
            var laneId = prefix + 'AlleySlot';
            var slot = document.getElementById(slotId);
            if(!slot) return;
            var pending = false;

            function update() {
                pending = false;
                var hand = document.getElementById(handId);
                var lane = document.getElementById(laneId);
                if(!hand || !lane) return;

                hand.style.transform = 'translateX(0px)';
                var cards = Array.from(hand.querySelectorAll(':scope > span[id]'));
                if(!cards.length) return;

                var cardRects = cards.map(function(card) { return card.getBoundingClientRect(); });
                var cardsLeft = Math.min.apply(null, cardRects.map(function(rect) { return rect.left; }));
                var cardsRight = Math.max.apply(null, cardRects.map(function(rect) { return rect.right; }));
                var laneRect = lane.getBoundingClientRect();
                var slotRect = slot.getBoundingClientRect();
                var idealShift = ((laneRect.left + laneRect.right) / 2) - ((cardsLeft + cardsRight) / 2);
                var minimumShift = (slotRect.left + 8) - cardsLeft;
                var maximumShift = (laneRect.right - 8) - cardsRight;
                var shift = Math.min(maximumShift, Math.max(minimumShift, idealShift));
                hand.style.transform = 'translateX(' + shift.toFixed(2) + 'px)';
            }

            function scheduleUpdate() {
                if(pending) return;
                pending = true;
                window.requestAnimationFrame(update);
            }

            new MutationObserver(scheduleUpdate).observe(slot, { childList: true, subtree: true });
            window.addEventListener('resize', scheduleUpdate);
            scheduleUpdate();
        }

        install('my');
        install('their');
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
    setupHandPlayAreaAlignment();
    setupIKZTokenIndicator();
    setupPassAvailabilityGlow();
    window.UpdateAzukiResponseOpportunity();
})();
</script>
<?php include __DIR__ . '/RematchClient.php'; ?>
