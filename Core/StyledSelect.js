/* StyledSelect.js — progressive-enhancement custom <select> widget, token-themed.
   Opt-in: add class="styled-select" (or data-styled-select) to a <select>. The native
   element is kept (hidden) so form submission + change events still work; the widget
   mirrors it and dispatches 'change' on pick. Self-injects its CSS. Reuses the shared
   .dropdown-panel component for the menu, so it themes per app for free. */
(function () {
  var CSS =
    ".sds-wrap{position:relative;display:inline-block;vertical-align:middle;}"
  + ".sds-native{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;left:0;top:0;}"
  + ".sds-trigger{display:inline-flex;align-items:center;justify-content:space-between;gap:10px;min-width:140px;"
  +   "padding:8px 12px;cursor:pointer;font:inherit;text-align:left;color:var(--text,#fff);"
  +   "background:var(--surface-sunken,#394452);border:var(--border-width,1px) solid var(--border,#454545);border-radius:var(--radius,5px);}"
  + ".sds-trigger:focus{outline:none;border-color:var(--accent,#5aa0ff);box-shadow:0 0 6px var(--glow,rgba(90,160,255,.35));}"
  + ".sds-caret{opacity:.8;font-size:.8em;}"
  + ".sds-menu{position:absolute;left:0;top:calc(100% + 4px);min-width:100%;z-index:1000;display:none;padding:4px 0;max-height:60vh;overflow:auto;}"
  + ".sds-menu.is-open{display:block;}"
  + ".sds-item{padding:8px 14px;cursor:pointer;white-space:nowrap;}";
  function ensureStyles() {
    if (document.getElementById('sds-styles')) return;
    var s = document.createElement('style'); s.id = 'sds-styles'; s.textContent = CSS;
    (document.head || document.documentElement).appendChild(s);
  }

  function enhance(sel) {
    if (!sel || sel.dataset.sdsDone === '1') return;
    if (sel.multiple || sel.size > 1) return;   // single-select only
    ensureStyles();
    sel.dataset.sdsDone = '1';

    var wrap = document.createElement('div'); wrap.className = 'sds-wrap';
    var trigger = document.createElement('button'); trigger.type = 'button'; trigger.className = 'sds-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    var label = document.createElement('span'); label.className = 'sds-label';
    var caret = document.createElement('span'); caret.className = 'sds-caret'; caret.textContent = '▾';
    trigger.appendChild(label); trigger.appendChild(caret);
    var menu = document.createElement('div'); menu.className = 'sds-menu dropdown-panel'; menu.setAttribute('role', 'listbox');

    function syncLabel() {
      var o = sel.options[sel.selectedIndex];
      label.textContent = o ? o.textContent : '';
    }
    function rebuild() {
      menu.innerHTML = '';
      Array.prototype.forEach.call(sel.options, function (opt, i) {
        var it = document.createElement('div');
        it.className = 'sds-item dropdown-panel__item' + (opt.selected ? ' is-active' : '');
        it.setAttribute('role', 'option'); it.textContent = opt.textContent;
        it.onclick = function () { pick(i); };
        menu.appendChild(it);
      });
      syncLabel();
    }
    function pick(i) {
      if (sel.selectedIndex !== i) {
        sel.selectedIndex = i;
        sel.dispatchEvent(new Event('input', { bubbles: true }));
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      }
      rebuild(); close();
    }
    function open() { menu.classList.add('is-open'); document.addEventListener('mousedown', outside, true); document.addEventListener('keydown', onKey, true); }
    function close() { menu.classList.remove('is-open'); document.removeEventListener('mousedown', outside, true); document.removeEventListener('keydown', onKey, true); }
    function outside(e) { if (!wrap.contains(e.target)) close(); }
    function onKey(e) {
      if (e.key === 'Escape') { close(); trigger.focus(); }
      else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        var d = e.key === 'ArrowDown' ? 1 : -1;
        var ni = Math.max(0, Math.min(sel.options.length - 1, sel.selectedIndex + d));
        pick(ni); open();
      }
    }
    trigger.onclick = function (e) { e.preventDefault(); if (menu.classList.contains('is-open')) close(); else open(); };

    // Insert the wrapper, move the (now hidden) native select inside it for form submission.
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel); sel.classList.add('sds-native'); sel.setAttribute('tabindex', '-1');
    wrap.appendChild(trigger); wrap.appendChild(menu);
    rebuild();
    // If other code sets the select's value programmatically, keep the widget in sync.
    sel.addEventListener('change', function () { rebuild(); });
  }

  function enhanceAll(root) {
    var sels = (root || document).querySelectorAll('select.styled-select, select[data-styled-select]');
    Array.prototype.forEach.call(sels, enhance);
  }

  window.StyledSelect = { enhance: enhance, enhanceAll: enhanceAll };
  if (document.readyState !== 'loading') enhanceAll();
  else document.addEventListener('DOMContentLoaded', function () { enhanceAll(); });
})();
