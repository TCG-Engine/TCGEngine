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
