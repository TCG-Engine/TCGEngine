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
     }

     .ga-board-art.is-dawn-of-ashes {
          background: url("/TCGEngine/Assets/Boards/dawn-of-ashes.webp") center center / cover no-repeat;
     }

     .ga-board-art.is-classic-blue {
          background: linear-gradient(180deg, #3f74aa 0%, #2b5e93 48%, #1e4678 100%);
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

     .ga-kb-hints {
          position: fixed;
          left: 50%;
          top: calc(50% - 18px);
          transform: translateX(-50%);
          z-index: 16;
          pointer-events: none;
          display: flex;
          align-items: center;
          gap: 14px;
          color: rgba(244, 236, 219, 0.32);
          font: 600 8px/1 var(--ga-font-label);
          letter-spacing: 0.08em;
          text-transform: uppercase;
          white-space: nowrap;
     }

     .ga-kb-hints kbd {
          display: inline-block;
          background: rgba(244, 236, 219, 0.10);
          border: 1px solid rgba(244, 236, 219, 0.18);
          border-radius: 3px;
          padding: 1px 3px;
          font: inherit;
          line-height: 1;
          margin-right: 2px;
     }

     .ga-zone {
          position: fixed;
          z-index: 30;
          pointer-events: auto;
     }

     .ga-shortcut-dock {
          position: fixed;
          left: 10px;
          bottom: max(56px, env(safe-area-inset-bottom, 0px) + 12px);
          z-index: 2800;
          display: flex;
          align-items: flex-end;
          gap: 10px;
          pointer-events: none;
     }

     .ga-shortcut-tab,
     .ga-shortcut-panel {
          pointer-events: auto;
     }

     .ga-shortcut-tab {
          position: relative;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-width: 44px;
          min-height: 132px;
          padding: 14px 10px;
          border: 1px solid rgba(244, 236, 219, 0.20);
          border-radius: 18px;
          background:
               linear-gradient(180deg, rgba(244, 236, 219, 0.16), rgba(255, 255, 255, 0.03)),
               linear-gradient(160deg, rgba(19, 32, 43, 0.92), rgba(45, 111, 115, 0.72));
          box-shadow: 0 18px 40px rgba(7, 14, 20, 0.30), inset 0 1px 0 rgba(255, 255, 255, 0.10);
          color: rgba(244, 236, 219, 0.94);
          cursor: pointer;
          writing-mode: vertical-rl;
          transform: rotate(180deg);
          text-transform: uppercase;
          letter-spacing: 0.24em;
          font: 700 11px/1 var(--ga-font-label);
          transition: transform 260ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 220ms ease, border-color 220ms ease, filter 220ms ease;
     }

     .ga-shortcut-tab::after {
          content: "";
          position: absolute;
          inset: 8px;
          border-radius: 12px;
          border: 1px solid rgba(244, 236, 219, 0.08);
          pointer-events: none;
     }

     .ga-shortcut-tab:hover {
          border-color: rgba(252, 238, 171, 0.36);
          box-shadow: 0 24px 48px rgba(7, 14, 20, 0.34), 0 0 24px rgba(200, 155, 70, 0.18);
          filter: saturate(110%);
     }

     .ga-shortcut-tab:focus-visible {
          outline: 2px solid rgba(252, 238, 171, 0.72);
          outline-offset: 3px;
     }

     .ga-shortcut-panel {
          width: min(310px, calc(100vw - 84px));
          max-width: min(310px, calc(100vw - 84px));
          max-height: min(calc(100vh - 72px), 420px);
          overflow: hidden;
          border: 1px solid rgba(244, 236, 219, 0.16);
          border-radius: 24px;
          background:
               linear-gradient(180deg, rgba(244, 236, 219, 0.15), rgba(255, 255, 255, 0.03)),
               linear-gradient(165deg, rgba(19, 32, 43, 0.94), rgba(19, 32, 43, 0.78));
          box-shadow: 0 28px 60px rgba(7, 14, 20, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.10);
          backdrop-filter: blur(18px) saturate(145%);
          -webkit-backdrop-filter: blur(18px) saturate(145%);
          transform-origin: left bottom;
          transition: opacity 280ms cubic-bezier(0.22, 1, 0.36, 1), transform 280ms cubic-bezier(0.22, 1, 0.36, 1), max-width 280ms cubic-bezier(0.22, 1, 0.36, 1), margin 280ms cubic-bezier(0.22, 1, 0.36, 1);
     }

     .ga-shortcut-dock.is-collapsed .ga-shortcut-panel {
          opacity: 0;
          transform: translateX(-18px) scale(0.92);
          max-width: 0;
          margin-right: -10px;
          pointer-events: none;
     }

     .ga-shortcut-dock:not(.is-collapsed) .ga-shortcut-tab {
          transform: translateY(-4px) rotate(180deg);
          border-color: rgba(252, 238, 171, 0.34);
          box-shadow: 0 26px 50px rgba(7, 14, 20, 0.34), 0 0 22px rgba(200, 155, 70, 0.20);
     }

     .ga-shortcut-panel-inner {
          padding: 16px 16px 18px;
          display: flex;
          flex-direction: column;
          gap: 12px;
          box-sizing: border-box;
          max-height: inherit;
          min-height: 0;
          overflow: hidden;
     }

     .ga-shortcut-header {
          display: flex;
          flex-direction: column;
          gap: 4px;
          padding-right: 16px;
     }

     .ga-shortcut-title {
          color: rgba(252, 238, 171, 0.96);
          text-transform: uppercase;
          letter-spacing: 0.22em;
          font: 700 11px/1 var(--ga-font-label);
     }

     .ga-shortcut-copy {
          color: rgba(244, 236, 219, 0.70);
          font: 500 12px/1.45 var(--ga-font-ui);
     }

     .ga-shortcut-list {
          display: flex;
          flex-direction: column;
          gap: 8px;
          flex: 1 1 auto;
          min-height: 0;
          overflow-y: auto;
          padding-right: 6px;
          padding-bottom: 6px;
          scrollbar-color: rgba(252, 238, 171, 0.32) rgba(244, 236, 219, 0.05);
          scrollbar-width: thin;
     }

     .ga-shortcut-list::-webkit-scrollbar {
          width: 8px;
     }

     .ga-shortcut-list::-webkit-scrollbar-track {
          border-radius: 999px;
          background: rgba(244, 236, 219, 0.05);
     }

     .ga-shortcut-list::-webkit-scrollbar-thumb {
          border: 2px solid rgba(19, 32, 43, 0.92);
          border-radius: 999px;
          background: rgba(252, 238, 171, 0.36);
     }

     .ga-shortcut-list::-webkit-scrollbar-thumb:hover {
          background: rgba(252, 238, 171, 0.52);
     }

     .ga-shortcut-row {
          display: flex;
          flex: 0 0 auto;
          align-items: center;
          justify-content: space-between;
          gap: 12px;
          padding: 10px 12px;
          border: 1px solid rgba(244, 236, 219, 0.10);
          border-radius: 16px;
          background: linear-gradient(180deg, rgba(244, 236, 219, 0.06), rgba(255, 255, 255, 0.02));
          box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
     }

     .ga-shortcut-row-label {
          color: rgba(244, 236, 219, 0.92);
          font: 600 13px/1.35 var(--ga-font-ui);
       }

     .ga-shortcut-toggle {
          position: relative;
          flex: 0 0 auto;
          width: 48px;
          height: 28px;
          border: none;
          border-radius: 999px;
          background: rgba(244, 236, 219, 0.18);
          cursor: pointer;
          transition: background 180ms ease, box-shadow 180ms ease, transform 180ms ease;
     }

     .ga-shortcut-toggle::before {
          content: "";
          position: absolute;
          top: 3px;
          left: 3px;
          width: 22px;
          height: 22px;
          border-radius: 50%;
          background: rgba(244, 236, 219, 0.96);
          box-shadow: 0 4px 10px rgba(7, 14, 20, 0.28);
          transition: transform 180ms ease, background 180ms ease;
     }

     .ga-shortcut-toggle.is-on {
          background: linear-gradient(90deg, rgba(200, 155, 70, 0.96), rgba(45, 111, 115, 0.88));
          box-shadow: 0 0 18px rgba(200, 155, 70, 0.24);
     }

     .ga-shortcut-toggle.is-on::before {
          transform: translateX(20px);
          background: #fff7df;
     }

     .ga-shortcut-toggle:hover {
          transform: translateY(-1px);
     }

     .ga-shortcut-footer {
          color: rgba(244, 236, 219, 0.50);
          font: 500 11px/1.4 var(--ga-font-ui);
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
     #theirGraveyardWrapper {
          overflow: hidden !important;
          scrollbar-width: none;
          -ms-overflow-style: none;
     }

     #myDeckWrapper::-webkit-scrollbar,
     #theirDeckWrapper::-webkit-scrollbar,
     #myBanishWrapper::-webkit-scrollbar,
     #theirBanishWrapper::-webkit-scrollbar,
     #myGraveyardWrapper::-webkit-scrollbar,
     #theirGraveyardWrapper::-webkit-scrollbar {
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

     #myFieldWrapper::before,
     #myFieldWrapper::after,
     #theirFieldWrapper::before,
     #theirFieldWrapper::after {
          content: "";
          position: absolute;
          top: 14px;
          bottom: 14px;
          width: 34px;
          pointer-events: none;
          z-index: 2;
          opacity: 0.92;
     }

     #myFieldWrapper:not(.ga-can-scroll-left)::before,
     #theirFieldWrapper:not(.ga-can-scroll-left)::before,
     #myFieldWrapper:not(.ga-can-scroll-right)::after,
     #theirFieldWrapper:not(.ga-can-scroll-right)::after {
          opacity: 0;
     }

     #myFieldWrapper::before,
     #theirFieldWrapper::before {
          left: 0;
          background: linear-gradient(90deg, rgba(33, 63, 112, 0.88), rgba(33, 63, 112, 0.52) 42%, rgba(33, 63, 112, 0));
     }

     #myFieldWrapper::after,
     #theirFieldWrapper::after {
          right: 0;
          background: linear-gradient(270deg, rgba(33, 63, 112, 0.88), rgba(33, 63, 112, 0.52) 42%, rgba(33, 63, 112, 0));
     }

     #myFieldWrapper::-webkit-scrollbar,
     #theirFieldWrapper::-webkit-scrollbar {
          display: none;
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

     /* Left-side resource piles share one display model: visible overflow and
        left-aligned contents so memory/material/mastery stay visually consistent. */
     #myMemoryWrapper,
     #theirMemoryWrapper,
     #myMaterialWrapper,
     #theirMaterialWrapper,
     #myMasteryWrapper,
     #theirMasteryWrapper {
          overflow: visible !important;
     }

     /* Keep the left-side resource stack visually aligned with memory. */
     #myMemory,
     #theirMemory,
     #myMaterial,
     #theirMaterial,
     #myMastery,
     #theirMastery {
          justify-content: flex-start !important;
     }

     .ga-intent {
          width: min(15vw, 210px);
          min-height: 112px;
          z-index: 37;
     }

     .ga-stack {
          width: clamp(320px, 34vw, 560px);
          min-height: 86px;
          z-index: 37;
     }

     #myHandSlot,
     #theirHandSlot {
          left: 50%;
          transform: translateX(-50%);
          /* Raise above field zones (z-index 30) so hand cards stay clickable
             when the hand expands upward and overlaps the field area. */
          z-index: 36;
          overflow: visible;
          transition: transform 260ms cubic-bezier(0.4, 0, 0.2, 1), border-color 160ms ease, box-shadow 160ms ease;
     }

     /* Collapse/expand tab — a small pill that floats at the top edge of myHandSlot. */
     .ga-hand-collapse-btn {
          position: absolute;
          top: 0;
          left: 50%;
          transform: translateX(-50%) translateY(-50%);
          width: 48px;
          height: 18px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: rgba(19, 32, 43, 0.88);
          border: 1px solid rgba(244, 236, 219, 0.22);
          border-radius: 99px;
          cursor: pointer;
          color: rgba(244, 236, 219, 0.65);
          font-size: 9px;
          line-height: 1;
          padding: 0;
          pointer-events: auto;
          z-index: 2;
          transition: color 120ms ease, background 120ms ease, border-color 120ms ease;
          user-select: none;
          -webkit-user-select: none;
     }

     .ga-hand-collapse-btn:hover {
          color: rgba(244, 236, 219, 0.96);
          background: rgba(35, 55, 72, 0.96);
          border-color: rgba(200, 155, 70, 0.45);
     }

     /* Collapsed state: slide the hand panel off-screen, leaving only the
        collapse-button tab visible just above the screen's bottom edge. */
     #myHandSlot.is-collapsed {
          transform: translateX(-50%) translateY(calc(100% - 18px));
     }

     /* Suppress the ga-glass hover lift while collapsed. */
     #myHandSlot.is-collapsed:hover {
          transform: translateX(-50%) translateY(calc(100% - 18px)) !important;
          border-color: rgba(244, 236, 219, 0.16) !important;
          box-shadow: 0 20px 52px rgba(7, 14, 20, 0.30), inset 0 1px 0 rgba(255, 255, 255, 0.13) !important;
     }

     /* Opponent hand: collapses upward, leaving only the panel edge at screen top. */
     #theirHandSlot.is-collapsed {
          transform: translateX(-50%) translateY(calc(-100% + 18px));
     }

     #theirHandSlot.is-collapsed:hover {
          transform: translateX(-50%) translateY(calc(-100% + 18px)) !important;
          border-color: rgba(244, 236, 219, 0.16) !important;
          box-shadow: 0 20px 52px rgba(7, 14, 20, 0.30), inset 0 1px 0 rgba(255, 255, 255, 0.13) !important;
     }

     #myFieldSlot,
     #theirFieldSlot {
          left: 50%;
          right: auto;
          transform: translateX(-50%);
     }

     .ga-field-scroll-btn {
          position: absolute;
          top: 50%;
          transform: translateY(-50%);
          width: 30px;
          height: 44px;
          display: flex;
          align-items: center;
          justify-content: center;
          border: 1px solid rgba(244, 236, 219, 0.16);
          border-radius: 999px;
          background: linear-gradient(180deg, rgba(19, 32, 43, 0.94), rgba(19, 32, 43, 0.78));
          color: rgba(244, 236, 219, 0.88);
          box-shadow: 0 10px 24px rgba(7, 14, 20, 0.28);
          cursor: pointer;
          z-index: 38;
          transition: opacity 120ms ease, transform 120ms ease, border-color 120ms ease, background 120ms ease;
     }

     .ga-field-scroll-btn:hover {
          background: linear-gradient(180deg, rgba(36, 58, 77, 0.96), rgba(24, 40, 54, 0.9));
          border-color: rgba(200, 155, 70, 0.42);
     }

     .ga-field-scroll-btn.is-hidden,
     .ga-field-scroll-btn.is-disabled {
          opacity: 0;
          pointer-events: none;
     }

     .ga-field-scroll-btn-left {
          left: -16px;
     }

     .ga-field-scroll-btn-right {
          right: -16px;
     }

     #EffectStackSlot {
          left: 50%;
          transform: translateX(-50%);
          max-width: calc(100vw - 32px);
     }

     .ga-window-drag-handle {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 30px;
          cursor: grab;
          z-index: 2000;
          touch-action: none;
     }

     .ga-zone[data-dragging="true"] .ga-window-drag-handle {
          cursor: grabbing;
     }

     .ga-zone[data-dragging="true"] {
          cursor: grabbing;
          user-select: none;
     }

     .ga-zone.is-custom-position {
          transform: none !important;
     }

     #EffectStackWrapper {
          overflow-x: auto !important;
          overflow-y: auto !important;
          max-height: min(36vh, 280px);
          scrollbar-width: thin;
          -webkit-overflow-scrolling: touch;
     }

     #EffectStack {
          flex-wrap: nowrap !important;
          justify-content: flex-start !important;
          min-width: 100%;
     }

     #EffectStack > span {
          flex: 0 0 auto;
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

          .ga-stack {
               width: clamp(280px, 40vw, 500px);
          }
     }

     /* Desktop/laptop ultra-short-height fallback:
        keep lanes readable without reflowing the full side-stack layout. */
     @media (max-height: 760px) and (min-width: 1025px) {
          .ga-pile {
               width: 84px;
               min-height: 84px;
          }

          .ga-token-bank,
          #myMaterialSlot,
          #theirMaterialSlot,
          #myMasterySlot,
          #theirMasterySlot {
               width: 84px;
               min-height: 84px;
          }

          .ga-hand {
               min-height: 100px;
          }

          .ga-field {
               min-height: 126px;
          }

          #myFieldSlot {
               top: calc(50% + 34px) !important;
          }

          #theirFieldSlot {
               top: calc(50% - 34px - 126px) !important;
          }

          #myHealthSlot {
               top: calc(50% + 24px) !important;
          }

          #theirHealthSlot {
               top: 24px !important;
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

          .ga-stack {
               width: calc(100vw - 84px);
               min-height: 104px;
          }

          #EffectStackWrapper {
               max-height: min(34vh, 240px);
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
          #theirMaterialSlot,
          #myMasterySlot,
          #theirMasterySlot {
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

     @media (max-width: 1024px) {
          .ga-shortcut-dock {
               left: 8px;
               bottom: max(64px, env(safe-area-inset-bottom, 0px) + 14px);
               gap: 8px;
          }

          .ga-shortcut-panel {
               width: min(310px, calc(100vw - 76px));
               max-width: min(310px, calc(100vw - 76px));
               max-height: min(calc(100vh - 86px), 440px);
          }

          .ga-shortcut-panel-inner {
               padding: 14px 14px 18px;
               gap: 10px;
          }

          :root {
               --ga-mobile-topbar-h: 34px;
               --ga-mobile-gap: 6px;
               --ga-mobile-hand-h: 74px;
               --ga-mobile-bank-h: 62px;
               --ga-mobile-field-h: 84px;
          }

          /* Top utility row: chat + utility buttons. */
          #chatWidget {
               position: fixed !important;
               top: 4px !important;
               left: 8px !important;
               bottom: auto !important;
               width: auto !important;
               z-index: 2700 !important;
               flex-direction: row !important;
               align-items: flex-start !important;
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
               z-index: 2700 !important;
               padding: 6px 12px !important;
               font-size: 12px !important;
          }

          #concede-button {
               right: 8px !important;
          }

          #bug-report-button {
               right: 96px !important;
          }

          /* Explicit stacked board rows (top -> center -> bottom). */
          #theirHandSlot {
               top: calc(var(--ga-mobile-topbar-h) + var(--ga-mobile-gap)) !important;
               bottom: auto !important;
          }

          #theirDeckSlot,
          #theirGraveyardSlot,
          #theirBanishSlot,
          #theirMaterialSlot,
          #theirMasterySlot {
               top: calc(var(--ga-mobile-topbar-h) + var(--ga-mobile-gap) + var(--ga-mobile-hand-h) + var(--ga-mobile-gap)) !important;
               bottom: auto !important;
          }

          #theirDeckSlot { left: 4% !important; right: auto !important; }
          #theirMaterialSlot { left: 23% !important; right: auto !important; }
          #theirMasterySlot { left: 42% !important; right: auto !important; }
          #theirBanishSlot { left: 61% !important; right: auto !important; }
          #theirGraveyardSlot { left: 80% !important; right: auto !important; }

          #theirFieldSlot {
               top: calc(var(--ga-mobile-topbar-h) + var(--ga-mobile-gap) + var(--ga-mobile-hand-h) + var(--ga-mobile-gap) + var(--ga-mobile-bank-h) + var(--ga-mobile-gap)) !important;
               bottom: auto !important;
          }

          #myFieldSlot {
               top: calc(50% + 24px) !important;
               bottom: auto !important;
          }

          #myDeckSlot,
          #myGraveyardSlot,
          #myBanishSlot,
          #myMaterialSlot,
          #myMasterySlot {
               top: calc(50% + 24px + var(--ga-mobile-field-h) + var(--ga-mobile-gap)) !important;
               bottom: auto !important;
          }

          #myGraveyardSlot { left: 4% !important; right: auto !important; }
          #myBanishSlot { left: 23% !important; right: auto !important; }
          #myMasterySlot { left: 42% !important; right: auto !important; }
          #myMaterialSlot { left: 61% !important; right: auto !important; }
          #myDeckSlot { left: 80% !important; right: auto !important; }

          #myHandSlot {
               top: auto !important;
               bottom: 4px !important;
          }

          /* Hands should hug cards on mobile (no oversized panel feel). */
          .ga-hand {
               width: calc(100vw - 10px) !important;
               min-height: 0 !important;
          }

          #myHandSlot.ga-glass,
          #theirHandSlot.ga-glass {
               padding: 2px 4px !important;
          }

          /* Keep phase rail near center as requested. */
          .ga-phase-track {
               top: calc(50% + 3px);
          }

          /* Reduce overlap from low-priority/legacy zones in strict mobile board mode. */
          #myHealthSlot,
          #theirHealthSlot,
          #myIntentSlot,
          #theirIntentSlot,
          #myMemorySlot,
          #theirMemorySlot {
               display: none !important;
          }

          #EffectStackSlot {
               top: calc(50% - 98px) !important;
          }

          #myFieldWrapper,
          #theirFieldWrapper {
               padding-top: 8px;
               padding-bottom: 8px;
               margin-top: -8px;
               margin-bottom: -8px;
          }

          .ga-board-axis::after,
          .ga-side-banner,
          .ga-phase-step::before {
               display: none;
          }

          .ga-board-axis::before {
               width: calc(100vw - 20px);
               opacity: 0.65;
          }

          .ga-glass {
               border-radius: 14px;
               padding: 22px 8px 8px;
          }

          .ga-glass::before {
               font-size: 9px;
               letter-spacing: 0.13em;
          }

          .ga-glass::after {
               top: 19px;
          }

          .ga-phase-track {
               top: calc(50% + 3px);
               width: calc(100vw - 20px);
               justify-content: space-between;
               gap: 4px;
               font-size: 8px;
               letter-spacing: 0.07em;
               color: rgba(244, 236, 219, 0.54);
          }

          .ga-pile {
               width: 64px;
               min-height: 80px;
          }

          .ga-token-bank,
          #myMaterialSlot,
          #theirMaterialSlot,
          #myMasterySlot,
          #theirMasterySlot {
               width: 72px;
               min-height: 80px;
          }

          .ga-stat {
               width: 86px;
               min-height: 54px;
          }

          .ga-intent {
               width: 108px;
               min-height: 72px;
          }

          .ga-hand {
               width: calc(100vw - 16px);
               min-height: 92px;
          }

          .ga-field {
               width: calc(100vw - 32px);
               min-height: 84px;
          }

          .ga-stack {
               width: calc(100vw - 24px);
               min-height: 90px;
          }

          #EffectStackSlot {
               top: calc(50% - 60px);
          }

          #EffectStackWrapper {
               max-height: min(30vh, 180px);
          }

          #myFieldSlot {
               top: calc(50% + 38px) !important;
          }

          #theirFieldSlot {
               top: calc(50% - 38px - 84px) !important;
          }

          #myHealthSlot,
          #theirHealthSlot {
               right: 82px;
          }

          #myIntentSlot,
          #theirIntentSlot {
               left: 6px;
               top: calc(50% - 30px);
          }

          #myMemorySlot,
          #theirMemorySlot,
          #myMaterialSlot,
          #theirMaterialSlot,
          #myMasterySlot,
          #theirMasterySlot {
               left: 4px;
          }

          #myDeckSlot,
          #theirDeckSlot,
          #myBanishSlot,
          #theirBanishSlot,
          #myGraveyardSlot,
          #theirGraveyardSlot {
               right: 4px;
          }

          #myBanishSlot.ga-banish-empty,
          #theirBanishSlot.ga-banish-empty,
          #myGraveyardSlot.ga-graveyard-empty,
          #theirGraveyardSlot.ga-graveyard-empty {
               min-height: 64px;
          }
     }

     @media (max-width: 1024px) and (max-height: 500px) {
          .ga-phase-track {
               display: none;
          }

          .ga-hand {
               min-height: 82px;
          }

          .ga-field {
               min-height: 78px;
          }

          #myFieldSlot {
               top: calc(50% + 32px) !important;
          }

          #theirFieldSlot {
               top: calc(50% - 32px - 78px) !important;
          }

          #myGraveyardSlot,
          #theirGraveyardSlot {
               display: none;
          }
     }

     @media (max-width: 760px) {
          .ga-shortcut-dock {
               bottom: max(76px, env(safe-area-inset-bottom, 0px) + 16px);
          }

          .ga-shortcut-panel {
               width: min(300px, calc(100vw - 68px));
               max-width: min(300px, calc(100vw - 68px));
               max-height: min(calc(100vh - 104px), 430px);
          }

          .ga-phase-track {
               display: none;
          }

          .ga-board-axis::before {
               opacity: 0.45;
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
<div class="ga-kb-hints" aria-hidden="true">
     <span><kbd>U</kbd> Undo</span>
     <span><kbd>S</kbd> Save</span>
     <span><kbd>Space</kbd> Pass</span>
     <span><kbd>↓</kbd> Collapse hand</span>
     <span><kbd>↑</kbd> Expand hand</span>
     <?php if (function_exists('GAGameMode') && GAGameMode() === 'hotseat'): ?>
     <span><kbd>W</kbd> Switch Player</span>
     <?php endif; ?>
</div>
<?php if (function_exists('GAGameMode') && GAGameMode() === 'hotseat'): ?>
<!-- Hotseat: hand the device to the other player — reloads as the other seat (shared authKey). -->
<button id="gaSwitchPlayerBtn" type="button" onclick="window.gaSwitchPlayer();"
        style="position: fixed; z-index: 40; bottom: 12px; left: 50%; transform: translateX(-50%);
               padding: 8px 16px; background: rgba(40,40,40,0.95); color: #fff; border: 2px solid #9f7a2f;
               border-radius: 8px; cursor: pointer; font-size: 14px;">Switch Player (W)</button>
<?php endif; ?>

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

<div id="gaShortcutDock" class="ga-shortcut-dock is-collapsed" aria-live="polite">
     <button id="gaShortcutTab" class="ga-shortcut-tab" type="button" aria-expanded="false" aria-controls="gaShortcutPanel">
          ⚡ Shortcuts
     </button>
     <div id="gaShortcutPanel" class="ga-shortcut-panel"></div>
</div>

<script>
(function() {
     // Hotseat: one person plays both seats from one browser (shared authKey). Switch reloads the
     // page as the OTHER seat. No-op in non-hotseat games. GA-local (no Core/ edit).
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

     // Game-over screen for match games (only match games carry a MatchId in their gamestate). The native
     // game-over trigger in NextTurn.php calls window.GAShowEndGameMenu (the way it calls SWUShowEndGameMenu
     // for SWUSim) with the pre-built card-activity matrix, so ONE overlay carries the matrix + Save Replay
     // + our nav buttons: Return to Main Menu, Rematch, Convert to Best of 3, Report Bug —
     // plus "Go to Next Game" for mid-series/sideboard states. We then poll EndGameInfo and rebuild the
     // overlay (KEEPING the matrix) only when the match state changes (opponent confirms a convert, or a
     // rematch spawns). GA-local — no Core/ edit; ShowGameOver/SubmitInput/openBugReportModal/
     // BuildMacroGameStatsHtml all come from Core JS. No Block Player (GA has no login yet).
     window.GAMatchId = <?php echo json_encode(class_exists('DecisionQueueController') ? strval(DecisionQueueController::GetVariable('MatchId') ?? '') : ''); ?>;
     (function() {
          if (!window.GAMatchId) return;   // goldfish / hotseat / non-match game → leave native ShowGameOver alone
          function gaAppBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0 ? p.slice(0, i+11) : '/TCGEngine/'; }
          var url = new URL(window.location.href);
          var pid = url.searchParams.get('playerID') || '1';
          var authKey = url.searchParams.get('authKey') || '';
          var gameName = url.searchParams.get('gameName') || '';
          var menuUrl = gaAppBase() + 'SharedUI/MainMenu.php';
          var statsHtml = '';   // the card-activity matrix; cached so state-change rebuilds keep showing it

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
               if (btn) { btn.textContent = 'Rematch Requested'; btn.disabled = true; }
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
               if (info.sideboardPending || info.nextGameName) {   // series continues → advance to the next game
                    b.push({label:'Go to Next Game', onClick:function(){ gaGoNext(info); }});
                    b.push({label:'Return to Main Menu', onClick: gaGoMenu});
                    b.push({label:'Report Bug', onClick: gaReportBug});
                    return b;
               }
               if (info.seriesOver) {                              // Bo1 done (or Bo3 decided) → rematch options
                    var bo = (info.bestOf === 3) ? 3 : 1;
                    var rematchLabel = 'Rematch', rematchDisabled = false;
                    if (info.rematchRequestedByMe && !info.rematchRequestedByOpp) {
                         rematchLabel = 'Rematch Requested';
                         rematchDisabled = true;
                    } else if (info.rematchRequestedByOpp && !info.rematchRequestedByMe) {
                         rematchLabel = 'Accept Rematch';
                    }
                    b.push({label:'Return to Main Menu', onClick: gaGoMenu});
                    b.push({id:'ga-rematch-btn', label: rematchLabel, disabled: rematchDisabled, onClick:function(){ gaSubmitRematch(bo); }});
                    if (info.convertible) {
                         var lbl = 'Convert to Best of 3', dis = false;
                         if (info.convertRequestedByMe && !info.convertRequestedByOpp) { lbl = 'Waiting on opponent…'; dis = true; }
                         else if (info.convertRequestedByOpp && !info.convertRequestedByMe) { lbl = 'Confirm Convert to Best of 3'; }
                         b.push({id:'ga-convert-btn', label: lbl, disabled: dis, onClick:function(){ SubmitInput('10012',''); }});
                    }
                    b.push({label:'Report Bug', onClick: gaReportBug});
                    return b;
               }
               b.push({label:'Return to Main Menu', onClick: gaGoMenu});   // fallback (over, unknown state)
               b.push({label:'Report Bug', onClick: gaReportBug});
               return b;
          }

          function gaRenderOverlay(info){
               var ex = document.getElementById('game-over-overlay'); if (ex && ex.remove) ex.remove();
               if (typeof ShowGameOver === 'function') ShowGameOver(!!info.didWin, menuUrl, statsHtml, gaBuildButtons(info));
          }

          var lastSig = null;
          function gaCheckMatchEnd(force) {
               return fetch(gaAppBase() + 'GrandArchiveSim/EndGameInfo.php?gameName=' + encodeURIComponent(gameName)
                    + '&playerID=' + encodeURIComponent(pid) + '&authKey=' + encodeURIComponent(authKey)
                    + '&folderPath=GrandArchiveSim')
                    .then(function(r){ return r.json(); })
                    .then(function(info) {
                         if (!info || !info.gameWinner) return;   // game not over yet
                         var sig = [info.sideboardPending, info.nextGameName, info.seriesOver, info.convertible,
                                    info.convertRequestedByMe, info.convertRequestedByOpp,
                                    info.rematchRequestedByMe, info.rematchRequestedByOpp].join('|');
                         if (sig === lastSig && !force) return;   // no change → leave the current overlay be
                         lastSig = sig;
                         gaRenderOverlay(info);
                    })
                    .catch(function(){});
          }

          // Called by NextTurn.php's native game-over trigger (with the pre-built matrix). Shows our unified
          // overlay, then starts the state-change poll (convert confirm / rematch spawn rebuild the overlay).
          var gaPollStarted = false;
          window.GAShowEndGameMenu = function(prebuiltStats){
               statsHtml = prebuiltStats || ((typeof BuildMacroGameStatsHtml === 'function') ? BuildMacroGameStatsHtml(pid) : '');
               gaCheckMatchEnd(true);
               if (!gaPollStarted) { gaPollStarted = true; setInterval(gaCheckMatchEnd, 3000); }
          };
     })();

     // App-level turn indicator config hook (consumed by Core/UILibraries20260703.js).
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

     function setupDraggablePanel(slotId, positionStorageKey) {
          var slot = document.getElementById(slotId);
          if (!slot) return;

          var dragState = null;
          slot.setAttribute('data-draggable', 'true');

          function ensureDragHandle() {
               var existing = slot.querySelector(':scope > .ga-window-drag-handle');
               if (existing) return existing;

               var handle = document.createElement('div');
               handle.className = 'ga-window-drag-handle';
               handle.setAttribute('title', 'Move window');
               handle.setAttribute('aria-hidden', 'true');
               slot.insertBefore(handle, slot.firstChild);
               attachHandleListeners(handle);
               return handle;
          }

          function clamp(value, min, max) {
               return Math.min(max, Math.max(min, value));
          }

          function applyPosition(left, top) {
               var rect = slot.getBoundingClientRect();
               var maxLeft = Math.max(8, window.innerWidth - rect.width - 8);
               var maxTop = Math.max(8, window.innerHeight - rect.height - 8);
               var boundedLeft = clamp(left, 8, maxLeft);
               var boundedTop = clamp(top, 8, maxTop);
               slot.classList.add('is-custom-position');
               slot.style.left = boundedLeft + 'px';
               slot.style.top = boundedTop + 'px';
               slot.style.right = 'auto';
               slot.style.bottom = 'auto';
          }

          function savePosition() {
               var left = parseFloat(slot.style.left);
               var top = parseFloat(slot.style.top);
               if (!Number.isFinite(left) || !Number.isFinite(top)) return;
               try {
                    localStorage.setItem(positionStorageKey, JSON.stringify({ left: left, top: top }));
               } catch (e) {
                    // Ignore storage failures (private mode, blocked storage, etc.)
               }
          }

          function loadPosition() {
               var raw = null;
               try {
                    raw = localStorage.getItem(positionStorageKey);
               } catch (e) {
                    return;
               }
               if (!raw) return;
               try {
                    var parsed = JSON.parse(raw);
                    if (!parsed || !Number.isFinite(parsed.left) || !Number.isFinite(parsed.top)) return;
                    applyPosition(parsed.left, parsed.top);
               } catch (e) {
                    // Ignore malformed saved state.
               }
          }

          function finishDrag() {
               if (!dragState) return;
               dragState = null;
               slot.removeAttribute('data-dragging');
               savePosition();
          }

          function beginDrag(clientX, clientY, button) {
               if (button !== 0) return false;
               var rect = slot.getBoundingClientRect();
               dragState = {
                    startX: clientX,
                    startY: clientY,
                    startLeft: rect.left,
                    startTop: rect.top
               };
               slot.setAttribute('data-dragging', 'true');
               slot.classList.add('is-custom-position');
               return true;
          }

          function moveDrag(clientX, clientY) {
               if (!dragState) return;
               var nextLeft = dragState.startLeft + (clientX - dragState.startX);
               var nextTop = dragState.startTop + (clientY - dragState.startY);
               applyPosition(nextLeft, nextTop);
          }

          function attachHandleListeners(handle) {
               handle.addEventListener('mousedown', function(ev) {
                    if (beginDrag(ev.clientX, ev.clientY, ev.button)) ev.preventDefault();
               });

               handle.addEventListener('touchstart', function(ev) {
                    if (!ev.touches || ev.touches.length === 0) return;
                    var touch = ev.touches[0];
                    if (beginDrag(touch.clientX, touch.clientY, 0)) ev.preventDefault();
               }, { passive: false });
          }

          window.addEventListener('mousemove', function(ev) {
               moveDrag(ev.clientX, ev.clientY);
          });

          window.addEventListener('mouseup', finishDrag);

          window.addEventListener('touchmove', function(ev) {
               if (!dragState || !ev.touches || ev.touches.length === 0) return;
               var touch = ev.touches[0];
               moveDrag(touch.clientX, touch.clientY);
               ev.preventDefault();
          }, { passive: false });

          window.addEventListener('touchend', finishDrag);
          window.addEventListener('touchcancel', finishDrag);

          window.addEventListener('resize', function() {
               if (!slot.classList.contains('is-custom-position')) return;
               var left = parseFloat(slot.style.left);
               var top = parseFloat(slot.style.top);
               if (!Number.isFinite(left) || !Number.isFinite(top)) return;
               applyPosition(left, top);
               savePosition();
          });

          new MutationObserver(function() { ensureDragHandle(); })
               .observe(slot, { childList: true });

          ensureDragHandle();
          loadPosition();
     }

     function setupMovableWindows() {
          setupDraggablePanel('EffectStackSlot', 'ga-effect-stack-position-v1');
          setupDraggablePanel('myIntentSlot', 'ga-my-intent-position-v1');
          setupDraggablePanel('theirIntentSlot', 'ga-their-intent-position-v1');
     }

     function setupHandCollapse() {
          var slot = document.getElementById('myHandSlot');
          var theirSlot = document.getElementById('theirHandSlot');
          if (!slot) return;
          var storageKey = 'ga-hand-collapsed-v1';
          var collapsed = false;
          try { collapsed = localStorage.getItem(storageKey) === '1'; } catch (e) {}

          function createBtn() {
               var b = document.createElement('button');
               b.className = 'ga-hand-collapse-btn';
               b.setAttribute('type', 'button');
               b.setAttribute('title', 'Collapse/expand hand');
               b.textContent = collapsed ? '\u25b2' : '\u25bc';
               b.setAttribute('aria-label', collapsed ? 'Expand hand' : 'Collapse hand');
               b.addEventListener('click', function (ev) {
                    ev.stopPropagation();
                    setCollapsed(!slot.classList.contains('is-collapsed'));
               });
               return b;
          }

          function ensureButton() {
               if (!slot.querySelector('.ga-hand-collapse-btn')) {
                    slot.insertBefore(createBtn(), slot.firstChild);
               }
          }

          function setCollapsed(c) {
               collapsed = c;
               slot.classList.toggle('is-collapsed', c);
               if (theirSlot) theirSlot.classList.toggle('is-collapsed', c);
               var b = slot.querySelector('.ga-hand-collapse-btn');
               if (b) {
                    b.textContent = c ? '\u25b2' : '\u25bc';
                    b.setAttribute('aria-label', c ? 'Expand hand' : 'Collapse hand');
               }
               try { localStorage.setItem(storageKey, c ? '1' : '0'); } catch (e) {}
          }

          window.GAHandCollapse = {
               toggle:   function() { setCollapsed(!slot.classList.contains('is-collapsed')); },
               collapse: function() { setCollapsed(true); },
               expand:   function() { setCollapsed(false); }
          };

          // NextTurnRender replaces myHandSlot.innerHTML on every poll, which removes
          // the button. Watch for child-list mutations and re-inject it each time.
          new MutationObserver(function () { ensureButton(); })
               .observe(slot, { childList: true });

          ensureButton();
          if (collapsed) {
               slot.classList.add('is-collapsed');
               if (theirSlot) theirSlot.classList.add('is-collapsed');
          }
     }

     function setupBoardTheme() {
          var boardArt = document.querySelector('.ga-board-art');
          if (!boardArt) return;

          var rootName = 'GrandArchiveSim';
          var settingKey = 'BoardBackgroundTheme';
          var defaultTheme = 'dawn';

          if (window.TCGSettings && typeof window.TCGSettings.registerSchema === 'function') {
               window.TCGSettings.registerSchema(rootName, {
                    BoardBackgroundTheme: { type: 'string', defaultValue: defaultTheme }
               });
          }

          function applyTheme(theme) {
               var normalized = (theme === 'classic') ? 'classic' : 'dawn';
               boardArt.classList.remove('is-dawn-of-ashes', 'is-classic-blue');
               boardArt.classList.add(normalized === 'classic' ? 'is-classic-blue' : 'is-dawn-of-ashes');
               boardArt.setAttribute('data-board-theme', normalized);
               return normalized;
          }

          function getStoredTheme() {
               if (!window.TCGSettings || typeof window.TCGSettings.get !== 'function') return defaultTheme;
               return window.TCGSettings.get(settingKey, { rootName: rootName, type: 'string', defaultValue: defaultTheme });
          }

          function setStoredTheme(theme) {
               if (!window.TCGSettings || typeof window.TCGSettings.set !== 'function') return;
               window.TCGSettings.set(settingKey, theme, { rootName: rootName, type: 'string' });
          }

          var activeTheme = applyTheme(getStoredTheme());

          window.GABoardTheme = {
               get: function() { return activeTheme; },
               set: function(theme) {
                    activeTheme = applyTheme(theme);
                    setStoredTheme(activeTheme);
                    return activeTheme;
               },
               toggle: function() {
                    return this.set(activeTheme === 'classic' ? 'dawn' : 'classic');
               }
          };
     }

     function setupFieldScrollButtons() {
          function installForSlot(slotId, wrapperId) {
               var slot = document.getElementById(slotId);
               if (!slot) return;
               var leftBtn = null;
               var rightBtn = null;
               var positionStorageKey = 'ga-field-scroll-v1-' + wrapperId;
               var lastKnownScrollLeft = 0;

               function ensureButton(side) {
                    var className = '.ga-field-scroll-btn-' + side;
                    var existing = slot.querySelector(className);
                    if (existing) return existing;
                    var btn = document.createElement('button');
                    btn.className = 'ga-field-scroll-btn ga-field-scroll-btn-' + side + ' is-hidden is-disabled';
                    btn.setAttribute('type', 'button');
                    btn.setAttribute('aria-label', side === 'left' ? 'Scroll field left' : 'Scroll field right');
                    btn.textContent = side === 'left' ? '\u2039' : '\u203a';
                    slot.appendChild(btn);
                    return btn;
               }

               function ensureButtons() {
                    leftBtn = ensureButton('left');
                    rightBtn = ensureButton('right');
               }

               ensureButtons();

               function getWrapper() {
                    return document.getElementById(wrapperId);
               }

               function readSavedScrollLeft() {
                    try {
                         var raw = localStorage.getItem(positionStorageKey);
                         var parsed = parseFloat(raw || '');
                         return Number.isFinite(parsed) ? Math.max(0, parsed) : 0;
                    } catch (e) {
                         return 0;
                    }
               }

               function saveScrollLeft(value) {
                    lastKnownScrollLeft = Math.max(0, value);
                    try {
                         localStorage.setItem(positionStorageKey, String(lastKnownScrollLeft));
                    } catch (e) {
                         // Ignore storage failures.
                    }
               }

               function restoreScrollLeft() {
                    var wrapper = getWrapper();
                    if (!wrapper) return;
                    var maxScroll = Math.max(0, wrapper.scrollWidth - wrapper.clientWidth);
                    var target = Math.min(maxScroll, Math.max(0, lastKnownScrollLeft));
                    if (Math.abs(wrapper.scrollLeft - target) <= 1) return;
                    wrapper.scrollLeft = target;
               }

               function updateButtons() {
                    ensureButtons();
                    var wrapper = getWrapper();
                    if (!wrapper) {
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
                    wrapper.classList.toggle('ga-can-scroll-left', canScrollLeft);
                    wrapper.classList.toggle('ga-can-scroll-right', canScrollRight);
               }

               function scrollByAmount(dir) {
                    var wrapper = getWrapper();
                    if (!wrapper) return;
                    var amount = Math.max(180, Math.floor(wrapper.clientWidth * 0.72));
                    wrapper.scrollBy({ left: dir * amount, behavior: 'smooth' });
                    window.setTimeout(updateButtons, 220);
               }

               function bindButtonHandlers(btn, dir) {
                    if (!btn || btn.dataset.gaScrollBound === '1') return;
                    btn.dataset.gaScrollBound = '1';
                    btn.addEventListener('click', function(ev) {
                         ev.preventDefault();
                         if (btn.classList.contains('is-disabled')) return;
                         scrollByAmount(dir);
                    });
               }

               function bindWrapper() {
                    var wrapper = getWrapper();
                    if (!wrapper || wrapper.dataset.gaScrollButtonsBound === '1') return;
                    wrapper.dataset.gaScrollButtonsBound = '1';
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

          installForSlot('myFieldSlot', 'myFieldWrapper');
          installForSlot('theirFieldSlot', 'theirFieldWrapper');
     }

     function setupShortcutPanel() {
          if (typeof IsSpectatorClient === 'function' && IsSpectatorClient()) return;

          var dock = document.getElementById('gaShortcutDock');
          var tab = document.getElementById('gaShortcutTab');
          var panel = document.getElementById('gaShortcutPanel');
          if (!dock || !tab || !panel) return;

          var rootName = 'GrandArchiveSim';
          var settingKey = 'ShortcutPreferences';
          var collapsedKey = 'ga-shortcut-panel-collapsed-v1';
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
               dock.style.display = 'none';
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
               return {
                    version: 1,
                    windows: normalizedWindows
               };
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
                         '<div class="ga-shortcut-row" data-window-id="' + entry.id + '">' +
                              '<div class="ga-shortcut-row-label">' + entry.label + '</div>' +
                              '<button class="ga-shortcut-toggle' + (checked ? ' is-on' : '') + '" type="button" aria-pressed="' + (checked ? 'true' : 'false') + '" data-window-id="' + entry.id + '"></button>' +
                         '</div>';
               }).join('');

               panel.innerHTML = '' +
                    '<div id="gaShortcutPanelInner" class="ga-shortcut-panel-inner">' +
                         '<div class="ga-shortcut-header">' +
                              '<div class="ga-shortcut-title">⚡ Shortcut Windows</div>' +
                              '<div class="ga-shortcut-copy">Toggle windows you want the server to auto-pass for this player.</div>' +
                         '</div>' +
                         '<div class="ga-shortcut-list">' + rows + '</div>' +
                         '<div class="ga-shortcut-footer">Changes persist locally and sync to the current game when you join or toggle them.</div>' +
                    '</div>';

               Array.prototype.forEach.call(panel.querySelectorAll('.ga-shortcut-toggle'), function(toggle) {
                    toggle.addEventListener('click', function(ev) {
                         ev.preventDefault();
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

          function setCollapsed(isCollapsed) {
               dock.classList.toggle('is-collapsed', isCollapsed);
               tab.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
               try {
                    localStorage.setItem(collapsedKey, isCollapsed ? '1' : '0');
               } catch (e) {}
          }

          tab.addEventListener('click', function() {
               setCollapsed(!dock.classList.contains('is-collapsed'));
          });

          var initialPayload = getStoredPayload();
          storePayload(initialPayload);
          renderPanel(initialPayload);
          syncPayloadToServer(initialPayload);

          var collapsed = true;
          try {
               collapsed = localStorage.getItem(collapsedKey) !== '0';
          } catch (e) {
               collapsed = true;
          }
          setCollapsed(collapsed);
     }

     // Run once DOM is ready (GameLayout.php is included after DOMContentLoaded equivalent)
     if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', function() {
               AUTO_HIDE_IDS.forEach(watchSlot);
               EMPTY_STATE_SLOTS.forEach(function(s) { watchEmptyStateSlot(s.id, s.cls); });
               watchPhaseData();
               setupMovableWindows();
               setupHandCollapse();
               setupBoardTheme();
               setupFieldScrollButtons();
               setupShortcutPanel();
          });
     } else {
          AUTO_HIDE_IDS.forEach(watchSlot);
          EMPTY_STATE_SLOTS.forEach(function(s) { watchEmptyStateSlot(s.id, s.cls); });
          watchPhaseData();
          setupMovableWindows();
          setupHandCollapse();
          setupBoardTheme();
          setupFieldScrollButtons();
          setupShortcutPanel();
     }
})();
</script>
