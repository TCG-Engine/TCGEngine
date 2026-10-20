/* The menu owns the public queue; the embedded practice board owns only its bot game. */
function startGAQueuePractice(seat, submission, hooks) {
  var active = true;
  var practicing = false;
  var closing = false;
  var pollController = null;
  var timer = null;
  var panel = document.createElement('div');
  panel.id = 'ga-queue-practice';
  panel.style.cssText = 'position:fixed;inset:0;z-index:1000;background:#10151d;color:white;display:flex;flex-direction:column;';
  var bar = document.createElement('div');
  bar.style.cssText = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:12px;padding:12px;flex-shrink:0;';
  var status = document.createElement('span');
  status.setAttribute('role', 'status');
  status.textContent = 'Searching for an opponent…';
  var practice = document.createElement('button');
  practice.textContent = 'Practice while waiting';
  var cancel = document.createElement('button');
  cancel.textContent = 'Cancel queue';
  var hint = document.createElement('p');
  hint.style.cssText = 'text-align:center;padding:20px;';
  hint.textContent = 'Practice against a bot using a copy of your deck. When an opponent is found, you’ll automatically join your match.';
  bar.appendChild(status);
  bar.appendChild(practice);
  bar.appendChild(cancel);
  panel.appendChild(bar);
  panel.appendChild(hint);
  document.body.appendChild(panel);
  practice.focus();

  async function request(endpoint, values, signal) {
    var response = await fetch(hooks.base + 'APIs/Lobbies/' + endpoint + '.php', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(values).toString(), signal: signal
    });
    if (!response.ok) throw new Error('Connection failed. Please try again.');
    return response.json();
  }
  function credentials() {
    return { rootName: 'GrandArchiveSim', lobbyID: seat.lobbyID, playerID: seat.playerID, authKey: seat.authKey };
  }
  function stop() {
    active = false;
    clearTimeout(timer);
    if (pollController) pollController.abort();
  }
  function found(response) {
    stop();
    panel.remove(); // Unload the bot board before the human-match countdown.
    hooks.found(response.playerID, response.gameName, seat.authKey);
  }
  async function poll() {
    if (!active) return;
    pollController = new AbortController();
    try {
      var response = await request('PollLobbyUpdates', credentials(), pollController.signal);
      if (!active) return;
      if (response.success && response.ready && response.gameName) return found(response);
      if (response.gone || response.message === 'Authentication failed.') {
        stop();
        status.textContent = response.message || 'The queue has ended.';
        practice.disabled = true;
        cancel.textContent = 'Back to menu';
        cancel.onclick = function() { panel.remove(); };
        return;
      }
      status.textContent = 'Searching for an opponent…';
    } catch (error) {
      if (!active) return;
      status.textContent = 'Reconnecting to matchmaking…';
    }
    timer = setTimeout(poll, 1500);
  }
  practice.onclick = async function() {
    if (!active || practicing || closing) return;
    practicing = true;
    practice.disabled = true;
    hint.textContent = 'Starting practice…';
    try {
      var response = await request('JoinQueue', {
        rootName: 'GrandArchiveSim', format: 'bot', queueType: 'bo1', botPlayers: '2',
        deckLink: submission.deckLink, preconstructedDeck: submission.preconstructedDeck || '',
        game_type: submission.gameType || '',
        shareAnonymizedGameplayData: submission.shareAnonymizedGameplayData ? '1' : '0'
      });
      // A human match or cancellation may have won the race with bot creation.
      if (!active) return;
      if (!response.success || !response.ready || !response.gameName) throw new Error(response.message || 'Unable to start practice.');
      var frame = document.createElement('iframe');
      frame.title = 'Grand Archive bot practice';
      frame.style.cssText = 'display:block;width:100%;flex:1 1 0;min-height:0;border:0;background:#10151d;';
      frame.src = hooks.gameUrl(response.playerID, response.gameName, response.authKey, false);
      hint.remove();
      practice.remove();
      panel.appendChild(frame);
    } catch (error) {
      if (!active) return;
      practicing = false;
      practice.disabled = false;
      hint.textContent = error.message + ' You are still queued.';
    }
  };
  cancel.onclick = async function() {
    if (!active || closing) return;
    closing = true;
    cancel.disabled = true;
    try {
      var response = await request('LeaveQueue', Object.assign(credentials(), { waitingOnly: '1' }));
      if (!active) return;
      if (!response.success) throw new Error(response.message || 'Unable to cancel queue.');
      stop();
      panel.remove();
    } catch (error) {
      if (!active) return;
      closing = false;
      cancel.disabled = false;
      status.textContent = error.message;
    }
  };
  poll();
}
