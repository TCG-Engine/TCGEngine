<?php return [
  'identity' => [
    'rootName' => 'SWUDeck',                       // internal id / dir name
    'appName'  => 'SWU Stats',                      // public-facing name
    'ipOwner'  => 'Fantasy Flight Games, Disney',   // rights holder
    'tcgName'  => 'Star Wars Unlimited',            // the game/franchise referenced in legal text
  ],
  'theme' => 'hud',   // single design-system theme for menu + in-game (Phase 0)
  'branding' => [
    'title'      => 'SWU Stats',
    'tagline'    => 'Star Wars Unlimited Stats',
    'homeHref'   => '/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php',
    'favicon'    => '/TCGEngine/Assets/Images/blueDiamond.png',
    'showBanner' => true,
    'menuOverlay' => true,
    'disclaimerName' => 'SWU Stats',
  ],
  'head' => [
    'styles'  => [   // SWUDeck-specific visual overrides layered over the shared theme stack
      '/TCGEngine/SharedUI/Sites/SWUDeck/css/swudeck-overrides.css',
    ],
    'scripts' => ['/TCGEngine/SharedUI/js/device-detector.js',
                  '/TCGEngine/SharedUI/js/burger-menu.js'],
    'fonts'   => ['Barlow', 'Teko'],
  ],
  'nav' => [
    ['label'=>'Support','href'=>'https://www.patreon.com/c/OotTheMonk','target'=>'_blank'],
    ['label'=>'APIs','href'=>'/TCGEngine/Stats/APIs.php'],
    ['label'=>'Decks','href'=>'/TCGEngine/Stats/Decks.php'],
    ['label'=>'Stats','kind'=>'dropdown','children'=>[
        ['label'=>'Deck Stats','href'=>'/TCGEngine/Stats/DeckMetaStats.php'],
        ['label'=>'Card Stats','href'=>'/TCGEngine/Stats/CardMetaStats.php'],
        ['label'=>'Melee Tournaments','href'=>'/TCGEngine/Stats/MeleeTournaments.php'],
    ]],
    // Self-contained Sites/SWUDeck/ paths (repointed from top-level SharedUI/ — intended change).
    ['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/SWUDeck/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Sign Up','href'=>'/TCGEngine/SharedUI/Sites/SWUDeck/Signup.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/SWUDeck/LoginPage.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine'],
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/5ZHXyVvVFC'],
  ],
  'profile' => [
    'sections'         => ['welcome+changePassword','team','developerOptions'],
    'oauthAppLabel'    => 'SWUDeck',
    'patreonFinalPage' => 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php',
    'discordOAuth'     => true,
  ],
];
