/**
 * Shared private-invite lobby UI, used by every sim's MainMenu.
 *
 * When the page URL carries a private invite code, the visitor is JOINING someone else's lobby. The
 * only sensible action is "Join Private Invite", so this module:
 *   - reveals the Join Private Invite button and the notice line;
 *   - HIDES the competing actions ("Create Private Game", "Join Queue") — either one abandons the
 *     invite the visitor followed;
 *   - DISABLES the format / match-type selects, because the server adopts the HOST lobby's settings
 *     for an invite join (see APIs/Lobbies/JoinQueue.php) and leaving them live implies a choice
 *     that no longer exists.
 *
 * ⚠ Re-enforcement is the whole reason this is a module. Each sim's own menu script owns those same
 * controls and re-runs on format changes (e.g. SWUSim's applyFormatUI), which would silently undo the
 * hides. Rather than requiring every sim to remember the gate in every branch, this module re-applies
 * itself after any change/input event on the document (bubble phase, so it lands AFTER the sim's own
 * handler). Sims may still gate their own code as belt-and-braces; applying twice is harmless.
 *
 * Defaults resolve every sim without configuration:
 *   - buttons match either an explicit id OR the legacy inline-onclick markup;
 *   - selects match the "<prefix>-format-select" / "<prefix>-queuetype-select" convention
 *     (swu-*, ga-*), and simply match nothing in sims that have no format picker (AzukiSim).
 */
