# DeployedOnAttackReadyEnemyDiscount2
#// TS26_06 Rex (leader deployed, 5/6) — On Attack: you may ready an exhausted enemy unit; if you do, the
#// next event you play this phase costs 2 less. Rex attacks LAW_124, readies the exhausted SEC_080, then
#// Urgent Mission (cost 2) plays for 0 (0 resources — only via the -2 discount), dealing 2 to P1's base.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:0;handCardIds:TS26_64}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [LAW_124:1:0 SEC_080:0:0]
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:1:READY
P1BASEDMG:2

---

# FrontReadyEnemyDiscountEvent
#// TS26_06 Rex (leader front) — Action [Exhaust, ready an exhausted enemy unit]: the next event you play
#// this phase costs 1 less. Rex readies the exhausted enemy SEC_080, then Urgent Mission (cost 2) plays for
#// 1 (only affordable via the discount: 1 resource → 0), dealing 2 to P1's own base.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06;myResources:1;handCardIds:TS26_64}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# Front_OffersOnlyEXHAUSTEDENEMYUnits_IncludingADeployedLeader
#// TS26_06 Rex (front) — the Action's cost is "ready an exhausted enemy unit", so the pool is exactly the
#// EXHAUSTED units the opponent controls. P1's own exhausted SOR_128 is not a legal payment, P2's ready
#// SOR_095 is not exhausted, and P2's exhausted DEPLOYED LEADER (SOR_002 Iden Versio, at their arena index
#// 2) is a unit like any other — so the offer is the exhausted SEC_080 plus that leader.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06;myResources:1;theirLeader:SOR_002:0:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:0:0
WithP2GroundArena: [SEC_080:0:0 SOR_095:1:0]
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-2

---

# Front_NoExhaustedEnemyUnit_NoDiscountIsArmed
#// TS26_06 Rex (front) — the cost cannot be paid when every enemy unit is ready, so no discount is armed.
#// Urgent Mission (cost 2, on-aspect under an Aggression base + Aggression/Heroism leader) then costs the
#// full 2, draining the pool to 0. Discriminating: with the discount it would cost 1 and leave 1.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1BASEDMG:2

---

# Front_DiscountsOnlyTheNEXTEventThisPhase
#// TS26_06 Rex (front) — "the NEXT event you play this phase costs 1 less" is consumed by the first event
#// and does not carry to the second. Two Urgent Missions off 3 resources: the first costs 1, the second
#// costs the full 2, ending at 0. Both deal 2 to P1's own base, so the base sits at 4.
#// Discriminating: if the discount persisted the pair would cost 2 and leave 1 resource.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_64 TS26_64]
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095 SEC_080 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1BASEDMG:4

---

# Front_DoesNotDiscountNonEventCards
#// TS26_06 Rex (front) — the discount applies to EVENTS only. With the -1 armed, the upgrade SOR_166
#// Infiltrator's Skill (cost 1) still costs 1, and crucially does not consume the discount: the Urgent
#// Mission played afterwards still costs 1. 3 - 1 - 1 = 1 resource left.
#// Discriminating: if the upgrade took the discount it would be free and the event would cost 2 (same
#// total, different route), so the UPGRADECOUNT assertion pins that the upgrade actually attached.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1Hand: [SOR_166 TS26_64]
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1BASEDMG:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Front_DiscountExpiresAtTheEndOfThePhase
#// TS26_06 Rex (front) — "this phase". The discount is armed but never spent; both players pass to end the
#// action phase, decline to resource in the next round, and only then is Urgent Mission played. It costs
#// the full 2 (pool 2 -> 0) instead of 1, proving the armed discount was cleared at the phase boundary.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06;myResources:2}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1Hand: TS26_64
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095 SEC_080 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1BASEDMG:2

---

# Deployed_OffersOnlyEXHAUSTEDENEMYUnits_IncludingADeployedLeader
#// TS26_06 Rex (deployed) — On Attack the ready-target pool matches the front side: exhausted ENEMY units
#// only. P1's own exhausted SOR_128 and P2's ready SOR_095 are excluded; P2's exhausted deployed leader
#// (SOR_002, their arena index 2) is offered alongside the exhausted SEC_080. Rex is P1's arena index 1,
#// behind the pre-seated SOR_128.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:1;theirLeader:SOR_002:0:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:0:0
WithP2GroundArena: [SEC_080:0:0 SOR_095:1:0]
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-2

---

# Deployed_CanBeDeclined_NoReadyAndNoDiscount
#// TS26_06 Rex (deployed) — "You MAY ready an exhausted enemy unit. IF YOU DO, ..." Declining does both
#// halves of nothing: SEC_080 stays exhausted and no discount is armed, so Urgent Mission costs the full 2.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1BASEDMG:2
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# Deployed_NoExhaustedEnemyUnit_NoOfferAndNoDiscount
#// TS26_06 Rex (deployed) — with every enemy unit ready there is nothing to ready, so the On Attack window
#// raises no decision at all and the attack on the base (5 power) resolves straight through. Urgent Mission
#// afterwards costs the full 2.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1BASEDMG:2
P2BASEDMG:5

---

# Deployed_DiscountsOnlyTheNEXTEventThisPhase
#// TS26_06 Rex (deployed) — the -2 is consumed by the first event only. Off 4 resources the first Urgent
#// Mission is free (2 - 2), the second costs the full 2, ending at 2. Both hit P1's own base for 2.
#// Discriminating: a persistent -2 would leave 4, a missing one would leave 0.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_64 TS26_64]
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095 SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:2
P1BASEDMG:4

---

# Deployed_DoesNotDiscountNonEventCards
#// TS26_06 Rex (deployed) — the -2 skips units. SOR_128 Death Star Stormtrooper costs 3 here (1 printed
#// +2 for the uncovered Villainy aspect) and still costs 3 with the discount armed; the Urgent Mission
#// played afterwards is the one that goes free. 4 - 3 - 0 = 1 resource left.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_128 TS26_64]
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1BASEDMG:2

---

# Deployed_DiscountExpiresAtTheEndOfThePhase
#// TS26_06 Rex (deployed) — "this phase" on the deployed side too. Rex attacks and readies SEC_080 to arm
#// the -2, then the phase is passed out and the next round's resource step declined. Urgent Mission now
#// costs the full 2 (pool 2 -> 0), not 0.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:2}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1Hand: TS26_64
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095 SEC_080 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1BASEDMG:2

---

# Deployed_CanBeDeclined_NoReadyAndNoDiscount_ByConfirmingEmpty
#// ⚠ PASS-TWIN of Deployed_CanBeDeclined_NoReadyAndNoDiscount — byte-for-byte except the decline.
#// TS26_06#0 is one of the 8 MZMAYCHOOSE continuations that CLOSE THE ACTION (SWUAfterAction). Since the
#// SWUQueueMayChooseTarget default flipped to dontSkipOnPass:1, they now RUN on a decline where they were
#// previously skipped — so this twin is what proves the flip did not introduce a DOUBLE close.
#// `-` and "PASS" are two different declines and the client only ever sends "PASS"; the pair must agree.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_06:1:1;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1BASEDMG:2
P2GROUNDARENAUNIT:0:EXHAUSTED
