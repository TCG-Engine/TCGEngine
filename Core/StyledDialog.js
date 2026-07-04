/* StyledDialog.js — shared, token-themed dialogs. No native alert/confirm/prompt.
   Generalized from DeckLibrary's confirmDialog/renameDialog. All Promise-based.
   Self-contained: injects its own CSS on first use (reads design-system role tokens
   when present, falls back to neutral values otherwise), so it works on ANY page with
   just this one script — no components.css dependency. */
(function () {
  var SD_CSS =
    ".sd-overlay{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);backdrop-filter:blur(var(--backdrop-blur,3px));-webkit-backdrop-filter:blur(var(--backdrop-blur,3px));}"
  + ".sd-modal{width:min(92vw,420px);background:var(--surface-raised,#2b2f36);color:var(--text,#fff);border:1px solid var(--border,#454545);border-radius:var(--radius,5px);padding:22px;box-shadow:0 18px 50px rgba(0,0,0,.5);font-family:var(--font-body,inherit);}"
  + ".sd-title{margin:0 0 12px;font-family:var(--font-display,inherit);text-transform:uppercase;letter-spacing:var(--tracking-caps,.06em);font-size:18px;color:var(--text,#fff);}"
  + ".sd-message{margin:0 0 18px;color:var(--text-muted,#aaa);line-height:1.45;font-size:14px;}"
  + ".sd-input{width:100%;box-sizing:border-box;padding:10px 12px;font-size:14px;margin:0 0 4px;background:var(--surface-sunken,#394452);color:var(--text,#fff);border:var(--border-width,1px) solid var(--border,#454545);border-radius:var(--radius,5px);}"
  + ".sd-input:focus{outline:none;border-color:var(--accent,#5aa0ff);}"
  + ".sd-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;}"
  + ".sd-actions .btn{cursor:pointer;font-weight:600;padding:var(--btn-pad,8px 16px);}"
  + ".sd-toast-wrap{position:fixed;left:50%;bottom:28px;transform:translateX(-50%);z-index:10001;display:flex;flex-direction:column;gap:8px;align-items:center;pointer-events:none;}"
  + ".sd-toast{min-width:200px;max-width:min(90vw,460px);padding:11px 18px;border-radius:var(--radius,5px);background:var(--surface-raised,#2b2f36);color:var(--text,#fff);border:1px solid var(--border,#454545);box-shadow:0 8px 24px rgba(0,0,0,.4);font-size:14px;text-align:center;opacity:0;transform:translateY(8px);transition:opacity .2s,transform .2s;}"
  + ".sd-toast.sd-show{opacity:1;transform:translateY(0);}"
  + ".sd-toast-success{border-color:var(--success,#64c878);}"
  + ".sd-toast-danger{border-color:var(--danger,#e46a6a);}"
  // Minimal .btn fallback so dialog buttons look right on pages that don't load components.css.
  + ".sd-modal .btn,.sd-toast .btn{display:inline-block;text-align:center;border:1px solid var(--btn-border,var(--border,#454545));border-radius:var(--btn-radius,var(--radius,5px));background:var(--btn-surface,#333);color:var(--btn-text,var(--text,#fff));}"
  + ".sd-modal .btn-primary{background:var(--accent,#5aa0ff);color:var(--on-accent,#08202b);border-color:var(--accent-strong,#7db4ff);}"
  + ".sd-modal .btn-danger{border-color:var(--danger,#e46a6a);background:var(--danger-surface,#3a1c22);color:var(--on-danger,#ffe4e4);}";
  function ensureStyles() {
    if (document.getElementById('sd-styles')) return;
    var s = document.createElement('style'); s.id = 'sd-styles'; s.textContent = SD_CSS;
    (document.head || document.documentElement).appendChild(s);
  }
  function overlay() { ensureStyles(); var o = document.createElement('div'); o.className = 'sd-overlay'; return o; }
  function panel(html) { var p = document.createElement('div'); p.className = 'sd-modal';
    p.setAttribute('role', 'dialog'); p.setAttribute('aria-modal', 'true'); p.innerHTML = html; return p; }
  function mount(o) { document.body.appendChild(o); }
  function unmount(o, onKey) { o.remove(); if (onKey) document.removeEventListener('keydown', onKey, true); }
  function titleHtml(t) { return t ? "<h3 class='sd-title'></h3>" : ""; }
  function actions(inner) { return "<div class='sd-actions'>" + inner + "</div>"; }

  window.StyledConfirm = function (message, opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var o = overlay();
      var p = panel(titleHtml(opts.title) + "<p class='sd-message'></p>"
        + actions("<button type='button' class='btn sd-cancel'></button>"
                + "<button type='button' class='btn " + (opts.danger ? 'btn-danger' : 'btn-primary') + " sd-ok'></button>"));
      o.appendChild(p);
      if (opts.title) p.querySelector('.sd-title').textContent = opts.title;
      p.querySelector('.sd-message').textContent = message;
      p.querySelector('.sd-cancel').textContent = opts.cancelLabel || 'Cancel';
      p.querySelector('.sd-ok').textContent = opts.confirmLabel || 'Confirm';
      function done(v) { unmount(o, onKey); resolve(v); }
      function onKey(e) { if (e.key === 'Escape') { e.preventDefault(); done(false); }
        else if (e.key === 'Enter') { e.preventDefault(); done(true); } }
      p.querySelector('.sd-cancel').onclick = function () { done(false); };
      p.querySelector('.sd-ok').onclick = function () { done(true); };
      o.addEventListener('mousedown', function (e) { if (e.target === o) done(false); });
      document.addEventListener('keydown', onKey, true);
      mount(o); p.querySelector('.sd-ok').focus();
    });
  };

  window.StyledPrompt = function (message, opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var o = overlay();
      var p = panel(titleHtml(opts.title) + "<p class='sd-message'></p>"
        + "<input type='text' class='sd-input' maxlength='256'>"
        + actions("<button type='button' class='btn sd-cancel'></button>"
                + "<button type='button' class='btn btn-primary sd-ok'></button>"));
      o.appendChild(p);
      if (opts.title) p.querySelector('.sd-title').textContent = opts.title;
      p.querySelector('.sd-message').textContent = message;
      var input = p.querySelector('.sd-input');
      input.value = opts.initial || ''; input.placeholder = opts.placeholder || '';
      p.querySelector('.sd-cancel').textContent = opts.cancelLabel || 'Cancel';
      p.querySelector('.sd-ok').textContent = opts.confirmLabel || 'Save';
      function done(v) { unmount(o, onKey); resolve(v); }
      function save() { var v = (input.value || '').trim(); done(v ? v : null); }
      function onKey(e) { if (e.key === 'Escape') { e.preventDefault(); done(null); }
        else if (e.key === 'Enter') { e.preventDefault(); save(); } }
      p.querySelector('.sd-cancel').onclick = function () { done(null); };
      p.querySelector('.sd-ok').onclick = save;
      o.addEventListener('mousedown', function (e) { if (e.target === o) done(null); });
      document.addEventListener('keydown', onKey, true);
      mount(o); input.focus(); input.select();
    });
  };

  window.StyledAlert = function (message, opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var o = overlay();
      var p = panel(titleHtml(opts.title) + "<p class='sd-message'></p>"
        + actions("<button type='button' class='btn btn-primary sd-ok'></button>"));
      o.appendChild(p);
      if (opts.title) p.querySelector('.sd-title').textContent = opts.title;
      p.querySelector('.sd-message').textContent = message;
      p.querySelector('.sd-ok').textContent = opts.okLabel || 'OK';
      function done() { unmount(o, onKey); resolve(); }
      function onKey(e) { if (e.key === 'Escape' || e.key === 'Enter') { e.preventDefault(); done(); } }
      p.querySelector('.sd-ok').onclick = done;
      o.addEventListener('mousedown', function (e) { if (e.target === o) done(); });
      document.addEventListener('keydown', onKey, true);
      mount(o); p.querySelector('.sd-ok').focus();
    });
  };

  window.Toast = function (message, opts) {
    opts = opts || {};
    ensureStyles();
    var wrap = document.querySelector('.sd-toast-wrap');
    if (!wrap) { wrap = document.createElement('div'); wrap.className = 'sd-toast-wrap'; document.body.appendChild(wrap); }
    var t = document.createElement('div');
    t.className = 'sd-toast sd-toast-' + (opts.type || 'info');
    t.textContent = message;
    wrap.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('sd-show'); });
    setTimeout(function () { t.classList.remove('sd-show');
      setTimeout(function () { t.remove(); if (!wrap.children.length) wrap.remove(); }, 250); },
      opts.ms || 3200);
  };
})();
