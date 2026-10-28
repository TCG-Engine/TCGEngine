// Read-only polling: credentials stay in this closure, never URLs or browser storage.
export function createLiveViewStore({ request, schedule = setTimeout, cancel = clearTimeout }) {
 let state = { status: 'disconnected', match: null }, generation = 0, timer, controller;
 const listeners = new Set();
 const publish = patch => { state = Object.freeze({ ...state, ...patch }); listeners.forEach(fn => fn()); };
 function stop() { generation++; cancel(timer); controller?.abort(); }
 return {
  getSnapshot: () => state,
  subscribe(fn) { listeners.add(fn); return () => listeners.delete(fn); },
  disconnect() { stop(); publish({ status: 'disconnected', match: null }); },
  connect(credentials) {
   stop(); const run = generation;
   publish({ status: 'connecting', match: null });
   async function poll() {
    const currentController = new AbortController();
    controller = currentController;
    const timeout = schedule(() => currentController.abort(), 10000);
    try {
     const match = await request(credentials, currentController.signal);
     if (run !== generation) return;
     if (!Number.isSafeInteger(match?.revision) || !Array.isArray(match.players) || ![null, 1, 2].includes(match.viewerSeat)) throw new Error('Invalid match');
     if (state.match && match.revision < state.match.revision) throw new Error('Older match');
     if (!state.match || match.revision !== state.match.revision) publish({ status: 'connected', match });
     timer = schedule(poll, 2000);
    } catch {
     if (run === generation) {
      // Clear private cards on every failure, including revoked access. Manual reconnect.
      publish({ status: 'error', match: null });
     }
    } finally { cancel(timeout); }
   }
   void poll();
  }
 };
}
export async function requestLiveMatch(credentials, signal) {
 const response = await fetch(new URL('../UIState.php', window.location.href), {
  method: 'POST', body: new URLSearchParams(credentials), signal,
  cache: 'no-store', credentials: 'same-origin', redirect: 'error'
 });
 if (!response.ok) throw new Error('Match unavailable');
 return response.json();
}
