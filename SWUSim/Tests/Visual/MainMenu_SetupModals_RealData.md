# VISUAL CHECK — Main Menu setup modals: real data, and the chamfer that pays for it

The four setup modals shipped with the mockup's fixtures baked in: three invented saved decks
("Krennic Blue", "Ahsoka Go Wide", "Maul Force Tempo") that belonged to nobody, and **one** Twin Suns
pre-con out of the four already sitting in `SWUSim/Custom/TwinSunsPreCons.json`. This check is that the
panels now render what the server actually has, and that the buttons look like the approved mockup.

## Fixture

Guest (the strict case — a guest owns no saved decks):

    http://localhost:3400/TCGEngine/SharedUI/MainMenu.php

Logged in, for the saved-deck picker (the `claudebot1` test account must own ≥1 saved deck):

    log in as claudebot1 / pass, then open the same page

Automated: `node DevTools/ui-harness/swusim-menu2-pickers.mjs` covers both, in all three engines.

## WHAT TO LOOK AT

### 1. Twin Suns → PRE-CONS — **four** rows, not one

Each row: three card images (two leaders + base), the deck name, `by Mobyus1`, then
`<leader> · <leader> / <base> · 80 cards · singleton`. The names are read from the card
dictionaries, so "TS26_02" must appear as **Anakin Skywalker, Protect Her At All Costs** — an id
showing through means `titleData` did not resolve and the row is lying about its deck.

### 2. Arenabot → BOT PRE-CONS — **23** rows, in a 340px scroll window

Two lines per row: name on top, `<leader> / <base> · N cards` under it, with the style tag
(`SOFT AGGRO`, `HARD CONTROL`, …) right-aligned. ⚠ If the name and the meta collapse onto ONE line with
no gap — "Ahsoka BlueAhsoka Tano, Trust in the Force" — the row is using the RICH (`.pc__text`-wrapped)
markup. `.pc__row` is a grid over its DIRECT children; the plain row needs name/meta/end as three
tracks, the rich row nests name+meta so the art can sit beside them. They are not one shape with a flag.

### 3. SAVED DECKS — the picker must PAINT

* **Guest:** a dashed empty panel reading "No saved decks yet", and the note under it must say the decks
  live on your **account** — this sim's `deckLibrary.storage` is `account`, so the old copy ("live in
  this browser") was simply false.
* **Logged in:** a fat row with leader + base art, the deck name, and `<leader> · <base>` beneath it,
  **left-aligned and in sentence case**.

⚠ The failure to look for is a **BLANK BAR**: correct height, correct chamfer, nothing inside it. A
`.deckprev` row is `display:none` until a rule for *its own key* reveals it. The mockup could hard-code
ten rules for ten fixture names; real keys are per-account hashes, so the rules are emitted with the page
by `SWUSetupPreviewStyles()`. Drop that call and the deck is still in the DOM, measuring 0×0. Presence in
the DOM is NOT the check — the geometry is.

### 4. The buttons — one chamfer, not two

`JOIN QUEUE` / `START 1P GAME` must be a **gold plate with near-black text**. `CREATE PRIVATE ROOM` is
steel. `CANCEL` is quiet.

⚠ `components.css` paints every bare `<button>` with its own chamfer (`::before` rim, `::after` fill,
`--btn-*` tokens) at specificity 0-2-1, and this design paints its own with `.ch` at 0-1-0 — so the
legacy one won and `.ch`'s plane never rendered. The tell is subtle and nasty: `.swu2-btn--primary` had
already switched the text to `--gold-ink` for a gold fill that was not being painted, leaving **dark text
on a dark plate**. If the primary action is unreadable, the two chamfer systems are fighting again.

### 5. The segmented controls — two options, not four

Twin Suns → ARRANGEMENT (`Free-For-All` / `Team Suns`) and 1P → MODE (`Goldfish` / `Hotseat`) are joined
pills of two equal halves.

