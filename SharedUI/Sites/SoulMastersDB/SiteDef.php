<?php return [
  'identity' => [
    'rootName' => 'SoulMastersDB',
    'appName'  => 'Soul Masters DB',
    'ipOwner'  => 'Soul Master Games LLC',
    'tcgName'  => 'Soul Masters',
  ],
  'theme' => 'neutral',   // no overlay; tokens.css defaults
  'branding' => [
    'title'          => 'Soul Masters DB',                 // home-header h1
    'headTitle'      => 'Soul Masters Deck Builder',        // browser-tab <title>
    'tagline'        => 'Soul Masters Deckbuilder',
    'homeHref'       => '/TCGEngine/SharedUI/Sites/SoulMastersDB/MainMenu.php',
    'favicon'        => '/TCGEngine/Assets/Images/icons/soulMastersIcon.png',
    'showBanner'     => true,
    'disclaimerName' => 'Soul Masters DB',
  ],
  'head' => [
    'styles'  => [],   // derived from `theme` (neutral) via _RenderThemeStack
    'scripts' => [],
    'fonts'   => ['Barlow', 'Teko'],
  ],
  'nav' => [
    ['label'=>'Soul Masters','href'=>'https://www.soulmasterstcg.com/'],
    ['label'=>'Support','href'=>'https://www.patreon.com/c/OotTheMonk','target'=>'_blank'],
    ['label'=>'Decks','href'=>'/TCGEngine/Stats/SoulMastersDecks.php'],
    // Self-contained Sites/SoulMastersDB/ paths (repointed from top-level — intended).
    ['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/SoulMastersDB/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Sign Up','href'=>'/TCGEngine/SharedUI/Sites/SoulMastersDB/Signup.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/SoulMastersDB/LoginPage.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/soulmasterstcg'],
  ],
  'profile' => [
    'sections'         => ['welcome'],
    'oauthAppLabel'    => 'Soul Masters DB',
    'patreonFinalPage' => 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php',
    'discordClientID'  => '1338995198730043432',
  ],
];
