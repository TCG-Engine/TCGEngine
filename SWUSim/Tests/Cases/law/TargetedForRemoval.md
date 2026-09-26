# OppCreditsOnDefeat
#// LAW_141 Targeted For Removal (Upgrade, +0/+0) — grants "When Defeated: An opponent creates Credit
#// tokens equal to this unit's cost." SEC_080 (cost 2) wears it and attacks the 8/8 SOR_039, dying. Its
#// granted When Defeated fires → P2 (the opponent) creates 2 Credit tokens (= SEC_080's cost).

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2CREDITCOUNT:2

---

# ReturnedToHand_NoCredits
#// LAW_141 Targeted For Removal — the granted ability is "When Defeated", so returning the attached unit to
#// hand (not a defeat) does NOT trigger it. P2's SEC_080 wears the upgrade; P1 plays Waylay (SOR_222) to
#// bounce it to P2's hand. No Credit tokens are created for either player.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:LAW_141
WithP1Hand: SOR_222

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# NGORDefeat_CreditsGoToTheDefeatedControllersOpponent
#// Control changes decide the payout direction: P1 plays No Glory, Only Results on P2's TIE/ln
#// carrying Targeted for Removal, takes control, and defeats it. At defeat the host's controller is
#// P1, so "the attached unit's controller's opponent" — P2 — creates Credits equal to the host's
#// printed cost (1). P1 gets nothing.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:LAW_141
WithP1Hand: JTL_043

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P1CREDITCOUNT:0
P2CREDITCOUNT:1

---

# AttachPool_AnyUnitEitherSideEitherArena
#// LAW_141 Targeted For Removal — the card prints no attach restriction at all, so its legal-host pool is
#// every unit in play regardless of controller or arena (CR 2.e). The grant is a DRAWBACK for the host's
#// controller, so the enemy half of that pool is the whole point of the card and must not be silently
#// narrowed to friendly. Discriminating board: a friendly ground unit, a friendly space unit, an enemy
#// ground unit and an enemy space unit are all legal hosts. Every other section seeds the upgrade
#// directly, so none of them exercises the attach path.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_141

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# CreditsScaleWithTheHostsPrintedCost_SixCostHost
#// LAW_141 Targeted For Removal — "Credit tokens equal to THIS UNIT's cost" reads the host's printed cost,
#// so the payout has to move with the host. Boundary partner of OppCreditsOnDefeat, which uses the cost-2
#// SEC_080 and produces 2 Credits: here the host is the cost-6 SOR_232 AT-ST and the same defeat produces
#// 6. A payout hardcoded to a constant, or one reading the upgrade's own cost, passes one of these two
#// sections and fails the other.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2CREDITCOUNT:6

---

# TokenHost_ZeroPrintedCost_NoCreditsAtAll
#// LAW_141 Targeted For Removal — the zero end of the same scale. A TWI_T01 Battle Droid token has a
#// printed cost of 0, so its defeat creates NO Credits: "equal to this unit's cost" must be allowed to
#// evaluate to zero rather than falling back to a minimum of one. The trigger still fires — it simply
#// creates nothing — so a section asserting a count of 0 is the only thing that can tell the two apart.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# UpgradeRemovedBeforeTheHostDies_NoCredits
#// LAW_141 Targeted For Removal — the When Defeated is GRANTED BY the upgrade, so removing the upgrade
#// removes the grant: a host that later dies with the upgrade already gone pays nothing. P1 plays
#// SOR_251 Confiscate to defeat the upgrade off its own SEC_080, then attacks into the 8/8 SOR_039 and
#// dies. Without this negative, a grant that was registered once and never revoked would look correct in
#// every other section here.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: SOR_039:1:0
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# ThreeSeat_ChoosesWhichOpponentGetsTheCredits
#// "AN OPPONENT creates Credit tokens equal to this unit's cost." With more than one opponent the
#// controlling player CHOOSES which one (official ruling), and here that choice matters: the Credits are
#// a gift to the enemy, so handing them to the wrong seat is a real loss.
#//
#// ⚠ The trigger called `SWUCreateCreditToken(OtherPlayer($player), $cost)` — seat 2, with no prompt,
#// for any seat-1 host. Seat 2 got free Credits without being involved in the combat at all. Two seats
#// cannot show it, which is why OppCreditsOnDefeat above passes either way.

## GIVEN
CommonSetup3P: rrk/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP3GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:P3G0

## EXPECT
SEATCOUNT:3
P1GROUNDARENACOUNT:0
P1HASDECISION
P2CREDITCOUNT:0
P3CREDITCOUNT:0

---

# ThreeSeat_CreditsGoToThePickedSeat
#// Resolution half: P1 picks seat 3, so seat 3 creates the 2 Credits (SEC_080's cost) and seat 2 — whom
#// the old one-seat read would have paid — gets nothing.

## GIVEN
CommonSetup3P: rrk/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP3GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:P3G0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P3CREDITCOUNT:2
P2CREDITCOUNT:0

---

# FourSeat_CreditsGoToThePickedSeatOfTHREE
#// 4P sibling: the pick is now a real menu of THREE opponents, and picking seat 4 must leave both other
#// opponents at zero. At three seats "the chosen one" and "the only other one" cannot be told apart.

## GIVEN
CommonSetup4P: rrk/rrk/rrk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP3GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:P3G0
- P1>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P4CREDITCOUNT:2
P2CREDITCOUNT:0
P3CREDITCOUNT:0
