<?php
// Front-controller-as-a-function. Generated per-site entry files are one-liners that
// call RenderSitePage($site, $page); ALL page logic + the shared preamble live here.

require_once __DIR__ . '/SiteDef.php';
require_once __DIR__ . '/Head.php';
require_once __DIR__ . '/MenuBar.php';
require_once __DIR__ . '/Header.php';
require_once __DIR__ . '/Profile.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Template.php';
require_once __DIR__ . '/Misc.php';

// Session bootstrap shared by every chrome/session page (extracted from the old MenuBar shim).
function _SitePageSessionBootstrap(): void {
    include_once __DIR__ . '/../../Assets/patreon-php-master/src/OAuth.php';
    include_once __DIR__ . '/../../Assets/patreon-php-master/src/API.php';
    include_once __DIR__ . '/../../Assets/patreon-php-master/src/PatreonLibraries.php';
    include_once __DIR__ . '/../../Assets/patreon-php-master/src/PatreonDictionary.php';
    include_once __DIR__ . '/../../Core/HTTPLibraries.php';
    include_once __DIR__ . '/../../Database/ConnectionManager.php';
    include_once __DIR__ . '/../../Database/functions.inc.php';
    if (!isset($_SESSION["userid"])) {
        if (isset($_COOKIE["rememberMeToken"])) { loginFromCookie(); }
    }
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

// Same-origin /TCGEngine/ redirect validation used by Login/Signup (extracted from the shims).
function _ComputeSafeRedirect(): string {
    $redirect = $_GET["redirect"] ?? "";
    $safeRedirect = "";
    if ($redirect != "") {
        $parts = parse_url($redirect);
        $path = $parts !== false && isset($parts["path"]) ? $parts["path"] : "";
        if ($parts !== false && !isset($parts["scheme"]) && !isset($parts["host"]) && strpos($path, "/TCGEngine/") === 0) {
            $safeRedirect = $redirect;
        }
    }
    return $safeRedirect;
}

function RenderSitePage(string $site, string $page): void {
    $def = LoadSiteDef($site);   // throws clearly if the SiteDef is missing

    switch ($page) {
        // --- Fragments (include-only) ---
        case 'Header':
            echo RenderHeader($def);
            return;

        case 'MenuBar':
            _SitePageSessionBootstrap();
            echo RenderMenuBar($def, BuildAuthContext());
            return;

        case 'Disclaimer':
            echo RenderTemplate('Disclaimer', $def);
            return;

        case 'MobileViewport':
            echo RenderMobileViewport();
            return;

        case 'Patreons':
            _SitePageSessionBootstrap();   // loads patreon libs (PatreonLogin) + DB + session
            require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
            echo RenderPatreons();
            return;

        // --- Full visited pages (chrome + body + disclaimer) ---
        case 'LoginPage':
            _SitePageSessionBootstrap();
            require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
            $safeRedirect = _ComputeSafeRedirect();
            if (IsUserLoggedIn()) {
                header("Location: " . ($safeRedirect != "" ? $safeRedirect : "./MainMenu.php"));
                exit();
            }
            echo RenderMenuBar($def, BuildAuthContext());
            echo "\n";
            echo RenderHeader($def);
            echo "\n";
            echo RenderLoginPage($def, $safeRedirect);
            echo "\n";
            echo RenderTemplate('Disclaimer', $def);
            return;

        case 'Signup':
            _SitePageSessionBootstrap();
            $safeRedirect = _ComputeSafeRedirect();
            echo RenderMenuBar($def, BuildAuthContext());
            echo "\n";
            echo RenderHeader($def);
            echo "\n";
            echo RenderSignup($def, $safeRedirect);
            echo "\n";
            echo RenderTemplate('Disclaimer', $def);
            return;

        case 'Profile':
            _SitePageSessionBootstrap();
            require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
            require_once __DIR__ . '/../../AccountFiles/AccountDatabaseAPI.php';
            require_once __DIR__ . '/../../APIKeys/APIKeys.php';
            echo RenderMenuBar($def, BuildAuthContext());
            echo RenderHeader($def);
            $userData = LoadUserDataFromId(LoggedInUser());
            if (!IsUserLoggedIn()) { header('Location: ./MainMenu.php'); exit(); }
            echo RenderProfile($def, BuildAuthContext(), $userData);
            echo RenderTemplate('Disclaimer', $def);
            return;

        case 'PrivacyPolicy':
            _SitePageSessionBootstrap();
            echo RenderMenuBar($def, BuildAuthContext());
            echo "\n";
            echo RenderHeader($def);
            echo "\n";
            echo RenderTemplate('PrivacyPolicy', $def);
            echo "\n";
            echo RenderTemplate('Disclaimer', $def);
            return;

        case 'TermsOfUse':
            _SitePageSessionBootstrap();
            echo RenderMenuBar($def, BuildAuthContext());
            echo "\n";
            echo RenderHeader($def);
            echo "\n";
            echo RenderTemplate('TermsOfUse', $def);
            echo "\n";
            echo RenderTemplate('Disclaimer', $def);
            return;

        default:
            throw new RuntimeException("Unknown page '$page' for site '$site' (run GenerateSites.php after adding it)");
    }
}
