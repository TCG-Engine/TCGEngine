<?php
// GameLayout.php — Container divs for all BindTo zones in GrandArchiveSim.
// Included from InitialLayout.php after the main split-screen structure.
// Edit this file to change the visual layout of zones. Regenerating the schema
// does NOT overwrite this file.
//
// Naming convention:
//   Player-scoped zones: my{BindTo} and their{BindTo}
//   Global zones:        {BindTo}  (no prefix)
?>
<style>
     :root {
          --ga-ink: #13202b;
          --ga-ink-soft: rgba(19, 32, 43, 0.72);
          --ga-brass: #c89b46;
          --ga-brass-soft: rgba(200, 155, 70, 0.26);
          --ga-ivory: #f4ecdb;
          --ga-teal: #2d6f73;
          --ga-wine: #8a514f;
          --ga-shadow: 0 18px 50px rgba(7, 14, 20, 0.28);
          --ga-font-ui: "Aptos", "Segoe UI Variable Display", "Trebuchet MS", sans-serif;
          --ga-font-label: "Bahnschrift", "Aptos Display", "Franklin Gothic Medium", sans-serif;
     }

     /* Override shared NextTurn.php styling: remove the gold frame around my play area for GA. */
     #myStuff {
          border: 0 !important;
     }

     .ga-board-art,
     .ga-board-glow,
     .ga-board-axis {
          position: fixed;
          inset: 0;
          pointer-events: none;
     }

     .ga-board-art {
          z-index: 11;
          background:
               radial-gradient(circle at 18% 20%, rgba(45, 111, 115, 0.18), transparent 24%),
               radial-gradient(circle at 82% 78%, rgba(138, 81, 79, 0.16), transparent 26%),
               linear-gradient(180deg, rgba(244, 236, 219, 0.08), rgba(19, 32, 43, 0));
          mix-blend-mode: screen;
     }

     .ga-board-glow {
          z-index: 12;
          background:
               radial-gradient(circle at 50% 50%, rgba(200, 155, 70, 0.11), transparent 26%),
               radial-gradient(circle at 50% 16%, rgba(244, 236, 219, 0.1), transparent 18%),
               radial-gradient(circle at 50% 84%, rgba(244, 236, 219, 0.1), transparent 18%);
     }

     .ga-board-axis {
          z-index: 13;
     }

     .ga-board-axis::before,
     .ga-board-axis::after {
          content: "";
          position: absolute;
          left: 50%;
          transform: translateX(-50%);
          width: min(900px, calc(100vw - 120px));
          border-radius: 999px;
     }

     .ga-board-axis::before {
          top: calc(50% - 2px);
          height: 4px;
          background: linear-gradient(90deg, transparent, var(--ga-brass-soft), rgba(244, 236, 219, 0.45), var(--ga-brass-soft), transparent);
          box-shadow: 0 0 25px rgba(200, 155, 70, 0.24);
     }

     .ga-board-axis::after {
          top: calc(50% - 24px);
          height: 48px;
          border: 1px solid rgba(244, 236, 219, 0.14);
          background: linear-gradient(90deg, transparent, rgba(244, 236, 219, 0.07), transparent);
     }

     .ga-side-banner {
          position: fixed;
          left: 50%;
          transform: translateX(-50%);
          width: min(420px, calc(100vw - 88px));
          height: 42px;
          z-index: 15;
          pointer-events: none;
          border: 1px solid rgba(244, 236, 219, 0.14);
          border-radius: 999px;
          box-shadow: 0 10px 30px rgba(7, 14, 20, 0.22);
          overflow: hidden;
     }

     .ga-side-banner::before {
          content: "";
          position: absolute;
          inset: 0;
          background:
               linear-gradient(90deg, rgba(19, 32, 43, 0.9), rgba(19, 32, 43, 0.72) 38%, rgba(19, 32, 43, 0.58)),
               linear-gradient(135deg, rgba(244, 236, 219, 0.09), transparent 45%),
               repeating-linear-gradient(90deg, transparent 0 22px, rgba(244, 236, 219, 0.03) 22px 23px);
     }

     .ga-side-banner::after {
          content: attr(data-label);
          position: absolute;
          left: 22px;
          top: 13px;
          color: rgba(244, 236, 219, 0.92);
          text-transform: uppercase;
          letter-spacing: 0.22em;
          font: 700 10px/1 var(--ga-font-label);
     }

     .ga-side-banner-top {
          top: 16px;
     }

     .ga-side-banner-top::before {
          background:
               linear-gradient(90deg, rgba(45, 111, 115, 0.42), rgba(19, 32, 43, 0.82) 32%, rgba(19, 32, 43, 0.72)),
               linear-gradient(135deg, rgba(244, 236, 219, 0.09), transparent 45%),
               repeating-linear-gradient(90deg, transparent 0 22px, rgba(244, 236, 219, 0.03) 22px 23px);
     }

     .ga-side-banner-bottom {
          bottom: 16px;
     }

     .ga-side-banner-bottom::before {
          background:
               linear-gradient(90deg, rgba(138, 81, 79, 0.35), rgba(19, 32, 43, 0.82) 32%, rgba(19, 32, 43, 0.72)),
               linear-gradient(135deg, rgba(244, 236, 219, 0.09), transparent 45%),
               repeating-linear-gradient(90deg, transparent 0 22px, rgba(244, 236, 219, 0.03) 22px 23px);
     }

     .ga-phase-track {
          position: fixed;
          left: 50%;
          top: calc(50% + 7px);
          transform: translateX(-50%);
          z-index: 16;
          pointer-events: none;
          width: min(900px, calc(100vw - 120px));
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
          color: rgba(244, 236, 219, 0.62);
          text-transform: uppercase;
          letter-spacing: 0.14em;
          font: 700 10px/1 var(--ga-font-label);
          text-shadow: 0 1px 8px rgba(7, 14, 20, 0.55);
     }

     .ga-phase-step {
          position: relative;
          padding: 0 2px;
          white-space: nowrap;
          opacity: 0.82;
          transition: color 140ms ease, opacity 140ms ease, text-shadow 140ms ease;
     }

     .ga-phase-step::before {
          content: "";
          position: absolute;
          left: -7px;
          top: 50%;
          transform: translateY(-50%);
          width: 3px;
          height: 3px;
          border-radius: 50%;
          background: rgba(244, 236, 219, 0.38);
          box-shadow: 0 0 8px rgba(244, 236, 219, 0.30);
     }

     .ga-phase-step:first-child::before {
          display: none;
     }

     .ga-phase-step.is-active {
          color: rgba(252, 238, 171, 0.98);
          opacity: 1;
          text-shadow: 0 0 16px rgba(252, 221, 120, 0.68), 0 0 26px rgba(200, 155, 70, 0.52);
     }

     .ga-zone {
          position: fixed;
          z-index: 30;
          pointer-events: auto;
     }

     /* Glass panel — applied only to Hand, Intent, Effect Stack */
     .ga-glass {
          border: 1px solid rgba(244, 236, 219, 0.16);
          border-radius: 26px;
          background:
               linear-gradient(180deg, rgba(244, 236, 219, 0.13), rgba(255, 255, 255, 0.02)),
               linear-gradient(160deg, rgba(19, 32, 43, 0.72), rgba(19, 32, 43, 0.52));
          box-shadow: 0 20px 52px rgba(7, 14, 20, 0.30), inset 0 1px 0 rgba(255, 255, 255, 0.13);
          backdrop-filter: blur(14px) saturate(140%);
          -webkit-backdrop-filter: blur(14px) saturate(140%);
          padding: 30px 14px 12px;
          transition: transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
     }

     .ga-glass::before {
          content: attr(data-label);
          position: absolute;
          top: 10px;
          left: 14px;
          right: 14px;
          color: rgba(244, 236, 219, 0.82);
          text-transform: uppercase;
          letter-spacing: 0.24em;
          font: 700 11px/1 var(--ga-font-label);
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          pointer-events: none;
     }

     .ga-glass::after {
          content: "";
          position: absolute;
          left: 14px;
          right: 14px;
          top: 24px;
          height: 1px;
          background: linear-gradient(90deg, var(--ga-brass-soft), rgba(244, 236, 219, 0.05));
          pointer-events: none;
     }

     .ga-glass:hover {
          transform: translateY(-3px);
          border-color: rgba(200, 155, 70, 0.38);
          box-shadow: 0 26px 58px rgba(7, 14, 20, 0.36), inset 0 1px 0 rgba(255, 255, 255, 0.16);
     }

     /* Hand slots: no label/divider, tight padding, flush to screen edge */
     #myHandSlot.ga-glass,
     #theirHandSlot.ga-glass {
          padding: 6px 8px;
     }

     #myHandSlot.ga-glass {
          border-bottom-left-radius: 0;
          border-bottom-right-radius: 0;
     }

     #theirHandSlot.ga-glass {
          border-top-left-radius: 0;
          border-top-right-radius: 0;
     }

     #myHandSlot.ga-glass::before,
     #theirHandSlot.ga-glass::before,
     #myHandSlot.ga-glass::after,
     #theirHandSlot.ga-glass::after {
          display: none;
     }

     /* Hide the fallback zone-name text that PopulateZone renders when the zone is empty.
        Empty-state spans have no id; real card spans do (e.g. myHand-0). */
     #myHand > span:not([id]),
     #theirHand > span:not([id]) {
          display: none;
     }

     .ga-zone > [id$="Wrapper"] {
          position: relative;
          z-index: 1;
     }

     /* Pile wrappers don't need visible scrollbars in this layout. */
     #myDeckWrapper,
     #theirDeckWrapper,
     #myBanishWrapper,
     #theirBanishWrapper,
     #myGraveyardWrapper,
     #theirGraveyardWrapper,
     #myMaterialWrapper,
     #theirMaterialWrapper,
     #myMasteryWrapper,
     #theirMasteryWrapper,
     #myMemoryWrapper,
     #theirMemoryWrapper {
          overflow: hidden !important;
          scrollbar-width: none;
          -ms-overflow-style: none;
     }

     #myDeckWrapper::-webkit-scrollbar,
     #theirDeckWrapper::-webkit-scrollbar,
     #myBanishWrapper::-webkit-scrollbar,
     #theirBanishWrapper::-webkit-scrollbar,
     #myGraveyardWrapper::-webkit-scrollbar,
     #theirGraveyardWrapper::-webkit-scrollbar,
     #myMaterialWrapper::-webkit-scrollbar,
     #theirMaterialWrapper::-webkit-scrollbar,
     #myMasteryWrapper::-webkit-scrollbar,
     #theirMasteryWrapper::-webkit-scrollbar,
     #myMemoryWrapper::-webkit-scrollbar,
     #theirMemoryWrapper::-webkit-scrollbar {
          display: none;
     }

     /* Better zero state for memory than raw zone-name text. */
     #myMemorySlot.ga-memory-empty,
     #theirMemorySlot.ga-memory-empty {
          box-sizing: border-box;
          border: 1px solid rgba(244, 236, 219, 0.24);
          border-radius: 14px;
          background:
               linear-gradient(180deg, rgba(244, 236, 219, 0.08), rgba(255, 255, 255, 0.02)),
               linear-gradient(160deg, rgba(19, 32, 43, 0.72), rgba(19, 32, 43, 0.56));
          box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10), 0 10px 26px rgba(7, 14, 20, 0.20);
     }

     #myMemorySlot.ga-memory-empty::before,
     #theirMemorySlot.ga-memory-empty::before {
          content: attr(data-label) " (0)";
          position: absolute;
          left: 50%;
          top: 50%;
          transform: translate(-50%, -50%);
          color: rgba(244, 236, 219, 0.82);
          text-transform: uppercase;
          letter-spacing: 0.12em;
          font: 700 11px/1 var(--ga-font-label);
          white-space: nowrap;
          pointer-events: none;
     }

     #myMemorySlot.ga-memory-empty #myMemory > span,
     #theirMemorySlot.ga-memory-empty #theirMemory > span {
          display: none;
     }

     /* Zero state for material slot — slightly smaller than full token-bank */
     #myMaterialSlot.ga-material-empty,
     #theirMaterialSlot.ga-material-empty {
          box-sizing: border-box;
          width: 96px;
          min-height: 96px;
          border: 1px solid rgba(244, 236, 219, 0.24);
          border-radius: 14px;
          background:
               linear-gradient(180deg, rgba(244, 236, 219, 0.08), rgba(255, 255, 255, 0.02)),
               linear-gradient(160deg, rgba(19, 32, 43, 0.72), rgba(19, 32, 43, 0.56));
          box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10), 0 10px 26px rgba(7, 14, 20, 0.20);
     }

     #myMaterialSlot.ga-material-empty::before,
     #theirMaterialSlot.ga-material-empty::before {
          content: attr(data-label) " (0)";
          position: absolute;
          left: 50%;
          top: 50%;
          transform: translate(-50%, -50%);
          color: rgba(244, 236, 219, 0.82);
          text-transform: uppercase;
          letter-spacing: 0.12em;
          font: 700 11px/1 var(--ga-font-label);
          white-space: nowrap;
          pointer-events: none;
     }

     #myMaterialSlot.ga-material-empty #myMaterial > span,
     #theirMaterialSlot.ga-material-empty #theirMaterial > span {
          display: none;
     }

     /* Zero state for banish slot */
     #myBanishSlot.ga-banish-empty,
     #theirBanishSlot.ga-banish-empty {
          box-sizing: border-box;
          min-height: 96px;
          border: 1px solid rgba(244, 236, 219, 0.24);
          border-radius: 14px;
          background:
               linear-gradient(180deg, rgba(244, 236, 219, 0.08), rgba(255, 255, 255, 0.02)),
               linear-gradient(160deg, rgba(19, 32, 43, 0.72), rgba(19, 32, 43, 0.56));
          box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10), 0 10px 26px rgba(7, 14, 20, 0.20);
     }

     #myBanishSlot.ga-banish-empty::before,
     #theirBanishSlot.ga-banish-empty::before {
          content: attr(data-label) " (0)";
          position: absolute;
          left: 50%;
          top: 50%;
          transform: translate(-50%, -50%);
          color: rgba(244, 236, 219, 0.82);
          text-transform: uppercase;
          letter-spacing: 0.12em;
          font: 700 11px/1 var(--ga-font-label);
          white-space: nowrap;
          pointer-events: none;
     }

     #myBanishSlot.ga-banish-empty #myBanish > span,
     #theirBanishSlot.ga-banish-empty #theirBanish > span {
          display: none;
     }

     /* Zero state for graveyard slot */
     #myGraveyardSlot.ga-graveyard-empty,
     #theirGraveyardSlot.ga-graveyard-empty {
          box-sizing: border-box;
          min-height: 96px;
          border: 1px solid rgba(244, 236, 219, 0.24);
          border-radius: 14px;
          background:
               linear-gradient(180deg, rgba(244, 236, 219, 0.08), rgba(255, 255, 255, 0.02)),
               linear-gradient(160deg, rgba(19, 32, 43, 0.72), rgba(19, 32, 43, 0.56));
          box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10), 0 10px 26px rgba(7, 14, 20, 0.20);
     }

     #myGraveyardSlot.ga-graveyard-empty::before,
     #theirGraveyardSlot.ga-graveyard-empty::before {
          content: attr(data-label) " (0)";
          position: absolute;
          left: 50%;
          top: 50%;
          transform: translate(-50%, -50%);
          color: rgba(244, 236, 219, 0.82);
          text-transform: uppercase;
          letter-spacing: 0.12em;
          font: 700 11px/1 var(--ga-font-label);
          white-space: nowrap;
          pointer-events: none;
     }

     #myGraveyardSlot.ga-graveyard-empty #myGraveyard > span,
     #theirGraveyardSlot.ga-graveyard-empty #theirGraveyard > span {
          display: none;
     }

     .ga-pile {
          width: 92px;
          min-height: 96px;
     }

     .ga-stat {
          width: 132px;
          min-height: 82px;
     }

     .ga-token-bank {
          width: 128px;
          min-height: 126px;
     }

     #myMaterialSlot,
     #theirMaterialSlot,
     #myMasterySlot,
     #theirMasterySlot {
          width: 96px;
          min-height: 96px;
     }

     .ga-hand {
          width: min(58vw, 1040px);
          min-height: 118px;
     }

     .ga-field {
          width: min(54vw, 980px);
          min-height: 154px;
     }

     /* Field lanes: keep one horizontal row and scroll sideways when crowded.
        Use y=hidden here because browsers treat y=visible as auto when x is scrollable,
        which creates a vertical scrollbar for rotated (exhausted) cards. */
     #myFieldWrapper,
     #theirFieldWrapper {
          overflow-x: auto !important;
          overflow-y: hidden !important;
          padding-top: 14px;
          padding-bottom: 14px;
          margin-top: -14px;
          margin-bottom: -14px;
          scrollbar-width: thin;
          -webkit-overflow-scrolling: touch;
     }

     #myField,
     #theirField {
          flex-wrap: nowrap !important;
          justify-content: flex-start !important;
          overflow: visible !important;
          min-width: 100%;
     }

     #myField > span,
     #theirField > span {
          flex: 0 0 auto;
     }

     .ga-intent {
          width: min(15vw, 210px);
          min-height: 112px;
     }

     .ga-stack {
          width: min(18vw, 240px);
          min-height: 86px;
     }

     #myHandSlot,
     #theirHandSlot {
          left: 50%;
          transform: translateX(-50%);
     }

     #myFieldSlot,
     #theirFieldSlot {
          left: 50%;
          right: auto;
          transform: translateX(-50%);
     }

     #EffectStackSlot {
          left: 50%;
          transform: translateX(-50%);
     }

          /* Both intent slots share the same center-left position near the midline.
             They auto-hide via JS when empty so they can safely overlap. */
          #myIntentSlot,
          #theirIntentSlot {
               top: calc(50% - 56px);
               left: calc(50% - 280px);
          }

     #myHealthSlot,
     #theirHealthSlot {
          right: 148px;
     }

     #myMaterialSlot,
     #theirMaterialSlot,
     #myMasterySlot,
     #theirMasterySlot {
          left: 24px;
     }

     #myMemorySlot,
     #myGraveyardSlot,
     #theirGraveyardSlot,
     #myDeckSlot,
     #theirDeckSlot,
     #myBanishSlot,
     #theirBanishSlot {
          width: 96px;
     }

     #myMemorySlot,
     #theirMemorySlot {
          width: 96px;
     }

     #myDeckSlot,
     #theirDeckSlot,
     #myBanishSlot,
     #theirBanishSlot,
     #myGraveyardSlot,
     #theirGraveyardSlot {
          right: 24px;
          left: auto;
     }

     #myMemorySlot,
     #theirMemorySlot,
     #myMaterialSlot,
     #theirMaterialSlot {
          left: 24px;
          right: auto;
     }

     @media (max-width: 1200px) {
          .ga-side-banner {
               width: min(360px, calc(100vw - 32px));
          }

          .ga-hand {
               width: min(64vw, 900px);
          }

          .ga-field {
               width: min(62vw, 760px);
          }

          .ga-intent {
               width: min(18vw, 184px);
          }
     }

     @media (max-width: 900px) {
          .ga-glass {
               padding: 26px 10px 10px;
               border-radius: 18px;
          }

          .ga-phase-track {
               top: calc(50% + 6px);
               width: min(760px, calc(100vw - 44px));
               gap: 8px;
               font-size: 10px;
               letter-spacing: 0.11em;
          }

          .ga-phase-step::before {
               left: -6px;
          }

          .ga-glass::before {
               top: 9px;
               left: 10px;
               right: 10px;
               letter-spacing: 0.18em;
               font-size: 10px;
          }

          .ga-glass::after {
               left: 10px;
               right: 10px;
               top: 22px;
          }

          .ga-side-banner {
               height: 34px;
          }

          .ga-side-banner::after {
               top: 11px;
               left: 16px;
               letter-spacing: 0.16em;
               font-size: 9px;
          }

          .ga-pile,
          .ga-token-bank {
               width: 78px;
               min-height: 104px;
          }

          #myBanishSlot.ga-banish-empty,
          #theirBanishSlot.ga-banish-empty,
          #myGraveyardSlot.ga-graveyard-empty,
          #theirGraveyardSlot.ga-graveyard-empty {
               min-height: 78px;
          }

          .ga-stat {
               width: 108px;
               min-height: 68px;
          }

          .ga-intent {
               width: 140px;
               min-height: 104px;
          }

          .ga-hand {
               width: calc(100vw - 72px);
               min-height: 112px;
          }

          .ga-field {
               width: calc(100vw - 84px);
               min-height: 142px;
          }

          #myMemorySlot,
          #theirMemorySlot,
          #myMaterialSlot,
          #theirMaterialSlot {
               left: 10px;
               right: auto;
          }

          #myGraveyardSlot,
          #theirGraveyardSlot,
          #myDeckSlot,
          #theirDeckSlot,
          #myBanishSlot,
          #theirBanishSlot {
               right: 10px;
               left: auto;
          }
     }

     /* GA override: taper turn-edge markers toward screen top/bottom. */
     #turn-miasma-overlay .turn-edge-glyph {
          width: 34px;
          height: min(64vh, 520px);
     }

     #turn-miasma-overlay .turn-edge-glyph::before,
     #turn-miasma-overlay .turn-edge-glyph::after {
          width: 10px;
          transform: translateX(-50%);
          border-radius: 0;
     }

     #turn-miasma-overlay .turn-edge-glyph::before {
          clip-path: polygon(50% 0, 100% 100%, 0 100%);
     }

     #turn-miasma-overlay .turn-edge-glyph::after {
          clip-path: polygon(0 0, 100% 0, 50% 100%);
     }

     @media (max-width: 900px) {
          #turn-miasma-overlay .turn-edge-glyph {
               width: 26px;
               height: min(56vh, 400px);
          }

          #turn-miasma-overlay .turn-edge-glyph::before,
          #turn-miasma-overlay .turn-edge-glyph::after {
               width: 8px;
          }
     }
