# Deployed_OnAttack_DamageReadyAnother
#// COVERAGE: offer=LeaderAction_Offer_PowerThreeIsInPowerFourIsOut +
#//           Deployed_OnAttack_Offer_ExcludesHimselfAndHighPower
#//           decline=Deployed_OnAttack_Decline
#//           boundary=LeaderAction_Offer_PowerThreeIsInPowerFourIsOut (3-or-less, as a pair inside one
#//           pool assertion) + EpicDeploy_FiveResourcesIsOneTooFew / EpicDeploy_SixResourcesIsEnough
#//           control=LeaderAction_StolenUnitCountsAsFriendly
#//           reqboundary=LeaderActionPickSurvivesRequestBoundary
#//           modes=2P only - "a friendly unit" with no player reference; the friendly/enemy axis is
#//           self-scoped on both sides.
#// SOR_011 Grand Inquisitor — Deployed: On Attack you MAY deal 1 damage to another friendly
#// unit with 3 or less power and ready it. GI (idx 1) attacks the base; the only other friendly
#// (a 3/3 at idx 0, exhausted) is chosen → takes 1 damage and is readied. Base takes GI's power 3.
#// COVERAGE (leader — both sides): FRONT = the [Exhaust] action + the Epic Action; DEPLOYED = the
#//           optional On Attack.
#//           boundary=LeaderAction_Offer_PowerThreeIsInPowerFourIsOut (power 3 in / 4 out) +
#//           EpicDeploy_FiveResourcesIsOneTooFew / EpicDeploy_SixResourcesIsEnough (5 vs 6
#//           resources) + LeaderAction_KillsTarget_NoReady (damage 2 vs 1 remaining HP) ·
#//           offer=LeaderAction_Offer_PowerThreeIsInPowerFourIsOut (front, pending SELECTABLEEXACT
#//           with two eligible bodies) and Deployed_OnAttack_Offer_ExcludesHimselfAndHighPower
#//           (deployed) — ⚠ the DEPLOYED one is RED, see below ·
#//           decline=Deployed_OnAttack_Decline · control=LeaderAction_StolenUnitCountsAsFriendly
#//           ("a friendly unit" is read from the CONTROLLER, so an opponent-OWNED unit is a legal
#//           target) · reqboundary=LeaderActionPickSurvivesRequestBoundary
#// ⚠ THREE RED SECTIONS — the deployed side's "ANOTHER friendly unit" exclusion does not hold: the
#//   deployed Grand Inquisitor (3/6, so he passes the "3 or less power" half) is offered as a target
#//   for his own On Attack, and when he is P1's ONLY unit the ability still prompts instead of not
#//   being offered at all. Reproduced from a seeded already-flipped leader AND from a live Epic Action
#//   deploy, so it is not a fixture artifact. The front side's pool (which has no "another") is
#//   correct, which is the passing control.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# Deployed_OnAttack_Decline
#// SOR_011 Grand Inquisitor — Deployed: the On Attack damage-and-ready is optional ("you may").
#// Declining the MZMAYCHOOSE leaves the other friendly unit untouched (no damage, still exhausted);
#// the attack still deals its base damage.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:3
P1LEADER:DEPLOYED

---

# LeaderAction_DamageReady
#// SOR_011 Grand Inquisitor — Leader Action [Exhaust]: Deal 2 damage to a friendly unit with
#// 3 or less power and ready it. The one eligible 3/3 friendly (exhausted) takes 2 damage and
#// is readied.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED

---

# LeaderAction_KillsTarget_NoReady
#// SOR_011 Grand Inquisitor — "Deal 2 damage to a friendly unit with 3 or less power and ready it."
#// If the 2 damage DEFEATS the chosen unit (a 3/1), there's nothing left to ready — the unit is gone,
#// no crash, leader still pays its exhaust.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_180:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoEligibleTarget_Fizzle
#// SOR_011 Grand Inquisitor — Leader Action targets "a friendly unit with 3 or less power".
#// The only friendly is a 4-power unit (ineligible), so the action fizzles: the leader still pays
#// its [Exhaust] cost but no unit is damaged and no decision is queued.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# LeaderAction_Offer_PowerThreeIsInPowerFourIsOut
#// SOR_011 Grand Inquisitor — FRONT side, OFFER + boundary. "Deal 2 damage to a friendly unit with 3
#// OR LESS power and ready it." Two eligible 3-power friendlies (SEC_080 and SOR_095, both 3/3) keep
#// the pick genuinely pending, and a 4-power friendly (SOR_164 Wampa, 4/5) sits one over the line and
#// must be absent from the pool. The decision is left unanswered so the offer itself is the assertion.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENAUNIT:2:DAMAGE:0

