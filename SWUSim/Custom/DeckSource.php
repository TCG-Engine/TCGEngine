<?php
// The SWUStats / Saved deck-source toggle (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2).
// Pattern 4 of the Petranaki theme (.seg options toggle): a radio pair, so arrow keys and screen readers
// work natively in every engine. SWUStats is checked server-side; swusim-deck-source.js applies the
// player's remembered choice on load.
// ⚠ JS twin: SWUDeckSource.toggleHtml() in SharedUI/js/swusim-deck-source.js builds this same markup for
// the Waiting Room's fill-bot dialog. Change one, change both — swusim-waitingroom-deck-source-xbrowser.mjs compares them.
function SWUDeckSourceToggle(string $idBase, string $swustatsInner, string $savedInner, string $labelledBy = ''): string {
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $b = $e($idBase);
    $label = $labelledBy !== '' ? ' aria-labelledby="' . $e($labelledBy) . '"' : ' aria-label="Deck source"';
    return '<div class="decksrc" data-decksrc>'
         . '<div class="seg decksrc__seg" role="radiogroup"' . $label . '>'
         . '<input class="seg__in u-vh" type="radio" name="' . $b . '-src" id="' . $b . '-src-ss" value="swustats" data-decksrc-opt checked>'
         . '<label class="seg__opt ch" for="' . $b . '-src-ss">SWUStats Decks</label>'
         . '<input class="seg__in u-vh" type="radio" name="' . $b . '-src" id="' . $b . '-src-saved" value="saved" data-decksrc-opt>'
         . '<label class="seg__opt ch" for="' . $b . '-src-saved">Saved Decks</label>'
         . '</div>'
         . '<div class="decksrc__panel" data-src="swustats"><div class="decksrc__host">' . $swustatsInner . '</div>'
         . '<button type="button" class="decksrc__refresh" data-decksrc-refresh>Refresh SWUStats decks</button></div>'
         . '<div class="decksrc__panel" data-src="saved" hidden>' . $savedInner . '</div>'
         . '</div>';
}
