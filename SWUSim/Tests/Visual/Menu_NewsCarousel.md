# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — the Welcome tab's "News & tips" carousel (added 2026-11-08)
#
#   Slides: every item in SharedUI/Sites/SWUSim/News.php (newest first), then the keyboard-shortcuts tip. A news slide
#   is kicker (NEWS · date) → title → a horizontal 2:1 image (the item's 'image', from assets/news/; no image if the
#   item sets none) → body. No auto-rotation, on purpose: the old did-you-know rotator was removed for WCAG 2.2.2.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1400px and 420px):
    cd DevTools/ui-harness && node swusim-news-carousel-xbrowser.mjs [baseUrl] [screenshotDir]
Then look, by eye, at http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php in each browser you have.

## WHAT TO LOOK AT

1. **Opens on the newest news item.** Gold "NEWS · NOV 8, 2026" kicker, the title, then the image as a wide
   rectangle filling the panel's width (the IC27 art: Ahsoka, Boba Fett, the Death Star, two duelists — uncropped), then the body text. Counter top-right reads "1 / 2".
2. **Paging.** The right arrow, the second dot, and → (with focus on a carousel control) all show the shortcuts tip;
   the left arrow on slide 1 wraps to the last slide. The active dot is the longer gold bar.
3. **Nothing below moves.** Games in Progress stays put while paging — the carousel is always as tall as its
   tallest slide, so the shortcuts slide has empty space under its list. That gap is intended.
4. **Phone (420px), touch.** A horizontal swipe on the slide pages it; a vertical drag still scrolls the page.
5. **The dots are bare bars.** No grey button plate, no chamfer, and no 1.1x grow on hover.
6. **Reduced motion.** With the OS setting on, slides switch with no fade or slide.
