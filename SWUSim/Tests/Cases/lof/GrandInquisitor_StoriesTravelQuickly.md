# AttackDefenderDebuff
#// LOF_014 Grand Inquisitor — Action [Exhaust, use the Force]: Attack with a friendly unit. The defender
#// gets -2/-0 for this attack. Plo Koon (6) attacks SOR_046 (3/7): SOR_046 takes 6, its counter is reduced
#// from 3 to 1, so Plo Koon takes only 1.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NOFORCE

---

# DeployedOnAttack
#// LOF_014 Grand Inquisitor (deployed) — On Attack: the defender gets -2/-0 for this attack (applied
#// synchronously in ExecuteSWUAttack, mirroring SOR_212). He deploys with a Shield (his Shielded), attacks
#// SOR_046 for 3, and the Shield absorbs the reduced counter so he takes 0. (The -2/-0 itself is masked by
#// the innate Shield here; it's verified directly by the leader-side LOF_014 and SOR_212 tests.)

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0
