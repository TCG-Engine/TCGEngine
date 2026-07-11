<?php
// Shared renderer for the SWUSim cosmetics chooser + live preview (Profile page).
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../../SWUSim/Cosmetics/Catalog.php';

function RenderCosmeticsChooser(int $userId): string {
    $esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $cat = SWUCosmeticCatalog();
    $cur = LoadUserCosmetics($userId);   // resolved current selections

    $select = fn($slot) => SWUCosmeticSelectHtml($slot, $cur[$slot]['id']);

    $bg   = $esc(SWUCosmeticAssetUrl($cur['background']['asset'] ?? null));
    $back = $esc(SWUCosmeticAssetUrl($cur['cardback']['asset'] ?? null));
    $mat  = $esc(SWUCosmeticAssetUrl($cur['playmat']['asset'] ?? null));
    // The opponent's deck (top pile) always shows the default card back — you're previewing
    // only your own cosmetics, and the opponent uses whatever back they've chosen (default here).
    $defBack = $esc(SWUCosmeticAssetUrl($cat['cardback'][SWUCosmeticDefault('cardback')]['asset'] ?? null));
    // Playmat art carries the same dark tint the real board lays over it (see ApplyCosmeticPlaymats).
    $matBg = $mat !== '' ? "linear-gradient(rgba(8,8,10,0.42),rgba(8,8,10,0.42)), url('$mat')" : 'none';

    return "<div class='cosmetics-chooser'>"
      . _CosmeticsChooserStyles()
      . "<div class='cos-row'><label>Game background</label>" . $select('background') . "</div>"
      . "<div class='cos-row'><label>Card back</label>"       . $select('cardback')   . "</div>"
      . "<div class='cos-row'><label>Playmat</label>"         . $select('playmat')    . "</div>"
      . "<div class='cos-row'><label><input type='checkbox' id='cos-show-playmats' checked> Show playmats in-game</label></div>"
      // Live preview laid out like a real board: game background behind your playmat (which
      // fills the lower half, your side), with a square card-back deck pile parked on the
      // right of each side. The playmat honors the Show-playmats toggle.
      . "<div class='cos-preview' style=\"background-image:url('$bg')\">"
      .   "<div class='cos-preview-mat' style=\"background-image:$matBg\"></div>"
      .   "<img class='cos-preview-back cos-preview-back--top' src='$defBack' alt='opponent card back'>"
      .   "<img class='cos-preview-back cos-preview-back--bot' src='$back' alt='your card back'>"
      . "</div>"
      . _CosmeticsChooserScript() . "</div>";
}

// Scoped styles for the live preview. Laid out to resemble a real board — a 16:9 stage
// holding the game background, two per-side play areas, and a deck-pile card back per side —
// so a cosmetic combination reads the way it will in-game.
function _CosmeticsChooserStyles(): string {
    return "<style>
  .cosmetics-chooser .cos-preview {
    position: relative;
    width: 100%;
    max-width: 480px;
    aspect-ratio: 16 / 9;
    margin-top: 12px;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid rgba(var(--accent-rgb, 140,210,255), 0.30);
    box-shadow: 0 6px 18px rgba(0,0,0,0.45);
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }
  /* Your playmat fills the lower half of the board (your side); the upper half shows the
     game background, as it would in a real game. Hidden when Show-playmats is toggled off. */
  .cosmetics-chooser .cos-preview-mat {
    position: absolute;
    top: 50%; left: 3%; right: 3%; bottom: 3%;
    background-size: cover;
    background-position: center top;   /* playmats anchor to the top (matches in-game) */
    background-repeat: no-repeat;
    border-radius: 16px;                        /* same rounded corners as the in-game playmat (64px, scaled to preview) */
    border: 1px solid rgba(255,255,255,0.12);
    box-shadow: inset 0 0 22px rgba(0,0,0,0.40);
    pointer-events: none;
  }
  .cosmetics-chooser .cos-preview.cos-hide-mat .cos-preview-mat { display: none; }
  /* Deck piles: a square card back (matches the 512x512 card-back art) parked on the right
     of each side. Stacked offset shadows read as a deck rather than one flat card. */
  .cosmetics-chooser .cos-preview-back {
    position: absolute;
    right: 6%;
    height: 26%;
    aspect-ratio: 1 / 1;
    width: auto;
    object-fit: cover;
    border-radius: 4px;
    outline: 1px solid rgba(0,0,0,0.45);
    box-shadow: 1.5px 1.5px 0 rgba(0,0,0,0.30), 3px 3px 0 rgba(0,0,0,0.22), 0 4px 9px rgba(0,0,0,0.55);
  }
  .cosmetics-chooser .cos-preview-back--top { top: 8%; }
  .cosmetics-chooser .cos-preview-back--bot { bottom: 8%; }
</style>";
}

function _CosmeticsChooserScript(): string {
    return "<script>(function(){
  if (window.__cosWired) return; window.__cosWired = true;
  function appBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0?p.slice(0,i+11):'/TCGEngine/'; }
  var URL = appBase()+'SWUSim/Cosmetics.php';
  var prev = document.querySelector('.cos-preview');
  document.addEventListener('change', function(e){
    var sel = e.target.closest('.cosmetics-chooser .cos-select');
    if (sel) {
      var slot = sel.getAttribute('data-slot');
      var opt = sel.options[sel.selectedIndex];
      var asset = opt ? opt.getAttribute('data-asset') : '';
      if (prev) {
        if (slot==='background') prev.style.backgroundImage = asset?\"url('\"+asset+\"')\":'';
        if (slot==='playmat'){ var mat = asset?\"linear-gradient(rgba(8,8,10,0.42),rgba(8,8,10,0.42)), url('\"+asset+\"')\":'none';
          prev.querySelectorAll('.cos-preview-mat').forEach(function(m){ m.style.backgroundImage = mat; }); }
        if (slot==='cardback'){ var b=prev.querySelector('.cos-preview-back--bot'); if(b) b.src = asset; }
      }
      var x=new XMLHttpRequest(); x.open('POST',URL,true);
      x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
      x.send('action=set&slot='+encodeURIComponent(slot)+'&choiceId='+encodeURIComponent(sel.value));
      return;
    }
    var tg = e.target.closest('#cos-show-playmats');
    if (tg) {
      if (prev) prev.classList.toggle('cos-hide-mat', !tg.checked);   // preview honors the toggle
      if (window.TCGSettings && typeof window.TCGSettings.set==='function')
        window.TCGSettings.set('ShowPlaymats', tg.checked, { rootName:'SWUSim', type:'boolean' });
    }
  });
  // reflect persisted toggle (checkbox + preview playmat visibility)
  try { if (window.TCGSettings) { var t=document.getElementById('cos-show-playmats');
    if (t) { var show = window.TCGSettings.get('ShowPlaymats', { rootName:'SWUSim', type:'boolean', defaultValue:true }) !== false;
      t.checked = show; if (prev) prev.classList.toggle('cos-hide-mat', !show); } } } catch(e){}
})();</script>";
}
