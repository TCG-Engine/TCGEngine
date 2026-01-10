  <head>

    <script src="./Core/UILibraries.js"></script>
    <script src="./Core/MZRearrangePopup.js"></script>
    <link rel="stylesheet" type="text/css" href="./Core/Styles/ScreenAnimations.css">
    <?php
    // Include GudnakSim-specific grassland background
    $tempFolderPath = isset($_GET['folderPath']) ? $_GET['folderPath'] : '';
    if ($tempFolderPath === 'GudnakSim') {
      echo '<link rel="stylesheet" type="text/css" href="./GudnakSim/Styles/GrasslandBackground.css">';
      echo '<script src="./GudnakSim/Styles/GrasslandBackground.js"></script>';
    }
    ?>

    <style>
      @keyframes move {
        from {margin-top: 0;}
        to {margin-top: -50px;}
      }

      .draggable {
      }

      .droppable {
          border: 3px dashed #ffff00 !important;
      }
      
      <?php

        error_reporting(E_ALL);

        include './Core/HTTPLibraries.php';
        //We should always have a player ID as a URL parameter
        $folderPath = TryGet("folderPath", "");
        if($folderPath == "") {
          echo ("Invalid folder path.");
          exit;
        }

        // Include GudnakSim-specific grassland background
        if ($folderPath === 'GudnakSim') {
          echo '<link rel="stylesheet" type="text/css" href="./GudnakSim/Styles/GrasslandBackground.css">';
          echo '<script src="./GudnakSim/Styles/GrasslandBackground.js"></script>';
        }

        // Define theme-specific colors based on folder path
        if ($folderPath === "GudnakSim") {
          $primaryBg = "#2c392c00";      // Grass green
          $secondaryBg = "#2c392c00";    // Grass green
          $borderColor = "transparent"; // No border
          $borderWidth = "0px";
          $accentBg = "#5AAD4D";       // Darker grass green
          $scrollbarBg = "#6BBF59";
          $scrollbarAccent = "#5AAD4D";
          $textColor = "#ffffff";      // White text for contrast
        } else {
          // Default grey theme
          $primaryBg = "#1e1e1e";      // Dark grey
          $secondaryBg = "#2a2a2a";    // Slightly lighter grey
          $borderColor = "#3a3a3a";    // Medium grey
          $borderWidth = "2px";
          $accentBg = "#333333";       // Dark grey
          $scrollbarBg = "#1e1e1e";
          $scrollbarAccent = "#5a5a5a";
          $textColor = "#ffffff";      // White text
        }
      ?>
      
        .panelTab {
          transition: background-color 0.3s, transform 0.3s, color 0.3s;
          background-color: <?php echo $primaryBg; ?>;
          border: <?php echo $borderWidth; ?> solid <?php echo $borderColor; ?>;
          border-radius: 8px;
          margin: 3px 3px;
          padding: 4px;
          font-family: 'Roboto', sans-serif; /* Modern font */
        }

        .panelTab:hover {
          background-color: <?php echo $secondaryBg; ?>;
          color: <?php echo $textColor; ?>;
          transform: scale(1.05);
        }

        .panelTab:active {
          background-color: <?php echo $accentBg; ?>;
          transform: scale(0.95);
          color: <?php echo $textColor; ?>;
        }

        /* Custom scrollbar styles */
        ::-webkit-scrollbar-thumb:hover {
          background: <?php echo $borderColor; ?>;
        }
        ::-webkit-scrollbar-thumb {
          border-radius: 10px;
          background: <?php echo $scrollbarAccent; ?>;
          border: 2px solid <?php echo $scrollbarBg; ?>;
        }
        ::-webkit-scrollbar-track {
          border-radius: 10px;
          background: <?php echo $scrollbarBg; ?>;
        }
        ::-webkit-scrollbar {
          width: 12px;
        }

        .filterBar {
          background-color: <?php echo $secondaryBg; ?>;
          border: <?php echo $borderWidth; ?> solid <?php echo $borderColor; ?>;
          border-radius: 8px;
          font-family: 'Roboto', sans-serif; /* Modern font */
          color: <?php echo $textColor; ?>;
        }

        .stuffParent {
          position:relative;
          top: 0px;
          bottom: 0px;
          left: 0px;
          right: 0px;
          width: 100%;
          height: 100%;
        }

        .stuff {
          position: absolute;
          top: 4px;
          bottom: 4px;
          left: 4px;
          right: 4px;
        }

        .myStuff {
          background-color: <?php echo ($primaryBg); ?>;
          border: <?php echo $borderWidth; ?> solid <?php echo ($folderPath === "GudnakSim" ? "transparent" : "#5a5a5a"); ?>;
          border-radius: 8px;
          font-family: 'Roboto', sans-serif; /* Modern font */
          color: <?php echo $textColor; ?>;
        }

        .myStuffWrapper {
          background-color: <?php echo ($secondaryBg); ?>;
        }

        .theirStuff {
          background-color: <?php echo ($secondaryBg); ?>;
        }

        .theirStuffWrapper {
          background-color: <?php echo ($secondaryBg); ?>;
        }
        </style>

    <?php

    $gameName = TryGet("gameName", "");
    if (!IsGameNameValid($gameName)) {
      echo ("Invalid game name.");
      exit;
    }
    $playerID = TryGet("playerID", 3);
    if (!is_numeric($playerID)) {
      echo ("Invalid player ID.");
      exit;
    }

    if (!file_exists("./" . $folderPath . "/Games/" . $gameName . "/")) {
      echo ("Game does not exist");
      exit;
    }

    session_start();
    if ($playerID == 1 && isset($_SESSION["p1AuthKey"])) $authKey = $_SESSION["p1AuthKey"];
    else if ($playerID == 2 && isset($_SESSION["p2AuthKey"])) $authKey = $_SESSION["p2AuthKey"];
    else $authKey = TryGet("authKey", "");
    session_write_close();

    if(($playerID == 1 || $playerID == 2) && $authKey == "")
    {
      if(isset($_COOKIE["lastAuthKey"])) $authKey = $_COOKIE["lastAuthKey"];
    }

    include "./" . $folderPath . "/ZoneClasses.php";
    include "./" . $folderPath . "/ZoneAccessors.php";
    include "./" . $folderPath . "/GamestateParser.php";
    include "./Core/UILibraries.php";
    include "./Core/Constants.php";
    include_once "./AccountFiles/AccountSessionAPI.php";
    include_once "./Assets/patreon-php-master/src/PatreonDictionary.php";
    include_once "./Assets/patreon-php-master/src/PatreonLibraries.php";

    ParseGamestate("./" . $folderPath . "/");

    function IsDarkMode() { return false; }
    function IsMuted() { return false; }
    function AreAnimationsDisabled() { return false; }
    function IsChatMuted() { return false; }

    /*
    if ($currentPlayer == $playerID) {
      $icon = "ready.png";
    } else {
      $icon = "notReady.png";
    }
    echo '<link id="icon" rel="shortcut icon" type="image/png" href="./Images/' . $icon . '"/>';
    */
    $darkMode = IsDarkMode($playerID);

    if ($darkMode) $backgroundColor = "rgba(20,20,20,0.70)";
    else $backgroundColor = "rgba(255,255,255,0.70)";

    $borderColor = ($darkMode ? "#DDD" : "#1a1a1a");

    $assetPath = function_exists('GetAssetReflectionPath') ? GetAssetReflectionPath($folderPath) : $folderPath;
    $dictionaryFiles = glob("./" . $assetPath . "/GeneratedCode/GeneratedCardDictionaries*.js");
    $generateFilename = $dictionaryFiles[0];
    $lastSlashPos = strrpos($generateFilename, '/');
    if ($lastSlashPos !== false) {
      $generateFilename = substr($generateFilename, $lastSlashPos + 1);
    }
    echo '<script src="./' . $assetPath . '/GeneratedCode/' . $generateFilename . '"></script>';

    ?>

    <head>
      <link rel="icon" type="image/png" href="/TCGEngine/Assets/Images/<?php if($folderPath == "SWUDeck") echo('blueDiamond'); else if($folderPath == "SoulMastersDB") echo('icons/soulMastersIcon'); ?>.png">
      <meta charset="utf-8">
      <title><?php echo($folderPath); ?></title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
      <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Gemunu+Libre:wght@200..800&display=swap" rel="stylesheet">
    </head>

    <script>
      var cardSize = window.innerWidth / 16;
      //Note: 96 = Card Size

    </script>

    <script src="./Core/jsInclude.js"></script>
    <script src="./<?php
      $generateFilename = glob("./" . $folderPath . "/GeneratedUI*.js")[0];
      $lastSlashPos = strrpos($generateFilename, '/');
      if ($lastSlashPos !== false) {
        $generateFilename = substr($generateFilename, $lastSlashPos + 1);
      }
      echo("$folderPath/$generateFilename");
      ?>">

    </script>

    <style>
      :root {
        <?php if (IsDarkMode($playerID)) echo ("color-scheme: dark;");
        else echo ("color-scheme: light;");

        ?>
      }
    </style>

  </head>

  <body onkeydown='Hotkeys(event)' onload='OnLoadCallback(<?php echo (filemtime("./" . $folderPath . "/Games/" . $gameName . "/Gamestate.txt")); ?>)'>

    <?php echo (CreatePopup("inactivityWarningPopup", [], 0, 0, "⚠️ Inactivity Warning ⚠️", 1, "", "", true, true, "Interact with the screen in the next 30 seconds or you could be kicked for inactivity.")); ?>
    <?php echo (CreatePopup("inactivePopup", [], 0, 0, "⚠️ You are Inactive ⚠️", 1, "", "", true, true, "You are inactive. Your opponent is able to claim victory. Interact with the screen to clear this.")); ?>

    <script>
      var IDLE_TIMEOUT = 30; //seconds
      var _idleSecondsCounter = 0;
      var _idleState = 0; //0 = not idle, 1 = idle warning, 2 = idle
      var _lastUpdate = 0;

      var activityFunction = function() {
        var oldIdleState = _idleState;
        _idleSecondsCounter = 0;
        _idleState = 0;
        var inactivityPopup = document.getElementById('inactivityWarningPopup');
        if (inactivityPopup) inactivityPopup.style.display = "none";
        var inactivePopup = document.getElementById('inactivePopup');
        if (inactivePopup) inactivePopup.style.display = "none";
        if (oldIdleState == 2) SubmitInput("100005", "");
      };

      document.onclick = activityFunction;

      document.onmousemove = activityFunction;

      document.onkeydown = activityFunction;

      window.setInterval(CheckIdleTime, 1000);

      function CheckIdleTime() {
        if (document.getElementById("iconHolder") == null || document.getElementById("iconHolder").innerText != "ready.png") return;
        _idleSecondsCounter++;
        if (_idleSecondsCounter >= IDLE_TIMEOUT) {
          if (_idleState == 0) {
            _idleState = 1;
            _idleSecondsCounter = 0;
            var inactivityPopup = document.getElementById('inactivityWarningPopup');
            if (inactivityPopup) inactivityPopup.style.display = "inline";
          } else if (_idleState == 1) {
            _idleState = 2;
            var inactivityPopup = document.getElementById('inactivityWarningPopup');
            if (inactivityPopup) inactivityPopup.style.display = "none";
            var inactivePopup = document.getElementById('inactivePopup');
            if (inactivePopup) inactivePopup.style.display = "inline";
            SubmitInput("100006", "");
          }
        }
      }
    </script>
