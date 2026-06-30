<?php return [
  'identity' => [
    'rootName' => 'AzukiSim',
    'appName'  => 'Azuki Sim',
    'ipOwner'  => 'the creators of Azuki',
    'tcgName'  => 'Azuki',
  ],
  'branding' => [
    'title'          => 'Azuki Sim',                       // home-header h1
    'headTitle'      => 'Azuki Simulator',                 // browser-tab <title>
    'tagline'        => 'Fan-made automated Azuki simulator',
    'homeHref'       => '/TCGEngine/SharedUI/Sites/AzukiSim/MainMenu.php',
    'favicon'        => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
    'showBanner'     => false,
    'disclaimerName' => 'Azuki Sim',
  ],
  'head' => [
    'styles'  => ['/TCGEngine/SharedUI/Sites/AzukiSim/css/ClarentMenuStyles.css'],
    'scripts' => [],
    'fonts'   => ['Barlow', 'Teko'],
  ],
  'nav' => [
    ['label'=>'Support','href'=>'https://www.patreon.com/c/OotTheMonk','target'=>'_blank'],
    ['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/AzukiSim/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/b9nfNyVFpM'],
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine'],
  ],
  'profile' => [
    'sections'         => ['discord','team'],
    'oauthAppLabel'    => 'Azuki Sim',
    'patreonFinalPage' => 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php',
    'discordClientID'  => '1338995198730043432',
  ],
];
