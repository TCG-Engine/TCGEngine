<?php return [
  'identity' => [
    'rootName' => 'GudnakSim',
    'appName'  => 'Gudnak Simulator',
    'ipOwner'  => 'the creators of Gudnak',
    'tcgName'  => 'Gudnak',
  ],
  'theme' => 'gudnak',
  'branding' => [
    'title'          => 'Gudnak Simulator',                // home-header h1 (also the browser-tab title)
    'tagline'        => 'Master the Ultimate Card Battle',
    'homeHref'       => '/TCGEngine/SharedUI/Sites/GudnakSim/MainMenu.php',
    'favicon'        => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
    'showBanner'     => true,
    'disclaimerName' => 'Gudnak Simulator',
  ],
  'head' => [
    'styles'  => [   // shared stack derived from `theme` (gudnak); only the app override remains
      '/TCGEngine/SharedUI/Sites/GudnakSim/css/gudnak-overrides.css',
    ],
    'scripts' => [],
    'fonts'   => ['Barlow', 'Teko'],
  ],
  'nav' => [
    ['label'=>'Support','href'=>'https://www.patreon.com/c/OotTheMonk','target'=>'_blank'],
    ['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/GudnakSim/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Sign Up','href'=>'/TCGEngine/SharedUI/Sites/GudnakSim/Signup.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/GudnakSim/LoginPage.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/gudnak'],
  ],
  'profile' => [
    'sections'         => ['welcome'],
    'oauthAppLabel'    => 'Gudnak Simulator',
    'patreonFinalPage' => 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php',
    'discordClientID'  => '1338995198730043432',
  ],
];
