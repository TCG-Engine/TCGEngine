import React, { useState } from 'react';

export function LiveDecision({ decision, disabled, onSubmit }) {
 const { type, options = [], minimum = 1, maximum = 1 } = decision;
 const [selected, setSelected] = useState([]);
 const [number, setNumber] = useState(String(minimum));
 const [name, setName] = useState('');
 const [amounts, setAmounts] = useState({});
 const [piles, setPiles] = useState(() => Object.fromEntries((decision.piles || []).map(pile => [pile.id, options.filter(o => o.pile === pile.id).map(o => o.id)])));
 const toggle = id => setSelected(prev => prev.includes(id) ? prev.filter(i => i !== id) : maximum === 1 ? [id] : [...prev, id]);
 const move = (id, from, to) => setPiles(prev => ({ ...prev, [from]: prev[from].filter(i => i !== id), [to]: [...prev[to], id] }));
 const reorder = (pile, index, offset) => setPiles(prev => {
  const ids = [...prev[pile]];
  [ids[index], ids[index + offset]] = [ids[index + offset], ids[index]];
  return { ...prev, [pile]: ids };
 });
 let answer = selected, valid = selected.length >= minimum && selected.length <= maximum;
 if (['NUMBERCHOOSE', 'TWOSIDEDSLIDER'].includes(type)) {
  answer = number; valid = /^-?\d+$/.test(number) && Number(number) >= minimum && Number(number) <= maximum;
 } else if (type === 'NAMECARD') { answer = name; valid = name.trim().length > 0; }
 else if (type === 'MZSPLITASSIGN') { answer = amounts; valid = Object.values(amounts).every(v => Number.isInteger(v) && v >= 0) && Object.values(amounts).reduce((a,b) => a+b, 0) === decision.amount; }
 else if (type === 'MZREARRANGE') { answer = piles; valid = true; }
 if (decision.budget !== undefined) valid = valid && options.filter(o => selected.includes(o.id)).reduce((sum,o) => sum + (o.weight || 0), 0) <= decision.budget;
 return <form className="live-decision" onSubmit={event => { event.preventDefault(); if (valid && !disabled) onSubmit(answer); }}>
 <h2>{decision.prompt}</h2>
 {type === 'TWOSIDEDSLIDER' && <p>{decision.left}: {number || '0'} · {decision.right}: {maximum - Number(number || 0)}</p>}
 {decision.budget !== undefined && <p>Combined {decision.budgetLabel || 'cost'}: at most {decision.budget}</p>}
 <fieldset disabled={disabled}><legend>{type === 'MZREARRANGE' ? 'Arrange cards in each pile, first card at the top' : type === 'MZSPLITASSIGN' ? `Assign exactly ${decision.amount}` : 'Your choice'}</legend>
 {['NUMBERCHOOSE', 'TWOSIDEDSLIDER'].includes(type) ? <label>Choose a number ({minimum}–{maximum})<input type="number" min={minimum} max={maximum} step="1" value={number} onChange={e => setNumber(e.target.value)} /></label>
 : type === 'NAMECARD' ? <label>Card name<input maxLength={200} value={name} onChange={e => setName(e.target.value)} /></label>
 : type === 'MZREARRANGE' ? decision.piles.map(pile => <section key={pile.id}><h3>{pile.label}</h3><ol>{piles[pile.id].map((id, index) => <li key={id}>
 <strong>{options.find(o => o.id === id)?.label}</strong>
 <div className="actions"><button type="button" disabled={index === 0} onClick={() => reorder(pile.id,index,-1)}>Up</button><button type="button" disabled={index === piles[pile.id].length-1} onClick={() => reorder(pile.id,index,1)}>Down</button>
 {decision.piles.filter(p => p.id !== pile.id).map(p => <button type="button" key={p.id} onClick={() => move(id,pile.id,p.id)}>Move to {p.label}</button>)}</div>
 </li>)}</ol></section>)
 : options.map(option => <div className="decision-option" key={option.id}><label>
 {type === 'MZSPLITASSIGN' ? <input aria-label={`Amount for ${option.label}`} type="number" min="0" max={decision.amount} step="1" value={amounts[option.id] ?? 0} onChange={e => setAmounts({...amounts,[option.id]:e.target.value === '' ? 0 : Number(e.target.value)})} />
 : <input type="checkbox" checked={selected.includes(option.id)} onChange={() => toggle(option.id)} disabled={!selected.includes(option.id) && maximum > 1 && selected.length >= maximum} />}
 <span>{option.label}</span></label>{option.printedText && <details><summary>Card reference</summary><p className="printed-text">{option.printedText}</p></details>}</div>)}
 {!['MZREARRANGE','MZSPLITASSIGN','NAMECARD','NUMBERCHOOSE','TWOSIDEDSLIDER'].includes(type) && <p>{selected.length} selected · Choose {minimum === maximum ? minimum : `${minimum}–${maximum}`}</p>}
 <button className="primary" disabled={!valid} type="submit">{type === 'MZMAYCHOOSE' && !selected.length ? 'Decline / pass' : 'Confirm choice'}</button>
 </fieldset></form>;
}
