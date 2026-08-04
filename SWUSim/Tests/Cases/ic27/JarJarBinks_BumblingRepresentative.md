# CostSixMill_PlusFourForThisAttack
#// IC27_187 Jar Jar Binks (Bumbling Representative) — 2 cost, 1/5, Heroism, Ground.
#// Text: "On Attack: Discard a card from your deck. If it costs 6 or more, this unit gets +4/+0 for
#// this attack."
#// His printed power is 1, so base damage reads the bonus directly: 1 + 4 = 5.
#// SOR_049 Obi-Wan Kenobi costs exactly 6 — the BOUNDARY HIT (see the cost-5 section for the miss).
#// The trailing POWER:1 is the "for THIS ATTACK" proof: a phase-buff implementation would leave 5.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_187:1:0
WithP1Deck: SOR_049

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_049
P1DISCARDUNIT:0:FROM:DECK
P1GROUNDARENAUNIT:0:POWER:1

---

# CostFiveMill_NoBonus
#// THE BOUNDARY MISS and the load-bearing negative: at cost 5 the "6 or more" gate is false, so he
#// deals his printed 1 — but the discard still happens (it is NOT conditional on the cost).

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_187:1:0
WithP1Deck: SOR_213

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_213

---

# EmptyDeck_NoMillNoBonusNoCrash
#// NO-VALID-TARGET: with nothing to discard the ability must resolve as far as it can and no-op
#// cleanly — the attack still happens for his printed 1.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_187:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P1DECKCOUNT:0
P1DISCARDCOUNT:0

---

# BonusAppliesAttackingAUnitToo
#// DISPATCH: the buffed power must reach the unit-combat branch, not just base damage.
#// 1 + 4 = 5 kills a 3/3 Imperial Dark Trooper; its 3-power counter lands on Jar Jar's 5 HP.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_187:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: SOR_049

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_187
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# SecondAttackNextRound_ReRollsTheMill
#// DURATION EDGE: the mill + cost check are re-derived on EVERY attack, not armed once. A cost-6 top
#// card buffs the first attack (5), and next round the new top card (cost 5) does not buff the second
#// (1) — total 6.
#// Jar Jar is UNIQUE, so this cannot be two copies attacking in one phase; it has to cross a round
#// boundary to re-ready him. Both decks are padded past the two mills so the regroup draws succeed —
#// an empty deck at regroup inflicts a +6 base penalty that would swamp the damage assertion.
#// ⚠ The trailing P2>Pass is load-bearing: P1OnlyActions hands P2 the CLAIMED initiative, so P2 is
#// the turn player when the new action phase opens and P1 cannot act until it passes. (P2 needs no
#// pass before the FIRST attack — its auto-pass only fires in response to a P1 action.)

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_187:1:0
WithP1Deck: [SOR_049 SOR_213 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1DISCARDCOUNT:2