</style>

<div class="ga-board-art"></div>
<div class="ga-board-glow"></div>
<div class="ga-board-axis"></div>
<div id="gaPhaseTrack" class="ga-phase-track" aria-live="polite" aria-label="Turn phases">
     <span class="ga-phase-step" data-phase-step="WU">Wake Up</span>
     <span class="ga-phase-step" data-phase-step="MAT">Materialize</span>
     <span class="ga-phase-step" data-phase-step="RECOLLECTION">Recollect</span>
     <span class="ga-phase-step" data-phase-step="DRAW">Draw</span>
     <span class="ga-phase-step" data-phase-step="MAIN">Main</span>
     <span class="ga-phase-step" data-phase-step="END">End</span>
</div>

<!-- =================== MY ZONES (bottom half) =================== -->

<!-- myDeckSlot: bottom-right corner -->
<div id="myDeckSlot" class="ga-zone ga-pile"
           data-label="Deck"
           style="bottom:126px; right:30px;">
</div>

<!-- myBanishSlot: above deck -->
<div id="myBanishSlot" class="ga-zone ga-pile"
           data-label="Banish"
           style="bottom:232px; right:30px;">
</div>

<!-- myGraveyardSlot: bottom-right -->
<div id="myGraveyardSlot" class="ga-zone ga-pile"
           data-label="Graveyard"
           style="bottom:20px; right:30px;">
