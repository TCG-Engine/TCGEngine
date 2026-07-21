# OnAttack_DeclineNoBuff
#// ASH_172 Razor Crest (Space, 3/5, cost 4) — declining the On Attack discard leaves the unit at base
#// power. Razor Crest attacks P2's base and P1 declines (NO), so the base takes only 3 and the hand card
#// is kept.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_095}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1

---

# OnAttack_DiscardForBuff
#// ASH_172 Razor Crest (Space, 3/5, Saboteur, cost 4) — On Attack: you may discard a card from your hand;
#// if you do, this unit gets +2/+0 for this attack. Razor Crest attacks P2's base; P1 accepts (YES) and
#// discards its one hand card, so the base takes 3+2 = 5 and the hand empties.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_095}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:5
P1HANDCOUNT:0

---

# OnAttack_DeclineDiscard_NoBuff
#// ASH_172 Razor Crest — the +2/+0 requires discarding a card. Declining leaves it at base 3 power when it
#// attacks the enemy base.
## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SOR_063}
WithP1SpaceArena: ASH_172:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:3
