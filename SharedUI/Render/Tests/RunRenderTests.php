<?php
// Curl-invoked render test harness. Run: curl http://localhost:3100/TCGEngine/SharedUI/Render/Tests/RunRenderTests.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
header('Content-Type: text/plain');
require_once __DIR__ . '/../SiteDef.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond) {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; }
    else { $FAIL++; $MSGS[] = "FAIL: $name"; }
}
function checkContains($name, $haystack, $needle) {
    check($name, is_string($haystack) && strpos($haystack, $needle) !== false);
}

$def = LoadSiteDef('SWUDeck');

// --- Task 1 tests: validator ---
check('valid def has no errors', ValidateSiteDef($def) === []);
check('missing title is caught', in_array('branding.title is required', ValidateSiteDef(['branding'=>[],'nav'=>[]])));
check('unknown section caught', (function() {
    $bad = LoadSiteDef('SWUDeck'); $bad['profile']['sections'][] = 'bogus';
    return in_array("profile.sections has unknown section 'bogus'", ValidateSiteDef($bad));
})());

// --- Task 2 tests: RenderHead ---
require_once __DIR__ . '/../Head.php';
$head = RenderHead($def);
checkContains('head has title', $head, '<title>SWU Stats</title>');
checkContains('head has favicon', $head, 'href="/TCGEngine/Assets/Images/blueDiamond.png"');
checkContains('head has menuStyles', $head, '/TCGEngine/SharedUI/css/menuStyles.css');
checkContains('head has device-detector', $head, '/TCGEngine/SharedUI/js/device-detector.js');
checkContains('head has burger-menu', $head, '/TCGEngine/SharedUI/js/burger-menu.js');
checkContains('head has Barlow font', $head, 'family=Barlow');
checkContains('head has Teko font', $head, 'family=Teko');

// --- Task 3 tests: RenderMenuBar ---
require_once __DIR__ . '/../MenuBar.php';
$navOut = RenderMenuBar($def, ['isLoggedIn'=>false,'isPatron'=>false,'username'=>null,'userId'=>null]);
$navIn  = RenderMenuBar($def, ['isLoggedIn'=>true,'isPatron'=>false,'username'=>'tester','userId'=>5]);
checkContains('menubar embeds head', $navOut, '<title>SWU Stats</title>');
checkContains('menubar has Support', $navOut, "https://www.patreon.com/c/ninintcg");
checkContains('menubar has Stats dropdown', $navOut, "class='dropdown'");
checkContains('menubar has Deck Stats child', $navOut, '/TCGEngine/Stats/DeckMetaStats.php');
checkContains('menubar has github icon', $navOut, 'icons/github.svg');

// --- The current page is marked (owner-reported, 2026-09-25) --------------------
// The nav had no current-page indicator at all, so SWUSim's "Log In" call-to-action plate was
// painted on every page — including Log In itself, where it points at the page you are already
// on, and Sign Up, where it competes with that page's own Create Account button and reads as a
// selected tab. aria-current is the honest fix: correct HTML, additive for every other sim, and
// something the stylesheet can key on.
// the FIRST INTERNAL item — nav[0] is an external Patreon link with target=_blank, which is
// correctly never "current", so hard-coding index 0 tested nothing
$navHref = '';
foreach (($def['nav'] ?? []) as $n) {
    $h = (string)($n['href'] ?? '');
    if ($h !== '' && $h !== '#' && empty($n['target']) && !preg_match('#^[a-z]+://#i', $h)) { $navHref = $h; break; }
}
check('the test found an internal nav item to mark', $navHref !== '');
$prevUri = $_SERVER['REQUEST_URI'] ?? null;
$_SERVER['REQUEST_URI'] = $navHref . '?x=1';          // query string must not defeat the match
$navHere = RenderMenuBar($def, ['isLoggedIn'=>false,'isPatron'=>false,'username'=>null,'userId'=>null]);
checkContains('the nav marks the page you are on', $navHere, "aria-current='page'");
check('only ONE item is marked current', substr_count($navHere, "aria-current='page'") === 1,
    substr_count($navHere, "aria-current='page'") . ' marked');
$_SERVER['REQUEST_URI'] = '/TCGEngine/somewhere/else.php';
$navAway = RenderMenuBar($def, ['isLoggedIn'=>false,'isPatron'=>false,'username'=>null,'userId'=>null]);
check('nothing is marked current on an unrelated page',
    strpos($navAway, 'aria-current') === false);
if ($prevUri === null) unset($_SERVER['REQUEST_URI']); else $_SERVER['REQUEST_URI'] = $prevUri;
checkContains('menubar has discord icon', $navOut, 'discord.gg/5ZHXyVvVFC');
checkContains('menubar renders burger on first paint', $navOut, 'class="burger-menu"');
checkContains('menubar burger has accessible label', $navOut, 'aria-label="Open navigation"');
checkContains('loggedout has Log In', $navOut, '/TCGEngine/SharedUI/Sites/SWUDeck/LoginPage.php');
check('loggedout hides Profile', strpos($navOut, 'SWUDeck/Profile.php') === false);
checkContains('loggedin has Profile', $navIn, '/TCGEngine/SharedUI/Sites/SWUDeck/Profile.php');
check('loggedin hides Log In', strpos($navIn, 'SWUDeck/LoginPage.php') === false);

$azukiDef = LoadSiteDef('AzukiSim');
$azukiNavOut = RenderMenuBar($azukiDef, ['isLoggedIn'=>false,'isPatron'=>false,'username'=>null,'userId'=>null]);
$azukiNavIn = RenderMenuBar($azukiDef, ['isLoggedIn'=>true,'isPatron'=>false,'username'=>'tester','userId'=>5]);
check('Azuki nav omits Support', strpos($azukiNavOut, '>Support<') === false && strpos($azukiNavIn, '>Support<') === false);
check('Azuki nav omits Discord invite', strpos($azukiNavOut, 'discord.gg') === false && strpos($azukiNavIn, 'discord.gg') === false);
checkContains('Azuki nav keeps GitHub link', $azukiNavOut, 'https://github.com/TCG-Engine/TCGEngine');
checkContains('Azuki loggedout nav offers account creation', $azukiNavOut, '>Create Account<');
checkContains('Azuki loggedout nav offers login', $azukiNavOut, '/TCGEngine/SharedUI/Sites/AzukiSim/LoginPage.php');
checkContains('Azuki auth links return to main menu', $azukiNavOut, 'redirect=%2FTCGEngine%2FSharedUI%2FSites%2FAzukiSim%2FMainMenu.php');
check('Azuki loggedin nav hides account creation', strpos($azukiNavIn, 'AzukiSim/Signup.php') === false);
check('Azuki loggedin nav hides login', strpos($azukiNavIn, 'AzukiSim/LoginPage.php') === false);

$hellbreakDef = LoadSiteDef('HellbreakSim');
$previousScriptName = $_SERVER['SCRIPT_NAME'] ?? null;
$_SERVER['SCRIPT_NAME'] = '/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php';
$hellbreakMainNav = RenderMenuBar($hellbreakDef, BuildAuthContext());
if ($previousScriptName === null) unset($_SERVER['SCRIPT_NAME']);
else $_SERVER['SCRIPT_NAME'] = $previousScriptName;
$hellbreakProfileNav = RenderMenuBar($hellbreakDef, ['isLoggedIn'=>true,'isPatron'=>false,'username'=>'tester','userId'=>5,'currentPage'=>'Profile']);
checkContains('Hellbreak main-menu nav includes replay intro on first paint', $hellbreakMainNav, 'id="hellbreak-replay-intro"');
check('Hellbreak non-main nav omits replay intro', strpos($hellbreakProfileNav, 'id="hellbreak-replay-intro"') === false);
checkContains('Hellbreak nav has northbeach.gg Discord invite', $hellbreakMainNav, 'discord.gg/BCr4f5HBxz');
checkContains('Hellbreak nav keeps GitHub link', $hellbreakMainNav, 'https://github.com/TCG-Engine/TCGEngine');

