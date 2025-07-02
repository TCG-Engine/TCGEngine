<?php
include_once './MenuBar.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../Database/ConnectionManager.php';
include_once '../SoulMastersDB/GeneratedCode/GeneratedCardDictionaries.php';

include_once 'Header.php';

?>
<style>
.sciFiScroll::-webkit-scrollbar {
  width: 12px;
}

/* Ensure the track itself has rounded corners */
.sciFiScroll::-webkit-scrollbar-track {
  background: #000022;
  box-shadow: inset 0 0 5px #000;
  border-radius: 8px; /* Ensure rounded edges */
  overflow: hidden; /* Prevents clipping */
}
/* Modify the scrollbar thumb */
.sciFiScroll::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #2a4b8d, #001f4d);
  border-radius: 12px; /* Increase for more rounded effect */
  box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.5);
}

/* Smooth animation for hover */
.sciFiScroll::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #2a4b8d, #3a5b9d);
}

/* Optional: Handle the scrollbar corners */
.sciFiScroll::-webkit-scrollbar-corner {
  background: transparent; /* Prevents awkward edges */
}
</style>

<?php
function LoadDecks() {
  $folderPath = "SoulMastersDB";
  if(!IsUserLoggedIn()) {
    echo("Log in to view your decks");
    return;
  }
  $decks = GetDecksByUserID(LoggedInUser());
  echo("<div class='sciFiScroll' style='overflow-x: auto; overflow-y:auto; max-height: calc(100vh - 310px);'>");
  echo("<table style='width: 100%; border-collapse: collapse;'>");
  $favoriteDecks = "";
  $otherDecks = "";
  foreach ($decks as $deck) {
    if($deck["assetStatus"] == 1) { // Check if it's deleted
      $thisDeck = "";
      $title = $deck["assetName"] != "" ? $deck["assetName"] : "Deck #" . $deck["assetIdentifier"] . " (Click to rename)";
      $thisDeck .= "<tr onclick=\"window.location='../NextTurn.php?gameName=" . $deck["assetIdentifier"] . "&playerID=1&folderPath=SoulMastersDB';\" onmouseover=\"this.style.boxShadow='0 0 10px 5px rgba(51, 204, 255, 0.6)'; this.style.transform='scaleY(1.015)';\" onmouseout=\"this.style.boxShadow='none'; this.style.transform='none';\" style='cursor: pointer; transition: all 0.1s ease-in-out;'>";
      $id = "deck" . $deck["assetIdentifier"] . "Title";
      $thisDeck .= "<td style='padding: 3px;'>";
      if (!empty($deck["keyIndicator1"])) {
        $thisDeck .= "<img src='../SoulMastersDB/concat/" . $deck["keyIndicator1"] . ".webp' style='height: 80px;' title='" . CardName($deck["keyIndicator1"]) . "' />";
      } else {
        $thisDeck .= "No Commander";
      }
      $thisDeck .= "</td>";
      $thisDeck .= "<td style='padding: 3px;'><span id='" . $id . "'><span onclick='event.stopPropagation(); DeckNameClick(\"" . $id . "\")'>" . $title . "</span></span></td>";
      /*
      $thisDeck .= "<td title='Stats' style='padding: 3px;'><button onclick='event.stopPropagation(); window.location.href=\"../$folderPath/DeckStats.php?gameName=" . $deck["assetIdentifier"] . "\"'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-bar-chart' viewBox='0 0 16 16'>
    <path d='M4 11H2v3h2zm5-4H7v7h2zm5-5v12h-2V2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z'/>
    </svg>";
      $thisDeck .= "</button></td>";
      */
      $thisDeck .= "<td title='Copy Link' style='padding: 3px;'><button onclick='event.stopPropagation(); showCopyOptions(\"" . $deck["assetIdentifier"] . "\", event)'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-clipboard2-check' viewBox='0 0 16 16'>
      <path d='M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z'/>
      <path d='M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z'/>
      <path d='M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z'/>
      </svg>";
      $thisDeck .= "</button></td>";
      if (!is_null($deck["assetSource"]) && !is_null($deck["assetSourceID"])) {
      $thisDeck .= "<td title='Refresh' style='padding: 3px;'><button onclick='event.stopPropagation(); RefreshDeck(\"" . $deck["assetIdentifier"] . "\", \"" . $deck["assetSourceID"] . "\", event)'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-arrow-clockwise' viewBox='0 0 16 16'>
      <path fill-rule='evenodd' d='M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z'/>
      <path d='M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466'/>
      </svg>";
      $thisDeck .= "</button></td>";
      } else {
        $thisDeck .= "<td title='Refresh' style='padding: 3px;'><button style='background-color: grey;' disabled onclick='event.stopPropagation();'>";
        $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-arrow-clockwise' viewBox='0 0 16 16'>
        <path fill-rule='evenodd' d='M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z'/>
        <path d='M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466'/>
        </svg>";
        $thisDeck .= "</button></td>";
      }
      if($deck["assetFolder"] == 0) {
        $thisDeck .= "<td title='Favorite' style='padding: 3px;'><button onclick='event.stopPropagation(); MoveDeck(\"" . $id . "\", 1)'>";
        $thisDeck .= "<img src='../Assets/Icons/heart.svg' width='16' height='16' alt='Favorite' style='filter: invert(100%);' />";
        $thisDeck .= "</button></td>";
      } else if($deck["assetFolder"] == 1) {
        $thisDeck .= "<td title='Favorite' style='padding: 3px;'><button onclick='event.stopPropagation(); MoveDeck(\"" . $id . "\", 0)'>";
        $thisDeck .= "<img src='../Assets/Icons/heart-fill.svg' width='16' height='16' alt='Favorite' style='filter: invert(100%);' />";
        $thisDeck .= "</button></td>";
      }

      $thisDeck .= "<td title='Delete' style='padding: 3px;'><button onclick='event.stopPropagation(); DeleteDeck(\"" . $id . "\")'>";
      $thisDeck .= "<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-trash3' viewBox='0 0 16 16'>
    <path d='M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5'/>
    <path d='M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1Z'/>
    <path d='M12.958 3l-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5ZM2.565 4.5a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L2.095 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47ZM8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5'/>
    </svg>";
      $thisDeck .= "</button></td>";
      $thisDeck .= "</tr>";
      if($deck["assetFolder"] == 0) $otherDecks .= $thisDeck;
      else $favoriteDecks .= $thisDeck;
    }
  }
  echo($favoriteDecks);
  echo($otherDecks);
  echo("</table>");
  echo("</div>");
}

  function LoadPatreonDecks($patreonID) {
    $folderPath = "SoulMastersDB";
    $decks = GetDecksByPatreon($patreonID);
    echo("<div class='sciFiScroll' style='overflow-x: auto; max-height: calc(100vh - 310px);'>");
    echo("<table style='width: 100%; border-collapse: collapse;'>");
    foreach ($decks as $deck) {
      if($deck["assetStatus"] == 1) { // Check if it's deleted
      $title = $deck["assetName"] != "" ? $deck["assetName"] : "Deck #" . $deck["assetIdentifier"];
      echo("<tr style='border-bottom: 1px solid #002249; padding: 3px;'>");
      $id = "deck" . $deck["assetIdentifier"] . "Title";
      echo("<td style='padding: 3px;'><span id='" . $id . "'>" . $title . "</span></td>");
      echo("<td title='View' style='padding: 3px;'><button onclick=\"window.location.href='../NextTurn.php?gameName=" . $deck["assetIdentifier"] . "&playerID=1&folderPath=SoulMastersDB'\">");
      echo("<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-eye' viewBox='0 0 16 16'><path d='M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM8 3a5 5 0 0 1 0 10A5 5 0 0 1 8 3z'/><path d='M8 5a3 3 0 1 0 0 6A3 3 0 0 0 8 5z'/></svg>");
      echo("</button></td>");
      echo("<td title='Stats' style='padding: 3px;'><button onclick='window.location.href=\"../$folderPath/DeckStats.php?gameName=" . $deck["assetIdentifier"] . "\"'>");
      echo("<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-bar-chart' viewBox='0 0 16 16'>
      <path d='M4 11H2v3h2zm5-4H7v7h2zm5-5v12h-2V2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z'/>
      </svg>");
      echo("</button></td>");
      echo("<td title='Copy Link' style='padding: 3px;'><button onclick='CopyDeckLink(\"" . $deck["assetIdentifier"] . "\", event)'>");
      echo("<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-clipboard2-check' viewBox='0 0 16 16'>
      <path d='M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z'/>
      <path d='M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z'/>
      <path d='M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z'/>
    </svg>");
      echo("</button></td>");
      echo("</tr>");
      }
    }
    echo("</table>");
    echo("</div>");
  }

  function GetDecksByUserID($userID) {
    $conn = GetLocalMySQLConnection();
    $sql = "SELECT * FROM ownership 
            WHERE assetType = 1 
            AND (assetOwner = ? 
                 OR assetVisibility = (1000 + COALESCE((SELECT teamID FROM users WHERE usersId = ?), 0)))";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $decks = [];
    while ($row = $result->fetch_assoc()) {
      $decks[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $decks;
  }

  function GetDecksByPatreon($patreonID) {
    $conn = GetLocalMySQLConnection();
    $sql = "SELECT * FROM ownership WHERE assetVisibility = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patreonID);
    $stmt->execute();
    $result = $stmt->get_result();
    $decks = [];
    while ($row = $result->fetch_assoc()) {
      $decks[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $decks;
  }

  ?>
  <script>
    function showFlashMessage(message, event) {
      var flashMessage = document.createElement("div");
      flashMessage.innerText = message;
      flashMessage.style.position = "absolute";
      flashMessage.style.backgroundColor = "#002249"; // Space blue color
      flashMessage.style.color = "#fff";
      flashMessage.style.padding = "10px";
      flashMessage.style.borderRadius = "5px";
      flashMessage.style.boxShadow = "0 0 10px rgba(0, 0, 0, 0.5)";

      document.body.appendChild(flashMessage);

      var rect = event.target.getBoundingClientRect();
      flashMessage.style.top = rect.top - 35 + window.scrollY + "px";
      flashMessage.style.left = rect.left + 30 + window.scrollX + "px";

      setTimeout(function() {
        document.body.removeChild(flashMessage);
      }, 650);
    }

    function RefreshDeck(deckID, assetSourceID, event) {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "../SoulMastersDB/RefreshImport.php?deckID=" + deckID + "&sourceID=" + assetSourceID, true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          showFlashMessage("Deck refreshed successfully!", event);
        }
      };
      xhr.send();
    }

    function showCopyOptions(deckID, event) {
      var optionsMenu = document.createElement("div");
      optionsMenu.style.position = "absolute";
      optionsMenu.style.backgroundColor = "#002249";
      optionsMenu.style.color = "#fff";
      optionsMenu.style.padding = "5px 10px";
      optionsMenu.style.border = "none";
      optionsMenu.style.boxShadow = "0 0 10px 2px #001f4d";
      optionsMenu.style.borderRadius = "4px";
      optionsMenu.style.top = event.clientY + "px";
      optionsMenu.style.left = event.clientX + "px";
      optionsMenu.style.zIndex = "1000";

      var copyLinkBtn = document.createElement("button");
      copyLinkBtn.innerText = "Copy Link";
      copyLinkBtn.style.marginRight = "10px";
      copyLinkBtn.onclick = function(e) {
        e.stopPropagation();
        CopyDeckLink(deckID, event);
        document.body.removeChild(optionsMenu);
      };

      var copyTextButton = document.createElement("button");
      copyTextButton.innerText = "Copy Text";
      copyTextButton.onclick = function(e) {
        e.stopPropagation();
        CopyDeckText(deckID, event);
        document.body.removeChild(optionsMenu);
      };
/*
      var copyJsonBtn = document.createElement("button");
      copyJsonBtn.innerText = "Copy JSON";
      copyJsonBtn.style.marginRight = "10px";
      copyJsonBtn.onclick = function(e) {
        e.stopPropagation();
        CopyDeckJSON(deckID, event);
        document.body.removeChild(optionsMenu);
      };
      
      var copyImageBtn = document.createElement("button");
      copyImageBtn.innerText = "Copy Image";
      copyImageBtn.onclick = function(e) {
        e.stopPropagation();
        CopyDeckImage(deckID, event);
        document.body.removeChild(optionsMenu);
      };
      */

      optionsMenu.appendChild(copyLinkBtn);
      optionsMenu.appendChild(copyTextButton);
      //optionsMenu.appendChild(copyJsonBtn);
      //optionsMenu.appendChild(copyImageBtn);
      document.body.appendChild(optionsMenu);

      document.addEventListener("click", function removeMenu(e) {
        if (document.body.contains(optionsMenu)) {
          document.body.removeChild(optionsMenu);
        }
        document.removeEventListener("click", removeMenu);
      });

      document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
          if (document.body.contains(optionsMenu)) {
            document.body.removeChild(optionsMenu);
          }
        }
      }, { once: true });
    }

    function CopyDeckText(deckID, event) {
      var deckLink = window.location.origin + "/TCGEngine/NextTurn.php?gameName=" + deckID + "&playerID=1&folderPath=SoulMastersDB";
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "../APIs/LoadDeck.php?deckID=" + deckID + "&format=text&folderPath=SoulMastersDB", false);
      xhr.send(null);
      var decktext = xhr.responseText;
      decktext += "\r\n\r\nDeck Link: " + deckLink;
      var tempTextarea = document.createElement("textarea");
      tempTextarea.value = decktext;
      document.body.appendChild(tempTextarea);
      tempTextarea.select();
      document.execCommand("copy");
      document.body.removeChild(tempTextarea);
      showFlashMessage("Text copied!", event);
    }

    function CopyDeckJSON(deckID, event) {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "../APIs/LoadDeck.php?deckID=" + deckID + "&setId=true", true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var deckJSON = JSON.parse(xhr.responseText);
          var tempInput = document.createElement("textarea");
          tempInput.value = JSON.stringify(deckJSON, null, 2);
          document.body.appendChild(tempInput);
          tempInput.select();
          document.execCommand("copy");
          document.body.removeChild(tempInput);
          showFlashMessage("Deck JSON copied!", event);
        }
      };
      xhr.send();
    }

    async function convertBlobToPNG(blob) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = function() {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          canvas.toBlob((pngBlob) => {
            if (pngBlob) {
              resolve(pngBlob);
            } else {
              reject(new Error('Canvas conversion failed'));
            }
          }, 'image/png');
        };
        img.onerror = function(error) {
          reject(new Error('Image load error: ' + error));
        };
        img.src = URL.createObjectURL(blob);
      });
    }

    async function CopyDeckImage(deckID, event) {
      try {
        const response = await fetch(`../SoulMastersDB/CreateImage.php?gameName=${deckID}`);
        if (!response.ok) {
          showFlashMessage("Failed to load image!", event);
          return;
        }
        const blob = await response.blob();

        // If the image is JPEG, convert it to PNG.
        let imageBlob = blob;
        if (blob.type === "image/jpeg") {
          imageBlob = await convertBlobToPNG(blob);
        }

        const clipboardItem = new ClipboardItem({ "image/png": imageBlob });
        await navigator.clipboard.write([clipboardItem]);
        showFlashMessage("Deck image copied!", event);
      } catch (error) {
        console.error("Error copying image:", error);
        showFlashMessage("Failed to copy image!", event);
      }
    }

    function CopyDeckLink(deckID, event) {
      var deckLink = window.location.origin + "/TCGEngine/NextTurn.php?gameName=" + deckID + "&playerID=1&folderPath=SoulMastersDB";
      var tempInput = document.createElement("input");
      tempInput.value = deckLink;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand("copy");
      document.body.removeChild(tempInput);
      showFlashMessage("Link copied!", event);
    }

  function DeckNameClick(id) {
    var currentName = document.getElementById(id).innerText;
    var el = document.getElementById(id);
    el.innerHTML = "<input type='text' id='deckNameInput' value='" + currentName + "' onblur='DeckNameSave(\"" + id + "\")' onkeypress='DeckNameKeypress(\"" + id + "\")' onclick='event.stopPropagation();' />";
    var input = document.getElementById("deckNameInput");
    input.focus();
    input.select();
  }

  function DeckNameKeypress(id) {
    if (event.key === 'Enter') {
      DeckNameSave(id);
    }
  }

  function DeckNameSave(id) {
    var newName = document.getElementById("deckNameInput").value;
    var el = document.getElementById(id);
    el.innerHTML = "<span onclick='DeckNameClick(\"" + id + "\")'>" + newName + "</span>";
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "../AccountFiles/RenameAsset.php?assetID=" + id.replace("deck", "").replace("Title", "") + "&newName=" + encodeURIComponent(newName) + "&assetType=1", true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        console.log("Deck name updated successfully");
      }
    };
    xhr.send();
  }
  function DeleteDeck(id) {
    if (confirm("Are you sure you want to delete this deck?")) {
      var deckID = id.replace("deck", "").replace("Title", "");
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "../AccountFiles/DeleteAsset.php?assetID=" + deckID + "&assetType=1", true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          console.log("Deck deleted successfully");
          location.reload();
        }
      };
      xhr.send();
    }
  }
  function MoveDeck(id, folderID) {
    var deckID = id.replace("deck", "").replace("Title", "");
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "../AccountFiles/MoveAsset.php?assetID=" + deckID + "&assetType=1&folderID=" + folderID, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        console.log("Deck moved successfully");
        location.reload();
      }
    };
    xhr.send();
  }