⚠ If a label wraps onto three lines in a ~63px box, the visually-hidden radios are visible again:
`input[type=radio]` (0-1-1) beats `.u-vh` (0-1-0), so each radio takes its own grid track and a
two-option toggle becomes four columns.

### 6. Fields

Every input and select is **44px** with its text vertically centred, and `SAVE DECK` has its icon
**beside** the label. A field at 74px means `menuStyles.css:119` (`input, select { margin: 10px 0 20px }`)
is leaking in again; a stacked Save Deck icon means the button lost `display: inline-flex`.

### 7. Choosing a deck SHOWS you what you chose (owner, 2026-09-25)

Pick a deck in SAVED DECKS. Three things must happen together:

* the **Deck Link box fills with that deck's source link** — the link is the thing you can copy,
  reopen and re-import, and it is now the only kind of input that can be saved at all;
* a line appears under the picker: *Loaded &lt;name&gt; — its deck link is in the box above.*;
* the pool chip re-detects from that deck.

Pick a **pre-con** instead and the line reads *Playing the &lt;name&gt; pre-con* (…*as the bot's
deck* in Arenabot), the link box is emptied — a pre-con is a full list, not a link — and the
saved-deck dropdown drops back to its "none" row.

⚠ **Nothing may look chosen twice in one slot.** A saved deck and a pre-con both wearing the
selected state means only one of them is really being played and the player cannot tell which.
Choosing either clears the other.

⚠ Picking a saved **Twin Suns** deck from PvP must still move you to Twin Suns, carrying the link
AND the picker's selection. If the destination's picker shows a different deck from the one in
its link box, `SYNC_PICKER_TO_LINK()` did not run.

### 8. Guests save decks to THIS BROWSER (owner, 2026-09-25)

Logged out, paste a link and press SAVE DECK. The deck must appear in the picker **without a page
reload**, and the confirmation *Saved "&lt;name&gt;" to this browser…* must still be on screen
afterwards. Reload by hand: the deck is still there.

The note under the picker reads *Saved decks are kept in this browser…*, never the old *Log in to
save decks* — guests can save now.

⚠ Guest pickers are built by `GUEST_BUILD_PICKER()` in JS, a **second builder** for the markup
`SWUSetupDeckPicker()` emits server-side. They must be indistinguishable: same `.deckpick`,
`data-slot`, `option[data-deck-input]`, `.deckprev--KEY` rows and listbox enhancement.
`swusim-menu2-pickers.mjs` asserts that; if you change one builder, run it.

⚠ Storage can fail (private window, blocked cookies, full quota). It must degrade to the empty
state and a message, never to a broken picker or a thrown error.

### 9. Only LINKS may be saved (owner, 2026-09-25)

Paste `{"leader":{"id":"SOR_001","count":1}}` into Deck Link and press SAVE DECK. It must be
refused with *Only deck links can be saved — a pasted list has no source to return to.* and
**nothing** may be written, to the account or to localStorage. Same for a multi-line pasted list.

Pasted lists still **play** — this restricts saving only, because a saved blob can never be
re-synced with the deck it came from and has no link to show in rule 7.

⚠ The verdict is the server's: `ValidateDeck.php` returns `savable`, from `SWUDeckInputIsLink()`.
The client must not re-implement it, and `SavedDecks.php` enforces it again — the UI is not a
boundary, and a direct POST will keep sending whatever it likes.

### 10. It must match the approved mockup

`node DevTools/ui-harness/swusim-menu2-mockup-diff.mjs` (also `DLG=setup-twin-suns`, `W=390`)
diffs computed styles against the preview, element by element. Arenabot and 1P must report **0
differences**; PvP and Twin Suns report only these, which are noise:

* `.dblock` 170 vs 69 — the mockup carries a "Mockup only" demo-link block (~102px) that is
  deliberately not ported, so `.pane` / `.body` / `.scroll` each come out ~102px shorter;
* pre-con and saved-deck counts — the mockup has one fixture, the live page has the real data.

