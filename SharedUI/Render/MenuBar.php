<?php
require_once __DIR__ . '/Head.php';

function _NavVisible(array $item, array $ctx): bool {
    $v = $item['visibility'] ?? 'always';
    if ($v === 'loggedIn')  return $ctx['isLoggedIn'];
    if ($v === 'loggedOut') return !$ctx['isLoggedIn'];
    if ($v === 'patron')    return $ctx['isPatron'];
    return true;
}

function _RenderNavItem(array $item): string {
    $kind = $item['kind'] ?? 'link';
    if ($kind === 'icon') {
        // Faithful: current icons use relative ../Assets path.
        return "<li><a target=\"_blank\" href=\"{$item['href']}\"><img src=\"/TCGEngine/Assets/Images/icons/{$item['icon']}\"></img></a></li>";
    }
    if ($kind === 'dropdown') {
        $children = '';
        foreach ($item['children'] as $c) {
            $children .= "\n                  <a href='{$c['href']}'>{$c['label']}</a>";
        }
        return "<li class='dropdown'>
                <a href='#' class='NavBarItem'>{$item['label']} <span class='dropdown-arrow'>▼</span></a>
                <div class='dropdown-content'>$children
                </div>
              </li>";
    }
    $target = isset($item['target']) ? " target='{$item['target']}'" : '';
    return "<li><a href='{$item['href']}'$target class='NavBarItem'>{$item['label']}</a></li>";
}

// Items in the separate nav-bar-links group: 'icon' (external link w/ image) or 'raw' (verbatim HTML, e.g. a settings button).
function _RenderNavLink(array $item): string {
    $kind = $item['kind'] ?? 'icon';
    if ($kind === 'raw') return $item['html'] ?? '';
    $title = isset($item['title']) ? " title=\"{$item['title']}\"" : '';
    return "<li><a target=\"_blank\" href=\"{$item['href']}\"$title><img src=\"/TCGEngine/Assets/Images/icons/{$item['icon']}\"></img></a></li>";
}

function RenderMenuBar(array $def, array $ctx): string {
    // Leading newline mirrors the template close-tag plus blank line before the head element.
    $out  = "\n" . RenderHead($def);
    $out .= "\n<body>\n";
    if (!empty($def['branding']['menuOverlay'])) {   // burger-menu overlay (sites with burger-menu.js)
        $out .= "  <!-- Simple menu overlay div that will be controlled by JS -->\n";
        $out .= "  <div class=\"menu-overlay\" style=\"display: none;\"></div>\n\n";
    }
    $out .= "  <div class='nav-bar'>\n    <div class='nav-bar-user'>\n      <ul class='rightnav'>\n        ";
    foreach ($def['nav'] as $item) {
        if (!_NavVisible($item, $ctx)) continue;
        $out .= _RenderNavItem($item);
    }
    $out .= "      </ul>\n    </div>\n";
    if (!empty($def['navLinks'])) {                  // separate right-aligned icon/button group (nav-bar-links)
        $out .= "\n    <div class='nav-bar-links'>\n      <ul>\n        ";
        foreach ($def['navLinks'] as $item) {
            $out .= _RenderNavLink($item);
        }
        $out .= "\n      </ul>\n    </div>\n";
    }
    $out .= "\n  </div>\n";
    // Intentionally do NOT close </body></html> — including pages (MainMenu/Profile/Login/Signup)
    // render the rest of the document and close it themselves. Mirrors the top-level SharedUI/MenuBar.php.
    $out .= "<!-- Note: do not close </body> or </html> here. Pages that include MenuBar.php will render the rest of\n";
    $out .= "  the document and should be responsible for closing body/html. Removing these tags prevents\n";
    $out .= "  prematurely terminating the DOM which can break client-side scripts that rely on event handlers\n";
    $out .= "  and dynamically-inserted elements (e.g. the header/menu JS). -->";
    return $out;
}
