# WhenDefeated_Deal1
#// SHD_164 Rhokai Gunship (2-cost 2/1 space) — "When Defeated: Deal 1 damage to a unit or base." Rhokai
#// attacks SOR_237 (2/3) and dies to the counter (self-defeat resolves the When Defeated inline in P1's
#// action); P1 then deals the 1 damage to P2's base.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: SHD_164:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:1
