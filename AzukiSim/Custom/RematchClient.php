<?php
// Shared by the desktop and mobile layouts. Non-match modes have no MatchId and keep the native
// end-game overlay, so tutorials/training games are unaffected.
?>
<style>
#game-over-overlay.azuki-end-game-actions #game-over-buttons {
    position: absolute;
    top: 24px;
    right: 24px;
    z-index: 3;
    display: flex !important;
    flex-wrap: nowrap !important;
    justify-content: flex-end !important;
    margin: 0 !important;
    animation: gameOverBtnSlideUp 0.6s ease-out 0.9s both;
}
#game-over-overlay.azuki-end-game-actions #game-over-buttons #game-over-menu-btn {
    position: static !important;
    top: auto !important;
    right: auto !important;
    margin: 0 !important;
    animation: none !important;
}
@media (max-width: 700px), (max-height: 520px) {
    #game-over-overlay.azuki-end-game-actions #game-over-buttons {
        top: 8px;
        right: 10px;
        gap: 6px !important;
    }
    #game-over-overlay.azuki-end-game-actions #game-over-buttons > button {
        padding: 7px 10px !important;
        font-size: 10px !important;
        line-height: 1 !important;
        white-space: nowrap;
    }
}
</style>
<script>
window.AzukiMatchId = <?php echo json_encode(class_exists('DecisionQueueController') ? strval(DecisionQueueController::GetVariable('MatchId') ?? '') : ''); ?>;
window.AzukiEndGameMode = <?php echo json_encode(class_exists('DecisionQueueController') ? strval(DecisionQueueController::GetVariable('GameMode') ?? '') : ''); ?>;
(function() {
    var isBotGame = window.AzukiEndGameMode === 'rlbot';
    if (!window.AzukiMatchId && !isBotGame) return;

    function appBase() {
        var path = location.pathname;
        var index = path.indexOf('/TCGEngine/');
        return index >= 0 ? path.slice(0, index + '/TCGEngine/'.length) : '/TCGEngine/';
    }

    var url = new URL(window.location.href);
    var playerID = url.searchParams.get('playerID') || '1';
    var authKey = url.searchParams.get('authKey') || '';
    var gameName = url.searchParams.get('gameName') || '';
    var isPlayerSeat = playerID === '1' || playerID === '2';
    var menuUrl = appBase() + 'SharedUI/Sites/AzukiSim/MainMenu.php';
    var statsHtml = '';
    var lastSignature = null;

    function mainMenuButton() {
        return {id:'game-over-menu-btn', label:'Main Menu', onClick:function() { location.href = menuUrl; }};
    }

    function playBotAgain() {
        var button = document.getElementById('azuki-play-again-btn');
        if (button) {
            button.textContent = 'Starting New Game...';
            button.disabled = true;
        }
        var body = 'gameName=' + encodeURIComponent(gameName)
            + '&playerID=' + encodeURIComponent(playerID)
            + '&authKey=' + encodeURIComponent(authKey);
        fetch(appBase() + 'AzukiSim/RestartBotGame.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: body
        }).then(function(response) {
            return response.json().then(function(payload) {
                if (!response.ok || !payload.success) throw new Error(payload.message || 'Unable to start a new game.');
                return payload;
            });
        }).then(function(payload) {
            location.href = appBase() + 'NextTurn.php?playerID=1&gameName=' + encodeURIComponent(payload.gameName)
                + '&authKey=' + encodeURIComponent(payload.authKey) + '&folderPath=AzukiSim';
        }).catch(function(error) {
            if (button) {
                button.textContent = 'Play Again';
                button.disabled = false;
            }
            if (typeof StyledAlert === 'function') StyledAlert(error.message || 'Unable to start a new game.');
        });
    }

    function renderBotOverlay(winner) {
        var existing = document.getElementById('game-over-overlay');
        if (existing && existing.remove) existing.remove();
        var buttons = [];
        if (isPlayerSeat) buttons.push({id:'azuki-play-again-btn', label:'Play Again', onClick:playBotAgain});
        buttons.push(mainMenuButton());
        if (typeof ShowGameOver === 'function') {
            ShowGameOver(parseInt(playerID, 10) === parseInt(winner, 10), menuUrl, statsHtml, buttons);
            var overlay = document.getElementById('game-over-overlay');
            if (overlay) overlay.classList.add('azuki-end-game-actions');
            if (window.GameLogClient && typeof window.GameLogClient.addGameOverButton === 'function') {
                window.GameLogClient.addGameOverButton(overlay);
            }
        }
    }

    function goToNextGame(info) {
        if (!info.nextGameName) return;
        location.href = appBase() + 'NextTurn.php?playerID=' + encodeURIComponent(playerID)
            + '&gameName=' + encodeURIComponent(info.nextGameName)
            + '&authKey=' + encodeURIComponent(authKey)
            + '&folderPath=AzukiSim';
    }

    function requestRematch() {
        var button = document.getElementById('azuki-rematch-btn');
        if (button) {
            button.textContent = 'Rematch Requested';
            button.disabled = true;
        }
        if (typeof SubmitEngineInput === 'function') {
            SubmitEngineInput('10013', '&inputText=1', {responseFormat:'json'})
                .then(function() { checkEndGame(true); })
                .catch(function() { checkEndGame(true); });
        } else {
            SubmitInput('10013', '&inputText=1');
            setTimeout(function() { checkEndGame(true); }, 500);
        }
    }

    function buttonsFor(info) {
        if (info.nextGameName) {
            return [
                {label:'Go to Rematch', onClick:function() { goToNextGame(info); }},
                mainMenuButton()
            ];
        }

        if (!isPlayerSeat) {
            return [mainMenuButton()];
        }

        var label = 'Rematch';
        var disabled = false;
        if (info.rematchRequestedByMe && !info.rematchRequestedByOpp) {
            label = 'Rematch Requested';
            disabled = true;
        } else if (info.rematchRequestedByOpp && !info.rematchRequestedByMe) {
            label = 'Accept Rematch';
        }
        return [
            {id:'azuki-rematch-btn', label:label, disabled:disabled, onClick:requestRematch},
            mainMenuButton()
        ];
    }

    function render(info) {
        var existing = document.getElementById('game-over-overlay');
        if (existing && existing.remove) existing.remove();
        if (typeof ShowGameOver === 'function') {
            ShowGameOver(!!info.didWin, menuUrl, statsHtml, buttonsFor(info));
            var overlay = document.getElementById('game-over-overlay');
            if (overlay) overlay.classList.add('azuki-end-game-actions');
            if (window.GameLogClient && typeof window.GameLogClient.addGameOverButton === 'function') {
                window.GameLogClient.addGameOverButton(overlay);
            }
        }
    }

    function checkEndGame(force) {
        return fetch(appBase() + 'AzukiSim/EndGameInfo.php?gameName=' + encodeURIComponent(gameName)
            + '&playerID=' + encodeURIComponent(playerID) + '&authKey=' + encodeURIComponent(authKey))
            .then(function(response) { return response.json(); })
            .then(function(info) {
                if (!info || !info.gameWinner) return;
                var signature = [info.nextGameName, info.rematchRequestedByMe, info.rematchRequestedByOpp].join('|');
                if (!force && signature === lastSignature) return;
                lastSignature = signature;
                render(info);
            })
            .catch(function() {});
    }

    var pollStarted = false;
    window.AzukiShowEndGameMenu = function(prebuiltStats, winner) {
        statsHtml = prebuiltStats || (typeof BuildMacroGameStatsHtml === 'function' ? BuildMacroGameStatsHtml(playerID) : '');
        if (isBotGame) {
            renderBotOverlay(winner);
            return;
        }
        checkEndGame(true);
        if (!pollStarted) {
            pollStarted = true;
            setInterval(checkEndGame, 3000);
        }
    };
})();
</script>
