import React, { useContext, useEffect, useRef, useState } from 'react';
import { LiveActionContext } from './live-actions.jsx';

// Inspection never submits an action. The parent keys this component by revision:
// an index-based card ID must not keep a stale inspection open after cards move.
export function LiveCard({ card, zone, owner }) {
 const actions = useContext(LiveActionContext);
 const commands = (actions?.commands || []).filter(command => command.id.split(':')[1] === card.id);
 const [open, setOpen] = useState(false);
 const trigger = useRef(null);
 const dialog = useRef(null);
 useEffect(() => { if (open) dialog.current?.showModal(); }, [open]);
 const close = () => { dialog.current?.close(); setOpen(false); trigger.current?.focus(); };
 return <>
 <button className="playing-card" ref={trigger} onClick={() => setOpen(true)} aria-label={`Inspect ${card.name || card.cardID}`}>
 <img className="live-card-art" loading="lazy" alt="" src={`../WebpImages/${encodeURIComponent(card.cardID)}.webp`} onError={event => { event.currentTarget.hidden = true; }} /><strong>{card.name || card.cardID}</strong>{commands.length > 0 && <small className="card-action-hint">Action available</small>}<small>{zone}{zone === 'Field' ? ` #${Number(card.id.split('-').slice(-1)[0]) + 1}` : ''} · Inspect</small>
 {zone === 'Field' && <span>{card.rested ? 'Rested' : 'Ready'} · {card.damage} damage</span>}
 {(card.power != null || card.hp != null || card.level != null) && <span>{[card.power != null ? `${card.power} power` : null, card.hp != null ? `${card.hp} life` : null, card.level != null ? `Level ${card.level}` : null].filter(Boolean).join(' · ')}</span>}
 {card.direction && <small>{card.direction}</small>}
 {card.keywords?.length > 0 && <small>{card.keywords.join(' · ')}</small>}
 {Object.entries(card.counters || {}).map(([name, amount]) => <small key={name}>{name}: {amount}</small>)}
 </button>
 {open && <dialog ref={dialog} aria-label={`Card details: ${card.name || card.cardID}`} onCancel={event => { event.preventDefault(); close(); }}>
 <div className="inspection"><button className="close" onClick={close} aria-label="Close card inspection">×</button>
 <small>Player {owner} · {zone}</small><h2>{card.name || card.cardID}</h2>
 <p className="printed-text">{card.printedText || 'Printed text is unavailable for this card.'}</p>
 {card.lineage?.length > 0 && <section><h3>Lineage / attached cards</h3>{card.lineage.map((entry,index) => <details key={index}><summary>{entry.name || 'Unknown card'}</summary><p className="printed-text">{entry.printedText}</p></details>)}</section>}
 <p>Printed card reference. In-game effects can change this card’s abilities.</p>
 {commands.map(command => <button className="primary" key={command.id} disabled={actions.disabled} onClick={() => { actions.command(command.id); close(); }}>{command.label}</button>)}
 <button onClick={close}>Back to board</button></div>
 </dialog>}
 </>;
}
