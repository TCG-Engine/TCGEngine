<?php
require_once __DIR__ . '/../../SharedUI/Render/AssetVersion.php';   // _VersionAsset() — ?v=<filemtime> cache busting
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
  <script src="<?php echo _VersionAsset('/TCGEngine/Core/StyledDialog.js'); ?>"></script>
  <link rel="stylesheet" href="<?php echo _VersionAsset('/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-overrides.css'); ?>">
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
    .cu-tile-actions { display: flex; gap: 6px; margin-top: 6px; }
    .cu-tile-actions button { font-size: 11px; padding: 4px 10px; border-radius: 6px; cursor: pointer;
      background: rgba(62,44,12,0.9); color: #f0ddb0; border: 1px solid rgba(190,155,50,0.35); }
    .cu-tile-actions button:hover { border-color: rgba(200,160,55,0.7); }
    .cu-tile-actions .cu-danger { background: rgba(74,20,20,0.9); color: #f0c0c0;
      border-color: rgba(190,70,70,0.4); }
    .cu-tile-actions .cu-danger:hover { border-color: rgba(220,90,90,0.75); }
    .cu-edit { margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(170,130,40,0.25); }
    .cu-edit[hidden] { display: none; }
    .cu-edit label { display: block; margin-bottom: 8px; color: #e8d5a8; font-size: 12px; }
    .cu-edit input[type=text] { width: 100%; }
    .cu-edit-hint { font-size: 11px; color: #b39a68; margin-bottom: 8px; }
    .cu-edit-hint code { color: #d8c290; }
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
        <?php foreach ($cat[$slot] as $id => $opt):
          $isDefault = !empty($opt['isDefault']);
          $asset = SWUCosmeticAssetUrl($opt['asset'] ?? null);
          // Replacing a cosmetic reuses the SAME asset path, so a plain URL would show the browser's
          // cached copy of the OLD art. Version the thumb by the file's mtime — same trick as
          // _VersionAsset() on the stylesheets.
          if ($asset) {
            $abs = realpath(__DIR__ . '/../..' . preg_replace('#^/TCGEngine#', '', $asset));
            if ($abs && is_file($abs)) $asset .= '?v=' . filemtime($abs);
          }
        ?>
          <div class="cu-tile" data-name="<?= $esc(strtolower($opt['label'])) ?>" id="cu-tile-<?= $esc($slot) ?>-<?= $esc($id) ?>">
            <div class="cu-thumb cu-thumb--<?= $esc($slot) ?>"<?= $asset ? " style=\"background-image:url('".$esc($asset)."')\"" : '' ?>><?= $asset ? '' : 'None' ?></div>
            <div class="cu-name"><?= $esc($opt['label']) ?></div>
            <?php if ($isDefault): ?>
              <?php /* The slot default is the fallback target for every unresolved selection
                        (SWUCosmeticResolve), so deleting it would break that fallback. It can still be
                        renamed — a rename touches neither the id nor the asset. */ ?>
              <span class="cu-builtin" title="This is the slot default — it can be renamed but not deleted.">default</span>
              <div class="cu-tile-actions">
                <button type="button" onclick="cuEditOpen('<?= $esc($slot) ?>','<?= $esc($id) ?>')">Edit</button>
              </div>
            <?php else: ?>
              <div class="cu-tile-actions">
                <button type="button" onclick="cuEditOpen('<?= $esc($slot) ?>','<?= $esc($id) ?>')">Edit</button>
                <button type="button" class="cu-danger" onclick="cuDelete('<?= $esc($slot) ?>','<?= $esc($id) ?>','<?= $esc($opt['label']) ?>')">Delete</button>
              </div>
            <?php endif; ?>
            <form class="cu-edit" id="cu-edit-<?= $esc($slot) ?>-<?= $esc($id) ?>" hidden
                  onsubmit="return cuEditSubmit(event,'<?= $esc($slot) ?>','<?= $esc($id) ?>')">
              <label>Name <input type="text" name="label" maxlength="128" required
                                 value="<?= $esc($opt['label']) ?>"></label>
              <label>Replace image <input type="file" name="image" accept="image/*"></label>
              <div class="cu-edit-hint">id <code><?= $esc($id) ?></code> — fixed, so anyone who already
                picked this keeps it. Leave the file empty to rename only.</div>
              <div class="cu-preview" hidden>
                <div class="cu-preview-caption">New image staged — confirm to replace, or cancel to keep the current one.</div>
                <div class="cu-thumb cu-thumb--<?= $esc($slot) ?>"></div>
              </div>
              <div class="cu-preview-actions">
                <button type="submit">Save</button>
                <button type="button" onclick="cuEditCancel('<?= $esc($slot) ?>','<?= $esc($id) ?>')">Cancel</button>
              </div>
            </form>
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

    // ── Editing an existing cosmetic ───────────────────────────────────────────────────────────────
    // The id and the asset PATH never change — only the label and the bytes behind that path — so a
    // player who already selected this cosmetic keeps it and simply sees the new name/art.
    function cuEditEl(slot,id){ return document.getElementById('cu-edit-'+slot+'-'+id); }
    var cuStaged = {};   // "slot/id" -> true when a replacement image is staged but not yet confirmed

    function cuEditOpen(slot,id){
      var f = cuEditEl(slot,id); if(!f) return;
      f.hidden = !f.hidden;
    }
    function cuEditCancel(slot,id){
      var f = cuEditEl(slot,id); if(!f) return;
      var key = slot+'/'+id;
      var finish = function(){
        delete cuStaged[key];
        f.querySelector('.cu-preview').hidden = true;
        f.reset(); f.hidden = true;
      };
      // Only ask the server to clean up if something was actually staged on disk.
      if (cuStaged[key]) cuPost('CosmeticsCommit.php',
        'action=discard_staged&slot='+encodeURIComponent(slot)+'&id='+encodeURIComponent(id),
        finish, 'Cancel');
      else finish();
    }

    // Shared POST helper — the older cuCommit is kept for the add flow's pending-slot bookkeeping.
    function cuPost(endpoint, body, onOk, what){
      var x=new XMLHttpRequest(); x.open('POST', cuBase()+'SWUSim/Mod/'+endpoint, true);
      x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
      x.onload=function(){ var r={}; try{r=JSON.parse(x.responseText);}catch(_){}
        if(r.success) onOk(r); else StyledAlert((what||'Request')+' failed: '+(r.error||'unknown')); };
      x.send(body);
    }

    function cuEditSubmit(e, slot, id){
      e.preventDefault();
      var f = e.target;
      var label = f.querySelector('input[name=label]').value.trim();
      var file  = f.querySelector('input[type=file]').files[0];
      var key   = slot+'/'+id;

      // No new image → a pure rename.
      if (!file && !cuStaged[key]) {
        cuPost('CosmeticsCommit.php',
          'action=rename&slot='+encodeURIComponent(slot)+'&id='+encodeURIComponent(id)
            +'&label='+encodeURIComponent(label),
          function(){ location.reload(); }, 'Rename');
        return false;
      }
      // A file was chosen → stage it first, show it, and let the SAME Save button confirm.
      if (file && !cuStaged[key]) {
        var fd = new FormData(); fd.append('slot', slot); fd.append('label', label);
        fd.append('replaceId', id); fd.append('image', file);
        var x=new XMLHttpRequest(); x.open('POST', cuBase()+'SWUSim/Mod/CosmeticsUpload.php', true);
        x.onload=function(){ var r={}; try{r=JSON.parse(x.responseText);}catch(_){}
          if(!r.success){ StyledAlert('Upload failed: '+(r.error||'unknown')); return; }
          cuStaged[key] = true;
          var url = (r.asset||'').replace(/^\.\//,'/TCGEngine/') + '?t=' + Date.now();
          var box = f.querySelector('.cu-preview');
          box.querySelector('.cu-thumb').style.backgroundImage = "url('"+url+"')";
          box.hidden = false;
        };
        x.send(fd);
        return false;
      }
      // Staged already → confirm the replacement (and the label along with it).
      cuPost('CosmeticsCommit.php',
        'action=replace&slot='+encodeURIComponent(slot)+'&id='+encodeURIComponent(id)
          +'&label='+encodeURIComponent(label),
        function(){ location.reload(); }, 'Replace');
      return false;
    }

    function cuDelete(slot, id, label){
      // ⚠ StyledConfirm returns a PROMISE — it takes (message, opts), not a callback. Passing a
      // function as the second argument silently makes it the OPTIONS object, so the dialog opens,
      // the callback never fires, and Delete does nothing.
      StyledConfirm('Delete "'+label+'"? This removes its catalog entry and its image file. '
        + 'Anyone who had it selected falls back to the slot default.',
        {title:'Delete cosmetic', confirmLabel:'Delete', danger:true}
      ).then(function(okd){
        if(!okd) return;
        cuPost('CosmeticsCommit.php',
          'action=delete&slot='+encodeURIComponent(slot)+'&id='+encodeURIComponent(id),
          function(){ location.reload(); }, 'Delete');
      });
    }
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
