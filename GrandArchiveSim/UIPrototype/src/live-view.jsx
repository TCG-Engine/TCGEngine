import React, { useEffect, useState, useSyncExternalStore } from 'react';
import { createLiveViewStore, requestLiveMatch } from './live-view-store.mjs';
import { LiveActions } from './live-actions.jsx';
import { LiveCard } from './live-card.jsx';
export function LiveView() {
 const [store] = useState(() => createLiveViewStore({ request: requestLiveMatch }));
 const [credentials, setCredentials] = useState(null);
 const state = useSyncExternalStore(store.subscribe, store.getSnapshot);
 useEffect(() => {
  const params = new URLSearchParams(window.location.hash.slice(1));
  const gameName = params.get('gameName'), playerID = params.get('playerID'), authKey = params.get('authKey');
  if (/^[0-9]+$/.test(gameName || '') && ['1', '2', 'S'].includes(playerID) && authKey) {
   const connection = { gameName, playerID, authKey };
   setCredentials(connection);
   store.connect(connection);
  }
  return () => store.disconnect();
 }, [store]);
 const connect = event => {
  event.preventDefault();
  const form = event.currentTarget;
  const connection = Object.fromEntries(new FormData(form));
  setCredentials(connection);
  store.connect(connection);
  form.reset();
 };
 useEffect(() => { if (state.status === 'error') setCredentials(null); }, [state.status]);
 return <div className="app live-view"><header><strong>GRAND ARCHIVE</strong><span>Live match · Prototype</span></header>
 <details className="guide live-connection" open={state.status !== 'connected'}><summary>{state.status === 'connected' ? 'Connected · Match settings' : 'Connect to a match'}</summary><h1>{state.status === 'connected' ? 'Live match' : 'Live match viewer'}</h1>{state.status !== 'connected' && <p>Open this preview from your game server at GrandArchiveSim/UIPrototype/. Enter your match credentials to view its current board. The engine validates every action.</p>}
 <p role="status">{({ disconnected: 'Not connected.', connecting: 'Connecting…', connected: 'Connected · Updates every 2 seconds.', error: 'Connection lost or access denied. Check your credentials and reconnect.' })[state.status]}</p>
 {['disconnected', 'error'].includes(state.status) ? <form onSubmit={connect} className="connection-form" autoComplete="off">
 <label>Game number<input name="gameName" required pattern="[0-9]+" inputMode="numeric" /></label>
 <label>View as<select name="playerID"><option value="1">Player 1</option><option value="2">Player 2</option><option value="S">Spectator</option></select></label>
 <label>Match access key<input name="authKey" type="password" required autoComplete="off" /></label>
 <button className="primary">Connect</button></form> : <button onClick={() => { store.disconnect(); setCredentials(null); }}>Disconnect</button>}
 {state.status !== 'connected' && <small>Manual credentials are kept in memory. Lobby links retain the match key in the address fragment so refreshing reconnects; keep that link private. Inspect cards before acting. Choices are sent only when you confirm them.</small>}</details>
 {state.match && <><p>Turn {state.match.turn} · {state.match.phase || 'Match'} · Player {state.match.turnPlayer}’s turn · Revision {state.match.revision}</p>
 {state.match.winner && <section className="live-attention" role="status"><h1>Player {state.match.winner} wins</h1><p>This game has ended.</p></section>}
 {state.match.combat?.attacker && <section className="stack" aria-label="Combat"><strong>{state.match.combat.attacker.name}</strong> attacking {state.match.combat.target?.name || '· choosing target'}</section>}
 {state.match.attention && !state.match.controls?.decision && !state.match.winner && <section className="live-attention" aria-label="Match attention" role="status"><strong>{state.match.attention.message}</strong></section>}
 {state.match.reveals?.length > 0 && <section className="stack" aria-label="Revealed cards"><h2>Revealed cards</h2>{state.match.reveals.map((card,index) => <details key={index}><summary>{card.name || 'Unknown card'}</summary><p className="printed-text">{card.printedText}</p></details>)}</section>}
 <details role="region" className="live-effect-stack" aria-label="Pending effects" open={state.match.effectStack?.length > 0}><summary>Pending effects · {state.match.effectStack?.length || 0}</summary>
 <p>Newest first. Declared targets appear when the engine records them.</p>
 {state.match.effectStack?.length > 0 ? <ol>{state.match.effectStack.map((effect, index) => <li key={effect.id}>
 <strong>{effect.name || effect.cardID || 'Unknown card'}</strong>
 <span> · {effect.kind}{effect.triggerLabel ? ` · ${effect.triggerLabel}` : ''}{effect.controller ? ` · Player ${effect.controller}` : ''}{index === 0 ? ' · Top of stack' : ''}</span>
 {effect.targets?.length > 0 && <p>Declared targets: {effect.targets.join(', ')}</p>}
 <details><summary>Printed card text</summary>
 <p>This is a card reference. It does not identify the pending ability, chosen targets, or copied and modified effects.</p>
 <p style={{whiteSpace: 'pre-wrap', overflowWrap: 'anywhere'}}>{effect.printedText || 'Printed text is unavailable for this card.'}</p>
 </details>
 </li>)}</ol> : <p>No pending stack entries. Combat or a decision may still be in progress.</p>}</details>
 {!state.match.controls?.decision && state.match.choices?.length > 0 && <section aria-label="Available card choices"><h2>Available card choices</h2>
 <p>Review the effect in the existing match screen, then choose a card below.</p>
 <ul>{state.match.choices.map(card => <li key={card.id}><strong>{card.name || card.cardID}</strong> · Player {card.owner} · {card.zone}</li>)}</ul></section>}
 <LiveActions match={state.match} credentials={credentials}>
 <div className="live-players">{[...state.match.players].sort((a,b) => (a.seat === state.match.viewerSeat) - (b.seat === state.match.viewerSeat)).map(player => <section key={player.seat} aria-label={`Player ${player.seat} board`}>
 <div className="player"><strong>Player {player.seat}{player.seat === state.match.viewerSeat ? ' · You' : ''}</strong><b>{player.damage}<small>damage</small></b></div>
 {Object.entries(player.zones).map(([name, zone]) => {
  const primary = name === 'Field' || (name === 'Hand' && player.seat === state.match.viewerSeat) || (name === 'Intent' && zone.count > 0);
  if (!primary && zone.count === 0) return null;
  const content = <div className="field">
   {zone.cards.map(card => <LiveCard key={`${state.match.revision}:${card.id}`} card={card} zone={name} owner={player.seat} />)}
   {zone.count > zone.cards.length && <p>{zone.count - zone.cards.length} hidden cards</p>}
   {zone.count === 0 && <small>Empty</small>}
  </div>;
  return primary ? <section className={`live-zone live-zone-${name.toLowerCase()}`} key={name}><h2>{name} <small>{zone.count} cards</small></h2>{content}</section>
   : <details className="secondary-zone" key={name}><summary>{name} · {zone.count} cards</summary>{content}</details>;
 })}
 </section>)}</div></LiveActions></>}
 </div>;
}
