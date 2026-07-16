# DeployedOnAttack
#// LOF_015 Cal Kestis (deployed) — On Attack: an opponent chooses a ready unit they control; exhaust it. He
#// attacks the base; P2 picks SOR_046 from its two ready units to be exhausted.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# OpponentExhaustsUnit
#// LOF_015 Cal Kestis — Action [Exhaust, use the Force]: An opponent chooses a ready unit they control;
#// exhaust that unit. P1 uses the Force; P2 chooses SOR_046 (from its two ready units) to be exhausted.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P1NOFORCE
