# SEC_122 Vuutun Palaa — "costs 1 resource less for each friendly Droid unit"
# SEC_122 is a Space Capital Ship, cost 9, Command aspect, unique.
# P1 controls 3 friendly Droid units:
#   - 2 × TWI_T01 Battle Droid tokens (type "Token Unit", trait Droid) in the Ground
#     arena — these PROVE that token Droid units are counted (GetField includes tokens).
#   - 1 × TWI_T01 Battle Droid token in the Space arena — proves both arenas are scanned.
# Discount = 3 × 1 = 3.  Effective cost = 9 - 3 = 6.
# P1 starts with exactly 6 ready resources → can afford SEC_122 at discounted cost.
# If tokens weren't counted (or only one arena checked), the cost would be 8 or 9 and
# the play would fail (not enough resources). Assertion P1RESAVAILABLE:0 confirms 6 paid.
#
# Implementation note: SWU uses traits (HasTrait / $traitData) for "Droid", not subtypes
# (CardSubtypes always returns '' in SWUSim). The modifier uses GetField + HasTrait.
#
# Aspect: SEC_122 is Command-only. Leader = SOR_007 Tarkin (Command+Villainy), base =
# SOR_024 Echo Base (Command). No aspect penalty.
#
# NOTE: SEC_122 is NOT a Droid (traits: Separatist,Vehicle,Capital Ship) and is in hand
# during the cost calculation, so it never counts itself.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6:SOR_095
WithP1Hand: SEC_122
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01
WithP1SpaceArena: TWI_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SEC_122
P1RESAVAILABLE:0
P1HANDCOUNT:0
