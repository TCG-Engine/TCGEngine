import {defaults,validate,VERSION} from './bloodrush-model.mjs?v=2';
const $=id=>document.getElementById(id),fmt=n=>n===null||n===undefined?'—':n.toFixed(2),pct=n=>`${(100*n).toFixed(2)}%`;
let rows=[],selected=0,config={...defaults},worker;
const groupSummary=row=>$('group').value==='all'?row.all:row.groups[Number($('group').value)];
const groupStats=row=>{const s=groupSummary(row);return $('metric').value==='cycle'?s:s[$('metric').value];};
const line=(x1,y1,x2,y2,color)=>`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${color}"/>`;
function render() {
    if(!rows.length)return;
    if(!rows.some(r=>r.reds===selected))selected=rows[0].reds;
    $('selected').innerHTML=rows.map(r=>`<option value="${r.reds}">${r.reds} reds / ${r.blue5} blue fives</option>`).join('');$('selected').value=selected;
    const valid=rows.filter(r=>groupStats(r).probability>0),max=Math.max(10,...valid.map(r=>groupStats(r).max));
    const x=n=>55+900*n/Math.max(1,rows.at(-1).reds),y=n=>285-250*n/max;
    let svg='';
    for(let n=0;n<=max;n+=5)svg+=line(55,y(n),955,y(n),'#344552')+`<text x="42" y="${y(n)+4}" text-anchor="end">${n}</text>`;
    for(const r of valid){const s=groupStats(r),xx=x(r.reds);svg+=`<rect x="${xx-5}" y="${y(s.max)}" width="10" height="${Math.max(1,y(s.min)-y(s.max))}" fill="#607a8d" opacity=".45"/>`+line(xx,y(Math.max(0,s.mean-s.sd)),xx,y(Math.min(max,s.mean+s.sd)),'#c5b2ec')+`<circle cx="${xx}" cy="${y(s.mean)}" r="${r.reds===selected?6:3}" fill="#87d5df"><title>${r.reds} reds: mean ${fmt(s.mean)}, range ${s.min}–${s.max}, frequency ${pct(s.probability)}</title></circle>`;}
    for(let n=0;n<rows.length;n+=Math.max(1,Math.ceil(rows.length/12)))svg+=`<text x="${x(n)}" y="307" text-anchor="middle">${n}</text>`;
    const metricLabel={cycle:'Full-cycle value (damage + block)',damage:'Unblocked damage',block:'Pre-turn block value'}[$('metric').value];
    svg+=`<text x="500" y="334" text-anchor="middle">Number of red seven-power attacks</text><text x="55" y="18">${metricLabel}</text>`;
    if(!valid.length)svg+='<text x="500" y="160" text-anchor="middle">No turns in this group</text>';
    $('chart').innerHTML=svg;
    $('rows').innerHTML=rows.map(r=>{const s=groupStats(r),total=groupSummary(r);return `<tr data-red="${r.reds}" class="${r.reds===selected?'selected':''}"><td>${r.reds}</td><td>${r.blue5}</td><td>${pct(s.probability)}</td><td>${fmt(s.mean)}</td><td>${s.min??'—'}</td><td>${s.max??'—'}</td><td>${fmt(s.sd)}</td><td>${fmt(total.damage.mean)}</td><td>${fmt(total.block.mean)}</td><td>${s.failedRate===null?'—':pct(s.failedRate)}</td></tr>`;}).join('');
    const row=rows.find(r=>r.reds===selected),s=groupStats(row),total=groupSummary(row);
    $('detailTitle').textContent=`${row.reds} red sevens · ${row.blue5} blue fives · ${config.blue6Block+config.blue6Nonblock} blue sixes · ${config.bellows} Bellows`;
    $('stats').innerHTML=[['Mean full-cycle value',fmt(total.mean)],['Mean damage',fmt(total.damage.mean)],['Mean block',fmt(total.block.mean)],['Selected metric min / max',s.min===null?'—':`${s.min} / ${s.max}`],['Selected metric SD',fmt(s.sd)],['Group frequency',pct(s.probability)],['Failed discard',s.failedRate===null?'—':pct(s.failedRate)]].map(([label,value])=>`<div class="stat"><span>${label}</span><strong>${value}</strong></div>`).join('');
    $('detailNote').textContent=(config.arsenal?`Bellow starts in arsenal, outside the four-card hand. Opening hand contains 0 / 1 / 2 / 3 additional Bellows: ${row.openingBellows.map(pct).join(' / ')}. `:`${pct(row.openingProbability)} of random four-card hands contain a Bellow. Focused openings contain 1 / 2 / 3 Bellows: ${row.openingBellows.slice(1).map(pct).join(' / ')}. `)+`Under this policy, turns play 0 / 1 / 2 / 3 Bellows: ${row.groups.map(g=>pct(g.probability)).join(' / ')}. Blocks are chosen before draws; drawn or unused cards receive no retroactive block credit.`;
    const hist=Object.entries(s.histogram).map(([d,p])=>[Number(d),p]),peak=Math.max(.01,...hist.map(([,p])=>p)),right=Math.max(1,...hist.map(([d])=>d));
    $('histogram').innerHTML=hist.length?hist.map(([d,p])=>{const xx=55+875*d/right,hh=160*p/peak;return `<rect x="${xx}" y="${185-hh}" width="${Math.min(20,850/(right+1))}" height="${hh}" fill="#87d5df"><title>${d} value: ${pct(p)}</title></rect><text x="${xx}" y="205" text-anchor="middle">${d}</text>`;}).join('')+`<text x="55" y="18">Peak probability ${pct(peak)} · hover for exact probabilities</text><text x="500" y="228" text-anchor="middle">${metricLabel}</text>`:'<text x="500" y="115" text-anchor="middle">No turns in this group</text>';
    $('download').disabled=false;
}
$('selected').onchange=()=>{selected=Number($('selected').value);render();};$('group').onchange=render;$('metric').onchange=render;
$('rows').onclick=event=>{const tr=event.target.closest('[data-red]');if(tr){selected=Number(tr.dataset.red);render();}};
$('run').onclick=()=>{
    const next=Object.fromEntries(Object.keys(defaults).map(k=>[k,Number($(k).value)]));
    try{validate(next,0);}catch(e){$('status').textContent=e.message;return;}
    worker?.terminate();config=next;rows=[];$('run').disabled=true;$('download').disabled=true;
    $('status').textContent='Enumerating all outcomes…';
    worker=new Worker(new URL('./bloodrush-worker.mjs?v=2',import.meta.url),{type:'module'});
    worker.onmessage=({data})=>{
        if(data.error){$('status').textContent=data.error;$('run').disabled=false;worker.terminate();return;}
        rows.push(data.row);$('status').textContent=`${rows.length} / ${data.total} compositions complete`;render();
        if(data.done){$('run').disabled=false;$('status').textContent=`Exact sweep complete · ${rows.length} compositions · model v${VERSION}`;worker.terminate();}
    };
    worker.onerror=e=>{$('status').textContent=`Unable to run: ${e.message}`;$('run').disabled=false;worker.terminate();};
    worker.postMessage(config);
};
$('download').onclick=()=>{
    const url=URL.createObjectURL(new Blob([JSON.stringify({version:VERSION,config,rows},null,2)],{type:'application/json'})),a=document.createElement('a');a.href=url;a.download='bloodrush-results.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
};
const saved=$('savedResults');
if(saved){const data=JSON.parse(saved.textContent);config=data.config;rows=data.rows;selected=rows.reduce((best,row)=>row.all.mean>best.all.mean?row:best).reds;for(const k of Object.keys(defaults))$(k).value=config[k];$('status').textContent=`Saved exact sweep · ${rows.length} compositions · model v${data.version}`;render();}
else $('run').click();
