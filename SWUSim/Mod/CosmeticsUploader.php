<?php
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo "<h2>Access denied</h2><p>".htmlspecialchars($modErr, ENT_QUOTES)."</p>"; exit; }
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
  <link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUSim/css/ClarentMenuStyles.css">
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
        <button type="submit">Upload <?= $esc($labels[$slot]) ?></button>
      </form>
    <?php endforeach; ?>

    <?php foreach (['background','cardback','playmat'] as $slot): ?>
      <h2><?= $esc($labels[$slot]) ?></h2>
      <div class="cu-grid">
        <?php foreach ($cat[$slot] as $id => $opt): $uploaded = !empty($opt['uploaded']); $asset = SWUCosmeticAssetUrl($opt['asset'] ?? null); ?>
          <div class="cu-tile" data-name="<?= $esc(strtolower($opt['label'])) ?>">
            <div class="cu-thumb cu-thumb--<?= $esc($slot) ?>"<?= $asset ? " style=\"background-image:url('".$esc($asset)."')\"" : '' ?>><?= $asset ? '' : 'None' ?></div>
            <div class="cu-name"><?= $esc($opt['label']) ?></div>
            <?php if ($uploaded): ?>
              <button type="button" class="cu-del" onclick="cuDelete('<?= $esc($slot) ?>','<?= $esc($id) ?>')">Delete</button>
            <?php else: ?>
              <span class="cu-builtin">built-in</span>
            <?php endif; ?>
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
    function cuUpload(e, slot){
      e.preventDefault();
      var fd=new FormData(e.target); fd.append('slot', slot);
      var x=new XMLHttpRequest(); x.open('POST', cuBase()+'SWUSim/Mod/CosmeticsUpload.php', true);
      x.onload=function(){ var r={}; try{r=JSON.parse(x.responseText);}catch(_){}
        if(r.success) location.reload(); else alert('Upload failed: '+(r.error||'unknown')); };
      x.send(fd); return false;
    }
    function cuDelete(slot, id){
      if(!confirm('Delete this cosmetic?')) return;
      var x=new XMLHttpRequest(); x.open('POST', cuBase()+'SWUSim/Mod/CosmeticsDelete.php', true);
      x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
      x.onload=function(){ var r={}; try{r=JSON.parse(x.responseText);}catch(_){}
        if(r.success) location.reload(); else alert('Delete failed: '+(r.error||'unknown')); };
      x.send('slot='+encodeURIComponent(slot)+'&id='+encodeURIComponent(id));
    }
  </script>
</body></html>