// --- Task 4 tests: RenderHeader ---
require_once __DIR__ . '/../Header.php';
$hdr = RenderHeader($def);
checkContains('header title link', $hdr, 'href="/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php"');
checkContains('header h1', $hdr, '<h1>SWU Stats</h1>');
checkContains('header tagline', $hdr, '<p>Star Wars Unlimited Stats</p>');
checkContains('header banner block', $hdr, 'class="banner block-1"');
check('header omits dead pull-to-refresh indicator', strpos($hdr, 'pull-indicator') === false);
// branding.logo is optional: absent → the header is byte-identical to before; present → an emblem img inside the title link.
check('header without branding.logo has no emblem', strpos($hdr, 'title-logo') === false && strpos($hdr, 'has-logo') === false);
$logoDef = $def; $logoDef['branding']['logo'] = '/TCGEngine/x/logo.svg';
$hdrLogo = RenderHeader($logoDef);
checkContains('header with branding.logo renders the emblem', $hdrLogo, '<img class="title-logo" src="/TCGEngine/x/logo.svg" alt="" aria-hidden="true">');
checkContains('header with branding.logo marks the title', $hdrLogo, 'class="title has-logo"');

// --- Task 5 tests: RenderProfile + RenderDisclaimer ---
require_once __DIR__ . '/../Profile.php';
$ud = ['teamID'=>null,'team'=>null,'teamInvites'=>[]];
$ctxIn = ['isLoggedIn'=>true,'isPatron'=>false,'username'=>'tester','userId'=>5];
$_SESSION['userid'] = 5; $_SESSION['useruid'] = 'tester';
$prof = RenderProfile($def, $ctxIn, $ud);
checkContains('profile has password form', $prof, 'id="selfResetPasswordForm"');
checkContains('profile welcomes user', $prof, 'Welcome tester');
checkContains('profile has team mgmt', $prof, 'Team Management');
checkContains('profile oauthDev app label', $prof, 'connect to SWUDeck');
checkContains('disclaimer names site', RenderDisclaimer($def), 'SWU Stats is in no way affiliated');
// --- The 'arena' profile layout (SWUSim only, owner 2026-09-25) -----------------
// RenderProfile injects _ProfilePaneStyle(): a flex-row layout plus `margin: 0 !important` on
// every pane. SWUSim's redesign lays the panes out in COLUMNS instead, and that !important
// would strip the gap between stacked panes. Opting out of the layout half is additive — the
// pane-neutralisation half (so two panels joined with '+' read as one box) is kept for everyone.
$arenaProf = $def;
$arenaProf['profile']['layout'] = 'arena';
$legacyProfile = RenderProfile($def, $ctxIn, $ud);
$arenaProfile  = RenderProfile($arenaProf, $ctxIn, $ud);

check('a site that does not opt in keeps the flex pane layout',
    strpos($legacyProfile, 'flex-wrap: wrap') !== false
    && strpos($legacyProfile, 'flex: 1 1 320px') !== false);
check('the arena profile drops the flex layout', strpos($arenaProfile, 'flex: 1 1 320px') === false);
// ⚠ Assert the rule that would do the damage, not the string. `margin: 0 !important` also
// appears on `.profile-pane > .container` — the INNER panel of a '+' pane — where it is
// required and harmless. The one that kills column gaps is the one targeting the PANES.
check('the arena profile drops the pane-level layout rule',
    strpos($arenaProfile, '.core-wrapper > .container') === false
    && strpos($arenaProfile, '.core-wrapper {') === false);
check('and the legacy layout still has it',
    strpos($legacyProfile, '.core-wrapper > .container') !== false);
// the part every site still needs: two panels joined with '+' must read as ONE box
checkContains('the arena profile still neutralises nested panel chrome', $arenaProfile,
    '.profile-pane > .container');
checkContains('the arena profile still styles the pane divider', $arenaProfile, 'profile-pane-sep');
check('both layouts render the same panels',
    substr_count($legacyProfile, 'container bg-black') === substr_count($arenaProfile, 'container bg-black'));

$noPw = $def; $noPw['profile']['sections'] = ['team'];
$prof2 = RenderProfile($noPw, $ctxIn, $ud);
check('omitting password hides form', strpos($prof2, 'id="selfResetPasswordForm"') === false);

// --- Task 6 tests: RenderLoginPage + RenderSignup ---
require_once __DIR__ . '/../Auth.php';
$login = RenderLoginPage($def); $signup = RenderSignup($def);
checkContains('login posts to AttemptPasswordLogin', $login, '/TCGEngine/AccountFiles/AttemptPasswordLogin.php');
checkContains('login has remember-me', $login, 'name="rememberMe"');
checkContains('login offers account creation', $login, 'Create account');
check('login has no relative ../ urls', strpos($login, '"../') === false && strpos($login, "'../") === false);
checkContains('signup posts to signup.inc', $signup, '/TCGEngine/Database/signup.inc.php');
checkContains('signup has responsive page hook', $signup, 'signup-page');
checkContains('signup fields expose autocomplete', $signup, 'autocomplete="new-password"');
checkContains('embedded signup posts through shared fields', RenderEmbeddedSignup($def, '/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php'), '/TCGEngine/Database/signup.inc.php');
checkContains('embedded signup keeps errors on its page', RenderEmbeddedSignup($def, '/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php'), 'name="signup_return"');
$_GET['error'] = 'invalidemail';
checkContains('embedded signup renders validation errors', RenderEmbeddedSignup($def, '/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php'), 'Choose a valid email address.');
unset($_GET['error']);
checkContains('signup has pwdrepeat', $signup, 'name="pwdrepeat"');
checkContains('login has redirect field', $login, 'name="redirect"');
checkContains('signup has redirect field', $signup, 'name="redirect"');
checkContains('login redirect escapes value', RenderLoginPage($def, '/TCGEngine/x"y'), 'value="/TCGEngine/x&quot;y"');

// --- The 'arena' auth layout (SWUSim only, owner 2026-09-25) --------------------
// SWUSim's auth pages move to the redesigned card. RenderLoginPage/RenderSignup are shared with
// FaBSim, HellbreakSim, SWUDeck and HellbreakDeck, so this is an OPT-IN: every site that does
// not ask for it must render exactly what it rendered before.
$arena = $def; $arena['auth'] = ['layout' => 'arena'];
$aLogin  = RenderLoginPage($arena);
$aSignup = RenderSignup($arena);

// ⚠ Assert the DEFAULT positively, against the legacy shell's own markers. Comparing
// RenderLoginPage($def) with a $login captured the same way is a tautology: flip the default to
// arena and both sides become arena, so it passes while four other sites have just been
// re-laid-out underneath them. (Caught by mutation — the first version of this check did that.)
check('a site that does not opt in still gets the legacy login shell',
    strpos($login, 'container bg-black') !== false
    && strpos($login, 'flex-padder') !== false
    && strpos($login, 'auth__card') === false);
check('a site that does not opt in still gets the legacy signup shell',
    strpos($signup, 'signup-disclosures') !== false
    && strpos($signup, 'auth__card') === false);
