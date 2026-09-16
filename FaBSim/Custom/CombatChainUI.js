(function () {
  'use strict';
  let preview = null;
  let closeTimer;
  function closePreview() {
    clearTimeout(closeTimer);
    if (!preview) return;
    preview.box.hidden = true;
    preview.button.setAttribute('aria-expanded', 'false');
    preview = null;
  }
  function deferClose() {
    clearTimeout(closeTimer);
    closeTimer = setTimeout(() => { if (preview && !preview.pinned) closePreview(); }, 180);
  }
  function showPreview(button, box, pinned = false) {
    closePreview();
    box.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    preview = { button, box, pinned };
    const rect = button.getBoundingClientRect();
    const width = box.getBoundingClientRect().width;
    const height = box.getBoundingClientRect().height;
    box.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)) + 'px';
    box.style.top = Math.max(8, rect.top - height - 8 >= 8 ? rect.top - height - 8 : Math.min(rect.bottom + 8, window.innerHeight - height - 8)) + 'px';
  }

  // Keep original zone indices: they are the server's click/choice identities.
  window.FaBChainLinks = function (zones, state) {
    const links = new Map();
    Object.entries(zones).forEach(([zone, raw]) => {
      String(raw || '').split('<|>').forEach((record, index) => {
        const fields = record.trim().split(' ');
        if (!fields[0] || fields[0] === '-') return;
        let data;
        try { data = JSON.parse(fields[2]); } catch (_) { return; }
        if (data.removed) return;
        const link = Number(data.ChainLink || 0);
        if (!links.has(link)) links.set(link, []);
        links.get(link).push({ zone, index, fields, data });
      });
    });
    const rank = card => card.data.Role === 'ATTACK' ? 0 : card.data.Role === 'DEFENSE' ? 1 : 2;
    return [...links].sort(([a], [b]) => a - b).map(([number, cards]) => ({
      number, current: number === Number(state.chainLink),
      cards: cards.sort((a, b) => rank(a) - rank(b)
        || Number(a.data.Counters?.FAB_PLAY_ORDER || 0) - Number(b.data.Counters?.FAB_PLAY_ORDER || 0)
        || a.index - b.index)
    }));
  };

  window.FaBRenderChain = function (target, zones, state) {
    if (!target) return;
    if (!document.getElementById('fab-chain-style')) {
      const style = document.createElement('style'); style.id = 'fab-chain-style';
      style.textContent = `
        .fab-chain-view{display:flex;align-items:center;gap:8px;width:100%;min-width:0;overflow-x:auto;color:#eee;font:12px system-ui}
        .fab-chain-history{display:flex;gap:3px;align-items:center;flex:none}
        .fab-chain-history:empty{display:none}
        button.fab-chain-link{flex:none;display:flex;flex-direction:column;align-items:center;justify-content:center;width:32px;padding:3px 0;border:1px solid transparent;border-radius:6px;background:transparent;color:#c7ab70;cursor:pointer;font:10px system-ui}
        button.fab-chain-link:hover,button.fab-chain-link[aria-expanded=true]{background:#d6aa4d18;border-color:#88713f}
        button.fab-chain-link:focus-visible{outline:2px solid #ead398;outline-offset:1px}
        .fab-chain-link svg{width:26px;height:18px;display:block}
        .fab-history-popover{position:fixed;z-index:1900;width:max-content;max-width:calc(100vw - 16px);max-height:calc(100vh - 16px);overflow:auto;box-sizing:border-box;padding:8px;border:1px solid #88713f;border-radius:9px;background:#101718;box-shadow:0 8px 28px #000b;color:#eee;font:12px system-ui}
        .fab-history-popover[hidden]{display:none}
        .fab-history-title{color:#d8c18d;font-size:11px;margin-bottom:5px}
        .fab-chain-row{display:flex;align-items:center;gap:8px;padding:4px 2px;flex:none}
        .fab-chain-card{position:relative;flex:0 0 92px;text-align:center}
        .fab-chain-card [data-mzid]{position:relative!important;display:inline-block!important;margin:0!important;width:92px!important;height:92px!important}
        .fab-chain-card [data-mzid]:before,.fab-chain-card [data-mzid]:after{display:none!important}
        .fab-chain-card img{width:92px!important;height:92px!important;object-fit:cover!important}
        .fab-chain-role{font-size:9px;color:#bcb8ac;margin-bottom:3px;white-space:nowrap}
        .fab-chain-arrow{flex:none;color:#d6aa4d;font-size:16px}
        .fab-go-again{position:absolute;top:15px;right:0;z-index:5;padding:2px 4px;border:1px solid #b3f7be;border-radius:12px;background:#196437;color:#fff;font:bold 10px system-ui;pointer-events:none}
        #fabCombatWindow .fab-chain-flow{display:block;overflow:auto;padding:5px 10px 7px;height:auto;min-height:0}
        #fabCombatWindow{height:auto;max-height:65vh}
        #myCombatChainSlot,#theirCombatChainSlot{display:none!important}
      `;
      document.head.appendChild(style);
      document.addEventListener('pointerdown', event => {
        if (preview && !preview.button.contains(event.target) && !preview.box.contains(event.target)) closePreview();
      });
      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && preview) { const button = preview.button; button.focus(); closePreview(); }
      });
      window.addEventListener('resize', closePreview);
      document.addEventListener('scroll', event => {
        if (preview && !preview.box.contains(event.target)) closePreview();
      }, true);
    }
    const previous = preview && target.contains(preview.button) ? { link: preview.button.dataset.link, pinned: preview.pinned } : null;
    if (previous) closePreview();
    let previews = document.getElementById(target.id + '-previews');
    if (!previews) { previews = document.createElement('div'); previews.id = target.id + '-previews'; document.body.appendChild(previews); }
    previews.replaceChildren();
    target.classList.add('fab-chain-view');
    target.replaceChildren();
    const history = document.createElement('div'); history.className = 'fab-chain-history';
    target.appendChild(history);
    const labels = { ATTACK: 'Attack', DEFENSE: 'Block', ATTACK_REACTION: 'Attack reaction', DEFENSE_REACTION: 'Defense reaction' };
    const links = window.FaBChainLinks(zones, state);
    target.dataset.cardCount = String(links.reduce((count, link) => count + link.cards.length, 0));
    links.forEach(link => {
      const row = document.createElement('div'); row.className = 'fab-chain-row';
      link.cards.forEach((card, index) => {
        if (index) { const arrow = document.createElement('span'); arrow.className = 'fab-chain-arrow'; arrow.textContent = '→'; arrow.setAttribute('aria-hidden', 'true'); row.appendChild(arrow); }
        const wrapper = document.createElement('div'); wrapper.className = 'fab-chain-card';
        const role = document.createElement('div'); role.className = 'fab-chain-role';
        role.textContent = (labels[card.data.Role] || 'Card') + ' · P' + Number(card.data.Controller || card.data.Owner);
        wrapper.appendChild(role);
        const image = document.createElement('div');
        image.innerHTML = createCardHTML(card.zone, 'CombatChain', './FaBSim/concat', 92, card.fields, card.index);
        wrapper.appendChild(image);
        if (link.current && Number(card.data.CombatGoAgain) === 1 && card.data.Role === 'ATTACK') {
          const badge = document.createElement('span'); badge.className = 'fab-go-again'; badge.textContent = '↻ Go again';
          badge.setAttribute('aria-label', 'This attack has go again'); wrapper.appendChild(badge);
        }
        row.appendChild(wrapper);
      });
      if (link.current) {
        row.setAttribute('aria-label', 'Current link ' + link.number);
        target.appendChild(row);
      } else {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'fab-chain-link'; button.dataset.link = String(link.number);
        const label = 'Link ' + link.number + ' · ' + link.cards.length + ' cards';
        button.setAttribute('aria-label', label); button.setAttribute('aria-expanded', 'false');
        button.innerHTML = '<svg viewBox="0 0 32 20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="11" cy="10" rx="8" ry="5" transform="rotate(-25 11 10)"/><ellipse cx="21" cy="10" rx="8" ry="5" transform="rotate(-25 21 10)"/></svg><span>' + link.number + '</span>';
        const box = document.createElement('div'); box.className = 'fab-history-popover'; box.hidden = true; box.id = previews.id + '-' + link.number;
        button.setAttribute('aria-controls', box.id);
        const title = document.createElement('div'); title.className = 'fab-history-title'; title.textContent = label;
        box.append(title, row); previews.appendChild(box); history.appendChild(button);
        button.addEventListener('mouseenter', () => {
          if (preview?.button === button) clearTimeout(closeTimer);
          else showPreview(button, box);
        });
        button.addEventListener('mouseleave', deferClose);
        button.addEventListener('focus', () => showPreview(button, box));
        button.addEventListener('blur', deferClose);
        box.addEventListener('mouseenter', () => clearTimeout(closeTimer));
        box.addEventListener('mouseleave', deferClose);
        button.addEventListener('click', () => {
          if (preview?.button === button && preview.pinned) closePreview();
          else showPreview(button, box, true);
        });
        if (previous?.link === String(link.number)) showPreview(button, box, previous.pinned);
      }
    });
  };
})();