**Anything else is a regression.** The typeface is **Archivo**, not Barlow — and this is the one
you cannot check by eye against a computed style, because `getComputedStyle` reports the declared
stack whether or not the face downloaded. Verify it with
`[...document.fonts].find(f => f.family === 'Archivo').status === 'loaded'`.

⚠ The diff tool's reference lives under `docs/superpowers/`, which is **gitignored** — on a fresh
clone it exits 2 rather than reporting a clean run against nothing.

### 11. The topbar

Brand at the page gutter (x=68 at 1440), nav links **centred in the viewport**, icon links flush
right, bar 69px tall. `LOG IN` is a chamfered steel plate (`.nav__link--edge`), the other links
are plain until hover, and a hairline separates them from the Discord/GitHub squares.

⚠ The CTA plate is keyed on the **login href**, not `:last-child`. Signed in, the last item is
"Log Out", and dressing a sign-out as the page's primary action is wrong.

⚠ The `<nav>` is a SIBLING of `<header>` (two separate shared renderers), so it is stretched
across the header band absolutely. Its height must stay pinned to the band —
`block-size: 100%` resolves against the **viewport**, which turned the nav into a 1440×900
transparent sheet over the page and made every mode card unclickable. If cards stop responding,
check `document.elementFromPoint(720, 500)`.

Below 900px the live site uses its **burger**, where the mockup reflows the links onto a second
row — a real divergence, because the mockup never modelled the burger. What must still hold: the
wordmark is `--t-15`, clear of the burger button, and the bar passes contrast.

### 12. The disclaimer footer

It sits at the bottom of the content with the legal text and both links **visible**.

⚠ The failure mode is a **completely blank bar**. `.foot::before` paints the footer's plate across
`inset: 0`, and the content has to be lifted above it — the mockup does that on `.foot__in`, but
the live footer is the shared `Disclaimer.php` markup (`.disclaimer > p`), which never got the
lift. The text was rendering at full size in the right colour underneath its own background.

### 13. "Last deck used"

Automated: `node DevTools/ui-harness/swusim-menu2-lastdeck.mjs`.

Open a modal and the Deck Link box is already filled with the deck you last **started a game**
with, above a line reading *Filled in the deck you played last — <name>*.

* Recorded where the submission succeeds, not where a link resolves — a deck you pasted to look
  at and rejected must not come back next visit.
* Prefills only modals that can **seat** it, matched on leader count: a one-leader Premier deck
  fills PvP / Arenabot / 1P and must leave **Twin Suns blank**.
* Remembered in localStorage always, and on the account when signed in; the **account copy wins**,
  so a machine used as a guest first cannot override it.
* If the remembered link no longer resolves, the box stays **blank** and the deck is forgotten so
  it stops coming back. Being offline is not "no longer available" — nothing is forgotten then.

### 14. The guest banner

*Playing as a guest — log in to use in-game chat.* must appear **only when logged out**. It came
across from the mockup ungated and told signed-in players they were guests.

### 15. The other SiteDef pages

`swusim-menu-2.css` is scoped `body:has(.home-header)` — **every** SWUSim SiteDef page, not just
the menu. Login, Signup, Profile, Previews, Waiting Room, Terms and Privacy all share the
topbar, the steel arena plate, Archivo and the restyled disclaimer.

⚠ The **gameboard is not one of them**. `NextTurn.php` builds its own `<head>` and never loads
this stylesheet, so nothing here can reach it — it keeps the amber until it is reworked.

⚠ No amber on an interior panel. The corner glints are `--pa-glow-tl/-br` from
`petranaki-glass.css`, which run `#ffd998 → #e9b866`; they are overridden to steel inside this
scope. Gold still marks the **active tab** and the **primary action** — that is the rationing,
not an inconsistency.

### 16. Login / Signup — the 'arena' layout

Automated: `node DevTools/ui-harness/swusim-auth-xbrowser.mjs`.

One centred glass card: title, Discord button, `OR` rule, labelled fields, a chamfered
checkbox, a **steel** submit, and a quiet consent note *below* the card rather than in a second
panel competing with it.