checkContains('arena login uses the new card', $aLogin, 'auth__card');
checkContains('arena signup uses the new card', $aSignup, 'auth__card');
check('the old auth shell is gone when opted in',
    strpos($aLogin, 'container bg-black') === false && strpos($aLogin, 'flex-padder') === false);

// ⚠ THE LOAD-BEARING PART. A beautiful form that posts the wrong field names is a broken login.
// Every name, action and type the backend reads has to survive the re-layout.
checkContains('arena login still posts to AttemptPasswordLogin', $aLogin, '/TCGEngine/AccountFiles/AttemptPasswordLogin.php');
foreach (['name="userID"', 'name="password"', 'name="rememberMe"', 'name="redirect"', 'type="submit"'] as $needle) {
    checkContains("arena login keeps $needle", $aLogin, $needle);
}
checkContains('arena login keeps remember-me checked by default', $aLogin, 'checked');
checkContains('arena login still links signup', $aLogin, '/Signup.php');
checkContains('arena login redirect still escapes',
    RenderLoginPage($arena, '/TCGEngine/x"y'), 'value="/TCGEngine/x&quot;y"');

checkContains('arena signup still posts to signup.inc', $aSignup, '/TCGEngine/Database/signup.inc.php');
foreach (['name="uid"', 'name="email"', 'name="pwd"', 'name="pwdrepeat"', 'name="redirect"'] as $needle) {
    checkContains("arena signup keeps $needle", $aSignup, $needle);
}
checkContains('arena signup keeps its autocomplete hints', $aSignup, 'autocomplete="new-password"');
checkContains('arena signup keeps the responsive page hook', $aSignup, 'signup-page');

// Errors must still reach the player, or a failed login looks like nothing happened.
$_GET['error'] = 'invalidemail';
checkContains('arena signup renders validation errors', RenderSignup($arena), 'Choose a valid email address.');
unset($_GET['error']);
$_GET['oauth_error'] = 'Discord said no';
checkContains('arena login renders oauth errors', RenderLoginPage($arena), 'Discord said no');
unset($_GET['oauth_error']);

check('arena auth has no relative ../ urls',
    strpos($aLogin, '"../') === false && strpos($aSignup, '"../') === false);

// --- Site page generator: identity validator, templates, MobileViewport ---
require_once __DIR__ . '/../Template.php';
require_once __DIR__ . '/../Misc.php';
check('identity validator catches missing key', in_array('identity.ipOwner is required', ValidateSiteDef((function() {
    $d = LoadSiteDef('SWUDeck'); unset($d['identity']['ipOwner']); return $d;
})())));
$priv = RenderTemplate('PrivacyPolicy', $def);
checkContains('privacy fills appName', $priv, 'SWU Stats');
checkContains('privacy fills ipOwner', $priv, 'Fantasy Flight Games, Disney');
check('privacy has no unreplaced tokens', !preg_match('/\{\{[a-zA-Z]+\}\}/', $priv));
$terms = RenderTemplate('TermsOfUse', $def);
check('terms has no unreplaced tokens', !preg_match('/\{\{[a-zA-Z]+\}\}/', $terms));
checkContains('terms fills tcgName', $terms, 'Star Wars Unlimited');
checkContains('disclaimer template fills tokens', RenderTemplate('Disclaimer', $def), 'Fantasy Flight Games, Disney');
checkContains('mobileviewport static', RenderMobileViewport(), 'width=device-width');

// --- Saved decks library ---
require_once __DIR__ . '/../DeckLibrary.php';
$decks = [
  ['decklink'=>'https://swudb.com/deck/a','name'=>'Aggro','hero'=>'SOR_010','baseId'=>'SOR_022','format'=>'premier','isFavorite'=>1,'wins'=>3,'losses'=>1,'lastUsed'=>null,'deckContent'=>null],
  ['decklink'=>'https://swudb.com/deck/b','name'=>'Control','hero'=>'JTL_005','baseId'=>'JTL_020','format'=>'premier','isFavorite'=>0,'wins'=>0,'losses'=>0,'lastUsed'=>null,'deckContent'=>null],
];
// Default: name-only dropdown, no card art, no action buttons.
$lib = RenderDeckLibrary(5, ['decks'=>$decks]);
checkContains('lib renders a name dropdown', $lib, "class='dl-select'");
checkContains('lib shows deck name', $lib, 'Aggro');
checkContains('lib marks favorite with star', $lib, '★ Aggro');
checkContains('lib option carries decklink id', $lib, 'data-id="https://swudb.com/deck/a"');
check('lib shows no card art', strpos($lib, '<img') === false);
check('default has no action buttons', strpos($lib, 'data-action=') === false);
// actionButtons: selector + Favorite/Rename/Delete + management wiring.
$withBtns = RenderDeckLibrary(5, ['decks'=>$decks,'actionButtons'=>true]);
checkContains('actionButtons has favorite', $withBtns, 'data-action="favorite"');
checkContains('actionButtons has rename', $withBtns, 'data-action="rename"');
checkContains('actionButtons has delete', $withBtns, 'data-action="delete"');
checkContains('actionButtons emits wiring', $withBtns, '__deckLibWired');
checkContains('options carry win data', $withBtns, 'data-wins="3"');
checkContains('options carry loss data', $withBtns, 'data-losses="1"');
checkContains('profile variant has stats readout', $withBtns, "class='dl-stats'");
checkContains('stats wiring fetches matchups', $withBtns, "action=matchups");
check('default variant has no stats readout', strpos($lib, "class='dl-stats'") === false);
$empty = RenderDeckLibrary(5, ['decks'=>[], 'emptyText'=>'No saved decks yet.']);
checkContains('empty state', $empty, 'No saved decks yet.');
$localLib = RenderDeckLibrary(0, ['storage'=>'local','rootName'=>'GrandArchiveSim','localStorageKey'=>'tcgengine:savedDecks:GrandArchiveSim','actionButtons'=>true]);
checkContains('local lib declares local storage', $localLib, 'data-storage="local"');
checkContains('local lib carries storage key', $localLib, 'tcgengine:savedDecks:GrandArchiveSim');
checkContains('local lib exposes save hook', $localLib, 'TCGDeckLibrarySaveCurrent');

// --- savedDecks profile section ---
$swusimDef = LoadSiteDef('SWUSim');
check('SWUSim profile enables savedDecks', strpos(implode(',', $swusimDef['profile']['sections'] ?? []), 'savedDecks') !== false);
check('validator accepts savedDecks', !in_array("profile.sections has unknown section 'savedDecks'", ValidateSiteDef($swusimDef), true));
$gaDef = LoadSiteDef('GrandArchiveSim');
check('GrandArchiveSim declares local deck library', ($gaDef['deckLibrary']['storage'] ?? '') === 'local');
check('validator accepts deckLibrary config', ValidateSiteDef($gaDef) === []);

// --- Cosmetics chooser ---
require_once __DIR__ . '/../CosmeticsChooser.php';
$cos = RenderCosmeticsChooser(0);   // userId 0 -> all defaults
checkContains('cosmetics has background select', $cos, "data-slot=\"background\"");
checkContains('cosmetics has cardback select', $cos, "data-slot=\"cardback\"");
checkContains('cosmetics playmat has None', $cos, '>None<');
checkContains('cosmetics has preview', $cos, "class='cos-preview'");
checkContains('cosmetics has show-playmats toggle', $cos, "id='cos-show-playmats'");
checkContains('cosmetics has card-motion toggle', $cos, "id='cos-card-motion'");
checkContains('cosmetics posts to endpoint', $cos, 'SWUSim/Cosmetics.php');
// cosmetics now shares a pane with sounds, so it appears as 'cosmetics+sounds' — assert the
// PANEL is enabled rather than the exact entry string, which is a grouping decision.
check('SWUSim profile enables cosmetics',
    in_array('cosmetics', array_merge(...array_map(
        fn($e) => array_map('trim', explode('+', $e)),
        $swusimDef['profile']['sections'] ?? [''])), true));
