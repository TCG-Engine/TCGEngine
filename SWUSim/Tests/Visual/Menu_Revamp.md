# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — the main-menu revamp (owner's mockup, 2026-09-21)
#
#   Same three columns and controls as before, restyled to the owner's mockup and recoloured from its navy to the
#   STONE GREY of the in-game buttons (petranaki-hud --btn-fill), with sandy-gold corner glows. Background art kept.
#   Where it lives:
#     SharedUI/Sites/SWUSim/css/swusim-menu.css      — everything inside .swu-menu-grid (menu page only)
#     SharedUI/Sites/SWUSim/css/swusim-overrides.css — header plate + nav plate (every SWUSim site page)
#     SharedUI/Render/Header.php                     — optional branding.logo (SiteDef) → img.title-logo
#     SharedUI/Sites/SWUSim/assets/stadium.svg       — the emblem (solid black; tinted gold by CSS filter)

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1672px and 390px):
    cd DevTools/ui-harness && node swusim-menu-revamp-xbrowser.mjs
Screenshots: /tmp/menu-revamp-<engine>-<width>.png. Then look, by eye, at
    http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php   (logged out, then logged in as claudebot1 / pass)
side by side with the mockup.

## WHAT TO LOOK AT

1. **Header plate — COMPACT** (owner, 2026-09-21: early user feedback said it "demands too much attention"): about the
   height of the nav chips (≈72px desktop, ≈58px phone), a 52px emblem (42px on phones), a 30px title, and the tagline
   on ONE line — title and tagline never wrap, even at 360px. The SAME glass as the cards (swusim-overrides.css "Petranaki GLASS"): flush to the left edge, cut
   top-left + bottom-right with the gold corner glows, blurred art through it, a soft shadow below. The gold stadium
   emblem sits left of "PETRANAKI ARENA" (spaced uppercase) and the tagline. NO darker rounded box inside the glass —
   that is the shadow layer showing through (its clip-path must cut the glass shape out of it).
2. **Nav.** Two glass chips like the cards: Previews | Support | Profile | Log Out (dividers between), and the
   Discord + GitHub icons in their own chip. Hover turns a link gold.
2b. **Every cut-corner button rim is EVEN** — the same width on the diagonal as on the straight edges, and top/left the
   same as bottom/right (menu buttons, the OK dialog button, the in-game Yes/No, Pass, base tabs). Rims must be WHOLE
   pixels: a 1.5px inset snaps to 2px on top/left but 1px on bottom/right. Inner cut = outer cut − 0.586 × rim.
3. **Panels.** All three are frosted stone-grey GLASS (2026-09-21 depth pass): the arena art shows through, BLURRED —
   never sharp — with a lit top edge, a darker bottom, and a soft drop shadow falling below each panel onto the art.
   Cut at top-left and bottom-right, a thin steel hairline, and a gold glow fading out from those two corners. The cut
   corner triangles must be clear (no blurred or shadowed square poking out). Inputs, selects and the Active Games box
   read as sunken wells; the action buttons cast a small shadow.
   ⚠ Playwright's headless Firefox does NOT render backdrop-filter at all (even a plain full-page blur), so in its
   screenshots the art looks sharp through the glass. Check the blur in a REAL Firefox. Browsers without backdrop-filter
   get a more opaque fill (@supports fallback in swusim-menu.css).
3b. **Mid widths (800–1180px).** The header plate sits BELOW the nav chips, never under them.
4. **Active Games.** "ACTIVE GAMES (N)" + a square refresh button; the grey subtitle; a sunken box with a people icon,
   "No active games in queue", "Be the first to challenge…", and a REFRESH QUEUE button. With players queued the line
   reads "N players waiting in the queue".
5. **Create a New Game.** Title, a rule, and the small "FIGHT TOGETHER / PLAY ANYWHERE" kicker (hidden on phones).
   DECK LINK / FREE TEXT are two separate cut-corner tabs; the active one has a gold rim and glow. Field labels are
   small spaced uppercase (GAME TYPE, OPPONENT, CARD POOL, MATCH TYPE); selects have a light chevron. The primary
   action (Join Queue / Start Arenabot / Join Private Invite) is gold-rimmed with gold text and an icon; Save Deck and
   Create Private Room are steel with icons. Switching Opponent/Mode relabels the buttons WITHOUT losing the icon.
   No "Test Deck" button (removed 2026-09-21).
6. **Welcome.** WELCOME / REPLAYS tabs (gold rim on the active one), the welcome text with a faint gold stadium
   watermark to its right, the gold-bordered DID YOU KNOW? card (lightning icon, square next-tip button), QUICK
   REFERENCE with key-cap badges, and "SAME GALAXY ◇ MORE GAMES" at the bottom.
7. **Footer.** "MAY THE GAMES CONTINUE" in spaced gold under the panels, above the disclaimer.
8. **Phone (390px).** Panels stack; the header plate stops short of the burger button; action buttons go full width;
   no horizontal scroll; the flourishes stay on one line.
9. **Other SWUSim pages** (Previews, Profile, Login): the new header and nav plates appear there too; their own panels
   are unchanged (the panel restyle is scoped to the menu grid).
10. **In game.** Nothing changes — the theme tokens were not touched.
