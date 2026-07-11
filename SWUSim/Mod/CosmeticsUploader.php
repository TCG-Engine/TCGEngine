<?php
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo "<h2>Access denied</h2><p>".htmlspecialchars($modErr, ENT_QUOTES)."</p>"; exit; }
require_once __DIR__ . '/DevGate.php';
if (!SWUIsLocalDevRequest()) { http_response_code(403); echo "<h2>Dev only</h2><p>The cosmetics uploader runs only in the local dev environment.</p>"; exit; }
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/../Cosmetics/Catalog.php';
$cat = SWUCosmeticCatalog();
$esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$labels = ['background'=>'Background', 'cardback'=>'Card back', 'playmat'=>'Playmat'];
?>
<!DOCTYPE html>
<html><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cosmetics Uploader</title>
  <script src="/TCGEngine/Core/StyledDialog.js"></script>
  <link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-overrides.css">
  <style>
    .cu-wrap { max-width: 1000px; margin: 32px auto; padding: 24px; }
    .cu-wrap h1, .cu-wrap h2 { color: #f5e6c0; }
    #cu-search { width: 100%; max-width: 360px; padding: 9px 12px; margin: 8px 0 18px;
      background: rgba(30,18,4,0.9); color: #f0ddb0; border: 1px solid rgba(180,140,45,0.5); border-radius: 8px; }
    .cu-add-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
    .cu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr)); gap: 14px; margin-bottom: 26px; }
    .cu-tile { background: rgba(62,44,12,0.88); border: 1px solid rgba(190,155,50,0.32); border-radius: 10px;
      padding: 10px; text-align: center; color: #e8d5a8; }
    .cu-thumb { width: 100%; border-radius: 6px; background: #1e1206 center/cover no-repeat;
      display: flex; align-items: center; justify-content: center; color: #c8b080; font-size: 12px; }
    .cu-thumb--background { aspect-ratio: 16 / 9; }
    .cu-thumb--playmat { aspect-ratio: 21 / 9; }
    .cu-thumb--cardback { aspect-ratio: 1 / 1; }
    .cu-name { font-size: 13px; margin: 8px 0 4px; word-break: break-word; }
    .cu-del { font-size: 12px; padding: 5px 10px; }
    .cu-builtin { font-size: 11px; color: #c8b080; }
    .cu-form { display: none; margin: 8px 0 18px; padding: 14px; border-radius: 10px;
      background: rgba(40,24,8,0.7); border: 1px solid rgba(170,130,40,0.25); }
    .cu-form.open { display: block; }
    .cu-form label { display: block; margin-bottom: 8px; color: #e8d5a8; }
    .cu-form input[type=text], .cu-form input[type=file] { display: block; margin-top: 4px; }
    .cu-preview { margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(170,130,40,0.25); }
    .cu-preview-caption { font-size: 12px; color: #c8b080; margin-bottom: 8px; }
    .cu-preview .cu-thumb { max-width: 320px; }
    .cu-preview-actions { display: flex; gap: 10px; margin-top: 10px; }
  </style>
</head><body>
  <div class="cu-wrap card container">
    <h1>Cosmetics Uploader</h1>
    <input type="text" id="cu-search" placeholder="Search by name…">
    <div class="cu-add-row">
      <button type="button" onclick="cuToggle('background')">Add Background</button>
      <button type="button" onclick="cuToggle('cardback')">Add Cardback</button>
      <button type="button" onclick="cuToggle('playmat')">Add Playmat</button>
    </div>
    <?php foreach (['background','cardback','playmat'] as $slot): ?>
      <form class="cu-form" id="cu-form-<?= $slot ?>" onsubmit="return cuUpload(event,'<?= $slot ?>')">
        <label>Name <input type="text" name="label" required maxlength="128"></label>
        <label>Image (PNG/JPG/WebP) <input type="file" name="image" accept="image/*" required></label>
        <button type="submit" class="cu-upload-btn">Upload <?= $esc($labels[$slot]) ?></button>
        <div class="cu-preview" id="cu-preview-<?= $slot ?>" hidden>
          <div class="cu-preview-caption">Preview — confirm to save, or cancel to discard.</div>
          <div class="cu-thumb cu-thumb--<?= $slot ?>"></div>
          <div class="cu-preview-actions">
            <button type="button" class="cu-confirm" onclick="cuConfirm('<?= $slot ?>')">Confirm &amp; Save</button>
            <button type="button" class="cu-cancel" onclick="cuCancel('<?= $slot ?>')">Cancel</button>
          </div>
        </div>
      </form>
    <?php endforeach; ?>

    <?php foreach (['background','cardback','playmat'] as $slot): ?>
      <h2><?= $esc($labels[$slot]) ?></h2>
      <div class="cu-grid">
        <?php foreach ($cat[$slot] as $id => $opt): $uploaded = !empty($opt['uploaded']); $asset = SWUCosmeticAssetUrl($opt['asset'] ?? null); ?>
          <div class="cu-tile" data-name="<?= $esc(strtolower($opt['label'])) ?>">
            <div class="cu-thumb cu-thumb--<?= $esc($slot) ?>"<?= $asset ? " style=\"background-image:url('".$esc($asset)."')\"" : '' ?>><?= $asset ? '' : 'None' ?></div>
            <div class="cu-name"><?= $esc($opt['label']) ?></div>
            <span class="cu-builtin">built-in</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <script>
    function cuBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0?p.slice(0,i+11):'/TCGEngine/'; }
    function cuToggle(slot){ var f=document.getElementById('cu-form-'+slot); f.classList.toggle('open'); }
    document.getElementById('cu-search').addEventListener('input', function(){
      var q=this.value.toLowerCase();
      document.querySelectorAll('.cu-tile').forEach(function(t){
        t.style.display = t.getAttribute('data-name').indexOf(q)>=0 ? '' : 'none';
      });
    });
    var cuPending = {};   // slot -> {id,label,asset} awaiting confirm
    function cuCommit(slot, action, done){
      var p = cuPending[slot]; if(!p) return;
      var body = 'action='+encodeURIComponent(action)+'&slot='+encodeURIComponent(slot)
               + '&id='+encodeURIComponent(p.id)+'&label='+encodeURIComponent(p.label);
      var x=new XMLHttpRequest(); x.open('POST', cuBase()+'SWUSim/Mod/CosmeticsCommit.php', true);
      x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
      x.onload=function(){ var r={}; try{r=JSON.parse(x.responseText);}catch(_){}
        if(r.success) done(); else StyledAlert(action+' failed: '+(r.error||'unknown')); };
      x.send(body);
    }
    function cuUpload(e, slot){
      e.preventDefault();
      // A new upload replaces any un-confirmed pending asset for this slot (discard it first).
      if(cuPending[slot]) cuCommit(slot, 'discard', function(){});
      var fd=new FormData(e.target); fd.append('slot', slot);
      var x=new XMLHttpRequest(); x.open('POST', cuBase()+'SWUSim/Mod/CosmeticsUpload.php', true);
      x.onload=function(){ var r={}; try{r=JSON.parse(x.responseText);}catch(_){}
        if(!r.success){ StyledAlert('Upload failed: '+(r.error||'unknown')); return; }
        cuPending[slot] = {id:r.id, label:r.label, asset:r.asset};
        var url = (r.asset||'').replace(/^\.\//,'/TCGEngine/');
        var box = document.getElementById('cu-preview-'+slot);
        box.querySelector('.cu-thumb').style.backgroundImage = url ? "url('"+url+"')" : '';
        box.hidden = false;
        e.target.querySelector('.cu-upload-btn').disabled = true;
      };
      x.send(fd); return false;
    }
    function cuConfirm(slot){ cuCommit(slot, 'save', function(){ location.reload(); }); }
    function cuCancel(slot){
      cuCommit(slot, 'discard', function(){
        delete cuPending[slot];
        var form = document.getElementById('cu-form-'+slot);
        document.getElementById('cu-preview-'+slot).hidden = true;
        form.querySelector('.cu-upload-btn').disabled = false;
        form.reset();
      });
    }
  </script>
</body></html>
