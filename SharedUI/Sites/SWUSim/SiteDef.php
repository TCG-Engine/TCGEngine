<?php return [
  'identity' => [
    'rootName' => 'SWUSim',
    'appName'  => 'Petranaki Arena',
    'ipOwner'  => 'Fantasy Flight Games, Disney',
    'tcgName'  => 'Star Wars Unlimited',
  ],
  'branding' => [
    'title'          => 'Petranaki Arena',                          // home-header h1 (also browser-tab title)
    'tagline'        => 'Fan-made Star Wars: Unlimited simulator',
    'homeHref'       => '/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php',
    'favicon'        => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
    'showBanner'     => false,
    'disclaimerName' => 'Petranaki Arena',
  ],
  'head' => [
    'styles'  => [
      '/TCGEngine/SharedUI/css/tokens.css',
      '/TCGEngine/SharedUI/css/components.css',
      '/TCGEngine/SharedUI/Themes/petranaki.tokens.css',
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
    ['kind'=>'raw','html'=>'<li><button type="button" id="ga-open-settings-btn" title="Menu Settings" aria-label="Open menu settings" style="background: transparent; border: 0; outline: 0; box-shadow: none; appearance: none; -webkit-appearance: none; padding: 0; margin: 0; width: 25px; height: 25px; cursor: pointer; color: inherit; display: inline-flex; align-items: center; justify-content: center; line-height: 0;"><svg width="25" height="25" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="display: block;"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.573 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.607 2.296.07 2.573-1.065Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/></svg></button></li>'],
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
