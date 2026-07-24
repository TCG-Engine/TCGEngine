# Deployed_HealOnSmuggle
#// SEC_008 Bail Organa (deployed) — When you play a card from your resources: Heal 1 damage from your base.
#// P1's base starts with 2 damage; P1 smuggles SHD_065 (Vigilance, covered by the JTL_019 base) from
#// resources → the deployed SEC_008 heals 1 from P1's base (2 → 1).

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008:1:1:1;
  myBase:JTL_019;
  myBaseDamage:2;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SHD_065:1,8:SOR_095:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:1
P1BASEDMG:1

---

# LeaderAction_NoDefeat_NoOp
#// SEC_008 Bail Organa (leader) — the effect is conditional: "If a friendly unit was defeated this phase".
#// With no friendly unit defeated, the action still pays its cost and exhausts the leader (like Iden), but
#// returns no resource and ramps nothing: resource COUNT unchanged, no decision, hand empty.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1RESCOUNT:3
P1HANDCOUNT:0
P1DECKCOUNT:2
P1NODECISION

---

# LeaderAction_ReturnResourceRamp
#// SEC_008 Bail Organa (leader) — Action [1 resource, Exhaust]: If a friendly unit was defeated this phase,
#// return a friendly resource to its owner's hand. If you do, put the top card of your deck into play as a
#// resource. P1's SOR_128 (3/1) attacks SOR_063 (2/4 Sentinel) and dies to the 2 counter-damage (friendly
#// defeated this phase). P1 then returns a resource (→ hand) and ramps the deck top as a resource.
#// Net resource count unchanged (return −1, ramp +1); hand +1; deck −1.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:3
P1HANDCOUNT:1
P1DECKCOUNT:1
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED

---

# LeaderAction_EnemyDefeated_NoOp
#// SEC_008 Bail Organa (leader) — the gate is "If a FRIENDLY unit was defeated this phase". An ENEMY unit
#// dying does NOT satisfy it. P1's 8/8 (SOR_039) attacks and defeats P2's Consular Security Force
#// (SOR_046, 3/7); only an enemy unit was defeated. Bail's action then still pays its cost and exhausts,
#// but returns no resource and ramps nothing: resource count unchanged, deck untouched, hand empty, no
#// decision.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1RESCOUNT:3
P1HANDCOUNT:0
P1DECKCOUNT:2
P1NODECISION

---

# LeaderAction_FriendlyDefeated_EmptyDeck_ReturnOnly
#// SEC_008 Bail Organa (leader) — "return a friendly resource to its owner's hand. If you do, put the top
#// card of your deck into play as a resource." With the deck EMPTY, the return still happens but the ramp
#// has no card to place. P1's SOR_128 (3/1) attacks SOR_063 (2/4 Sentinel) and dies to the counter
#// (friendly defeated). Bail returns a resource (→ hand): resource count drops 3 → 2, hand +1, deck stays
#// empty, leader exhausted.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Deck: []
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:2
P1HANDCOUNT:1
P1DECKCOUNT:0
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED

---

# Deployed_HealOnPlot
#// SEC_008 Bail Organa (deployed) — "When you play a card from your resources: Heal 1 damage from your
#// base." Playing a card via Plot counts. P1's base (JTL_019) has 5 damage. Deploying Bail opens the Plot
#// window; P1 plays Unveiled Might (SEC_123, cost 4, Command — on-aspect for the bgw base) from resources.
#// That resource-play heals 1 (5 → 4). The Plot card is replaced by the top of the deck, so the resource
#// count holds at 14 and the deck drops 2 → 1.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008;
  myBase:JTL_019;
  myBaseDamage:5;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SEC_123:1,13:SOR_095:1
WithP1Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1BASEDMG:4
P1RESCOUNT:14
P1DECKCOUNT:1
P1NODECISION

---

# Deployed_HealPerPlotCard
#// SEC_008 Bail Organa (deployed) — the heal fires once per card played from resources. Deploying Bail
#// opens the Plot window with TWO Plot cards in P1's resources: Unveiled Might (SEC_123) and Armor of
#// Fortune (SEC_070). P1 plays both from resources; each triggers the deployed heal, so P1's base drops
#// from 5 damage to 3 (two heals of 1). The final Plot offer is declined.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008;
  myBase:JTL_019;
  myBaseDamage:5;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SEC_123:1,2:SEC_070:1,13:SOR_095:1
WithP1Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myResources-1
- P1>AnswerDecision:PASS

## EXPECT
P1LEADER:DEPLOYED
P1BASEDMG:3
P1NODECISION

---

# Deploy_DiscardTwo_NonEpicRepeatable
#// SEC_008 Bail Organa — deploy is "Action [Exhaust, discard 2 cards from your hand]: if you control 4 or
#// more resources, deploy this leader." Non-epic + repeatable. P1 controls 5 resources and discards 2 hand
#// cards to deploy Bail: the leader ends DEPLOYED + exhausted, the Epic Action is STILL available (this is
#// NOT the epic deploy), 2 cards leave the hand to the discard, and no resources are spent.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:SEC_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: [SOR_095 SOR_046 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EXHAUSTED
P1LEADER:EPICAVAILABLE
P1HANDCOUNT:1
P1DISCARDCOUNT:2
P1RESAVAILABLE:5

---

# Deploy_LessThan4Resources_NoOp
#// SEC_008 Bail Organa — the deploy requires controlling 4+ resources. With only 3, DeployLeader is a no-op:
#// Bail stays undeployed and the hand is untouched (no discard cost is paid).

## GIVEN
CommonSetup: ggw/rrk/{myLeader:SEC_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: [SOR_095 SOR_046 SOR_095]

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1HANDCOUNT:3
P1DISCARDCOUNT:0
