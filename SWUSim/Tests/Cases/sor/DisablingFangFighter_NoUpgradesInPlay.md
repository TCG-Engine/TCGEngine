# SOR_162 Disabling Fang Fighter — NoUpgradesInPlay
# No upgrades anywhere → "You may" silently skips, no YESNO presented.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1NODECISION