check('validator accepts cosmetics', !in_array("profile.sections has unknown section 'cosmetics'", ValidateSiteDef($swusimDef), true));

// --- Profile panel registry (order-driven) + welcome gating ---
require_once __DIR__ . '/../Profile.php';
$pCtx = ['username' => 'Tester', 'userId' => 0];
$defA = ['profile' => ['sections' => ['team','blockedUsers']]];
$defB = ['profile' => ['sections' => ['blockedUsers','team']]];
$htmlA = RenderProfile($defA, $pCtx, []);
$htmlB = RenderProfile($defB, $pCtx, []);
check('panels render in sections order (team before blocked)', strpos($htmlA,'Team Management') < strpos($htmlA,'Blocked Users'));
check('order follows sections (reversed)', strpos($htmlB,'Blocked Users') < strpos($htmlB,'Team Management'));
check('unlisted panel absent (no Cosmetics)', strpos($htmlA,'>Cosmetics<') === false);

$defWD = ['profile' => ['sections' => ['welcome'], 'discordOAuth' => true]];
$defWN = ['profile' => ['sections' => ['welcome']]];
$welD = RenderProfile($defWD, $pCtx, []);
checkContains('welcome greets the user', $welD, 'Welcome Tester!');
check('welcome shows discord when configured', strpos($welD, 'discord-button') !== false || strpos($welD, 'Discord Account') !== false);
check('welcome hides discord when not configured', strpos(RenderProfile($defWN, $pCtx, []), 'discord-button') === false);
$azukiProfile = RenderProfile(LoadSiteDef('AzukiSim'), $pCtx, []);
check('Azuki profile omits Patreon login', strpos($azukiProfile, 'containerPatreon') === false);

// --- All sites validate under the new panel keys + render their listed panels ---
$expectPanels = [
  'SWUDeck'         => ['welcome+changePassword','team','developerOptions'],
  // regrouped 2026-09-25 for the redesigned profile: one pane per task, ordered so the
  // columns balance. cosmetics+sounds are merged because they are the same thing to a player.
  'SWUSim'          => ['savedDecks','welcome+changePassword','cosmetics+sounds','blockedUsers'],
  'GrandArchiveSim' => ['welcome'],
  'AzukiSim'        => ['welcome'],
  'GudnakSim'       => ['welcome'],
  'SoulMastersDB'   => ['welcome'],
];
foreach ($expectPanels as $site => $panels) {
    $sd = LoadSiteDef($site);
    check("$site validates clean", ValidateSiteDef($sd) === []);
    check("$site sections match target", ($sd['profile']['sections'] ?? []) === $panels);
}
// SWUDeck full render is DB-free (changePassword/welcome/team/developerOptions); assert its panels.
// (SWUSim's savedDecks/cosmetics panels hit swusim-DB tables not present on this harness's DB, so
//  its composition is covered by the sections-match assertion above rather than a full render.)
$swuDeckHtml = RenderProfile(LoadSiteDef('SWUDeck'), ['username'=>'T','userId'=>0], []);
checkContains('SWUDeck profile has Team Management', $swuDeckHtml, 'Team Management');
check('SWUDeck profile has no Saved Decks', strpos($swuDeckHtml,'>Saved Decks<') === false);
$swusimFlat = implode(',', LoadSiteDef('SWUSim')['profile']['sections']);
check('SWUSim drops team, includes savedDecks', strpos($swusimFlat,'team') === false && strpos($swusimFlat,'savedDecks') !== false);

// Combined-pane syntax ('a+b'): two panels merge into one .profile-pane with a divider (max 2).
$defPane = ['profile' => ['sections' => ['team+blockedUsers']]];
$paneHtml = RenderProfile($defPane, ['username'=>'T','userId'=>0], []);
checkContains('combined entry wraps a profile-pane', $paneHtml, "class='profile-pane container bg-black'");
checkContains('combined pane has a divider', $paneHtml, 'profile-pane-sep');
check('combined pane holds both panels', strpos($paneHtml,'Team Management') !== false && strpos($paneHtml,'Blocked Users') !== false);
check('combined pane keeps order (team before blocked)', strpos($paneHtml,'Team Management') < strpos($paneHtml,'Blocked Users'));
$defCap = ['profile' => ['sections' => ['team+blockedUsers+cosmetics']]];
check('validator rejects >2 combined', in_array("profile.sections entry 'team+blockedUsers+cosmetics' combines more than 2 panels", ValidateSiteDef($defCap)));
check('validator flags an unknown part in a combined entry', in_array("profile.sections has unknown section 'bogus'", ValidateSiteDef(['profile'=>['sections'=>['welcome+bogus']]])));
// (a valid combined entry passing cleanly is covered by the 'SWUSim validates clean' check above.)

// --- Task 4 (Phase 0): SiteDef theme key + validation ---
check('SWUDeck declares theme hud', LoadSiteDef('SWUDeck')['theme'] === 'hud');
check('SWUSim declares theme petranaki-hud', LoadSiteDef('SWUSim')['theme'] === 'petranaki-hud');
check('GrandArchiveSim declares theme clarent', LoadSiteDef('GrandArchiveSim')['theme'] === 'clarent');
check('validator accepts a valid theme', !in_array('theme must be a non-empty string', ValidateSiteDef(LoadSiteDef('SWUDeck')), true));
check('validator rejects empty theme', in_array('theme must be a non-empty string', ValidateSiteDef(array_merge(LoadSiteDef('SWUDeck'), ['theme'=>'']))));
check('validator rejects non-string theme', in_array('theme must be a non-empty string', ValidateSiteDef(array_merge(LoadSiteDef('SWUDeck'), ['theme'=>['x']]))));

// --- Task 5 (Phase 0): centralized menu theme stack ---
$dsHead = RenderHead(LoadSiteDef('SWUDeck'));
checkContains('shared head declares mobile viewport', $dsHead, 'name="viewport"');
checkContains('menu stack has menuStyles (hud)', $dsHead, '/TCGEngine/SharedUI/css/menuStyles.css');
checkContains('menu stack has tokens.css', $dsHead, '/TCGEngine/SharedUI/css/tokens.css');
checkContains('menu stack has components.css', $dsHead, '/TCGEngine/SharedUI/css/components.css');
checkContains('menu stack links hud theme (SWUDeck)', $dsHead, '/TCGEngine/SharedUI/Themes/hud.tokens.css');
$gaHead = RenderHead(LoadSiteDef('GrandArchiveSim'));
checkContains('clarent app links clarent theme', $gaHead, '/TCGEngine/SharedUI/Themes/clarent.tokens.css');
check('clarent app has NO menuStyles', strpos($gaHead, '/TCGEngine/SharedUI/css/menuStyles.css') === false);
$smHead = RenderHead(LoadSiteDef('SoulMastersDB'));
check('neutral app links NO theme overlay', strpos($smHead, '/TCGEngine/SharedUI/Themes/') === false);
checkContains('neutral app still has tokens.css', $smHead, '/TCGEngine/SharedUI/css/tokens.css');
checkContains('neutral app keeps menuStyles', $smHead, '/TCGEngine/SharedUI/css/menuStyles.css');
// Turnkey property: a def with EMPTY head.styles still gets the whole stack FROM the theme key.
$minimal = LoadSiteDef('SWUDeck'); $minimal['head']['styles'] = [];
$minHead = RenderHead($minimal);
checkContains('empty head.styles still yields components (from theme)', $minHead, '/TCGEngine/SharedUI/css/components.css');
checkContains('empty head.styles still yields hud theme (from theme)', $minHead, '/TCGEngine/SharedUI/Themes/hud.tokens.css');
checkContains('empty head.styles still yields menuStyles (hud base)', $minHead, '/TCGEngine/SharedUI/css/menuStyles.css');

