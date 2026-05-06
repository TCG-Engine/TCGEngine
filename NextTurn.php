  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="./Core/UILibraries20260415.js"></script>
    <script src="./Core/CounterRendering.js"></script>
    <script src="./Core/MZRearrangePopup.js"></script>
    <script src="./Core/MZSplitAssignUI.js"></script>
    <script src="./Core/MZModalUI.js"></script>
    <script src="./Core/IconChoiceUI.js"></script>
    <script src="./Core/NumberChooseUI.js"></script>
    <link rel="stylesheet" type="text/css" href="./Core/Styles/ScreenAnimations.css">

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

        // Define theme-specific colors based on folder path
        if ($folderPath === "GudnakSim") {
          $primaryBg = "#3e503fff";      // Grass green
          $secondaryBg = "#3e503fff";    // Grass green
          $borderColor = "transparent"; // No border
          $borderWidth = "0px";
          $accentBg = "#5AAD4D";       // Darker grass green
          $scrollbarBg = "#6BBF59";
          $scrollbarAccent = "#5AAD4D";
          $textColor = "#ffffff";      // White text for contrast
        } else if ($folderPath === "GrandArchiveSim") {
          // Grand Archive: Prismatic Ethereal — deep navy + soft gold
          $primaryBg = "#0d1b2a";      // Midnight blue (Material/Memory void)
          $secondaryBg = "#1a2f4a";    // Deeper navy
          $borderColor = "#c9a84c";    // Soft gold (Archive / high-rarity)
          $borderWidth = "2px";
          $accentBg = "#1d3a5e";       // Steel blue accent
          $scrollbarBg = "#0d1b2a";
          $scrollbarAccent = "#c9a84c"; // Gold scrollbar thumb
          $textColor = "#f0e6c8";      // Warm off-white / parchment
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
          background-color: <?php echo $primaryBg; ?>;
          border: <?php echo $borderWidth; ?> solid <?php echo ($folderPath === "GudnakSim" ? "transparent" : ($folderPath === "GrandArchiveSim" ? $borderColor : "#5a5a5a")); ?>;
          border-radius: 8px;
          font-family: 'Roboto', sans-serif; /* Modern font */
          color: <?php echo $textColor; ?>;
        }

        .myStuffWrapper {
          background-color: <?php echo $secondaryBg; ?>;
        }

        .theirStuff {
          background-color: <?php echo $secondaryBg; ?>;
        }

        .theirStuffWrapper {
          background-color: <?php echo $secondaryBg; ?>;
        }

        /* ---- Mobile layout (≤1000px): deck editor panel reorganization ---- */
        @media (max-width: 1000px) {
          body, html { overflow-x: hidden; }
          .stuffParent { overflow: hidden; }
          /* Tighter radius on mobile for more screen real-estate */
          .myStuff { border-radius: 4px !important; }
          /* Header nav wraps gracefully on small screens */
          .flex-item { flex-wrap: wrap; }
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
    include_once "./Core/RegressionTestFramework.php";
    include "./Core/Constants.php";
    include_once "./AccountFiles/AccountSessionAPI.php";
    include_once "./Assets/patreon-php-master/src/PatreonDictionary.php";
    include_once "./Assets/patreon-php-master/src/PatreonLibraries.php";

    ParseGamestate("./" . $folderPath . "/");

    $showRegressionControls =
      function_exists('IsUserLoggedIn') &&
      IsUserLoggedIn() &&
      function_exists('SupportsRegressionRecording') &&
      SupportsRegressionRecording();
    $regressionRecordingActive = $showRegressionControls ? RegressionIsRecordingActive($folderPath, $gameName) : false;
    $regressionFixtureOptions = $showRegressionControls ? RegressionListFixtureOptions($folderPath) : [];
    $regressionReplayState = $showRegressionControls ? RegressionReadReplayState($folderPath, $gameName) : null;
    $selectedRegressionFixtureSlug = is_array($regressionReplayState) ? strval($regressionReplayState['slug'] ?? '') : '';

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

    // Include GeneratedMacroCount.js for ability count and names (if it exists)
    $macroCountFile = "./" . $assetPath . "/GeneratedCode/GeneratedMacroCount.js";
    if (file_exists($macroCountFile)) {
      echo '<script src="' . $macroCountFile . '"></script>';
    }

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
      function CalculateCardSize() {
        if (window.innerWidth <= 1000) {
          var viewportWidth = (window.visualViewport && window.visualViewport.width) ? window.visualViewport.width : window.innerWidth;
          var mobileColumns = 4;
          var rowPadding = 24; // account for wrapper padding and edge spacing
          var perCardHorizontalSpacing = 4; // two 1px margins + buffer for layout rounding
          var calculatedSize = Math.floor((viewportWidth - rowPadding - (mobileColumns * perCardHorizontalSpacing)) / mobileColumns);
          return Math.max(64, Math.min(80, calculatedSize));
        }
        return window.innerWidth / 16;
      }

      var cardSize = CalculateCardSize();
      window.cardSize = cardSize;

      window.addEventListener('resize', function() {
        window.cardSize = CalculateCardSize();
      });

      // Note: 96 is the historical default card size.

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

    <?php
      // Check if coming from match (for fade-in effect)
      $fromMatch = TryGet("fromMatch", "0");
      if ($fromMatch === "1") {
        echo '<style>
          @keyframes cloudsPartTop {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(-100%); opacity: 0; }
          }
          @keyframes cloudsPartBottom {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(100%); opacity: 0; }
          }
          @keyframes cloudsDrift {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
          }
          .cloud-overlay {
            position: fixed;
            left: 0;
            right: 0;
            height: 55%;
            z-index: 9999;
            pointer-events: none;
            background: linear-gradient(180deg,
              rgba(60, 70, 80, 0.98) 0%,
              rgba(80, 90, 100, 0.95) 30%,
              rgba(100, 110, 120, 0.9) 60%,
              rgba(140, 150, 160, 0.7) 80%,
              rgba(180, 190, 200, 0.3) 95%,
              transparent 100%);
          }
          .cloud-overlay::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
              radial-gradient(ellipse 80% 40% at 20% 60%, rgba(255,255,255,0.15) 0%, transparent 50%),
              radial-gradient(ellipse 60% 30% at 70% 40%, rgba(255,255,255,0.1) 0%, transparent 50%),
              radial-gradient(ellipse 90% 50% at 50% 80%, rgba(255,255,255,0.12) 0%, transparent 50%),
              radial-gradient(ellipse 70% 35% at 30% 30%, rgba(200,210,220,0.1) 0%, transparent 50%);
            animation: cloudsDrift 2s ease-out forwards;
          }
          .cloud-overlay-top {
            top: 0;
            animation: cloudsPartTop 1.2s ease-in-out 0.3s forwards;
          }
          .cloud-overlay-bottom {
            bottom: 0;
            transform: scaleY(-1);
            animation: cloudsPartBottom 1.2s ease-in-out 0.3s forwards;
          }
        </style>
        <div class="cloud-overlay cloud-overlay-top"></div>
        <div class="cloud-overlay cloud-overlay-bottom"></div>
        <script>
          // Remove the fromMatch parameter from the URL and clean up clouds after animation
          document.addEventListener("DOMContentLoaded", function() {
            var url = new URL(window.location);
            url.searchParams.delete("fromMatch");
            window.history.replaceState({}, document.title, url.toString());

            // Remove cloud overlays after animation completes
            setTimeout(function() {
              var clouds = document.querySelectorAll(".cloud-overlay");
              clouds.forEach(function(cloud) { cloud.remove(); });
            }, 1600);
          });
        </script>';
      }
    ?>

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

      var _reloadRequestInFlight = false;

      function QueueReload(lastUpdate) {
        if (lastUpdate == "NaN") return;
        window.setTimeout(function() {
          CheckReloadNeeded(lastUpdate);
        }, 0);
      }

      function ParseFrameAnimations(responseArr) {
        if (!Array.isArray(responseArr) || responseArr.length < 2) return [];
        var raw = responseArr[responseArr.length - 1];
        if (!raw) return [];
        var trimmed = String(raw).trim();
        if (trimmed === "") return [];
        if (trimmed.charAt(0) !== "[" && trimmed.charAt(0) !== "{") return [];
        try {
          var parsed = JSON.parse(trimmed);
          if (Array.isArray(parsed)) return parsed;
          if (parsed && Array.isArray(parsed.animations)) return parsed.animations;
        } catch (e) {}
        return [];
      }

      function NormalizeAnimationTarget(target, perspectivePlayerID) {
        if (!target) return "";
        var mzid = "";
        if (typeof target === "string") mzid = target;
        else if (typeof target === "object") mzid = target.value || target.mzID || "";
        mzid = String(mzid || "").trim();
        if (mzid === "") return "";

        if (mzid.indexOf("p1") === 0 || mzid.indexOf("p2") === 0) {
          var isP1 = mzid.indexOf("p1") === 0;
          var convertedPrefix = (isP1 && perspectivePlayerID === 1) || (!isP1 && perspectivePlayerID === 2) ? "my" : "their";
          mzid = convertedPrefix + mzid.substring(2);
        }

        return mzid;
      }

      function ResolveAnimationTargetElement(animation, perspectivePlayerID) {
        if (!animation) return null;
        var target = animation.target || animation.mzID || "";
        if (target === "P1BASE" || target === "P2BASE") {
          return document.getElementById(target);
        }
        var mzid = NormalizeAnimationTarget(target, perspectivePlayerID);
        if (!mzid) return null;
        var byID = document.getElementById(mzid);
        if (byID) return byID;
        return document.querySelector("[data-mzid='" + mzid + "']");
      }

      function ApplySingleFrameAnimation(animation, perspectivePlayerID) {
        var element = ResolveAnimationTargetElement(animation, perspectivePlayerID);
        if (!element) return 0;

        var durationMs = parseInt(animation.durationMs || 0, 10);
        if (Number.isNaN(durationMs) || durationMs < 0) durationMs = 0;
        var delayMs = parseInt(animation.delayMs || 0, 10);
        if (Number.isNaN(delayMs) || delayMs < 0) delayMs = 0;
        var easing = animation.easing || "ease";
        var totalMs = durationMs + delayMs;

        var type = String(animation.type || "").toUpperCase();
        if (type === "DAMAGE") {
          var amount = animation.amount != null ? animation.amount : "";
          element.innerHTML += "<div class='dmg-animation dmg-animation-a'><div class='dmg-animation-a-inner'></div></div>";
          element.innerHTML += "<div class='dmg-animation-a-label'><div class='dmg-animation-a-label-inner'>-" + amount + "</div></div>";
          if (totalMs < 500) totalMs = 500;
        } else if (type === "RESTORE") {
          var restoreAmount = animation.amount != null ? animation.amount : "";
          element.innerHTML += "<div class='restore-animation restore-animation-a'><div class='restore-animation-a-inner'></div></div>";
          element.innerHTML += "<div class='restore-animation-a-label'><div class='restore-animation-a-label-inner'>+" + restoreAmount + "</div></div>";
          if (totalMs < 500) totalMs = 500;
        } else if (type === "EXHAUST") {
          var exhaustAnimation = [
            { transform: "rotate(0deg) scale(1)" },
            { transform: "rotate(5deg) scale(1)" },
          ];
          var exhaustTiming = { duration: 60, iterations: 1 };
          element.animate(exhaustAnimation, exhaustTiming);
          element.innerHTML += "<div style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 16px); padding: 0 2px; border-radius:12px; background-color:rgba(0,0,0,0.5);'><div style='width:100%; height:100%:'></div></div>";
          element.className += " exhausted";
          if (totalMs < 60) totalMs = 60;
        } else {
          var animationName = animation.name || (animation.params && animation.params.animationName) || "";
          var cssClass = animation.className || (animation.params && animation.params.className) || "";
          if (cssClass) {
            element.classList.add(cssClass);
            if (totalMs > 0) {
              window.setTimeout(function() { element.classList.remove(cssClass); }, totalMs);
            }
          }
          if (animationName) {
            if (durationMs <= 0) durationMs = 300;
            totalMs = durationMs + delayMs;
            element.style.animation = animationName + " " + durationMs + "ms " + easing + " " + delayMs + "ms 1";
            window.setTimeout(function() { element.style.animation = ""; }, totalMs);
          }
        }

        return totalMs;
      }

      function PlayFrameAnimations(animations, perspectivePlayerID) {
        if (!Array.isArray(animations) || animations.length === 0) return 0;

        var popup = document.getElementById("CHOOSEMULTIZONE");
        if (!popup) popup = document.getElementById("MAYCHOOSEMULTIZONE");
        if (popup) popup.style.display = "none";

        var blockingDelayMs = 0;
        for (var i = 0; i < animations.length; ++i) {
          var animation = animations[i];
          var thisDuration = ApplySingleFrameAnimation(animation, perspectivePlayerID);
          var isBlocking = animation && animation.blocking !== false;
          if (isBlocking && thisDuration > blockingDelayMs) blockingDelayMs = thisDuration;
        }

        return blockingDelayMs;
      }

      function CheckReloadNeeded(lastUpdate) {
        if (_reloadRequestInFlight) return;
        _reloadRequestInFlight = true;
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            _reloadRequestInFlight = false;
            var responseText = (this.responseText || "").trim();
            if (responseText == "NaN") {} //Do nothing, game is invalid
            else if(responseText == "" || responseText == "KEEPALIVE") {
              QueueReload(_lastUpdate);
            } else if (responseText.split("REMATCH")[0] == "1234") {
              location.replace('GameLobby.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&authKey=<?php echo ($authKey); ?>');
            } else {
              HideCardDetail();
              var responseArr = responseText.split("<~>");
              var update = parseInt(responseArr[0], 10);
              if (Number.isNaN(update)) {
                _lastUpdate = "NaN";
                return;
              }
              if(update <= _lastUpdate) {
                QueueReload(_lastUpdate);
                return;
              }
              //An update was received, begin processing it

              _lastUpdate = update;
              QueueReload(update);
              var frameAnimations = ParseFrameAnimations(responseArr);
              if(<?php echo(AreAnimationsDisabled($playerID) ? 'true' : 'false');?>) frameAnimations = [];
              var timeoutAmount = PlayFrameAnimations(frameAnimations, <?php echo($playerID); ?>);
              if(timeoutAmount > 0) setTimeout(RenderUpdate, timeoutAmount, responseArr);
              else RenderUpdate(responseArr);
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
        if (typeof ClearSelectionMode === 'function') {
          ClearSelectionMode();
        }
        var newHTML = "";
        var playerID = <?php echo($playerID); ?>;
        <?php include "./" . $folderPath . "/NextTurnRender.php"; ?>
        UpdateTurnPlayerMiasma();
        // Game-over detection: check for GAMEOVER_WINNER set by server-side TriggerGameOver()
        if (!window._gameOverShown && window.DecisionQueueVariablesData) {
          try {
            var _goVars = JSON.parse(window.DecisionQueueVariablesData);
            if (_goVars && _goVars.GAMEOVER_WINNER) {
              var _goWinner = parseInt(_goVars.GAMEOVER_WINNER, 10);
              if (_goWinner > 0 && typeof ShowGameOver === 'function') {
                window._gameOverShown = true;
                ShowGameOver(playerID === _goWinner);
              }
            }
          } catch (e) {}
        }
        if (typeof UpdateVersionDropdown === 'function' && typeof window.myVersionsData !== 'undefined') {
          UpdateVersionDropdown(window.myVersionsData);
        }
      }

    </script>

    <?php
    // Display hidden elements and Chat UI
    ?>
    <?php if ($showRegressionControls): ?>
    <div id="regressionControls" style="position:fixed; top:16px; right:16px; z-index:12000; background:rgba(7, 18, 30, 0.92); color:#f0e6c8; border:1px solid #c9a84c; border-radius:10px; padding:12px; min-width:220px; box-shadow:0 8px 24px rgba(0,0,0,0.35);">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;">
        <div style="font-weight:700;">Regression Tools</div>
        <button
          type="button"
          id="regressionToggle"
          onclick="toggleRegressionControls()"
          aria-expanded="true"
          title="Collapse regression tools"
          style="padding:2px 8px; font-size:14px; line-height:1; cursor:pointer;"
        >-</button>
      </div>
      <div id="regressionControlsBody">
      <div style="font-size:12px; margin-bottom:10px;">Status: <span id="regressionStatus"><?= $regressionRecordingActive ? 'Recording' : 'Idle'; ?></span></div>
      <?php if (!empty($regressionFixtureOptions)): ?>
      <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:10px;">
        <label for="regressionFixtureSelect" style="font-size:12px;">Fixture</label>
        <select id="regressionFixtureSelect" style="padding:6px 8px; max-width:260px;" onchange="persistRegressionFixtureSelection()">
          <?php foreach ($regressionFixtureOptions as $fixtureOption): ?>
          <option value="<?= htmlspecialchars($fixtureOption['slug'], ENT_QUOTES, 'UTF-8'); ?>"<?= $selectedRegressionFixtureSlug === $fixtureOption['slug'] ? ' selected' : ''; ?>><?= htmlspecialchars($fixtureOption['name'], ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" onclick="loadRegressionFixtureInitialState()" style="padding:6px 10px;">Load Initial State</button>
        <button type="button" onclick="replayRegressionFixture()" style="padding:6px 10px;">Replay Fixture Actions</button>
        <button type="button" id="replayRegressionNextButton" onclick="replayRegressionFixtureNextAction()" style="padding:6px 10px;">Replay Next Action</button>
        <div id="regressionReplaySummary" style="font-size:12px; line-height:1.35; white-space:pre-line; opacity:0.92;"></div>
      </div>
      <?php endif; ?>
      <div style="display:flex; flex-direction:column; gap:6px;">
        <button type="button" onclick="startRegressionRecording()" style="padding:6px 10px;">Start Recording</button>
        <button type="button" onclick="stopRegressionRecording()" style="padding:6px 10px;">Stop Recording</button>
        <button type="button" onclick="addRegressionAssertion()" style="padding:6px 10px;">Add Assertion</button>
        <button type="button" onclick="saveRegressionFixture()" style="padding:6px 10px;">Save Test</button>
        <button type="button" onclick="rerecordRegressionFixture()" style="padding:6px 10px;">Re-record Selected</button>
        <div style="display:flex; gap:4px; align-items:center; margin-top:4px;">
          <input type="text" id="regressionCardIdInput" placeholder="Card ID" style="flex:1; padding:5px 7px; font-size:12px; min-width:0;" />
          <button type="button" onclick="linkCardToFixture()" style="padding:5px 8px; white-space:nowrap;">Link Card</button>
        </div>
      </div>
      </div>
    </div>
    <script>
      function applyRegressionControlsCollapsedState(collapsed) {
        var body = document.getElementById("regressionControlsBody");
        var toggle = document.getElementById("regressionToggle");
        var panel = document.getElementById("regressionControls");
        if (!body || !toggle || !panel) return;
        body.style.display = collapsed ? "none" : "block";
        toggle.textContent = collapsed ? "+" : "-";
        toggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
        toggle.setAttribute("title", collapsed ? "Expand regression tools" : "Collapse regression tools");
        panel.style.minWidth = collapsed ? "unset" : "220px";
      }

      function toggleRegressionControls() {
        var collapsed = localStorage.getItem("regressionControlsCollapsed") === "true";
        collapsed = !collapsed;
        localStorage.setItem("regressionControlsCollapsed", collapsed ? "true" : "false");
        applyRegressionControlsCollapsedState(collapsed);
      }

      (function initializeRegressionControlsState() {
        var collapsed = localStorage.getItem("regressionControlsCollapsed") === "true";
        applyRegressionControlsCollapsedState(collapsed);
      })();

      var regressionReplayState = <?= json_encode($regressionReplayState, JSON_UNESCAPED_SLASHES); ?>;

      function regressionSelectionStorageKey() {
        var folderPathInput = document.getElementById("folderPath");
        var gameNameInput = document.getElementById("gameName");
        return "regressionFixtureSelection:" +
          (folderPathInput ? folderPathInput.value : "") + ":" +
          (gameNameInput ? gameNameInput.value : "");
      }

      function persistRegressionFixtureSelection() {
        var select = document.getElementById("regressionFixtureSelect");
        if (!select || !select.value) return;
        localStorage.setItem(regressionSelectionStorageKey(), select.value);
        refreshRegressionReplaySummary();
      }

      function restoreRegressionFixtureSelection() {
        var select = document.getElementById("regressionFixtureSelect");
        if (!select || !select.options.length) return;

        var preferred = regressionReplayState && regressionReplayState.slug ? regressionReplayState.slug : localStorage.getItem(regressionSelectionStorageKey());
        if (!preferred) return;

        for (var index = 0; index < select.options.length; ++index) {
          if (select.options[index].value === preferred) {
            select.value = preferred;
            break;
          }
        }
      }

      function refreshRegressionReplaySummary() {
        var summary = document.getElementById("regressionReplaySummary");
        var button = document.getElementById("replayRegressionNextButton");
        var select = document.getElementById("regressionFixtureSelect");
        if (!summary) return;

        if (!regressionReplayState || !regressionReplayState.slug) {
          summary.textContent = "Replay state: idle";
          if (button) button.disabled = false;
          return;
        }

        var selectedSlug = select ? select.value : "";
        var isCurrentSelection = selectedSlug === regressionReplayState.slug;
        var lines = [];
        lines.push("Replay fixture: " + regressionReplayState.slug);
        lines.push("Progress: " + regressionReplayState.nextActionIndex + " / " + regressionReplayState.actionCount + " actions");

        if (regressionReplayState.matchesExpectedFinal === true) {
          lines.push("Snapshot: matches expected final");
        } else if (regressionReplayState.matchesExpectedFinal === false) {
          lines.push("Snapshot: differs from expected final");
        }

        if (regressionReplayState.lastMessage) {
          lines.push(regressionReplayState.lastMessage);
        }

        if (!isCurrentSelection && selectedSlug) {
          lines.push("Selected fixture differs from loaded replay state.");
        }

        summary.textContent = lines.join("\n");
        if (button) {
          button.disabled = isCurrentSelection && regressionReplayState.nextActionIndex >= regressionReplayState.actionCount;
        }
      }

      (function initializeRegressionFixtureSelection() {
        restoreRegressionFixtureSelection();
        refreshRegressionReplaySummary();
      })();

      function submitRegressionRequest(mode, inputText) {
        return fetch(
          "ProcessInput.php?gameName=" + encodeURIComponent(document.getElementById("gameName").value) +
          "&playerID=" + encodeURIComponent(document.getElementById("playerID").value) +
          "&authKey=" + encodeURIComponent(document.getElementById("authKey").value) +
          "&folderPath=" + encodeURIComponent(document.getElementById("folderPath").value) +
          "&mode=" + encodeURIComponent(mode) +
          "&inputText=" + encodeURIComponent(inputText || ""),
          { method: "GET" }
        ).then(function(response) {
          return response.text().then(function(message) {
            return (message || "").trim();
          });
        });
      }

      function startRegressionRecording() {
        submitRegressionRequest(11000, "").then(function() {
          location.reload();
        });
      }

      function stopRegressionRecording() {
        submitRegressionRequest(11001, "").then(function() {
          location.reload();
        });
      }

      function addRegressionAssertion() {
        var type = prompt("Assertion type:\nphase_is\nturn_player_is\nzone_count\ncard_exists\ncard_property_equals\ndecision_queue_empty\nflash_message_contains");
        if (!type) return;
        type = type.trim();
        var payload = { type: type };
        if (type === "phase_is" || type === "turn_player_is" || type === "flash_message_contains") {
          var value = prompt("Expected value:");
          if (value === null) return;
          payload.value = value;
        } else if (type === "zone_count") {
          payload.zone = prompt("Zone name (for example myField):", "myField");
          if (payload.zone === null) return;
          payload.value = prompt("Expected count:", "0");
          if (payload.value === null) return;
        } else if (type === "card_exists") {
          payload.zone = prompt("Zone name (for example myField):", "myField");
          if (payload.zone === null) return;
          payload.cardID = prompt("Card ID:");
          if (payload.cardID === null) return;
        } else if (type === "card_property_equals") {
          payload.mzId = prompt("MZ ID (for example myField-0):");
          if (payload.mzId === null) return;
          payload.property = prompt("Property name (for example Damage):");
          if (payload.property === null) return;
          payload.value = prompt("Expected value:");
          if (payload.value === null) return;
        } else if (type === "decision_queue_empty") {
          payload.player = prompt("Player to check (1, 2, or all):", "all");
          if (payload.player === null) return;
        } else {
          alert("Unsupported assertion type.");
          return;
        }

        submitRegressionRequest(11002, JSON.stringify(payload)).then(function(message) {
          if (message) alert(message);
          location.reload();
        });
      }

      function saveRegressionFixture() {
        var slug = prompt("Fixture slug:", "");
        if (!slug) return;
        var name = prompt("Fixture name:", slug);
        if (name === null) return;
        var notes = prompt("Notes (optional):", "");
        if (notes === null) return;

        submitRegressionRequest(11003, JSON.stringify({
          slug: slug,
          name: name,
          notes: notes
        })).then(function(message) {
          if (message) alert(message);
          location.reload();
        });
      }

      function rerecordRegressionFixture() {
        var select = document.getElementById("regressionFixtureSelect");
        if (!select || !select.value) {
          alert("Select an existing fixture first.");
          return;
        }

        var confirmed = confirm(
          "Re-record fixture '" + select.value + "'? This will overwrite its initial state, actions, assertions, and expected final snapshot while preserving its existing metadata."
        );
        if (!confirmed) return;

        persistRegressionFixtureSelection();
        submitRegressionRequest(11007, JSON.stringify({ slug: select.value })).then(function(message) {
          if (message) alert(message);
          location.reload();
        });
      }

      function submitRegressionFixtureLoad(replayActions) {
        var select = document.getElementById("regressionFixtureSelect");
        if (!select || !select.value) {
          alert("Select a fixture first.");
          return;
        }

        persistRegressionFixtureSelection();

        submitRegressionRequest(11004, JSON.stringify({ slug: select.value, replayActions: replayActions })).then(function(message) {
          location.reload();
        });
      }

      function loadRegressionFixtureInitialState() {
        submitRegressionFixtureLoad(false);
      }

      function replayRegressionFixture() {
        submitRegressionFixtureLoad(true);
      }

      function replayRegressionFixtureNextAction() {
        var select = document.getElementById("regressionFixtureSelect");
        if (!select || !select.value) {
          alert("Select a fixture first.");
          return;
        }

        persistRegressionFixtureSelection();

        submitRegressionRequest(11005, JSON.stringify({ slug: select.value })).then(function(message) {
          location.reload();
        });
      }

      function linkCardToFixture() {
        var select = document.getElementById("regressionFixtureSelect");
        var input = document.getElementById("regressionCardIdInput");
        if (!select || !select.value) {
          alert("Select a fixture first.");
          return;
        }
        var cardId = input ? input.value.trim() : "";
        if (!cardId) {
          alert("Enter a card ID to link.");
          return;
        }
        submitRegressionRequest(11006, JSON.stringify({ slug: select.value, cardId: cardId })).then(function(message) {
          if (message) alert(message);
          if (input) input.value = "";
        });
      }
    </script>
    <?php endif; ?>
    <div id='popupContainer'></div>
    <div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>
    <div id='mainDiv' style='position:fixed; z-index:0; left:0; top:0; width:100%; height:100%;'>

    <div id='chatWidget' style='z-index:40; position:fixed; bottom:20px; left:140px; display:flex; flex-direction:column; align-items:flex-start; width:280px;'>
        <div id='chatExpanded' style='display:none; flex-direction:column; width:100%;'>
            <div id='chatLog'
                 style='background:rgba(0,0,0,0.82); border:1px solid #555; border-bottom:none; border-radius:5px 5px 0 0; color:white;
                        font-family:barlow,sans-serif; height:160px; overflow-y:auto; padding:4px 6px;'></div>
            <?php if ($playerID != 3 && !IsChatMuted()): ?>
            <div style='display:flex;'>
                <input id='chatText'
                      style='flex:1; background:#111; color:white; font-size:14px; font-family:barlow,sans-serif;
                             height:30px; border:1px solid #555; border-radius:0; padding:0 6px; outline:none;'
                      type='text'
                      name='chatText'
                      value=''
                      autocomplete='off'
                      onkeypress='ChatKey(event)'>
                <button style='border:1px solid #555; border-left:none; border-radius:0 0 5px 0; width:50px; height:30px;
                               background:#333; color:white; margin:0; padding:0; font-size:13px; font-weight:600; box-shadow:none; cursor:pointer;'
                        onclick='SubmitChat()'>Send
                </button>
            </div>
            <?php endif; ?>
        </div>
        <button id='chatToggleBtn'
                onclick='_ToggleChat()'
                style='border:1px solid #555; border-radius:5px; background:#222; color:white; height:28px; padding:0 12px;
                       font-size:13px; font-weight:600; box-shadow:none; cursor:pointer; margin-top:2px;'>
            &#128172; Chat
        </button>
    </div>
    <script>
    function _ToggleChat() {
        var exp = document.getElementById('chatExpanded');
        var btn = document.getElementById('chatToggleBtn');
        var open = exp.style.display === 'flex';
        exp.style.display = open ? 'none' : 'flex';
        exp.style.flexDirection = 'column';
        btn.style.borderRadius = open ? '5px' : '0 0 5px 5px';
        if (!open) {
            btn.innerHTML = '&#128172; Chat';
            var log = document.getElementById('chatLog');
            if (log) log.scrollTop = log.scrollHeight;
            var inp = document.getElementById('chatText');
            if (inp) inp.focus();
        }
    }
    StartChatPoll();
    </script>

    <?php include "./" . $folderPath . "/InitialLayout.php"; ?>
    </div>


    <input type='hidden' id='gameName' value='<?= htmlspecialchars($gameName, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='playerID' value='<?= htmlspecialchars($playerID, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='authKey' value='<?= htmlspecialchars($authKey, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='folderPath' value='<?= htmlspecialchars($folderPath, ENT_QUOTES, 'UTF-8'); ?>'>


  </body>