⚠ The submit is deliberately **not** the gold primary. Gold works on the menu because it competes
with steel buttons; on a page whose only action is "log in" it has nothing to beat and the card
just goes gold. The checkbox tick is the one warm accent — it marks the thing that changes state.

⚠ **This is a SiteDef opt-in** (`auth.layout = 'arena'`). `RenderLoginPage`/`RenderSignup` are
shared with FaBSim, HellbreakSim, SWUDeck and HellbreakDeck, which must keep the legacy shell.
`RunRenderTests` asserts that **positively**, against the old markup's own markers — comparing
the default against a value computed the same way is a tautology that passes while all four
sites get re-laid-out underneath them.

⚠ The thing that actually matters is that **it still logs you in**. The gate drives a real
sign-in in all three engines. A re-layout that renames `userID`, `password` or `rememberMe`, or
turns the styled checkbox into a `<span>`, is a site nobody can get into — and it would look
perfect the whole time.

### 17. The six control patterns

Owner, 2026-09-25 — the Petranaki theme is built from six control patterns, and the main menu is
where each is canonical. **Anything that looks like one of these must BE one of these.**

| # | pattern | canonical markup | where |
|---|---|---|---|
| 1 | important dropdown | `.selwrap.ch > select.select` | Match Type |
| 2 | minor dropdown | `.poolpick` → `.lb--chip` | the Premier pool chip |
| 3 | input + button | `.inwrap.ch > input.input` + `.swu2-btn.ch` | Deck Link + Save Deck |
| 4 | options toggle | `.seg > .seg__in.u-vh + .seg__opt.ch` | Arrangement |
| 5 | action / neutral / cancel | `.swu2-btn--primary` / `.swu2-btn` / `.swu2-btn--quiet` | Join Queue row |
| 6 | deck dropdown | `.deckpick` → `.lb--deck` | Saved Decks |

Every one is **44px tall, chamfered, `border-radius: 0` and has no `border`**. If a control shows
a 3px radius or a 1px border, it is on the legacy styling, not the pattern — that is the quickest
tell, and it is what `_tmp_audit`-style probes should assert.

Patterns 1, 3 and 5 recur off the menu, on pages whose markup comes from **shared renderers** that
cannot carry `.selwrap` / `.inwrap` / `.swu2-btn`. Those recipes are therefore mapped onto what
those renderers emit, from the same tokens — one source for the look, two sets of selectors.
`:not(.select)` / `:not(.input)` is what keeps the mapped rules off the canonical controls: a
control already inside a wrapper has its well drawn by the WRAPPER, and applying it to the
control as well chamfers it twice.

⚠ **`:is()` takes the specificity of its most specific argument.** The shared field rule's list
contains `input[type="text"]:not(.input)` at 0-2-1, which lifts that whole compound above a
select-only rule at 0-1-1 — so `background-image: none` silently beat the chevron and **every
dropdown on Profile lost its caret while looking otherwise perfect**. Properties that differ per
control type (background-image, padding) are therefore set in per-type rules, which target
disjoint elements and cannot fight.

## 18. Arenabot — Bot Style follows the bot's deck

Open **Arenabot**. The default bot pre-con (Ahsoka Blue) is checked, so **Bot Style already reads
`Soft Aggro`, not `Midrange`**, and a full-width status line sits under the Start/Cancel row:

> Bot style set to Soft Aggro — matching the Ahsoka Blue pre-con. Change it above for a different matchup.

Then, in order:

| do this | Bot Style becomes | the line says |
|---|---|---|
| click the **Aurra Red** pre-con | Hard Control | `matching the Aurra Red pre-con` |
| paste a link into **Bot Deck Link** | whatever the classifier answers | `it matches the deck you gave the bot` |
| clear Bot Deck Link, paste into **Deck Link** | the classifier's answer for *your* deck | `the bot will be playing your deck` |
| paste nonsense into Bot Deck Link | **unchanged** | unchanged |

