<?php return [
  'identity' => [
    'rootName' => 'AzukiSim',
    'appName'  => 'Azuki Sim',
    'ipOwner'  => 'the creators of Azuki',
    'tcgName'  => 'Azuki',
  ],
  'theme' => 'clarent',
  'branding' => [
    'title'          => 'Azuki Sim',                       // home-header h1
    'headTitle'      => 'Azuki Simulator',                 // browser-tab <title>
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
    ],
    'scripts' => ['/TCGEngine/SharedUI/js/burger-menu.js'],
    'fonts'   => ['Barlow', 'Teko'],
  ],
  'nav' => [
    ['label'=>'Deck Builder','href'=>'/TCGEngine/AzukiDeck/'],
    ['label'=>'Support','href'=>'https://www.patreon.com/c/OotTheMonk','target'=>'_blank'],
    ['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/AzukiSim/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/b9nfNyVFpM'],
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine'],
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
