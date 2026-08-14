# WhenDefeated2ExpForceDraw
#// SOR_049 Obi-Wan Kenobi (4/6) — When Defeated: give 2 Experience tokens to another
#// friendly unit; if it's a Force unit, draw a card. Obi-Wan (pre-damaged to 1 remaining
#// HP) attacks P2's Battlefield Marine and dies in the exchange. His only other friendly
#// is Count Dooku (SOR_038, a Force unit) → auto-gets +2/+2 (5/4 → 7/6) and P1 draws.
#// COVERAGE: offer=Offer_ExcludesSelfAndEnemyUnits (pending SELECTABLEEXACT: only the OTHER
#//           friendly units — never Obi-Wan himself, never an enemy unit) · reqboundary=
#//           PicksNonForce_NoDraw (the exp-recipient answer arrives in a separate request from
#//           the attack that killed him) · control=TakenByNoGlory_EnemyGivesExpAndDraws (the
#//           defeat resolves under the OPPONENT's control → their seat picks/receives) ·
#//           boundary pair=WhenDefeated2ExpForceDraw (Force → draw) + PicksNonForce_NoDraw
#//           (non-Force → no draw) · decline=N/A ("Give 2 Experience..." is mandatory — no
#//           "you may" on either clause)

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SOR_038:1:0    # Force recipient (5/4) — index 0 (stays put)
WithP1GroundArena: SOR_049:1:5    # Obi-Wan, 5 damage → 1 remaining HP — index 1
WithP2GroundArena: SOR_095:1:0    # defender (3/3)

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:6
P1HANDCOUNT:1

---

# Offer_ExcludesSelfAndEnemyUnits
#// Intended: "another friendly unit" — the exp-recipient pool is every OTHER friendly unit
#// and never Obi-Wan himself nor any enemy unit. Two other friendlies (Dooku + Marine) keep
#// the pick interactive; the decision is left pending so the exact pool can be inspected.
#// The surviving enemy Consular Security Force proves enemy units are excluded.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_038:1:0    # Count Dooku (Force) — idx 0
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (non-Force) — idx 1
WithP1GroundArena: SOR_049:1:5    # Obi-Wan, 1 remaining HP — idx 2 (dies; indexes 0/1 stay put)
WithP2GroundArena: SOR_046:1:0    # enemy Consular Security Force (3/7) — survives the exchange

## WHEN
- P1>AttackGroundArena:2:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# PicksNonForce_NoDraw
#// Intended: the draw is conditional on the RECIPIENT being a Force unit. Same board as the
#// offer section; P1 picks the Battlefield Marine (non-Force) → it gets the 2 Experience
#// (3/3 → 5/5) but NO card is drawn (deck seeded so a wrong draw would be visible).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SOR_038:1:0    # Count Dooku (Force) — idx 0, untouched
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (non-Force) — idx 1, the pick
WithP1GroundArena: SOR_049:1:5    # Obi-Wan, 1 remaining HP — idx 2
WithP2GroundArena: SOR_046:1:0    # enemy defender (3/7)

## WHEN
- P1>AttackGroundArena:2:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1HANDCOUNT:0

---

# TakenByNoGlory_EnemyGivesExpAndDraws
#// Intended: control change follows the defeat. P2 plays No Glory, Only Results (JTL_043:
#// take control of a non-leader unit, then defeat it) on P1's Obi-Wan. At the defeat Obi-Wan
#// is under P2's control, so the When Defeated resolves for P2: "another friendly unit" is
#// P2's Count Dooku (sole candidate → auto-resolves), who gets the 2 Experience (5/4 → 7/6);
#// Dooku is a Force unit → P2 draws. Obi-Wan still goes to his OWNER's (P1) discard.

## GIVEN
CommonSetup: bbw/bbk/{theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: JTL_043
WithP2Deck: SOR_095
WithP1GroundArena: SOR_049:1:0    # Obi-Wan — the NGOR target
WithP2GroundArena: SOR_038:1:0    # Count Dooku (Force) — P2's exp recipient

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2HANDCOUNT:1
P2DISCARDCOUNT:1