// --- WaitingRoom: the shared, opt-in lobby page ---
// Opting in is the PRESENCE of a waitingRoom block in SiteDef. A sim without one still has the page
// (the generator emits it unconditionally) but must be redirected away rather than shown an empty room.
require_once __DIR__ . '/../WaitingRoom.php';

check('opted-in sim resolves a config',        WaitingRoomConfigFromSiteDef(LoadSiteDef('SWUSim')) !== null);
check('sim without the block resolves null',   WaitingRoomConfigFromSiteDef(LoadSiteDef('SWUDeck')) === null);

$wrCfg = WaitingRoomConfigFromSiteDef(LoadSiteDef('SWUSim'));
check('config carries the adapter path',       ($wrCfg['adapter']  ?? '') === 'SWUSim/LobbyAdapter.php');
check('config carries rootName',               ($wrCfg['rootName'] ?? '') === 'SWUSim');
// The pre-seated state offers a deck picker, which is driven by the sim's EXISTING deckLibrary block —
// opting in must not require restating it.
check('config inherits the deckLibrary block', is_array($wrCfg['deckLibrary'] ?? null));

// A block with no adapter is a misconfiguration, not an opt-in: rendering a room whose deck validation
// and routing predicate are absent would fail later and less clearly.
$wrBad = LoadSiteDef('SWUSim'); $wrBad['waitingRoom'] = [];
check('a waitingRoom block with no adapter is not an opt-in', WaitingRoomConfigFromSiteDef($wrBad) === null);

$wrHtml = (function () { ob_start(); RenderWaitingRoom(LoadSiteDef('SWUSim')); return ob_get_clean(); })();
checkContains('page has a state container',       $wrHtml, 'id="wr-root"');
checkContains('page has a roster container',      $wrHtml, 'id="wr-roster"');
checkContains('page carries rootName for the poll', $wrHtml, 'data-root-name="SWUSim"');
checkContains('script declares the storage key prefix', $wrHtml, 'tcg:lobbyAuth:');

// The four states are a DOM CONTRACT the cross-browser harness asserts against, so pin the pieces
// that contract depends on rather than trusting the script to keep emitting them.
checkContains('script defines the GONE state',      $wrHtml, "renderGone");
checkContains('script adopts the server playerID',  $wrHtml, "if (r.playerID) myPlayerID = r.playerID;");
checkContains('roster seats carry the wr-seat hook',$wrHtml, '<div class="wr-seat');
checkContains('identity thumbs carry the wr-card hook', $wrHtml, "'<img class=\"wr-card'");
// The fixed box is what keeps the identity strip on one baseline; height:auto would go ragged the
// first time a portrait card reached it.
// Tiles scale with the panel, and aspect-ratio derives the height from the real 628x450 landscape
// card so leaders and bases share one baseline without a second number that can drift.
checkContains('thumbnails scale with the panel', $wrHtml, 'width: clamp(96px, 9vw, 172px)');
checkContains('thumbnails keep the card aspect', $wrHtml, 'aspect-ratio: 628 / 450');
// No match-found countdown here: that popup celebrates an unexpected queue pairing.
checkContains('started state shows a plain Starting…', $wrHtml, "Starting…");
check('page does NOT ship a match-found countdown', strpos($wrHtml, 'match-found-popup') === false);
// The invite link must point at the page, not back at the menu.
checkContains('invite link targets the waiting room', $wrHtml, 'WaitingRoom.php?invite=');

// ⚠ TEAM PICKER REGRESSION GUARD. The first cut of this page ported the team COLUMNS but not the
// team PICKING, so a Team Suns host saw four "open" seats, no Join buttons, and no sign of
// themselves — a seat is NULL until its player picks a team, so an unassigned player belongs to no
// column and must be listed separately.
checkContains('team columns offer a Join button',   $wrHtml, 'wr-join-team');
checkContains('the Join button carries its team',   $wrHtml, 'data-team=');
checkContains('unassigned players are listed',      $wrHtml, 'Not on a team yet:');
checkContains('picking a team calls SetTeam',       $wrHtml, 'APIs/Lobbies/SetTeam.php');
checkContains('team columns show an occupancy count', $wrHtml, "occupied + '/'");
// You must not be offered a team you are already on, nor one that is full.
checkContains('join is suppressed when full or already yours', $wrHtml, 'var mine = (myTeam === team);');

// Twin Suns (and any team-less private lobby) uses the SAME 2-column grid as the team table, so a
// 4-seat room reads as 2x2 rather than a tall stack. auto-fit collapses it to one column when narrow.
// ⚠ EXPLICIT 2-track grid, never auto-fit: auto-fit divides the panel width by the track floor, so a
// wide panel silently turns a 4-seat room into 1x4. Measured at 3 columns before this was pinned.
checkContains('roster is an explicit 2-column grid', $wrHtml, 'grid-template-columns: repeat(2, minmax(0, 1fr))');
check('roster does NOT use auto-fit', strpos($wrHtml, 'grid-template-columns: repeat(auto-fit') === false
                                          && strpos($wrHtml, 'grid-template-columns:repeat(auto-fit') === false);
checkContains('grid collapses to one column when narrow', $wrHtml, '@media (max-width: 760px)');
checkContains('the panel is wide', $wrHtml, 'max-width: min(1600px, 96vw)');

// ⚠ IDLE FLICKER GUARD. The poll fires every 1.5s and rendering rebuilds #wr-roster with innerHTML,
// which destroys and recreates every <img> — so an idle lobby visibly flashed its card art (the alt
// text showing through) once a second. Re-render only when something the roster shows has changed.
checkContains('roster re-renders only on change', $wrHtml, 'if (sig !== lastSig) { lastSig = sig; render(r); }');
checkContains('the signature covers what the roster draws', $wrHtml,
              'JSON.stringify([r.roster, r.seatModel, r.blockers, r.numPlayers, r.inviteCode, myPlayerID, !!r.removed])');

// Identity rings carry the card's aspect colours. ONE colour = a smooth ring (a single-aspect leader,
// an aspect-less base, or DJ's Cunning,Cunning which the adapter dedupes); several = equal hard-stop
// segments, so a dual-aspect leader reads as two halves.
checkContains('the ring is a wrapper, not the img border', $wrHtml, 'class="wr-cardwrap"');
checkContains('one colour paints a smooth ring',  $wrHtml, "if (cs.length === 1) return cs[0];");
checkContains('several colours split the ring',   $wrHtml, "'linear-gradient(90deg,' + stops.join(',') + ')'");
checkContains('a colourless card falls back to neutral', $wrHtml, "['#4b5b6d']");

