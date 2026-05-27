<?php


include_once __DIR__ . '/../../../Core/HTTPLibraries.php';

session_start();

if (!isset($_SESSION["userid"])) {
  if (isset($_COOKIE["rememberMeToken"])) {
    //loginFromCookie();
  }
}

$isPatron = isset($_SESSION["isPatron"]);

$isMobile = IsMobile();

?>

<head>
  <meta charset="utf-8">
  <title>Grand Archive Simulator</title>
  <link rel="icon" type="image/png" href="/TCGEngine/Assets/Images/icons/gudnakIcon.png">
  <link rel="stylesheet" href="./css/ClarentMenuStyles.css">
  <script src="/TCGEngine/Core/AppSettings.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">
</head>

<body>

  <div class='nav-bar'>

    <div class='nav-bar-user'>
      <ul class='rightnav'>
        <?php //if($isPatron) echo "<li><a href='Replays.php'>Replays[BETA]</a></li>";
        ?>
        <?php
        echo "<li><a href='https://www.patreon.com/c/OotTheMonk' target='_blank' class='NavBarItem'>Support</a></li>";
        //echo "<li><a href='../Stats/GameStats.php' class='NavBarItem'>Stats</a></li>";
        if (isset($_SESSION["useruid"])) {
          //echo "<li><a href='ProfilePage.php' class='NavBarItem'>Profile</a></li>";
          //echo "<li><a href='./Profile.php' class='NavBarItem'>Profile</a></li>";
          //echo "<li><a href='../AccountFiles/LogoutUser.php' class='NavBarItem'>Log Out</a></li>";
        } else {
          //echo "<li><a href='/TCGEngine/SharedUI/Signup.php' class='NavBarItem'>Sign Up</a></li>";
          //echo "<li><a href='/TCGEngine/SharedUI/LoginPage.php' class='NavBarItem'>Log In</a></li>";
        }
        ?>
      </ul>
    </div>

    <div class='nav-bar-links'>
      <ul>
          <?php
            echo '<li><a target="_blank" href="https://discord.gg/b9nfNyVFpM" title="Clarent Development Server"><img src="/TCGEngine/Assets/Images/icons/discord.svg"></img></a></li>';
            echo '<li><a target="_blank" href="https://github.com/TCG-Engine/TCGEngine"><img src="../../../Assets/Images/icons/github.svg"></img></a></li>';
            echo '<li><button type="button" id="ga-open-settings-btn" title="Menu Settings" aria-label="Open menu settings" style="background: transparent; border: 0; outline: 0; box-shadow: none; appearance: none; -webkit-appearance: none; padding: 0; margin: 0; width: 25px; height: 25px; cursor: pointer; color: inherit; display: inline-flex; align-items: center; justify-content: center; line-height: 0;"><svg width="25" height="25" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="display: block;"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.573 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.607 2.296.07 2.573-1.065Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/></svg></button></li>';
          ?>
      </ul>
    </div>

  </div>
