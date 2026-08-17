# FrontDiscardDeal1
#// LAW_011 Darth Vader (leader front) — "Action [Exhaust, discard a card from your hand]: Deal 1 damage
#// to a unit or base." P1 discards SEC_080 (cost) and deals 1 to P2's SOR_128 (3/1), defeating it.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_011;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# FrontNoActionWhenHandEmpty
#// LAW_011 Darth Vader (leader front) — the Action's cost is [Exhaust, discard a card from your hand].
#// With an empty hand there is no card to discard, so the ability is unavailable. P1 has too few
#// resources to deploy (cost 7) and an empty hand → using the leader ability no-ops: Vader stays ready,
#// the enemy unit is undamaged, and no decision is pending.

## GIVEN
CommonSetup: yrk/grw/{myLeader:LAW_011;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1LEADER:NOTDEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# DeployedDiscardTwoDealTwo
#// LAW_011 Darth Vader (leader unit / deployed side) — On Attack: discard any number of cards from your
#// hand, then deal that much damage to a unit or base. P1 has 3 cards in hand, attacks the base, discards
#// 2 of them and deals 2 damage to the enemy base; the third card stays in hand.

## GIVEN
CommonSetup: yrk/grw/{myLeader:LAW_011:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_128 SOR_046]
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1HANDCOUNT:1
P1DISCARDCOUNT:2
P1LEADER:DEPLOYED

---

# DeployedDiscardAllDealTwo
#// LAW_011 Darth Vader (deployed) — the player may discard their entire hand. With 2 cards in hand, P1
#// discards both on attack and deals 2 damage to the enemy base; hand is emptied.

## GIVEN
CommonSetup: yrk/grw/{myLeader:LAW_011:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_128]
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1HANDCOUNT:0
P1DISCARDCOUNT:2

---

# DeployedDiscardNothingOnlyCombat
#// LAW_011 Darth Vader (deployed) — discarding is optional ("any number", may be zero). P1 attacks and
#// chooses to discard nothing, so no ability damage is dealt; the hand is untouched and only combat
#// damage lands on the defender.

## GIVEN
CommonSetup: yrk/grw/{myLeader:LAW_011:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_128]
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1HANDCOUNT:2
P1DISCARDCOUNT:0

---

# DeployedEmptyHandOnlyCombat
#// LAW_011 Darth Vader (deployed) — attacking with an empty hand simply resolves combat with no ability
#// damage (nothing to discard). P1 attacks the base with no cards in hand; the base takes no ability
#// damage and the turn passes to P2.

## GIVEN
CommonSetup: yrk/grw/{myLeader:LAW_011:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:0
P1DISCARDCOUNT:0
P1NODECISION

---

# EpicDeployCountsAControlledEnemyOwnedResource
#// LAW_011 Darth Vader — "Epic Action: If you CONTROL 7 or more resources, deploy this leader." The gate
#// is the one place on this card where owner and controller can diverge, and it must count control. P1 has
#// six of their own cards resourced; the seventh slot in P1's resource zone holds a P2-OWNED card — the
#// end state after an effect resources an enemy card (e.g. SHD_122 Arquitens Assault Cruiser). Controlling
#// seven clears the gate and Vader deploys. EpicDeployBlockedAtSixOwnResources below holds the identical
#// board without that slot and does NOT deploy, so it is provably the P2-owned resource that crosses the
#// threshold rather than a loose or missing check.

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_011;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1ResourceControlled: SOR_095:2

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED

---

# EpicDeployBlockedAtSixOwnResources
#// LAW_011 Darth Vader — the negative partner that makes the section above load-bearing: six P1-owned
#// resources and no controlled seventh is one short of "7 or more resources", so the Epic Action does
#// nothing and Vader stays on his leader side.
#//
#// COVERAGE: control=EpicDeployCountsAControlledEnemyOwnedResource + this section (the Epic resource gate
#//           counts resources you CONTROL, including a P2-owned card sitting in P1's resource zone). The
#//           rest of the card cannot separate owner from controller: a leader is always owned and
#//           controlled by its own seat, and "your hand" / the discard cost therefore have only one seat
#//           to resolve to · offer="a unit or base" is unqualified and both sides are reachable, but the
#//           exact pool is not pinned with SELECTABLEEXACT · decline=DeployedDiscardNothingOnlyCombat
#//           (answer "-" on the "any number" discard) · boundary pair=FrontDiscardDeal1 vs
#//           FrontNoActionWhenHandEmpty, and DeployedDiscardTwoDealTwo vs DeployedEmptyHandOnlyCombat,
#//           plus the two Epic sections above · reqboundary=not encoded

## GIVEN
CommonSetup: yrk/grw/{
  myLeader:LAW_011;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
