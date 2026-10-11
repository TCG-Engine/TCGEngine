<style>
.fab-combat-progress{display:grid;grid-template-columns:repeat(7,minmax(58px,82px));justify-content:center;gap:5px;padding:7px 40px 6px;border-bottom:1px solid rgba(255,255,255,.07);background:rgba(0,0,0,.18)}
.fab-combat-step{position:relative;display:flex;align-items:center;justify-content:center;gap:5px;min-width:0;height:28px;padding:0 6px;border:1px solid rgba(255,255,255,.08);border-radius:15px;color:#767a78;text-align:center;font-size:8px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;transition:background .16s,border-color .16s,color .16s,transform .16s,box-shadow .16s}
.fab-step-glyph{display:block;width:20px;height:20px;flex:none;color:#9b9d99;filter:drop-shadow(0 1px 1px #0008)}
.fab-combat-step:not(:last-child):after{content:"";position:absolute;z-index:2;top:50%;right:-6px;width:7px;height:1px;background:rgba(255,255,255,.14)}
.fab-combat-step.is-complete{color:#aaa89f;border-color:rgba(214,170,77,.18);background:rgba(214,170,77,.055)}
.fab-combat-step.is-complete .fab-step-glyph{color:#69c887}
.fab-combat-progress{--fab-priority:105,200,135}
.fab-combat-progress[data-priority="opponent"]{--fab-priority:239,104,104}
.fab-combat-step.is-active{color:#fff;border-color:rgb(var(--fab-priority));background:rgba(var(--fab-priority),.2);transform:translateY(-1px);box-shadow:0 0 6px rgba(var(--fab-priority),.65),inset 0 0 8px rgba(var(--fab-priority),.12)}
.fab-combat-step.is-active .fab-step-glyph{color:rgb(var(--fab-priority));filter:drop-shadow(0 0 3px rgba(var(--fab-priority),.65))}

[data-fab-drag-handle]{cursor:grab;touch-action:none;user-select:none}
[data-fab-drag-handle]:active{cursor:grabbing}
[data-fab-drag-handle]:focus-visible{outline:2px solid #f6d68b;outline-offset:-3px}
.fab-combat-progress{grid-template-columns:repeat(7,minmax(0,82px));box-sizing:border-box}
#fab-upf-chain-panel header h2{flex:none;white-space:nowrap}
#fab-upf-chain-panel header .fab-combat-progress{flex:1;min-width:0;padding:0;border:0;background:none}
#fab-upf-chain-panel header button{flex:none}
#fab-upf-chain-panel .fab-upf-panel-body{max-height:calc(65vh - 65px)}
@media(max-width:1100px){#fab-upf-chain-panel .fab-step-label{display:none}}
@media(max-width:700px){#fab-upf-chain-panel header h2{display:none}}
@media(max-width:700px){.fab-combat-progress{padding-left:8px;padding-right:32px;gap:3px}.fab-combat-step{padding:0 2px;gap:2px}.fab-combat-step .fab-step-label{display:none}}
</style>
<script>
window.FaBClampPanel = function(panel, left, top) {
  if (panel.hidden) return;
  const rect = panel.getBoundingClientRect();
  // Convert centered layouts to viewport coordinates before moving them.
  panel.style.transform = 'none';
  panel.style.left = Math.max(8, Math.min(left ?? rect.left, window.innerWidth - rect.width - 8)) + 'px';
  panel.style.top = Math.max(8, Math.min(top ?? rect.top, window.innerHeight - Math.min(rect.height, window.innerHeight) - 8)) + 'px';
};
window.FaBMakeDraggablePanel = function(panel, handle) {
  if (!panel || !handle || handle.dataset.fabDragBound) return;
  handle.dataset.fabDragBound = 'true';
  handle.style.touchAction = 'none';
  const key = panel.id + '-position';
  let drag = null;
  const save = () => {
    try { localStorage.setItem(key, JSON.stringify({left: parseFloat(panel.style.left), top: parseFloat(panel.style.top)})); } catch (_) {}
  };
  if (!panel.dataset.fabDragBound) {
    panel.dataset.fabDragBound = 'true';
    try {
      const pos = JSON.parse(localStorage.getItem(key));
      if (pos && Number.isFinite(pos.left) && Number.isFinite(pos.top)) {
        panel.style.transform = 'none';
        panel.style.left = pos.left + 'px'; panel.style.top = pos.top + 'px';
      }
    } catch (_) {}
    window.addEventListener('resize', () => window.FaBClampPanel(panel));
  }
  handle.addEventListener('pointerdown', event => {
    if (event.defaultPrevented || event.button !== 0 || event.target.closest('button')) return;
    const rect = panel.getBoundingClientRect();
    drag = {x: event.clientX, y: event.clientY, left: rect.left, top: rect.top};
    handle.setPointerCapture(event.pointerId); event.preventDefault();
  });
  handle.addEventListener('pointermove', event => {
    if (drag) window.FaBClampPanel(panel, drag.left + event.clientX - drag.x, drag.top + event.clientY - drag.y);
  });
  handle.addEventListener('pointerup', () => { if (drag) { drag = null; save(); } });
  handle.addEventListener('pointercancel', () => { drag = null; });
  handle.addEventListener('lostpointercapture', () => { drag = null; });
  handle.addEventListener('keydown', event => {
    if (event.defaultPrevented || event.target !== handle) return;
    const delta = {ArrowLeft:[-20,0], ArrowRight:[20,0], ArrowUp:[0,-20], ArrowDown:[0,20]}[event.key];
    if (!delta) return;
    event.preventDefault();
    const rect = panel.getBoundingClientRect();
    window.FaBClampPanel(panel, rect.left + delta[0], rect.top + delta[1]); save();
  });
};
window.FaBUpdateCombatProgress = function(panel, state) {
  if (!panel) return;
  var active = String(state.combatStep || 'NONE').toUpperCase();
  var order = ['LAYER', 'ATTACK', 'DEFEND', 'REACTION', 'DAMAGE', 'RESOLUTION', 'CLOSE'];
  var activeIndex = order.indexOf(active);
  panel.querySelectorAll('[data-fab-step]').forEach(function(node) {
    var index = order.indexOf(node.dataset.fabStep);
    node.classList.toggle('is-active', index === activeIndex);
    node.classList.toggle('is-complete', activeIndex > index);
  });

  var player = Number(window.PriorityPlayerData || 0);
  var viewer = Number(document.getElementById('playerID')?.value);
  if (!viewer) viewer = Number(document.getElementById('viewerPerspective')?.value || 1);
  var progress = panel.querySelector('.fab-combat-progress');
  if (progress) {
    progress.dataset.priority = player === viewer ? 'self' : 'opponent';
    progress.setAttribute('aria-label', 'Combat progress. Player ' + player + ' has priority. Drag or use arrow keys to move.');
    progress.title = 'Player ' + player + ' has priority · Drag to move';
  }
  if (panel) panel.dataset.combatStep = active;
};
</script>
