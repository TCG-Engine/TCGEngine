# OnAttack_OpponentAutoDefeatsNonLeader
#// SOR_040 Avenger "Hunting Star Destroyer" (Unit, cost 9, [Vigilance][Villainy],
#// Imperial/Vehicle/Capital Ship, UNIQUE, 8/8 Space) — "When Played/On Attack: An opponent chooses a
#// non-leader unit they control. Defeat that unit."
#// COVERAGE: offer=Offer_TheChosenOpponentsPoolExcludesTheirLEADERUnitAndAllOfMine (menu asserted on a
#//           PENDING decision in the CHOOSING seat's frame — their deployed leader unit and every unit
#//           of mine are the excluded controls) + TwinSuns_CasterChoosesWhichOpponent /
#//           TwinSuns_OpponentWithNoNonLeaderUnitIsNotOffered (the "an opponent" seat menu) ·
#//           boundary pair=WhenPlayed_OpponentChoosesNonLeader (two legal targets → a real choice) vs
#//           OnAttack_OpponentAutoDefeatsNonLeader (exactly one → auto-resolve) vs
#//           OnlyALeaderUnit_NothingIsDefeated (zero → clean fizzle) · decline=N/A — the printed text
#//           has no "you may" and no cost: the opponent MUST choose one of their non-leader units, so
#//           the only "decline" the rules allow is having nothing legal to pick, which is
#//           OnlyALeaderUnit_NothingIsDefeated · control change=N/A — the ability resolves entirely
#//           through "an opponent" and "a unit THEY control", both of which are read live off the
#//           CONTROLLER at resolution time; there is no "your"-zone clause and nothing is stored, and
#//           the far-seat sections (TwinSuns_ChosenFarSeatIsTheOneThatDefeats) already prove the seat
#//           is resolved from the board rather than from a cached value · request boundary=structural
#//           in every resolving section — the play/attack is one request and the opponent's answer is
#//           a SEPARATE one on a DIFFERENT seat, so the pool is rebuilt from serialized state;
#//           Offer_TheChosenOpponentsPoolExcludesTheirLEADERUnitAndAllOfMine reads it still pending.
#// Both dispatch paths are covered: the When Played window (WhenPlayed_OpponentChoosesNonLeader) and
#// the On Attack window (OnAttack_OpponentAutoDefeatsNonLeader) share one closure in the impl, so each
#// needs its own section.
#// This section — the On Attack window: Avenger attacks the base; the opponent has a
#// single non-leader unit (SEC_080), so the forced choice defeats it directly (no decision), then the
#// 8 combat damage lands on the base.

