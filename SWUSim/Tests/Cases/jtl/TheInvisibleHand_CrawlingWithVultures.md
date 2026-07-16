# CompleteAttack_SearchDroid
#// JTL_089 The Invisible Hand — the "When this unit completes an attack (and survives)" copy of its
#// search. The Invisible Hand attacks the enemy base, survives, then searches the top 8 for a Droid
#// (LOF_158, cost 3 → draw-only, no free-play branch) and draws it. Mirrors the When Played tests.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_089:1:0
WithP1Deck: [LOF_158 SOR_095 SOR_237]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:LOF_158

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION

---

# WhenPlayed_SearchDroid
#// JTL_089 The Invisible Hand — When Played: search the top 8 cards of your deck for a Droid unit, reveal
#// it, and draw it. The deck has one Droid (SEC_080); P1 draws it, the other two cards go to the bottom.
#// SEC_080 costs 2, so the "If it costs 2 or less, you may play it for free" rider offers a YESNO — here
#// P1 DECLINES (NO), so the drawn Droid stays in hand (hand 1, deck 2). See the YES branch in
#// InvisibleHand089_WhenPlayed_SearchDroid_PlayFree.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [SEC_080 SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION

---

# WhenPlayed_SearchDroidDrawOnly
#// JTL_089 The Invisible Hand — When Played: search the top 8 cards of your deck for a Droid unit, reveal
#// it, and draw it. If it costs 2 or less, you may play it for free.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [LOF_158 SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_158

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION

---

# WhenPlayed_SearchDroid_PlayFree
#// JTL_089 The Invisible Hand — When Played: search top 8 for a Droid, reveal and draw it; "If it costs
#// 2 or less, you may play it for free." The deck's lone Droid SEC_080 costs 2, so P1 draws it then
#// accepts the free-play YESNO. P1 starts with exactly 6 resources, all exhausted paying for JTL_089
#// (cost 6), so there are NO ready resources left — SEC_080 still enters play, proving it was free.
#// Result: JTL_089 in space (1), SEC_080 in ground (1), hand empty, the other 2 cards on the deck bottom.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [SEC_080 SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:0
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1DECKCOUNT:2
P1NODECISION
