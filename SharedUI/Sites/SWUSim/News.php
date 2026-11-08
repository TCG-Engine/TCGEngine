<?php
// News items for the main menu's Welcome panel (MainMenu.php, the "News & tips" carousel). Newest FIRST: the carousel
// opens on the first entry. The keyboard-shortcuts tip is not listed here; it is always the slide after these, because
// its list is rendered by renderHotkeyList() from _hotkeyList.
//   date  — YYYY-MM-DD, shown as "Nov 8, 2026"
//   title — one line
//   body  — plain text, escaped on output
//   image — optional; a horizontal image shown between the title and the body, cropped to 2:1 (1280x640 WebP in
//           assets/news/ fits exactly). Leave it out for a text-only slide.
return [
  [
    'date'  => '2026-11-08',
    'title' => 'Every IC27 preview is playable',
    'body'  => 'All 194 IC27 cards are implemented and now run on official card data.',
    'image' => '/TCGEngine/SharedUI/Sites/SWUSim/assets/news/news-item-ic27.webp',
  ],
];
