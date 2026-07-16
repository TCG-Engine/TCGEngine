# RemovesSentinel
#// SOR_140 SpecForce Soldier (2/2) — When Played: a unit loses Sentinel for this
#// phase. P2's Echo Base Defender (SOR_098, Sentinel, 4/3) is the only unit with
#// Sentinel → auto-targeted and loses it. P1's Battlefield Marine can then attack
#// P2's base directly (3 damage) — which the Sentinel would otherwise have blocked.

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;handCardIds:SOR_140}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # attacker (3/3), index 0
WithP2GroundArena: SOR_098:1:0    # Echo Base Defender (Sentinel, 4/3)

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1