// Ready state. Loading a legal deck AUTO-READIES (bringing a deck is how you say "good to go"), so
// Unready is the deliberate "hold on, still swapping" signal. deckOk and ready are DIFFERENT facts
// and get separate pills — a legal deck you are still tinkering with is not one you are ready to play.
checkContains('a ready seat shows a READY pill',      $wrHtml, 'wr-pill-ready">READY');
checkContains('an un-ready seat shows NOT READY',     $wrHtml, 'wr-pill-notready">NOT READY');
checkContains('a deckless seat shows NO DECK',        $wrHtml, 'wr-pill-baddeck">NO DECK');
checkContains('the button toggles its own label',     $wrHtml, "(amReady ? 'Unready' : 'Ready')");
checkContains('ready cannot be set without a deck',   $wrHtml, "(me && me.deckOk ? '' : ' disabled')");
checkContains('ready posts an explicit value',        $wrHtml, 'APIs/Lobbies/SetReady.php');
// The deck panel stays available while seated — changing decks in the lobby is the whole feature.
checkContains('the deck panel is not hidden when seated', $wrHtml, "el('wr-deck').style.display = '';");
checkContains('the deck button renames when seated',  $wrHtml, "seated ? 'Change deck' : 'Join with this deck'");

// Action placement: Leave in the header (far from Start, so the destructive action is never adjacent
// to the one everyone is waiting on); Ready bottom-left because it is YOURS; Start bottom-right
// because it is the host's.
checkContains('the header has an action slot',  $wrHtml, 'id="wr-head-actions"');
checkContains('the footer splits left/right',   $wrHtml, 'id="wr-actions-left"');
checkContains('the footer has a right slot',    $wrHtml, 'id="wr-actions-right"');
checkContains('Leave renders into the header',  $wrHtml, "head.innerHTML  = '<button id=\"wr-leave\"");
// Ready sits NEXT TO the deck button, not on its own row: loading a deck auto-readies you, so the two
// are one thought. #wr-actions-left stays reserved for the GONE state's way out.
checkContains('Ready renders beside the deck button', $wrHtml, "el('wr-ready-slot').innerHTML =");
checkContains('the deck bar has a Ready slot',        $wrHtml, 'id="wr-ready-slot"');
checkContains('Ready shares the row height chain',    $wrHtml, '.wr-deckbar .btn { height: 34px;');
// Not-seated and GONE both clear the slot — there is nothing to ready without a seat.
checkContains('the Ready slot is cleared when seatless', $wrHtml, "el('wr-ready-slot').innerHTML = '';");
checkContains('Start renders bottom-right',     $wrHtml, '<button id="wr-start" type="button" class="btn wr-btn-start');
// Start is the terminal action: biggest control on the page, and it wears the THEME's success skin
// (.btn-success -> --success / --success-surface) rather than a hardcoded green.
checkContains('Start uses the shared button component', $wrHtml, 'class="btn wr-btn-start');
checkContains('Start is skinned from the theme success tokens', $wrHtml, "(canStart ? ' btn-success' : '')");
// Only the host can start, so only the host sees the button — a permanently disabled control is noise
// for everyone else. The host keeps seeing it while blocked, because disabled-with-a-reason IS the
// feedback that explains why.
checkContains('Start is rendered only for the host', $wrHtml, "right.innerHTML = isHost");
checkContains('the Start handler tolerates its absence', $wrHtml, "if (el('wr-start')) el('wr-start').onclick = doStart;");
checkContains('Start is sized via the button system knob', $wrHtml, '.wr-btn-start { --btn-pad:');
// A disabled Start must be neutral grey, not a washed-out green — .btn:disabled only dims.
check('the success skin is dropped when disabled',
      strpos($wrHtml, "(canStart ? ' btn-success' : '')") !== false
      && strpos($wrHtml, 'background-color:#2d8a57') === false);
// The action block: left column stacks (deck link over Ready), Start spans both rows to its right.
checkContains('the action block is a two-column flex row', $wrHtml, '.wr-actions { display: flex;');
checkContains('the left column stacks its rows',           $wrHtml, '.wr-actions-main { flex: 1 1 auto;');
// ⚠ Start is CENTRED across the two rows, not stretched to fill them: filling made it a ~106px slab
// louder than the rest of the panel. Prominence comes from padding and type size, which scale with
// the button rather than with however tall the left column happens to be.
checkContains('Start is centred, not stretched', $wrHtml, '#wr-actions-right { display: flex; flex-direction: column; justify-content: center;');
check('Start does not stretch to the column height', strpos($wrHtml, '#wr-actions-right > .btn { flex: 1 1 auto; }') === false);
check('Start does NOT rely on a percentage height',  strpos($wrHtml, 'height: 100%') === false);
checkContains('the status line is its own box',            $wrHtml, '.wr-status { display: flex;');

// Joining is the DECK BAR's button — the deck you join with is chosen right there, so a second
// standalone "Join" beside it was two controls for one action.
check('there is no standalone Join button', strpos($wrHtml, 'id="wr-join"') === false);
checkContains('the deck bar button joins when not seated', $wrHtml, "seated ? 'Change deck' : 'Join with this deck'");
checkContains('a full lobby disables the join button', $wrHtml, 'if (jb) jb.disabled = full;');

// ⚠ STRUCTURAL GUARD: every el('x') must name an element that actually exists — either static in the
// markup, or emitted by the script itself. Removing the standalone Join button left doJoin() still
// calling el('wr-join').disabled, which threw "can't access property disabled, el(...) is null" the
// moment anyone tried to join. A grep for the ID would have caught it; this makes the check automatic.
preg_match_all("/\bel\('([a-z0-9-]+)'\)/", $wrHtml, $wrIds);
$wrOrphans = [];
foreach (array_unique($wrIds[1]) as $wrId) {
    // Static markup, or an id the script writes into innerHTML.
    if (strpos($wrHtml, 'id="' . $wrId . '"') !== false) continue;
    $wrOrphans[] = $wrId;
}
check('no el() call references a non-existent element (' . implode(', ', $wrOrphans) . ')', $wrOrphans === []);

// The deck bar is a secondary control and is capped at roughly half the panel so it does not compete
// with Start. ⚠ The percentage resolves against the LEFT COLUMN, not the panel — a literal 50% here
// measures ~44% of the panel, which is why the value is 57%.
checkContains('the deck bar is capped', $wrHtml, '#wr-deck { max-width: 57%; }');
checkContains('the cap is lifted when stacked', $wrHtml, '#wr-deck { max-width: none; }');

// No NATIVE browser dialogs. StyledDialog.js loads on every SiteDef site (Head.php), so an unthemed
// OS box mid-flow is never necessary. The clipboard fallback is a PROMPT rather than an alert because
// its input is selectable — someone whose clipboard was blocked can still copy the link by hand.
// The comment lines that mention window.prompt are stripped before the scan so the guard is real.
$wrCode = preg_replace('~^\s*//.*$~m', '', $wrHtml);
check('no native window.prompt/alert/confirm',
      !preg_match('/\bwindow\.(prompt|alert|confirm)\s*\(/', $wrCode)
      && !preg_match('/(^|[^.\w])(prompt|alert|confirm)\s*\(/m', $wrCode));
checkContains('the clipboard fallback uses StyledPrompt', $wrHtml, "typeof StyledPrompt === 'function'");
checkContains('a successful copy toasts',                 $wrHtml, "Toast('Invite link copied.'");
// Both are called through typeof guards, so a page that somehow lacks the primitive degrades to
// silence rather than throwing mid-click.
checkContains('the toast is guarded',   $wrHtml, "typeof Toast === 'function'");

