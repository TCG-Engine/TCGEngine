# ASH_196 Gorian Shard's Corsair (Underworld) — "Damage dealt by friendly Underworld cards is
# unpreventable." With ASH_196 in play (space), a friendly Underworld GROUND unit (SOR_247, 2 power)
# attacks a Shielded SOR_095: the Shield does NOT absorb the hit — SOR_095 takes the full 2 and the Shield
# token remains (it was bypassed, not consumed).
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
