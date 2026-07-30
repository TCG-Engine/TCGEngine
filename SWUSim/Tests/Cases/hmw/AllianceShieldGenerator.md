# PreventsExactlyFiveDamageThenDefeatsItselfAndDraws
#// HMW_081 — "Fortify. If attached base would be dealt 5 or more damage, prevent that damage. If you do,
#// defeat this upgrade and draw a card." The generator sits on P2's base and P1 attacks it with ASH_061
#// (a vanilla 5/5), so 5 is the inclusive edge of the threshold. "Draw a card" belongs to the upgrade's
#// controller — P2 — and the defeated non-token upgrade goes to P2's discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: ASH_061:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2BASE:UPGRADECOUNT:0
P2DISCARDCOUNT:1
P2HANDCOUNT:1
P2DECKCOUNT:1

---

# PreventsMoreThanFiveDamageToo
#// "5 or MORE" — ASH_061 carrying SOR_120 Academy Training (+2/+2) attacks for 7. Guards a threshold
#// written as == 5 instead of >= 5.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: ASH_061:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2BASE:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# FourDamageIsBelowTheThresholdAndLands
#// Just under the threshold: LAW_124 (4/7) deals its 4 in full, the generator stays attached, no draw.
#// Nothing is consumed — this is a conditional prevention, not a one-shot shield.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2BASE:UPGRADECOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:2

---

# IndirectDamageIsUnpreventableAndIsNotConsumed
#// A PREVENTION must skip unpreventable damage. JTL_234 deals 5 indirect damage to a player; P2 controls
#// no units, so all 5 auto-assign to their base. It lands in full, the generator is NOT defeated, and no
#// card is drawn — so a later preventable 5 would still be stopped.
#// ("Deal N indirect damage to a player" first asks WHICH player — the You&Opponent OPTIONCHOOSE.)

## GIVEN
CommonSetup: yyk/yyk/{myResources:3;myhandCardIds:JTL_234}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5
P2BASE:UPGRADECOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:2
