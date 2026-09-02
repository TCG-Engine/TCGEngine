# Deployed_RaidAndAttackAnother
#// COVERAGE: offer=LeaderAction_Offer_ReadyRebelsOnly +
#//           LeaderAction_SecondOffer_ExcludesTheFirstAttacker
#//           decline=LeaderAction_DeclineSecond ("you may attack with another")
#//           boundary=EpicDeploy_FourResourcesIsOneTooFew / EpicDeploy_FiveResourcesIsEnough
#//           control=LeaderAction_StolenRebelIsALegalAttacker
#//           reqboundary=LeaderActionChain_SurvivesRequestBoundary
#//           modes=2P only - "a Rebel unit" is a trait pool with no controller word or player
#//           reference; the deployed Raid rider is self-scoped.
#// SOR_009 Leia Organa — Deployed: Raid 1 + "When this unit completes an attack: you may attack
#// with another Rebel unit." Deployed Leia (3/6, Rebel) attacks the base for 3+1(Raid)=4, then her
#// OnAttackEnd lets a second Rebel attack the base for 3 → 7 total base damage.
#// COVERAGE (leader — both sides): FRONT = the chained [Exhaust] action + the Epic Action;
#//           DEPLOYED = Raid 1 + the optional "attack with another Rebel" rider.
#//           offer=LeaderAction_Offer_ReadyRebelsOnly (first stage, pending SELECTABLEEXACT: ready
#//           Rebels only — an exhausted Rebel and a ready non-Rebel are both on the board as the
#//           excluded bodies) + LeaderAction_SecondOffer_ExcludesTheFirstAttacker (second stage,
#//           three Rebels so the pool stays plural after the first attack) ·
#//           decline=LeaderAction_DeclineSecond (the "you may" second attack) ·
#//           boundary=EpicDeploy_FourResourcesIsOneTooFew / EpicDeploy_FiveResourcesIsEnough (4 vs 5
#//           resources) + Deployed_Raid1_AppliesWhileAttackingNotWhileDefending vs
#//           Deployed_RaidAndAttackAnother (+1 attacking, +0 defending) ·
#//           control=LeaderAction_StolenRebelIsALegalAttacker ("a Rebel unit" is read from the
#//           CONTROLLER, so an opponent-OWNED Rebel can be sent in) ·
#//           reqboundary=LeaderActionChain_SurvivesRequestBoundary

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:DEPLOYED

---

# LeaderAction_AttackTwoRebels
#// SOR_009 Leia Organa — Leader Action [Exhaust]: Attack with a Rebel unit. Then, you may attack
#// with another Rebel unit. P1 has two Rebels; both attack the base (opponent has only a base) for
#// 3 each → 6 total.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1LEADER:EXHAUSTED

---

# LeaderAction_DeclineSecond
#// SOR_009 Leia Organa — the second attack is optional ("you may"). Declining it leaves only the
#// first Rebel's attack: base takes 3, the second Rebel is untouched and stays ready.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:READY
P1LEADER:EXHAUSTED

---

# LeaderAction_MultiTarget_ChooseAttackTargets
#// SOR_009 Leia Organa — leader action with the opponent holding a UNIT (not just a base), so each
#// attack chooses its target. First Rebel (3/7) attacks the enemy 3/1 (a real MZCHOOSE between the
#// unit and the base) and defeats it; the chained second Rebel then attacks the base (the only target
#// left) for 3.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# LeaderActionChain_SurvivesRequestBoundary
#// SOR_009 Leia's Leader Action is a GENUINE chained action (attack with a Rebel, THEN may attack with
#// another) — its single After Action is owned by the leader-action path, NOT by the event/Support extra-
#// action flags (both are inert here: no FINISH_PLAY_CARD, no SUPPORT_GRANT). Verify the chain still resolves
#// correctly when every interactive decision crosses a request boundary: first Rebel (3/7) attacks the enemy
#// 3/1 (a real target choice) and defeats it; the chained second Rebel then attacks the base for 3.
## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# LeaderAction_Offer_ReadyRebelsOnly
#// SOR_009 Leia Organa — FRONT side, OFFER axis. "Attack with a REBEL unit" builds a pool of friendly
#// Rebels that are actually able to attack. Four friendly bodies: two ready Rebels (SOR_095 idx 0,
#// SOR_046 idx 1) that must be offered, an EXHAUSTED Rebel (LAW_180 idx 2) and a ready NON-Rebel
#// (SEC_080, Imperial/Droid/Trooper, idx 3) that must not. Two legal attackers keep the pick genuinely
#// pending, so the offer rather than the outcome is the assertion.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: LAW_180:0:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P2BASEDMG:0

---

# LeaderAction_SecondOffer_ExcludesTheFirstAttacker
#// SOR_009 Leia Organa — FRONT side, the second stage's OFFER. "Then, you may attack with ANOTHER
#// Rebel unit": the first attacker must be out of the second pool. Three ready Rebels are seeded so
#// that after the first one attacks there are still TWO candidates left — without that the second
#// offer would collapse to a single auto-resolving target and prove nothing. The first attacker
#// (idx 0) has hit the base for 3 and is absent from the pending second offer.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: LAW_180:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EpicDeploy_FourResourcesIsOneTooFew
#// SOR_009 Leia Organa — "Epic Action: If you control 5 OR MORE resources, deploy this leader."
#// Boundary pair, low side: at exactly 4 the deploy is refused, the leader stays on its front side
#// with the Epic Action available, the arena stays empty and nothing is spent.

## GIVEN
CommonSetup: ggw/brw/{
  myResources:4;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4

---

# EpicDeploy_FiveResourcesIsEnough
#// SOR_009 Leia Organa — boundary pair, high side. One more resource and the same command flips her
#// into the ground arena and spends the Epic Action; all 5 resources stay ready, because "control 5 or
#// more" is a condition rather than a payment.

## GIVEN
CommonSetup: ggw/brw/{
  myResources:5;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1RESAVAILABLE:5

---

# Deployed_Raid1_AppliesWhileAttackingNotWhileDefending
#// SOR_009 Leia Organa — DEPLOYED side, boundary on the Raid 1 half. Raid is "+1/+0 WHILE ATTACKING",
#// so when the deployed Leia (3/6) is the DEFENDER she deals only her printed 3 back, not 4: the 3/7
#// attacking her ends at exactly 3 damage. The attacking side of the same boundary is
#// Deployed_RaidAndAttackAnother above, where she hits the base for 4. Her "when this unit completes
#// an attack" rider is likewise silent on defense — no second attack is offered.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_009:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# LeaderAction_StolenRebelIsALegalAttacker
#// SOR_009 Leia Organa — CONTROL CHANGE. "Attack with a Rebel unit" reads from the CONTROLLER: a
#// Rebel sitting in P1's arena but OWNED by P2 is a legal attacker for the leader action. It is picked
#// explicitly (an answer outside the offered pool is rejected, so the pick proves membership), attacks
#// the base for 3 and ends exhausted, while the genuinely-owned Rebel beside it is left ready because
#// the optional second attack is declined. Controlled units seat after the plain ones, so the arena is
#// [SOR_046, the stolen SOR_095].

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaControlled: SOR_095:2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