## GIVEN
CommonSetup: bbk/brw/{
  theirLeader:SOR_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_040:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:8
P1SPACEARENAUNIT:0:EXHAUSTED

---

# WhenPlayed_IdenInteraction
#// SOR_040 Avenger (8/8 Space, cost 9) — the When Played window with a real choice. P1 plays Avenger;
#// the opponent controls TWO non-leader units (SEC_080, SOR_128) and chooses which to defeat. Here the
#// opponent picks myGroundArena-1 (SOR_128), leaving SEC_080 (reindexed to 0). SOR_002/SOR_021 cover
#// Vigilance+Villainy so Avenger plays at its printed cost 9.
#// Iden should be allowed to heal 2 at the end

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P2>AttackGroundArena:0
- P1>UseLeaderAbility
- P2>Claim
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:1

---

# WhenPlayed_OpponentChoosesNonLeader
#// SOR_040 Avenger (8/8 Space, cost 9) — the When Played window with a real choice. P1 plays Avenger;
#// the opponent controls TWO non-leader units (SEC_080, SOR_128) and chooses which to defeat. Here the
#// opponent picks myGroundArena-1 (SOR_128), leaving SEC_080 (reindexed to 0). SOR_002/SOR_021 cover
#// Vigilance+Villainy so Avenger plays at its printed cost 9.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# TwinSuns_CasterChoosesWhichOpponent
#// ⚠ USER-REPORTED BUG + OFFICIAL RULING (03/01/2024, card-specific-rulings.md):
#//   "If there are multiple opponents, the controlling player chooses which one will be 'an opponent.'"
#// SOR_040 reads "AN opponent chooses a non-leader unit they control" — per the Twin Suns player-reference
#// table, "an opponent" means OF YOUR CHOICE, i.e. a real prompt. The shared helper hardcoded
#// OtherPlayer($caster), which at 3+ seats is not a choice at all.
#//
#// This section asserts the MENU, not an answer — a spare answer is silently absorbed, so answering a
#// prompt you never asserted proves nothing. All three opponents field a non-leader unit, so all three
#// must be offered and the CASTER must not be.

## GIVEN
CommonSetup: bbk/brw/{myBase:SOR_021; theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_040
WithP1Resources: 9
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP3Base: SOR_019:0
WithP3GroundArena: SOR_128:1:0
WithP4Base: SOR_019:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1

---

# TwinSuns_OpponentWithNoNonLeaderUnitIsNotOffered
#// ELIGIBILITY = WHO ACTS (shape 1): the chosen opponent acts on their OWN board, so an opponent who
#// cannot make the demanded choice must not appear on the menu (the LAW_216 rule).
#// Seat 3 controls NOTHING, so it is not a legal "an opponent" here. Seats 2 and 4 both qualify — TWO
#// eligible, which is required or the picker correctly auto-resolves and there is no menu to assert.

## GIVEN
CommonSetup: bbk/brw/{myBase:SOR_021; theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_040
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1OPTIONHAS:P2
P1OPTIONHAS:P4
P1OPTIONNOT:P3

---

# TwinSuns_ChosenFarSeatIsTheOneThatDefeats
#// ★ THE DISCRIMINATING SECTION — it cannot pass at two seats, and it cannot pass under the bug.
#// P1 picks seat 4. Only seat 4's unit dies; seats 2 and 3 are untouched.
#// Under the old OtherPlayer($caster) hardcode the defeat always landed on SEAT 2, so this section reds
#// with the exact wrong-seat signature. Reverting the fix must red THIS section and leave the 2-player
#// ones green.

## GIVEN
CommonSetup: bbk/brw/{myBase:SOR_021; theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_040
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP3Base: SOR_019:0
WithP3GroundArena: SOR_128:1:0
WithP4Base: SOR_019:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P4
- P4>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P4GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:SOR_128

---

# Offer_TheChosenOpponentsPoolExcludesTheirLEADERUnitAndAllOfMine
#// THE OFFER CELL, asserted as a MENU on a PENDING decision (answering only proves the branch, never
#// the pool). "An opponent chooses a NON-LEADER unit THEY control" carries two restrictions and both
#// are exercised on one board:
#//   P2's myGroundArena-0 SEC_080  non-leader, theirs → legal
#//   P2's myGroundArena-1 SOR_128  non-leader, theirs → legal (two, or the pick auto-resolves)
#//   P2's myGroundArena-2 SOR_014  their DEPLOYED LEADER unit → EXCLUDED by "non-leader"
#//   P1's SOR_095                  a non-leader unit, but NOT one they control → EXCLUDED
#// The offer belongs to P2 — the choosing seat — so the pool is read in P2's frame.
#// ⚠ Deployed leader units seat at the END of the arena, hence index 2.

## GIVEN
CommonSetup: bbk/brw/{
  theirLeader:SOR_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP1Resources: 9
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# OnlyALeaderUnit_NothingIsDefeated
#// THE NO-VALID-TARGET CELL, and the proof that the "non-leader" restriction is load-bearing rather
#// than decorative. P2's ONLY unit is their deployed leader (SOR_014 Sabine Wren, 2/5). "An opponent
#// chooses a non-leader unit they control" has an empty pool, so the effect fizzles: the leader unit
#// survives untouched, Avenger still enters play, and neither seat is left holding a decision.
#// Contrast OnAttack_OpponentAutoDefeatsNonLeader, where the single legal target IS defeated — the
#// only difference between the two boards is whether that unit is a leader.

## GIVEN
CommonSetup: bbk/brw/{
  theirLeader:SOR_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP1Resources: 9

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_040
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
P2NODECISION
