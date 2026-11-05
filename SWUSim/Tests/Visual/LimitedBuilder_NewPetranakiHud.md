# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the Limited DECKBUILDER page (SWUSim/LimitedBuilder.php), not a gamestate.
#
# VISUAL CHECK — the SWUSim Limited deckbuilder, in the "New Petranaki HUD" style
#
#   What changed: a new standalone page (like the sideboard) where a Limited player builds from their own pool
#   (docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.4; mockup
#   docs/superpowers/specs/2026-11-04-limited-mockups/deckbuilder.html). Two contexts, one page:
#     ?lobbyID=<id>                              the room's 30-minute build (seat key from localStorage)
#     ?matchId=<id>&playerID=<n>&authKey=<k>     between Bo3 games (SWUSim/Sideboard.php redirects a Limited match here)
#   Markup: SWUSim/LimitedBuilder.php; client: SWUSim/Limited/ui/limited-builder.js; data: SWUSim/LimitedBuilderAction.php.
#   Styles: SharedUI/Sites/SWUSim/css/swusim-menu-2.css "Limited deckbuilder", scoped `body:has(.lb-root)`, lb- prefixed.
#
#   The bugs it guards: a pool that is not also the sideboard (cards vanishing instead of greying at ×0); Ready offered on
#   a deck that changed since Submit; the seg labels running their count into the text ("CARDS (54LEFT)" — a flex label
#   collapses the space between its children, so each label's text sits in ONE inline span).

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1440px and 390px; the build context and the between-games context):
    cd DevTools/ui-harness && node swusim-limited-builder-xbrowser.mjs [BASE] [SHOTS_DIR]
Fixture: SWUSim/DevTools/tests/limited_fixture.php?phase=building (6 SOR packs each, nobody submitted) and ?phase=sideboard
(a Bo3 after game 1). Then by eye: play one Bo3 Sealed event as claudebot1 vs claudebot2 (Main Menu → Limited).

## WHAT TO LOOK AT (in order; each one can fail)

1. **Head.** "BUILD YOUR DECK" (or "BETWEEN GAMES — YOUR DECK") and a gold m:ss clock that counts down; red under a minute.
2. **Tabs.** LEADERS (n) / BASES (n) / CARDS (n LEFT) — the number and its words spaced, never "54LEFT", on one line at
   1440px; at 390px a two-line label is fine but the parenthesis stays with its number.
3. **Search + filters.** A sunken search well; six aspect icon chips, then Unit / Event / Upgrade / Ground / Space /
   Cost 0-2 / 3-4 / 5+ chips. A pressed chip has a gold outline and a full-opacity icon. Every chip ≥44px.
4. **Pool grid.** Square tile art with a gold ×n badge (copies NOT in the deck). A used-up card stays, at 30% opacity,
   ×0. Hover 500 ms → a 300px full-art preview beside the cursor; a click hides it.
5. **Deck side.** Leader and base slots (landscape art, gold rim when filled, dashed "LEADER" / "BASE" when empty), the
   cost-curve bars 0…6, 7+ with counts, "Main deck n / 30 min ✓ ready to submit", "Sideboard: n", the deck grid by cost.
6. **Buttons.** Submit (steel) is live only for a changed, complete deck; Ready (gold) only once submitted and unchanged.
   Ready asks "Lock in this deck?"; after it, the status reads "Ready — waiting for your opponent" and clicks do nothing.
7. **Errors.** A refused submit lists its reasons in red, naming cards by TITLE (never SOR_010).
8. **Between games.** Opens on the last game's 30 cards with Ready already live (keeping the deck is one click), a
   ≤5:00 clock, and every leader of the pool on the Leaders tab.
9. **Phone (390px).** A POOL | DECK (n) switch shows one column at a time; the footer (n/30, the clock, Submit, Ready,
   status, Back to the room) sticks to the bottom while the pool scrolls; the chat is a block at the foot of the page,
   not a floating launcher; no sideways scroll.

## NEIGHBOURING INVARIANTS (must not change)

- The plain sideboard screen (Sideboard.php) for every non-Limited match: swusim-sideboard-preview-xbrowser.mjs stays green.
- The lb- rules are scoped `body:has(.lb-root)`; no other page has that root.

## CROSS-BROWSER / MOBILE

Chromium, Firefox and WebKit at 1440px and 390px (both contexts). Responsive CSS, no separate mobile markup, so
`?swuLayout=mobile` changes nothing here. ⚠ The long-press preview and native touch on a real iPhone (Safari) are the
owner's manual checks — Playwright cannot drive a real long-press.
