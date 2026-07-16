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