---

# EpicDeploy_FiveResourcesIsOneTooFew
#// SOR_011 Grand Inquisitor — "Epic Action: If you control 6 OR MORE resources, deploy this leader."
#// Boundary pair, low side: at exactly 5 the deploy is refused. The leader stays on its front side with
#// the Epic Action still available, the ground arena stays empty and nothing is spent.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  myResources:5;
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
P1RESAVAILABLE:5

---

# EpicDeploy_SixResourcesIsEnough
#// SOR_011 Grand Inquisitor — boundary pair, high side. One more resource and the same command puts
#// the leader unit into the ground arena and spends the Epic Action; the 6 resources stay ready,
#// because "control 6 or more" is a condition rather than a cost.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  myResources:6;
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
P1RESAVAILABLE:6

---

# Deployed_OnAttack_Offer_ExcludesHimselfAndHighPower
#// SOR_011 Grand Inquisitor — DEPLOYED side, OFFER axis. "Deal 1 damage to ANOTHER friendly unit with
#// 3 or less power and ready it." The deployed Grand Inquisitor is himself a 3-power friendly unit, so
#// "another" is the only thing keeping him out of his own pool — the sharpest exclusion on this card.
#// Board: two eligible 3/3s, a 4-power Wampa (over the power line) and the deployed leader at idx 3.
#// The offer is exactly the two 3/3s; the decision is left pending.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:3:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENAUNIT:3:ISLEADERUNIT

---

# Deployed_OnAttack_NoOtherFriendlyUnit_NoOfferAtAll
#// SOR_011 Grand Inquisitor — DEPLOYED side, the negative that proves "another" is load-bearing. The
#// deployed leader is P1's ONLY unit and he qualifies on power (3), so a self-inclusive reading would
#// prompt. Instead the optional On Attack has no eligible recipient and is not offered at all: no
#// decision, the leader takes no self-damage, and the attack simply lands its 3 on the base.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# LeaderAction_StolenUnitCountsAsFriendly
#// SOR_011 Grand Inquisitor — CONTROL CHANGE. "A FRIENDLY unit" is read from the CONTROLLER: a 3/3
#// sitting in P1's arena but OWNED by P2 is a legal target for the front-side action. It is picked
#// explicitly (an answer outside the offered pool is rejected, so the pick itself proves membership),
#// takes the 2 damage and is readied, while the genuinely-owned 3/3 beside it is untouched.
#// Controlled units seat after the plain ones, so the arena is [SEC_080, the stolen SOR_095].

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaControlled: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# LeaderActionPickSurvivesRequestBoundary
#// SOR_011 Grand Inquisitor — REQUEST BOUNDARY. The leader's [Exhaust] payment is written in the
#// action request and the damage-and-ready is applied in the LATER request that answers the pick, so
#// the pending decision and the spent exhaust must both survive serialization. Two eligible 3/3s keep
#// the pick real; the second one takes the 2 and readies, the first is untouched.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# DeployedViaEpicAction_OnAttack_NoOtherFriendly_NoOffer
#// SOR_011 Grand Inquisitor — the same board as
#// Deployed_OnAttack_NoOtherFriendlyUnit_NoOfferAtAll, reached through the real Epic Action deploy
#// instead of being seeded already-flipped. The printed deployed text is "deal 1 damage to ANOTHER
#// friendly unit with 3 or less power", and the leader unit is P1's only body, so there is nothing to
#// offer: the attack lands its 3 on the base with no decision and the leader takes no self-damage.
#// Pairing the two routes to the same board is deliberate — it isolates HOW the attacker's identity
#// reaches the ability from WHAT the ability is supposed to do.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  myResources:6;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:0
