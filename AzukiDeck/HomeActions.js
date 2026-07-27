(function () {
  "use strict";

  function flash(message) {
    var toast = document.createElement("div");
    toast.textContent = message;
    toast.style.cssText = "position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1769aa;color:#fff;padding:10px 18px;border-radius:6px;z-index:6001;box-shadow:0 4px 14px rgba(0,0,0,.4)";
    document.body.appendChild(toast);
    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 1800);
  }

  function request(url, onSuccess) {
    fetch(url, { credentials: "same-origin" })
      .then(function (response) {
        if (!response.ok) throw new Error("Request failed");
        onSuccess();
      })
      .catch(function () { flash("That deck action failed. Please try again."); });
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text);
    }
    var input = document.createElement("input");
    input.value = text;
    document.body.appendChild(input);
    input.select();
    document.execCommand("copy");
    document.body.removeChild(input);
    return Promise.resolve();
  }

  function loadingOverlay() {
    var overlay = document.createElement("div");
    overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:6000;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px";
    overlay.textContent = "Generating deck image\u2026";
    document.body.appendChild(overlay);
    return function () {
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
    };
  }

  async function fetchImage(deckID, sort) {
    var response = await fetch("/TCGEngine/AzukiDeck/CreateImage.php?gameName=" + encodeURIComponent(deckID) + "&sort=" + encodeURIComponent(sort));
    if (!response.ok) throw new Error("Image request failed");
    var blob = await response.blob();
    if (!blob.type.startsWith("image/")) throw new Error("Invalid image response");
    return blob;
  }

  function showImage(deckID, initialBlob) {
    var overlay = document.createElement("div");
    overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:6000;display:flex;align-items:center;justify-content:center;padding:20px";
    var panel = document.createElement("div");
    panel.style.cssText = "background:#12202f;border:1px solid rgba(118,196,255,.35);border-radius:10px;padding:16px;max-width:min(96vw,1400px);max-height:92vh;display:flex;flex-direction:column;align-items:center;gap:12px";
    var image = document.createElement("img");
    var blob = initialBlob;
    var objectURL = URL.createObjectURL(blob);
    image.src = objectURL;
    image.alt = "Deck image";
    image.style.cssText = "max-width:100%;max-height:70vh;object-fit:contain;border-radius:4px";

    var controls = document.createElement("div");
    controls.style.cssText = "display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:center";
    var sort = document.createElement("select");
    sort.innerHTML = '<option value="cost">Sort by cost</option><option value="name">Sort by name</option>';
    var regenerate = document.createElement("button");
    regenerate.textContent = "Regenerate";
    regenerate.onclick = async function () {
      regenerate.disabled = true;
      try {
        var nextBlob = await fetchImage(deckID, sort.value);
        URL.revokeObjectURL(objectURL);
        blob = nextBlob;
        objectURL = URL.createObjectURL(blob);
        image.src = objectURL;
      } catch (error) {
        flash("Failed to generate the image.");
      } finally {
        regenerate.disabled = false;
      }
    };
    var copy = document.createElement("button");
    copy.textContent = "Copy image";
    copy.onclick = function () {
      if (!window.ClipboardItem || !navigator.clipboard || !navigator.clipboard.write) {
        flash("Image copying is not supported in this browser.");
        return;
      }
      var clipboardPayload = {};
      clipboardPayload[blob.type] = blob;
      navigator.clipboard.write([new ClipboardItem(clipboardPayload)])
        .then(function () { flash("Image copied!"); })
        .catch(function () { flash("Image copying is not supported here."); });
    };
    var close = document.createElement("button");
    close.textContent = "Close";
    function dismiss() {
      URL.revokeObjectURL(objectURL);
      document.removeEventListener("keydown", onKeydown);
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
    }
    function onKeydown(event) {
      if (event.key === "Escape") dismiss();
    }
    close.onclick = dismiss;
    overlay.onclick = function (event) {
      if (event.target === overlay) dismiss();
    };
    document.addEventListener("keydown", onKeydown);

    controls.appendChild(sort);
    controls.appendChild(regenerate);
    controls.appendChild(copy);
    controls.appendChild(close);
    panel.appendChild(image);
    panel.appendChild(controls);
    overlay.appendChild(panel);
    document.body.appendChild(overlay);
  }

  window.AzukiDeckHome = {
    move: function (deckID, folderID) {
      request("/TCGEngine/AccountFiles/MoveAsset.php?assetID=" + encodeURIComponent(deckID) + "&assetType=1&folderID=" + encodeURIComponent(folderID), function () {
        window.location.reload();
      });
    },
    copyLink: function (deckID) {
      var code = (window.AZUKI_DECK_CODES || {})[deckID];
      var link = code
        ? window.location.origin + "/deck/" + code
        : window.location.origin + "/TCGEngine/NextTurn.php?gameName=" + encodeURIComponent(deckID) + "&playerID=1&folderPath=AzukiDeck";
      copyText(link).then(function () { flash("Link copied!"); }).catch(function () { flash("Could not copy the link."); });
    },
    generateImage: async function (deckID) {
      if (window.__azukiDeckImageBusy) return;
      window.__azukiDeckImageBusy = true;
      var closeLoader = loadingOverlay();
      try {
        var blob = await fetchImage(deckID, "cost");
        closeLoader();
        showImage(deckID, blob);
      } catch (error) {
        closeLoader();
        flash("Failed to generate the image.");
      } finally {
        window.__azukiDeckImageBusy = false;
      }
    },
    remove: function (deckID) {
      if (!window.confirm("Are you sure you want to delete this deck?")) return;
      request("/TCGEngine/AccountFiles/DeleteAsset.php?assetID=" + encodeURIComponent(deckID) + "&assetType=1", function () {
        window.location.reload();
      });
    }
  };
})();
