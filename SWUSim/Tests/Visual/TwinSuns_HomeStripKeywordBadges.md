# VISUAL CHECK — keyword badges + overlays on the Twin Suns / Team Suns HOME view mini-boards
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then run the single WHEN step.
#
# WHY THIS FILE EXISTS. A home-strip thumbnail is a background-image SPAN, not a Card() render, so
# NOTHING the schema declares under `Counters:` / `Overlay:` reaches it — the badges and overlays are
# emitted explicitly by swuMbUnitBadges()/swuMbUnitOverlays() in GameLayoutShared.php. That makes them
# invisible to every server-side assertion: the regression suite can prove a unit HAS Raid and still
# tell you nothing about whether the preview drew it. This board is the only evidence.
#
# ⚠ The badge FLAGS need no new server data — they are the arena zones' schema Virtuals and are already
#   on the wire for every seat, 3 and 4 included. If a badge goes missing, suspect the client renderer
#   (or swuReadSeatBlock dropping the field), not the payload.
# ⚠ Twin Suns and Team Suns are ONE code path here: swuRenderMiniBoard renders any seat, and the Team
#   Suns home view deliberately keeps the teammate's tile. Checking this board covers both.
# ⚠ MOBILE IS NOT COVERED and cannot be: the mobile home view (swuRenderSeatRow) has no unit
#   thumbnails at all — it is a counts-only scoreboard by deliberate design — so there is nothing to
#   hang a per-unit badge on. Don't "fix" that here.
#
# Seat 2 is the busy seat; seat 3 exists to hold the Coordinate NEGATIVE.
#   P2 ground  TS26_21 Gar Saxon        -> FOUR badges (Overwhelm, Hidden, Raid, Restore)
#   P2 ground  SOR_229 Cell Block Guard -> SENTINEL: the tech-wall OVERLAY, and NO badge (Sentinel is
#                                          an Overlay in the schema, not a Counter)
#   P2 ground  TWI_106 Coruscant Guard  -> Coordinate ACTIVE (this seat controls 3+ units)
#   P2 ground  SOR_143 Fighters/Freedom -> Saboteur
#   P2 ground  SHD_027 Hylobon Enforcer -> Grit + Bounty
#   P2 ground  SOR_198 Han Solo         -> Ambush
#   P2 ground  SOR_095 Battlefield Marine -> NO badges, NO overlay  (the negative control)
#   P2 space   LOF_088 Eye of Sion      -> FOUR badges (Overwhelm, Hidden, Ambush, Restore)
#   P3 ground  TWI_106 Coruscant Guard  -> Coordinate INACTIVE (this seat controls only 1 unit)
#
# The WHEN plays LOF_107 Village Tender from P2's hand. It has to be PLAYED, not placed: the smoke
# overlay keys off HiddenUnattackable, which is only set for a unit that entered play this phase — a
# unit dropped straight into the arena by a WithP2GroundArena line has it 0 and draws no smoke.
#
# What to look at (P1 is the viewer; the two opponent tiles are the home strips):
#   • Gar Saxon and Eye of Sion each show FOUR icons that SHRINK to stay inside their own thumbnail —
#     they must not spill into the neighbouring card in the arena grid. A one- or two-badge unit keeps
#     the larger icon size; only a crowded unit shrinks.
#   • Cell Block Guard wears the tech wall. Judge it by the TOP: the wall's opening should line up with
#     the card's art window and its top bar should clear the title, which is readable underneath. (The
#     schema's own offset does NOT produce this — tech-wall.webp is square and the full board draws it
#     over a PORTRAIT card, so its numbers land differently on a square thumbnail.) It also must not
#     bleed down into the second grid row; it is positioned with background-position rather than a
#     transform precisely so it cannot — see the CSS warning.
#   • Village Tender, after the WHEN, is wrapped in smoke AND still shows its Hidden + Restore badges
#     on top of it.
#   • The two Coruscant Guards show DIFFERENT Coordinate icons — animated on P2, static on P3. They are
#     mutually exclusive; a unit never shows both.
#   • Battlefield Marine shows nothing at all. Without it, a renderer that badged every unit would look
#     correct here.
#   • Badge ORDER matches the schema's Counters: block, so it reads the same as the full board you get
#     from "Zoom in".
#   • PROPORTIONS are the full board's own, measured off a live board: each badge is 0.221 of the
#     card's width and HANGS 0.13 of it below the card's bottom edge, so a bit over half the icon sits
#     off the card. That overhang is load-bearing to check, because the arena grid CLIPS: `overflow-x:
#     auto` forces overflow-y to a non-visible value, so the row's padding-bottom is the only thing
#     keeping the BOTTOM row's badges whole, and the row-gap is the only thing stopping a badge landing
#     on the card beneath it. Look at both rows, not just the first.

## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithP2Resources: 9
WithP2Hand: [LOF_107]
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: TS26_21:1:0
WithP2GroundArena: SOR_229:1:0
WithP2GroundArena: TWI_106:1:0
WithP2GroundArena: SOR_143:1:0
WithP2GroundArena: SHD_027:1:0
WithP2GroundArena: SOR_198:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: LOF_088:1:0
WithP3GroundArena: TWI_106:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
SEATCOUNT:3
P2GROUNDARENACOUNT:8
P2SPACEARENACOUNT:1
P3GROUNDARENACOUNT:1