// The status box reports the seat count alongside what is being waited on.
checkContains('the status box shows a seat count', $wrHtml, 'id="wr-count-n"');
checkContains('the count uses the people icon',    $wrHtml, '/TCGEngine/Assets/Icons/people-icon.png');
checkContains('the icon has alt text',             $wrHtml, 'alt="Players"');
// ⚠ people-icon.png is a solid BLACK glyph; without inverting it is invisible on the dark panel.
checkContains('the icon is inverted for a dark panel', $wrHtml, '.wr-count-icon { filter: invert(100%);');
checkContains('the count is occupied/available',   $wrHtml, "(d.numPlayers || 0) + '/'");
// GONE and STARTED have no lobby to count, so the box is hidden rather than showing a stale number.
checkContains('the status box hides when there is no lobby', $wrHtml, "el('wr-status').style.display = 'none';");

// Every button opts into the .btn COMPONENT rather than leaning on the bare-element alias in
// components.css (which that file itself calls "the one intentional structural repeat"). Same tokens
// either way, but the component is the supported path and wires the chamfer pseudos identically.
preg_match_all('/<button(?![^>]*class="[^"]*\bbtn\b)[^>]*>/', $wrHtml, $wrBare);
check('every rendered button uses the .btn component (' . implode(' ', $wrBare[0]) . ')', $wrBare[0] === []);

// The deck bar's input and button must line up exactly.
// ⚠ The real culprit was a theme rule giving text inputs 10px top / 20px BOTTOM margin: the flex line
// grew to the input's margin box and the centred button sat 5px low. It looked like a height problem
// and was not — margin:0 is the fix, and an explicit height chain (not align-items:stretch, which
// blew the button up to 59px) is what keeps them equal in all three engines.
checkContains('the deck box margin is zeroed', $wrHtml, '.wr-deckbar textarea { flex: 1 1 260px; min-width: 0; margin: 0;');
checkContains('deck box and button share an explicit height', $wrHtml, '.wr-deckbar .btn { height: 34px;');
// ⚠ A TEXTAREA, not <input type=text>. An input silently STRIPS newlines, so a pasted decklist
// collapsed to one line and the server rejected it with "Paste a deck list, JSON, or a URL" — an
// error inviting exactly what the control could not hold. Caught only by the live two-seat run.
checkContains('the deck box is a textarea so decklists survive', $wrHtml, '<textarea id="wr-deck-input"');
check('the deck box is NOT a single-line input', strpos($wrHtml, '<input id="wr-deck-input"') === false);
check('the deck bar does not rely on flex-stretch', strpos($wrHtml, '.wr-deckbar { display: flex; align-items: stretch;') === false);

// Seat labels are Title Case and readable at a glance; your OWN seat carries a green ring.
// ⚠ The ring is an INSET box-shadow, not a border or background: the team columns set both of those
// inline (red/blue accents), so anything else would be overridden in Team Suns or fight the tint.
checkContains('seat labels are Title Case',  $wrHtml, '<div class="wr-seat-label">Seat ');
// i + 1 because the flat roster is POSITIONAL now: seat ids are unique but not dense, so the loop
// counts tiles rather than indexing the roster by playerID.
checkContains('empty seats read Title Case', $wrHtml, "emptySeat(i + 1, 'Waiting…')");
checkContains('team open seats read Title Case', $wrHtml, '>Open</div>');
checkContains('seat labels are larger',      $wrHtml, '.wr-seat-label { font-size: 14px;');
checkContains('your own seat is ringed',     $wrHtml, '.wr-seat-mine { box-shadow: inset 0 0 0 2px var(--success');
// Tagged in BOTH renderers — the flat grid and the team table build their tiles separately, so a fix
// in one is invisible in the other.
check('own-seat tagging exists in both roster renderers',
      substr_count($wrHtml, "playerID === myPlayerID ? ' wr-seat-mine'") === 2);

// "(you)" is gone from the seat tiles — the green ring says it, and two signals for one fact is noise.
// It SURVIVES in the unassigned holding line, which has no tile and therefore no ring.
check('seat tiles do not label you in text',
      strpos($wrHtml, "(entry.playerID === myPlayerID ? ' (you)' : '')") === false);
checkContains('the unassigned line still identifies you', $wrHtml, "(r.playerID === myPlayerID ? ' (you)' : '')");

// ── Presence, removal and the host's Remove control ──────────────────────────────────────────────
// Presence is a DISPLAY state now: the page must be able to DRAW an away seat, and the host must be
// able to remove one, because nothing removes anybody automatically any more.
checkContains('roster can draw an AWAY pill',        $wrHtml, 'wr-pill-away');
checkContains('an away tile is dimmed',              $wrHtml, 'wr-seat-away');
checkContains('the away class is driven by row.away',$wrHtml, "(e.away ? ' wr-seat-away' : '')");
checkContains('host-only Remove control exists',     $wrHtml, 'data-kick=');
checkContains('Remove posts to KickSeat',            $wrHtml, 'APIs/Lobbies/KickSeat.php');
// You may not remove yourself (that is Leave), and a non-host must not be offered the control at all.
checkContains('Remove is host-only and never self',  $wrHtml, "iAmHost && entry.playerID !== myPlayerID");

// Removal is ANNOUNCED. Silently swapping the button back to "Join with this deck" is exactly how
// the original reports read: people could see they had dropped out, but not why.
checkContains('page has a removal banner',           $wrHtml, 'id="wr-removed"');
checkContains('the banner is driven by r.removed',   $wrHtml, "r.removed ? '' : 'none'");

// ⚠ THE REGRESSION GUARD THAT MATTERS. The seat key used to expire on a fixed 15-minute clock from
// the moment of joining; after that the page polled with no authKey, could not be stamped, and the
// player was deleted while watching. `ts` must slide on every recognised poll.
checkContains('the seat key is restamped on every recognised poll', $wrHtml, 'if (live) saveKey(lobbyID, live);');
// ⚠ Assert the CODE, not the literal: the comment above that constant quotes the old 900000 on
// purpose, so needling the number would match the prose explaining the bug rather than the fix.
checkContains('the key backstop is a day, not 15 minutes', $wrHtml, 'LOBBY_KEY_BACKSTOP_MS = 86400000');
check('the old 15-minute key lifetime is gone', strpos($wrHtml, 'LOBBY_TTL_MS') === false);

// A hidden tab is throttled to ~1/minute, so returning to it must re-register immediately rather
// than wait out the throttled timer.
checkContains('the page re-polls when the tab becomes visible', $wrHtml, "addEventListener('visibilitychange'");

// Every rejoin used to start a SECOND poll loop in the same tab, compounding the request rate.
// ⚠ Match the COMMENT, not the clearTimeout line: doLeave has contained that same line since day one,
// so needling it would pass whether or not doJoin was fixed. A check that cannot fail is not a check.
checkContains('joining cancels the pending poll first', $wrHtml, 'or every rejoin doubles the poll rate');

// The flat roster is POSITIONAL. Seat ids are unique but not dense (a room that lost seat 2 and
// gained a joiner holds 1, 3, 4), so indexing by playerID drops anyone whose id exceeds maxPlayers.
check('the flat roster no longer indexes seats by playerID', strpos($wrHtml, 'var e = byId[i];') === false);
checkContains('the flat roster sorts entries positionally', $wrHtml, 'roster.slice().sort(');

// ⚠ REJOIN GUARD. inviteCode is read from ?invite= at boot, but rewriteUrl() swaps the URL to
// ?lobby=<id> as soon as you join — so without adopting it from the poll payload, a player who lost
// their seat pressed "Join with this deck" and was sent to the PUBLIC queue instead of back here.
checkContains('the page adopts the invite code from the poll', $wrHtml, 'if (r.inviteCode) inviteCode = r.inviteCode;');

