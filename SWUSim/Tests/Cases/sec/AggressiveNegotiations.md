# AttackBonusPerCardInHand
#// SEC_179 Aggressive Negotiations (event, cost 3) — Attack with a unit. For this attack, it gets +1/+0
#//   for each card in your hand. After SEC_179 leaves hand, 2 cards remain → SEC_041 (power 1) attacks
#//   P2's base for 1+2 = 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_179
WithP1Hand: SEC_042
WithP1Hand: SEC_045

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3

---

# BonusSnapshotNotReducedByDiscardDuringOnAttack
#// SEC_179 Aggressive Negotiations — the +1/+0-per-card bonus is locked in when the attack is declared and
#//   is NOT recomputed if the hand shrinks mid-attack. The attacker (SOR_095, base power 3) carries
#//   LOF_139 Battle Fury (+3/+3, and "On Attack: Discard a card from your hand"). After AN is played, 2
#//   cards remain in hand → +2. On attack, Battle Fury forces a discard (hand → 1), but the snapshot bonus
#//   stays +2: base takes 3 + 3 + 2 = 8 (a live recompute would deal only 7).

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_139
WithP1Hand: SEC_179
WithP1Hand: SOR_164
WithP1Hand: SOR_232
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P2BASEDMG:8
P1HANDCOUNT:1
