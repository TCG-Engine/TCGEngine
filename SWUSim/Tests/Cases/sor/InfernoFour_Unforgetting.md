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
