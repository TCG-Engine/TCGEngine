<style>
#game-over-overlay.fab-game-over{position:fixed;inset:0;z-index:10000;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;padding:28px;box-sizing:border-box;overflow:auto;background:radial-gradient(ellipse at top,rgba(78,56,29,.96),rgba(8,15,19,.98) 70%);color:#f3eee5;text-align:center}
.fab-game-over #game-over-title{font-size:clamp(36px,7vw,80px);font-weight:800;letter-spacing:-.04em;color:#e5bf70}
.fab-game-over #game-over-buttons button,#fab-game-result{padding:12px 20px;border:1px solid #ac8745;border-radius:8px;background:#283437;color:#f3eee5;font:600 15px system-ui;cursor:pointer}
.fab-game-over #game-over-buttons button:first-child{background:#b7944c;color:#121a1c}
.fab-game-over button:focus-visible,#fab-game-result:focus-visible{outline:3px solid #f0cc80;outline-offset:4px}
#fab-game-result{position:fixed;top:8px;left:50%;transform:translateX(-50%);z-index:500;padding:7px 14px}
#fab-game-result[hidden]{display:none}
</style>
<button id="fab-game-result" type="button" hidden>View game result</button>
<script>
(function() {
  var shownWinner = 0;
  var menu = './SharedUI/Sites/FaBSim/MainMenu.php';
  function showResult() {
    var winner = Number(window.WinnerData);
    if (!(winner > 0) || typeof ShowGameOver !== 'function') return;
    var input = document.getElementById('playerID');
    var viewer = Number(input && input.value);
    var seats = String(window.SeatOrderData || '12').match(/[1-4]/g) || [];
    var spectator = !seats.includes(String(viewer));
    var subtitle = 'Player ' + winner + ' wins · ' + (seats.length > 2 ? 'Ultimate Pit Fight' : '1v1');
    ShowGameOver(viewer === winner, menu, '', [
      {label: 'Return to menu', onClick: function() { window.location.href = menu; }},
      {label: 'Review final board', onClick: function() {
        var overlay = document.getElementById('game-over-overlay');
        if (overlay) overlay.remove();
        document.getElementById('fab-game-result').focus();
      }}
    ], subtitle);
    var overlay = document.getElementById('game-over-overlay');
    if (!overlay) return;
    overlay.classList.add('fab-game-over');
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'game-over-title');
    if (spectator) document.getElementById('game-over-title').textContent = 'Game over';
    var buttons = overlay.querySelectorAll('button');
    if (buttons.length) buttons[0].focus();
    overlay.onkeydown = function(event) {
      if (event.key === 'Escape') {
        overlay.remove();document.getElementById('fab-game-result').focus();
      }
      if (event.key === 'Tab' && buttons.length) {
        var first = buttons[0], last = buttons[buttons.length - 1];
        if (event.shiftKey && document.activeElement === first) {event.preventDefault();last.focus();}
        else if (!event.shiftKey && document.activeElement === last) {event.preventDefault();first.focus();}
      }
    };
  }
  window.FaBRefreshGameOver = function() {
    var winner = Number(window.WinnerData) || 0;
    var button = document.getElementById('fab-game-result');
    if (!button) return;
    button.hidden = winner <= 0;
    if (winner <= 0) {
      shownWinner = 0;
      var overlay = document.getElementById('game-over-overlay');
      if (overlay && overlay.classList.contains('fab-game-over')) overlay.remove();
      return;
    }
    button.textContent = 'Player ' + winner + ' wins · View result';
    if (shownWinner === winner || typeof ShowGameOver !== 'function') return;
    shownWinner = winner;
    showResult();
  };
  document.getElementById('fab-game-result').onclick = showResult;
})();
</script>
