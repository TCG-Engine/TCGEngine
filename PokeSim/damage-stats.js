/* Shared match and batch views; missing legacy stats are never interpreted as zero. */
(function(root){
    function aggregate(games, deck='all') {
        const bins = new Map();
        for (const game of games) {
            if (game.status !== 'complete') continue;
            for (const row of game.damageTurns || []) {
                if (!row.complete || deck !== 'all' && row.deck !== deck) continue;
                const key = row.turn + ':' + row.order;
                if (!bins.has(key)) bins.set(key, {turn:row.turn, order:row.order, count:0, sum:0, counts:{}});
                const bin = bins.get(key); ++bin.count; bin.sum += row.damage;
                bin.counts[row.damage] = (bin.counts[row.damage] || 0) + 1;
            }
        }
        return [...bins.values()].sort((a,b)=>a.turn-b.turn || a.order.localeCompare(b.order));
    }
    function el(tag, value) { const e=document.createElement(tag); if(value!==undefined)e.textContent=value; return e; }
    function chart(container, series, label) {
        const ns='http://www.w3.org/2000/svg', svg=document.createElementNS(ns,'svg');
        svg.setAttribute('viewBox','0 0 680 260'); svg.setAttribute('role',series.some(s=>s.points.some(p=>p.details))?'group':'img'); svg.setAttribute('aria-label',label);
        const maxTurn=Math.max(2,...series.flatMap(s=>s.points.map(p=>p.turn))), maxDamage=Math.max(100,...series.flatMap(s=>s.points.map(p=>p.damage)));
        const x=t=>52+(t-1)*600/(maxTurn-1), y=d=>215-d*185/maxDamage;
        function shape(tag, attrs, value){const e=document.createElementNS(ns,tag);for(const [k,v] of Object.entries(attrs))e.setAttribute(k,v);if(value!==undefined)e.textContent=value;svg.append(e);return e;}
        for(let i=0;i<=4;i++){const d=maxDamage*i/4;shape('line',{x1:52,x2:652,y1:y(d),y2:y(d),stroke:'#d6dfd7'});shape('text',{x:44,y:y(d)+4,'text-anchor':'end','font-size':12},Math.round(d));}
        for(let t=1;t<=maxTurn;t++)if(t===1||t===maxTurn||t%Math.ceil(maxTurn/12)===0)shape('text',{x:x(t),y:236,'text-anchor':'middle','font-size':12},t);
        shape('text',{x:350,y:256,'text-anchor':'middle','font-size':12},'Each player’s turn');
        const legend=el('div'); legend.className='damage-legend';
        const plot=el('div');plot.className='damage-plot';
        const tooltip=el('div');tooltip.className='damage-tooltip';tooltip.hidden=true;tooltip.setAttribute('aria-hidden','true');
        let hovered=null,focused=null;
        function updateTooltip(){
            const active=hovered||focused;
            if(!active){tooltip.hidden=true;return;}
            tooltip.textContent=active.details;tooltip.hidden=false;
            const bounds=plot.getBoundingClientRect(),dotBounds=active.dot.getBoundingClientRect();
            const center=dotBounds.left+dotBounds.width/2-bounds.left;
            tooltip.style.left=Math.max(0,Math.min(center-tooltip.offsetWidth/2,bounds.width-tooltip.offsetWidth))+'px';
            const above=dotBounds.top-bounds.top-tooltip.offsetHeight-10;
            tooltip.style.top=(above>=0?above:dotBounds.bottom-bounds.top+10)+'px';
        }
        for(const s of series){
            shape('polyline',{points:s.points.map(p=>x(p.turn)+','+y(p.damage)).join(' '),fill:'none',stroke:s.color,'stroke-width':3,'stroke-dasharray':s.dashed?'7 5':'none'});
            for(const p of s.points){
                const dot=shape('circle',{cx:x(p.turn),cy:y(p.damage),r:4,fill:s.color});
                if(p.details){
                    const active={dot,details:p.details};
                    dot.classList.add('damage-dot');dot.setAttribute('tabindex','0');dot.setAttribute('role','img');dot.setAttribute('aria-label',p.details);
                    dot.addEventListener('mouseenter',()=>{hovered=active;updateTooltip();});
                    dot.addEventListener('mouseleave',()=>{hovered=null;updateTooltip();});
                    dot.addEventListener('focus',()=>{focused=active;updateTooltip();});
                    dot.addEventListener('blur',()=>{focused=null;updateTooltip();});
                    dot.addEventListener('click',()=>{dot.focus();focused=active;updateTooltip();});
                    dot.addEventListener('keydown',event=>{if(event.key==='Escape'){hovered=null;focused=null;updateTooltip();}});
                }else{
                    const title=document.createElementNS(ns,'title');title.textContent=s.name+' · Turn '+p.turn+': '+p.damage.toFixed(1)+' damage';dot.append(title);
                }
            }
            const item=el('span'),swatch=el('span');item.style.color=s.color;swatch.className='damage-legend-swatch'+(s.dashed?' dashed':'');swatch.setAttribute('aria-hidden','true');item.append(swatch,document.createTextNode(s.name));legend.append(item);
        }
        plot.append(svg,tooltip);container.append(legend,plot);
    }
    function table(container, headers, rows){const wrap=el('div'),t=el('table'),head=el('thead'),tr=el('tr'),body=el('tbody');wrap.className='damage-table';for(const h of headers){const th=el('th',h);th.scope='col';tr.append(th);}head.append(tr);for(const values of rows){const row=el('tr');for(const v of values)row.append(el('td',v));body.append(row);}t.append(head,body);wrap.append(t);container.append(wrap);}
    function match(container, rows, names=['Player 1','Player 2']){
        container.replaceChildren(el('h3','Damage this game'));
        if(!rows?.length){container.append(el('p','Damage tracking starts with the first turn.'));return;}
        chart(container,[1,2].map((seat,i)=>({name:names[i],color:i?'#b85c32':'#287357',points:rows.filter(r=>r.player===seat).map(r=>({turn:r.turn,damage:r.damage}))})), 'Damage per turn by each player');
        table(container,['Player','Turn','Damage','Status'],rows.map(r=>[names[r.player-1],r.turn,r.damage,r.complete?'Finished':'In progress']));
    }
    function averages(container,games){
        const selected=container.querySelector('select')?.value||'all';
        container.replaceChildren(el('h3','Average damage by deck and starting order'));
        const decks=Array.from(new Set(games.flatMap(g=>(g.damageTurns||[]).map(r=>r.deck)))).filter(Boolean).sort();
        const deckName=key=>key==='sinistcha'?'Dhelmise / Sinistcha':key==='dhelmise-v2'?'dhelmise v2':key==='brisbane-lopunny'?'Brisbane Lopunny':key==='relicanth-v2-draw'?'relicanth v2 - draw':key==='relicanth-v3-meta-tune'?'Relicanth-v3-meta-tune':key==='relicanth-v4-colress'?'Relicanth v4 - Colress':key==='relicanth-v5-bastiodon'?'Relicanth v5 - Bastiodon':key==='relicanth-v6-explorers-guidance'?"Relicanth v6 - Explorer's Guidance":key==='relicanth-v7-lanas-aid'?"Relicanth v7 - Lana's Aid":key;
        const label=el('label','Deck '),select=el('select');select.setAttribute('aria-label','Damage statistics deck');
        for(const [key,name] of [['all',decks.length===2?'Both decks':'All decks'],...decks.map(key=>[key,deckName(key)])]){const option=el('option',name);option.value=key;select.append(option);}
        select.value=selected;if(!select.value)select.value='all';select.onchange=()=>averages(container,games);label.append(select);container.append(label);
        container.append(el('p','Completed games only. Zero-damage turns count; turns never reached do not. Damage includes opposing Active and Bench Pokémon, after modifiers and counters. Self-damage and Checkup are excluded.'));
        container.append(el('p','Each deck has its own color. Solid lines show going first; dashed lines show going second. These values are average damage, not attack success percentages.'));
        const series=(select.value==='all'?decks:[select.value]).flatMap(deck=>{
            const bins=aggregate(games,deck);
            const color=deck==='sinistcha'?'#287357':deck==='brisbane-lopunny'?'#b85c32':['#446cad','#8154a3','#9b711d'][decks.indexOf(deck)%3];
            return ['first','second'].map(order=>({name:deckName(deck)+' · Going '+order,color,dashed:order==='second',points:bins.filter(b=>b.order===order).map(b=>({turn:b.turn,damage:b.sum/b.count,details:deckName(deck)+'\nTurn '+b.turn+' · Going '+b.order+'\nAverage damage: '+(b.sum/b.count).toFixed(1)+'\nSamples: '+b.count+'\nDamage → count\n'+Object.entries(b.counts).sort((a,c)=>Number(a[0])-Number(c[0])).map(([d,n])=>d+' → '+n).join(' · ')}))}));
        }).filter(s=>s.points.length);
        if(!series.length){container.append(el('p','Complete a tracked game to see averages and damage counts. Older games without tracking are excluded.'));return;}
        container.append(el('p','Hover, focus, or tap a dot to see its average damage, samples, and damage counts.'));
        chart(container,series,'Average damage per turn by deck, going first and second');
    }
    root.PokeDamageStats={aggregate,match,averages};
    if(typeof module!=='undefined')module.exports=root.PokeDamageStats;
})(typeof window==='undefined'?globalThis:window);
