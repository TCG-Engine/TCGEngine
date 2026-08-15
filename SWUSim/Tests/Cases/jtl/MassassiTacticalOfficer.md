# Action_FighterAttack
#// JTL_146 Massassi Tactical Officer — Action [Exhaust]: Attack with a Fighter unit (+2/+0). The Fighter
#// SOR_237 (power 2) gets +2 → 4 and hits the enemy base for 4; the officer is exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_146:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# SimulateRequestBoundary_AttackBuffSurvivesTargetChoice
#// JTL_146 Massassi Tactical Officer — the +2/+0 "for this attack" is granted when the ability resolves,
#// but with an enemy unit in the Fighter's arena the ATTACK TARGET is an interactive decision
#// (theirBase-0 / theirSpaceArena-0). In production that decision ends the request, so the in-flight
#// attack and its +2/+0 rider must be reconstructed from the serialized gamestate — otherwise the
#// Fighter would land its printed 2 instead of 4. Mirrors Action_FighterAttack with a second enemy
#// space unit (to keep the target choice interactive) and a request boundary before the answer.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_146:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENAUNIT:0:DAMAGE:0
