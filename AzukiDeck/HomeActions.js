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

  function deckLink(deckID) {
    var code = (window.AZUKI_DECK_CODES || {})[deckID];
    return code
      ? window.location.origin + "/deck/" + code
      : window.location.origin + "/TCGEngine/NextTurn.php?gameName=" + encodeURIComponent(deckID) + "&playerID=1&folderPath=AzukiDeck";
  }

  function pngClipboardBlob(sourceBlob) {
    if (sourceBlob.type === "image/png") return Promise.resolve(sourceBlob);
    return new Promise(function (resolve, reject) {
      var sourceURL = URL.createObjectURL(sourceBlob);
      var sourceImage = new Image();
      sourceImage.onload = function () {
        var canvas = document.createElement("canvas");
        canvas.width = sourceImage.naturalWidth;
        canvas.height = sourceImage.naturalHeight;
        var context = canvas.getContext("2d");
        if (!context) {
          URL.revokeObjectURL(sourceURL);
          reject(new Error("Canvas is unavailable"));
          return;
        }
        context.drawImage(sourceImage, 0, 0);
        URL.revokeObjectURL(sourceURL);
        canvas.toBlob(function (pngBlob) {
          if (pngBlob) resolve(pngBlob);
          else reject(new Error("PNG conversion failed"));
        }, "image/png");
      };
      sourceImage.onerror = function () {
        URL.revokeObjectURL(sourceURL);
        reject(new Error("Image decode failed"));
      };
      sourceImage.src = sourceURL;
    });
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
    sort.innerHTML = '<option value="cost">Group by cost</option><option value="type">Group by type</option><option value="element">Group by element</option><option value="name">Sort by name</option>';
    sort.setAttribute("aria-label", "Deck image sorting");
    var status = document.createElement("span");
    status.style.cssText = "min-width:70px;color:#a9bed1;font-size:13px";
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
        flash("Failed to generate the image.");
      } finally {
        sort.disabled = false;
        image.style.opacity = "1";
      }
    };
    var download = document.createElement("button");
    download.textContent = "Download";
    download.onclick = function () {
      var link = document.createElement("a");
      var extension = blob.type === "image/png" ? "png" : "webp";
      link.href = objectURL;
      link.download = "azuki-deck-" + deckID + "." + extension;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };
    var copy = document.createElement("button");
    copy.textContent = "Copy image";
    copy.onclick = async function () {
      if (!window.ClipboardItem || !navigator.clipboard || !navigator.clipboard.write) {
        flash("Image copying is not supported in this browser.");
        return;
      }
      copy.disabled = true;
      try {
        var clipboardBlob = await pngClipboardBlob(blob);
        await navigator.clipboard.write([new ClipboardItem({ "image/png": clipboardBlob })]);
        flash("Image copied!");
      } catch (error) {
        flash("Image copying is not supported here.");
      } finally {
        copy.disabled = false;
      }
    };
    var copyLinkButton = document.createElement("button");
    copyLinkButton.textContent = "Copy deck link";
    copyLinkButton.onclick = function () {
      copyText(deckLink(deckID))
        .then(function () { flash("Link copied!"); })
        .catch(function () { flash("Could not copy the link."); });
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
    controls.appendChild(status);
    controls.appendChild(download);
    controls.appendChild(copy);
    controls.appendChild(copyLinkButton);
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
      copyText(deckLink(deckID)).then(function () { flash("Link copied!"); }).catch(function () { flash("Could not copy the link."); });
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
      StyledConfirm("Are you sure you want to delete this deck?", { title: 'Delete deck', danger: true, confirmLabel: 'Delete' }).then(function (ok) {
        if (!ok) return;
        request("/TCGEngine/AccountFiles/DeleteAsset.php?assetID=" + encodeURIComponent(deckID) + "&assetType=1", function () {
          window.location.reload();
        });
      });
    }
  };
})();
