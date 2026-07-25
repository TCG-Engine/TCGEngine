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

---

# Leader_NoForce_Unavailable
#// LOF_014 Grand Inquisitor (front) — the Action requires the Force. Without a Force token it is unavailable
#// and UseLeaderAbility is a no-op: the leader stays READY and no attack happens. Ref: "cannot be used
#// without the Force".

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NOFORCE
P1NODECISION

---

# Leader_Exhausted_Unavailable
#// LOF_014 Grand Inquisitor (front) — the Action costs "Exhaust this leader". Already exhausted
#// (myLeader:LOF_014:0), the cost can't be paid, so even holding the Force the Action is unavailable: no-op,
#// the Force is retained. Ref: "cannot be used if exhausted".

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_014:0;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1HASFORCE
P1NODECISION

---

# Deployed_ReducedCombatDamage_Unmasked
#// LOF_014 Grand Inquisitor (deployed) — On Attack: the defender gets -2/-0, verified directly (no innate
#// Shield masking it). Seeded pre-deployed (myLeader:LOF_014:1:1:1 — the Shielded "when you deploy" does not
#// re-fire), he attacks Wampa (SOR_164, 4/5): the -2/-0 drops Wampa's power 4→2, so Wampa deals only 2 back
#// to the un-shielded Grand Inquisitor while taking his 3. Ref: deployed "should give -2/-0 to the defender".

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LOF_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1HASFORCE

---

# Leader_MultiDefender_AllGetDebuff
#// LOF_014 Grand Inquisitor front — "Attack with a friendly unit. The defender gets -2/-0." When the chosen
#// attacker hits MULTIPLE units (Darth Maul TWI_135 "attack 2 units"), EACH defender gets -2/-0. Maul (5/6)
#// attacks two SOR_046 (3/7): each defender's counter is 3-2=1, so Maul takes 1+1=2 (not 6). Each SOR_046
#// takes Maul's 5.
## GIVEN
CommonSetup: yyk/bbk/{myLeader:LOF_014;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Resources: 4
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:5
