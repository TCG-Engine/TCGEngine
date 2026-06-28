<?php
// Generator: emits each migrated site's standard entry files + the root SharedUI/ pointers
// from every Sites/<site>/SiteDef.php. Emits only trivial wiring (one-liners) — no page logic.
// Generated files are .gitignore'd. Run:
//   curl http://localhost:3100/TCGEngine/SharedUI/Render/GenerateSites.php
//   (or: php SharedUI/Render/GenerateSites.php)
header('Content-Type: text/plain');

$sharedUI = dirname(__DIR__);          // .../SharedUI
$sitesDir = $sharedUI . '/Sites';

$STANDARD_PAGES = ['Disclaimer','Header','LoginPage','MenuBar','MobileViewport','Patreons','PrivacyPolicy','Profile','Signup','TermsOfUse'];
$ROOT_POINTERS  = ['MainMenu','Profile','LoginPage','Signup'];

$written = [];

// Per-site entry files (only sites that have a SiteDef.php are migrated)
foreach (glob($sitesDir . '/*/SiteDef.php') as $defPath) {
    $site = basename(dirname($defPath));
    foreach ($STANDARD_PAGES as $page) {
        $entry = "<?php require_once __DIR__ . '/../../Render/PageEntry.php'; RenderSitePage('$site', '$page');\n";
        $path = "$sitesDir/$site/$page.php";
        file_put_contents($path, $entry);
        $written[] = $path;
    }
}

// Root SharedUI/ pointers -> render the active site (ActiveSite.php) in-place
foreach ($ROOT_POINTERS as $page) {
    $pointer = "<?php // Generated root pointer: renders the active site (ActiveSite.php) in-place.\n"
             . "include __DIR__ . '/Sites/' . (require __DIR__ . '/ActiveSite.php') . '/$page.php';\n";
    $path = "$sharedUI/$page.php";
    file_put_contents($path, $pointer);
    $written[] = $path;
}

echo "Generated " . count($written) . " files:\n";
foreach ($written as $w) echo "  " . str_replace($sharedUI . '/', '', $w) . "\n";
echo "DONE\n";
