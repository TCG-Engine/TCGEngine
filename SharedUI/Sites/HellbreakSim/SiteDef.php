<?php return [
  'identity' => [
    'rootName' => 'HellbreakSim',
    'appName' => 'northbeach.gg',
    'ipOwner' => 'the creators of Hellbreak',
    'assetOwner' => 'their respective owners',
    'tcgName' => 'Hellbreak',
    'disclaimerLead' => 'northbeach.gg is a fan-made Hellbreak project and is not affiliated with the creators of Hellbreak.',
  ],
  'theme' => 'hellish',   // ink/ember-gold/crimson; drives the menu, the board and the tutorial
  'branding' => [
    'title' => 'northbeach.gg',
    'headTitle' => 'northbeach.gg — Hellbreak Deck Builder & Simulator',
    'tagline' => 'There’s something in the water.',
    'homeHref' => '/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php',
    'favicon' => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
    'showBanner' => false,
    'menuOverlay' => true,
    'disclaimerName' => 'northbeach.gg',
  ],
  'head' => [
    'styles' => [
      '/TCGEngine/SharedUI/Sites/HellbreakSim/css/main-menu.css',
      '/TCGEngine/SharedUI/Sites/HellbreakSim/css/north-beach-vignette.css',
    ],
    'scripts' => [
      '/TCGEngine/SharedUI/js/burger-menu.js',
      '/TCGEngine/SharedUI/Sites/HellbreakSim/js/north-beach-vignette.js',
    ],
    'fonts' => ['Barlow'],
  ],
  'nav' => [
    ['label'=>'Profile','icon'=>'zendo-profile.svg','href'=>'/TCGEngine/SharedUI/Sites/HellbreakSim/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','icon'=>'zendo-logout.svg','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Create Account','href'=>'/TCGEngine/SharedUI/Sites/HellbreakSim/Signup.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FHellbreakSim%2FMainMenu.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/HellbreakSim/LoginPage.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FHellbreakSim%2FMainMenu.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine','title'=>'View TCGEngine on GitHub'],
  ],
  'profile' => ['sections' => ['welcome'], 'oauthAppLabel' => 'northbeach.gg', 'discordOAuth' => true],
];
