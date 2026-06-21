# LOF_231 Darth Tyranus — "Shielded. While the Force is with you, this unit gains Ambush."
# Integration: a Force unit (LOF_112) attacks the enemy base; Fortress Vader (LOF_026) creates P1's
# Force token via "When a friendly Force unit attacks." P1 then plays Tyranus from hand — because the
# Force is now with P1 he has BOTH entry keywords (Shielded + Ambush) → two entry triggers. P1 resolves
# Shielded first (EffectStack-0), then takes the Ambush attack into Consular Security Force (SOR_046, 3/7).
# Tyranus (4 power) deals 4 to SOR_046 (survives, 4 damage); SOR_046's 3 counter is absorbed by the
# shield (shield consumed → Tyranus ends undamaged, 0 shields). LOF_112 (2 power) dealt 2 to P2's base.

## GIVEN
P1LeaderBase: SOR_002/LOF_026
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_112:1:0
WithP1Hand: LOF_231
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES

## EXPECT
P1HASFORCE
P2BASEDMG:2
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_231
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
