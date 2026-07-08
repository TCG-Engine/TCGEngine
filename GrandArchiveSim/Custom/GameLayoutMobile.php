<?php
// GameLayoutMobile.php - phone layout for GrandArchiveSim.
//
// Reuses the generated slot IDs, but arranges the phone board as a compact
// split viewport: opponent resources, opponent field, stack/intent, player
// field, player hand, then player resources.
?>
<style>
     html,
     body {
          margin: 0;
          padding: 0;
          overflow: hidden;
          background: #101722;
     }

     :root {
          --ga-m-ink: #111a22;
          --ga-m-panel: rgba(14, 24, 32, 0.88);
          --ga-m-panel-soft: rgba(20, 36, 46, 0.62);
          --ga-m-brass: #c89b46;
          --ga-m-ivory: #f4ecdb;
          --ga-m-teal: #2d6f73;
          --ga-m-wine: #8a514f;
          --ga-font-ui: "Aptos", "Segoe UI Variable Display", "Trebuchet MS", sans-serif;
          --ga-font-label: "Bahnschrift", "Aptos Display", "Franklin Gothic Medium", sans-serif;
     }

     #myStuff,
     #theirStuff {
          border: 0 !important;
          background: transparent !important;
     }

     #gaMobileRoot {
          position: fixed;
          inset: 0;
          z-index: 40;
          height: 100dvh;
          padding-top: 36px;
          overflow: hidden;
          display: grid;
          grid-template-rows: 54px minmax(76px, 1fr) 58px minmax(76px, 1fr) 98px 56px;
          color: rgba(244, 236, 219, 0.96);
          font-family: var(--ga-font-ui);
          background:
               linear-gradient(180deg, rgba(10, 17, 23, 0.72), rgba(10, 20, 26, 0.90)),
               url("/TCGEngine/Assets/Boards/dawn-of-ashes.webp") center center / cover no-repeat;
     }

     #gaMobileRoot,
     #gaMobileRoot * {
          box-sizing: border-box;
     }

     #gaMobileRoot .ga-zone {
          position: static;
          z-index: 1;
          pointer-events: auto;
     }

     .ga-m-band,
     .ga-m-field-section,
     .ga-m-hand-section,
     .ga-m-center-strip {
          min-height: 0;
          overflow: hidden;
          border-bottom: 1px solid rgba(244, 236, 219, 0.08);
     }

     .ga-m-band {
          display: flex;
          align-items: center;
          gap: 4px;
          padding: 3px 6px;
          overflow-x: auto;
          overflow-y: hidden;
          background: rgba(8, 14, 20, 0.84);
          scrollbar-width: none;
          -webkit-overflow-scrolling: touch;
     }

     .ga-m-band::-webkit-scrollbar {
          display: none;
     }

     .ga-m-band.is-mine,
     .ga-m-field-section.is-mine,
     .ga-m-hand-section.is-mine {
          background-color: rgba(45, 111, 115, 0.09);
     }

     .ga-m-band.is-theirs,
     .ga-m-field-section.is-theirs {
          background-color: rgba(138, 81, 79, 0.075);
     }

     .ga-m-label,
     .ga-m-pile-label {
          color: rgba(200, 155, 70, 0.88);
          font: 700 8px/1 var(--ga-font-label);
          letter-spacing: 0.09em;
          text-transform: uppercase;
          white-space: nowrap;
     }

     .ga-m-label {
          flex: 0 0 auto;
          margin: 0 0 4px;
          font-size: 9px;
     }

     .ga-m-pile,
     .ga-m-pass,
     .ga-m-hand-summary {
          flex: 0 0 auto;
          min-width: 40px;
          height: 48px;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 2px;
          overflow: hidden;
     }

     .ga-m-pile {
          width: 40px;
          min-width: 40px;
     }

     .ga-m-pass {
          min-width: 72px;
     }

     .ga-m-hand-summary {
          min-width: 92px;
          flex-direction: row;
          justify-content: flex-start;
          gap: 5px;
          padding: 3px 5px;
          border: 1px solid rgba(200, 155, 70, 0.24);
          border-radius: 8px;
          background: rgba(12, 22, 30, 0.66);
     }

     .ga-m-summary-thumb {
          width: 24px;
          height: 34px;
          flex: 0 0 auto;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 4px;
          overflow: hidden;
          border: 1px solid rgba(244, 236, 219, 0.14);
          background: rgba(7, 12, 17, 0.84);
     }

     .ga-m-summary-thumb img {
          width: 100%;
          height: 100%;
          display: block;
          object-fit: cover;
     }

     .ga-m-summary-meta {
          min-width: 0;
          display: flex;
          flex-direction: column;
          gap: 3px;
     }

     .ga-m-summary-count {
          color: rgba(244, 236, 219, 0.96);
          font: 800 11px/1 var(--ga-font-ui);
          white-space: nowrap;
     }

     .ga-m-pile > div:not(.ga-m-pile-label),
     .ga-m-pass > div {
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

     .ga-m-pile > div:not(.ga-m-pile-label) {
          border-radius: 5px;
          background: rgba(8, 14, 20, 0.48);
          box-shadow: inset 0 0 0 1px rgba(200, 155, 70, 0.16);
     }

     .ga-m-field-section,
     .ga-m-hand-section {
          min-height: 0;
          display: flex;
          flex-direction: column;
          padding: 4px 6px 5px;
     }

     .ga-m-center-strip {
          position: relative;
          display: grid;
          grid-template-columns: minmax(0, 1fr) minmax(128px, 1.05fr) minmax(0, 1fr);
          align-items: center;
          gap: 5px;
          padding: 4px 6px;
          background: rgba(7, 12, 17, 0.72);
     }

     .ga-m-center-strip::before {
          content: "";
          position: absolute;
          left: 8px;
          right: 8px;
          top: 34%;
          height: 1px;
          background: linear-gradient(90deg, transparent, rgba(200, 155, 70, 0.48), transparent);
          pointer-events: none;
     }

     .ga-phase-track {
          position: absolute;
          left: 50%;
          top: calc(50% - 1px);
          transform: translateX(-50%);
          z-index: 2;
          pointer-events: none;
          width: min(342px, calc(100% - 48px));
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 7px;
          color: rgba(244, 236, 219, 0.56);
          font: 700 9px/1 var(--ga-font-label);
          letter-spacing: 0.055em;
          text-align: center;
          text-shadow: 0 1px 8px rgba(7, 14, 20, 0.62);
          text-transform: uppercase;
          white-space: nowrap;
     }

     .ga-phase-step {
          position: relative;
          padding: 0 1px;
          opacity: 0.72;
          transition: color 140ms ease, opacity 140ms ease, text-shadow 140ms ease;
     }

     .ga-phase-step::before {
          content: "";
          position: absolute;
          left: -5px;
          top: 50%;
          transform: translateY(-50%);
          width: 2px;
          height: 2px;
          border-radius: 50%;
          background: rgba(244, 236, 219, 0.36);
          box-shadow: 0 0 7px rgba(244, 236, 219, 0.28);
     }

     .ga-phase-step:first-child::before {
          display: none;
     }

     .ga-phase-step.is-active {
          color: rgba(252, 238, 171, 0.98);
          opacity: 1;
          text-shadow: 0 0 14px rgba(252, 221, 120, 0.72), 0 0 24px rgba(200, 155, 70, 0.56);
     }

     #myFieldSlot,
     #theirFieldSlot,
     #myHandSlot,
     #myIntentSlot,
     #theirIntentSlot,
     #EffectStackSlot {
          min-height: 0;
          overflow: hidden !important;
          padding: 2px;
          border: 1px solid rgba(200, 155, 70, 0.18);
          border-radius: 8px;
          background: rgba(10, 17, 23, 0.55);
     }

     #EffectStackSlot {
          position: absolute !important;
          top: 4px;
          right: 6px;
          width: 64px;
          height: 50px;
          z-index: 3;
          min-height: 44px;
          max-height: 50px;
          background: rgba(21, 34, 44, 0.84);
          box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22);
     }

     #myIntentSlot,
     #theirIntentSlot {
          min-height: 42px;
          max-height: 50px;
     }

     #theirIntentSlot {
          grid-column: 1;
     }

     #myIntentSlot {
          grid-column: 3;
     }

     #myFieldSlot,
     #theirFieldSlot,
     #myHandSlot {
          flex: 1 1 auto;
     }

     #myDeckWrapper,
     #theirDeckWrapper,
     #myBanishWrapper,
     #theirBanishWrapper,
     #myGraveyardWrapper,
     #theirGraveyardWrapper,
     #myMemoryWrapper,
     #theirMemoryWrapper,
     #myMaterialWrapper,
     #theirMaterialWrapper,
     #myMasteryWrapper,
     #theirMasteryWrapper,
     #myHealthWrapper,
     #theirHealthWrapper,
     #myFieldWrapper,
     #theirFieldWrapper,
     #myHandWrapper,
     #theirHandWrapper,
     #myIntentWrapper,
     #theirIntentWrapper,
     #EffectStackWrapper {
          width: 100% !important;
          height: 100% !important;
          min-width: 0 !important;
          min-height: 0 !important;
          overflow: hidden !important;
          scrollbar-width: none;
     }

     #myDeckWrapper::-webkit-scrollbar,
     #theirDeckWrapper::-webkit-scrollbar,
     #myBanishWrapper::-webkit-scrollbar,
     #theirBanishWrapper::-webkit-scrollbar,
     #myGraveyardWrapper::-webkit-scrollbar,
     #theirGraveyardWrapper::-webkit-scrollbar,
     #myMemoryWrapper::-webkit-scrollbar,
     #theirMemoryWrapper::-webkit-scrollbar,
     #myMaterialWrapper::-webkit-scrollbar,
     #theirMaterialWrapper::-webkit-scrollbar,
     #myMasteryWrapper::-webkit-scrollbar,
     #theirMasteryWrapper::-webkit-scrollbar,
     #myFieldWrapper::-webkit-scrollbar,
     #theirFieldWrapper::-webkit-scrollbar,
     #myHandWrapper::-webkit-scrollbar,
     #theirHandWrapper::-webkit-scrollbar,
     #EffectStackWrapper::-webkit-scrollbar {
          width: 0;
          height: 0;
          display: none;
     }

     #myDeck,
     #theirDeck,
     #myBanish,
     #theirBanish,
     #myGraveyard,
     #theirGraveyard,
     #myMemory,
     #theirMemory,
     #myMaterial,
     #theirMaterial,
     #myMastery,
     #theirMastery {
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

     #myField,
     #theirField {
          --ga-field-card-size: 96px;
          --ga-field-columns: 1;
          --ga-field-gap-x: 6px;
          --ga-field-gap-y: 6px;
          width: 100%;
          height: 100%;
          display: grid !important;
          grid-template-columns: repeat(var(--ga-field-columns), var(--ga-field-card-size));
          grid-auto-rows: var(--ga-field-card-size);
          place-content: center !important;
          justify-items: center !important;
          align-items: center !important;
          gap: var(--ga-field-gap-y) var(--ga-field-gap-x);
          padding: 3px;
          overflow: hidden;
     }

     #myField > [id^="myField-"],
     #theirField > [id^="theirField-"] {
          width: var(--ga-field-card-size) !important;
          height: var(--ga-field-card-size) !important;
          margin: 0 !important;
          display: flex !important;
          align-items: center;
          justify-content: center;
          position: relative !important;
          max-width: 100%;
          max-height: 100%;
     }

     #myField > [id^="myField-"] > a,
     #theirField > [id^="theirField-"] > a {
          width: 100% !important;
          height: 100% !important;
          margin: 0 !important;
          display: block !important;
     }

     #myField > [id^="myField-"] > a > img:not(.counter-image-icon),
     #theirField > [id^="theirField-"] > a > img:not(.counter-image-icon) {
          display: block !important;
          width: 100% !important;
          height: 100% !important;
          max-width: 100% !important;
          max-height: 100% !important;
          object-fit: contain;
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

     #myIntent,
     #theirIntent,
     #EffectStack {
          width: 100%;
          height: 100%;
          display: flex !important;
          flex-wrap: nowrap !important;
          align-items: center !important;
          justify-content: center !important;
          overflow: hidden !important;
     }

     #myHand > *,
     #theirHand > * {
          flex: 0 0 auto;
     }

     #myHand > * + * {
          margin-left: -14px !important;
     }

     #theirHand > * + * {
          margin-left: -17px !important;
     }

     .ga-m-pile img:not(.counter-image-icon),
     .ga-m-pass img:not(.counter-image-icon) {
          height: 36px !important;
          max-width: 38px !important;
          width: auto !important;
          border-radius: 4px;
     }

     #myIntentSlot img:not(.counter-image-icon),
     #theirIntentSlot img:not(.counter-image-icon),
     #EffectStackSlot img:not(.counter-image-icon) {
          height: 42px !important;
          width: auto !important;
     }

     #gaMobileRoot .counter-bubble {
          min-width: 18px !important;
          height: 18px !important;
          line-height: 14px !important;
          font: 800 10px/14px var(--ga-font-ui) !important;
     }

     #gaMobileRoot [data-counter-field],
     #gaMobileRoot img.counter-image-icon {
          zoom: 0.72;
     }

     #gaMobileRoot [data-counter-field] img.counter-image-icon {
          zoom: 1;
     }

     #turn-miasma-overlay {
          z-index: 1200 !important;
          pointer-events: none !important;
     }

     #turn-miasma-overlay .turn-edge-glyph {
          top: calc(50% - 32px) !important;
          width: 18px !important;
          height: 58px !important;
          opacity: 0.7;
     }

     #turn-edge-glyph-left {
          left: 2px !important;
          right: auto !important;
     }

     #turn-edge-glyph-right {
          right: 2px !important;
          left: auto !important;
     }

     #turn-miasma-overlay .turn-edge-glyph::before,
     #turn-miasma-overlay .turn-edge-glyph::after {
          width: 4px !important;
          border-radius: 999px !important;
          transform: translateX(-50%) !important;
     }

     #turn-miasma-overlay .turn-edge-glyph::before {
          clip-path: none !important;
          top: 8px !important;
     }

     #turn-miasma-overlay .turn-edge-glyph::after {
          bottom: 8px !important;
          clip-path: none !important;
     }

     #turn-miasma-overlay .turn-edge-core {
          width: 14px !important;
          height: 14px !important;
          box-shadow: 0 0 12px currentColor, 0 0 20px currentColor !important;
     }

     #turn-miasma-message {
          max-width: calc(100vw - 24px) !important;
          border-radius: 10px !important;
          font-size: 12px !important;
          line-height: 1.2 !important;
          padding: 8px 10px !important;
     }

     #myHealth,
     #theirHealth {
          display: flex !important;
          align-items: center !important;
          justify-content: center !important;
          flex-wrap: nowrap !important;
     }

     #myHealth .widget-button-pass {
          min-width: 66px;
          border: 1px solid rgba(200, 155, 70, 0.72);
          border-radius: 8px;
          padding: 8px 10px;
          background: linear-gradient(180deg, rgba(200, 155, 70, 0.30), rgba(200, 155, 70, 0.14));
          color: #f4ecdb;
          font: 700 11px/1 var(--ga-font-label);
          letter-spacing: 0.08em;
          text-transform: uppercase;
     }

     .ga-hand-collapse-btn,
     .ga-field-scroll-btn,
     .ga-window-drag-handle {
          display: none !important;
     }

     #myField > span:not([id]),
     #theirField > span:not([id]),
     #myHand > span:not([id]),
     #theirHand > span:not([id]),
     #myIntent > span:not([id]),
     #theirIntent > span:not([id]),
     #EffectStack > span:not([id]),
     #myDeck > span:not([id]),
     #theirDeck > span:not([id]),
     #myBanish > span:not([id]),
     #theirBanish > span:not([id]),
     #myGraveyard > span:not([id]),
     #theirGraveyard > span:not([id]),
     #myMemory > span:not([id]),
     #theirMemory > span:not([id]),
     #myMaterial > span:not([id]),
     #theirMaterial > span:not([id]),
     #myMastery > span:not([id]),
     #theirMastery > span:not([id]) {
          display: none !important;
     }

     #gaMobileRoot .ga-m-empty::before {
          content: attr(data-label);
          color: rgba(244, 236, 219, 0.54);
          font: 700 8px/1 var(--ga-font-label);
          letter-spacing: 0.08em;
          text-transform: uppercase;
          white-space: nowrap;
     }

     .ga-m-hidden-bindings {
          position: fixed;
          left: -10000px;
          top: -10000px;
          width: 1px;
          height: 1px;
          overflow: hidden;
          opacity: 0;
          pointer-events: none;
     }

     .ga-m-hidden-bindings .ga-zone,
     #theirHandSlot,
     #theirHealthSlot,
     #myDecisionQueueWrapper,
     #theirDecisionQueueWrapper,
     #myVersionsWrapper,
     #theirVersionsWrapper,
     #myShortcutPreferencesWrapper,
     #theirShortcutPreferencesWrapper {
          width: 1px !important;
          height: 1px !important;
          min-width: 0 !important;
          min-height: 0 !important;
          overflow: hidden !important;
     }

     #regressionControls,
     #manualControls,
     #grand-archive-utility-button-bar,
     #gaShortcutDock {
          display: none !important;
     }

     .ga-m-admin-menu {
          position: fixed;
          top: 4px;
          right: 8px;
          z-index: 12050;
     }

     .ga-m-admin-menu-btn {
          width: 32px;
          height: 30px;
          padding: 0;
          display: inline-flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 4px;
          border: 1px solid rgba(200, 155, 70, 0.58);
          border-radius: 8px;
          background: rgba(8, 13, 18, 0.94);
          box-shadow: 0 8px 18px rgba(0, 0, 0, 0.32);
          cursor: pointer;
     }

     .ga-m-admin-menu-btn span {
          width: 15px;
          height: 2px;
          border-radius: 99px;
          background: rgba(255, 244, 207, 0.94);
     }

     .ga-m-admin-menu-panel {
          position: absolute;
          top: 36px;
          right: 0;
          width: min(278px, calc(100vw - 16px));
          max-height: calc(100dvh - 48px);
          display: none;
          padding: 7px;
          overflow-y: auto;
          border: 1px solid rgba(200, 155, 70, 0.28);
          border-radius: 10px;
          background: rgba(8, 13, 18, 0.98);
          box-shadow: 0 14px 32px rgba(0, 0, 0, 0.42);
     }

     .ga-m-admin-menu.is-open .ga-m-admin-menu-panel {
          display: flex;
          flex-direction: column;
          gap: 6px;
     }

     .ga-m-admin-menu-panel > button {
          width: 100%;
          padding: 8px 10px;
          border: 1px solid rgba(200, 155, 70, 0.28);
          border-radius: 7px;
          background: rgba(20, 34, 44, 0.92);
          color: rgba(255, 244, 207, 0.96);
          font: 800 11px/1 var(--ga-font-label);
          text-align: left;
          cursor: pointer;
     }

     .ga-m-shortcuts {
          display: flex;
          flex-direction: column;
          gap: 6px;
          padding-top: 4px;
          border-top: 1px solid rgba(244, 236, 219, 0.10);
     }

     .ga-m-shortcut-title {
          color: rgba(200, 155, 70, 0.92);
          font: 800 10px/1 var(--ga-font-label);
          letter-spacing: 0.12em;
          text-transform: uppercase;
     }

     .ga-m-shortcut-row {
          display: grid;
          grid-template-columns: minmax(0, 1fr) 42px;
          align-items: center;
          gap: 8px;
          color: rgba(244, 236, 219, 0.9);
          font: 700 11px/1.2 var(--ga-font-ui);
     }

     .ga-m-shortcut-row span {
          min-width: 0;
          overflow-wrap: anywhere;
     }

     .ga-m-shortcut-toggle {
          position: relative;
          justify-self: end;
          flex: 0 0 auto;
          min-width: 42px;
          max-width: 42px;
          width: 42px;
          height: 24px;
          padding: 0;
          border: 0;
          border-radius: 999px;
          background: rgba(244, 236, 219, 0.18);
          box-shadow: none;
          cursor: pointer;
     }

     .ga-m-shortcut-toggle::before {
          content: "";
          position: absolute;
          top: 3px;
          left: 3px;
          width: 18px;
          height: 18px;
          border-radius: 50%;
          background: rgba(244, 236, 219, 0.96);
          transition: transform 160ms ease, background 160ms ease;
     }

     .ga-m-shortcut-toggle.is-on {
          background: linear-gradient(90deg, rgba(200, 155, 70, 0.94), rgba(45, 111, 115, 0.88));
     }

     .ga-m-shortcut-toggle.is-on::before {
          transform: translateX(18px);
          background: #fff7df;
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

     @media (max-height: 560px) {
          #gaMobileRoot {
               grid-template-rows: 48px minmax(62px, 1fr) 48px minmax(62px, 1fr) 82px 50px;
          }

          .ga-m-band {
               padding-top: 2px;
               padding-bottom: 2px;
          }

          .ga-m-label {
               font-size: 8px;
               margin-bottom: 3px;
          }

          .ga-m-pile-label {
               font-size: 7px;
          }

          .ga-m-pile,
          .ga-m-pass,
          .ga-m-hand-summary {
               height: 42px;
          }

          #myIntentSlot,
          #theirIntentSlot,
          #EffectStackSlot {
               max-height: 42px;
          }

          .ga-phase-track {
               width: calc(100% - 50px);
               gap: 4px;
               font-size: 7px;
               letter-spacing: 0.04em;
          }

          #turn-miasma-overlay .turn-edge-glyph {
               top: calc(50% - 28px) !important;
               height: 48px !important;
          }
     }
