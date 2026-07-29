<?php return [
  'identity' => [
    'rootName' => 'AzukiSim',
    'appName'  => 'Zendō',
    'ipOwner'  => 'the creators of Azuki',
    'assetOwner' => 'their respective owners',
    'tcgName'  => 'Azuki',
    'disclaimerLead' => 'Zendō is a fan-made project and is not affiliated with the creators of Azuki.',
  ],
  'theme' => 'clarent',
  'branding' => [
    'title'          => 'Zendō',                           // home-header h1
    'headTitle'      => 'Zendō — Azuki Simulator',         // browser-tab <title>
    'tagline'        => 'Fan-made automated Azuki simulator',
    'homeHref'       => '/TCGEngine/SharedUI/Sites/AzukiSim/MainMenu.php',
    'favicon'        => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
    'showBanner'     => false,
    'menuOverlay'    => true,
    'disclaimerName' => 'Azuki Sim',
  ],
  'head' => [
    'styles'  => [   // shared stack derived from `theme` (clarent); only the app override remains
      '/TCGEngine/SharedUI/Sites/AzukiSim/css/azuki-overrides.css',
      '/TCGEngine/SharedUI/Sites/AzukiSim/css/matches.css',
    ],
    'scripts' => ['/TCGEngine/SharedUI/js/burger-menu.js'],
    'fonts'   => ['Barlow'],
  ],
  'nav' => [
    ['label'=>'Support','icon'=>'zendo-support.svg','href'=>'https://www.patreon.com/c/OotTheMonk','target'=>'_blank'],
    ['label'=>'Matches','href'=>'/TCGEngine/SharedUI/Sites/AzukiSim/Matches.php','visibility'=>'loggedIn'],
    ['label'=>'Profile','icon'=>'zendo-profile.svg','href'=>'/TCGEngine/SharedUI/Sites/AzukiSim/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','icon'=>'zendo-logout.svg','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Create Account','href'=>'/TCGEngine/SharedUI/Sites/AzukiSim/Signup.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FAzukiSim%2FMainMenu.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/AzukiSim/LoginPage.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FAzukiSim%2FMainMenu.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/b9nfNyVFpM','title'=>'Join the Zendō Discord'],
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine','title'=>'View Zendō on GitHub'],
  ],
  'deckLibrary' => [
    'storage'         => 'local',
    'localStorageKey' => 'tcgengine:savedDecks:AzukiSim',
    'emptyText'       => 'No saved decks yet - paste a deck link and save it.',
  ],
  'profile' => [
    'sections'         => ['welcome'],
    'oauthAppLabel'    => 'Azuki Sim',
    'patreonFinalPage' => 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php',
    'discordOAuth'     => true,
  ],
];
