# Deployed_Raid2_MoreCardsInHand
#// SEC_010 Dedra Meero (deployed) — While you have more cards in hand than an opponent, this unit gains
#// Raid 2 (+2/+0 while attacking). P1 has 2 cards, P2 has 0 → Raid 2 active. SEC_010 (2/5) attacks the
#// enemy base for 2 + 2 = 4.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4

---

# LeaderAction_OpponentDeals2
#// SEC_010 Dedra Meero (leader) — the opponent ACCEPTS (YES) → its controller deals 2 damage to its own
#// unit, and P1 does NOT draw.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Deck: [SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:0
P1LEADER:EXHAUSTED

---

# LeaderAction_OpponentDeclines_Draw
#// SEC_010 Dedra Meero (leader) — Action [1 resource, Exhaust]: Choose an enemy unit. Its controller may
#// deal 2 damage to it. If they don't, draw a card. Here the opponent DECLINES (NO) → P1 draws a card and
#// the enemy unit is undamaged.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Deck: [SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Deployed_NoRaid_WhenHandsAreEqual
#// SEC_010 Dedra Meero (deployed) — the Raid 2 is gated on having MORE cards in hand than the opponent.
#// With both hands at 2 the condition is false (equal is not more), so she attacks at her printed 2.
#// The negative that proves the gate in Deployed_Raid2_MoreCardsInHand is load-bearing.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021;
  theirHandCardIds:SOR_095,SOR_095
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Deployed_NoRaid_WhenHoldingFewerCards
#// SEC_010 Dedra Meero (deployed) — and strictly fewer cards is likewise not "more": P1 holds 1 card to
#// P2's 3, so no Raid and the base takes her printed 2.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021;
  theirHandCardIds:SOR_095,SOR_095,SOR_095
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
