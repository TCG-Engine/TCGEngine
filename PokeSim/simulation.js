const $=id=>document.getElementById(id);
let job=null,running=false,pauseRequested=false;
const storageKey='pokesimMatchupBatch:v2';
try{
    const saved=JSON.parse(localStorage.getItem(storageKey)||'null');
    if(saved&&Number.isInteger(saved.total)&&saved.total>=2&&saved.total<=10000&&saved.total%2===0&&
        Number.isInteger(saved.seed)&&saved.seed>0&&Array.isArray(saved.rows)&&saved.rows.length<=saved.total&&saved.rows.length%2===0){
        job=saved;$('games').value=job.total;$('startSeed').value=job.seed;$('simDeck1').value=job.deck1;$('simDeck2').value=job.deck2;
        $('simulationStatus').textContent=job.rows.length===job.total?'Previous batch results restored.':'Previous batch restored. Resume to continue.';
    }
}catch{}
function save(){
    try{if(job)localStorage.setItem(storageKey,JSON.stringify(job));else localStorage.removeItem(storageKey);}
    catch{$('simulationError').textContent='Browser storage is full or unavailable. Download CSV before leaving this page.';}
}
function text(tag,value){const e=document.createElement(tag);e.textContent=value;return e;}
function deckName(key){return key==='brisbane-lopunny'?'Brisbane Lopunny':'Dhelmise / Sinistcha';}
function rate(wins,games){return games?(100*wins/games).toFixed(1)+'%':'—';}
function render(){
    const rows=job?.rows||[],complete=rows.filter(r=>r.status==='complete');
    const first=complete.filter(r=>r.winnerOrder==='first').length,second=complete.length-first;
    $('firstRate').textContent=rate(first,complete.length);$('secondRate').textContent=rate(second,complete.length);
    $('firstCount').textContent=first+' wins / '+complete.length+' completed';$('secondCount').textContent=second+' wins / '+complete.length+' completed';
    $('firstBar').style.width=(complete.length?100*first/complete.length:0)+'%';$('secondBar').style.width=(complete.length?100*second/complete.length:0)+'%';
    $('averageTurns').textContent=complete.length?(complete.reduce((n,r)=>n+r.turns,0)/complete.length).toFixed(1):'—';
    $('incomplete').textContent=rows.length-complete.length;
    $('progress').max=job?.total||Number($('games').value);$('progress').value=rows.length;
    $('progressText').textContent=job?rows.length+' / '+job.total+' games':'Ready to run';
    const locked=running||!!job&&rows.length<job.total;
    $('simDeck1').disabled=locked;$('simDeck2').disabled=locked;$('games').disabled=locked;$('startSeed').disabled=locked;$('run').disabled=running;
    $('run').textContent=running?'Running…':job&&rows.length<job.total?'Resume batch ↗':'Run batch ↗';
    $('pause').hidden=!running;$('pause').disabled=pauseRequested;
    $('pause').textContent=pauseRequested?'Pausing…':'Pause';
    $('clear').hidden=!job;$('clear').disabled=running;$('download').disabled=!rows.length;
    $('resultCount').textContent=rows.length;
    $('policy').textContent=job?.policy?deckName(job.deck1)+' (Seat 1) vs '+deckName(job.deck2)+' (Seat 2) · Bot / rules fingerprint: '+job.policy+' · Seeds '+job.seed+'–'+(job.seed+Math.max(0,rows.length/2-1)):'';
    const firstRows=complete.filter(r=>r.firstPlayer===1),secondRows=complete.filter(r=>r.firstPlayer===2);
    $('seatCheck').textContent=complete.length?'Seat 1 win rate: '+rate(firstRows.filter(r=>r.winner===1).length,firstRows.length)+' going first ('+firstRows.length+' games) · '+rate(secondRows.filter(r=>r.winner===1).length,secondRows.length)+' going second ('+secondRows.length+' games)':'';
    $('results').replaceChildren();
    PokeDamageStats.averages($('batchDamage'), rows);
    const selectedGame=$('damageGame').value;
    $('damageGame').replaceChildren();
    rows.forEach((row,i)=>{const option=text('option','Game '+(i+1)+' · Seed '+row.seed+' · Seat 1 '+(row.firstPlayer===1?'first':'second'));option.value=i;$('damageGame').append(option);});
    $('damageGame').value=selectedGame!==''&&Number(selectedGame)<rows.length?selectedGame:String(Math.max(0,rows.length-1));
    renderDamageGame();
    for(const row of rows.slice(-100).reverse()){
        const tr=document.createElement('tr');
        for(const value of [row.seed,row.firstPlayer===1?'First':'Second',row.winner?'Seat '+row.winner+' · '+row.winnerOrder:'—',row.turns,row.status==='complete'?row.reason:row.status+': '+row.reason])tr.append(text('td',value));
        $('results').append(tr);
    }
}
async function run(event){
    event.preventDefault();if(running)return;
    if(!job||job.rows.length===job.total){
        const total=Number($('games').value),seed=Number($('startSeed').value);
        if(!Number.isInteger(total)||total<2||total>10000||total%2||!Number.isInteger(seed)||seed<1||seed+total/2-1>2147483647){$('simulationError').textContent='Choose an even number of games (2–10,000) and a valid positive seed.';return;}
        job={total,seed,deck1:$('simDeck1').value,deck2:$('simDeck2').value,rows:[],policy:null};
    }
    running=true;pauseRequested=false;$('simulationError').textContent='';render();
    try{
        const auth=await (await fetch('api.php?player=1',{cache:'no-store'})).json();
        if(!auth.ok)throw Error(auth.error);
        const began=performance.now();
        while(job.rows.length<job.total&&!pauseRequested){
            $('simulationStatus').textContent='Both bots are playing. You can pause after the current group of games.';
            const pairs=Math.min(5,(job.total-job.rows.length)/2),seed=job.seed+job.rows.length/2;
            const response=await fetch('simulate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:auth.token,seed,pairs,deck1:job.deck1,deck2:job.deck2,...(job.policy?{policy:job.policy}:{})})});
            const data=await response.json();if(!data.ok)throw Error(data.error);
            if(job.policy&&job.policy!==data.policy)throw Error('The bot changed during this batch. Clear this batch and start again.');
            job.policy=data.policy;job.rows.push(...data.results);save();render();
        }
        $('simulationStatus').textContent=job.rows.length===job.total?'Batch complete. Download the CSV to keep every result.':'Paused. Resume to continue with the next seed.';
        if(job.rows.length===job.total)$('simulationStatus').textContent+=' Finished in '+((performance.now()-began)/1000).toFixed(1)+'s this run.';
    }catch(error){$('simulationError').textContent=error.message;$('simulationStatus').textContent='Stopped. Completed results are still available to download.';}
    finally{running=false;render();}
}
$('simulationForm').onsubmit=run;
$('pause').onclick=()=>{pauseRequested=true;render();};
$('clear').onclick=()=>{if(running)return;job=null;save();$('simulationStatus').textContent='';$('simulationError').textContent='';render();};
$('download').onclick=()=>{
    if(!job?.rows.length)return;
    const quote=value=>'"'+String(value??'').replaceAll('"','""')+'"';
    const keys=['seed','deck1','deck2','firstPlayer','winner','winnerOrder','turns','actions','status','reason','damageTurns'];
    const lines=[keys.concat('policy').map(quote).join(','),...job.rows.map(row=>keys.map(key=>quote(key==='damageTurns'?JSON.stringify(row[key]||[]):row[key])).concat(quote(job.policy)).join(','))];
    const url=URL.createObjectURL(new Blob([lines.join('\r\n')+'\r\n'],{type:'text/csv;charset=utf-8'})),a=document.createElement('a');
    a.href=url;a.download='pokesim-'+job.deck1+'-vs-'+job.deck2+'-seed-'+job.seed+'-'+job.rows.length+'-games.csv';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
};
function renderDamageGame(){const row=job?.rows[Number($('damageGame').value)];PokeDamageStats.match($('batchGameDamage'),row?.damageTurns||[]);}
$('damageGame').onchange=renderDamageGame;
render();

function updatePlayLink(){$('playLink').href='./?mode=bot&new=1&deck1='+$('playDeck').value+'&deck2='+$('playOpponent').value;}
$('playDeck').onchange=updatePlayLink;$('playOpponent').onchange=updatePlayLink;updatePlayLink();
