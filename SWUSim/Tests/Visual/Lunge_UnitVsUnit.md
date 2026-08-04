# VISUAL CHECK — attack lunge, unit vs unit
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Load it by hand in
# the Test Schema Editor and step the WHEN.
#
# ⚠ NOTHING AUTOMATED VERIFIES THE ANIMATIONS. The schema suite bypasses ProcessInput and
# TestSchemaStep stubs the animation functions to no-ops, so these schemas are the ONLY proof that
# card motion works. Treat a failure here as a real bug, not a flaky demo.
#
# What to look at:
#   • P1's Dark Trooper LEANS toward the enemy Consular Security Force, then snaps back.
#   • It leans ALREADY EXHAUSTED — tilted 9° AND shaded for the whole lean, because exhausting is the
#     attack's cost and must read as paid BEFORE the motion it paid for. An upright or full-brightness
#     lean that tips over / darkens once the motion ends is the bug.
#   • The shade is the same 50%-black the re-rendered exhausted card carries: there must be no visible
#     step in darkness when the lunge finishes and the real board comes back.
#   • The lunge happens BEFORE the damage numbers land.
#   • Neither card is left duplicated or displaced once the motion ends (the clone is cleaned up).

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
