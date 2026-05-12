<?php
include_once './MenuBar.php';
include_once '../../../AccountFiles/AccountSessionAPI.php';
include_once '../../../Database/ConnectionManager.php';
include_once '../../../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';

include_once 'Header.php';

?>
<div class="row-wrapper" style="display: flex; flex-direction: row; flex-grow: 1;">
  <div class="card" style="flex-grow: 1; margin: 10px; padding: 20px; background-color: rgba(51, 51, 51, 0.9); color: white; border-radius: 10px; position: relative;">
    <button style="position: absolute; top: 10px; right: 10px; background: none; border: none; cursor: pointer;" onclick="refreshOpenGames()">
      <img src='../../../Assets/Icons/refresh.svg' width='16' height='16' alt='Refresh' style='filter: invert(100%);' />
    </button>
  <!-- Open Games Section -->
    <h2>Active Games (<span id="active-game-count">0</span>)</h2>
    <ul style="list-style-type: none; padding: 0; display: flex; flex-direction: column;">
      <!-- List of open games will be dynamically populated here -->
      <div id="open-games-list" style="max-height: 400px; overflow-y: auto;">
        <p style="color: #999;">Loading games...</p>
      </div>
    </ul>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        refreshOpenGames();
      });
    </script>
  </div>
  
  <!-- Create New Game Section -->
  <div class="card" style="flex-grow: 1; margin: 10px; padding: 20px; background-color: rgba(51, 51, 51, 0.9); color: white; border-radius: 10px;">
    <h2>Create a New Game</h2>
    <div>
      <p style="color: #ccc; margin: 0 0 8px 0; font-size: 14px;">Choose your starter deck:</p>
      <select id="starter-deck-select" style="margin-bottom: 12px; min-width: 220px;">
        <option value="Raizan">Raizan Starter Deck</option>
        <option value="Shao">Shao Starter Deck</option>
      </select>
      <br>
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="joinQueue()">Join Queue</button>
        <button onclick="createPrivateGame()" style="background-color: #2f6f9f;">Create Private Game</button>
        <button id="join-private-invite-btn" onclick="joinPrivateInvite()" style="display: none; background-color: #2d8a57;">Join Private Invite</button>
      </div>
      <div id="queue-inline-error" style="display: none; margin-top: 10px; color: #ff6b6b; font-size: 13px; line-height: 1.35;"></div>
      <div id="private-invite-notice" style="display: none; margin-top: 10px; color: #9ed9b4; font-size: 13px;"></div>
    </div>
  </div>
  
  <!-- Tips & Info Section -->
  <div class="card" style="flex-grow: 1; margin: 10px; padding: 20px; background-color: rgba(51, 51, 51, 0.9); color: white; border-radius: 10px; display: flex; flex-direction: column; gap: 16px;">
    <h2 style="margin: 0 0 4px 0;">Welcome to Azuki TCG Simulator!</h2>
    <p class="login-message" style="margin: 0; color: #ccc; font-size: 14px;">A fan-made online simulator for the Azuki TCG.</p>

    <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 0;">

    <!-- Did you know? -->
    <div id="did-you-know-box" style="
      background: linear-gradient(135deg, rgba(52,152,219,0.15) 0%, rgba(30,30,50,0.4) 100%);
      border: 1px solid rgba(52,152,219,0.35);
      border-radius: 8px;
      padding: 14px 16px;
      position: relative;
    ">
      <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <span style="font-size: 18px;">💡</span>
        <span style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #3498db;">Did you know?</span>
      </div>
      <p id="did-you-know-text" style="margin: 0; font-size: 14px; color: #e8e8e8; line-height: 1.55;"></p>
      <button onclick="cycleDidYouKnow()" title="Next tip" style="
        position: absolute; top: 10px; right: 10px;
        background: none; border: none; cursor: pointer;
        color: #3498db; font-size: 16px; padding: 2px 6px; border-radius: 4px;
        transition: background 0.2s;
      " onmouseover="this.style.background='rgba(52,152,219,0.15)'" onmouseout="this.style.background='none'">→</button>
    </div>

    <!-- Quick-reference hotkeys -->
    <div>
      <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #888; margin-bottom: 8px;">Quick Reference</div>
      <div style="display: flex; flex-direction: column; gap: 6px;" id="hotkey-list"></div>
    </div>
  </div>
