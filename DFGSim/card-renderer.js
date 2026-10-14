'use strict';
window.DFGCardRenderer = (() => {
  const esc = value => String(value ?? '').replace(/[&<>\"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[ch]));
  const classColor = color => color.toLowerCase();
function details(c, styles) {
  return `<div class="card-top ${classColor(c.color)}"><small>${esc(c.color.toUpperCase())} / ${esc((styles[c.color] ?? '').toUpperCase())}</small><h3>${esc(c.name)}</h3></div><div class="card-stats"><div><span>ATTACK</span><strong>${c.attack}</strong></div><div><span>BLOCK</span><strong>${c.block}</strong></div></div><div class="card-text"><p><b>ABILITY</b>${c.ability?esc(c.ability).replace(/\n/g,'<br>'):'<span class="empty-ability">No additional ability</span>'}</p>${c.bonus?`<p><b>BURN · BONUS</b>${esc(c.bonus)}</p>`:''}${c.reaction?`<p><b>BURN · REACTION</b>${esc(c.reaction)}</p>`:''}</div><div class="card-switches">SWITCH TO ${c.switches.map(color=>`<span class="color-chip ${classColor(color)}">${esc(color)}</span>`).join('')}</div>`;
}
  return {details};
})();
