# SOR_010 Darth Vader — Leader Action: Villainy card played → deal 1 to unit + 1 to base.
# SOR_128 (Death Star Stormtrooper) is Villainy, cost 1.

## GIVEN
CommonSetup: rrk/grw/{myResources:2;handCardIds:SOR_128}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:1
P1LEADER:EXHAUSTED
P1RESCOUNT:2
P1RESAVAILABLE:0
