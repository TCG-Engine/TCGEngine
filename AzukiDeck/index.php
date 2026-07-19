<?php

include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../SharedUI/Sites/AzukiSim/MenuBar.php';
include_once __DIR__ . '/../SharedUI/Sites/AzukiSim/Header.php';
include_once __DIR__ . '/DeckService.php';

$decks = IsUserLoggedIn() ? AzukiDeckLoadOwnedDecks(LoggedInUser()) : [];
$error = trim((string)($_GET['error'] ?? ''));

?>
<style>
  .azuki-deck-home { max-width: 1120px; margin: 28px auto; padding: 0 18px 48px; color: #eef4ff; }
  .azuki-deck-hero, .azuki-deck-list { background: rgba(18,24,34,.92); border: 1px solid rgba(86,166,255,.35); border-radius: 12px; box-shadow: 0 14px 40px rgba(0,0,0,.3); }
  .azuki-deck-hero { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:24px; }
  .azuki-deck-hero h1 { margin:0 0 6px; font-family:Teko,sans-serif; font-size:42px; line-height:1; }
  .azuki-deck-hero p { margin:0; color:#b9c7d8; }
  .azuki-deck-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
  .azuki-deck-button { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:0 18px; border:1px solid #4ca7ff; color:white; background:#1769aa; text-decoration:none; border-radius:6px; font-weight:700; cursor:pointer; }
  .azuki-deck-button.secondary { background:rgba(37,53,72,.9); }
  .azuki-deck-import { display:flex; gap:8px; margin-top:12px; }
  .azuki-deck-import input { min-width:320px; height:42px; box-sizing:border-box; background:#0d131c; color:white; border:1px solid #44576d; border-radius:6px; padding:0 12px; }
  .azuki-deck-list { margin-top:18px; padding:18px; }
  .azuki-deck-list h2 { margin:0 0 14px; }
  .azuki-deck-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:12px; }
  .azuki-deck-card { display:flex; gap:12px; align-items:center; min-height:92px; padding:12px; color:white; text-decoration:none; background:rgba(9,15,23,.88); border:1px solid rgba(255,255,255,.12); border-radius:8px; }
  .azuki-deck-card:hover { border-color:#4ca7ff; transform:translateY(-1px); }
  .azuki-deck-card { position:relative; flex-direction:column; align-items:stretch; gap:10px; }
  .azuki-deck-open { display:flex; gap:12px; align-items:center; min-width:0; color:white; text-decoration:none; }
  .azuki-deck-open > div { min-width:0; }
  .azuki-deck-open strong { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .azuki-deck-cardactions { display:flex; gap:6px; justify-content:flex-start; flex-wrap:wrap; padding-top:8px; border-top:1px solid rgba(255,255,255,.08); }
  .azuki-deck-cardactions button { width:34px; height:34px; border-radius:6px; border:1px solid rgba(255,255,255,.18); background:rgba(37,53,72,.9); color:#fff; font-size:15px; cursor:pointer; line-height:1; display:flex; align-items:center; justify-content:center; }
  .azuki-deck-cardactions button:hover { border-color:#4ca7ff; }
  .azuki-deck-subhead { margin:18px 0 8px; font-size:15px; color:#9fb0c2; }
  .azuki-deck-card img { width:58px; height:82px; object-fit:cover; border-radius:5px; }
  .azuki-deck-card strong { display:block; font-size:17px; }
  .azuki-deck-card span { color:#9fb0c2; font-size:13px; }
  .azuki-deck-empty, .azuki-deck-error { padding:16px; border-radius:8px; color:#c7d4e3; background:rgba(255,255,255,.04); }
  .azuki-deck-error { margin-top:14px; color:#ffd0d0; border:1px solid rgba(255,100,100,.35); }
  @media(max-width:700px){ .home-header{box-sizing:border-box;padding-top:58px!important}.azuki-deck-hero{align-items:stretch;flex-direction:column}.azuki-deck-actions{justify-content:flex-start}.azuki-deck-import{flex-direction:column}.azuki-deck-import input{min-width:0;width:100%} }
</style>
<main class="azuki-deck-home">
  <section class="azuki-deck-hero">
    <div>
      <h1>AzukiDeck</h1>
      <p>Build and save Azuki decks with the SWUDeck editor experience.</p>
      <?php if (IsUserLoggedIn()): ?>
      <form class="azuki-deck-import" action="CreateDeck.php" method="get">
        <input name="deckLink" aria-label="Import deck link" placeholder="Optional thegateikz.com deck URL or slug">
        <button class="azuki-deck-button secondary" type="submit">Import Deck</button>
      </form>
      <?php endif; ?>
    </div>
    <div class="azuki-deck-actions">
      <?php if (IsUserLoggedIn()): ?>
        <a id="createDeckButton" class="azuki-deck-button" href="CreateDeck.php">New Deck</a>
      <?php else: ?>
        <a class="azuki-deck-button" href="/TCGEngine/SharedUI/Sites/AzukiSim/Signup.php?redirect=%2FTCGEngine%2FAzukiDeck%2F">Create account</a>
        <a class="azuki-deck-button secondary" href="/TCGEngine/SharedUI/Sites/AzukiSim/LoginPage.php?redirect=%2FTCGEngine%2FAzukiDeck%2F">Log in</a>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($error !== ''): ?><div class="azuki-deck-error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>

  <section class="azuki-deck-list">
    <h2>Your decks</h2>
    <?php if (!IsUserLoggedIn()): ?>
      <div class="azuki-deck-empty">Log in to create and manage Azuki decks.</div>
    <?php elseif (empty($decks)): ?>
      <div class="azuki-deck-empty">No Azuki decks yet. Create one to open the builder.</div>
    <?php else:
      $favorites = array_values(array_filter($decks, fn($d) => intval($d['assetFolder'] ?? 0) === 1));
      $others    = array_values(array_filter($decks, fn($d) => intval($d['assetFolder'] ?? 0) !== 1));
      $renderCard = function($deck) {
        $deckID   = intval($deck['assetIdentifier']);
        $name     = trim((string)$deck['assetName']) !== '' ? $deck['assetName'] : 'Deck #' . $deckID;
        $leaderID = trim((string)($deck['keyIndicator1'] ?? ''));
        $isFav    = intval($deck['assetFolder'] ?? 0) === 1;
        $openURL  = '../NextTurn.php?gameName=' . $deckID . '&playerID=1&folderPath=AzukiDeck';
        ?>
        <div class="azuki-deck-card" data-deckid="<?php echo $deckID; ?>">
          <a class="azuki-deck-open" href="<?php echo $openURL; ?>">
            <?php if ($leaderID !== ''): ?><img src="../AzukiSim/WebpImages/<?php echo rawurlencode($leaderID); ?>.webp" alt=""><?php endif; ?>
            <div><strong><?php echo htmlspecialchars($name, ENT_QUOTES); ?></strong><span>Open deck builder</span></div>
          </a>
          <div class="azuki-deck-cardactions">
            <button type="button" title="<?php echo $isFav ? 'Unfavorite' : 'Favorite'; ?>" aria-label="<?php echo $isFav ? 'Unfavorite' : 'Favorite'; ?>" onclick="MoveDeck(<?php echo $deckID; ?>, <?php echo $isFav ? 0 : 1; ?>)"><svg viewBox="0 0 24 24" width="18" height="18" fill="<?php echo $isFav ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15 9 22 9 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9 9 9"/></svg></button>
            <button type="button" title="Copy link" aria-label="Copy link" onclick="CopyDeckLink(<?php echo $deckID; ?>, event)"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg></button>
            <button type="button" title="Generate image" aria-label="Generate image" onclick="GenerateDeckImage(<?php echo $deckID; ?>, event)"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></button>
            <button type="button" title="Delete" aria-label="Delete" onclick="DeleteDeck(<?php echo $deckID; ?>)"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg></button>
          </div>
        </div>
      <?php };
    ?>
      <?php if (!empty($favorites)): ?>
        <h3 class="azuki-deck-subhead">Favorites</h3>
        <div class="azuki-deck-grid">
          <?php foreach ($favorites as $deck) $renderCard($deck); ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($favorites) && !empty($others)): ?><h3 class="azuki-deck-subhead">All decks</h3><?php endif; ?>
      <?php if (!empty($others)): ?>
        <div class="azuki-deck-grid">
          <?php foreach ($others as $deck) $renderCard($deck); ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>

  <script>
    window.AZUKI_DECK_CODES = <?php
      $codes = [];
      foreach ($decks as $d) {
        $c = trim((string)($d['friendlyCode'] ?? ''));
        if ($c !== '') $codes[(string)intval($d['assetIdentifier'])] = $c;
      }
      echo json_encode($codes, JSON_UNESCAPED_SLASHES);
    ?>;

    function azukiFlash(msg) {
      var t = document.createElement('div');
      t.textContent = msg;
      t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1769aa;color:#fff;padding:10px 18px;border-radius:6px;z-index:6000;box-shadow:0 4px 14px rgba(0,0,0,.4);';
      document.body.appendChild(t);
      setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, 1800);
    }

    function azukiCopyText(text) {
      if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text); return; }
      var i = document.createElement('input');
      i.value = text; document.body.appendChild(i); i.select();
      document.execCommand('copy'); document.body.removeChild(i);
    }

    function CopyDeckLink(deckID, event) {
      var code = (window.AZUKI_DECK_CODES || {})[deckID];
      var link = code
        ? window.location.origin + "/deck/" + code
        : window.location.origin + "/TCGEngine/NextTurn.php?gameName=" + deckID + "&playerID=1&folderPath=AzukiDeck";
      azukiCopyText(link);
      azukiFlash("Link copied!");
    }

    function MoveDeck(deckID, folderID) {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/TCGEngine/AccountFiles/MoveAsset.php?assetID=" + deckID + "&assetType=1&folderID=" + folderID, true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) location.reload();
      };
      xhr.send();
    }

    function azukiConfirm(message, onYes) {
      var overlay = document.createElement('div');
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:6000;display:flex;align-items:center;justify-content:center;padding:20px;';
      var box = document.createElement('div');
      box.style.cssText = 'background:#12202f;border:1px solid rgba(255,100,100,.4);border-radius:10px;padding:20px;max-width:360px;color:#eef4ff;box-shadow:0 14px 40px rgba(0,0,0,.5);';
      box.innerHTML = '<div style="margin-bottom:16px;font-size:15px;">' + message + '</div>';
      var row = document.createElement('div');
      row.style.cssText = 'display:flex;gap:10px;justify-content:flex-end;';
      var cancel = document.createElement('button');
      cancel.textContent = 'Cancel';
      cancel.style.cssText = 'padding:8px 16px;border-radius:6px;border:1px solid #44576d;background:#25384c;color:#fff;cursor:pointer;';
      var ok = document.createElement('button');
      ok.textContent = 'Delete';
      ok.style.cssText = 'padding:8px 16px;border-radius:6px;border:1px solid #c0392b;background:#c0392b;color:#fff;cursor:pointer;';
      function close(){ if (overlay.parentNode) overlay.parentNode.removeChild(overlay); }
      cancel.onclick = close;
      ok.onclick = function(){ close(); onYes(); };
      overlay.onclick = function(e){ if (e.target === overlay) close(); };
      row.appendChild(cancel); row.appendChild(ok); box.appendChild(row); overlay.appendChild(box);
      document.body.appendChild(overlay);
    }

    function DeleteDeck(deckID) {
      azukiConfirm("Are you sure you want to delete this deck?", function() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "/TCGEngine/AccountFiles/DeleteAsset.php?assetID=" + deckID + "&assetType=1", true);
        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4 && xhr.status === 200) location.reload();
        };
        xhr.send();
      });
    }

    const AZUKI_IMAGE_SORTS = [["cost","Cost"],["name","Name"]];

    async function azukiFetchImageBlob(deckID, sort) {
      const resp = await fetch(`/TCGEngine/AzukiDeck/CreateImage.php?gameName=${deckID}&sort=${encodeURIComponent(sort)}`);
      if (!resp.ok) throw new Error("load failed");
      const blob = await resp.blob();
      if (!blob.type.startsWith("image/")) throw new Error("not an image");
      return blob;
    }

    function azukiLoadingOverlay(message) {
      if (!document.getElementById("azukiSpinKeyframes")) {
        const st = document.createElement("style");
        st.id = "azukiSpinKeyframes";
        st.textContent = "@keyframes azukiSpin{to{transform:rotate(360deg)}}";
        document.head.appendChild(st);
      }
      const o = document.createElement("div");
      o.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:5000;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px;";
      const s = document.createElement("div");
      s.style.cssText = "width:54px;height:54px;border:5px solid rgba(140,210,255,.25);border-top-color:#8cd2ff;border-radius:50%;animation:azukiSpin .8s linear infinite;";
      const l = document.createElement("div");
      l.textContent = message || "Loading…"; l.style.cssText = "color:#fff;font-size:16px;";
      o.appendChild(s); o.appendChild(l); document.body.appendChild(o);
      return { close: function(){ if (o.parentNode) o.parentNode.removeChild(o); } };
    }

    function azukiOpenImageModal(deckID, blob, sort) {
      const overlay = document.createElement("div");
      overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:5000;display:flex;align-items:center;justify-content:center;padding:20px;";
      const panel = document.createElement("div");
      panel.style.cssText = "background:#12202f;border-radius:8px;padding:16px;max-width:min(96vw,1400px);max-height:92vh;display:flex;flex-direction:column;align-items:center;gap:12px;";
      let url = URL.createObjectURL(blob), cur = blob;
      const img = document.createElement("img");
      img.src = url; img.alt = "Deck image";
      img.style.cssText = "max-width:100%;max-height:70vh;height:auto;object-fit:contain;border-radius:4px;";
      const controls = document.createElement("div");
      controls.style.cssText = "display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:center;";
      const sortSel = document.createElement("select");
      sortSel.style.cssText = "padding:6px;";
      AZUKI_IMAGE_SORTS.forEach(function(p){ const o=document.createElement("option"); o.value=p[0]; o.textContent=p[1]; if(p[0]===sort)o.selected=true; sortSel.appendChild(o); });
      const regen = document.createElement("button");
      regen.textContent = "Generate New Image"; regen.style.cssText = "padding:8px 18px;cursor:pointer;";
      regen.onclick = async function(e){
        e.stopPropagation(); regen.disabled = true; const prev = regen.textContent; regen.textContent = "Generating…";
        try { const nb = await azukiFetchImageBlob(deckID, sortSel.value); URL.revokeObjectURL(url); url = URL.createObjectURL(nb); cur = nb; img.src = url; }
        catch (err) { azukiFlash("Failed to load image!"); }
        regen.disabled = false; regen.textContent = prev;
      };
      const copyBtn = document.createElement("button");
      copyBtn.textContent = "Copy Image"; copyBtn.style.cssText = "padding:8px 18px;cursor:pointer;";
      copyBtn.onclick = function(e){
        e.stopPropagation();
        if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
          navigator.clipboard.write([new ClipboardItem({[cur.type]: cur})]).then(function(){ azukiFlash("Image copied!"); }, function(){ azukiFlash("Copy not supported here."); });
        } else { azukiFlash("Copy not supported in this browser."); }
      };
      const closeBtn = document.createElement("button");
      closeBtn.textContent = "Close"; closeBtn.style.cssText = "padding:8px 18px;cursor:pointer;";
      function close(){ URL.revokeObjectURL(url); if (overlay.parentNode) overlay.parentNode.removeChild(overlay); document.removeEventListener("keydown", onEsc); }
      function onEsc(e){ if (e.key === "Escape") close(); }
      closeBtn.onclick = function(e){ e.stopPropagation(); close(); };
      overlay.onclick = function(e){ if (e.target === overlay) close(); };
      document.addEventListener("keydown", onEsc);
      controls.appendChild(sortSel); controls.appendChild(regen);
      const btnRow = document.createElement("div"); btnRow.style.cssText = "display:flex;gap:12px;";
      btnRow.appendChild(copyBtn); btnRow.appendChild(closeBtn);
      panel.appendChild(img); panel.appendChild(controls); panel.appendChild(btnRow);
      overlay.appendChild(panel); document.body.appendChild(overlay);
    }

    async function GenerateDeckImage(deckID, event) {
      if (window.__azukiImageBusy) return;
      window.__azukiImageBusy = true;
      const loader = azukiLoadingOverlay("Generating deck image…");
      try { const blob = await azukiFetchImageBlob(deckID, "cost"); loader.close(); azukiOpenImageModal(deckID, blob, "cost"); }
      catch (err) { loader.close(); azukiFlash("Failed to load image!"); }
      finally { window.__azukiImageBusy = false; }
    }
  </script>
</main>
