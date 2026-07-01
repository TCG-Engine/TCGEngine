<?php
// Generator: emits a migrated site's standard entry files + the root SharedUI/ pointers
// from Sites/<site>/SiteDef.php. Emits only trivial wiring (one-liners) — no page logic.
// Generated files are .gitignore'd.
//
// By default it generates ONLY the active site (resolved from ActiveSite.php via the
// connected DB), so a prod deployment that serves one site never generates files for the
// others. Run:
//   curl http://localhost:3400/TCGEngine/SharedUI/Render/GenerateSites.php   # active site only
//   (or: php SharedUI/Render/GenerateSites.php)
// Target a specific site:
//   curl '.../GenerateSites.php?rootName=SWUSim'   (or: php GenerateSites.php SWUSim)
// Generate every migrated site (e.g. a shared local checkout):
//   curl '.../GenerateSites.php?all=1'             (or: php GenerateSites.php all)
// The root SharedUI/ pointers are always emitted, in every mode.
header('Content-Type: text/plain');

$sharedUI = dirname(__DIR__);          // .../SharedUI
$sitesDir = $sharedUI . '/Sites';

// Which site(s) to generate. Precedence: explicit rootName > all > default (active site).
$arg = isset($_GET['rootName']) ? $_GET['rootName'] : (isset($argv[1]) ? $argv[1] : null);
$all = isset($_GET['all']) || $arg === 'all';

$only = null;                          // null = every migrated site
if (!$all) {
    // No explicit rootName -> fall back to the active site (from ActiveSite.php).
    $only = ($arg !== null && $arg !== '') ? trim($arg) : require $sharedUI . '/ActiveSite.php';
    if (!is_dir("$sitesDir/$only/") || !is_file("$sitesDir/$only/SiteDef.php")) {
        http_response_code(400);
        echo "No such migrated site: '$only' (expected a folder with SiteDef.php under Sites/)\n";
        exit;
    }
}

$STANDARD_PAGES = ['Disclaimer','Header','LoginPage','MenuBar','MobileViewport','Patreons','PrivacyPolicy','Profile','Signup','TermsOfUse'];
$ROOT_POINTERS  = ['MainMenu','Profile','LoginPage','Signup'];

$written = [];

// Per-site entry files (only sites that have a SiteDef.php are migrated)
foreach (glob($sitesDir . '/*/SiteDef.php') as $defPath) {
    $site = basename(dirname($defPath));
    if ($only !== null && $site !== $only) continue;
    foreach ($STANDARD_PAGES as $page) {
        $entry = "<?php require_once __DIR__ . '/../../Render/PageEntry.php'; RenderSitePage('$site', '$page');\n";
        $path = "$sitesDir/$site/$page.php";
        file_put_contents($path, $entry);
        $written[] = $path;
    }
}

// Root SharedUI/ pointers -> render the active site (ActiveSite.php) in-place.
// Always emitted (even in a single-site run): their content is invariant, and a repo
// that never had a full run — e.g. one newly gaining a SiteDef — needs them to exist.
foreach ($ROOT_POINTERS as $page) {
    $pointer = "<?php // Generated root pointer: renders the active site (ActiveSite.php) in-place.\n"
             . "include __DIR__ . '/Sites/' . (require __DIR__ . '/ActiveSite.php') . '/$page.php';\n";
    $path = "$sharedUI/$page.php";
    file_put_contents($path, $pointer);
    $written[] = $path;
}

echo "Mode: " . ($all ? "all migrated sites" : "site '$only'") . "\n";
echo "Generated " . count($written) . " files:\n";
foreach ($written as $w) echo "  " . str_replace($sharedUI . '/', '', $w) . "\n";
echo "DONE\n";
