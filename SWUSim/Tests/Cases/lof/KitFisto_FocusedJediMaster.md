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