(function () {
  'use strict';

  var DEFAULTS = {
    joinBtn:    '#join-private-invite-btn',
    createBtn:  '#create-private-game-btn, button[onclick="createPrivateGame()"]',
    queueBtn:   '#join-queue-btn, button[onclick="joinQueue()"]',
    notice:     '#private-invite-notice',
    selects:    '[id$="-format-select"], [id$="-queuetype-select"]',
    // Some sims reveal the join button with a CSS class rather than by clearing inline display
    // (AzukiSim uses .is-visible). When set, the class is added instead of touching style.display.
    joinBtnVisibleClass: null,
    noticeText: 'Private invite detected. Format and match type are set by the invite — just choose ' +
                'your deck, then click Join Private Invite.',
    // Shown instead of noticeText when the link carries &casterMode=1.
    noticeTextCaster: null,
    // Which sim to look the invite up under. Required for the lookup that reveals the host's
    // format/match type; without it the selects are still disabled, just not corrected.
    rootName: null,
    lookupUrl: '/TCGEngine/APIs/Lobbies/GetLobbies.php'
  };

  // Values are stored normalized (e.g. 'premier', 'bo3'); these are for display only.
  var LABELS = {
    premier: 'Premier', open: 'Open', eternal: 'Eternal', twinsuns: 'Twin Suns',
    padawan: 'Padawan', 'padawan-preview': 'Padawan Preview',
    'twinsuns-preview': 'Twin Suns (Preview)', preview: 'Preview', standard: 'Standard',
    bo1: 'Best of 1', bo3: 'Best of 3'
  };
  function label(v) { return LABELS[v] || v; }

  function all(sel) {
    if (!sel) return [];
    try { return Array.prototype.slice.call(document.querySelectorAll(sel)); } catch (e) { return []; }
  }

  function hide(el) { el.style.setProperty('display', 'none', 'important'); }

  var api = {
    /** Invite code from the URL ('' when this is a normal visit). */
    code: '',
    /** Azuki-style caster links carry &casterMode=1. */
    casterMode: false,
    /** { format, queueType } of the host lobby once looked up; null until then / if not found. */
    lobby: null,
    _opts: null,

    /** Read the invite code without touching the DOM. Safe to call anywhere. */
    detect: function () {
      try {
        var params = new URLSearchParams(window.location.search || '');
        api.code = (params.get('privateInvite') || params.get('invite') || '').trim();
        api.casterMode = params.get('casterMode') === '1';
      } catch (e) {
        api.code = '';
        api.casterMode = false;
      }
      return api.code;
    },

    /** Apply (or re-apply) the joining-an-invite UI state. Idempotent; no-op without a code. */
    enforce: function () {
      if (!api.code || !api._opts) return;
      var o = api._opts;
      all(o.joinBtn).forEach(function (el) {
        if (o.joinBtnVisibleClass) el.classList.add(o.joinBtnVisibleClass);
        else el.style.display = '';
      });
      // ⚠ Hide with !important. A plain inline `style.display='none'` loses to sim stylesheets that
      // declare the button's layout as !important (AzukiSim's .azuki-game-action-* are `display:grid
      // !important`), so the button stayed visible while its inline style claimed otherwise.
      all(o.createBtn).forEach(hide);
      all(o.queueBtn).forEach(hide);
      // Show the HOST's settings before locking the selects. Without this the controls sit at whatever
      // this visitor's menu defaulted to (typically Premier / Bo1) while the game actually starts in the
      // host's format — a disabled dropdown displaying the wrong value is worse than no dropdown at all.
      // Only assign when the option genuinely exists, so a format this sim's menu doesn't list can't
      // blank the select.
      // ⚠ The host's format may not be among THIS visitor's options: the menu only offers non-Open
      // formats to logged-in users, yet an anonymous visitor IS allowed to join a non-Open game by
      // invite (a logged-in host created it — see JoinQueue.php). Inject the missing option so the
      // locked control tells the truth instead of silently showing "Open". Display-only: the control
      // is disabled and the server uses the lobby's own settings regardless.
      if (api.lobby) {
        all(o.selects).forEach(function (el) {
          var want = /queuetype/i.test(el.id) ? api.lobby.queueType : api.lobby.format;
          if (!want) return;
          var has = Array.prototype.some.call(el.options, function (opt) { return opt.value === want; });
          if (!has) {
            var opt = document.createElement('option');
            opt.value = want;
            opt.textContent = label(want);
            el.appendChild(opt);
          }
          el.value = want;
        });
      }
      all(o.selects).forEach(function (el) { el.disabled = true; });
      var text = (api.casterMode && o.noticeTextCaster) ? o.noticeTextCaster : o.noticeText;
      if (api.lobby && (api.lobby.format || api.lobby.queueType) && !api.casterMode) {
        text = 'Private invite detected — ' + label(api.lobby.format) +
               (api.lobby.queueType ? ', ' + label(api.lobby.queueType) : '') +
               '. Choose your deck, then click Join Private Invite.';
      }
      all(o.notice).forEach(function (el) {
        el.style.display = '';
        if (text) el.textContent = text;
      });
    },

    /**
     * Detect + apply, and keep it applied. Call once from the sim's menu init.
     * Returns the invite code so callers can branch on it.
     */
    init: function (options) {
      var o = {};
      for (var k in DEFAULTS) { o[k] = DEFAULTS[k]; }
      if (options) { for (var j in options) { if (options[j] !== undefined) o[j] = options[j]; } }
      api._opts = o;

      if (!api.detect()) return '';
      api.enforce();
      // Re-apply after the host page's own handlers run — see the ⚠ note above.
      document.addEventListener('change', api.enforce, false);
      document.addEventListener('input', api.enforce, false);
      // Then fill in the host's real format / match type. Async and best-effort: if the lookup fails or
      // the invite has expired, the UI simply keeps the generic (still-correct) locked state.
      api.lookup();
      return api.code;
    },

    /** Resolve the invite's lobby so the UI can display the host's settings. Best-effort. */
    lookup: function () {
      var o = api._opts;
      if (!api.code || !o || !o.rootName || typeof window.fetch !== 'function') return;
      var url = o.lookupUrl + '?rootName=' + encodeURIComponent(o.rootName) +
                '&inviteCode=' + encodeURIComponent(api.code);
      window.fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          var row = j && j.data && j.data.length ? j.data[0] : null;
          if (!row) return;
          api.lobby = { format: row.format || '', queueType: row.queueType || '' };
          api.enforce();
        })
        .catch(function () { /* leave the generic locked state */ });
    }
  };

  window.PrivateInviteUI = api;
})();
