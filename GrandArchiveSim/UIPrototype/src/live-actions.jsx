import React, { createContext, useEffect, useRef, useState, useSyncExternalStore } from 'react';
import { LiveDecision } from './live-decision.jsx';
import { createLiveAdapter } from './live-adapter.mjs';
import { requestLiveMatch } from './live-view-store.mjs';

export const LiveActionContext = createContext(null);
export function LiveActions({ match, credentials, children }) {
 const [adapter] = useState(() => createLiveAdapter({
  submit: async (_mode, params) => {
   const body = new URLSearchParams(params);
   for (const [key, value] of Object.entries(credentials)) body.set(key, value);
   const response = await fetch(new URL('../UIAction.php', window.location.href), {
    method: 'POST', body, cache: 'no-store', credentials: 'same-origin', redirect: 'error',
    signal: AbortSignal.timeout(10000)
   });
   if (!response.ok) throw new Error('Action unavailable');
   return response.json();
  },
  refresh: () => requestLiveMatch(credentials, AbortSignal.timeout(10000))
 }));
 const panel = useRef(null);
 useEffect(() => {
  const element = panel.current, app = element.closest('.app');
  const observer = new ResizeObserver(() => app.style.setProperty('--decision-height', `${element.getBoundingClientRect().height}px`));
  observer.observe(element);
  return () => { observer.disconnect(); app.style.removeProperty('--decision-height'); };
 }, []);
 const state = useSyncExternalStore(adapter.subscribe, adapter.getSnapshot);
 useEffect(() => { adapter.receive(match); }, [adapter, match]);
 const disabled = state.pending || state.revision !== match.revision;
 const cardCommands = state.commands.filter(command => /^(play|attack|ability):/.test(command.id));
 return <LiveActionContext.Provider value={{commands:cardCommands,disabled,command:id=>adapter.command(id,state.revision)}}><div className="live-table-layout"><div className="live-board">{children}</div><section className="decision live-controls" ref={panel} aria-label="Match actions">
 <small className="eyebrow">{match.winner ? 'GAME COMPLETE' : match.controls?.decision ? 'YOUR DECISION' : match.attention?.message || 'MATCH ACTIONS'}</small>
 {match.controls?.decision && <LiveDecision key={match.revision} decision={match.controls.decision} disabled={state.pending || state.revision !== match.revision} onSubmit={answer => adapter.command('answer-decision', state.revision, answer)} />}
 {!match.controls?.decision && cardCommands.length > 0 && <p>Inspect a card to play, attack, or activate an ability.</p>}
 {state.commands.filter(command => !/^(play|attack|ability):/.test(command.id) && command.id !== 'answer-decision' && !(match.controls?.decision && command.id.startsWith('select:'))).map(command => <button key={command.id} disabled={state.pending || state.revision !== match.revision}
  onClick={() => { if (command.id !== 'concede' || window.confirm('Concede this game? Your opponent will win.')) adapter.command(command.id, state.revision); }}>{command.label}</button>)}
 {state.pending && <p role="status">Waiting for the updated match…</p>}
 {state.notice && <p role="status">{state.notice}</p>}
 {state.error && <p role="alert">{state.error}</p>}
 </section></div></LiveActionContext.Provider>;
}
