# ForceFromBase_ShieldedThenAmbush
#// LOF_231 Darth Tyranus — "Shielded. While the Force is with you, this unit gains Ambush."
#// Integration: a Force unit (LOF_112) attacks the enemy base; Fortress Vader (LOF_026) creates P1's
#// Force token via "When a friendly Force unit attacks." P1 then plays Tyranus from hand — because the
#// Force is now with P1 he has BOTH entry keywords (Shielded + Ambush) → two entry triggers. P1 resolves
#// Shielded first (EffectStack-0), then takes the Ambush attack into Consular Security Force (SOR_046, 3/7).
#// Tyranus (4 power) deals 4 to SOR_046 (survives, 4 damage); SOR_046's 3 counter is absorbed by the
#// shield (shield consumed → Tyranus ends undamaged, 0 shields). LOF_112 (2 power) dealt 2 to P2's base.

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_026;
  theirBase:SOR_021
}
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

---

# NoForce_NoAmbush
#// LOF_231 Darth Tyranus — the Ambush is conditional on "While the Force is with you." With NO Force,
#// Tyranus has only his innate Shielded: playing him gives a shield and adds NO Ambush entry trigger
#// (so no attack into the enemy unit, and he does not have the Ambush keyword). Absence guard for the
#// conditional keyword grant.

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_026;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_231
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_231
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Bug976d_AmbushCombatResolvesFullyBeforeShieldedDoes
#// LOF_231 Darth Tyranus (Villainy, Force/Separatist/Sith, cost 4, 4/3 Ground) —
#// "Shielded / While the Force is with you, this unit gains Ambush."
#// The bug-#976d family guard, on the card where the interaction is DESIGNED rather than incidental:
#// LOF_021 Shadowed Undercity reads "When a friendly FORCE unit attacks: create your Force token", and
#// Tyranus is a Force unit whose Ambush is SWITCHED ON by holding the Force. So the base both enables
#// him and fires mid-combat — the exact ingredient that made Mae HMW_055's shield resolve too early.
#// CR: resolving Ambush first must finish the whole attack, damage included, before Shielded begins.
#// Tyranus (4 power) kills the 1/3 Village Tender and takes its 1 counter BARE, ending on 1 damage and
#// alive on 3 HP; only then does his Shield arrive. Before the fix the shield landed first, absorbed the
#// counter, and he ended undamaged with no token.
#// He is played from HAND here, not via Osha — the defect is in the shared trigger orchestration, so the
#// guard should not depend on any particular card having put him into play.
#// Force is pre-held so Ambush is active on entry (it is a conditional grant, checked as he enters).

## GIVEN
CommonSetup: brk/rrk/{myBase:LOF_021;myResources:6}
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_231
WithP2GroundArena: LOF_107:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_231
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:1