**Why the last row matters.** Owner, 2026-09-22: every deck load re-picks, even over a hand-picked
style; a failed lookup changes nothing. What is new is that it *says so* — the legacy
`swuAutoPickBotStyle()` rewrote the control on every deck blur with no notice and no undo.

**A pre-con must not touch the network.** Its archetype is `data-style`, straight from
`BotDeckLabels.json` — the same label the classifier would return, so asking would be a round trip
to be told what the row already displays. `swusim-menu2-botstyle.mjs` counts requests to
`APIs/SWUBotDeckStyle.php` and fails if a pre-con makes one.

⚠ **The gate's own trap.** The first version of the own-deck-fallback check passed with the
fallback *deleted*, because the default pre-con (`ahsoka_blue`) and the fixture link happen to
share an archetype. Emptying the bot slot through the real UI path, plus a sentinel style the
answer cannot be, is what makes that check bite. Same family as
`geometry-assertions-cannot-see-a-screenshot`: a check that cannot distinguish the fix from the
bug is not a check.

Fixed along the way: `#ab-bot-link` carries no `data-detect` (only the *own* box does, because
format detection reads the player's deck), so SETUP_BIND_PICKERS' `input` handler never saw it —
typing a bot deck link left the bot pre-con checked and looking chosen while `SETUP_DECK_FOR` was
already playing the typed link. That handler now matches the same link-box list the rest of the
file uses.

## 19. The mode cards count REAL lobbies

The PvP and Twin Suns cards' stat lines were mockup fixtures: `4 players in queue` and
`2 tables forming`, hardcoded. They are now derived from the live lobby cache — the same rows
`APIs/Lobbies/GetLobbies.php` builds.

Open the menu with nothing running: **`No one in queue`** and **`No tables forming`**. Queue for
Premier in one browser and reload in another: the PvP line reads `1 player in queue` (singular).
Press Escape to cancel and it goes back.

| rule | why |
|---|---|
| a PRIVATE room is never counted | it is not a queue anyone can join, and counting it advertises that the room exists |
| a MATCHED lobby is never counted | it has become a game — that is Games in Progress' story |
| PvP counts PLAYERS, multiplayer counts TABLES | a queue is people waiting; a table is the thing you join |
| the split is the format's own **seat range** | never an id list — `SWUFormatIsRoomFormat()` states the same rule |
| zero says something | a card whose foot goes blank reads as broken, not as empty |

The label comes from `SWUMenuStatLabel()` for BOTH the server's first paint and the 20s poll, so
the two can never word one state differently. The counts ride along on
`SWUSim/PublicGames.php` (additive) rather than costing a second request.

⚠ **`SWUFormatSeatRange()` returns a LIST, `[min, max]`** — not `['maxPlayers' => …]`. The first
version of `MenuLobbyStats.php` read the associative key, and the unit test injected a fake of
that same invented shape: fake and code agreed, 25/25 green, and a live Twin Suns table was being
counted as two PvP players the whole time. `swusim-menu2-modestats.mjs` caught it. The test now
also drives the REAL registry with no fake at all — those checks cannot be fooled that way.

⚠ **The mode-stats gate asserts no ±1 delta.** A lobby has a 600s TTL, so a dev box carries
strays, and a second visitor joining the same public queue MATCHES the first — which starts a game
and empties the queue. The gate uses a private room (cannot match, must not be counted) and a Twin
Suns table (needs 3-4 players, so one joiner cannot complete it).

## 20. The Waiting Room

`WaitingRoom.php` is a shared renderer (FaBSim uses it too) that prints its own `<style>` in the
BODY, so its rules are later in source order than the redesign sheet and win any specificity tie.
Its Petranaki port therefore lives in `swusim-menu-2.css` scoped `body:has(#wr-root)`.

Every control on the page passes the tell: **44px, chamfered, `border-radius: 0`, no border** —
seats, status pills, the deck link field, the saved-deck dropdown, the status strip, and the
buttons (Leave, Copy Invite Link, Change Deck, Ready, Start Game).

⚠ **A `<textarea>`/`<select>` cannot host a `::before` plane** the way `.inwrap` does — the
control's own box paints over it. The well is built from **stacked background layers** instead:
the sunken plane over the rim gradient, inset by the rim, chamfer clipped from the pair. An inset
`box-shadow` was the first attempt and it covered the caret as well as the rim — the same
"dropdown looks perfect but lost its arrow" failure as the Profile fields (§17).

⚠ **Scope the `.btn` mapping to `.wr-panel`, not the page.** `body:has(#wr-root) .btn` also matched
`#tcg-chat-toggle` — the chat drawer's handle, which lives OUTSIDE the room and is `display:none`
above 900px — and popped a 44px bubble onto the left edge of every desktop lobby.

The chat panel HUGS ITS CONTENT by owner ruling (2026-09-22: a full-height column "makes it clear
we are wasting precious UI real-estate"). Its short card is correct, not a bug.

## 21. Games in Progress — the match chip

The chip is the approved shape from `docs/superpowers/mockups/2026-09-24-swusim-main-menu.html`:
leader + base art, the leader's title with its **set code**, the base name under it, a `VS` rule
between the two seats, then format / round / elapsed and Spectate.

Owner, 2026-09-25, and the one change FROM the mockup: **the base is the same size as the leader**
(`--tb-w/--tb-h` = `--tl-w/--tl-h` = 38×27), not the mockup's smaller base.

⚠ **The CSS was right for days and nothing was wearing it.** The whole `MATCH CHIPS` section was
ported with the redesign while `swuGameChip()` still emitted the older `.swu-game-chip` /
`.swu-idstack` markup. A stylesheet-only check cannot see this, which is why
`swusim-menu2-gamechip.mjs` asserts the LIVE DOM. It needs a public game in progress and exits 2
with "NO GAME TO TEST" rather than passing on air when there is none.

⚠ **`createdAt` on the active-game index is NOT the game's start time.** That index is an APCu
cache with a **60s TTL**: let a game go quiet for a minute and the entry is rebuilt with
`createdAt = now`, so an elapsed time taken from it silently RESETS mid-game — measured doing
exactly that, 1790366499 → 1790366695 on one idle game. `startedAt` therefore comes from
`Match.json`'s own `createdAt`, which is a real file. The same TTL is why a quiet game drops off
the panel entirely until its next action.

**Round** rides on the index (the engine rewrites it on every action, so it costs no extra read —
the alternative was parsing each running game's full gamestate on a 20s poll). It is legitimately
**0** until a game's first action after an index rebuild, and the chip omits "Round N" rather than
guessing. The gate compares the chip against the payload that drew it for the same reason: a flat
"there must be a round" check goes red on a quiet board.

## 22. The legal pages — Terms of Use / Privacy Policy

Opted in by SiteDef `legal.layout = 'arena'`; `RenderLegalPage()` (Render/Template.php) frames the
shared `templates/*.tmpl` in `.row-wrapper > .card.ga-glass-card > .legal-page__prose`. The panel
therefore comes from the INTERIOR PANELS recipe (§ above) and only the prose is styled.

Open either page at 1440px: the text sits in a chamfered panel on the 1368px measure, paragraphs
bounded to **72ch**, headings in **Archivo**, no horizontal scroll. At 760px the panel keeps
>=16px of padding.

⚠ **Before this they had NO FRAME.** The templates are bare prose — every site supplies its own
wrapper and SWUSim supplied none — so each line was a DIRECT CHILD OF `<body>`, running the full
1440px hard against both screen edges. The gate measured paragraphs at **174ch**.

⚠ **`PrivacyPolicy.tmpl` has no paragraphs.** Its body copy is bare text nodes separated by
`<br><br>`, so a reading measure applied to `<p>` reaches none of it. The measure lives on
`.legal-page__prose`, which bounds loose text whatever markup a template uses. A gate that
requires a `<p>` is testing the template's markup, not readability — mine did, and was wrong.

⚠ **Another previous-generation rule had to be retired**: `swusim-overrides.css`'s 2026-09-21
title rule put Barlow on every heading inside a site panel at 0-4-2, beating the redesign at
0-2-2. Removing it moved Login, Signup, Profile and Previews to Archivo as well.

⚠ **The small-screen MENU trim was leaking**: `.card { padding: 12px !important }` fires on any
page under 900px, which put legal prose against the panel edge. Now `:not(.legal-page)`.

**Heading outline (owner, 2026-09-26).** `PrivacyPolicy.tmpl` used six `<h1>`s as section headings
and had no page title; it is now one `h1` ("Privacy Policy"), six `h2` sections, eight `h3`
subsections. Two malformed tags surfaced during the demotion and were fixed: `<h1>Sources</h2>`
(mismatched) and a stray `</a>`. `RunRenderTests` now asserts one `h1`, that the document opens
with it and names itself, and that no heading level is skipped — the rule passes `TermsOfUse`,
which was already correct, and failed `PrivacyPolicy`, which is what makes it a real check.

Gate: `DevTools/ui-harness/swusim-legal-xbrowser.mjs` — 108 checks, 3 engines, 1440px and 760px.

## 23. The Sideboard (between games of a Bo3)

    php SWUSim/DevTools/make-sideboard-fixture.php
    http://localhost:3400/TCGEngine/SWUSim/Sideboard.php?matchId=zzsideboardfixture&playerID=1&authKey=fixture

⚠ **That fixture is why this page was missed.** `Sideboard.php` 404s without a live match, so it
sat out the entire redesign carrying its own self-contained cyan-on-navy stylesheet
(`--swu-cyan`, Aptos/Bahnschrift). A page you cannot open is a page nobody re-skins. Run
`--remove` to delete the fixture.

It now renders `RenderHead()` + `RenderHeader()`, so it takes the site's fonts and stylesheet
stack, and the header plate gives it `.home-header` — the scope hook the redesign's rules and the
Petranaki chat skin key off, so the chat panel and buttons come along for free.
⚠ **The MENU BAR is deliberately absent.** You are mid-match; a nav link out of a sideboard
abandons the game.

Check: Archivo throughout, Submit is the gold primary at 44px, both card grids chamfered with a
gold quantity badge, hovering any card shows the large preview, no horizontal scroll.

**Card links in the log (owner, 2026-09-26).** `GetGameLog.php` returns lines carrying
`[[SET_NNN]]` tokens plus a server-built `names` map (only the server has the dictionary — the
page's own `titles` covers just your deck, not the opponent's). The page used to flatten each
token to plain text, so a log full of cards had nothing to hover. `TCGChatPanel.appendLog()` takes
an optional `{cards}` map and renders them as elements.

⚠ **DOM NODES, NEVER innerHTML.** Every other row in that panel is user-authored chat. A card name
comes from the dictionary but still lands as a text node — the gate feeds it a name containing
`<img onerror>` and asserts it renders as text.

⚠ **A GATE THAT CALLS `appendLog()` DIRECTLY TESTS THE PANEL, NOT THE PAGE.** The first version of
`swusim-sideboard-xbrowser.mjs` did exactly that, and the mutant restoring the reported bug
(flattening the tokens) passed **39/39**. It now intercepts `GetGameLog.php` and asserts what the
PAGE rendered from a real response. Same family as [[test-called-a-different-function-than-production]].

Gate: `DevTools/ui-harness/swusim-sideboard-xbrowser.mjs` — 51 checks, 3 engines.

## What must NOT be here

No "Krennic Blue", "Ahsoka Go Wide", "Maul Force Tempo", "Jabba Qi'ra Combo", "Kylo Vonreg Arenas",
"Bane Fett Tempo", "Leia Go Tall", "Luke Pilots" or "Tarkin Grind" anywhere on the page. Those were the
mockup's fixtures. `grep -c 'krennic' SharedUI/Sites/SWUSim/MainMenu.php` must be 0.
