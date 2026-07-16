# CreditIfUnderworld
#// LAW_134 Bib Fortuna (Command,Villainy, cost 2) — When Played: if you control another Underworld unit,
#// create a Credit token. P1 controls LAW_124 (Underworld) -> 1 Credit.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_134

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
