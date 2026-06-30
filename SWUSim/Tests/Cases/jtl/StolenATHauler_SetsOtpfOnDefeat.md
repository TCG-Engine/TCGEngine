# Stolen AT-Hauler: When Defeated sets OTPF on discard entry
# JTL_221 (Stolen AT-Hauler, 3/5) starts with 3 pre-existing damage.
# SOR_237 (Alliance X-Wing, 3/2) attacks it — power 3 is enough to kill it (needs 2 more).
# JTL_221 attacks back: power 3 >= SOR_237 HP 2, so SOR_237 also dies.
# After defeat, P1's discard entry for JTL_221 should have Modifier:OTPF.

## GIVEN
CommonSetup: grw/grw
WithP1SpaceArena: JTL_221:1:3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_221
P1DISCARDUNIT:0:MODIFIER:OTPF
