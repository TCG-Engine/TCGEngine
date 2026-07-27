# OnAttack_ControlsLeaderUnit_DrawsWhenYes
#// SOR_067 Rugged Survivors (Ground, 3/5, Vigilance, cost 5, Grit) — On Attack: if you control a leader
#//   unit, you may draw a card. Leader deployed (controls a leader unit) + attack the base + answer YES
#//   → draw 1 (hand 0→1, deck 1→0). Base takes the attacker's 3 power.

## GIVEN
P1LeaderBase: SOR_010:1:1:1/SOR_022
P2LeaderBase: SOR_010/SOR_022
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P2BASEDMG:3

---

# OnAttack_ControlsLeaderUnit_DeclineNoDraw
#// "you may" → declining draws nothing (hand stays 0, deck stays 1). The attack still resolves (base
#//   takes 3).

## GIVEN
P1LeaderBase: SOR_010:1:1:1/SOR_022
P2LeaderBase: SOR_010/SOR_022
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:3

---

# OnAttack_NoLeaderUnit_NoOp
#// No leader unit (leader undeployed) → the condition fails → no draw prompt at all. Attack still hits
#//   the base for 3; no decision pending.

## GIVEN
P1LeaderBase: SOR_010/SOR_022
P2LeaderBase: SOR_010/SOR_022
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:3
P1NODECISION
