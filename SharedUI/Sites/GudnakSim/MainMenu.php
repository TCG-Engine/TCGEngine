<?php
include_once './MenuBar.php';
include_once '../../../AccountFiles/AccountSessionAPI.php';
include_once '../../../Database/ConnectionManager.php';
include_once '../../../RBDeck/GeneratedCode/GeneratedCardDictionaries.php';

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
      <label for="preconstructed-deck" style="display: block; margin-bottom: 8px; font-weight: 500;">Choose Your Deck:</label>
      <select id="preconstructed-deck" name="preconstructed_deck" required style="
        width: 100%;
        padding: 10px 15px;
        background-color: rgba(40, 40, 40, 0.95);
        color: white;
        border: 2px solid rgba(100, 100, 100, 0.5);
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        outline: none;
      " onmouseover="this.style.borderColor='rgba(52, 152, 219, 0.8)'; this.style.backgroundColor='rgba(50, 50, 50, 0.95)';" onmouseout="this.style.borderColor='rgba(100, 100, 100, 0.5)'; this.style.backgroundColor='rgba(40, 40, 40, 0.95)';" onfocus="this.style.borderColor='#3498db'; this.style.boxShadow='0 0 8px rgba(52, 152, 219, 0.4)';" onblur="this.style.borderColor='rgba(100, 100, 100, 0.5)'; this.style.boxShadow='none';">
        <option value="" disabled selected style="color: #999;">Select a preconstructed deck...</option>
        <option value="Refractory">Refractory</option>
      </select>
      <label for="deck-link" style="display: none;">Deck Link:</label>
      <input type="text" id="deck-link" name="deck_link" style="display: none;">
      <!--
      <label for="game-name">Game Name:</label>
      <input type="text" id="game-name" name="game_name" required>
      <br>
      <label for="game-type">Game Type:</label>
      <select id="game-type" name="game_type">
      <option value="casual">Casual</option>
      <option value="ranked">Ranked</option>
      </select>
    -->
      <br>
      <button onclick="joinQueue()">Join Queue</button>
    </div>
  </div>
  
  <!-- News Section -->
  <div class="card" style="flex-grow: 1; margin: 10px; padding: 20px; background-color: rgba(51, 51, 51, 0.9); color: white; border-radius: 10px;">
    <h2>Welcome to Gudnak Simulator!</h2>
    <p class="login-message">Gudnak Simulator is a fan-made online simulator for the Gudnak expandable card game.</p>
    <p class="login-message">Build your deck, challenge other players, and master the game. Join our community on Discord for feedback and updates!</p>
  </div>
</div>

<script>

  var rootName = "GudnakSim";
      var _lobby_id = "";

      function joinQueue() {
        var preconstructedDeck = document.getElementById('preconstructed-deck').value;
        if (!preconstructedDeck) {
          alert('Please select a preconstructed deck.');
          return;
        }
        var deckLink = document.getElementById('deck-link').value; // Keep for future
        var gameName = 'Quick Match'; // Default game name since input is commented out
        var gameType = 'casual'; // Default game type since select is commented out

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../../APIs/Lobbies/JoinQueue.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            console.log('Successfully joined queue:', xhr.responseText);
            var response = JSON.parse(xhr.responseText);
            if(response.ready) {
              DisplayMatchFoundPopup(response.playerID, response.gameName);
            } else {
              _lobby_id = response.lobbyID;
              DisplayWaitingPopup("Waiting for opponent... (Esc to cancel)", response.playerID, response.authKey);
              // Start polling for lobby updates
              pollLobbyUpdates(response.playerID, response.authKey);
            }
          } else {
            console.error('Error joining queue:', xhr.statusText);
          }
        };

        xhr.onerror = function() {
          console.error('Error joining queue:', xhr.statusText);
        };

        var params = 'deckLink=' + encodeURIComponent(deckLink) + '&game_type=' + encodeURIComponent(gameType);
        params += '&preconstructedDeck=' + encodeURIComponent(preconstructedDeck);
        params += "&rootName=" + encodeURIComponent(rootName);
        xhr.send(params);
      }
      function DisplayWaitingPopup(message, playerID, authKey) {
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
        document.body.appendChild(waitingPopup);

        // Add event listener for Escape key
        document.addEventListener('keydown', function handleEscapeKey(event) {
          if (event.key === 'Escape') {
            document.body.removeChild(waitingPopup);
            document.removeEventListener('keydown', handleEscapeKey);

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
        });
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
                    // Redirect with fade parameter
                    window.location.href = `../../../NextTurn.php?playerID=${playerID}&gameName=${gameName}&folderPath=${encodeURIComponent(rootName)}&fromMatch=1`;
                  }, 400);
                }
              }, 400);
            }, 500);
          }
        }
        updateCountdown();
      }

      function refreshOpenGames() {
        console.log('Refreshing open games');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../../../APIs/Lobbies/GetLobbies.php', true);
        xhr.responseType = 'json';

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
          var data = xhr.response;
          var openGamesList = document.getElementById('open-games-list');
          var gameCountElement = document.getElementById('active-game-count');
          
          if (data.data && Array.isArray(data.data)) {
            gameCountElement.textContent = data.data.length;
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
          var openGamesList = document.getElementById('open-games-list');
          gameCountElement.textContent = '0';
          openGamesList.innerHTML = '<p style="color: #999;">Failed to load open games.</p>';
          }
        };

        xhr.onerror = function() {
          console.error('Error fetching open games:', xhr.statusText);
          var openGamesList = document.getElementById('open-games-list');
          var gameCountElement = document.getElementById('active-game-count');
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
    </script>

<?php
include_once './Disclaimer.php';
?>