</div>

<style>
  .row-wrapper > .card {
    flex: 1 1 0 !important;
    min-width: 0;
  }
  .hotkey-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #ccc; }
  .hotkey-badge {
    display: inline-block; min-width: 28px; text-align: center;
    padding: 2px 7px; border-radius: 5px;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2);
    font-family: monospace; font-size: 13px; font-weight: 700; color: #fff;
    flex-shrink: 0;
  }
  #did-you-know-box {
    transition: opacity 0.25s;
    min-height: 140px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }
  #did-you-know-text {
    display: block;
    width: 100%;
    max-width: 100%;
    min-height: 66px;
    max-height: 66px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 4px;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
  }
</style>

<script>
  var _didYouKnowTips = [
    { key: 'u', label: 'Undo your most recent action' },
    { text: 'Hover a card on the field to see its full text' },
    { text: 'You can queue with either the Raizan or Shao starter deck.' },
    { text: 'Private games generate a shareable invite link — send it to your opponent and they can join instantly.' },
    { text: 'The queue matches you with the first available opponent. No need to refresh — it polls automatically.' },
    { key: 'Esc', label: 'Cancel matchmaking while waiting for an opponent' },
  ];
  var _dykIndex = 0;

  var _hotkeyList = [
    { key: 'u',   label: 'Undo most recent action' },
    { key: 'Esc', label: 'Cancel matchmaking' },
  ];

  function renderDidYouKnow() {
    var tip = _didYouKnowTips[_dykIndex];
    var el = document.getElementById('did-you-know-text');
    if (!el) return;
    var box = document.getElementById('did-you-know-box');
    box.style.opacity = '0';
    setTimeout(function() {
      if (tip.key) {
        el.innerHTML = 'Press <span class="hotkey-badge">' + tip.key + '</span> to <strong>' + tip.label + '</strong>.';
      } else {
        el.textContent = tip.text;
      }
      box.style.opacity = '1';
    }, 200);
  }

  function cycleDidYouKnow() {
    _dykIndex = (_dykIndex + 1) % _didYouKnowTips.length;
    renderDidYouKnow();
  }

  function renderHotkeyList() {
    var container = document.getElementById('hotkey-list');
    if (!container) return;
    var html = '';
    _hotkeyList.forEach(function(h) {
      html += '<div class="hotkey-row"><span class="hotkey-badge">' + h.key + '</span><span>' + h.label + '</span></div>';
    });
    container.innerHTML = html;
  }

  document.addEventListener('DOMContentLoaded', function() {
    renderDidYouKnow();
    renderHotkeyList();
    // Rotate tips every 8 seconds
    setInterval(cycleDidYouKnow, 8000);
  });
</script>

