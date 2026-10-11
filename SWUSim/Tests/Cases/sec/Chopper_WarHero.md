# BaseHit_EachPlayerDiscards
#// SEC_147 Chopper (Ground, 4/1) — When this unit deals combat damage to a base: each player discards a
#//   card from their hand. SEC_147 hits P2's base for 4; P1 has exactly 1 card (auto-discards), P2 has 2
#//   and chooses one to discard.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_147:1:0
WithP1Hand: SOR_095
WithP2Hand: SOR_095
WithP2Hand: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:0
P2HANDCOUNT:1

---

# ThreeSeat_EACHPlayerDiscards_IncludingTheFarSeat
#// "EACH PLAYER discards a card from their hand" — an UNQUALIFIED player reference, so it is every live
#// seat, not the active player plus one. Chopper hits seat 3's base; all three seats hold exactly one
#// card, so all three auto-discard to empty. ⚠ The trailing passes drain each non-active seat's queue —
#// a discard offered to a seat that is not acting sits there until that seat runs.
#// The trigger looped the literal pair [$player, OtherPlayer($player)] — two seats — so seat 3 kept its
#// card at three seats and seats 3 AND 4 kept theirs at four.

## GIVEN
CommonSetup3P: rrw/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_147:1:0
WithP1Hand: SOR_095
WithP2Hand: SOR_095
WithP3Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:P3B
- P2>Pass
- P3>Pass

## EXPECT
SEATCOUNT:3
P3BASEDMG:4
P1HANDCOUNT:0
P2HANDCOUNT:0
P3HANDCOUNT:0

---

# FourSeat_EACHPlayerDiscards_AllFourSeats
#// 4P sibling — four hands must empty, which is where "the pair" and "every seat" differ most.

## GIVEN
CommonSetup4P: rrw/rrk/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_147:1:0
WithP1Hand: SOR_095
WithP2Hand: SOR_095
WithP3Hand: SOR_095
WithP4Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:P3B
- P2>Pass
- P3>Pass
- P4>Pass

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P2HANDCOUNT:0
P3HANDCOUNT:0
P4HANDCOUNT:0
