# Front_AttackIntoACostlierUnit_ExhaustToDraw
#// HMW_014 Wicket (leader FRONT, undeployed) — "When a friendly unit attacks a unit that costs more than
#// it: You may exhaust this leader. If you do, draw a card."
#// COST is always PRINTED cost. SOR_046 Consular Security Force (cost 4) attacks LAW_124 Industrious Team
#// (cost 8): 8 > 4, so the offer appears. Accepting exhausts the undeployed leader and draws 1.
#// Stats picked so neither unit dies (4 back onto 7 HP, 3 onto 7) — the draw is the only thing moving.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# Front_Decline_LeaderStaysReadyAndNoDraw
#// "You MAY exhaust this leader" — declining costs nothing and draws nothing.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1LEADER:READY
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# Front_EqualCostDefender_NoTrigger
#// "costs MORE than it" is a strict comparison — the boundary case. SOR_046 (cost 4) attacks another
#// SOR_046 (cost 4): equal, so no offer is raised at all.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1LEADER:READY
P1HANDCOUNT:0

---

# Front_CheaperDefender_NoTrigger
#// The other side of the gate: SOR_046 (cost 4) attacks LAW_180 Inspired Recruit (cost 1). 1 is not more
#// than 4, so nothing triggers even though a unit was attacked and defeated.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_180:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1LEADER:READY
P1HANDCOUNT:0

---

# Front_AttackingABase_NoTrigger
#// "attacks a UNIT that costs more than it" — a base is not a unit, so a base attack never triggers
#// (and a base has no cost to compare against).

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1LEADER:READY
P1HANDCOUNT:0
P2BASEDMG:3

---

# Front_ACostlierDeployedENEMYLEADERAlsoCounts
#// A deployed leader is a unit and still has its PRINTED cost, so it participates in the comparison.
#// P2's deployed SOR_010 Darth Vader (printed cost 7) is attacked by SOR_046 (cost 4): 7 > 4, offer fires.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  theirLeader:SOR_010:1:1:1;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1

---

# Front_AlreadyExhaustedLeader_NoOffer
#// The exhaust IS the cost, so an already-exhausted leader cannot pay it and the offer must not appear
#// (rather than appearing and doing nothing).

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014:0;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0

---

# Front_DoesNotFireOnceWicketIsDEPLOYED
#// The two sides are mutually exclusive: once deployed, the front reactive ability is gone (the deployed
#// side's On Attack replaces it). The deployed Wicket attacks a costlier unit and no front-side offer
#// appears — and with no friendly unit costing 3 or less, the deployed side draws nothing either, so the
#// hand stays empty and both halves are proven silent in one board.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014:1:1:1;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# Deployed_OnAttack_ControlsACheapUnit_Draws
#// HMW_014 deployed — "On Attack: If you control a unit that costs 3 or less, draw a card."
#// P1 also controls LAW_180 Inspired Recruit (printed cost 1), so the condition holds and the attack
#// draws 1. No exhaust cost on this side and no choice — it just happens.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014:1:1:1;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_180:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# Deployed_OnAttack_NoCheapUnit_NoDraw
#// The negative that proves the condition is load-bearing: P1's only other unit is SOR_046 (cost 4), and
#// Wicket himself is printed cost 4 — nothing at 3 or less, so no card is drawn.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014:1:1:1;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# Deployed_ViaARealDeploy_OnAttackDraws
#// The REAL execution path: actually DeployLeader (Epic Action, 4+ resources) rather than seeding the
#// deployed state, then attack — this exercises the deploy→dispatch wiring, not just the handler.

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_180:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE

## EXPECT
P1LEADER:DEPLOYED
P1HANDCOUNT:1
P1DECKCOUNT:2
