# SEC_194 Fully Armed and Operational (Event, cost 1, Cunning/Villainy, Trick, Plot)
#   "If an opponent attacked your base during their previous action this phase, play a unit from your
#    hand. Give it Ambush for this phase."
# P2's space unit (SOR_237) attacks P1's base for 2 (P2's previous action = a base attack). P1 then plays
# SEC_194: the condition is met, so P1 plays SOR_095 from hand, and it enters with Ambush granted for the
# phase. P2 has no GROUND unit, so the Ambush attack has no legal target and the unit simply enters.
# (HASKEYWORD:Ambush on a vanilla SOR_095 proves the grant.)

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1DISCARDCOUNT:1
