# Twin Suns Phase 3: SHD_143 Ruthlessness ("When this unit attacks and defeats a unit: Deal 2 damage to
# the defending player's base") must hit the DEFENDING player's base — the owner of the defeated unit —
# not merely OtherPlayer. P1's Ruthlessness-equipped 4/7 attacks and defeats P3's 3/3, so P3's base takes
# 2 (P2's base is untouched). Derived from the defender's mzID via SWUMzOwner.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: LAW_124:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP3GroundArena: SOR_095:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P3G0

## EXPECT
SEATCOUNT:3
P3BASEDMG:2
P2BASEDMG:0
P3GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
