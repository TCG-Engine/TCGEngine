# WhenDefeated
#// SOR_031 Inferno Four — WhenDefeated scry 2: trigger fires when defeated in combat.
#// SOR_031 (3/3) attacks P2's SOR_066 (4/6). SOR_031 takes 4 damage and dies.
#// Scry: put SOR_095 on bottom, keep SOR_128 on top.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1SpaceArena: SOR_031:1:0
WithP2SpaceArena: SOR_066:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128

---

# WhenPlayed_KeepBoth
#// SOR_031 Inferno Four — WhenPlayed scry 2: keep both cards on top, preserve order.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_128|

## EXPECT
P1DECKTOPCARD:SOR_095

---

# WhenPlayed_KeepBothSwap
#// SOR_031 Inferno Four — WhenPlayed scry 2: keep both on top but swap order.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128,SOR_095|

## EXPECT
P1DECKTOPCARD:SOR_128

---

# WhenPlayed_TopToBottom
#// SOR_031 Inferno Four — WhenPlayed scry 2: put top card on bottom, keep second.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128

---

# NoGloryOnlyResults_NewControllerResolvesIt
#// SOR_031 Inferno Four — a take-control-then-defeat (JTL_043) defeats the unit under the TAKER's
#// control, so the TAKER resolves the When Defeated and "your deck" is the TAKER's deck: P1 looks at
#// the top 2 of P1's deck, keeps SOR_128 on top and puts SOR_095 on the bottom. Inferno Four goes to its OWNER P2's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2SpaceArena: SOR_031:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P2SPACEARENACOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128
P2DISCARDCOUNT:1

---

# WhenPlayed_ScryAnsweredAcrossRequestBoundary
#// SOR_031 Inferno Four — the SCRY panel is an INTERACTIVE decision, so in production the player
#// answers it in a LATER request, in a fresh process. DoScry splices the peeked cards out of the deck
#// and parks them in memory, so the finalize must still find them after the boundary. Same board as
#// WhenPlayed_KeepBothSwap; the only difference is the boundary between the play and the answer.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_128,SOR_095|

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128
