<?php return [
  'identity' => [
    'rootName' => 'HellbreakSim',
    'appName' => 'Hellbreak Sim',
    'ipOwner' => 'the creators of Hellbreak',
    'assetOwner' => 'their respective owners',
    'tcgName' => 'Hellbreak',
    'disclaimerLead' => 'Hellbreak Sim is a fan-made project and is not affiliated with the creators of Hellbreak.',
  ],
  'theme' => 'hellish',   // ink/ember-gold/crimson; drives the menu, the board and the tutorial
  'branding' => [
    'title' => 'Hellbreak',
    'headTitle' => 'Hellbreak — Deck Builder & Simulator',
    'tagline' => 'Fan-made deck builder and simulator',
    'homeHref' => '/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php',
    'favicon' => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
    'showBanner' => false,
    'menuOverlay' => true,
    'disclaimerName' => 'Hellbreak Sim',
  ],
  'head' => [
    'styles' => ['/TCGEngine/SharedUI/Sites/HellbreakSim/css/main-menu.css'],
    'scripts' => ['/TCGEngine/SharedUI/js/burger-menu.js'],
    'fonts' => ['Barlow'],
  ],
  'nav' => [
    ['label'=>'Home','href'=>'/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php'],
    ['label'=>'Log Out','icon'=>'zendo-logout.svg','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Create Account','href'=>'/TCGEngine/SharedUI/Sites/HellbreakSim/Signup.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FHellbreakSim%2FMainMenu.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/HellbreakSim/LoginPage.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FHellbreakSim%2FMainMenu.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine','title'=>'View TCGEngine on GitHub'],
  ],
  'profile' => ['sections' => ['welcome'], 'oauthAppLabel' => 'Hellbreak Sim', 'discordOAuth' => true],
];
