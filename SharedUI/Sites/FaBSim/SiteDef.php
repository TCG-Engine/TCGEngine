<?php return [
  'identity'=>['rootName'=>'FaBSim','appName'=>'FaBSim','ipOwner'=>'Legend Story Studios','assetOwner'=>'Legend Story Studios and their respective artists','tcgName'=>'Flesh and Blood','disclaimerLead'=>'FaBSim is a fan-made project and is not affiliated with Legend Story Studios.'],
  'theme'=>'clarent',
  'branding'=>['title'=>'FaBSim','headTitle'=>'FaBSim — Flesh and Blood Simulator','tagline'=>'Fan-made Flesh and Blood simulator','homeHref'=>'/TCGEngine/SharedUI/Sites/FaBSim/MainMenu.php','favicon'=>'/TCGEngine/Assets/Images/icons/gudnakIcon.png','showBanner'=>false,'menuOverlay'=>true,'disclaimerName'=>'FaBSim'],
  'head'=>['styles'=>['/TCGEngine/SharedUI/Sites/FaBSim/css/fab-overrides.css'],'scripts'=>['/TCGEngine/SharedUI/js/burger-menu.js'],'fonts'=>['Barlow']],
  'nav'=>[['label'=>'Deck Builder','href'=>'/TCGEngine/FaBDeck/CreateDeck.php'],['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/FaBSim/Profile.php','visibility'=>'loggedIn'],['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],['label'=>'Create Account','href'=>'/TCGEngine/SharedUI/Sites/FaBSim/Signup.php','visibility'=>'loggedOut'],['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/FaBSim/LoginPage.php','visibility'=>'loggedOut']],
  'navLinks'=>[['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine','title'=>'View FaBSim on GitHub']],
  'deckLibrary'=>['storage'=>'local','localStorageKey'=>'tcgengine:savedDecks:FaBSim','emptyText'=>'No saved decks yet.'],
  'profile'=>['sections'=>['welcome'],'oauthAppLabel'=>'FaBSim','discordOAuth'=>true],
];
