'use strict';
(() => {
  const {families, colors, palette, conventions} = window.DFGFamilyStudy;
  const byId = new Map(families.map(f=>[f.id,f]));
  const evaluate = window.DFGStudyEvaluate;
  const $ = id => document.getElementById(id);
  const esc = value => String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let category = 'All', savedOnly = false, saved = new Set();
  try {
    const value = JSON.parse(localStorage.getItem('dfg-family-study-shortlist') || '[]');
    if (Array.isArray(value)) saved = new Set(value.filter(id=>byId.has(id)));
  } catch { /* Gallery remains usable when storage is unavailable. */ }
  $('conventions').innerHTML = `<ul>${conventions.map(c=>`<li>${esc(c)}</li>`).join('')}</ul>`;
  $('entry-total').textContent=window.DFGFamilyStudy.entryCount;
  $('family-total').textContent=families.length;
  $('live-total').textContent=window.DFGFamilyStudy.liveCount;
  const coverage=window.DFGFamilyStudy.coverage;
  const facetCount=coverage.reduce((n,c)=>n+Object.values(c.facets).flat().length,0);
  $('coverage-count').textContent=`${coverage.length} / ${window.DFGStudyLiveCatalog.cards.length} current cards · ${facetCount} mapped clauses and mode/route fields · rules v${window.DFGFamilyStudy.liveSource.rulesVersion}`;
  const facetLinks=facets=>facets.length?facets.map(facet=>facet.families.map(id=>`<a href="#${id}" title="${esc(facet.text)}" aria-label="${id}: ${esc(facet.text)}">${id}</a>`).join(' ')).join('<br>'):'<span class="absent">None</span>';
  $('coverage-rows').innerHTML=coverage.map(c=>`<tr data-live-card="${c.cardId}"><th scope="row"><a href="#${c.primary}">${esc(c.name)} ↗</a><small>${esc(c.color)} / #${c.cardId} / ${c.primary}</small></th>${['main','objective','spend','bonus','reaction','routes'].map(kind=>`<td>${facetLinks(c.facets[kind])}</td>`).join('')}</tr>`).join('');
  $('export-data').addEventListener('click',()=>{
    const blob=new Blob([JSON.stringify(window.DFGFamilyStudy,null,2)],{type:'application/json'});
    const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=`dfg-families-revision-${window.DFGFamilyStudy.revision}.json`;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
  });
  for (const color of colors) $('hand').add(new Option(color,color));
  for (const label of ['All',...new Set(families.map(f=>f.category))]) {
    const b = document.createElement('button'); b.type='button'; b.textContent=label;
    b.dataset.category=label; b.setAttribute('aria-pressed',String(label==='All'));
    b.addEventListener('click',()=>{category=label;render();}); $('categories').append(b);
  }

  const routesMarkup = routes => routes.map(c=>`<span><i class="route-dot" style="--dot:${palette[c]}" aria-hidden="true"></i>${esc(c)}</span>`).join(' ');
  function cardMarkup(v,f) {
    const label = v.costLabel || (v.cost ? `${v.cost} STA` : 'FREE');
    const color=v.color??f.color;
    return `<article class="draft-card${v.liveCardId?' live-card':''}" data-color="${esc(color)}" data-card="${esc(v.id)}" ${v.liveCardId?`data-live-id="${v.liveCardId}"`:''} aria-labelledby="title-${v.id}">
      <div class="card-strip"><span>${esc(color)} / ${v.liveCardId?'CURRENT':v.referenceOf?'REFERENCE':'DRAFT'}</span><span>${v.id}</span></div>
      <div class="card-face"><h4 id="title-${v.id}">${esc(v.name)}</h4><span class="variant-index ${v.liveCardId?'live-index':''}" aria-hidden="true">${v.liveCardId?`v${v.liveRulesVersion}`:v.id.split('-')[1].padStart(2,'0')}</span></div>
      <div class="stat-row"><div><strong>${v.attack}</strong><span>Attack</span></div><div><strong>${v.block}</strong><span>Block</span></div><div class="cost"><strong>${esc(label)}</strong><span>Cost</span></div></div>
      <div class="card-rules"><p class="${v.text?'':'no-effect'}">${esc(v.text || 'No additional main effect.')}</p></div>
      <p class="card-role"><b>The bet</b>${esc(v.bet)}</p>
      <div class="card-footer"><div><b>${v.liveCardId?'SOURCE':'TAGS'}</b> ${v.liveCardId?`Current card #${v.liveCardId} · no draft tags`:esc(v.tags.join(' · '))}</div><div><b>BONUS</b> ${esc((v.bonus ?? f.bonus)||'Unavailable')}</div><div><b>REACTION</b> ${esc((v.reaction ?? f.reaction)||'Unavailable')}</div><div class="routes"><b>NEXT HAND</b> ${routesMarkup(v.routes ?? f.routes)}</div></div>
    </article>`;
  }
  function familyMarkup(f) {
    return `<section class="family" id="${f.id}" aria-labelledby="heading-${f.id}">
      <div class="family-top"><div><p class="family-kicker">${esc(f.category)} / DRAFT HAND ${esc(f.color)} / ${f.variants.length} GALLERY ENTRIES</p><h3 id="heading-${f.id}"><span class="family-number">${String(f.number).padStart(2,'0')}</span>${esc(f.title)}<span class="scope-tag ${f.scope==='New system'?'new':''}">${esc(f.scope)}</span></h3></div><button type="button" class="save" data-save="${f.id}" aria-pressed="${saved.has(f.id)}" aria-label="${saved.has(f.id)?'Remove':'Shortlist'} ${esc(f.title)}">${saved.has(f.id)?'✓ Saved':'+ Shortlist'}</button></div>
      <p class="family-question">${esc(f.question)}</p><div class="family-context"><span><b>VARIATION</b> ${esc(f.axis)}</span><span>Draft hand: ${esc(f.color)}${f.variants.some(v=>v.liveCardId)?' · current baselines retain their own hand and modes':''}</span></div>
      ${f.sourceCards?.length?`<details class="source-links"><summary>Current-card connections (${f.sourceCards.length})</summary><p>${f.sourceCards.map(c=>`<a href="#${c.primary}">${esc(c.name)} ↗</a>`).join(' · ')}</p></details>`:''}
      <div class="family-review"><p><strong>Self-critique / coverage gap</strong>${esc(f.review.critique)}</p><p><strong>Refinement</strong>${esc(f.review.changes)}</p>${f.before.length?`<details><summary>Compare the ${f.before.length}-entry first draft</summary><ul>${f.before.map(v=>`<li><b>${esc(v.name)}</b> · ${v.attack}/${v.block}${v.cost?` · ${v.cost} stamina`:''}. ${esc(v.text||'No main effect.')}<br><small>Bonus: ${esc(v.bonus)} Reaction: ${esc(v.reaction)} Routes: ${esc(v.routes.join('/'))}</small></li>`).join('')}</ul></details>`:'<small>New family added to cover a current-card mechanic.</small>'}</div>
      <div class="family-grid" style="--columns:${Math.min(6,f.variants.length)}">${f.variants.map(v=>cardMarkup(v,f)).join('')}</div>
      <div class="family-notes"><p><strong>Where the tradeoff matters</strong>${esc(f.crossing)}</p><p><strong>Still to test</strong>${esc(f.review.test)}</p></div>
      <div class="family-actions">${f.lab?`<button type="button" data-lab="${f.id}">Explore this payoff ↑</button>`:''}<a href="#${f.id}" aria-label="Link to ${esc(f.title)}">Family permalink ↗</a></div>
    </section>`;
  }
  function render() {
    const query = $('search').value.toLocaleLowerCase().trim();
    const visible = families.filter(f=>(category==='All'||category===f.category)&&($('hand').value==='All'||$('hand').value===f.color||f.variants.some(v=>v.color===$('hand').value))&&($('scope').value==='All'||$('scope').value===f.scope)&&(!savedOnly||saved.has(f.id))&&(!query||JSON.stringify({title:f.title,question:f.question,axis:f.axis,color:f.color,category:f.category,scope:f.scope,variants:f.variants,sources:f.sourceCards}).toLocaleLowerCase().includes(query)));
    $('families').innerHTML=visible.map(familyMarkup).join('');
    $('result-count').textContent=`${visible.length} of ${families.length} families · ${visible.reduce((n,f)=>n+f.variants.length,0)} entries${savedOnly?' · shortlisted families':''} · whole families stay together${query?' · matching current-card connections included':''}`;
    $('empty').hidden=visible.length!==0;
    $('saved-count').textContent=saved.size;
    $('saved-filter').setAttribute('aria-pressed',String(savedOnly));
    for (const b of $('categories').children) b.setAttribute('aria-pressed',String(category===b.dataset.category));
  }
  function reset() {category='All';savedOnly=false;$('search').value='';$('hand').value='All';$('scope').value='All';render();}
  $('reset').addEventListener('click',reset); $('clear-empty').addEventListener('click',reset);
  $('saved-filter').addEventListener('click',()=>{savedOnly=!savedOnly;render();});
  for (const id of ['search','hand','scope']) $(id).addEventListener(id==='search'?'input':'change',render);
  $('families').addEventListener('click',event=>{
    const b = event.target.closest('button'); if(!b)return;
    if(b.dataset.save) {
      const id=b.dataset.save; if(saved.has(id))saved.delete(id);else saved.add(id);
      try{localStorage.setItem('dfg-family-study-shortlist',JSON.stringify([...saved]));}catch{}
      render(); document.querySelector(`[data-save="${id}"]`)?.focus({preventScroll:true});
    } else if(b.dataset.lab) {
      $('lab-family').value=b.dataset.lab; renderLab(); $('lab-title').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth',block:'start'}); $('lab-family').focus({preventScroll:true});
    }
  });

  for(const f of families.filter(f=>f.lab))$('lab-family').add(new Option(`${f.id} · ${f.title}`,f.id));
  function renderLab() {
    const f=byId.get($('lab-family').value);
    const input={life:Number($('life').value),enemyAttack:Number($('enemy-attack').value),enemyBlock:Number($('enemy-block').value),stamina:Number($('stamina').value),ownBonus:$('own-bonus').checked,enemyBonus:$('enemy-bonus').checked};
    $('life-value').value=input.life; $('attack-value').value=input.enemyAttack; $('block-value').value=input.enemyBlock; $('stamina-value').value=input.stamina;
    const resource=f.variants.some(v=>v.cost||v.model?.reserveThreshold);
    $('stamina-control').hidden=!resource;
    $('lab-caption').textContent=`${f.id} / ${f.title} — one-exchange comparison`;
    $('lab-assumptions').textContent=`${f.crossing} Assumptions: bonus flags affect conditions only; no burn stat effects, reactions, advantage, carry, or statuses. Opposing values are assumed both printed and final. Both attacks resolve even on knockout. ${f.number===1||f.number===16?'Recovery occurs on reveal, before damage. ':f.number===2||f.number===3?'Recovery follows damage and requires survival. ':''}${resource?'Unaffordable mains are unavailable; stamina spending has no assigned value in the life-swing metric.':''}`;
    const variants=f.variants.filter(v=>!v.liveCardId);
    const results=variants.map(v=>evaluate(v,input));
    const best=Math.max(...results.filter(r=>r.available&&!r.ko).map(r=>r.score));
    $('lab-results').innerHTML=variants.map((v,i)=>{
      const r=results[i]; if(!r.available)return `<tr class="unavailable"><th scope="row">${esc(v.name)}</th><td>${v.attack} / ${v.block}</td><td colspan="5">Unavailable · requires ${v.cost} stamina</td></tr>`;
      const leads=!r.ko&&r.score===best;
      return `<tr class="${leads?'best ':''}${r.ko?'ko':''}"><th scope="row">${esc(v.name)}${leads?' · lead':''}</th><td>${r.attack} / ${r.block}</td><td>+${r.gained}</td><td>${r.taken}</td><td>${r.dealt}</td><td>${r.life}${r.ko?' · KO':''}</td><td>${r.score>0?'+':''}${r.score}</td></tr>`;
    }).join('');
  }
  for(const id of ['lab-family','life','enemy-attack','enemy-block','stamina','own-bonus','enemy-bonus'])$(id).addEventListener(id==='lab-family'||id.endsWith('bonus')?'change':'input',renderLab);
  function goToHash() {
    const id=location.hash.slice(1); if(!byId.has(id))return;
    if(!$(id))reset();
    requestAnimationFrame(()=>$(id)?.scrollIntoView({block:'start'}));
  }
  render();renderLab();goToHash();window.addEventListener('hashchange',goToHash);
})();