</div>

<!-- myHandSlot: bottom-center -->
<div id="myHandSlot" class="ga-zone ga-glass ga-hand"
           data-label=""
           style="bottom:0;">
</div>

<!-- myFieldSlot: upper-right of bottom half -->
<div id="myFieldSlot" class="ga-zone ga-field"
           data-label="Field"
           style="top:calc(50% + 40px); overflow-y:visible;">
</div>

<!-- myIntentSlot: bottom-left stack area -->
<div id="myIntentSlot" class="ga-zone ga-glass ga-intent"
           data-label="Intent">
</div>

<!-- myMemorySlot: bottom-left corner -->
<div id="myMemorySlot" class="ga-zone ga-pile"
           data-label="Memory"
           style="bottom:20px; left:30px;">
</div>

<!-- myMaterialSlot: mirrors my deck on the left -->
<div id="myMaterialSlot" class="ga-zone ga-token-bank"
           data-label="Material"
           style="bottom:126px; left:30px;">
</div>

<!-- myHealthSlot: top-right of bottom half -->
<div id="myHealthSlot" class="ga-zone ga-stat"
           data-label="Health"
           style="top:calc(50% + 30px); right:148px;">
</div>

<!-- myMasterySlot: mirrors my banish on the left -->
<div id="myMasterySlot" class="ga-zone ga-token-bank"
           data-label="Mastery"
           style="bottom:232px; left:30px;">
