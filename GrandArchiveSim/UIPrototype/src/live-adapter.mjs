// Accept only a viewer-filtered server projection, never a raw engine gamestate.
// Used by the opt-in live preview; the simulated demo remains separate.
export function createLiveAdapter({ submit, refresh }) {
  let snapshot = Object.freeze({ revision: null, commands: [], pending: false, error: null, notice: null });
  const listeners = new Set();
  let inFlight = false;
  let awaitingState = false;
  let requestSequence = 0;
  const publish = patch => {
    snapshot = Object.freeze({ ...snapshot, ...patch });
    listeners.forEach(fn => fn());
  };
  const receive = (projection, allowEqual = false, error = null) => {
    if (!Number.isSafeInteger(projection?.revision) || projection.revision < 0 || !Array.isArray(projection.commands)) return false;
    if (snapshot.revision !== null && (projection.revision < snapshot.revision || (!allowEqual && projection.revision === snapshot.revision))) return false;
    const commands = projection.commands.map(command => {
      if (typeof command.id !== 'string' || typeof command.label !== 'string' || typeof command.mode !== 'string' || typeof command.params !== 'string') throw new TypeError('Invalid command projection');
      return Object.freeze({ id: command.id, label: command.label, mode: command.mode, params: command.params });
    });
    awaitingState = false;
    publish({ revision: projection.revision, commands: Object.freeze(commands), pending: inFlight, error });
    return true;
  };
  return {
    getSnapshot: () => snapshot,
    subscribe(fn) { listeners.add(fn); return () => listeners.delete(fn); },
    receive: projection => receive(projection),
    async command(id, revision, answer) {
      if (inFlight || awaitingState || revision !== snapshot.revision) return false;
      const command = snapshot.commands.find(item => item.id === id);
      if (!command) return false;
      const request = ++requestSequence;
      inFlight = true;
      awaitingState = true;
      publish({ pending: true, error: null, notice: null });
      let accepted = false;
      let refused = false;
      try {
        const result = await submit(command.mode, command.params + '&expectedRevision=' + encodeURIComponent(revision) + (id === 'answer-decision' ? '&answer=' + encodeURIComponent(JSON.stringify(answer)) : ''), { responseFormat: 'json' });
        accepted = result?.success === true;
        if (accepted && result?.message) publish({ notice: String(result.message) });
        refused = result?.success === false;
        if (!accepted) publish({ error: String(result?.message || 'Action refused. Refreshing the match.') });
      } catch {
        // An interrupted response does not prove the server rejected the action.
        // Never retry automatically: wait for a newer authoritative snapshot.
        publish({ error: 'Connection interrupted. Refresh the match before trying again.' });
      } finally {
        inFlight = false;
        awaitingState = true;
        publish({ pending: true });
        try {
          // Only this post-response read can acknowledge an unchanged refusal.
          // Polls and ambiguous transport outcomes still require a newer revision.
          const projection = await refresh();
          if (projection && request === requestSequence) receive(projection, refused, snapshot.error);
        } catch {
          if (request === requestSequence) publish({ error: 'Match refresh failed. Reconnect before acting.' });
        }
      }
      return accepted;
    }
  };
}
