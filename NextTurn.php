<?php
// Start the session before ANY output so its cookie can be sent and $_SESSION is
// populated for the whole request. With output_buffering off, headers are sent the
// moment the markup below is emitted, after which session_start() silently no-ops —
// which stripped the logged-in user's identity from the deck visibility dropdown
// (the Team/Patreon options), since those need LoggedInUser() deep in InitialLayout.php.
if (session_status() === PHP_SESSION_NONE) session_start();
?>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="./Core/AppSettings.js"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"
      integrity="sha384-jb8JQMbMoBUzgWatfe6COACi2ljcDdZQ2OxczGA3bGNeWe+6DChMTBJemed7ZnvJ"
      crossorigin="anonymous"></script>
    <script src="./Core/UILibraries20260703.js?v=<?php echo filemtime('./Core/UILibraries20260703.js'); ?>"></script>
    <script src="./Core/CounterRendering.js"></script>
    <script src="./Core/MZRearrangePopup.js"></script>
    <script src="./Core/MZSplitAssignUI.js"></script>
    <script src="./Core/MZMultiChooseUI.js"></script>
    <script src="./Core/MZModalUI.js"></script>
    <script src="./Core/TwoSidedSliderUI.js"></script>
    <script src="./Core/IconChoiceUI.js"></script>
    <script src="./Core/NumberChooseUI.js"></script>
    <script src="./Core/NameCardUI.js"></script>
    <script src="./Core/MatchReplayClient.js"></script>
    <script src="./Core/OptionChooseUI.js"></script>
    <link rel="stylesheet" type="text/css" href="./Core/Styles/ScreenAnimations.css">
    <!-- Preload shield-break frames so the first shatter doesn't stutter fetching frames 2-5 mid-animation. -->
    <link rel="preload" as="image" href="./Assets/Icons/space-shield_break1.svg">
    <link rel="preload" as="image" href="./Assets/Icons/space-shield_break2.svg">
    <link rel="preload" as="image" href="./Assets/Icons/space-shield_break3.svg">
    <link rel="preload" as="image" href="./Assets/Icons/space-shield_break4.svg">
    <link rel="preload" as="image" href="./Assets/Icons/space-shield_break5.svg">

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
        include_once './Core/GameAuth.php';
        include './Core/ViewerIdentity.php';
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
    $viewerInfo = NormalizeViewerIdentity(TryGet("playerID", "S"));
    if ($viewerInfo['viewerID'] === '') {
      echo ("Invalid player ID.");
      exit;
    }
    $playerID = $viewerInfo['viewerID'];
    $viewerPerspective = NormalizeViewerPerspective($viewerInfo, TryGet("viewerPerspective", ""));
    $isSpectatorViewer = $viewerInfo['isSpectator'];

    if (!file_exists("./" . $folderPath . "/Games/" . $gameName . "/")) {
      echo ("Game does not exist");
      exit;
    }

    // The session was started at the very top of this file (before any output), so
    // $_SESSION is populated here and further down (e.g. the deck visibility dropdown's
    // Team/Patreon options). Read seat auth from it, then release the session lock so
    // concurrent same-session requests aren't blocked ($_SESSION stays readable after close).
    $authKey = "";
    if ($playerID == 1 && isset($_SESSION["p1AuthKey"])) $authKey = $_SESSION["p1AuthKey"];
    else if ($playerID == 2 && isset($_SESSION["p2AuthKey"])) $authKey = $_SESSION["p2AuthKey"];
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    if ($authKey === "") $authKey = TryGet("authKey", "");

    if(($playerID == 1 || $playerID == 2) && $authKey == "")
    {
      if(isset($_COOKIE["lastAuthKey"])) $authKey = $_COOKIE["lastAuthKey"];
    }

    if (!SimGameValidateViewerAuth($folderPath, $gameName, $viewerInfo, $authKey)) {
      // SWUSim public spectating requires a logged-in account: send anonymous spectators to the
      // login page with a return link back to this spectate URL (rather than the generic "link
      // invalid" page, which would be misleading — nothing is wrong with the link). This file has
      // already emitted markup by now (output buffering is off), so a header() redirect can't work
      // — use a client-side redirect, the same way SimGameRenderInvalidAuthPage renders post-output.
      if (SimGameSpectatorLoginRequiredMissing($folderPath, $gameName, $viewerInfo)) {
        $loginUrl = './SharedUI/LoginPage.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '');
        echo '<script>window.location.replace(' . json_encode($loginUrl) . ');</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        exit;
      }
      SimGameRenderInvalidAuthPage($folderPath, $gameName, $playerID);
    }

    $spectatorAuthKey = SimGameGetSpectatorAuthKey($folderPath, $gameName);
    $privateSpectatorAuthRequired = SimGameIsPrivateGame($folderPath, $gameName);

    include "./" . $folderPath . "/ZoneClasses.php";
    include "./" . $folderPath . "/ZoneAccessors.php";
    include "./" . $folderPath . "/GamestateParser.php";
    include "./Core/UILibraries.php";
    include_once "./Core/RegressionTestFramework.php";
    include_once "./Core/MatchReplay.php";
    include "./Core/Constants.php";
    include_once "./AccountFiles/AccountSessionAPI.php";
    include_once "./Assets/patreon-php-master/src/PatreonDictionary.php";
    include_once "./Assets/patreon-php-master/src/PatreonLibraries.php";

    ParseGamestate("./" . $folderPath . "/");

    function IsRegressionLocalDevelopmentRequest() {
      if (getenv('DEVENV') === 'true') return true;
      $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
      $host = strtolower(strval($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
      if (substr($host, 0, 1) === '[') {
        $closeBracket = strpos($host, ']');
        if ($closeBracket !== false) $host = substr($host, 0, $closeBracket + 1);
      } else if (substr_count($host, ':') === 1) {
        $host = preg_replace('/:\d+$/', '', $host);
      }
      return in_array($remoteAddr, ['127.0.0.1', '::1'], true) &&
        in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    }

    $canUseRegressionControls = IsRegressionLocalDevelopmentRequest();
    $showRegressionControls =
      $canUseRegressionControls &&
      function_exists('SupportsRegressionRecording') &&
      SupportsRegressionRecording() &&
      !$isSpectatorViewer;
    $showManualControls = $showRegressionControls && in_array($folderPath, ['GrandArchiveSim', 'AzukiSim'], true);
    $regressionRecordingActive = $showRegressionControls ? RegressionIsRecordingActive($folderPath, $gameName) : false;
    $regressionFixtureOptions = $showRegressionControls ? RegressionListFixtureOptions($folderPath) : [];
    $regressionReplayState = $showRegressionControls ? RegressionReadReplayState($folderPath, $gameName) : null;
    $selectedRegressionFixtureSlug = is_array($regressionReplayState) ? strval($regressionReplayState['slug'] ?? '') : '';
    $matchReplayEnabled = MatchReplayIsEnabled();
    $matchReplayCanDownload = $matchReplayEnabled && MatchReplayCanDownload();
    $matchReplayPlaybackState = $matchReplayEnabled ? MatchReplayPlaybackState() : null;

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

    <script>
      window.MatchReplayConfig = <?= json_encode([
        'enabled' => $matchReplayEnabled,
        'rootName' => $folderPath,
        'canDownload' => $matchReplayCanDownload,
        'playbackState' => $matchReplayPlaybackState,
        'apiBaseUrl' => './APIs/MatchReplay.php',
        'processInputUrl' => './ProcessInput.php',
        'nextTurnBaseUrl' => './NextTurn.php',
      ], JSON_UNESCAPED_SLASHES); ?>;
      if (window.MatchReplayClient) {
        window.MatchReplayClient.init(window.MatchReplayConfig);
      }
    </script>

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
          // Phone-specific layouts can show fewer, larger cards. Gate on the same
          // phone/query override triggers their mobile layouts use, so a narrow desktop window
          // that keeps the wide desktop layout still gets the historical small-card grid.
          var isSWUDeckPhone = <?php echo ($folderPath === 'SWUDeck' ? 'true' : 'false'); ?> &&
            (/iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|webOS|Opera Mini|IEMobile/i.test(navigator.userAgent)
              || /[?&]swuLayout=mobile/.test(location.search));
          var isAzukiPhone = <?php echo ($folderPath === 'AzukiSim' ? 'true' : 'false'); ?> &&
            (/iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|webOS|Opera Mini|IEMobile/i.test(navigator.userAgent)
              || /[?&]azukiLayout=mobile/.test(location.search));
          var mobileColumns = isSWUDeckPhone ? 4 : (isAzukiPhone ? 6 : 7);
          var maxCardSize = isSWUDeckPhone ? 120 : (isAzukiPhone ? 62 : 80);
          var rowPadding = 24; // account for wrapper padding and edge spacing
          var perCardHorizontalSpacing = 4; // two 1px margins + buffer for layout rounding
          var calculatedSize = Math.floor((viewportWidth - rowPadding - (mobileColumns * perCardHorizontalSpacing)) / mobileColumns);
          return Math.max(36, Math.min(maxCardSize, calculatedSize));
        }
        // SWUDeck's desktop deck editor shows larger cards (smaller divisor => bigger card,
        // fewer per row); other sims keep the historical /16 sizing.
        return window.innerWidth / <?php echo ($folderPath === 'SWUDeck' ? '13.5' : '16'); ?>;
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
      $fileMtime = @filemtime("./" . $folderPath . "/" . $generateFilename);
      echo("$folderPath/$generateFilename?v=$fileMtime");
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

  <?php
    $initialGamestateUpdateMarker = 0;
    if (function_exists('GamestateUsesMemoryStorage') && GamestateUsesMemoryStorage()) {
      $initialGamestateUpdateMarker = intval($updateNumber);
    } else {
      $gamestateFile = "./" . $folderPath . "/Games/" . $gameName . "/Gamestate.txt";
      if (is_file($gamestateFile)) {
        $initialGamestateUpdateMarker = intval(filemtime($gamestateFile));
      }
    }
  ?>
  <body onkeydown='Hotkeys(event)' onload='OnLoadCallback(<?php echo ($initialGamestateUpdateMarker); ?>)'>

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
      (function persistMostRecentSimGame() {
        var rawPlayerID = <?php echo json_encode(strval($playerID)); ?>;
        if (rawPlayerID !== "1" && rawPlayerID !== "2") return;
        var authKey = <?php echo json_encode(strval($authKey)); ?>;
        var folderPath = <?php echo json_encode(strval($folderPath)); ?>;
        var gameName = <?php echo json_encode(strval($gameName)); ?>;
        if (!authKey || !folderPath || !gameName) return;

        try {
          localStorage.setItem('tcgengine:lastSimGame:' + folderPath, JSON.stringify({
            rootName: folderPath,
            gameName: gameName,
            playerID: rawPlayerID,
            authKey: authKey,
            updatedAt: Date.now()
          }));
        } catch (e) {}

        if (typeof SetCookieValue === 'function') {
          SetCookieValue('lastAuthKey', authKey, 30);
        } else {
          document.cookie = 'lastAuthKey=' + encodeURIComponent(authKey) + '; max-age=' + (30 * 24 * 60 * 60) + '; path=/; SameSite=Lax';
        }
      })();

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

      window.QueueGameUpdate = function() {
        QueueReload(_lastUpdate);
      };

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

      function ParseChatPayload(responseArr) {
        if (!Array.isArray(responseArr) || responseArr.length < 2) return null;
        var raw = responseArr[0] === "CHATONLY" ? responseArr[1] : responseArr[responseArr.length - 2];
        if (!raw) return null;
        var trimmed = String(raw).trim();
        if (trimmed === "" || trimmed.charAt(0) !== "{") return null;
        try {
          var parsed = JSON.parse(trimmed);
          if (parsed && typeof parsed === "object") return parsed;
        } catch (e) {}
        return null;
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

      function NormalizeAnimationUniqueID(animation) {
        if (!animation) return "";
        var uniqueID = animation.uniqueID;
        if ((uniqueID == null || uniqueID === "") && animation.target && typeof animation.target === "object") {
          uniqueID = animation.target.uniqueID;
        }
        if (uniqueID == null || uniqueID === "") return "";
        return String(uniqueID).trim();
      }

      function ResolveAnimationTargetElement(animation, perspectivePlayerID) {
        if (!animation) return null;
        var target = animation.target || animation.mzID || "";
        if (target === "P1BASE" || target === "P2BASE") {
          return document.getElementById(target);
        }
        var uniqueID = NormalizeAnimationUniqueID(animation);
        if (uniqueID) {
          var byUniqueID = document.querySelector("[data-uniqueid='" + uniqueID + "']");
          if (byUniqueID) return byUniqueID;
        }
        var mzid = NormalizeAnimationTarget(target, perspectivePlayerID);
        if (!mzid) return null;
        var byID = document.getElementById(mzid);
        if (byID) return byID;
        return document.querySelector("[data-mzid='" + mzid + "']");
      }

      function GetFrameAnimationTargetKey(animation, perspectivePlayerID) {
        if (!animation) return "";
        var target = animation.target || animation.mzID || "";
        if (target === "P1BASE" || target === "P2BASE") return String(target);
        var uniqueID = NormalizeAnimationUniqueID(animation);
        if (uniqueID) return "UID:" + uniqueID;
        return NormalizeAnimationTarget(target, perspectivePlayerID);
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
          var damageDelayStyle = delayMs > 0 ? " style='animation-delay:" + delayMs + "ms;opacity:0;'" : "";
          var damageLabelDelayStyle = delayMs > 0 ? " style='animation-delay:" + delayMs + "ms;opacity:0;'" : "";
          element.innerHTML += "<div class='dmg-animation dmg-animation-a'" + damageDelayStyle + "><div class='dmg-animation-a-inner'></div></div>";
          element.innerHTML += "<div class='dmg-animation-a-label'><div class='dmg-animation-a-label-inner'" + damageLabelDelayStyle + ">-" + amount + "</div></div>";
          if (totalMs < 500) totalMs = 500;
        } else if (type === "PREVENTED_DAMAGE") {
          var preventedDelayStyle = delayMs > 0 ? " style='animation-delay:" + delayMs + "ms;opacity:0;'" : "";
          var preventedLabelDelayStyle = delayMs > 0 ? " style='animation-delay:" + delayMs + "ms;opacity:0;'" : "";
          element.innerHTML += "<div class='prevented-dmg-animation prevented-dmg-animation-a'" + preventedDelayStyle + "><div class='prevented-dmg-animation-a-inner'></div></div>";
          element.innerHTML += "<div class='prevented-dmg-animation-a-label'><div class='prevented-dmg-animation-a-label-inner'" + preventedLabelDelayStyle + ">-0</div></div>";
          if (totalMs < 500) totalMs = 500;
        } else if (type === "RESTORE") {
          var restoreAmount = animation.amount != null ? animation.amount : "";
          var restoreDelayStyle = delayMs > 0 ? " style='animation-delay:" + delayMs + "ms;opacity:0;'" : "";
          var restoreLabelDelayStyle = delayMs > 0 ? " style='animation-delay:" + delayMs + "ms;opacity:0;'" : "";
          element.innerHTML += "<div class='restore-animation restore-animation-a'" + restoreDelayStyle + "><div class='restore-animation-a-inner'></div></div>";
          element.innerHTML += "<div class='restore-animation-a-label'><div class='restore-animation-a-label-inner'" + restoreLabelDelayStyle + ">+" + restoreAmount + "</div></div>";
          if (totalMs < 500) totalMs = 500;
        } else if (type === "SHIELD_BREAK") {
          // Play the shatter at the broken shield's own top-right orb (slot 0 = rightmost,
          // each +20px to the left), matching the shield token layout (28px box centered
          // on the 20px orb). See UILibraries shield-orb render.
          var shieldSlot = parseInt(animation.slot || 0, 10);
          if (Number.isNaN(shieldSlot) || shieldSlot < 0) shieldSlot = 0;
          // Hide the underlying shield orb at this slot so only the shatter shows during the
          // animation. Orbs render in slot order (shi 0 = rightmost), so the Nth orb is slot N.
          // Setting the inline style before innerHTML += below preserves it through the re-parse.
          // The board re-render after the block removes the consumed shield for real.
          var shieldOrbs = element.querySelectorAll("img[title='Shield']");
          if (shieldOrbs[shieldSlot]) shieldOrbs[shieldSlot].style.display = "none";
          var shieldStyle = "top:1px; right:" + (shieldSlot * 20 + 1) + "px; width:28px; height:28px;";
          if (delayMs > 0) shieldStyle += " animation-delay:" + delayMs + "ms; opacity:0;";
          element.innerHTML += "<div class='shield-break-animation' style='" + shieldStyle + "'></div>";
          if (totalMs < 600) totalMs = 600;
        } else if (type === "EXHAUST") {
          var exhaustAnimation = [
            { transform: "rotate(0deg) scale(1)" },
            { transform: "rotate(5deg) scale(1)" },
          ];
          var exhaustTiming = { duration: 60, delay: delayMs, iterations: 1 };
          element.animate(exhaustAnimation, exhaustTiming);
          var exhaustOverlayStyle = "position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 16px); padding: 0 2px; border-radius:12px; background-color:rgba(0,0,0,0.5);";
          if (delayMs > 0) exhaustOverlayStyle += " animation: damageFlash 60ms ease-out " + delayMs + "ms 1 forwards; opacity:0;";
          element.innerHTML += "<div style='" + exhaustOverlayStyle + "'><div style='width:100%; height:100%:'></div></div>";
          if (delayMs > 0) {
            window.setTimeout(function() { element.className += " exhausted"; }, delayMs);
          } else {
            element.className += " exhausted";
          }
          if (totalMs < 60) totalMs = 60;
        } else {
          var animationName = animation.name || (animation.params && animation.params.animationName) || "";
          var cssClass = animation.className || (animation.params && animation.params.className) || "";
          if (cssClass) {
            if (delayMs > 0) {
              window.setTimeout(function() { element.classList.add(cssClass); }, delayMs);
            } else {
              element.classList.add(cssClass);
            }
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

        var perTargetQueuedDelayMs = {};
        var sameTargetStaggerMs = 180;
        var blockingDelayMs = 0;
        for (var i = 0; i < animations.length; ++i) {
          var animation = animations[i];
          if (animation && typeof animation === "object") {
            animation = Object.assign({}, animation);
            // Shield breaks fire simultaneously: exempt from the same-target stagger so multiple
            // shields stripped (e.g. by Saboteur) all play at once and the break isn't pushed
            // behind the prevented-damage "-0" on the same unit.
            var isShieldBreak = String(animation.type || "").toUpperCase() === "SHIELD_BREAK";
            var targetKey = isShieldBreak ? "" : GetFrameAnimationTargetKey(animation, perspectivePlayerID);
            if (targetKey) {
              var existingDelayMs = parseInt(animation.delayMs || 0, 10);
              if (Number.isNaN(existingDelayMs) || existingDelayMs < 0) existingDelayMs = 0;
              var staggerDelayMs = perTargetQueuedDelayMs[targetKey] || 0;
              animation.delayMs = existingDelayMs + staggerDelayMs;
              perTargetQueuedDelayMs[targetKey] = staggerDelayMs + sameTargetStaggerMs;
            }
          }
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
            } else if (responseText.split("SIDEBOARD")[0] == "1236") {
              // Bo3: this game ended and sideboarding is pending. Show the end-game menu (the hub);
              // the menu's "Go to Next Game" navigates to the sideboard screen. Keep polling so the
              // menu stays in sync if the opponent forfeits/leaves. (After a full rematch a NEW match
              // is sideboarding — SWUEnterSideboardOrMenu detects that and navigates straight there,
              // since the completed-match overlay would otherwise block the menu rebuild.)
              if (typeof window.SWUEnterSideboardOrMenu === 'function') window.SWUEnterSideboardOrMenu();
              else if (typeof window.SWUShowEndGameMenu === 'function') window.SWUShowEndGameMenu();
              QueueReload(_lastUpdate);
              return;
            } else if (responseText.split("MATCHADVANCE")[0] == "1235") {
              // Bo3: this game advanced to the next child game — redirect (authKey carries over).
              var nextGame = responseText.split("MATCHADVANCE")[1] || "";
              if (nextGame) {
                var u = new URL(window.location.href);
                u.searchParams.set('gameName', nextGame);
                window.location.replace(u.toString());
                return;
              }
            } else if (responseText.split("REMATCH")[0] == "1234") {
              location.replace('GameLobby.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&authKey=<?php echo ($authKey); ?>');
            } else {
              var responseArr = responseText.split("<~>");
              var chatPayload = ParseChatPayload(responseArr);
              var chatChanged = ApplyChatPayload(chatPayload);
              if (responseArr[0] === "CHATONLY") {
                QueueReload(_lastUpdate);
                return;
              }
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
              var timeoutAmount = PlayFrameAnimations(frameAnimations, <?php echo($viewerPerspective); ?>);
              if(timeoutAmount > 0) setTimeout(RenderUpdate, timeoutAmount, responseArr);
              else RenderUpdate(responseArr);
            }
          }
        };
        var dimensions = "&windowWidth=" + window.innerWidth + "&windowHeight=" + window.innerHeight;
        var lcpEl = document.getElementById("lastCurrentPlayer");
        var lastCurrentPlayer = "&lastCurrentPlayer=" + (!lcpEl ? "0" : lcpEl.innerHTML);
        var lastChatVersion = "&lastChatVersion=" + encodeURIComponent(_lastChatVersion);
        var lastChatID = "&lastChatID=" + encodeURIComponent(_lastChatID);
        if (lastUpdate == "NaN") window.location.replace("https://www.petranaki.net/Arena/MainMenu.php");
        else xmlhttp.open("GET", "./<?php echo($folderPath);?>/GetNextTurn.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo urlencode($playerID); ?>&viewerPerspective=<?php echo($viewerPerspective); ?>&lastUpdate=" + lastUpdate + "&authKey=<?php echo urlencode($authKey); ?>" + lastChatVersion + lastChatID + dimensions, true);
        xmlhttp.send();
      }

      function RenderUpdate(responseArr) {
        if (typeof FreezeCardDetailUntilMouseMove === 'function') FreezeCardDetailUntilMouseMove();
        if (typeof ClearSelectionMode === 'function') {
          ClearSelectionMode();
        }
        if (typeof window !== 'undefined') {
          window.__nextCardStatusByMzid = {};
          window.__nextReliquaryDrawByMzid = {};
          window.__nextVerdurePreserveByMzid = {};
        }
        var newHTML = "";
        var playerID = <?php echo($viewerPerspective); ?>;
        var viewerIdentity = <?php echo json_encode($playerID); ?>;
        var viewerCanAct = <?php echo($isSpectatorViewer ? 'false' : 'true'); ?>;
        <?php include "./" . $folderPath . "/NextTurnRender.php"; ?>
        if (typeof window !== 'undefined') {
          window.__prevCardStatusByMzid = window.__nextCardStatusByMzid || {};
          window.__prevReliquaryDrawByMzid = window.__nextReliquaryDrawByMzid || {};
          window.__prevVerdurePreserveByMzid = window.__nextVerdurePreserveByMzid || {};
          window.__cardStatusHistoryReady = true;
        }
        if (typeof window !== 'undefined' && typeof window.ApplyExhaustedEnterAnimations === 'function') {
          window.ApplyExhaustedEnterAnimations();
        }
        if (typeof window !== 'undefined' && typeof window.ApplyWakeEnterAnimations === 'function') {
          window.ApplyWakeEnterAnimations();
        }
        if (typeof window !== 'undefined' && typeof window.ApplyReliquaryDrawAnimations === 'function') {
          window.ApplyReliquaryDrawAnimations();
        }
        if (typeof window !== 'undefined' && typeof window.ApplyVerdurePreserveAnimations === 'function') {
          window.ApplyVerdurePreserveAnimations();
        }
        UpdateTurnPlayerMiasma();
        // Game-over detection: check for GAMEOVER_WINNER set by server-side TriggerGameOver()
        if (!window._gameOverShown && window.DecisionQueueVariablesData) {
          try {
            var _goVars = JSON.parse(window.DecisionQueueVariablesData);
            if (_goVars && _goVars.GAMEOVER_WINNER) {
              var _goWinner = parseInt(_goVars.GAMEOVER_WINNER, 10);
              if (_goWinner > 0 && typeof ShowGameOver === 'function') {
                window._gameOverShown = true;
                if (window.MatchReplayConfig && window.MatchReplayConfig.enabled) {
                  window.MatchReplayConfig.canDownload = true;
                  if (window.MatchReplayClient && typeof window.MatchReplayClient.renderPanelList === 'function') {
                    window.MatchReplayClient.renderPanelList();
                  }
                }
                var _goStatsHtml = '';
                if (typeof BuildMacroGameStatsHtml === 'function') {
                  _goStatsHtml = BuildMacroGameStatsHtml(playerID);
                }
                // SWUSim / GrandArchiveSim: show the match-aware end-game menu (contextual buttons + the
                // same card-activity stats matrix, in one overlay). Other sims keep the plain overlay.
                if (typeof window.SWUShowEndGameMenu === 'function') {
                  window.SWUShowEndGameMenu();
                } else if (typeof window.GAShowEndGameMenu === 'function') {
                  window.GAShowEndGameMenu(_goStatsHtml);
                } else {
                  ShowGameOver(viewerCanAct && playerID === _goWinner, undefined, _goStatsHtml);
                }
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
        <div style="display:flex; flex-direction:column; gap:4px; align-items:stretch; margin-top:4px; position:relative;">
          <div style="display:flex; gap:4px; align-items:center;">
            <input type="text" id="regressionCardIdInput" placeholder="Card name or ID" style="flex:1; padding:5px 7px; font-size:12px; min-width:0;" autocomplete="off" spellcheck="false" />
            <button type="button" onclick="linkCardToFixture()" style="padding:5px 8px; white-space:nowrap;">Link Card</button>
          </div>
          <div id="regressionCardLookupStatus" style="display:none; font-size:11px; line-height:1.35; color:#c7d8ff;"></div>
          <div id="regressionCardSuggestions" style="display:none; max-height:150px; overflow-y:auto; border:1px solid rgba(201, 168, 76, 0.28); border-radius:8px; background:rgba(9, 18, 33, 0.98); padding:4px;"></div>
        </div>
      </div>
      </div>
    </div>
    <script>
      var regressionCardSuggestionIndex = -1;

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

      function getRegressionCardLookupElements() {
        return {
          input: document.getElementById("regressionCardIdInput"),
          suggestions: document.getElementById("regressionCardSuggestions"),
          status: document.getElementById("regressionCardLookupStatus")
        };
      }

      function clearRegressionCardSuggestions() {
        var elements = getRegressionCardLookupElements();
        if (elements.suggestions) {
          elements.suggestions.innerHTML = "";
          elements.suggestions.style.display = "none";
        }
        regressionCardSuggestionIndex = -1;
      }

      function setRegressionCardLookupStatus(message, isError) {
        var elements = getRegressionCardLookupElements();
        if (!elements.status) return;
        if (!message) {
          elements.status.textContent = "";
          elements.status.style.display = "none";
          return;
        }
        elements.status.textContent = message;
        elements.status.style.display = "block";
        elements.status.style.color = isError ? "#ffb2b2" : "#c7d8ff";
      }

      function refreshRegressionCardSuggestionHighlight() {
        var elements = getRegressionCardLookupElements();
        if (!elements.suggestions) return;
        var children = Array.prototype.slice.call(elements.suggestions.children || []);
        children.forEach(function(child, index) {
          child.style.background = index === regressionCardSuggestionIndex ? "rgba(34, 62, 104, 0.98)" : "rgba(11, 24, 44, 0.84)";
          child.style.borderColor = index === regressionCardSuggestionIndex ? "rgba(219, 188, 99, 0.62)" : "rgba(150, 183, 235, 0.18)";
        });
      }

      function chooseRegressionCardSuggestion(name) {
        var elements = getRegressionCardLookupElements();
        if (!elements.input) return;
        elements.input.value = name;
        clearRegressionCardSuggestions();
        if (window.NameCardLookup && typeof window.NameCardLookup.getRepresentativeCardIdForName === "function") {
          var cardId = window.NameCardLookup.getRepresentativeCardIdForName(name);
          setRegressionCardLookupStatus(cardId ? ("Will link " + name + " (" + cardId + ").") : "");
        } else {
          setRegressionCardLookupStatus("");
        }
        elements.input.focus();
      }

      function updateRegressionCardSuggestions() {
        var elements = getRegressionCardLookupElements();
        if (!elements.input || !elements.suggestions) return [];
        if (!window.NameCardLookup || typeof window.NameCardLookup.findMatchingCardNames !== "function") {
          clearRegressionCardSuggestions();
          setRegressionCardLookupStatus("");
          return [];
        }

        var query = elements.input.value.trim();
        elements.suggestions.innerHTML = "";
        regressionCardSuggestionIndex = -1;

        if (!query) {
          clearRegressionCardSuggestions();
          setRegressionCardLookupStatus("");
          return [];
        }

        var matches = window.NameCardLookup.findMatchingCardNames(query, 8);
        if (matches.length === 0) {
          clearRegressionCardSuggestions();
          var directCardId = window.NameCardLookup.resolveCardIdFromInput(query);
          setRegressionCardLookupStatus(directCardId ? ("Will link card ID " + directCardId + ".") : "No local card-name matches yet.");
          return [];
        }

        matches.forEach(function(name, index) {
          var cardId = window.NameCardLookup.getRepresentativeCardIdForName(name) || "";
          var option = document.createElement("button");
          option.type = "button";
          option.style.display = "block";
          option.style.width = "100%";
          option.style.padding = "6px 8px";
          option.style.border = "1px solid rgba(150, 183, 235, 0.18)";
          option.style.borderRadius = "6px";
          option.style.background = "rgba(11, 24, 44, 0.84)";
          option.style.color = "#eef4ff";
          option.style.textAlign = "left";
          option.style.cursor = "pointer";
          option.style.fontSize = "12px";
          option.style.marginBottom = index === matches.length - 1 ? "0" : "4px";
          option.textContent = cardId ? (name + " (" + cardId + ")") : name;
          option.onmouseenter = function() {
            regressionCardSuggestionIndex = index;
            refreshRegressionCardSuggestionHighlight();
          };
          option.onclick = function() {
            chooseRegressionCardSuggestion(name);
          };
          elements.suggestions.appendChild(option);
        });

        elements.suggestions.style.display = "block";

        var resolvedCardId = window.NameCardLookup.resolveCardIdFromInput(query);
        if (resolvedCardId) {
          var resolvedName = typeof Cardname === "function" ? (Cardname(resolvedCardId) || query) : query;
          setRegressionCardLookupStatus("Will link " + resolvedName + " (" + resolvedCardId + ").");
        } else if (matches.length === 1) {
          var exactCardId = window.NameCardLookup.getRepresentativeCardIdForName(matches[0]);
          setRegressionCardLookupStatus(exactCardId ? ("Press Enter to link " + matches[0] + " (" + exactCardId + ").") : "");
        } else {
          setRegressionCardLookupStatus(matches.length + " matches. Keep typing or pick one below.");
        }
        return matches;
      }

      function initializeRegressionCardLookup() {
        var elements = getRegressionCardLookupElements();
        if (!elements.input) return;

        elements.input.addEventListener("input", function() {
          updateRegressionCardSuggestions();
        });
        elements.input.addEventListener("keydown", function(event) {
          var suggestionButtons = Array.prototype.slice.call((getRegressionCardLookupElements().suggestions || {}).children || []);
          if (event.key === "ArrowDown" && suggestionButtons.length > 0) {
            event.preventDefault();
            regressionCardSuggestionIndex = Math.min(regressionCardSuggestionIndex + 1, suggestionButtons.length - 1);
            refreshRegressionCardSuggestionHighlight();
            return;
          }
          if (event.key === "ArrowUp" && suggestionButtons.length > 0) {
            event.preventDefault();
            regressionCardSuggestionIndex = Math.max(regressionCardSuggestionIndex - 1, 0);
            refreshRegressionCardSuggestionHighlight();
            return;
          }
          if (event.key === "Enter") {
            if (regressionCardSuggestionIndex >= 0 && regressionCardSuggestionIndex < suggestionButtons.length) {
              event.preventDefault();
              suggestionButtons[regressionCardSuggestionIndex].click();
              return;
            }
            event.preventDefault();
            linkCardToFixture();
            return;
          }
          if (event.key === "Escape") {
            clearRegressionCardSuggestions();
            setRegressionCardLookupStatus("");
          }
        });
        elements.input.addEventListener("blur", function() {
          setTimeout(function() {
            clearRegressionCardSuggestions();
          }, 120);
        });
      }

      function linkCardToFixture() {
        var select = document.getElementById("regressionFixtureSelect");
        var input = document.getElementById("regressionCardIdInput");
        if (!select || !select.value) {
          alert("Select a fixture first.");
          return;
        }
        var rawValue = input ? input.value.trim() : "";
        if (!rawValue) {
          alert("Enter a card name or card ID to link.");
          return;
        }
        var cardId = rawValue;
        if (window.NameCardLookup && typeof window.NameCardLookup.resolveCardIdFromInput === "function") {
          cardId = window.NameCardLookup.resolveCardIdFromInput(rawValue);
        }
        if (!cardId) {
          setRegressionCardLookupStatus("No unique local card match found for \"" + rawValue + "\".", true);
          alert("No unique local card match found. Keep typing, pick a suggestion, or enter the exact card ID.");
          return;
        }
        submitRegressionRequest(11006, JSON.stringify({ slug: select.value, cardId: cardId })).then(function(message) {
          if (message) alert(message);
          if (input) input.value = "";
          clearRegressionCardSuggestions();
          setRegressionCardLookupStatus("");
        });
      }

      initializeRegressionCardLookup();
    </script>
    <?php endif; ?>
    <?php if ($showManualControls): ?>
    <div id="manualControls" style="position:fixed; top:16px; right:260px; z-index:12000; background:rgba(7, 18, 30, 0.92); color:#f0e6c8; border:1px solid #c9a84c; border-radius:10px; padding:12px; min-width:220px; box-shadow:0 8px 24px rgba(0,0,0,0.35);">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;">
        <div style="font-weight:700;">Manual Controls</div>
        <button
          type="button"
          id="manualToggle"
          onclick="toggleManualControls()"
          aria-expanded="true"
          title="Collapse manual controls"
          style="padding:2px 8px; font-size:14px; line-height:1; cursor:pointer;"
        >-</button>
      </div>
      <div id="manualControlsBody">
        <div style="display:flex; flex-direction:column; gap:6px;">
          <input type="text" id="manualCardIdInput" placeholder="Card ID" style="padding:6px 8px; font-size:12px;" />
          <button type="button" onclick="addManualCardToHand(1)" style="padding:6px 10px;">Add to P1 Hand</button>
          <button type="button" onclick="addManualCardToHand(2)" style="padding:6px 10px;">Add to P2 Hand</button>
          <button type="button" onclick="addManualCardToGraveyard(1)" style="padding:6px 10px;">Add to P1 Graveyard</button>
          <button type="button" onclick="addManualCardToGraveyard(2)" style="padding:6px 10px;">Add to P2 Graveyard</button>
          <button type="button" onclick="addManualCardToTopDeck(1)" style="padding:6px 10px;">Add to P1 Top Deck</button>
          <button type="button" onclick="addManualCardToTopDeck(2)" style="padding:6px 10px;">Add to P2 Top Deck</button>
        </div>
      </div>
    </div>
    <script>
      function applyManualControlsCollapsedState(collapsed) {
        var body = document.getElementById("manualControlsBody");
        var toggle = document.getElementById("manualToggle");
        var panel = document.getElementById("manualControls");
        if (!body || !toggle || !panel) return;
        body.style.display = collapsed ? "none" : "block";
        toggle.textContent = collapsed ? "+" : "-";
        toggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
        toggle.setAttribute("title", collapsed ? "Expand manual controls" : "Collapse manual controls");
        panel.style.minWidth = collapsed ? "unset" : "220px";
      }

      function toggleManualControls() {
        var collapsed = localStorage.getItem("manualControlsCollapsed") === "true";
        collapsed = !collapsed;
        localStorage.setItem("manualControlsCollapsed", collapsed ? "true" : "false");
        applyManualControlsCollapsedState(collapsed);
      }

      (function initializeManualControlsState() {
        var collapsed = localStorage.getItem("manualControlsCollapsed") === "true";
        applyManualControlsCollapsedState(collapsed);
      })();

      function submitManualCardAdd(mode) {
        var input = document.getElementById("manualCardIdInput");
        var cardId = input ? input.value.trim() : "";
        if (!cardId) {
          alert("Enter a card ID first.");
          return;
        }

        submitRegressionRequest(mode, cardId).then(function(message) {
          if (message) alert(message);
          if (input) input.value = "";
          location.reload();
        });
      }

      function addManualCardToHand(player) {
        submitManualCardAdd(player === 1 ? 11008 : 11009);
      }

      function addManualCardToGraveyard(player) {
        submitManualCardAdd(player === 1 ? 11012 : 11013);
      }

      function addManualCardToTopDeck(player) {
        submitManualCardAdd(player === 1 ? 11010 : 11011);
      }
    </script>
    <?php endif; ?>
    <div id='popupContainer'></div>
    <div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>
    <div id='mainDiv' style='position:fixed; z-index:0; left:0; top:0; width:100%; height:100%;'>

    <?php if ($folderPath !== "SWUDeck"): ?>
    <div id='chatWidget' style='z-index:40; position:fixed; bottom:20px; left:140px; display:flex; flex-direction:column; align-items:flex-start; width:280px;'>
        <div id='chatExpanded' style='display:none; flex-direction:column; width:100%;'>
            <div id='chatLog'
                 style='background:rgba(0,0,0,0.82); border:1px solid #555; border-bottom:none; border-radius:5px 5px 0 0; color:white;
                        font-family:barlow,sans-serif; height:160px; overflow-y:auto; padding:4px 6px;'></div>
            <?php if (!IsChatMuted()): ?>
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
    <?php endif; ?>

    <?php if ($isSpectatorViewer): ?>
    <div id='spectatorControls'
         style='position:fixed; top:16px; left:16px; z-index:12000; background:rgba(7, 18, 30, 0.92); color:#f0e6c8; border:1px solid #c9a84c; border-radius:10px; padding:10px 12px; box-shadow:0 8px 24px rgba(0,0,0,0.35);'>
      <div style='font-weight:700; margin-bottom:8px;'>Spectator View</div>
      <div style='display:flex; gap:8px; align-items:center;'>
        <button type='button'
                onclick='SetSpectatorPerspective(1)'
                style='padding:6px 10px; <?php echo($viewerPerspective === 1 ? "background:#c9a84c; color:#0d1b2a;" : "background:#1d3a5e; color:#f0e6c8;"); ?>'>View P1 Side</button>
        <button type='button'
                onclick='SetSpectatorPerspective(2)'
                style='padding:6px 10px; <?php echo($viewerPerspective === 2 ? "background:#c9a84c; color:#0d1b2a;" : "background:#1d3a5e; color:#f0e6c8;"); ?>'>View P2 Side</button>
      </div>
    </div>
    <script>
      function SetSpectatorPerspective(perspective) {
        var url = new URL(window.location.href);
        url.searchParams.set('playerID', 'S');
        url.searchParams.set('viewerPerspective', perspective === 2 ? '2' : '1');
        window.location.replace(url.toString());
      }
    </script>
    <?php endif; ?>

    <?php include "./" . $folderPath . "/InitialLayout.php"; ?>
    </div>


    <input type='hidden' id='gameName' value='<?= htmlspecialchars($gameName, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='playerID' value='<?= htmlspecialchars($playerID, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='viewerPerspective' value='<?= htmlspecialchars(strval($viewerPerspective), ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='authKey' value='<?= htmlspecialchars($authKey, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='spectatorAuthKey' value='<?= htmlspecialchars($spectatorAuthKey, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='privateSpectatorAuthRequired' value='<?= $privateSpectatorAuthRequired ? '1' : '0'; ?>'>
    <input type='hidden' id='folderPath' value='<?= htmlspecialchars($folderPath, ENT_QUOTES, 'UTF-8'); ?>'>


  </body>
