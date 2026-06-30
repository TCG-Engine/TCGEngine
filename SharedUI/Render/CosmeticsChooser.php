<?php
// Shared renderer for the SWUSim cosmetics chooser + live preview (Profile page).
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../../SWUSim/Cosmetics/Catalog.php';

function RenderCosmeticsChooser(int $userId): string {
    $esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $cat = SWUCosmeticCatalog();
    $cur = LoadUserCosmetics($userId);   // resolved current selections

    $select = function($slot) use ($cat, $cur, $esc) {
        $opts = '';
        foreach ($cat[$slot] as $id => $o) {
            $sel = ($cur[$slot]['id'] === $id) ? ' selected' : '';
            $asset = SWUCosmeticAssetUrl($o['asset'] ?? null);
            $opts .= "<option value=\"{$esc($id)}\" data-asset=\"{$esc($asset)}\"$sel>{$esc($o['label'])}</option>";
        }
        return "<select class='cos-select' data-slot=\"{$esc($slot)}\">$opts</select>";
    };

    $bg   = $esc(SWUCosmeticAssetUrl($cur['background']['asset'] ?? null));
    $back = $esc(SWUCosmeticAssetUrl($cur['cardback']['asset'] ?? null));
    $mat  = $esc(SWUCosmeticAssetUrl($cur['playmat']['asset'] ?? null));

    return "<div class='cosmetics-chooser'>"
      . "<div class='cos-row'><label>Game background</label>" . $select('background') . "</div>"
      . "<div class='cos-row'><label>Card back</label>"       . $select('cardback')   . "</div>"
      . "<div class='cos-row'><label>Playmat</label>"         . $select('playmat')    . "</div>"
      . "<div class='cos-row'><label><input type='checkbox' id='cos-show-playmats' checked> Show playmats in-game</label></div>"
      . "<div class='cos-preview' style=\"background-image:url('$bg')\">"
      .   "<div class='cos-preview-mat' style=\"background-image:url('$mat')\"></div>"
      .   "<img class='cos-preview-back' src='$back' alt='card back'>"
      .   "<img class='cos-preview-back' src='$back' alt='card back'>"
      . "</div>"
      . _CosmeticsChooserScript() . "</div>";
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
        if (slot==='playmat'){ var m=prev.querySelector('.cos-preview-mat'); if(m) m.style.backgroundImage = asset?\"url('\"+asset+\"')\":''; }
        if (slot==='cardback') prev.querySelectorAll('.cos-preview-back').forEach(function(b){ b.src = asset; });
      }
      var x=new XMLHttpRequest(); x.open('POST',URL,true);
      x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
      x.send('action=set&slot='+encodeURIComponent(slot)+'&choiceId='+encodeURIComponent(sel.value));
      return;
    }
    var tg = e.target.closest('#cos-show-playmats');
    if (tg && window.TCGSettings && typeof window.TCGSettings.set==='function') {
      window.TCGSettings.set('ShowPlaymats', tg.checked, { rootName:'SWUSim', type:'boolean' });
    }
  });
  // reflect persisted toggle
  try { if (window.TCGSettings) { var t=document.getElementById('cos-show-playmats');
    if (t) t.checked = window.TCGSettings.get('ShowPlaymats', { rootName:'SWUSim', type:'boolean', defaultValue:true }) !== false; } } catch(e){}
})();</script>";
}