<script>

  var rootName = "AzukiSim";
  var _lobby_id = "";
  var _privateInviteCode = "";
  var _waitingEscHandler = null;

      function switchDeckTab(tab) {
        var isLink = tab === 'link';
        document.getElementById('deck-input-link').style.display = isLink ? '' : 'none';
        document.getElementById('deck-input-text').style.display = isLink ? 'none' : '';
        document.getElementById('tab-link').style.background = isLink ? 'rgba(52,152,219,0.25)' : 'rgba(40,40,40,0.7)';
        document.getElementById('tab-link').style.color = isLink ? 'white' : '#aaa';
        document.getElementById('tab-link').style.borderBottom = isLink ? '2px solid #3498db' : '2px solid transparent';
        document.getElementById('tab-text').style.background = isLink ? 'rgba(40,40,40,0.7)' : 'rgba(52,152,219,0.25)';
        document.getElementById('tab-text').style.color = isLink ? '#aaa' : 'white';
        document.getElementById('tab-text').style.borderBottom = isLink ? '2px solid transparent' : '2px solid #3498db';
        try { localStorage.setItem('ga_deck_tab', tab); } catch(e) {}
      }

      (function() {
        var saved = '';
        try { saved = localStorage.getItem('ga_deck_tab') || ''; } catch(e) {}
        if (saved === 'text') switchDeckTab('text');
      })();

      function initializePrivateInviteFromUrl() {
        try {
          var params = new URLSearchParams(window.location.search || '');
          _privateInviteCode = (params.get('privateInvite') || params.get('invite') || '').trim();
          if (!_privateInviteCode) return;

          var joinBtn = document.getElementById('join-private-invite-btn');
          var notice = document.getElementById('private-invite-notice');
          if (joinBtn) joinBtn.style.display = '';
          if (notice) {
            notice.style.display = '';
            notice.textContent = 'Private invite detected. Choose your deck, then click Join Private Invite.';
          }
        } catch (e) {
          console.error('Failed to parse private invite URL:', e);
        }
      }

      function getDeckSubmission() {
        var starterDeck = 'Raizan';
        var starterSelect = document.getElementById('starter-deck-select');
        if (starterSelect && starterSelect.value) {
          starterDeck = starterSelect.value;
        }

        var gameType = 'casual';
        return {
          preconstructedDeck: starterDeck,
          deckLink: '',
          gameType: gameType
        };
      }

      function buildPrivateInviteLink(inviteCode) {
        var url = new URL(window.location.href);
        url.searchParams.set('privateInvite', inviteCode);
        return url.toString();
      }

      function joinQueue() {
        submitQueueJoin({
          waitingMessage: 'Waiting for opponent... (Esc to cancel)'
        });
      }

      function createPrivateGame() {
        submitQueueJoin({
          createPrivate: true,
          waitingMessage: 'Waiting for invited opponent... (Esc to cancel)'
        });
      }

      function joinPrivateInvite() {
        if (!_privateInviteCode) {
          showQueueInlineError('No private invite code found in this link.');
          return;
        }
        submitQueueJoin({
          privateInviteCode: _privateInviteCode,
          waitingMessage: 'Waiting for host to start... (Esc to cancel)'
        });
      }

      function submitQueueJoin(options) {
        options = options || {};
        clearQueueInlineError();
        var submission = getDeckSubmission();
        if (!submission) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../../APIs/Lobbies/JoinQueue.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            console.log('Successfully joined queue:', xhr.responseText);
            var response;
            try {
              response = JSON.parse(xhr.responseText);
            } catch (e) {
              var raw = (xhr.responseText || '').trim();
              var preview = raw.length > 240 ? raw.slice(0, 240) + '...' : raw;
              showQueueInlineError('Unexpected server response while joining queue. ' + preview);
              return;
            }
            if (!response.success) {
              showQueueInlineError(response.message || 'Unable to join queue.');
              return;
            }
            clearQueueInlineError();
            if(response.ready) {
              DisplayMatchFoundPopup(response.playerID, response.gameName);
            } else {
              _lobby_id = response.lobbyID;
              var inviteLink = '';
              if (response.inviteCode) {
                inviteLink = buildPrivateInviteLink(response.inviteCode);
              }
              DisplayWaitingPopup(options.waitingMessage || 'Waiting for opponent... (Esc to cancel)', response.playerID, response.authKey, inviteLink);
              // Start polling for lobby updates
              pollLobbyUpdates(response.playerID, response.authKey);
            }
          } else {
            console.error('Error joining queue:', xhr.statusText);
            showQueueInlineError('Failed to join queue. Please try again.');
          }
        };

        xhr.onerror = function() {
          console.error('Error joining queue:', xhr.statusText);
          showQueueInlineError('Failed to join queue. Please try again.');
        };

        var params = 'deckLink=' + encodeURIComponent(submission.deckLink) + '&game_type=' + encodeURIComponent(submission.gameType);
        params += '&preconstructedDeck=' + encodeURIComponent(submission.preconstructedDeck);
        params += "&rootName=" + encodeURIComponent(rootName);
        if (options.createPrivate) {
          params += '&createPrivate=1';
        }
        if (options.privateInviteCode) {
          params += '&privateInviteCode=' + encodeURIComponent(options.privateInviteCode);
        }
        xhr.send(params);
      }

      function showQueueInlineError(message) {
        var el = document.getElementById('queue-inline-error');
        if (!el) {
          alert(message);
          return;
        }
        el.textContent = message || 'Unable to join queue.';
        el.style.display = '';
      }

      function clearQueueInlineError() {
        var el = document.getElementById('queue-inline-error');
        if (!el) return;
        el.textContent = '';
        el.style.display = 'none';
      }

      function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          return navigator.clipboard.writeText(text);
        }
        return new Promise(function(resolve, reject) {
          try {
            var tempInput = document.createElement('textarea');
            tempInput.value = text;
            tempInput.style.position = 'fixed';
            tempInput.style.opacity = '0';
            document.body.appendChild(tempInput);
            tempInput.focus();
            tempInput.select();
            var ok = document.execCommand('copy');
            document.body.removeChild(tempInput);
            if (ok) resolve();
            else reject(new Error('copy_failed'));
          } catch (err) {
            reject(err);
          }
        });
      }

      function DisplayWaitingPopup(message, playerID, authKey, inviteLink) {
        var existingWaitingPopup = document.getElementById('waiting-popup');
        if (existingWaitingPopup) existingWaitingPopup.remove();
        if (_waitingEscHandler) {
          document.removeEventListener('keydown', _waitingEscHandler);
          _waitingEscHandler = null;
        }

        var waitingPopup = document.createElement('div');
        waitingPopup.id = 'waiting-popup';
        waitingPopup.style.position = 'fixed';
        waitingPopup.style.top = '0';
        waitingPopup.style.left = '0';
        waitingPopup.style.width = '100%';
        waitingPopup.style.height = '100%';
        waitingPopup.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        waitingPopup.style.display = 'flex';
        waitingPopup.style.flexDirection = 'column';
        waitingPopup.style.justifyContent = 'center';
        waitingPopup.style.alignItems = 'center';
        waitingPopup.style.zIndex = '1000';

        var animation = document.createElement('div');
        animation.style.border = '16px solid #f3f3f3';
        animation.style.borderTop = '16px solid #3498db';
        animation.style.borderRadius = '50%';
        animation.style.width = '120px';
        animation.style.height = '120px';
        animation.style.animation = 'spin 2s linear infinite';

        var style = document.createElement('style');
        style.textContent = `
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `;
        document.head.appendChild(style);

        var messageElement = document.createElement('p');
        messageElement.textContent = message;
        messageElement.style.color = 'white';
        messageElement.style.marginTop = '20px';
        messageElement.style.fontSize = '18px';
        messageElement.style.textAlign = 'center';
        messageElement.style.fontStyle = 'italic';

        waitingPopup.appendChild(animation);
        waitingPopup.appendChild(messageElement);

        if (inviteLink) {
          var inviteHint = document.createElement('p');
          inviteHint.textContent = 'Share this invite link with your opponent:';
          inviteHint.style.color = '#d8d8d8';
          inviteHint.style.marginTop = '14px';
          inviteHint.style.marginBottom = '8px';
          inviteHint.style.fontSize = '14px';
          waitingPopup.appendChild(inviteHint);

          var linkPreview = document.createElement('div');
          linkPreview.textContent = inviteLink;
          linkPreview.style.maxWidth = '680px';
          linkPreview.style.wordBreak = 'break-all';
          linkPreview.style.color = '#9ed9b4';
          linkPreview.style.fontSize = '12px';
          linkPreview.style.marginBottom = '10px';
          linkPreview.style.padding = '8px 10px';
          linkPreview.style.border = '1px solid rgba(255,255,255,0.15)';
          linkPreview.style.borderRadius = '6px';
          linkPreview.style.backgroundColor = 'rgba(0,0,0,0.28)';
          waitingPopup.appendChild(linkPreview);

          var copyButton = document.createElement('button');
          copyButton.textContent = 'Copy Invite Link';
          copyButton.style.backgroundColor = '#2d8a57';
          copyButton.onclick = function() {
            copyTextToClipboard(inviteLink)
              .then(function() {
                copyButton.textContent = 'Copied!';
                setTimeout(function() {
                  copyButton.textContent = 'Copy Invite Link';
                }, 1200);
              })
              .catch(function() {
                alert('Unable to copy automatically. Please copy the invite link manually.');
              });
          };
          waitingPopup.appendChild(copyButton);
        }

        document.body.appendChild(waitingPopup);

        // Add event listener for Escape key
        _waitingEscHandler = function handleEscapeKey(event) {
          if (event.key === 'Escape') {
            document.body.removeChild(waitingPopup);
            document.removeEventListener('keydown', _waitingEscHandler);
            _waitingEscHandler = null;

            // Send a message to the server to cancel the queue
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../../../APIs/Lobbies/LeaveQueue.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
              if (xhr.status >= 200 && xhr.status < 300) {
              console.log('Queue canceled successfully:', xhr.responseText);
              } else {
              console.error('Error canceling queue:', xhr.statusText);
              }
            };

            xhr.onerror = function() {
              console.error('Error canceling queue:', xhr.statusText);
            };

            var params = 'rootName=' + encodeURIComponent(rootName) + '&playerID=' + encodeURIComponent(playerID) + '&lobbyID=' + encodeURIComponent(_lobby_id) + '&authKey=' + encodeURIComponent(authKey);
            xhr.send(params);
            }
        };
        document.addEventListener('keydown', _waitingEscHandler);
      }

      function DisplayMatchFoundPopup(playerID, gameName) {
        var matchPopup = document.createElement('div');
        matchPopup.id = 'match-found-popup';
        matchPopup.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.9);
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          z-index: 1000;
          animation: fadeInPopup 0.3s ease-out;
        `;

        var style = document.createElement('style');
        style.textContent = `
          @keyframes fadeInPopup {
            from { opacity: 0; }
            to { opacity: 1; }
          }
          @keyframes pulseGlow {
            0%, 100% { text-shadow: 0 0 20px rgba(52, 152, 219, 0.8), 0 0 40px rgba(52, 152, 219, 0.4); }
            50% { text-shadow: 0 0 30px rgba(52, 152, 219, 1), 0 0 60px rgba(52, 152, 219, 0.6); }
          }
          @keyframes countdownPop {
            0% { transform: scale(1.5); opacity: 0; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
          }
          @keyframes countdownFade {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0.8); opacity: 0; }
          }
        `;
        document.head.appendChild(style);

        var titleElement = document.createElement('h1');
        titleElement.textContent = '⚔️ Match Found!';
        titleElement.style.cssText = `
          color: #3498db;
          font-size: 48px;
          margin-bottom: 30px;
          font-family: 'Roboto', sans-serif;
          animation: pulseGlow 1.5s ease-in-out infinite;
        `;

        var subtitleElement = document.createElement('p');
        subtitleElement.textContent = 'Joining in...';
        subtitleElement.style.cssText = `
          color: #ccc;
          font-size: 20px;
          margin-bottom: 20px;
          font-family: 'Roboto', sans-serif;
        `;

        var countdownElement = document.createElement('div');
        countdownElement.id = 'countdown-number';
        countdownElement.style.cssText = `
          color: white;
          font-size: 120px;
          font-weight: bold;
          font-family: 'Roboto', sans-serif;
          min-height: 150px;
          display: flex;
          align-items: center;
          justify-content: center;
        `;

        matchPopup.appendChild(titleElement);
        matchPopup.appendChild(subtitleElement);
        matchPopup.appendChild(countdownElement);
        document.body.appendChild(matchPopup);

        // Animated countdown
        var count = 3;
        function updateCountdown() {
          countdownElement.textContent = count;
          countdownElement.style.animation = 'none';
          countdownElement.offsetHeight; // Trigger reflow
          countdownElement.style.animation = 'countdownPop 0.5s ease-out forwards';
          
          if (count > 0) {
            setTimeout(function() {
              countdownElement.style.animation = 'countdownFade 0.4s ease-in forwards';
              setTimeout(function() {
                count--;
                if (count > 0) {
                  updateCountdown();
                } else {
                  countdownElement.textContent = 'GO!';
                  countdownElement.style.color = '#2ecc71';
                  countdownElement.style.animation = 'countdownPop 0.3s ease-out forwards';
                  setTimeout(function() {
                    // Remove the popup before redirecting
                    if (matchPopup && matchPopup.parentNode) {
                      matchPopup.parentNode.removeChild(matchPopup);
                    }
                    // Redirect with fade parameter
                    window.location.href = `../../../NextTurn.php?playerID=${playerID}&gameName=${gameName}&folderPath=${encodeURIComponent(rootName)}&fromMatch=1`;
                  }, 400);
                }
              }, 400);
            }, 500);
          }
        }
        updateCountdown();
        
        // Also clean up any existing match found popups on page load to handle browser back button
        window.addEventListener('pageshow', function(event) {
          if (event.persisted) {
            var existingPopup = document.getElementById('match-found-popup');
            if (existingPopup) {
              existingPopup.remove();
            }
          }
        });
      }

      function refreshOpenGames() {
        console.log('Refreshing open games');
        var openGamesList = document.getElementById('open-games-list');
        var gameCountElement = document.getElementById('active-game-count');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../../../APIs/Lobbies/GetLobbies.php?rootName=' + encodeURIComponent(rootName), true);
        xhr.responseType = 'json';

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
          var data = xhr.response;
          
          if (data.data && Array.isArray(data.data)) {
            var totalCount = (typeof data.totalCount === 'number') ? data.totalCount : data.data.length;
            gameCountElement.textContent = totalCount;
            if (data.data.length === 0) {
              openGamesList.innerHTML = '<p style="color: #999;">No active games. Create one to get started!</p>';
            } else {
              var html = '';
              data.data.forEach(function(game, index) {
                html += '<div style="padding: 8px; border-bottom: 1px solid #444; display: flex; justify-content: space-between;">';
                html += '<span>' + (game.gameName || 'Game ' + (index + 1)) + '</span>';
                html += '<span style="color: #aaa; font-size: 0.9em;">Waiting for opponent...</span>';
                html += '</div>';
              });
              openGamesList.innerHTML = html;
            }
          } else {
            gameCountElement.textContent = '0';
            openGamesList.innerHTML = '<p style="color: #999;">Unable to load games.</p>';
          }
          } else {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
          openGamesList.innerHTML = '<p style="color: #999;">Failed to load open games.</p>';
          }
        };

        xhr.onerror = function() {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
          openGamesList.innerHTML = '<p style="color: #999;">Failed to load open games.</p>';
        };

        xhr.send();
      }
      function pollLobbyUpdates(playerID, authKey) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../../APIs/Lobbies/PollLobbyUpdates.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            var response = JSON.parse(xhr.responseText);
            if (response.ready) {
              // Close waiting popup and show match found popup
              var waitingPopup = document.getElementById('waiting-popup');
              if (waitingPopup) waitingPopup.remove();
              if (_waitingEscHandler) {
                document.removeEventListener('keydown', _waitingEscHandler);
                _waitingEscHandler = null;
              }
              DisplayMatchFoundPopup(response.playerID, response.gameName);
            } else {
              // Continue polling if the lobby is not ready
              pollLobbyUpdates(playerID, authKey);
            }
          } else {
            console.error('Error polling lobby updates:', xhr.statusText);
          }
        };

        xhr.onerror = function() {
          console.error('Error polling lobby updates:', xhr.statusText);
          // Retry polling after a delay in case of an error
          setTimeout(function() {
            pollLobbyUpdates(playerID, authKey);
          }, 5000);
        };

        var params = 'rootName=' + encodeURIComponent(rootName) + 
                     '&playerID=' + encodeURIComponent(playerID) + 
                     '&lobbyID=' + encodeURIComponent(_lobby_id) + 
                     '&authKey=' + encodeURIComponent(authKey);
        xhr.send(params);
      }

      document.addEventListener('DOMContentLoaded', function() {
        initializePrivateInviteFromUrl();
      });
    </script>

<?php
include_once './Disclaimer.php';
?>