</style>

<div id="gaMobileRoot">
     <div id="gaMobileAdminMenu" class="ga-m-admin-menu">
          <button id="gaMobileAdminMenuBtn" class="ga-m-admin-menu-btn" type="button" aria-label="Game menu" aria-expanded="false">
               <span></span><span></span><span></span>
          </button>
          <div id="gaMobileAdminMenuPanel" class="ga-m-admin-menu-panel">
               <button type="button" data-ga-mobile-action="undo" data-ga-mobile-player-action="true">Undo</button>
               <button type="button" data-ga-admin-target="bug-report-button">Report Bug</button>
               <button type="button" data-ga-admin-target="copy-spectate-link-button">Copy Spectate Link</button>
               <button type="button" data-ga-admin-target="concede-button">Concede</button>
               <?php if (function_exists('GAGameMode') && GAGameMode() === 'hotseat'): ?>
               <button type="button" data-ga-mobile-action="switch-player">Switch Player</button>
               <?php endif; ?>
               <div id="gaMobileShortcutPanel" class="ga-m-shortcuts"></div>
          </div>
     </div>

     <div class="ga-m-band is-theirs">
          <div id="theirHandSummary" class="ga-m-hand-summary" aria-label="Their hand summary"></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Memory</div><div id="theirMemorySlot" class="ga-zone" data-label="Memory"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Material</div><div id="theirMaterialSlot" class="ga-zone" data-label="Material"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Mastery</div><div id="theirMasterySlot" class="ga-zone" data-label="Mastery"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Deck</div><div id="theirDeckSlot" class="ga-zone" data-label="Deck"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Banish</div><div id="theirBanishSlot" class="ga-zone" data-label="Banish"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Grave</div><div id="theirGraveyardSlot" class="ga-zone" data-label="Graveyard"></div></div>
     </div>

     <div class="ga-m-field-section is-theirs">
          <div class="ga-m-label">Their Field</div>
          <div id="theirFieldSlot" class="ga-zone" data-label="Field"></div>
     </div>

     <div class="ga-m-center-strip">
          <div id="theirIntentSlot" class="ga-zone" data-label="Their Intent"></div>
          <div id="gaPhaseTrack" class="ga-phase-track" aria-live="polite" aria-label="Turn phases">
               <span class="ga-phase-step" data-phase-step="WU">Wake Up</span>
               <span class="ga-phase-step" data-phase-step="MAT">Materialize</span>
               <span class="ga-phase-step" data-phase-step="RECOLLECTION">Recollect</span>
               <span class="ga-phase-step" data-phase-step="DRAW">Draw</span>
               <span class="ga-phase-step" data-phase-step="MAIN">Main</span>
               <span class="ga-phase-step" data-phase-step="END">End</span>
          </div>
          <div id="EffectStackSlot" class="ga-zone" data-label="Effect Stack"></div>
          <div id="myIntentSlot" class="ga-zone" data-label="My Intent"></div>
     </div>

     <div class="ga-m-field-section is-mine">
          <div class="ga-m-label">My Field</div>
          <div id="myFieldSlot" class="ga-zone" data-label="Field"></div>
     </div>

     <div class="ga-m-hand-section is-mine">
          <div class="ga-m-label">My Hand</div>
          <div id="myHandSlot" class="ga-zone" data-label="Hand"></div>
     </div>

     <div class="ga-m-band is-mine">
          <div class="ga-m-pass"><div id="myHealthSlot" class="ga-zone" data-label="Pass"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Memory</div><div id="myMemorySlot" class="ga-zone" data-label="Memory"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Material</div><div id="myMaterialSlot" class="ga-zone" data-label="Material"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Mastery</div><div id="myMasterySlot" class="ga-zone" data-label="Mastery"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Deck</div><div id="myDeckSlot" class="ga-zone" data-label="Deck"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Banish</div><div id="myBanishSlot" class="ga-zone" data-label="Banish"></div></div>
          <div class="ga-m-pile"><div class="ga-m-pile-label">Grave</div><div id="myGraveyardSlot" class="ga-zone" data-label="Graveyard"></div></div>
     </div>

     <div class="ga-m-hidden-bindings" aria-hidden="true">
          <div id="theirHandSlot" class="ga-zone"></div>
          <div id="theirHealthSlot" class="ga-zone"></div>
     </div>
