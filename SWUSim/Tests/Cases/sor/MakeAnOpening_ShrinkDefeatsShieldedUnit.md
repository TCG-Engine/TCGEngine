# SOR_076 Make an Opening — the shrink is NOT damage: it lowers HP directly.
# A 2/2 unit dropped to 0 HP is defeated as a state-based effect — and a Shield
# token does NOT save it, because shields only prevent damage, not HP reduction.
# Target Leia (SOR_189, 2/2) carrying a Shield → −2/−2 → 0 HP → defeated.
# The shield token is set aside (not discarded); only the unit hits the discard.
# Base heal still applies (3 → 1).

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3;myResources:3;handCardIds:SOR_076}
WithP2GroundArena: SOR_189:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1BASEDMG:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
