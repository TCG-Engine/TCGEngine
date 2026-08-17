(function () {
  "use strict";

  function flash(message, type) {
    if (window.Toast) {
      window.Toast(message, { type: type || "success" });
      return;
    }
    var toast = document.createElement("div");
    toast.textContent = message;
    toast.style.cssText = "position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1d302e;color:#fff;padding:10px 18px;border:1px solid #b79a61;border-radius:6px;z-index:6001;box-shadow:0 4px 14px rgba(0,0,0,.45)";
    document.body.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 1800);
  }

  function deckLink(deckID) {
    return window.location.origin + "/TCGEngine/NextTurn.php?gameName=" + encodeURIComponent(deckID) + "&playerID=1&folderPath=HellbreakDeck";
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text);
    var input = document.createElement("input");
    input.value = text;
    document.body.appendChild(input);
    input.select();
    document.execCommand("copy");
    input.remove();
    return Promise.resolve();
  }

  function loadingOverlay() {
    var overlay = document.createElement("div");
    overlay.style.cssText = "position:fixed;inset:0;background:rgba(3,12,13,.82);z-index:6000;display:flex;align-items:center;justify-content:center;color:#eee2c6;font-size:16px";
    overlay.textContent = "Generating deck image\u2026";
    document.body.appendChild(overlay);
    return function () { overlay.remove(); };
  }

  async function fetchImage(deckID, sort) {
    var response = await fetch("/TCGEngine/HellbreakDeck/CreateImage.php?gameName=" + encodeURIComponent(deckID) + "&sort=" + encodeURIComponent(sort), { credentials: "same-origin" });
    if (!response.ok) throw new Error("Image request failed");
    var blob = await response.blob();
    if (!blob.type.startsWith("image/")) throw new Error("Invalid image response");
    return blob;
  }

  function pngClipboardBlob(sourceBlob) {
    if (sourceBlob.type === "image/png") return Promise.resolve(sourceBlob);
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(sourceBlob);
      var image = new Image();
      image.onload = function () {
        var canvas = document.createElement("canvas");
        canvas.width = image.naturalWidth;
        canvas.height = image.naturalHeight;
        var context = canvas.getContext("2d");
        if (!context) { URL.revokeObjectURL(url); reject(new Error("Canvas unavailable")); return; }
        context.drawImage(image, 0, 0);
        URL.revokeObjectURL(url);
        canvas.toBlob(function (blob) { blob ? resolve(blob) : reject(new Error("PNG conversion failed")); }, "image/png");
      };
      image.onerror = function () { URL.revokeObjectURL(url); reject(new Error("Image decode failed")); };
      image.src = url;
    });
  }

  function showImage(deckID, initialBlob) {
    var overlay = document.createElement("div");
    overlay.style.cssText = "position:fixed;inset:0;background:rgba(3,12,13,.88);z-index:6000;display:flex;align-items:center;justify-content:center;padding:20px";
    var panel = document.createElement("div");
    panel.style.cssText = "background:#132120;border:1px solid rgba(183,154,97,.55);border-radius:8px;padding:16px;max-width:min(96vw,1400px);max-height:92vh;display:flex;flex-direction:column;align-items:center;gap:12px";
    var image = document.createElement("img");
    var blob = initialBlob;
    var objectURL = URL.createObjectURL(blob);
    image.src = objectURL;
    image.alt = "Hellbreak deck image";
    image.style.cssText = "max-width:100%;max-height:72vh;object-fit:contain;border-radius:3px";

    var controls = document.createElement("div");
    controls.style.cssText = "display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:center";
    var sort = document.createElement("select");
    sort.innerHTML = '<option value="cost">Group by cost</option><option value="aspect">Group by aspect</option><option value="type">Group by type</option><option value="name">Sort by name</option>';
    sort.setAttribute("aria-label", "Deck image sorting");
    var status = document.createElement("span");
    status.style.cssText = "min-width:64px;color:#9fb5ae;font-size:13px";
    status.setAttribute("aria-live", "polite");
    sort.onchange = async function () {
      sort.disabled = true;
      status.textContent = "Updating\u2026";
      image.style.opacity = ".55";
      try {
        var nextBlob = await fetchImage(deckID, sort.value);
        URL.revokeObjectURL(objectURL);
        blob = nextBlob;
        objectURL = URL.createObjectURL(blob);
        image.src = objectURL;
        status.textContent = "Updated";
      } catch (error) {
        status.textContent = "";
        flash("Failed to generate the image.", "danger");
      } finally {
        sort.disabled = false;
        image.style.opacity = "1";
      }
    };

    var download = document.createElement("button");
    download.textContent = "Download";
    download.onclick = function () {
      var link = document.createElement("a");
      link.href = objectURL;
      link.download = "hellbreak-deck-" + deckID + (blob.type === "image/png" ? ".png" : ".webp");
      document.body.appendChild(link);
      link.click();
      link.remove();
    };
    var copy = document.createElement("button");
    copy.textContent = "Copy image";
    copy.onclick = async function () {
      if (!window.ClipboardItem || !navigator.clipboard || !navigator.clipboard.write) {
        flash("Image copying is not supported in this browser.", "danger");
        return;
      }
      copy.disabled = true;
      try {
        var png = await pngClipboardBlob(blob);
        await navigator.clipboard.write([new ClipboardItem({ "image/png": png })]);
        flash("Image copied!");
      } catch (error) {
        flash("Image copying is not supported here.", "danger");
      } finally { copy.disabled = false; }
    };
    var copyLink = document.createElement("button");
    copyLink.textContent = "Copy deck link";
    copyLink.onclick = function () {
      copyText(deckLink(deckID))
        .then(function () { flash("Link copied!"); })
        .catch(function () { flash("Could not copy the link.", "danger"); });
    };
    var close = document.createElement("button");
    close.textContent = "Close";
    function dismiss() {
      URL.revokeObjectURL(objectURL);
      document.removeEventListener("keydown", onKeydown);
      overlay.remove();
    }
    function onKeydown(event) { if (event.key === "Escape") dismiss(); }
    close.onclick = dismiss;
    overlay.onclick = function (event) { if (event.target === overlay) dismiss(); };
    document.addEventListener("keydown", onKeydown);

    [sort, status, download, copy, copyLink, close].forEach(function (control) { controls.appendChild(control); });
    panel.appendChild(image);
    panel.appendChild(controls);
    overlay.appendChild(panel);
    document.body.appendChild(overlay);
  }

  window.HellbreakDeckHome = {
    generateImage: async function (deckID) {
      if (window.__hellbreakDeckImageBusy) return;
      window.__hellbreakDeckImageBusy = true;
      var closeLoader = loadingOverlay();
      try {
        var blob = await fetchImage(deckID, "cost");
        closeLoader();
        showImage(deckID, blob);
      } catch (error) {
        closeLoader();
        flash("Failed to generate the image.", "danger");
      } finally { window.__hellbreakDeckImageBusy = false; }
    }
  };
})();
