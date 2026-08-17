# FrontGrantRaid1
#// LAW_012 Sebulba (leader front) — "Action [Exhaust, discard a card from your deck]: A friendly unit
#// gains Raid 1 for this phase." Grant Raid 1 to SEC_080, then it attacks the base for 3+1 = 4 (Raid
#// gives +1/+0 while attacking).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# FrontNoFriendlyUnits_UsableAnyway
#// LAW_012 Sebulba (front) — "Action [Exhaust, discard a card from your deck]: A friendly unit gains
#// Raid 1." With NO friendly units in play the ability has no legal target, but the Action is still usable
#// (CR 6.4.587.c "Use it anyway"): the costs are still paid — Sebulba exhausts and the top of the deck is
#// discarded (deck 2 -> 1).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1DECKCOUNT:1

---

# FrontEmptyDeck_CannotUse
#// LAW_012 Sebulba (front) — the "discard a card from your deck" portion of the cost cannot be paid with
#// an empty deck, so the Action is unavailable. Sebulba stays ready and the friendly SEC_080 gains NO
#// Raid 1: it then attacks the base for its base power 3 (not 4).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:READY
P2BASEDMG:3

---

# DeployedOnAttackDiscardsFromDeck
#// LAW_012 Sebulba (deployed) — "On Attack: Discard a card from your deck." Non-optional, no prompt.
#// Deployed Sebulba attacks the base (3 damage: 2 power + Raid 1) and the top card of P1's deck is discarded (deck 2 -> 1);
#// the opponent's deck is untouched.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012:1:1:1;
  myBase:SOR_028;
  theirBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_128
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:1
P2BASEDMG:3

---

# DeployedOnAttackEmptyDeck_NoOp
#// LAW_012 Sebulba (deployed) — the On Attack discard does nothing if P1's deck is empty. Sebulba attacks
#// the base for 3 (2 power + Raid 1) and there is no card to discard (deck stays at 0).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012:1:1:1;
  myBase:SOR_028;
  theirBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:0
P2BASEDMG:3

---

# FrontGrantRaid1_SurvivesTheRequestBoundary
#// LAW_012 — request-boundary guard for FrontGrantRaid1. Production starts a FRESH process on every
#// answered decision, so the leader action's pending payload (which grant, Raid 1, and the "for this
#// phase" duration it will stamp) has to be reconstructed from serialized gamestate rather than an
#// in-memory continuation global — and the granted Raid then has to be read back by a LATER action, the
#// attack, from that same serialized state.
#// FrontGrantRaid1's own fixture has exactly one friendly unit, so its grant AUTO-RESOLVES and a boundary
#// there would be vacuous; a second friendly unit (SOR_095) is seeded purely to make the choose real
#// (MZCHOOSE [myGroundArena-0&myGroundArena-1]). SEC_080 is still the one granted, and still attacks the
#// base for 3+1 = 4.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# EpicDeploy_ForeignOwnedResourceCounts
#// LAW_012 — control axis, Epic Action: "If you CONTROL 4 or more resources, deploy this leader." A
#// leader can never change control, so the observable owner-vs-controller question lives in what it
#// COUNTS: resources are counted by who controls them (whose resource zone they sit in), not by who
#// owns the cards. P1 has 3 own resources plus one P2-OWNED resource seated in P1's zone (the end
#// state after e.g. an Arquitens resources an enemy card into your pool) = 4 controlled, and the epic
#// deploy goes through — Sebulba leaves the leader row for the ground arena.
#// Paired with EpicDeploy_ThreeResourcesBlocked below, which is the identical board minus the foreign
#// resource: at 3 the epic is refused. So the deploy here is caused by the P2-OWNED resource being
#// counted for its CONTROLLER, and nothing else.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028;
  myResources:3
}
SkipPreGame: true
P1OnlyActions: true
WithP1ResourceControlled: SOR_095:2

## WHEN
- P1>DeployLeader

## EXPECT
P1RESCOUNT:4
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1

---

# EpicDeploy_ThreeResourcesBlocked
#// LAW_012 — the negative half that makes EpicDeploy_ForeignOwnedResourceCounts discriminating, and the
#// gate's own boundary (3 vs 4). With 3 controlled resources and no foreign-owned resource in the pool,
#// the Epic Action's condition is false: the deploy is refused outright — Sebulba stays undeployed and
#// the ground arena stays empty.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028;
  myResources:3
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1RESCOUNT:3
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0

---

# FrontGrantRaid_TargetsAForeignOwnedFriendlyUnit
#// LAW_012 — control axis for the front Action's "A FRIENDLY unit gains Raid 1 for this phase".
#// Friendly means controlled by the ability's controller, whoever owns the card. The only unit on the
#// board is SEC_080 sitting in P1's ground arena but OWNED BY P2 (the end state after a control-take),
#// and it is a legal recipient: with Raid 1 it attacks the base for 3+1 = 4 rather than 3.
#// The Action's cost is paid from the CONTROLLER's deck as well — P1's one-card deck is milled to 0
#// and that card lands in P1's discard, so both halves of the ability run off P1's seat while the
#// buffed unit belongs to P2.
#//
#// COVERAGE: offer=FrontNoFriendlyUnits_UsableAnyway pins the empty-pool case (CR "use it anyway");
#//           the pool itself is single-target in every fixture, and this section proves it is scoped
#//           by CONTROL rather than ownership · decline=N/A (no "you may" on either side) ·
#//           control=EpicDeploy_ForeignOwnedResourceCounts + EpicDeploy_ThreeResourcesBlocked (the
#//           epic's resource count is by controller) + FrontGrantRaid_TargetsAForeignOwnedFriendlyUnit
#//           ("a friendly unit" spans a controlled-but-not-owned unit) ·
#//           reqboundary=FrontGrantRaid1_SurvivesTheRequestBoundary · boundary=FrontEmptyDeck_CannotUse
#//           vs FrontGrantRaid1 (the deck-discard cost must be payable) + DeployedOnAttackEmptyDeck_NoOp
#//           vs DeployedOnAttackDiscardsFromDeck (deployed side) + EpicDeploy_ThreeResourcesBlocked vs
#//           EpicDeploy_ForeignOwnedResourceCounts (3 vs 4 resources).

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_012;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1DECKCOUNT:0
P1DISCARDCOUNT:1
