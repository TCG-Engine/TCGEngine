(function () {
  'use strict';

  const ROOT = 'FaBSim';
  const SETTING = 'ShortcutPreferences';

  function registry() {
    try {
      const raw = typeof GetModuleConfig === 'function' ? GetModuleConfig('ShortcutWindows') : '{}';
      return JSON.parse(raw || '{}');
    } catch (_) { return {}; }
  }

  function install() {
    if (document.getElementById('fab-shortcut-dock')) return;
    const entries = Object.entries(registry()).sort((a, b) => Number(a[1].order || 0) - Number(b[1].order || 0));
    if (!entries.length) return;
    const defaults = { version: 1, holdPriority: false, windows: {} };
    entries.forEach(([id, spec]) => { defaults.windows[id] = !!spec.default; });

    if (window.TCGSettings?.registerSchema) {
      window.TCGSettings.registerSchema(ROOT, { ShortcutPreferences: { type: 'json', defaultValue: defaults } });
    }
    const normalize = raw => {
      const source = raw?.windows && typeof raw.windows === 'object' ? raw.windows : (raw || {});
      const value = { version: 1, holdPriority: !!raw?.holdPriority, windows: {} };
      entries.forEach(([id, spec]) => { value.windows[id] = Object.hasOwn(source, id) ? !!source[id] : !!spec.default; });
      return value;
    };
    let payload = normalize(window.TCGSettings?.get?.(SETTING, { rootName: ROOT, type: 'json', defaultValue: defaults }) || defaults);

    const bolt = '<svg class="fab-shortcut-bolt" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13.5 2 4 13h7l-1 9 10-12h-7z"/></svg>';
    const dock = document.createElement('div'); dock.id = 'fab-shortcut-dock'; dock.className = 'fab-shortcut-ui';
    dock.innerHTML = `
      <section id="fab-shortcut-panel" class="fab-shortcut-ui" role="dialog" aria-labelledby="fab-shortcut-title" hidden>
        <header class="fab-shortcut-header">
          <span class="fab-shortcut-emblem">${bolt}</span>
          <div><span class="fab-shortcut-eyebrow">PRIORITY CONTROL</span><h2 id="fab-shortcut-title">Shortcuts</h2></div>
          <button type="button" class="fab-shortcut-close" aria-label="Close shortcuts">×</button>
        </header>
        <div class="fab-shortcut-body">
          <p id="fab-shortcut-status" class="fab-shortcut-status" aria-live="polite"></p>
          <div id="fab-shortcut-groups"></div>
          <p class="fab-shortcut-note">Enabled windows pass automatically, even with an instant available. Your own playable actions, chain continuation, and arsenal choices still stop.</p>
          <div id="fab-shortcut-choices" class="fab-shortcut-choices"><h3>Card choices</h3></div>
        </div>
        <footer class="fab-shortcut-footer">Preferences saved in this browser</footer>
      </section>
      <button id="fab-shortcut-toggle" type="button" aria-expanded="false" aria-controls="fab-shortcut-panel" aria-haspopup="dialog">
        ${bolt}<span id="fab-shortcut-toggle-label">Shortcuts</span><span id="fab-shortcut-count"></span>
      </button>
      <label class="fab-shortcut-master fab-shortcut-row" title="Hold all priority without changing your saved shortcuts">
        <span>Hold all priority</span>
        <input id="fab-shortcut-hold" class="fab-shortcut-switch" type="checkbox" role="switch">
      </label>`;
    document.body.appendChild(dock);
    // Keep the popup outside transformed/scrolling board containers.
    const panel = dock.querySelector('#fab-shortcut-panel');
    document.body.appendChild(panel);
    const mountControls = () => {
      const selector = document.body.classList.contains('fab-upf-active') ? '.fab-upf-tools' : '.fab-overlay-toolbar';
      const toolbar = document.querySelector(selector);
      if (toolbar && dock.parentElement !== toolbar) { toolbar.appendChild(dock); positionPanel(); }
    };
    mountControls();
    new MutationObserver(mountControls).observe(document.body, { attributes: true, attributeFilter: ['class'] });

    const groups = new Map();
    entries.forEach(([id, spec]) => {
      const groupName = spec.group || 'Priority';
      if (!groups.has(groupName)) {
        const group = document.createElement('fieldset');
        group.className = 'fab-shortcut-group';
        const legend = document.createElement('legend'); legend.textContent = groupName;
        group.appendChild(legend); panel.querySelector('#fab-shortcut-groups').appendChild(group);
        groups.set(groupName, group);
      }
      const row = document.createElement('label'); row.className = 'fab-shortcut-row';
      const label = document.createElement('span'); label.textContent = spec.label || id;
      const input = document.createElement('input');
      input.type = 'checkbox'; input.className = 'fab-shortcut-switch'; input.dataset.id = id;
      input.setAttribute('role', 'switch'); input.setAttribute('aria-label', 'Auto-pass ' + (spec.label || id));
      row.append(label, input); groups.get(groupName).appendChild(row);
    });

    const autoRow = document.createElement('label');
    autoRow.className = 'fab-shortcut-row';
    autoRow.innerHTML = '<span>Auto-choose single option</span><input class="fab-shortcut-switch" type="checkbox" role="switch" aria-describedby="fab-shortcut-auto-note">';
    autoRow.title = 'Automatically select a lone card target, including optional choices. Saved for this browser.';
    const autoInput = autoRow.querySelector('input');
    autoInput.checked = !!window.TCGSettings?.get('AutoChooseSingleOption', { rootName: ROOT, type: 'boolean', defaultValue: false });
    autoInput.onchange = () => window.TCGSettings?.set('AutoChooseSingleOption', autoInput.checked, { rootName: ROOT, type: 'boolean' });
    panel.querySelector('#fab-shortcut-choices').appendChild(autoRow);
    const autoNote = document.createElement('div');
    autoNote.className = 'fab-shortcut-note';
    autoNote.id = 'fab-shortcut-auto-note';
    autoNote.textContent = 'Includes optional single-card choices. Independent of holding priority.';
    panel.querySelector('#fab-shortcut-choices').appendChild(autoNote);
    const holdInput = dock.querySelector('#fab-shortcut-hold');
    const render = () => {
      const selected = entries.filter(([id]) => payload.windows[id]).length;
      dock.classList.toggle('is-holding', payload.holdPriority);
      panel.classList.toggle('is-holding', payload.holdPriority);
      holdInput.checked = payload.holdPriority;
      panel.querySelector('#fab-shortcut-status').textContent = payload.holdPriority
        ? 'Holding priority · pass manually at every window'
        : `${selected} of ${entries.length} windows set to auto-pass`;
      dock.querySelector('#fab-shortcut-count').textContent = payload.holdPriority ? 'PAUSED' : `${selected}/${entries.length}`;
      groups.forEach(group => { group.disabled = payload.holdPriority; });
      panel.querySelectorAll('[data-id]').forEach(input => { input.checked = !!payload.windows[input.dataset.id]; });
    };
    const sync = () => {
      window.TCGSettings?.set?.(SETTING, payload, { rootName: ROOT, type: 'json' });
      if (typeof SubmitInput === 'function') SubmitInput('10015', '&inputText=' + encodeURIComponent(JSON.stringify(payload)));
    };
    const toggle = dock.querySelector('#fab-shortcut-toggle');
    function positionPanel() {
      if (panel.hidden) return;
      const anchor = toggle.getBoundingClientRect();
      const width = panel.getBoundingClientRect().width;
      const top = Math.max(8, Math.min(dock.getBoundingClientRect().bottom + 8, innerHeight - 160));
      panel.style.left = Math.max(8, Math.min(anchor.right - width, innerWidth - width - 8)) + 'px';
      panel.style.top = top + 'px';
      panel.style.maxHeight = Math.max(120, innerHeight - top - 8) + 'px';
    }
    window.addEventListener('resize', positionPanel);
    document.addEventListener('scroll', event => {
      if (!panel.contains(event.target)) positionPanel();
    }, true);
    new ResizeObserver(positionPanel).observe(dock);
    const setOpen = (open, restoreFocus = false) => {
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      if (open) { positionPanel(); panel.querySelector('.fab-shortcut-close').focus(); }
      else if (restoreFocus) toggle.focus();
    };
    toggle.onclick = () => setOpen(panel.hidden);
    panel.querySelector('.fab-shortcut-close').onclick = () => setOpen(false, true);
    document.addEventListener('pointerdown', event => {
      if (!panel.hidden && !dock.contains(event.target) && !panel.contains(event.target)) setOpen(false);
    });
    // Safari may leave focus on the page after clicking a checkbox.
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !panel.hidden) {
        event.preventDefault(); event.stopPropagation(); setOpen(false, true);
      }
    });
    holdInput.onchange = () => { payload.holdPriority = holdInput.checked; render(); sync(); };
    panel.querySelectorAll('[data-id]').forEach(input => input.onchange = () => {
      payload.windows[input.dataset.id] = input.checked; render(); sync();
    });
    render(); sync();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install);
  else install();

  document.addEventListener('keydown', function (event) {
    const target = event.target;
    const tagName = target?.tagName || '';
    if (tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT' || target?.isContentEditable) return;
    if (event.repeat || event.ctrlKey || event.metaKey || event.altKey) return;
    if (String(event.key || '').toLowerCase() !== 'u' && event.keyCode !== 85) return;
    if (typeof SubmitInput !== 'function') return;
    event.preventDefault();
    event.stopPropagation();
    SubmitInput(10004, '');
  }, true);
})();
