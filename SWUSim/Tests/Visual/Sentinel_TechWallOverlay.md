# VISUAL CHECK — Sentinel tech-wall overlay
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor.
#
# Units with the Sentinel keyword show a full-card tech-wall overlay
# (Assets/Overlays/tech-wall.webp) UNDER the power/HP/damage badges (low DrawOrder).
# Unlike the Hidden smoke overlay, this is a plain has-keyword check (server-side
# HasKeyword_Sentinel via the HasSentinel virtual), so it shows on any Sentinel unit
# — no play step needed.
#
# P1's ground arena holds:
#   SOR_229 Cell Block Guard   (Sentinel)   -> tech-wall overlay SHOWS
#   SOR_095 Battlefield Marine (none)        -> NO overlay
#
# What to look at:
#   • SOR_229 is covered by the tech-wall, with its badges still readable on top.
#   • SOR_095 shows no overlay.
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: SOR_229:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_229
