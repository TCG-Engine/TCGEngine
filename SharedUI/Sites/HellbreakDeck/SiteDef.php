<?php
// HellbreakDeck renders no menu pages of its own — index.php redirects to HellbreakSim's
// MainMenu, and its only surface is the deck-builder board served by NextTurn.php with
// folderPath=HellbreakDeck. That board resolves its design-system stack via LoadSiteDef($folderPath),
// so without this file the builder falls back to the neutral theme while the sim renders 'hellish'.
// Deliberately minimal: only `theme` is read in-game; the branding/nav keys exist so the
// SiteDef shape stays valid if a menu page is ever added here (cf. Sites/FaBDeck/SiteDef.php).
return [
  'identity' => [
    'rootName'    => 'HellbreakDeck',
    'appName'     => 'northbeach.gg Deck Builder',
    'ipOwner'     => 'the creators of Hellbreak',
    'assetOwner'  => 'their respective owners',
    'tcgName'     => 'Hellbreak',
  ],
  'theme' => 'hellish',
  'branding' => [
    'title'     => 'northbeach.gg',
    'headTitle' => 'northbeach.gg — Deck Builder',
    'homeHref'  => '/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php',
    'favicon'   => '/TCGEngine/Assets/Images/icons/gudnakIcon.png',
  ],
  'head' => ['styles' => [], 'scripts' => [], 'fonts' => ['Barlow']],
  'nav' => [], 'navLinks' => [], 'profile' => ['sections' => []],
];
