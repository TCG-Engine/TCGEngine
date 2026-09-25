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
    'favicon'        => '/TCGEngine/SharedUI/Sites/SWUSim/assets/petranaki-favicon.png',
    // Header emblem (also the faint watermark on the menu's Welcome card). The SVG is solid black; the menu CSS tints it
    // gold with a filter (swusim-overrides.css .title-logo, swusim-menu.css .swu-welcome__mark).
    'logo'           => '/TCGEngine/SharedUI/Sites/SWUSim/assets/stadium.svg',
    'showBanner'     => false,
    'menuOverlay'    => true,   // renders the burger button + overlay (see MenuBar.php); paired with burger-menu.js below
    'disclaimerName' => 'Petranaki Arena',
  ],
  'head' => [
    'styles'  => [   // shared stack derived from `theme` (hud); only the app override remains
      // ⚠ BEFORE the override file, which uses its tokens. The recipe is split out so the
      // in-game board (GameLayout.php, which never loads swusim-overrides.css) can share it.
      '/TCGEngine/SharedUI/Sites/SWUSim/css/petranaki-glass.css',
      '/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-overrides.css',
      // The redesigned menu. UNLAYERED and LAST on purpose: menuStyles.css's unlayered
      // bare-element resets would beat a layered stylesheet regardless of specificity.
      '/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-menu-2.css',
    ],
    'scripts' => ['/TCGEngine/Core/AppSettings.js',
                  '/TCGEngine/SharedUI/js/burger-menu.js'],
    // Archivo is the menu redesign's typeface; Barlow and Teko stay for the game board and the
    // other pages, which are still set in them.
    'fonts'   => ['Barlow', 'Teko', 'Archivo'],
  ],
  'nav' => [
    ['label'=>'Previews','href'=>'/TCGEngine/SharedUI/Sites/SWUSim/Previews.php'],
    ['label'=>'Support','href'=>'https://www.patreon.com/c/ninintcg','target'=>'_blank'],
    ['label'=>'Profile','href'=>'/TCGEngine/SharedUI/Sites/SWUSim/Profile.php','visibility'=>'loggedIn'],
    ['label'=>'Log Out','href'=>'/TCGEngine/AccountFiles/LogoutUser.php','visibility'=>'loggedIn'],
    ['label'=>'Sign Up','href'=>'/TCGEngine/SharedUI/Sites/SWUSim/Signup.php','visibility'=>'loggedOut'],
    ['label'=>'Log In','href'=>'/TCGEngine/SharedUI/Sites/SWUSim/LoginPage.php','visibility'=>'loggedOut'],
  ],
  'navLinks' => [
    ['kind'=>'icon','icon'=>'discord.svg','href'=>'https://discord.gg/a8EFSmAcqQ','title'=>'Petranaki Arena Development Server'],
    ['kind'=>'icon','icon'=>'github.svg','href'=>'https://github.com/TCG-Engine/TCGEngine'],
    // Menu-settings gear removed for now — its only control (board background) was a no-op,
    // and cosmetics are adjusted from the Profile menu. The dormant #ga-settings-modal markup
    // + JS stay in MainMenu.php so this can be re-added by restoring this nav entry.
  ],
  'deckLibrary' => [
    'storage'  => 'account',
    'endpoint' => 'SWUSim/SavedDecks.php',
  ],
  // Opts Login/Signup into the redesigned single-card layout (SharedUI/Render/Auth.php).
  // The renderers are shared with FaBSim, HellbreakSim, SWUDeck and HellbreakDeck; without this
  // key they render exactly what they always did, which RunRenderTests asserts positively.
  'auth' => [
    'layout' => 'arena',
  ],
  // Opts this sim into the shared WaitingRoom page (SharedUI/Render/WaitingRoom.php).
  // Presence of this block IS the opt-in — the adapter supplies everything sim-specific (routing
  // predicate, seat model, deck validation, start gate), so nothing else here needs a key.
  'waitingRoom' => [
    'adapter' => 'SWUSim/LobbyAdapter.php',
  ],
  // Order of the "Run Build Pipeline" steps on zzCodeGeneratorMain.php. This is a DEPENDENCY CHAIN,
  // not a preference: `cards` (zzCardCodeGenerator) writes the dictionaries that `keywords`
  // (Data/ProcessKeywordsSWU.php) then parses, and `site` regenerates entry files over everything else.
  // ⚠ Steps this app does not have are ignored, and any step NOT named here still runs, last — so a
  // newly added generator can never be silently dropped by a stale list.
  // Valid ids: hellbreak-workbook · cards · game · turn · hellbreak-deck · keywords · site
  // ⚠ Deliberately NOT the historical order (which was cards → game → turn → keywords → site).
  // Cheap, near-certain steps run FIRST so the expensive card fetch is last; the pipeline stops on the
  // first failure, so this gets the trivial wiring out of the way before anything slow is attempted.
  //
  // The ONE hard dependency is preserved: `keywords` (Data/ProcessKeywordsSWU.php) READS
  // SWUSim/GeneratedCode/cardArrayCache.json, which the `cards` step (zzCardCodeGenerator) WRITES — so
  // keywords must always follow cards. Run it earlier and it silently parses the PREVIOUS build's card
  // data: the file is written successfully, just stale.
  //
  // Everything else is genuinely independent, verified rather than assumed: GenerateSites reads only
  // SiteDef.php (zero refs to GeneratedCode/cardArrayCache/TurnStates/ZoneAccessors), and the game and
  // turn generators do not read each other's output in either direction.
  //
  // ⚠ Trade-off of putting `site` first: a trivial failure there now blocks a card regen that would
  // have succeeded. With `cards` first the opposite holds — a card-fetch failure stops everything
  // early, which is often what you want since the rest is downstream of it. Chosen deliberately.
  'pipelineActionsOrder' => ['site', 'turn', 'game', 'cards', 'keywords'],

  'profile' => [
    // 'arena' drops RenderProfile's injected flex-row layout so swusim-menu-2.css can lay the
    // panes out in columns; the pane-neutralisation half of that style is kept for every site.
    'layout'           => 'arena',
    // One pane per TASK, ordered so the columns balance. Cosmetics and sounds are merged
    // because they are the same thing to a player — how the game looks and sounds — and were
    // only ever separate because the config grew that way. Saved Decks stands alone (it is the
    // substantial one) and Blocked Users stands alone (it is two controls).
    'sections'         => ['savedDecks','welcome+changePassword','cosmetics+sounds','blockedUsers'],
    'oauthAppLabel'    => 'Petranaki Arena',
    'patreonFinalPage' => 'https://swustats.net/TCGEngine/SharedUI/MainMenu.php',
    'discordOAuth'     => true,
  ],
];
