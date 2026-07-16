# UseForce_HealBase
#// LOF_048 Itinerant Warrior (4/4) — Shielded + When Played: you may use the Force → heal 3 damage from a
#// base. Two entry triggers (Shielded + WhenPlayed): resolve the WhenPlayed (EffectStack-0) first, use the
#// Force, heal P1's own base (5 → 2). The Shield token lands on the Warrior.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:LOF_048;myBaseDamage:5}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P1NOFORCE
P1BASEDMG:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
