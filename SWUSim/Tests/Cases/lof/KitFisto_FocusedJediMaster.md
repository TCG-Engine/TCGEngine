# Deployed_Passive_PerJedi
#// LOF_011 Kit Fisto (deployed, 1/6) — passive: gets +1/+0 for each OTHER friendly Jedi unit. With
#// two other Jedi (LOF_230, LOF_093) → power 3.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:LOF_011:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_230:1:0
WithP1GroundArena: LOF_093:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:2:POWER:3

---

# JediAttackDeal2
#// LOF_011 Kit Fisto — Action [1 resource, Exhaust]: If you attacked with a Jedi unit this phase, deal 2
#// damage to a unit. Plo Koon (a Jedi) attacks first; then the leader deals 2 to SOR_059.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_011;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# NoJediAttack_NoEffect
#// LOF_011 Kit Fisto (front) — the Action's "deal 2 damage" is gated on having attacked with a Jedi this
#// phase. P1 attacks only with a NON-Jedi (SOR_046 Battlefield Marine) and then activates Kit Fisto: the
#// condition is unmet so no damage is dealt, but the ability still resolves — Kit Fisto exhausts and the
#// 1-resource cost is paid. Ref: "should not be able to deal 2 damage ... hasn't attacked with a Jedi".

## GIVEN
CommonSetup: brw/bbk/{myLeader:LOF_011;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
