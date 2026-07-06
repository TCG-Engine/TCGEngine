<?php
// Shared site-definition loader, validator, and auth-context builder.

function LoadSiteDef(string $site): array {
    $path = __DIR__ . '/../Sites/' . $site . '/SiteDef.php';
    if (!is_file($path)) {
        throw new RuntimeException("Missing SiteDef.php for site '$site' (expected: $path)");
    }
    return require $path;
}

// Returns a list of human-readable error strings. Empty list == valid.
function ValidateSiteDef(array $def): array {
    $errors = [];
    if (empty($def['branding']['title']))    $errors[] = "branding.title is required";
    if (empty($def['branding']['homeHref']))  $errors[] = "branding.homeHref is required";
    if (!isset($def['nav']) || !is_array($def['nav'])) {
        $errors[] = "nav must be a list";
    } else {
        foreach ($def['nav'] as $i => $item) {
            $isIcon = (($item['kind'] ?? '') === 'icon');
            $isDropdown = (($item['kind'] ?? '') === 'dropdown');
            if ($isIcon) {
                if (empty($item['icon']) || empty($item['href'])) $errors[] = "nav[$i] icon needs icon+href";
            } elseif ($isDropdown) {
                if (empty($item['label']) || empty($item['children'])) $errors[] = "nav[$i] dropdown needs label+children";
            } else {
                if (empty($item['label']) || empty($item['href'])) $errors[] = "nav[$i] link needs label+href";
            }
        }
    }
    $known = ['changePassword','welcome','team','developerOptions','savedDecks','cosmetics','blockedUsers'];
    foreach (($def['profile']['sections'] ?? []) as $s) {
        // An entry may combine up to 2 panels with '+' (e.g. 'welcome+changePassword') → one pane.
        $panels = array_values(array_filter(array_map('trim', explode('+', (string)$s))));
        if (count($panels) > 2) $errors[] = "profile.sections entry '$s' combines more than 2 panels";
        foreach ($panels as $p) {
            if (!in_array($p, $known, true)) $errors[] = "profile.sections has unknown section '$p'";
        }
    }
    if (isset($def['deckLibrary'])) {
        if (!is_array($def['deckLibrary'])) {
            $errors[] = "deckLibrary must be an object";
        } else {
            $storage = $def['deckLibrary']['storage'] ?? 'account';
            if (!in_array($storage, ['account', 'local'], true)) {
                $errors[] = "deckLibrary.storage must be account or local";
            }
            if (isset($def['deckLibrary']['endpoint']) && !is_string($def['deckLibrary']['endpoint'])) {
                $errors[] = "deckLibrary.endpoint must be a string";
            }
            if (isset($def['deckLibrary']['localStorageKey']) && !is_string($def['deckLibrary']['localStorageKey'])) {
                $errors[] = "deckLibrary.localStorageKey must be a string";
            }
        }
    }
    foreach (['rootName','appName','ipOwner','tcgName'] as $k) {
        if (empty($def['identity'][$k])) $errors[] = "identity.$k is required";
    }
    if (isset($def['theme']) && (!is_string($def['theme']) || $def['theme'] === '')) {
        $errors[] = "theme must be a non-empty string";
    }
    return $errors;
}

function BuildAuthContext(): array {
    return [
        'isLoggedIn' => isset($_SESSION['useruid']),
        'isPatron'   => isset($_SESSION['isPatron']),
        'username'   => $_SESSION['useruid'] ?? null,
        'userId'     => isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : null,
    ];
}
