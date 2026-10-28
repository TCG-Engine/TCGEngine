import React, { memo, useState, useSyncExternalStore, useRef, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { createDemoStore } from './store.mjs';
import './style.css';
import { LiveView } from './live-view.jsx';
const store = createDemoStore();
const Card = memo(function Card({ card, onInspect }) {
 return <button className="playing-card" onClick={e=>onInspect(card.id,e.currentTarget)}><span className="art" aria-hidden="true">{card.symbol}</span><strong>{card.name}</strong><small>{card.zone} · Inspect</small></button>;
});
// Stable event function lets unchanged cards skip renders on priority updates.
function App() {
 const state = useSyncExternalStore(store.subscribe, store.getSnapshot);
 const [selected,setSelected]=useState(null), [options,setOptions]=useState(false);
 const origin=useRef(null), dialog=useRef(null), decision=useRef(null), app=useRef(null);
 useEffect(()=>{
  const observer=new ResizeObserver(()=>{
   app.current?.style.setProperty('--decision-height', `${decision.current.getBoundingClientRect().height}px`);
  });
  observer.observe(decision.current);
  return ()=>observer.disconnect();
 },[]);
 const inspect=React.useCallback((id,element)=>{origin.current=element;setSelected(id);},[]);
 const card=state.cards.find(c=>c.id===selected);
 useEffect(()=>{if(card && !dialog.current.open)dialog.current.showModal();},[card]);
 const close=()=>{dialog.current.close();setSelected(null);origin.current?.focus();};
 const send=(type,cardId)=>store.command({type,cardId,revision:state.revision});
 const title=state.priority!=='you'?'Opponent responding':state.reminderMatched?'Use Forese before resolution?':state.scenario==='attack'?'Respond to the attack':'Your action';
 return <><div className="trial-bar"><strong>React UI trial</strong><span>Simulated match · No live actions</span><label>Scenario <select value={state.scenario} onChange={e=>{store.scenario(e.target.value);setOptions(false);}}><option value="action">Your action</option><option value="attack">Attack response</option><option value="forese">Forese response</option><option value="waiting">Opponent priority</option></select></label><button onClick={()=>store.reset()}>Reset</button></div>
 <div className="app" ref={app}><header><strong>GRAND ARCHIVE</strong><span>Turn 6 · {state.scenario==='attack'?'Combat':'Main phase'}</span></header><main><section className="battlefield" aria-label="Match board"><div className="player"><div><strong>Opponent · Lorraine</strong><small>Level 2 · Hand 4 · Memory 3</small></div><b>8 <small>damage</small></b></div><div className="zone-label">OPPONENT’S FIELD</div><div className="field opponent"><div className="opponent-card"><span>♜</span><strong>Opposing ally</strong><small>{state.scenario==='attack'?'Attacking':'Ready'}</small></div><div className="opponent-card"><span>⚔</span><strong>Weapon</strong><small>2 durability</small></div></div><div className="stack"><span aria-hidden="true">◇</span> {state.pending || 'No effects waiting to resolve'}</div><div className="zone-label">YOUR FIELD <span>Forese · {state.foreseReady?'Ready':'Rested'}</span></div><div className="field">{state.cards.filter(c=>c.zone==='Your field').map(c=><Card key={c.id} card={c} onInspect={inspect}/>)}</div><div className="player"><div><strong>You · Arisanna</strong><small>Level 2 · {state.priority==='you'?'Your priority':'Waiting'}</small></div><b>5 <small>damage</small></b></div><div className="resources"><span>Memory 3</span><span>Material 8</span><span>Graveyard 6</span><span>Banish 2</span></div><div className="zone-label">YOUR HAND</div><div className="hand">{state.cards.filter(c=>c.zone==='Hand').map(c=><Card key={c.id} card={c} onInspect={inspect}/>)}</div></section>
 <aside><section className="decision" ref={decision} aria-label="Response controls"><small className="eyebrow">{state.reminderMatched?'SAVED RESPONSE · FORESE':'PRIORITY'}</small><h1>{title}</h1><p role="status">{state.notice}</p><div className="actions">{state.reminderMatched&&state.priority==='you'&&<button className="primary" onClick={()=>send('activate','forese')}>Activate Forese</button>}<button className={state.reminderMatched?'':'primary'} disabled={state.priority!=='you'} onClick={()=>send('pass')}>Pass once</button></div><button className="preferences" aria-expanded={options} onClick={()=>setOptions(!options)}>Response preferences</button>{options&&<div className="preferences-panel"><label><input type="checkbox" checked={state.reminder} onChange={e=>store.setReminder(e.target.checked)}/> Remind me about Forese when I play a Harmony or Melody</label><p>Demo preference only. Automatic passing is not implemented.</p></div>}</section><section className="guide"><h2>Inspect, then act</h2><p>Tap a card for details. Playing or activating requires a separate choice.</p><small>Illustrative cards and values; no rules simulation.</small></section><section className="probe"><button onClick={()=>store.backgroundUpdate()}>Simulate unrelated update</button><small>Updates: {state.updates} · Revision: {state.revision}</small></section></aside></main></div>
 <dialog ref={dialog} onCancel={e=>{e.preventDefault();close();}} aria-labelledby="card-title"><div className="inspection"><button className="close" onClick={close} aria-label="Close card inspection">×</button><small>CARD INSPECTION</small><h2 id="card-title">{card?.name}</h2><div className="large-art" aria-hidden="true">{card?.symbol}</div><p>{card?.detail}</p>{card?.id==='forese'&&<button className="primary" disabled={!state.foreseReady||state.priority!=='you'} onClick={()=>{send('activate','forese');close();}}>Activate Forese</button>}{card?.zone==='Hand'&&<button className="primary" disabled={state.priority!=='you'} onClick={()=>{send('play',card.id);close();}}>Play {card.name}</button>}<button onClick={close}>Back to board</button></div></dialog></>;
}
function Trial() {
 const [live, setLive] = useState(false);
 return <><nav className="trial-bar" aria-label="Preview mode"><button onClick={() => setLive(!live)}>{live ? 'Return to simulated preview' : 'Open live viewer'}</button></nav>{live ? <LiveView/> : <App/>}</>;
}
createRoot(document.getElementById('ga-react-trial')).render(<Trial/>);
