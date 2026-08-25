# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT and will load as "Step 0 / 0" against an empty board.
# This checks the LOBBY ROOM SCREEN (MainMenu.php), which is not a gamestate and cannot be built by
# the schema editor. Every other file in Tests/Visual/ IS a loadable board schema; these two are the
# exception. Drive them from the room UI (or by calling renderRoomRoster directly in the console).
#
# VISUAL CHECK — Team Suns lobby: the four-seat table
#
#   renderRoomRoster() in SharedUI/Sites/SWUSim/MainMenu.php. Team rooms render a TABLE (Red = seats
#   1,3 / Blue = seats 2,4); every other room renders the original flat list.
#
#   ⚠ ONE FUNCTION, TWO FORMATS — this is the whole risk. In Twin Suns `team` and `seat` are BOTH null
#   on every row, so the flat fallback must still list everyone. The original bug was matching rows on
#   `.seat` (null in Twin Suns) instead of `.playerID`, which listed four seated players as "waiting…".
#   (TeamSuns_LobbyRoster.md briefly existed as retroactive cover for the PRE-TABLE rendering; it was
#   deleted once this file superseded it — it described a flat-list format that no longer ships, and two
#   fixtures for one function drift apart. Everything it guaranteed is asserted here, against current code.)
#
#   Joining picks a TEAM, not a seat — the server assigns the lowest free seat of that team's pair
#   (SWURoomAssignTeam). The button is therefore per-team even though it is drawn inside a seat box.

## HOW TO RUN

Live: four bot accounts (claudebot1..4 / pass) through the Phase 1 lobby flow at
    http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php

Render-only: call renderRoomRoster(myPlayerID, payload) in the console with a hand-built payload. That
is how this was verified — driving the function EXTRACTED FROM THE SHIPPED FILE so the check cannot
drift from the code.

## WHAT TO LOOK AT

### 1. TEAM SUNS — full room (2/2 + 2/2)
  • Heading reads "Team Suns Room" (not "Twin Suns Room").
  • Two columns: "Red (2/2)" and "Blue (2/2)", red/blue accented.
  • Four seat boxes labelled seat 1..4; Red holds 1 and 3, Blue holds 2 and 4.
  • Each occupied box: "P1 (host) (you)", that seat's TWO LEADERS, and deck ✓ / deck missing-invalid.
    The leaders are what make a within-team conflict visible BEFORE anyone presses Start.
  • A full room offers NO Join buttons anywhere.

### 2. TEAM SUNS — partial room
  • An empty slot on a team with room shows a "Join Red" / "Join Blue" button.
  • A player already on a team is NOT offered a button for their OWN team (nothing to do), but IS still
    offered the other team, so switching works.
  • A joined-but-unpicked player appears in the holding row: "Not on a team yet: P2 (you)".
  • Team headers show live counts — "Red (1/2)".

### 3. BLOCKERS DRIVE START
  • A within-team leader conflict disables Start and the hint names the leader:
    "Team Red: two players have the same leader (SEC_002)."
  • A NON-HOST sees Start hidden and the hint "Waiting for host to start." — never the blocker text.

### 4. TWIN SUNS FALLBACK — must not regress
  • Heading reverts to "Twin Suns Room".
  • NO team table, NO Join buttons, NO team headers.
  • ⚠ ALL occupied seats render as flat rows despite seat/team being null. If they read "waiting…",
    the playerID/seat match has been broken again.
  • Only the genuinely empty slot says "waiting…".
  • Start enabled when blockers is an empty array.

## VERIFIED 2026-08-25
  24 assertions (every bullet above), driven against the shipped renderRoomRoster:
    chromium — 24/24
    firefox  — 24/24
    webkit   — NOT RUN; hangs at launch on this machine under Playwright.
  ⚠ TWO-ENGINE coverage, not three.
