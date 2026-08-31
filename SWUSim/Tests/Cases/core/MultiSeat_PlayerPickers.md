# CORE — "an opponent" vs "a player" pickers at 3+ seats.
#
# Two different pickers, and the difference is INVISIBLE at two seats:
#   `SWUQueueChooseOpponent`  → "an opponent" — OpponentsOf(), so a TEAMMATE IS EXCLUDED
#   `SWUPlayerPickerLabels`   → "a player"    — every live seat except the caster, TEAMMATE INCLUDED
#                                              (an unqualified player reference names everybody)
#
# ⚠ WHY TWO SEATS CANNOT TEST EITHER ONE. `SWUQueueChooseOpponent` emits a silent `PASSPARAMETER` when
# exactly one opponent is eligible and an `OPTIONCHOOSE` only at two or more. In Premier there is always
# exactly one opponent, so **the menu is never built and its scope is unobservable** — a picker that
# offered the wrong seats, or the caster themselves, would look identical. Every scope assertion in this
# file therefore needs three seats or more, and each leaves the decision PENDING so the option list can
# be read with P{n}OPTIONHAS / P{n}OPTIONNOT rather than inferred from an outcome.
#
# Cards used, both plain wrappers around the shared helpers rather than special cases:
#   SHD_161 Stolen Landspeeder (cost 1) — "When Played: … an opponent takes control of it" (no filter)
#   SEC_216 Regulations Bureaucrat      — "Action [Exhaust]: Exhaust a resource" (unqualified "a player")

---

# TwoSeat_AnOpponentPicker_AUTORESOLVES_WithNoPrompt
#// The baseline that shows why the rest of this file exists. One eligible opponent → PASSPARAMETER, so
#// control transfers with NO decision ever raised. Nothing here can see WHICH seats a menu would list,
#// because no menu is built.
## GIVEN
CommonSetup2P: rrk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SHD_161
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:2
P1NODECISION
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:0

---

# ThreeSeat_AnOpponentPicker_OffersEveryOpponentAndNeverTheChooser
#// Two eligible opponents → a real OPTIONCHOOSE. Left pending so the option list itself is the
#// assertion. The chooser must never appear in an "an opponent" menu.
## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SHD_161
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:3
P1HASDECISION
P1DECISIONTOOLTIP:Choose_an_opponent_to_take_control_of_it
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P1

---

# FourSeat_AnOpponentPicker_OffersAllThree
## GIVEN
CommonSetup4P: rrk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SHD_161
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1

---

# TeamSuns_AnOpponentPicker_EXCLUDESTheTeammate
#// THE RULES CLAIM, and half of this file's discriminating pair. Seat 3 is seat 1's partner, so "an
#// opponent" must offer only 2 and 4. A teammate leaking in here would let a player hand their own unit
#// to their partner as if it were an opponent's.
## GIVEN
CommonSetup4P: rrk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SHD_161
## WHEN
- P1>PlayHand:0
## EXPECT
OPPONENTSOF:1:2,4
P1OPTIONHAS:P2
P1OPTIONHAS:P4
P1OPTIONNOT:P3
P1OPTIONNOT:P1

---

# EliminatedSeatIsNeverOffered
#// Seat 3 is gone, so the menu lists 2 and 4 only. A pool built from SeatOrder rather than LiveSeats
#// would offer a seat that cannot answer — which is not merely wrong, it soft-locks whatever waits on
#// that seat's queue.
## GIVEN
CommonSetup4P: rrk/bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 124
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SHD_161
## WHEN
- P1>PlayHand:0
## EXPECT
SEATLIVE:3:false
P1OPTIONHAS:P2
P1OPTIONHAS:P4
P1OPTIONNOT:P3

---

# NarrowingToASingleOpponent_AUTORESOLVES_Again
#// The auto-resolve boundary is about how many are ELIGIBLE, not how many seats the table started with.
#// A four-seat game narrowed to one live opponent behaves exactly like Premier: no prompt at all.
## GIVEN
CommonSetup4P: rrk/bbk/bbk/bbk
SkipPreGame: true
WithLiveSeats: 12
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SHD_161
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P1NODECISION
P2GROUNDARENACOUNT:1

---

# ThreeSeat_APlayerPicker_OffersYouAndEveryOtherSeat
#// The OTHER picker. "Exhaust a resource" names no controller, so the menu is "You" plus every other
#// live seat — the caster IS a legal choice here, which is exactly what an "an opponent" menu forbids.
## GIVEN
CommonSetup3P: yyk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_216:1:0
WithP1Resources: 3
WithP2Resources: 3
WithP3Resources: 3
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Exhaust_a_resource_(choose_a_player)
P1OPTIONHAS:You
P1OPTIONHAS:P2
P1OPTIONHAS:P3

---

# TeamSuns_APlayerPicker_INCLUDESTheTeammate
#// THE OTHER HALF OF THE PAIR, and the whole reason both pickers exist. Same table as
#// TeamSuns_AnOpponentPicker_EXCLUDESTheTeammate, same seat 3 — but this is an unqualified "a player",
#// so the teammate IS offered. If these two sections ever agree about P3, one of the two pickers has
#// been pointed at the wrong helper.
## GIVEN
CommonSetup4P: yyk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1GroundArena: SEC_216:1:0
WithP1Resources: 3
WithP2Resources: 3
WithP3Resources: 3
WithP4Resources: 3
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
OPPONENTSOF:1:2,4
P1OPTIONHAS:You
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4

---

# TwoSeat_APlayerPicker_KeepsTheLegacyLabels
#// Premier keeps the historical "You&Opponent" wording rather than "You&P2" — a seat number would be
#// a UI regression for every 2-player game. Pinned so the N-player generalisation cannot quietly
#// rewrite the Premier menu.
## GIVEN
CommonSetup2P: yyk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_216:1:0
WithP1Resources: 3
WithP2Resources: 3
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
SEATCOUNT:2
P1OPTIONHAS:You
P1OPTIONHAS:Opponent
P1OPTIONNOT:P2
