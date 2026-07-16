# BaseDamage_Draw
#// SOR_013 Cassian Andor (deployed Leader Unit, 4/6) — "When you deal damage to an enemy base: You may
#// draw a card." P1 deploys Cassian (6 resources) and attacks P2's base (Saboteur, 4 power). The base
#// takes 4, and the reactive offers P1 a draw → YES → P1 draws 1 (deck 1 → 0, hand 0 → 1).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EPICUSED

---

# DeclineDraw
#// SOR_013 Cassian Andor (deployed) — the draw is optional ("You may"). Cassian deploys and attacks
#// P2's base; the reactive offers a draw, P1 declines (NO) → no card drawn (deck stays 1, hand stays 0).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EPICUSED

---

# OncePerRound
#// SOR_013 Cassian Andor (deployed) — "Use this ability only once each round." Two enemy-base hits in
#// the same round; Cassian's reactive draws only for the FIRST. P1 deploys Cassian (ground) and has an
#// Alliance X-Wing (SOR_237) in space; Cassian attacks P2's base (4) → draw (YES), then the X-Wing
#// attacks P2's base (2) → no second offer. P1 drew exactly 1 (deck 2 → 1, hand 1), base took 4+2=6.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DECKCOUNT:1
P1LEADER:EPICUSED

---

# LeaderAction_2BaseDamage_NoDraw
#// SOR_013 Cassian Andor — the threshold is 3 (not "any"). P1's Alliance X-Wing (SOR_237, 2 power)
#// deals only 2 to P2's base, below the bar. The leader action is still used — Cassian exhausts and
#// pays 1 resource (1 → 0) — but the condition fails, so NO card is drawn (deck stays 1, hand stays 0).
#// Distinguishes "3 or more" from a buggy ">0" / ">=1".

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:2
P1HANDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_3BaseDamage_Draw
#// SOR_013 Cassian Andor (leader) — Action [1 resource, Exhaust]: If you've dealt 3 or more damage to
#// an enemy base this phase, draw a card. P1's Battlefield Marine (SOR_095, 3 power) attacks P2's base
#// for 3, meeting the threshold; P1 then uses the leader action — pays 1 resource (1 → 0), Cassian
#// exhausts, and the condition is met so P1 draws 1 (deck 1 → 0, hand 0 → 1).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_NoResource_NoOp
#// SOR_013 Cassian Andor — the leader Action costs 1 resource. With 0 ready resources it is a full
#// no-op: the cost can't be paid, so the action never starts — Cassian stays READY (action not spent),
#// nothing is drawn, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P1HANDCOUNT:0
P1DECKCOUNT:1