</div>

<script>
(function() {
     window.GAIsHotseat = <?php echo (function_exists('GAGameMode') && GAGameMode() === 'hotseat') ? 'true' : 'false'; ?>;
     window.gaSwitchPlayer = function () {
          if (!window.GAIsHotseat) return;
          var url = new URL(window.location.href);
          var cur = parseInt(url.searchParams.get('playerID') || '1', 10);
          url.searchParams.set('playerID', cur === 1 ? '2' : '1');
          window.location.href = url.toString();
     };
     document.addEventListener('keydown', function(e) {
          if (e.key !== 'w' && e.key !== 'W') return;
          if (!window.GAIsHotseat) return;
          var t = e.target;
          if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
          e.preventDefault();
          window.gaSwitchPlayer();
     });

     window.GAMatchId = <?php echo json_encode(class_exists('DecisionQueueController') ? strval(DecisionQueueController::GetVariable('MatchId') ?? '') : ''); ?>;
     (function() {
          if (!window.GAMatchId) return;
          function gaAppBase(){
               var p = location.pathname;
               var i = p.indexOf('/TCGEngine/');
               return i >= 0 ? p.slice(0, i + '/TCGEngine/'.length) : '/TCGEngine/';
          }
          var url = new URL(window.location.href);
          var pid = url.searchParams.get('playerID') || '1';
          var authKey = url.searchParams.get('authKey') || '';
          var gameName = url.searchParams.get('gameName') || '';
          var menuUrl = gaAppBase() + 'SharedUI/Sites/GrandArchiveSim/MainMenu.php';
          var statsHtml = '';

          function gaGoMenu(){ location.href = menuUrl; }
          function gaReportBug(){ if (typeof openBugReportModal === 'function') openBugReportModal(); }
          function gaGoNext(info){
               if (info.sideboardPending) {
                    location.href = gaAppBase() + 'GrandArchiveSim/Sideboard.php?matchId=' + encodeURIComponent(info.matchId)
                         + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(authKey);
               } else if (info.nextGameName) {
                    location.href = gaAppBase() + 'NextTurn.php?playerID=' + encodeURIComponent(pid)
                         + '&gameName=' + encodeURIComponent(info.nextGameName) + '&authKey=' + encodeURIComponent(authKey)
                         + '&folderPath=GrandArchiveSim';
               }
          }
          function gaSubmitRematch(bo){
               var btn = document.getElementById('ga-rematch-btn');
               if (btn) {
                    btn.textContent = 'Rematch Requested';
                    btn.disabled = true;
               }
               if (typeof SubmitEngineInput === 'function') {
                    SubmitEngineInput('10013', '&inputText=' + encodeURIComponent(bo), {responseFormat:'json'})
                         .then(function(){ gaCheckMatchEnd(true); })
                         .catch(function(){ gaCheckMatchEnd(true); });
               } else {
                    SubmitInput('10013', '&inputText=' + encodeURIComponent(bo));
                    setTimeout(function(){ gaCheckMatchEnd(true); }, 500);
               }
          }

          function gaBuildButtons(info){
               var b = [];
               if (info.sideboardPending || info.nextGameName) {
                    b.push({label:'Go to Next Game', onClick:function(){ gaGoNext(info); }});
                    b.push({label:'Return to Main Menu', onClick:gaGoMenu});
                    b.push({label:'Report Bug', onClick:gaReportBug});
                    return b;
               }
               if (info.seriesOver) {
                    var bo = (info.bestOf === 3) ? 3 : 1;
                    var rematchLabel = 'Rematch';
                    var rematchDisabled = false;
                    if (info.rematchRequestedByMe && !info.rematchRequestedByOpp) {
                         rematchLabel = 'Rematch Requested';
                         rematchDisabled = true;
                    } else if (info.rematchRequestedByOpp && !info.rematchRequestedByMe) {
                         rematchLabel = 'Accept Rematch';
                    }
                    b.push({label:'Return to Main Menu', onClick:gaGoMenu});
                    b.push({id:'ga-rematch-btn', label:rematchLabel, disabled:rematchDisabled, onClick:function(){ gaSubmitRematch(bo); }});
                    if (info.convertible) {
                         var lbl = 'Convert to Best of 3';
                         var dis = false;
                         if (info.convertRequestedByMe && !info.convertRequestedByOpp) {
                              lbl = 'Waiting on opponent';
                              dis = true;
                         } else if (info.convertRequestedByOpp && !info.convertRequestedByMe) {
                              lbl = 'Confirm Convert to Best of 3';
                         }
                         b.push({id:'ga-convert-btn', label:lbl, disabled:dis, onClick:function(){ SubmitInput('10012', ''); }});
                    }
                    b.push({label:'Report Bug', onClick:gaReportBug});
                    return b;
               }
               b.push({label:'Return to Main Menu', onClick:gaGoMenu});
               b.push({label:'Report Bug', onClick:gaReportBug});
               return b;
          }

          function gaRenderOverlay(info){
               var ex = document.getElementById('game-over-overlay');
               if (ex && ex.remove) ex.remove();
               if (typeof ShowGameOver === 'function') ShowGameOver(!!info.didWin, menuUrl, statsHtml, gaBuildButtons(info));
          }

          var lastSig = null;
          function gaCheckMatchEnd(force) {
               return fetch(gaAppBase() + 'GrandArchiveSim/EndGameInfo.php?gameName=' + encodeURIComponent(gameName)
                    + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(authKey)
                    + '&folderPath=GrandArchiveSim')
                    .then(function(r){ return r.json(); })
                    .then(function(info) {
                         if (!info || !info.gameWinner) return;
                         var sig = [info.sideboardPending, info.nextGameName, info.seriesOver, info.convertible,
                                    info.convertRequestedByMe, info.convertRequestedByOpp,
                                    info.rematchRequestedByMe, info.rematchRequestedByOpp].join('|');
                         if (sig === lastSig && !force) return;
                         lastSig = sig;
                         gaRenderOverlay(info);
                    })
                    .catch(function(){});
          }

          var gaPollStarted = false;
          window.GAShowEndGameMenu = function(prebuiltStats){
               statsHtml = prebuiltStats || ((typeof BuildMacroGameStatsHtml === 'function') ? BuildMacroGameStatsHtml(pid) : '');
               gaCheckMatchEnd(true);
               if (!gaPollStarted) {
                    gaPollStarted = true;
                    setInterval(gaCheckMatchEnd, 3000);
               }
          };
     })();

     window.TurnIndicatorSettings = {
          showWaitingMessage: true,
          messageAnchorId: 'myHandSlot',
          waitingMessageBuilder: function(ctx) {
               if (!ctx || typeof ctx.defaultBuilder !== 'function') return null;
               return ctx.defaultBuilder();
          }
     };

     var AUTO_HIDE_IDS = ['myIntentSlot', 'theirIntentSlot', 'EffectStackSlot'];
     var EMPTY_STATE_IDS = [
          'myFieldSlot', 'theirFieldSlot',
          'myHandSlot',
          'myMemorySlot', 'theirMemorySlot',
          'myMaterialSlot', 'theirMaterialSlot',
          'myMasterySlot', 'theirMasterySlot',
          'myDeckSlot', 'theirDeckSlot',
          'myBanishSlot', 'theirBanishSlot',
          'myGraveyardSlot', 'theirGraveyardSlot'
     ];

     function hasCards(slot) {
          return slot.querySelector('[id$="-0"]') !== null;
     }

     function refreshVisibility(slot) {
          slot.style.display = hasCards(slot) ? '' : 'none';
     }

     function refreshEmptyState(slot) {
          slot.classList.toggle('ga-m-empty', !hasCards(slot));
     }

     function getMobileFieldCards(field) {
          if (!field) return [];
          return Array.prototype.filter.call(field.children, function(child) {
               return !!(child && child.id && /^(myField|theirField)-\d+$/.test(child.id) && child.querySelector('a > img:not(.counter-image-icon)'));
          });
     }

     function maxMobileFieldCardSize(count) {
          if (count <= 1) return 112;
          if (count <= 2) return 104;
          if (count <= 3) return 94;
          if (count <= 4) return 90;
          if (count <= 6) return 80;
          if (count <= 9) return 70;
          if (count <= 12) return 62;
          return 54;
     }

     function mobileFieldGap(count) {
          if (count <= 2) return 8;
          if (count <= 6) return 6;
          return 4;
     }

     function preferredMobileFieldColumns(count) {
          if (count <= 1) return 1;
          if (count <= 3) return count;
          if (count <= 4) return 2;
          if (count <= 9) return 3;
          if (count <= 12) return 4;
          return Math.max(4, Math.ceil(Math.sqrt(count * 1.35)));
     }

     function chooseMobileFieldLayout(count, width, height) {
          var gap = mobileFieldGap(count);
          var maxSize = maxMobileFieldCardSize(count);
          var minSize = 36;
          var preferredCols = preferredMobileFieldColumns(count);
          var best = { cols: Math.max(1, Math.min(count, preferredCols)), rows: 1, size: minSize, score: -1 };

          for (var cols = 1; cols <= count; ++cols) {
               var rows = Math.ceil(count / cols);
               var sizeFromWidth = (width - gap * (cols - 1)) / cols;
               var sizeFromHeight = (height - gap * (rows - 1)) / rows;
               var size = Math.floor(Math.min(maxSize, sizeFromWidth, sizeFromHeight));
               if (!Number.isFinite(size)) size = minSize;
               size = Math.max(minSize, size);

               var score = size * 100;
               score -= Math.abs(cols - preferredCols) * 6;
               score -= Math.abs((cols / rows) - (width / Math.max(height, 1))) * 2;
               if (count <= 3 && rows === 1) score += 40;
               if (count === 4 && cols === 2) score += 45;
               if (count >= 5 && count <= 6 && cols === 3) score += 35;
               if (count >= 7 && count <= 9 && cols === 3) score += 35;
               if (count >= 10 && count <= 12 && cols === 4) score += 35;

               if (score > best.score) {
                    best = { cols: cols, rows: rows, size: size, score: score };
               }
          }

          return {
               cols: Math.max(1, Math.min(count, best.cols)),
               rows: Math.max(1, best.rows),
               size: best.size,
               gap: gap
          };
     }

     function refreshMobileFieldLayout(slotID) {
          var slot = document.getElementById(slotID);
          if (!slot) return;
          var fieldID = slotID === 'myFieldSlot' ? 'myField' : 'theirField';
          var field = document.getElementById(fieldID);
          if (!field) return;

          var cards = getMobileFieldCards(field);
          var count = cards.length;
          slot.setAttribute('data-card-count', String(count));
          field.setAttribute('data-card-count', String(count));
          if (count === 0) {
               field.style.setProperty('--ga-field-columns', '1');
               field.style.setProperty('--ga-field-card-size', '72px');
               field.style.setProperty('--ga-field-gap-x', '4px');
               field.style.setProperty('--ga-field-gap-y', '4px');
               field.removeAttribute('data-field-layout');
               return;
          }

          var rect = field.getBoundingClientRect();
          var usableWidth = Math.max(1, rect.width - 8);
          var usableHeight = Math.max(1, rect.height - 8);
          var layout = chooseMobileFieldLayout(count, usableWidth, usableHeight);
          field.style.setProperty('--ga-field-columns', String(layout.cols));
          field.style.setProperty('--ga-field-card-size', layout.size + 'px');
          field.style.setProperty('--ga-field-gap-x', layout.gap + 'px');
          field.style.setProperty('--ga-field-gap-y', layout.gap + 'px');
          field.setAttribute('data-field-layout', layout.cols + 'x' + layout.rows);
     }

     var fieldLayoutFrame = null;
     function refreshMobileFieldLayouts() {
          if (fieldLayoutFrame !== null) return;
          fieldLayoutFrame = window.requestAnimationFrame(function() {
               fieldLayoutFrame = null;
               refreshMobileFieldLayout('theirFieldSlot');
               refreshMobileFieldLayout('myFieldSlot');
          });
     }

     function watchSlot(id) {
          var el = document.getElementById(id);
          if (!el || !window.MutationObserver) return;
          el.style.display = 'none';
          new MutationObserver(function() { refreshVisibility(el); })
               .observe(el, { childList: true, subtree: true });
     }

     function watchEmptyState(id) {
          var el = document.getElementById(id);
          if (!el || !window.MutationObserver) return;
          refreshEmptyState(el);
          new MutationObserver(function() { refreshEmptyState(el); })
               .observe(el, { childList: true, subtree: true });
     }

     function watchMobileFieldLayout(id) {
          var el = document.getElementById(id);
          if (!el) return;
          refreshMobileFieldLayouts();
          if (!window.MutationObserver) return;
          new MutationObserver(refreshMobileFieldLayouts)
               .observe(el, { childList: true, subtree: true });
     }

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
               if (isActive) step.setAttribute('aria-current', 'step');
               else step.removeAttribute('aria-current');
          }
     }

     function parseZoneCount(dataKey) {
          var raw = window[dataKey];
          if (!raw || typeof raw !== 'string') return 0;
          var trimmed = raw.trim();
          if (trimmed === '') return 0;
          return trimmed.split('<|>').filter(function(entry) {
               entry = entry.trim();
               if (entry === '' || entry === '-') return false;
               var cardID = entry.split(' ')[0] || '';
               return cardID !== '' && cardID !== '-';
          }).length;
     }

     function renderOpponentHandSummary() {
          var summary = document.getElementById('theirHandSummary');
          if (!summary) return;
          var count = parseZoneCount('theirHandData');
          summary.innerHTML =
               '<div class="ga-m-summary-thumb"><img alt="" src="./GrandArchiveSim/concat/CardBack.webp"></div>' +
               '<div class="ga-m-summary-meta">' +
                    '<div class="ga-m-pile-label">Their Hand</div>' +
                    '<div class="ga-m-summary-count">' + count + ' cards</div>' +
               '</div>';
     }

     function setupAdminMenu() {
          var menu = document.getElementById('gaMobileAdminMenu');
          var btn = document.getElementById('gaMobileAdminMenuBtn');
          var panel = document.getElementById('gaMobileAdminMenuPanel');
          if (!menu || !btn || !panel) return;

          function setOpen(open) {
               menu.classList.toggle('is-open', open);
               btn.setAttribute('aria-expanded', open ? 'true' : 'false');
          }

          function updateAvailability() {
               var actions = panel.querySelectorAll('[data-ga-admin-target]');
               for (var i = 0; i < actions.length; ++i) {
                    var action = actions[i];
                    var targetID = action.getAttribute('data-ga-admin-target');
                    action.style.display = document.getElementById(targetID) ? 'block' : 'none';
               }
               var playerActions = panel.querySelectorAll('[data-ga-mobile-player-action]');
               var hidePlayerActions = (typeof IsSpectatorClient === 'function' && IsSpectatorClient());
               for (var j = 0; j < playerActions.length; ++j) {
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
               var action = event.target && event.target.closest ? event.target.closest('[data-ga-admin-target]') : null;
               if (action) {
                    event.preventDefault();
                    event.stopPropagation();
                    var target = document.getElementById(action.getAttribute('data-ga-admin-target'));
                    if (target && typeof target.click === 'function') target.click();
                    setOpen(false);
                    return;
               }
               var mobileAction = event.target && event.target.closest ? event.target.closest('[data-ga-mobile-action]') : null;
               if (!mobileAction) return;
               event.preventDefault();
               event.stopPropagation();
               var mobileActionName = mobileAction.getAttribute('data-ga-mobile-action');
               if (mobileActionName === 'undo' && typeof SubmitInput === 'function') {
                    SubmitInput(10004, '');
               } else if (mobileActionName === 'switch-player' && typeof window.gaSwitchPlayer === 'function') {
                    window.gaSwitchPlayer();
               }
               setOpen(false);
          });

          document.addEventListener('click', function(event) {
               if (!menu.contains(event.target)) setOpen(false);
          });

          updateAvailability();
          window.setInterval(updateAvailability, 700);
     }

     function setupShortcutPanel() {
          if (typeof IsSpectatorClient === 'function' && IsSpectatorClient()) return;

          var panel = document.getElementById('gaMobileShortcutPanel');
          if (!panel) return;

          var rootName = 'GrandArchiveSim';
          var settingKey = 'ShortcutPreferences';
          var registryRaw = (typeof GetModuleConfig === 'function') ? GetModuleConfig('ShortcutWindows') : null;
          var registry = {};
          try {
               registry = registryRaw ? JSON.parse(registryRaw) : {};
          } catch (e) {
               registry = {};
          }

          var entries = Object.keys(registry).map(function(windowId) {
               var entry = registry[windowId] || {};
               return {
                    id: windowId,
                    label: (entry.label && String(entry.label).trim() !== '') ? String(entry.label) : windowId,
                    defaultValue: !!entry.default,
                    order: Number.isFinite(Number(entry.order)) ? Number(entry.order) : 0
               };
          }).sort(function(a, b) {
               if (a.order !== b.order) return a.order - b.order;
               return a.label.localeCompare(b.label);
          });

          if (entries.length === 0) {
               panel.style.display = 'none';
               return;
          }

          var defaultWindows = {};
          entries.forEach(function(entry) {
               defaultWindows[entry.id] = entry.defaultValue;
          });
          var defaultPayload = { version: 1, windows: defaultWindows };

          if (window.TCGSettings && typeof window.TCGSettings.registerSchema === 'function') {
               window.TCGSettings.registerSchema(rootName, {
                    ShortcutPreferences: { type: 'json', defaultValue: defaultPayload }
               });
          }

          function normalizePayload(raw) {
               var incoming = raw;
               if (!incoming || typeof incoming !== 'object') incoming = {};
               var incomingWindows = (incoming.windows && typeof incoming.windows === 'object') ? incoming.windows : incoming;
               var normalizedWindows = {};
               entries.forEach(function(entry) {
                    if (Object.prototype.hasOwnProperty.call(incomingWindows, entry.id)) {
                         normalizedWindows[entry.id] = !!incomingWindows[entry.id];
                    } else {
                         normalizedWindows[entry.id] = entry.defaultValue;
                    }
               });
               return { version: 1, windows: normalizedWindows };
          }

          function getStoredPayload() {
               if (!window.TCGSettings || typeof window.TCGSettings.get !== 'function') {
                    return normalizePayload(defaultPayload);
               }
               return normalizePayload(window.TCGSettings.get(settingKey, {
                    rootName: rootName,
                    type: 'json',
                    defaultValue: defaultPayload
               }));
          }

          function storePayload(payload) {
               if (!window.TCGSettings || typeof window.TCGSettings.set !== 'function') return;
               window.TCGSettings.set(settingKey, normalizePayload(payload), {
                    rootName: rootName,
                    type: 'json'
               });
          }

          function syncPayloadToServer(payload) {
               if (typeof SubmitInput !== 'function') return;
               SubmitInput('10015', '&inputText=' + encodeURIComponent(JSON.stringify(normalizePayload(payload))));
          }

          function renderPanel(payload) {
               var normalized = normalizePayload(payload);
               var rows = entries.map(function(entry) {
                    var checked = !!normalized.windows[entry.id];
                    return '' +
                         '<div class="ga-m-shortcut-row" data-window-id="' + entry.id + '">' +
                              '<span>' + entry.label + '</span>' +
                              '<button class="ga-m-shortcut-toggle' + (checked ? ' is-on' : '') + '" type="button" aria-pressed="' + (checked ? 'true' : 'false') + '" data-window-id="' + entry.id + '"></button>' +
                         '</div>';
               }).join('');

               panel.innerHTML = '<div class="ga-m-shortcut-title">Shortcut Windows</div>' + rows;

               Array.prototype.forEach.call(panel.querySelectorAll('.ga-m-shortcut-toggle'), function(toggle) {
                    toggle.addEventListener('click', function(ev) {
                         ev.preventDefault();
                         ev.stopPropagation();
                         var windowId = toggle.getAttribute('data-window-id');
                         var nextPayload = normalizePayload(getStoredPayload());
                         nextPayload.windows[windowId] = !nextPayload.windows[windowId];
                         storePayload(nextPayload);
                         toggle.classList.toggle('is-on', !!nextPayload.windows[windowId]);
                         toggle.setAttribute('aria-pressed', nextPayload.windows[windowId] ? 'true' : 'false');
                         syncPayloadToServer(nextPayload);
                    });
               });
          }

          var initialPayload = getStoredPayload();
          storePayload(initialPayload);
          renderPanel(initialPayload);
          syncPayloadToServer(initialPayload);
     }

     AUTO_HIDE_IDS.forEach(watchSlot);
     EMPTY_STATE_IDS.forEach(watchEmptyState);
     watchMobileFieldLayout('theirFieldSlot');
     watchMobileFieldLayout('myFieldSlot');
     setupAdminMenu();
     setupShortcutPanel();
     updatePhaseTrack();
     refreshMobileFieldLayouts();
     renderOpponentHandSummary();
     watchEmptyState('theirHandSlot');
     if (window.MutationObserver) {
          var globalStuff = document.getElementById('globalStuff');
          if (globalStuff) {
               new MutationObserver(updatePhaseTrack)
                    .observe(globalStuff, { childList: true, subtree: true, characterData: true });
          }
          var theirHand = document.getElementById('theirHandSlot');
          if (theirHand) {
               new MutationObserver(renderOpponentHandSummary)
                    .observe(theirHand, { childList: true, subtree: true, characterData: true });
          }
     }
     window.addEventListener('resize', refreshMobileFieldLayouts);
     if (window.visualViewport && typeof window.visualViewport.addEventListener === 'function') {
          window.visualViewport.addEventListener('resize', refreshMobileFieldLayouts);
     }
     window.setInterval(function() {
          updatePhaseTrack();
          refreshMobileFieldLayouts();
          renderOpponentHandSummary();
     }, 400);
})();
</script>