<!--
    <audio id="yourTurnSound" src="../Assets/prioritySound.wav"></audio>
-->
    <script>
      function reload() {
        CheckReloadNeeded(0);
      }

      function CheckReloadNeeded(lastUpdate) {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            if (this.responseText == "NaN") {} //Do nothing, game is invalid
            else if(this.responseText == "KEEPALIVE") {
              if(_lastUpdate != "NaN") CheckReloadNeeded(_lastUpdate);
            } else if (this.responseText.split("REMATCH")[0] == "1234") {
              location.replace('GameLobby.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&authKey=<?php echo ($authKey); ?>');
            } else {
              HideCardDetail();
              var responseArr = this.responseText.split("<~>");
              var update = parseInt(responseArr[0]);
              if (update != "NaN") CheckReloadNeeded(update);
              else {
                _lastUpdate = "NaN";
                return;
              }
              if(update <= _lastUpdate) return;
              //An update was received, begin processing it

              _lastUpdate = update;
              //Handle events; they may need a delay in the card rendering
              //var events = responseArr[1];
              var events = "";//TODO: Fix this
              var eventsArr = events.split("~");
              if(<?php echo(AreAnimationsDisabled($playerID) ? 'true' : 'false');?>) eventsArr = [];
              if(eventsArr.length > 0) {
                var popup = document.getElementById("CHOOSEMULTIZONE");
                if(!popup) popup = document.getElementById("MAYCHOOSEMULTIZONE");
                if(popup) popup.style.display = "none";
                var timeoutAmount = 0;
                for(var i=0; i<eventsArr.length; i+=2) {
                  var eventType = eventsArr[i];//DAMAGE
                  if(eventType == "DAMAGE") {
                    var eventArr = eventsArr[i+1].split("!");
                    //Now do the animation
                    if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                    else var element = document.getElementById("unique-" + eventArr[0]);
                    if(!!element) {
                      if(timeoutAmount < 500) timeoutAmount = 500;
                      element.innerHTML += "<div class='dmg-animation dmg-animation-a'><div class='dmg-animation-a-inner'></div></div>";
                      element.innerHTML += "<div class='dmg-animation-a-label'><div class='dmg-animation-a-label-inner'>-" + eventArr[1] + "</div></div>";
                    }
                  } else if(eventType == "RESTORE") {
                    var eventArr = eventsArr[i+1].split("!");
                    //Now do the animation
                    if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                    else var element = document.getElementById("unique-" + eventArr[0]);
                    if(!!element) {
                      if(timeoutAmount < 500) timeoutAmount = 500;
                      element.innerHTML += "<div class='dmg-animation' style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 8px); padding: 0 2px; border-radius:12px; background-color:rgba(95,167,219,0.5); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'></div></div>";
                      element.innerHTML += "<div style='position:absolute; text-align:center; animation-name: move; animation-duration: 0.6s; font-size:34px; font-weight: 600; text-shadow: 1px 1px 0px rgba(0, 0, 0, 0.60); top:0px; left:0px; width:100%; height:100%; background-color:rgba(0,0,0,0); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'>+" + eventArr[1] + "</div></div>";
                    }
                  } else if(eventType == "EXHAUST") {
                    var eventArr = eventsArr[i+1].split("!");
                    //Now do the animation
                    if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                    else var element = document.getElementById("unique-" + eventArr[0]);
                    const timing = {
                        duration: 60,
                        iterations: 1,
                      };
                      const exhaustAnimation = [
                      { transform: "rotate(0deg) scale(1)" },
                      { transform: "rotate(5deg) scale(1)" },
                    ];
                    if(!!element) {
                      if(timeoutAmount < 60) timeoutAmount = 60;
                      element.animate(exhaustAnimation,timing);
                      element.innerHTML += "<div style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 16px); padding: 0 2px; border-radius:12px; background-color:rgba(0,0,0,0.5);'><div style='width:100%; height:100%:'></div></div>";
                      element.className += "exhausted";
                    }
                  }
                }
                if(timeoutAmount > 0) setTimeout(RenderUpdate, timeoutAmount, responseArr);
                else RenderUpdate(responseArr);
              }
            }
          }
        };
        var dimensions = "&windowWidth=" + window.innerWidth + "&windowHeight=" + window.innerHeight;
        var lcpEl = document.getElementById("lastCurrentPlayer");
        var lastCurrentPlayer = "&lastCurrentPlayer=" + (!lcpEl ? "0" : lcpEl.innerHTML);
        if (lastUpdate == "NaN") window.location.replace("https://www.petranaki.net/Arena/MainMenu.php");
        else xmlhttp.open("GET", "./<?php echo($folderPath);?>/GetNextTurn.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&lastUpdate=" + lastUpdate + "&authKey=<?php echo ($authKey); ?>" + dimensions, true);
        xmlhttp.send();
      }

      function RenderUpdate(responseArr) {
        var newHTML = "";
        var playerID = <?php echo($playerID); ?>;
        <?php include "./" . $folderPath . "/NextTurnRender.php"; ?>
        UpdateTurnPlayerMiasma();
      }

    </script>

    <?php
    // Display hidden elements and Chat UI
    ?>
    <div id='popupContainer'></div>
    <div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>
    <div id='mainDiv' style='position:fixed; z-index:0; left:0; top:0; width:100%; height:100%;'>
    <!--
    <div id='chatbox' style='z-index:40; position:fixed; bottom:20px; right:18px; display:flex;'>
        <?php if ($playerID != 3 && !IsChatMuted()): ?>
            <input id='chatText'
                  style='background: black; color: white; font-size:16px; font-family:barlow; margin-left: 8px; height: 32px; border: 1px solid #454545; border-radius: 5px 0 0 5px;'
                  type='text'
                  name='chatText'
                  value=''
                  autocomplete='off'
                  onkeypress='ChatKey(event)'>
            <button style='border: 1px solid #454545; border-radius: 0 5px 5px 0; width:55px; height:32px; color: white; margin: 0 0 0 -1px; padding: 0 5px; font-size:16px; font-weight:600; box-shadow: none;'
                    onclick='SubmitChat()'>Chat
            </button>
            <button title='Disable Chat'
                    <?= ProcessInputLink($playerID, 26, $SET_MuteChat . "-1", fullRefresh:true); ?>
                    style='border: 1px solid #454545; color: #1a1a1a; padding: 0; box-shadow: none;'>
                <img style='height:16px; width:16px; float:left; margin: 7px;' src='./Images/disable.png' />
            </button>
        <?php else: ?>
            <button title='Re-enable Chat'
                    <?= ProcessInputLink($playerID, 26, $SET_MuteChat . "-0", fullRefresh:true); ?>
                    style='border: 1px solid #454545; width: 100%; padding: 0 0 4px 0; height: 32px; font: inherit; box-shadow: none;'>
                ⌨️ Re-enable Chat
            </button>
        <?php endif; ?>
    </div>
        -->

    <?php include "./" . $folderPath . "/InitialLayout.php"; ?>
    </div>


    <input type='hidden' id='gameName' value='<?= htmlspecialchars($gameName, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='playerID' value='<?= htmlspecialchars($playerID, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='authKey' value='<?= htmlspecialchars($authKey, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='folderPath' value='<?= htmlspecialchars($folderPath, ENT_QUOTES, 'UTF-8'); ?>'>


  </body>
