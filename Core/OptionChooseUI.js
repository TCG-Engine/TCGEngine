/**
 * OptionChooseUI.js - Labeled multi-option picker for the Decision Queue.
 *
 * Renders a centered banner with one button per option; clicking submits that
 * option's label verbatim. Used for "choose an arena" style decisions
 * (SOR_221 Outmaneuver: "Ground" / "Space").
 *
 * Decision queue Param format: "[@CardID&]Opt1&Opt2[&Opt3...]"
 *   e.g. "Ground&Space"
 *   A leading "@CardID" segment (e.g. "@SOR_157&Play&Discard&Leave") is rendered as the card
 *   being acted on (its image is shown above the options) and is NOT a selectable option.
 *
 * Return value: the chosen option label as a string (e.g. "Ground").
 *
 * Deprecated for new card-authoring. Prefer MZMODAL / await $player.Modal(...)
 * for new finite labeled choices; keep this file for existing queued paths.
 *
 * Usage (called from the decision dispatcher in UILibraries.js):
 *   ShowOptionChooseUI(paramString, tooltip, decisionIndex, submitCallback)
 */

(function() {
  'use strict';

  const OPTION_CHOOSE_STYLES = `
    /* ⚠ THIS BANNER IS CENTER-ANCHORED, SO ANY OVERFLOW SPILLS OFF **BOTH** EDGES.
       It is fixed + translateX(-50%) here, and SWUSim's HUD sweep (GameLayoutShared.php) re-centers it
       on both axes. A center-anchored box cannot be scrolled back into view, so content that does not
       fit is not merely ugly — it is UNREACHABLE. It must therefore WRAP rather than grow.
       Reported 2026-09-25 (game 1310334): with three opponents to choose between, all three buttons
       rendered past the right edge of a 390px phone and the prompt could not be answered at all. */
    .optchoose-banner {
      position: fixed;
      bottom: 16px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
      gap: 18px;
      padding: 16px 32px;
      max-width: min(92vw, 760px);
      box-sizing: border-box;
      background: linear-gradient(145deg, #0D1B2A, #162d44);
      border: 1.5px solid rgba(95,208,255,0.45);
      border-radius: 14px;
      box-shadow: 0 0 24px rgba(95,208,255,0.25), 0 4px 24px rgba(0,0,0,0.5);
      font-family: 'Orbitron', 'Segoe UI', monospace;
      user-select: none;
    }
    /* ⚠ NOT flex-shrink:0. Both children used to refuse to shrink AND the row could not wrap, so the
       banner's max-width was decorative — the children simply overflowed it. */
    .optchoose-label {
      color: #d0ecff;
      font-size: 14px;
      max-width: 260px;
      min-width: 0;
      text-align: center;
    }
    /* Card strip scrolls horizontally so a large searched zone (e.g. a 30+ card deck)
       stays within the 80vw banner while the prompt and OK button remain visible. */
    .optchoose-cards {
      display: flex;
      gap: 8px;
      align-items: center;
      flex: 1 1 auto;
      min-width: 0;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-width: thin;
      padding-bottom: 6px;
    }
    .optchoose-cards::-webkit-scrollbar { height: 8px; }
    .optchoose-cards::-webkit-scrollbar-thumb {
      background: rgba(95,208,255,0.4);
      border-radius: 4px;
    }
    .optchoose-card {
      height: 132px;
      border-radius: 6px;
      border: 1px solid rgba(95,208,255,0.5);
      box-shadow: 0 0 10px rgba(95,208,255,0.3);
      display: block;
      flex: 0 0 auto;
    }
    .optchoose-options { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; min-width: 0; }
    /* Skin from .btn (button.css); layout only kept here. */
    .optchoose-btn { padding: 10px 24px; font-size: 15px; max-width: 100%; }

    /* Phone: give the prompt its own full-width row so the options always get the banner's whole
       width, and trim the chrome that was eating it. The options themselves still wrap.
       ⚠ The option labels here are USERNAMES (optionDisplayLabel humanises the P<n> seat tokens), so
       they are arbitrarily long and arbitrarily many — this must not assume a short "Ground"/"Space"
       pair, which is what the original sizing was built for. */
    @media (max-width: 640px) {
      .optchoose-banner  { max-width: 94vw; padding: 12px 14px; gap: 10px; }
      .optchoose-label   { flex-basis: 100%; max-width: 100%; }
      .optchoose-options { flex-basis: 100%; }
      .optchoose-btn     { padding: 10px 16px; font-size: 14px; }
      .optchoose-card    { height: 104px; }
    }
  `;

  let styleEl = null;
  let bannerEl = null;

  function injectStyles() {
    if (styleEl) return;
    styleEl = document.createElement('style');
    styleEl.textContent = OPTION_CHOOSE_STYLES;
    document.head.appendChild(styleEl);
  }

  function render(options, tooltip, decisionIndex, submitCallback, cardIDs) {
    if (bannerEl) bannerEl.remove();

    bannerEl = document.createElement('div');
    bannerEl.className = 'optchoose-banner';

    // Optional: the card(s) being acted on, shown to the left of the prompt.
    if (cardIDs && cardIDs.length) {
      const cardsWrap = document.createElement('div');
      cardsWrap.className = 'optchoose-cards';
      // Shared SWU art corpus — see window.assetImageFolder (NextTurnRender.php); the rootPath form
      // resolves to the deleted ./SWUSim/concat tree and 404s.
      const imgBase = (window.assetImageFolder || ((window.rootPath || '.') + '/concat')) + '/';
      cardIDs.forEach(function(cid) {
        const img = document.createElement('img');
        img.className = 'optchoose-card';
        // Preview (mock) cards are stored as mock_<CardID>.webp — resolve, never use the raw CardID.
        img.src = imgBase + (typeof resolveCardImageID === 'function' ? resolveCardImageID(cid) : cid) + '.webp';
        img.alt = cid;
        cardsWrap.appendChild(img);
      });
      bannerEl.appendChild(cardsWrap);
    }

    const label = document.createElement('div');
    label.className = 'optchoose-label';
    label.textContent = tooltip;

    const optionsWrap = document.createElement('div');
    optionsWrap.className = 'optchoose-options';

    options.forEach(function(opt) {
      const btn = document.createElement('button');
      btn.className = 'optchoose-btn btn';
      // DISPLAY is humanised; the SUBMITTED value is always the raw option (see optionDisplayLabel).
      btn.textContent = optionDisplayLabel(opt);
      btn.addEventListener('click', function() {
        submitCallback(opt, decisionIndex);
        HideOptionChooseUI();
      });
      optionsWrap.appendChild(btn);
    });

    bannerEl.appendChild(label);
    bannerEl.appendChild(optionsWrap);
    document.body.appendChild(bannerEl);
  }

  /**
   * Seat tokens -> something a human recognises, for the "choose an opponent" picker
   * (SWUQueueChooseOpponent emits its options as the raw seat tokens "P2", "P3", "P4").
   *
   * ⚠ DISPLAY ONLY. The button still SUBMITS the untouched option string, because the server parses it
   * back with /^P(\d+)$/ (SWUPickedOpponent). Never let the rendered text reach submitCallback, and
   * never move this humanising server-side: a username is arbitrary user input and the decision Param is
   * a delimited transport, so a name containing "&" or a space would corrupt the queue row.
   *
   * Name resolution: the seat's account username when it has one, else its match display name ("Guest PN",
   * window.SWU_SEAT_DISPLAY_NAMES), else "Player N" (a game outside the match system). window.SWU_SEAT_USERNAMES
   * is published per board render and deliberately contains ONLY seats with a real account (userId > 0).
   * Gated on that global EXISTING so other sims sharing this Core file are untouched; inside SWUSim it is
   * always defined (an empty object when nobody is logged in).
   */
  function optionDisplayLabel(opt) {
    // Underscores are a TRANSPORT artifact, not typography: DecisionQueue's Param is space-delimited,
    // so a multi-word option label has to be written "Replace_Raid_With_Restore" server-side. Render it
    // as words. Display only — the button still SUBMITS the untouched string (see the note below), and
    // no option label uses an underscore for any other purpose.
    opt = String(opt).replace(/_/g, ' ');
    if (!window.SWU_SEAT_USERNAMES) return opt;
    const m = /^P(\d+)$/.exec(String(opt));
    if (!m) return opt;                       // "You", "Opponent", "Ground", … pass through untouched
    const seat = m[1];
    const name = window.SWU_SEAT_USERNAMES[seat];
    if (name && String(name).trim() !== '') return String(name);
    const shown = window.SWU_SEAT_DISPLAY_NAMES ? window.SWU_SEAT_DISPLAY_NAMES[seat] : null;
    return (shown && String(shown).trim() !== '') ? String(shown) : ('Player ' + seat);
  }

  /**
   * @param {string} paramString - "Opt1&Opt2[&...]"
   * @param {string} tooltip - Human-readable prompt
   * @param {number} decisionIndex - Index in the decision queue
   * @param {function} submitCallback - Called with (optionLabel, decisionIndex)
   */
  window.ShowOptionChooseUI = function(paramString, tooltip, decisionIndex, submitCallback) {
    injectStyles();
    const segs = String(paramString || '').split('&').filter(function(o) { return o !== ''; });
    // Segments prefixed with "@" are card images (the card being acted on), not options.
    const cardIDs = [];
    const options = [];
    segs.forEach(function(s) {
      if (s.charAt(0) === '@') cardIDs.push(s.slice(1));
      else options.push(s);
    });
    render(options, tooltip, decisionIndex, submitCallback, cardIDs);
  };

  window.HideOptionChooseUI = function() {
    if (bannerEl) { bannerEl.remove(); bannerEl = null; }
  };
})();