</script>

<div class="core-wrapper">
<div class="tabs" style="width: 70%; margin: 0 auto; margin-left:20px;">
  <style>
    /* Override the horizontal scrollbar: hide it on load; show on hover */
    .sciFiScroll {
      overflow-x: hidden !important;
    }
    .sciFiScroll:hover {
      overflow-x: auto !important;
    }
    /* Add color change for the selected tab */
    .tab-button {
      background-color: #001f4d;
      color: #fff;
      border: none;
      cursor: pointer;
    }
    .tab-button.active {
      background-color: #2a4b8d;
      color: #fff;
    }
  </style>
  <div class="login container bg-black" style='width:70%;'>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
      <div class="tab-buttons">
        <button class="tab-button active" onclick="switchTab('tab-decks', event)">Decks</button>
        <?php
        /*
        $isKTODPatron = IsPatron("11987758");
        $isRebelResourcePatron = IsPatron("12716027");
        echo("<button style='padding-bottom:8px;' class=\"tab-button\" onclick=\"switchTab('tab-ktod', event)\"><img src='../Assets/Images/logos/KTODLogo.webp' alt='KTOD' style='height: 15px;'></button>");
        //if ($isRebelResourcePatron) echo("<button class=\"tab-button\" onclick=\"switchTab('tab-rebel', event)\">Rebel Resource Decks</button>");
        echo("<button style='margin-left:3px; padding-bottom:8px;' class=\"tab-button\" onclick=\"switchTab('tab-rebel', event)\"><img src='../Assets/Images/logos/RebelResourceLogo.webp' alt='Rebel Resource' style='height: 15px;'></button>");
        echo("<button style='margin-left:3px; padding-bottom:8px;' class=\"tab-button\" onclick=\"switchTab('tab-L8Night', event)\"><img src='../Assets/Images/logos/L8NightBanner.webp' alt='L8 Night Gaming' style='height: 15px; '></button>");
        */
        ?>
      </div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <button id="createDeckButton" onclick="createDeck()" title="Create New Deck" style="font-size: 18px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
          </svg>
        </button>
        <button id="importDeckButton" onclick="importDeck()" title="Import Deck" style="font-size: 18px;">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cloud-arrow-down" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/>
            <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="tab-content-container">
      <div id="tab-decks" class="tab-content" style="display: block;">
        <div><?php LoadDecks(); ?></div>
      </div>
      <!--
      <div id="tab-ktod" class="tab-content" style="display: none;">
        <div>
          <?php
            /*
            if ($isKTODPatron) {
              if (isset($_SESSION["isWokling"]) && $_SESSION["isWokling"]) {
                echo("<h3>Wokling Tier</h3>");
                echo "<a href='https://www.patreon.com/c/ktod/membership' target='_blank'>Upgrade your tier to Kashyyyk Operative or above to see KTOD decks as they are built!</a>";
              } else {
                LoadPatreonDecks("11987758");
              }
            } else {
              echo("<p>Subscribe to the <a href='https://www.patreon.com/c/ktod/membership' target='_blank'>KTOD Kashyyyk+ Tier</a> on Patreon to unlock exclusive access to in-progress decklists!</p>");
            }
              */
          ?>
        </div>
      </div>
  -->
    </div>
  </div>
