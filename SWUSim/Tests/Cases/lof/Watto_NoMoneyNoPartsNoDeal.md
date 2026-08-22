# OnAttack_OpponentChoosesDraw
#// LOF_065 Watto — On Attack: an opponent chooses one: you give an Experience token to a friendly unit,
#// or you draw a card. Watto attacks the base; P2 picks "Draw", so P1 draws a card.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_065:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:1
P2BASEDMG:1

---

# OnAttack_OpponentChoosesGiveExperience
#// LOF_065 Watto — On Attack the opponent instead picks the Experience branch: P1 gives an Experience token
#// to a friendly unit (here the Marine, SOR_095) — no card is drawn. Watto (idx 0) attacks the base; P2
#// chooses GiveExp; P1 puts the Experience on the Marine (idx 1). Intended: opponent chooses to have you give an
#// Experience token to a friendly unit.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_065:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:GiveExp
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1HANDCOUNT:0
P2BASEDMG:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4

---

# TwinSuns_TheCHOSENOpponentMakesTheChoice
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, PROMPT). "An opponent chooses one" — Watto's
#// controller picks WHICH opponent does the choosing; OtherPlayer() picked one silently.
#// ⚠ NO $eligible filter, and this is the PUREST case of taxonomy shape 3: the chosen opponent needs
#// nothing on their board, in hand or in deck — the "pool" they act on is not theirs at all, they are
#// merely picking between two things that happen to the CASTER. No live opponent can be unable to act.
#// P1's Watto attacks; P1 hands the choice to SEAT 3. Seat 3 must own the OPTIONCHOOSE, and seat 2 — whom
#// the old code always asked — must have no decision at all.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer() and this reds (the choice lands on seat 2).

## GIVEN
CommonSetup: rrk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: LOF_065:1:0
WithP2GroundArena: SOR_095:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P3B
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HASDECISION
P2NODECISION
P4NODECISION
