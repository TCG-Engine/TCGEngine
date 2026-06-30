# SOR_085 Rukh via SOR_022 Energy Conversion Lab (Epic Action: play a ≤6-cost unit with Ambush).
# P1 plays Rukh from hand with Ambush; Rukh ambush-attacks the enemy 3/7 and his "deals combat
# damage to a non-leader unit → defeat it" finishes it off. (Rukh enters with two entry triggers —
# Shielded + Ambush — so the trigger-order MZCHOOSE is answered first; the shield absorbs the 3
# counter-damage, so Rukh ends undamaged.)

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_085
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_085
P1GROUNDARENAUNIT:0:DAMAGE:0
