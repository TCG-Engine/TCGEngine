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
checkContains('menubar has Support', $navOut, "https://www.patreon.com/c/OotTheMonk");
checkContains('menubar has Stats dropdown', $navOut, "class='dropdown'");
checkContains('menubar has Deck Stats child', $navOut, '/TCGEngine/Stats/DeckMetaStats.php');
checkContains('menubar has github icon', $navOut, 'icons/github.svg');
checkContains('menubar has discord icon', $navOut, 'discord.gg/5ZHXyVvVFC');
checkContains('menubar renders burger on first paint', $navOut, 'class="burger-menu"');
checkContains('menubar burger has accessible label', $navOut, 'aria-label="Open navigation"');
checkContains('loggedout has Log In', $navOut, '/TCGEngine/SharedUI/Sites/SWUDeck/LoginPage.php');
check('loggedout hides Profile', strpos($navOut, 'SWUDeck/Profile.php') === false);
checkContains('loggedin has Profile', $navIn, '/TCGEngine/SharedUI/Sites/SWUDeck/Profile.php');
check('loggedin hides Log In', strpos($navIn, 'SWUDeck/LoginPage.php') === false);

// --- Task 4 tests: RenderHeader ---
require_once __DIR__ . '/../Header.php';
$hdr = RenderHeader($def);
checkContains('header title link', $hdr, 'href="/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php"');
checkContains('header h1', $hdr, '<h1>SWU Stats</h1>');
checkContains('header tagline', $hdr, '<p>Star Wars Unlimited Stats</p>');
checkContains('header banner block', $hdr, 'class="banner block-1"');
check('header omits dead pull-to-refresh indicator', strpos($hdr, 'pull-indicator') === false);

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
$noPw = $def; $noPw['profile']['sections'] = ['team'];
$prof2 = RenderProfile($noPw, $ctxIn, $ud);
check('omitting password hides form', strpos($prof2, 'id="selfResetPasswordForm"') === false);

// --- Task 6 tests: RenderLoginPage + RenderSignup ---
require_once __DIR__ . '/../Auth.php';
$login = RenderLoginPage($def); $signup = RenderSignup($def);
checkContains('login posts to AttemptPasswordLogin', $login, '/TCGEngine/AccountFiles/AttemptPasswordLogin.php');
checkContains('login has remember-me', $login, 'name="rememberMe"');
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
check('lib shows no card art', strpos($lib, '/TCGEngine/SWUSim/concat/') === false);
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
checkContains('cosmetics posts to endpoint', $cos, 'SWUSim/Cosmetics.php');
check('SWUSim profile enables cosmetics', in_array('cosmetics', $swusimDef['profile']['sections'] ?? [], true));
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

// --- All sites validate under the new panel keys + render their listed panels ---
$expectPanels = [
  'SWUDeck'         => ['welcome+changePassword','team','developerOptions'],
  'SWUSim'          => ['welcome+changePassword','savedDecks+blockedUsers','cosmetics'],
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

// (later tasks append their checks above this line)

echo "PASS=$PASS FAIL=$FAIL\n";
foreach ($MSGS as $m) echo $m . "\n";
echo $FAIL === 0 ? "ALL GREEN\n" : "RED\n";
