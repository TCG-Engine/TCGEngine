// Demo adapter only. A live adapter must supply viewer-safe state and route
// versioned commands to existing server-validated actions, never infer legality.
const cards = [
  { id: 'forese', name: 'Forese', zone: 'Your field', symbol: '♫', detail: 'Ready. This demo illustrates a saved Harmony / Melody response, not printed card rules.' },
  { id: 'ally', name: 'Your ally', zone: 'Your field', symbol: '♘', detail: 'An illustrative ready ally. Inspection does not activate or play it.' },
  { id: 'melody', name: 'Melody', zone: 'Hand', symbol: '♬', detail: 'Illustrative Melody. Playing it in this demo opens the Forese response scenario.' },
  { id: 'harmony', name: 'Harmony', zone: 'Hand', symbol: '♪', detail: 'Illustrative Harmony. Playing it in this demo opens the Forese response scenario.' },
];
export function createDemoStore() {
  const listeners = new Set();
  let state = Object.freeze({ revision: 0, scenario: 'action', priority: 'you', pending: null, notice: 'Choose a card or pass priority.', cards, foreseReady: true, reminder: true, reminderMatched: false, updates: 0 });
  const publish = patch => { state = Object.freeze({ ...state, ...patch, revision: state.revision + 1 }); listeners.forEach(fn => fn()); };
  const scenario = name => publish({ scenario: name, priority: name === 'waiting' ? 'opponent' : 'you', pending: name === 'forese' ? 'Your Melody' : name === 'attack' ? 'Opposing ally → Your champion' : null, foreseReady: true, reminderMatched: name === 'forese', notice: name === 'forese' ? 'Saved response matched. Your Melody has not resolved.' : name === 'attack' ? 'An attack was declared. You have a response window.' : name === 'waiting' ? 'Waiting for your opponent.' : 'Choose a card or pass priority.' });
  return {
    getSnapshot: () => state,
    subscribe(fn) { listeners.add(fn); return () => listeners.delete(fn); },
    scenario,
    setReminder(value) { publish({ reminder: value }); },
    backgroundUpdate() { publish({ updates: state.updates + 1 }); },
    command({ type, cardId, revision }) {
      if (revision !== state.revision || state.priority !== 'you') return false;
      if (type === 'pass') { publish({ priority: 'opponent', reminderMatched: false, notice: 'Priority passed once. Waiting for your opponent.' }); return true; }
      if (type === 'play' && ['melody', 'harmony'].includes(cardId) && state.cards.some(c => c.id === cardId && c.zone === 'Hand')) {
        publish({ pending: `Your ${cardId === 'melody' ? 'Melody' : 'Harmony'}`, cards: state.cards.filter(c => c.id !== cardId), scenario: 'forese', reminderMatched: state.reminder && state.foreseReady, notice: state.reminder && state.foreseReady ? 'Saved response matched. Your card has not resolved.' : 'Your card is waiting to resolve.' }); return true;
      }
      if (type === 'activate' && cardId === 'forese' && state.foreseReady) { publish({ foreseReady: false, reminderMatched: false, priority: 'opponent', pending: 'Forese activation', notice: 'Demo activation submitted. No live game was changed.' }); return true; }
      return false;
    },
    reset() { publish({ cards, foreseReady: true, reminder: true }); scenario('action'); }
  };
}
