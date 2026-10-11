# VISUAL CHECK — Twin Suns HOME view: an UPGRADE/TOKEN target must be pickable from a preview tile
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, stay on the HOME view, then play Sabine from P1's hand.
# It is also the board the DevTools/ui-harness/swusim-preview-subcard-xbrowser.mjs script drives.
#
# WHY THIS EXISTS — bug #1068 (game 1105765, 4-seat Twin Suns)
# "could not select shield to defeat it with Sabine ability … the When Played shows Invalid Selection
# when they try to pick a unit on the home preview."
#
# LAW_078 Sabine Wren's When Played is "you may defeat an upgrade", which the engine offers as SUBCARD
# mzIDs ("p3GroundArena-0.u0" — the upgrade, addressed on its host). The full board renders those as a
# clickable sliver under the unit. The HOME view does not render a far seat's board at all: it draws a
# preview TILE, whose attached cards are a count badge opening a read-only panel. swuHighlightPreviewTargets
# ignored spec.subIndex, so the HOST unit lit up as if it were the target, and swuPreviewTargetClick
# submitted the bare host mzID — which SWUValidateDecisionAnswer refuses, giving the reporter
# "Invalid selection." on every attempt. The tile was the ONLY rendering of that Shield, so the ability
# was unplayable from the view the game defaults to.
#
# THE BOARD — a 4-seat Twin Suns game, P1 (you) to act, holding Sabine. THREE upgrades exist, on three
# different seats, so nothing auto-resolves and the offered/not-offered distinction is visible:
#   • P3's unit carries a SHIELD token (SOR_T02)      → offered, and reachable ONLY from its tile
#   • P4's unit carries an EXPERIENCE token (SOR_T01) → offered, also tile-only
#   • P1's own unit carries a Shield                  → offered, and rendered on your own half of the board
# P2's unit is deliberately BARE: its tile must stay dark, which is the negative control for the
# "highlight only units carrying a legal upgrade" rule.
# P3's unit also holds a CAPTIVE, so the split badges (upgrades vs captives) are visible on one tile.
#
# WHAT TO LOOK AT — play LAW_078 and stay on Home:
#   • P3's and P4's tiles glow with a DASHED green ring (.mini-subcard-host) — "there is something here
#     to pick", as opposed to the SOLID ring a unit-level target takes. Your own unit's Shield glows in
#     place on the board as a sliver, exactly as before this change.
#   • ⚠ P2's tile does NOT glow. A unit that merely HAS upgrades must not glow, and a unit with none
#     certainly must not — a glow that opens an empty panel is the same broken promise in a new place.
#   • P3's tile carries TWO badges: a WHITE PILL (upgrades) and, under it, a GOLDENROD CIRCLE (captives).
#     The pill counts the Shield only — a captive must never inflate the upgrade count. The circle is
#     the same goldenrod as the base tile's ARRESTED chip and the full board's ARRESTED tab.
#   • Click P3's tile. The "Attached Upgrades" panel opens (this is the fix: the click no longer submits
#     the unit). The Shield entry has a green ring and a pointer cursor; hovering blows it up as usual.
#   • Click the Shield IN THE PANEL. The panel closes, the Shield is defeated on P3's unit, and the
#     upgrade pill on that tile disappears. NO "Invalid selection." toast — that toast is the bug.
#   • Undo / reload and repeat with P4's Experience token, to prove the address is per-seat and not
#     hardcoded to one tile.
#   • Open P3's GOLDENROD badge instead: the "Captured Units" panel lists the captive. Under Sabine's
#     offer its entry is NOT ringed and does not respond to a click — a captive is not an upgrade.
#   • Repeat once from a matchup view (Zoom In on P3): the Shield must still be pickable as a SLIVER on
#     the real board. This change must not have moved the on-board path.
#   • With no decision active, click any tile's badge: the panel must open READ-ONLY — no rings, no
#     pointer, nothing clickable. The panel is an information view the rest of the time.
#   • Cross-browser: Chromium, Firefox AND WebKit (repo rule). The panel's dimming of non-offered
#     entries uses :has(), which is the one selector here with a real engine floor — if it no-ops the
#     dimming is simply absent, which is a cosmetic downgrade, not a broken pick.

## GIVEN
#// Twin Suns decks run TWO leaders sharing a force-side.
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myResources:6}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Resources: 6

#// Sabine LAW_078 — "When Played: You may defeat a non-unique upgrade. If you control a Vigilance or
#// Command unit, you may defeat an upgrade instead." SOR_095 is the Vigilance body that widens it to
#// ANY upgrade, so the offer is not silently narrowed while reading the board.
WithP1Hand: [LAW_078]
WithP1GroundArena: [SOR_095:1:0]
WithP1GroundArenaUpgrade: [0:SOR_T02]

#// P2 — BARE. The negative control: this tile must never glow.
WithP2GroundArena: [SOR_046:1:0]

#// P3 — the reported shape: an upgrade that exists NOWHERE but in a preview tile, plus a captive, so
#// the split badges are on the same card.
WithP3GroundArena: [SOR_050:1:0]
WithP3GroundArenaUpgrade: [0:SOR_T02]
WithP3GroundArenaCaptive: [0:SOR_046]
WithP3Base: SOR_026:0
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015

#// P4 — a second tile-only upgrade, a different token, to prove the address is per-seat.
WithP4GroundArena: [SOR_108:1:0]
WithP4GroundArenaUpgrade: [0:SOR_T01]
WithP4Base: SOR_026:0
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN
#// Play Sabine, then order her two entry triggers — she has BOTH Ambush and a When Played, so the
#// first decision is the trigger-order pick, not the upgrade offer. Resolving the When Played first
#// leaves the SUBCARD offer pending, which is the state to look at.
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
#// Shape only — the point of this file is the pixels, but a wrong board must not pass for a wrong fix.
SEATCOUNT:4
P1GROUNDARENACOUNT:2
P3GROUNDARENAUNIT:0:UPGRADECOUNT:2
P4GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
