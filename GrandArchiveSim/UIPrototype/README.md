# Grand Archive live React prototype

An opt-in match interface that uses the existing PHP rules engine. The original
match screen remains available. Nothing has been deployed or linked from the lobby.

## Open it

```sh
npm --prefix GrandArchiveSim/UIPrototype ci --ignore-scripts
npm --prefix GrandArchiveSim/UIPrototype run build
```

On the **same PHP host as your match**, open `/GrandArchiveSim/UIPrototype/`,
choose **Open live viewer**, and enter the game number, seat, and match access
key. Use the match key, not the remote card-database API key. Spectators can
connect without receiving private cards or controls.

`npm --prefix GrandArchiveSim/UIPrototype run serve` serves the simulated design
preview at http://127.0.0.1:3211. That static server cannot connect to live PHP
matches. A generated GrandArchiveSim runtime and the normal game hosting setup
are prerequisites for live play.

## Gameplay coverage

- Play hand cards and supported graveyard/banish/material activations; activate
  static and granted abilities; declare attacks; end the main phase through the
  engine's normal opportunity window.
- Respond at fast speed, including choices between multiple abilities on a card.
- Materialization, target selection, optional choices, multiple-card selection,
  modal choices, yes/no, numbers, split assignment, two-sided numeric splits,
  card naming, and rearrangement between piles.
- Revision-bound choices with explicit confirmation. Multiple-choice budgets,
  numeric limits, split totals, duplicate picks, and rearrangement membership
  are checked server-side as well as in the interface.
- Public board and mastery zones, private own hand/memory/material, concealed
  opponent counts, lazy-loaded art, keyboard-accessible printed-card inspection,
  power/life/level, common counters and keyword indicators, and public lineage.
- Public reveal events, pending effects in resolution order, trigger labels, recorded public field
  targets, combat participants, victory, and confirmed concession.
- Configured bot games expose **Continue bot turn** through the existing bot
  controller. Bot stepping is currently explicit, not automatically scheduled.

The narrow server control map is rebuilt under the game write lock. Clients
never choose arbitrary engine modes or engine answer strings. Decision option
IDs map back to the current offered choices only after revision and membership
validation. The legacy input API remains available and unchanged for other apps.

Credentials stay in memory and POST bodies. Polling is sequential every two
seconds, unchanged revisions avoid rerenders, and failed reads clear the board.
Actions never retry automatically. An uncertain response keeps controls locked
until a newer snapshot arrives or the user reconnects. Editing a choice or
inspecting a card does not submit anything. No priority auto-pass is enabled.

## Validation

From the repository root:

```sh
php DevTools/test-ga-ui-projection.php
php DevTools/test-ga-ui-controls.php
php DevTools/test-ga-ui-revision.php
php DevTools/test-ga-legacy-modal-codegen.php
php DevTools/test-ga-ui-revision-http.php --browser
npm --prefix GrandArchiveSim/UIPrototype test
node GrandArchiveSim/UIPrototype/browser-check.mjs
```

For browser checks, install Playwright with Chromium, Firefox and WebKit, or set
`PLAYWRIGHT_MODULE` to an existing Playwright `index.mjs`. The responsive suite
uses port 3211 by default (`GA_REACT_URL` overrides it) and checks four viewport
sizes plus the new form families. The HTTP harness creates a disposable game,
uses real authentication/locking/input handling, and cleans up afterward. It
checks actual play, materialization, combat entry, concession, and a short
2-player game through normal phase decisions to deck-out victory. Its browser
mode checks real authenticated views, selections and passes in all three engines.
APCu is substituted in that local harness; production cache behavior is not
certified by these tests.

The local generated ability file was found empty during this work. Regeneration
also uncovered older two-argument letter-key Modal rows. GA's code-generation
adapter now translates those into the modern indexed Modal protocol while
preserving letter results; it does not write to the hosted database. Generated
files must be regenerated, never edited by hand.

Latest validation (2026-10-28): 281 projection checks, 75 control checks,
10 revision checks, 7 legacy-modal compatibility checks, and 17 JavaScript tests.
The full HTTP/browser run passed 77 checks, including real browser play through
card inspection. The responsive suite passed all 12 browser/viewport combinations
and exercised six form families in each. The final gameplay-only rerun also
covers attack availability and public reveal projection. Built JavaScript:
221,408 bytes (68,300 gzip).

## Prototype limits and rollout work

This is a playable prototype, not full production parity. Unknown decision
formats fail closed. Existing engine/card limitations still apply. Printed text
is a reference, not a full account of every copied/modified ability; targets are
shown only when recorded with supported public identities. Other temporary
reveal permissions and arbitrary internal counters are not dumped to clients.

Lobby/deck selection, match-series sideboarding/rematch, chat, undo/replay controls,
full priority preferences, automatic bot pacing, and a production opt-in switch
remain outside this prototype. Pantheon remains on its separate branch.

Performance comparison against the original renderer and real-device usability
are still rollout gates. The React trial does not run in RL/self-play. Bundle
size is reported by the build; a small payload is not proof of faster matches.

### Launching from your own local browser

Open `http://localhost:3200/TCGEngine/SharedUI/Sites/GrandArchiveSim/MainMenu.php`. On localhost, 127.0.0.1, or ::1, the lobby defaults to **Classic** while React work is paused. Select **React prototype (paused)** explicitly to try this screen. With the prototype selected, create, join, or rejoin a game to connect automatically. Choose **Classic** before launching to use the original screen. Other hosts retain the classic default. Build assets with `npm --prefix GrandArchiveSim/UIPrototype run build` before use. Port 3211 serves only the static demo.

The lobby passes match credentials in the URL fragment (not sent in HTTP request URLs or referrers). Keep this URL private: it grants access to your seat. Refreshing the page reconnects to the same game. Manually entered credentials still remain in memory only.
