<?php return [
  'identity' => [
    'rootName' => 'SWUSim',
    'appName'  => 'Petranaki Arena',
    'ipOwner'  => 'Fantasy Flight Games, Disney',
    'tcgName'  => 'Star Wars Unlimited',
  ],
  'theme' => 'petranaki-hud',   // HUD geometry/shapes recolored with the Petranaki sandy-gold palette; menu adopts it too
  'branding' => [
    'title'          => 'Petranaki Arena',                          // home-header h1 (also browser-tab title)
    'tagline'        => 'Fan-made Star Wars: Unlimited simulator',
    'homeHref'       => '/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php',
    'favicon'        => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
    'showBanner'     => false,
    'disclaimerName' => 'Petranaki Arena',
  ],
  'head' => [
    'styles'  => [   // shared stack derived from `theme` (hud); only the app override remains
      '/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-overrides.css',
    ],
    'scripts' => ['/TCGEngine/Core/AppSettings.js'],
    'fonts'   => ['Barlow', 'Teko'],
  ],
  'nav' => [
    ['label'=>'Support','href'=>'https://www.patreon.com/c/OotTheMonk','target'=>'_blank'],
    ['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/SWUSim/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Sign Up','href'=>'/TCGEngine/SharedUI/Sites/SWUSim/Signup.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/SWUSim/LoginPage.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/b9nfNyVFpM','title'=>'Petranaki Arena Development Server'],
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine'],
    // Menu-settings gear removed for now — its only control (board background) was a no-op,
    // and cosmetics are adjusted from the Profile menu. The dormant #ga-settings-modal markup
    // + JS stay in MainMenu.php so this can be re-added by restoring this nav entry.
  ],
  'deckLibrary' => [
    'storage'  => 'account',
    'endpoint' => 'SWUSim/SavedDecks.php',
  ],
  'profile' => [
    'sections'         => ['welcome+changePassword','savedDecks+blockedUsers','cosmetics'],
    'oauthAppLabel'    => 'Petranaki Arena',
    'patreonFinalPage' => 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php',
    'discordClientID'  => '1338995198730043432',
  ],
];
