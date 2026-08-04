# Front_PaysResourceAndSacrifice_DrawsAndHealsTwo
#// IC27_001 Darth Vader (No One to Stop Us) — 7 cost, 5/7, Vigilance+Villainy, Force/Imperial/Sith.
#// Front: "Action [1 resource, Exhaust, defeat a friendly unit]: Draw a card and heal 2 damage from
#//   your base."
#// Epic Action: deploy at 7+ resources (generic — the threshold IS the printed cost).
#// Deployed: "On Attack: You may defeat another friendly unit. If you do, draw a card and heal 2
#//   damage from your base."
#// Structurally IDENTICAL to SOR_006 Emperor Palpatine on BOTH sides (user ruling): the front defeat is
#// part of the bracketed COST, the deployed one is an effect behind "you may … If you do".
#// Base seeded at 5 damage so the heal is a full 2 (5 -> 3).

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;myBaseDamage:5;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1BASEDMG:3
P1HANDCOUNT:1

---

# Front_NoFriendlyUnit_ActionUnavailable_CompleteNoOp
#// THE COST GATE, and the reason this leader was pair-programmed. The defeat is part of the COST, so
#// with no friendly unit to sacrifice the action is UNAVAILABLE — the leader must not exhaust, the
#// resource must not be spent, and the player keeps their action. (A cost-requirement gate is KEPT in
#// SWULeaderActionAffordable; only effect-target gates were dropped by the CR 6.4.587.c sweep.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;myBaseDamage:5;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:3
P1BASEDMG:5
P1HANDCOUNT:0
P1NODECISION

---

# Front_NoReadyResource_ActionUnavailable
#// The other half of the same cost: [1 resource] is unpayable, so the action is a no-op even with a
#// friendly unit available to sacrifice.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:5;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1Resources: 1:SOR_046:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENACOUNT:1
P1BASEDMG:5
P1NODECISION

---

# Front_HealIsCappedByActualBaseDamage
#// QUANTITY: "heal 2" is capped by the damage actually present — a base with only 1 damage goes to 0,
#// not to -1.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;myBaseDamage:1;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# Deployed_OnAttack_TakeTheOffer_SacrificesDrawsAndHeals
#// THE DEPLOYED SIDE — its own ability set, driven through the REAL path (Epic deploy at 7 resources,
#// then attack). Here the defeat is an EFFECT behind "you may … If you do", so it opens with a YES/NO
#// rather than being a cost gate.
#// Vader deploys into ground index 1 (behind the pre-placed Marine), so he attacks from index 1.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;myBaseDamage:5;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:5
P1BASEDMG:3
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:7

---

# Deployed_OnAttack_Decline_NoSacrificeNoDrawNoHeal
#// TAKE/DECLINE — the load-bearing difference from the front side. Because the deployed defeat is an
#// EFFECT, declining is legal and costs nothing: the friendly unit lives, no card is drawn, and the
#// base stays damaged. The attack itself still happens.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;myBaseDamage:5;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:5
P1BASEDMG:5
P1HANDCOUNT:0
P1GROUNDARENACOUNT:2

---

# Deployed_OnAttack_NoOtherFriendlyUnit_NoPrompt
#// SCOPE: "another friendly unit" excludes Vader himself, so attacking alone leaves no legal sacrifice
#// and no prompt is raised. The attack still resolves — unlike the front side, the deployed ability is
#// not a cost, so nothing is gated on it.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;myBaseDamage:5;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1BASEDMG:5
P1HANDCOUNT:0
P1NODECISION

---

# Deploy_BelowThreshold_IsBlocked
#// The Epic deploy threshold is the leader's printed cost (7). With 6 resources he must stay
#// undeployed and the Epic must remain available.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;myLeader:IC27_001}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:6