</div>

<!-- =================== THEIR ZONES (top half) =================== -->

<!-- theirDeckSlot: mirrors my deck position in the top half -->
<div id="theirDeckSlot" class="ga-zone ga-pile"
           data-label="Deck"
           style="top:126px; right:30px;">
</div>

<!-- theirBanishSlot: mirrors my banish position in the top half -->
<div id="theirBanishSlot" class="ga-zone ga-pile"
           data-label="Banish"
           style="top:232px; right:30px;">
</div>

<!-- theirGraveyardSlot: mirrors my graveyard in the top half -->
<div id="theirGraveyardSlot" class="ga-zone ga-pile"
           data-label="Graveyard"
           style="top:20px; right:30px;">
</div>

<!-- theirHandSlot: mirrors my hand in the top half -->
<div id="theirHandSlot" class="ga-zone ga-glass ga-hand"
           data-label=""
           style="top:0;">
</div>

<!-- theirFieldSlot: mirrors my field in the top half -->
<div id="theirFieldSlot" class="ga-zone ga-field"
           data-label="Field"
           style="top:calc(50% - 40px - 154px); overflow-y:visible;">
</div>

<!-- theirIntentSlot: mirrors my intent in the top half -->
<div id="theirIntentSlot" class="ga-zone ga-glass ga-intent"
           data-label="Intent">