// --- Menu redesign Task 1: the document must leave quirks mode ---
// Before this, RenderHead() emitted "<head>" as the first bytes of the response, so every page
// in the monolith rendered in BackCompat (verified in all three engines, 2026-09-24) with no
// language set — mispronouncing "Petranaki", "Arenabot" and "Bo1" for every screen-reader user.
check('head starts with a doctype', str_starts_with($head, '<!DOCTYPE html>'));
checkContains('head opens html with lang', $head, '<html lang="en">');

// --- Menu redesign Task 2: path-versioned CSS ---
// ?v=<mtime> is a QUERY STRING, and a CDN configured to ignore query strings drops it — which is
// why JS already ships as UILibraries<YYYYMMDD>.js. CSS had no equivalent lever, so restructuring
// stylesheets risked serving a half-old sheet to returning players.
$pv = _VersionAsset('/TCGEngine/SharedUI/css/tokens.css', true);
check('path-versioned CSS carries the mtime in the filename', (bool)preg_match('#/tokens\.\d+\.css$#', $pv));
check('path-versioned CSS has no query string', strpos($pv, '?v=') === false);
check('default call still uses the query string', strpos(_VersionAsset('/TCGEngine/SharedUI/css/tokens.css'), '?v=') !== false);

// --- Menu redesign Task 5: the layered token tier ---
$sysPath = __DIR__ . '/../../css/system.css';
check('system.css exists', file_exists($sysPath));
$sysCss  = file_exists($sysPath) ? file_get_contents($sysPath) : '';
checkContains('system.css declares the layer order first', $sysCss, '@layer tokens, base, components, utilities;');
checkContains('system.css exposes a surface token', $sysCss, '--sys-surface-1:');
checkContains('system.css exposes the six aspect colours', $sysCss, '--aspect-villainy:');
// ⚠ A color-mix() whose operands are BOTH color-mix() hard-crashes the WebKit renderer — not a
// mis-paint, a blank page. One mix operand is fine. This guards the whole token ramp.
// Strip /* … */ comments first: the file DOCUMENTS the rule in prose, and counting the prose
// made this assertion fail on a file that was actually correct.
$sysCode = preg_replace('#/\*.*?\*/#s', '', $sysCss);
$nested = 0;
foreach (explode(';', $sysCode) as $decl) {
    if (substr_count($decl, 'color-mix(') > 1) { $nested++; }
}
check('system.css has no nested color-mix (crashes WebKit)', $nested === 0);

// --- Menu redesign Task 6: system.css leads every theme stack ---
$sysHead = RenderHead($def);
$sysPos  = strpos($sysHead, '/SharedUI/css/system.css');
$tokPos  = strpos($sysHead, '/SharedUI/css/tokens.css');
check('system.css is in the stack', $sysPos !== false);
check('system.css loads before tokens.css', $sysPos !== false && $tokPos !== false && $sysPos < $tokPos);
// clarent sites load no menuStyles; system.css must still lead THEIR stack too
$claHead = RenderHead(LoadSiteDef('FaBSim'));
$claSys  = strpos($claHead, '/SharedUI/css/system.css');
$claTok  = strpos($claHead, '/SharedUI/css/tokens.css');
check('system.css leads a clarent stack too', $claSys !== false && $claTok !== false && $claSys < $claTok);

// --- Menu redesign Task 9: semantic landmarks in the shared chrome ---
// The gate found NO-LANDMARK header,nav on every SWUSim state. These renderers emit plain divs,
// so no page in the monolith has a banner or navigation landmark — a screen-reader user cannot
// jump past the chrome. Fixing it here fixes it for all six sites.
$lmHdr = RenderHeader($def);
$lmNav = RenderMenuBar($def, ['isLoggedIn'=>false,'isPatron'=>false,'username'=>null,'userId'=>null]);
check('header renders a <header> landmark', strpos($lmHdr, '<header') !== false);
check('menu bar renders a <nav> landmark', strpos($lmNav, '<nav') !== false);
check('the nav landmark is labelled', strpos($lmNav, 'aria-label') !== false);

// --- The 'arena' legal layout (SWUSim only, owner 2026-09-26) -------------------
// Terms of Use and Privacy Policy render templates/*.tmpl, which are BARE PROSE: <h1>, <h2>, <p>,
// <ul> and nothing around them. On SWUSim that landed as direct children of <body>, so every line
// ran the full 1440px of the viewport with no gutter and no panel -- measured, not assumed.
//
// The templates are shared with FaBSim, HellbreakSim, SWUDeck and HellbreakDeck, so the wrapper is
// an OPT-IN, the same shape as auth.layout and profile.layout above.
$legalDef = $def; $legalDef['legal'] = ['layout' => 'arena'];
$plainTerms  = RenderLegalPage($def,       'TermsOfUse');
$arenaTerms  = RenderLegalPage($legalDef,  'TermsOfUse');
$arenaPriv   = RenderLegalPage($legalDef,  'PrivacyPolicy');

// ⚠ The default is asserted POSITIVELY, against the bare prose itself -- not by comparing against
// another call that would flip with it. That tautology is why the auth check above carries the
// same warning.
check('a site that does not opt in still gets bare prose',
    strpos($plainTerms, '<h1>') !== false
    && strpos($plainTerms, 'legal-page') === false
    && strpos($plainTerms, 'row-wrapper') === false);
checkContains('the arena legal page is wrapped', $arenaTerms, 'row-wrapper');
checkContains('the arena legal page sits in a panel', $arenaTerms, 'ga-glass-card');
checkContains('the arena legal page is marked for styling', $arenaTerms, 'legal-page');
checkContains('the arena legal page keeps its content', $arenaTerms, 'Terms of Use');
checkContains('privacy opts in the same way', $arenaPriv, 'legal-page');
check('the wrapper adds no text of its own',
    strip_tags($arenaTerms) === strip_tags($plainTerms));

// --- Legal documents have a sane heading outline (owner 2026-09-26) -------------
// PrivacyPolicy.tmpl used SIX <h1>s as section headings and had no page title at all, so a screen
// reader (and any outline view) read it as six separate documents. TermsOfUse.tmpl was already
// right, which is what makes the pair a useful check: the rule must pass one and fail the other.
foreach (['TermsOfUse' => 'Terms of Use', 'PrivacyPolicy' => 'Privacy Policy'] as $tpl => $title) {
    $doc = RenderTemplate($tpl, $def);
    preg_match_all('/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', $doc, $hs, PREG_SET_ORDER);
    $levels = array_map(fn($m) => (int)$m[1], $hs);

    check("$tpl has exactly one h1", count(array_filter($levels, fn($l) => $l === 1)) === 1,
        'h1 count = ' . count(array_filter($levels, fn($l) => $l === 1)));
    check("$tpl opens with its h1", ($levels[0] ?? 0) === 1);
    check("$tpl names itself in that h1",
        isset($hs[0][2]) && stripos(strip_tags($hs[0][2]), $title) !== false,
        $hs[0][2] ?? '(none)');

    // no skipped levels: h1 -> h3 with no h2 between is an outline hole
    $skips = [];
    foreach ($levels as $i => $l) {
        if ($i === 0) continue;
        if ($l > $levels[$i - 1] + 1) $skips[] = "h{$levels[$i-1]} -> h$l";
    }
    check("$tpl skips no heading levels", $skips === [], implode(', ', $skips));
}

// (later tasks append their checks above this line)

echo "PASS=$PASS FAIL=$FAIL\n";
foreach ($MSGS as $m) echo $m . "\n";
echo $FAIL === 0 ? "ALL GREEN\n" : "RED\n";
