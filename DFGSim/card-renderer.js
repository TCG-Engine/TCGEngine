'use strict';
window.DFGCardRenderer = (() => {
  const esc = value => String(value ?? '').replace(/[&<>\"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[ch]));
  const classColor = color => color.toLowerCase();
  const switchIcon = '<svg viewBox="0 0 24 20" aria-hidden="true" focusable="false"><path d="M3 5h18m-5-4 5 4-5 4M21 15H3m5-4-5 4 5 4"/></svg>';
  const attackIcon = '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m4 16 11-11 2-2-1 5L7 17M3 12l5 5M2 18l3-3"/></svg>';
  const blockIcon = '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 2 17 5v5c0 4-4 7-7 8-3-1-7-4-7-8V5Z"/></svg>';
  // Stable catalog IDs keep artwork attached to cards after hand relocation.
  function artURL(c) {
    if(/^adventure\/assets\/techniques\/10[0-3][0-5]-[a-z0-9-]+-v[1-9][0-9]*\.webp$/.test(c.artPath??'')) return c.artPath;
    if(/^roguelike\/assets\/(fox|tortoise|tengu|dragon)-v2\.png$/.test(c.artPath??'')) return c.artPath;
    if(/^roguelike\/assets\/sumerian\/(imdugud|huwawa|bull-of-heaven)-v1\.png$/.test(c.artPath??'')||c.artPath==='assets/fighter-red-transparent.webp')return c.artPath;
    const id = Number(c.artId??c.id);
    if (!Number.isInteger(id) || id < 1 || id > 36) return null;
    const revision = ({10: 'v5', 12: 'v5', 20: 'v5', 33: 'v5', 35: 'v5'})[id] ?? 'v4'; // Individual art revisions.
    const file = String(id).padStart(3, '0') + '-' + revision + '.webp';
    return `assets/cards/individual/${file}`;
  }
  function art(c, compact = false) {
    const url = artURL(c);
    if (!url) return '';
    return `<span class="technique-art${compact ? ' technique-art-compact' : ''}" aria-hidden="true" style="--art-image:url('${url}')"></span>`;
  }
  function mini(c, {badge = '', attack = c.attack, block = c.block} = {}) {
    const stat = (label, icon, value) => `<span aria-label="${label} ${esc(value)}">${icon}<span aria-hidden="true">${esc(value)}</span></span>`;
    return `${art(c, true)}${badge ? `<span class="tile-badge">${esc(badge)}</span>` : ''}<span class="tile-caption"><span class="tile-heading"><strong class="tile-name" title="${esc(c.name)}">${esc(c.name)}</strong><span class="tile-stats">${stat('Attack', attackIcon, attack)}${stat('Block', blockIcon, block)}</span></span><span class="tile-switches" aria-label="Switch to ${esc(c.switches.join(', '))}">${switchIcon}${c.switches.map(color=>`<i class="${classColor(color)}" aria-hidden="true"></i>`).join('')}</span></span>`;
  }
  function details(c, styles, {textOnly = false} = {}) {
    const burn = (label, text) => text ? `<p class="burn-ability"><b>${label}</b><span>${esc(text)}</span></p>` : '';
    const heading = textOnly ? '' : `<header class="card-top"><small><span>${esc(c.color.toUpperCase())}</span> / ${esc((styles[c.color] ?? '').toUpperCase())}</small></header>${art(c)}<div class="card-heading"><h3>${esc(c.name)}</h3><div class="card-stats"><div><span>ATTACK</span><strong>${esc(c.attack)}</strong></div><div><span>BLOCK</span><strong>${esc(c.block)}</strong></div></div></div>`;
    return `<div class="technique-card ${classColor(c.color)}${textOnly ? ' rules-preview' : ''}" aria-label="${esc(c.name)} rules">${heading}<div class="card-text"><div class="main-ability">${c.ability?esc(c.ability).replace(/\n/g,'<br>'):'<span class="empty-ability">No additional ability</span>'}${c.extraText?'<br>'+esc(c.extraText):''}</div><div class="card-burns">${burn('BURN BONUS',c.bonus)}${burn('BURN REACTION',c.reaction)}</div></div><footer class="card-switches" aria-label="Switch to ${esc(c.switches.join(', '))}"><div class="switch-divider">${switchIcon}</div><span class="card-destinations">${c.switches.map(color=>`<span class="color-chip ${classColor(color)}"><i aria-hidden="true"></i>${esc(color)}</span>`).join('')}</span></footer></div>`;
  }
  return {details, art, artURL, mini};
})();
