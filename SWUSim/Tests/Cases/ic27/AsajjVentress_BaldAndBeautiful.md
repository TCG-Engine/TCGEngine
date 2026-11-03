# Front_AttackThenReadyAResource
#// IC27_007 Asajj Ventress (Leader, Cunning/Villainy; deployed 5/6) — FRONT: "Action [Exhaust]: Attack with a unit.
#// After completing this attack, if the attacker survived, you may ready a resource." DEPLOYED: "Saboteur. On Attack:
#// You may ready a resource." Epic Action: deploy at 6+ resources (generic, not tested here).
#// SEC_080 Imperial Dark Trooper (3/3) is the only ready unit and P2 has none, so it attacks the base for 3; it
#// survives, and P1 readies ONE of two exhausted resources.
#// "ready a resource" = one of YOUR resources (the engine-wide reading of the unqualified wording — an owner ruling on
#// it is open; see the Oct bugs pass). With none exhausted the offer is skipped (no pointless prompt).
#// COVERAGE (front): offer=Front_OffersEveryReadyFriendlyUnit · decline=Front_Decline · quantity=this section (2
#//           exhausted → 1 readied) · negative=Front_AttackerDefeated_NoReady + Front_OnlyThisAttack_NotLaterOnes ·
#//           duration=Front_TheSameUnitsNextAttack_NoReady (the marker lasts the attack, not the phase) ·
#//           survive-and-defeat=Front_AttackerSurvivesAUnitFight · no target=Front_NoLegalAttacker_StillExhausts +
#//           Front_NoExhaustedResource_NoPrompt · control=Front_StolenUnitAttacks_YourResourceReadies ·
#//           reqboundary=Front_AcrossARequestBoundary · modes=2P only ("you" / "your" only)
#// COVERAGE (deployed): positive=Deployed_OnAttack_ReadyAResource · decline=Deployed_Decline ·
#//           no target=Deployed_NoExhaustedResource_NoPrompt · negative=Deployed_AnotherUnitsAttack_NoReady ·
#//           Saboteur=Deployed_Saboteur_IgnoresSentinel + Deployed_Saboteur_DefeatsShields

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
P1NODECISION

---

# Front_Decline

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:0
P1NODECISION

---

# Front_AttackerDefeated_NoReady
#// SEC_080 (3/3) attacks Consular Security Force (3/7) and takes 3: it is defeated, so nothing is offered.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1RESAVAILABLE:0
P1NODECISION

---

# Front_AttackerSurvivesAUnitFight
#// SEC_080 (3/3) attacks SHD_110 (a 2/2 ground unit): it defeats it and survives with 2 damage, so the ready is
#// offered.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:1
P1NODECISION

---

# Front_OffersEveryReadyFriendlyUnit
#// The attacker pick is left pending: both READY friendly units, ground and space, are offered; the exhausted one and
#// the enemy's unit are not.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# Front_NoLegalAttacker_StillExhausts
#// With only an exhausted unit there is no attacker, but the Action can still be used (its cost changes the game
#// state): Asajj exhausts and nothing else happens.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P2BASEDMG:0
P1RESAVAILABLE:0
P1NODECISION

---

# Front_NoExhaustedResource_NoPrompt
#// Every resource is already ready: there is nothing to ready, so the "may" is not asked.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:3
P1NODECISION

---

# Front_OnlyThisAttack_NotLaterOnes
#// The rider belongs to the Action's attack only: P1 then attacks with Battlefield Marine normally and is offered
#// nothing — the second exhausted resource stays exhausted.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:6
P1RESAVAILABLE:1
P1NODECISION

---

# Front_AcrossARequestBoundary

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:1
P1NODECISION

---

# Front_StolenUnitAttacks_YourResourceReadies
#// "a unit" to attack with is a FRIENDLY one — a unit P1 controls but P2 owns (Consular Security Force) attacks, and
#// the resource readied is P1's (the Action's controller), not the attacker's owner's.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_046:2
WithP1Resources: 2:SOR_095:0
WithP2Resources: 2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:1
P2RESAVAILABLE:0
P1NODECISION

---

# Deployed_OnAttack_ReadyAResource
#// Deployed Asajj (5/6) attacks the base: On Attack readies one of two exhausted resources, then she deals 5.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myLeaderDeployed:1}
P1OnlyActions: true
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_007
P2BASEDMG:5
P1RESAVAILABLE:1
P1NODECISION

---

# Deployed_Decline

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myLeaderDeployed:1}
P1OnlyActions: true
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:5
P1RESAVAILABLE:0
P1NODECISION

---

# Deployed_NoExhaustedResource_NoPrompt

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myLeaderDeployed:1;myResources:3}
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1RESAVAILABLE:3
P1NODECISION

---

# Deployed_AnotherUnitsAttack_NoReady
#// "On Attack" is Asajj's own: deployed Asajj in play, Imperial Dark Trooper attacks — nothing is offered. (Fixture order:
#// the Trooper is slot 0, the deployed leader slot 1.)

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myLeaderDeployed:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_095:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:CARDID:IC27_007
P1RESAVAILABLE:0
P1NODECISION

---

# Deployed_Saboteur_IgnoresSentinel
#// P2's SOR_229 is a Sentinel; Saboteur lets Asajj attack the base anyway.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myLeaderDeployed:1;myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_229:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_Saboteur_DefeatsShields
#// Saboteur defeats the defender's Shield before damage: Consular Security Force (3/7) with a Shield takes the full 5.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007;myLeaderDeployed:1;myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# Front_TheSameUnitsNextAttack_NoReady
#// The rider lasts for the Action's attack only, not the phase: after it, SOR_169 Keep Fighting (2 + 2 off-aspect =
#// 4) readies Imperial Dark Trooper and it attacks again — nothing is offered the second time.
#// Resources: 4 ready + 2 exhausted; the rider readies one (5 ready), Keep Fighting spends 4 → 1 ready.

## GIVEN
CommonSetup: yyk/bbw/{myLeader:IC27_007}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_169
WithP1Resources: 4:SOR_095:1,2:SOR_095:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:1
P1NODECISION