</div>
<script>
  function switchTab(tabId, event) {
    var tabs = document.getElementsByClassName('tab-content');
    for (var i = 0; i < tabs.length; i++) {
      tabs[i].style.display = 'none';
    }
    document.getElementById(tabId).style.display = 'block';

    var buttons = document.getElementsByClassName('tab-button');
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].classList.remove('active');
    }
    event.target.classList.add('active');
  }
</script>

<script>
  function createDeck() {
    window.location.href = "../SoulMastersDB/CreateDeck.php";
  }
  
  function importDeck() {
    var popup = document.createElement("div");
    popup.id = "importDeckPopup";
    popup.style.position = "fixed";
    popup.style.top = "50%";
    popup.style.left = "50%";
    popup.style.transform = "translate(-50%, -50%)";
    popup.style.backgroundColor = "#002249"; // Darker blue background color
    popup.style.color = "#fff"; // White text color for better contrast
    popup.style.padding = "20px";
    popup.style.boxShadow = "0 0 10px rgba(0, 0, 0, 0.5)";
    popup.style.zIndex = "1000";
    popup.innerHTML = `
      <h3>Import Deck</h3>
      <input type="text" id="deckLinkInput" placeholder="Enter deck link" style="width: 100%; padding: 10px; margin-bottom: 10px;" />
      <button onclick="importDeckLink()" style="padding: 10px 20px; margin-right: 10px;">Import</button>
      <button onclick="closePopup()" style="padding: 10px 20px;">Cancel</button>
    `;
    document.body.appendChild(popup);
    document.getElementById("deckLinkInput").focus();
  }

  function closePopup() {
    var popup = document.getElementById("importDeckPopup");
    if (popup) {
      document.body.removeChild(popup);
    }
  }

  function importDeckLink() {
    var deckLink = document.getElementById("deckLinkInput").value;
    if (deckLink !== "") {
      window.location.href = "../SoulMastersDB/CreateDeck.php?deckLink=" + encodeURIComponent(deckLink);
    } else {
      alert("Enter a deck link to import");
    }
  }
</script>

<div class="flex-padder"></div>
<div class="column-wrapper" style="display: flex; flex-grow: 1;">
  <div class="flex-wrapper" style="flex-grow: 1;">
    <div class="login container bg-black" style="flex-grow: 1;">
      <h2>Welcome to Soul Masters DB!</h2>
      <p class="login-message">Soul Masters DB is a fan-made deck building site for the Soul Masters TCG. </p>
      <p class="login-message">If you have any feedback on the site or are interested in contributing, please let us know on discord!</p>
    </div>
  </div>
</div>

<div class="flex-padder"></div>
</div>

<?php
include_once './Disclaimer.php';
?>