</div>

<!-- theirMemorySlot: mirrors my memory in the top half -->
<div id="theirMemorySlot" class="ga-zone ga-pile"
           data-label="Memory"
           style="top:20px; left:30px;">
</div>

<!-- theirMaterialSlot: mirrors their deck on the left -->
<div id="theirMaterialSlot" class="ga-zone ga-token-bank"
           data-label="Material"
           style="top:126px; left:30px;">
</div>

<!-- theirHealthSlot: mirrors my health in the top half -->
<div id="theirHealthSlot" class="ga-zone ga-stat"
           data-label="Health"
           style="top:30px; right:148px; display:none;">
</div>

<!-- theirMasterySlot: mirrors their banish on the left -->
<div id="theirMasterySlot" class="ga-zone ga-token-bank"
           data-label="Mastery"
           style="top:232px; left:30px;">
</div>

<!-- =================== GLOBAL ZONES =================== -->

<!-- EffectStackSlot: center of screen (effect queue display) -->
<div id="EffectStackSlot" class="ga-zone ga-glass ga-stack"
           data-label="Effect Stack ('U' to undo)"
           style="top:calc(50% - 43px);">
</div>

<script>
(function() {
     // App-level turn indicator config hook (consumed by Core/UILibraries20260415.js).
     // This keeps ownership/wording customizable per app layout.
     window.TurnIndicatorSettings = {
          showWaitingMessage: true,
              messageAnchorId: 'myHandSlot',
          waitingMessageBuilder: function(ctx) {
               if (!ctx || typeof ctx.defaultBuilder !== 'function') return null;
               return ctx.defaultBuilder();
          }
     };

     var AUTO_HIDE_IDS = ['myIntentSlot', 'theirIntentSlot', 'EffectStackSlot', 'myMasterySlot', 'theirMasterySlot'];
     var EMPTY_STATE_SLOTS = [
          { id: 'myMemorySlot',      cls: 'ga-memory-empty' },
          { id: 'theirMemorySlot',   cls: 'ga-memory-empty' },
          { id: 'myMaterialSlot',    cls: 'ga-material-empty' },
          { id: 'theirMaterialSlot', cls: 'ga-material-empty' },
          { id: 'myBanishSlot',      cls: 'ga-banish-empty' },
          { id: 'theirBanishSlot',   cls: 'ga-banish-empty' },
          { id: 'myGraveyardSlot',   cls: 'ga-graveyard-empty' },
          { id: 'theirGraveyardSlot',cls: 'ga-graveyard-empty' },
     ];

     var PHASE_ALIASES = {
          WU: 'WU',
          WAKEUP: 'WU',
          MAT: 'MAT',
          MATERIALIZE: 'MAT',
          BREC: 'RECOLLECTION',
          REC: 'RECOLLECTION',
          RECOLLECTION: 'RECOLLECTION',
          DRAW: 'DRAW',
          MAIN: 'MAIN',
          BEND: 'END',
          BEOP: 'END',
          END: 'END'
     };

     function normalizePhaseStep(rawPhase) {
          var value = (rawPhase || '').toString().trim();
          if (value === '' || value === '-') return '';
          var key = value.toUpperCase();
          return PHASE_ALIASES[key] || '';
     }

     function updatePhaseTrack() {
          var track = document.getElementById('gaPhaseTrack');
          if (!track) return;
          var raw = (typeof window.CurrentPhaseData === 'string') ? window.CurrentPhaseData : '';
          var normalized = normalizePhaseStep(raw);
          track.setAttribute('data-raw-phase', raw || '-');
          var steps = track.querySelectorAll('[data-phase-step]');
          for (var i = 0; i < steps.length; ++i) {
               var step = steps[i];
               var isActive = step.getAttribute('data-phase-step') === normalized;
               step.classList.toggle('is-active', isActive);
          }
     }

     function hasCards(slot) {
          // PopulateZone renders card items as spans with id like "zoneName-0"
          return slot.querySelector('[id$="-0"]') !== null;
     }

     function refreshVisibility(slot) {
          slot.style.display = hasCards(slot) ? '' : 'none';
     }

     function refreshEmptyState(slot, cls) {
          slot.classList.toggle(cls, !hasCards(slot));
     }

     function watchSlot(id) {
          var el = document.getElementById(id);
          if (!el) return;
          el.style.display = 'none'; // start hidden
          new MutationObserver(function() { refreshVisibility(el); })
               .observe(el, { childList: true, subtree: true });
     }

     function watchEmptyStateSlot(id, cls) {
          var el = document.getElementById(id);
          if (!el) return;
          refreshEmptyState(el, cls);
          new MutationObserver(function() { refreshEmptyState(el, cls); })
               .observe(el, { childList: true, subtree: true });
     }

     function watchPhaseData() {
          updatePhaseTrack();
          var globalStuff = document.getElementById('globalStuff');
          if (!globalStuff) return;
          new MutationObserver(function() { updatePhaseTrack(); })
               .observe(globalStuff, { childList: true, subtree: true });
     }

     // Run once DOM is ready (GameLayout.php is included after DOMContentLoaded equivalent)
     if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', function() {
               AUTO_HIDE_IDS.forEach(watchSlot);
               EMPTY_STATE_SLOTS.forEach(function(s) { watchEmptyStateSlot(s.id, s.cls); });
               watchPhaseData();
          });
     } else {
          AUTO_HIDE_IDS.forEach(watchSlot);
          EMPTY_STATE_SLOTS.forEach(function(s) { watchEmptyStateSlot(s.id, s.cls); });
          watchPhaseData();
     }
})();
</script>
