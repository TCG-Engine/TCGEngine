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
    const defaults = { version: 1, windows: {} };
    entries.forEach(([id, spec]) => { defaults.windows[id] = !!spec.default; });

    if (window.TCGSettings?.registerSchema) {
      window.TCGSettings.registerSchema(ROOT, { ShortcutPreferences: { type: 'json', defaultValue: defaults } });
    }
    const normalize = raw => {
      const source = raw?.windows && typeof raw.windows === 'object' ? raw.windows : (raw || {});
      const value = { version: 1, windows: {} };
      entries.forEach(([id, spec]) => { value.windows[id] = Object.hasOwn(source, id) ? !!source[id] : !!spec.default; });
      return value;
    };
    let payload = normalize(window.TCGSettings?.get?.(SETTING, { rootName: ROOT, type: 'json', defaultValue: defaults }) || defaults);

    const style = document.createElement('style');
    style.textContent = `
      #fab-shortcut-dock{position:fixed;right:12px;bottom:12px;z-index:1800;font:600 13px system-ui;color:#eee}
      #fab-shortcut-toggle{border:1px solid #8a6a21;background:#17140e;color:#e8c766;border-radius:8px;padding:8px 12px;cursor:pointer}
      #fab-shortcut-panel{display:none;position:absolute;right:0;bottom:42px;width:245px;padding:12px;border:1px solid #70551c;border-radius:10px;background:rgba(15,15,14,.97);box-shadow:0 12px 35px #000}
      #fab-shortcut-dock.open #fab-shortcut-panel{display:block}.fab-shortcut-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:7px 0}
      .fab-shortcut-row button{width:42px;height:23px;border:0;border-radius:14px;background:#494949;cursor:pointer;position:relative}
      .fab-shortcut-row button:after{content:'';position:absolute;top:3px;left:3px;width:17px;height:17px;border-radius:50%;background:#ddd;transition:.14s}
      .fab-shortcut-row button.on{background:#2f9d55}.fab-shortcut-row button.on:after{left:22px}.fab-shortcut-note{font-weight:400;color:#aaa;font-size:11px;margin-bottom:6px}`;
    document.head.appendChild(style);

    const dock = document.createElement('div'); dock.id = 'fab-shortcut-dock';
    dock.innerHTML = `<div id="fab-shortcut-panel"><div style="font-size:15px;margin-bottom:3px">Shortcut windows</div><div class="fab-shortcut-note">On means automatically pass that window.</div>${entries.map(([id, spec]) => `<div class="fab-shortcut-row"><span>${spec.label || id}</span><button type="button" data-id="${id}" aria-pressed="${payload.windows[id]}"></button></div>`).join('')}</div><button id="fab-shortcut-toggle" type="button">Shortcuts</button>`;
    document.body.appendChild(dock);
    const render = () => dock.querySelectorAll('[data-id]').forEach(button => button.classList.toggle('on', !!payload.windows[button.dataset.id]));
    const sync = () => {
      window.TCGSettings?.set?.(SETTING, payload, { rootName: ROOT, type: 'json' });
      if (typeof SubmitInput === 'function') SubmitInput('10015', '&inputText=' + encodeURIComponent(JSON.stringify(payload)));
    };
    dock.querySelector('#fab-shortcut-toggle').onclick = () => dock.classList.toggle('open');
    dock.querySelectorAll('[data-id]').forEach(button => button.onclick = () => {
      payload.windows[button.dataset.id] = !payload.windows[button.dataset.id]; render(); sync();
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
