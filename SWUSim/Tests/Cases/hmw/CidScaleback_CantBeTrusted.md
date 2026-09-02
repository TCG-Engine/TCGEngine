# OpponentChoosesTheirOwnUnit_ItGetsWeakness
#// HMW_197 Cid Scaleback "Can't Be Trusted" (Unit, Ground, 2/2, cost 2, [Cunning][Villainy], Underworld,
#// unique) — "When Played: An opponent chooses a unit they control. Give a Weakness token to it."
#//
#// COVERAGE: offer=Offer_ItIsTHEIRDecision_AndOnlyTheirOwnUnits (P2SELECTABLEEXACT, with a unit on the
#//           caster's board that must NOT appear) + the two menu assertions in the far-seat sections
#//           decline=N/A (structural: neither half is a "may" or an "up to". The opponent's choose is a
#//           mandatory MZCHOOSE over a PUBLIC zone — their own arena — so the hidden-zone
#//           always-declinable rule does not reach it either)
#//           boundary=N/A (structural: no threshold, no count, no amount anywhere in the text — exactly
#//           one token to exactly one unit)
#//           control=N/A (structural: "a unit THEY control" is control-scoped by construction, and it is
#//           re-derived from the chooser's own live board at resolution rather than from any zone the
#//           caster owns; there is no owner-scoped zone for a control change to send astray)
#//           reqboundary=RequestBoundary_TheOpponentsPickSurvivesIt
#//           modes=2P,TwinSuns,TeamSuns — "an opponent" is a PLAYER REFERENCE, so the caster picks WHICH
#//           opponent (TwinSuns_CasterPicksWhichOpponent_FarSeat) and an opponent who cannot make the
#//           demanded choice is filtered off the menu (TwinSuns_OpponentWithNoUnitsIsNotOffered). Team
#//           Suns is NOT a duplicate here even though the card says no "friendly": in a 2v2 "an
#//           opponent" must exclude your TEAMMATE, which is a different answer SET from the free-for-all
#//           (TeamSuns_TeammateIsNotOnTheMenu).
#//
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md. The one judgement call is ELIGIBILITY —
#// see the Twin Suns sections below; the reading is taken from LAW_216 Jabba's Rancor, whose text is
#// the structural twin ("An opponent chooses a ground unit they control").
#//
#// Positive at two seats: P2 controls two units, so the pick is a real choice on THEIR queue. The unit
#// they name takes a Weakness token (HMW_T02, a −1/−1 Token Upgrade), so the 3/3 Battlefield Marine
#// becomes a 2/2. The unit they did not name is untouched.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_197
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_197
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:7

---

# Offer_ItIsTHEIRDecision_AndOnlyTheirOwnUnits
#// HMW_197 — the OFFER cell, and it carries two claims at once, neither of which answering a target
#// could prove:
#//   (1) the decision belongs to the OPPONENT, not the caster — P2HASDECISION with P1NODECISION;
#//   (2) the pool is "a unit THEY control", so the caster's own board is excluded. P1 fields a unit of
#//       its own here precisely so an unscoped pool would be visibly wrong.
#// The decision is left PENDING so the offer itself can be read. P2's frame, so "my…" means P2's.
#// P2 gets TWO units so the choose cannot auto-resolve — with one it would resolve itself and there
#// would be no offer left to inspect.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_197
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2HASDECISION
P1NODECISION
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# TwinSuns_CasterPicksWhichOpponent_FarSeat
#// ⚠⚠ THE SEAT-COUNT CELL — this section CANNOT PASS AT TWO SEATS, which is the whole point.
#//
#// "AN opponent" is a player reference, so at 3–4 seats it is a real CHOICE: the caster picks which
#// opponent, and only then does that opponent pick a unit. The two-seat shortcut `OtherPlayer($player)`
#// answers 2 for seat 1 and 1 for everyone else, so a card built that way would always weaken P2 no
#// matter who the caster named. Here P1 names P3, and the token must land on P3's board — P2 and P4,
#// who both also control units, must be untouched.
#// The menu assertion runs first: three eligible opponents, and the caster must not be on his own menu.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_197
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P3>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:SOR_046
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P3GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P3GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P4GROUNDARENAUNIT:0:UPGRADECOUNT:0
P4GROUNDARENAUNIT:0:POWER:3

---

# TwinSuns_MenuOffersEveryEligibleOpponentAndNotTheCaster
#// HMW_197 — the MENU itself, left pending. Behaviour alone cannot see an eligibility list: a card can
#// compute one and then fail to pass it (the documented ASH_006 shape, where the code read correctly and
#// only an OPTIONNOT assertion caught it). All three opponents control a unit, so all three are on the
#// menu and the caster is not.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_197
WithP1GroundArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1

---

# TwinSuns_OpponentWithNoUnitsIsNotOffered
#// ⚠ THE JUDGEMENT CALL, and the reason this card needed a ruling read rather than a copy-paste.
#//
#// The eligibility doctrine splits on what the effect asks of the chosen seat. Where something is done
#// TO an opponent, a seat that "can't be affected" must STAY on the menu — aiming at them can be the
#// caster's best line (TS26_43, TWI_222). But where the chosen player has to ACT ON THEIR OWN BOARD, an
#// opponent who cannot make the demanded choice would be choosing among nothing, and must be filtered
#// off. Cid Scaleback is the second shape, exactly like its structural twin LAW_216 Jabba's Rancor:
#// the opponent CHOOSES a unit they control. With no units they have nothing to choose, so naming them
#// would be a menu entry that provably does nothing.
#// ⚠ The counter-argument, recorded because it is not silly: in a free-for-all, deliberately naming a
#// unit-less opponent IS a way to decline the whole effect, and the card gives no "you may". I took the
#// LAW_216 reading — no no-op prompts — but if this is ever ruled the other way, THIS section is the
#// one that changes.
#// P2 controls nothing; P3 and P4 do. The menu must be exactly P3 and P4.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_197
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P2

---

# TeamSuns_TeammateIsNotOnTheMenu
#// HMW_197 — "an OPPONENT", so in a 2v2 your TEAMMATE is not a legal answer. Teams are seat PARITY
#// (1+3 versus 2+4), so P1's partner is P3 and its opponents are P2 and P4 — a different answer SET
#// from the four-seat free-for-all above, which is why this is not a duplicate of the Twin Suns menu
#// section. All three other seats control a unit, so only the team relationship can shorten the menu.
#// ⚠ MEASURED CAVEAT: this section is guarded by SHARED code, not by the card. SWUQueueChooseOpponent
#// intersects the eligibility list it is handed with its own OpponentsOf($chooser), so widening the
#// card's own loop to "every live seat but me" leaves this section GREEN. Keep it as the cross-card
#// guard on that helper — just do not read it as proof of anything in HMW_197's own file.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_197
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P4
P1OPTIONNOT:P3

---

# NoOpponentControlsAUnit_NoPromptAtAll
#// HMW_197 — the no-valid-target cell, and the other half of the eligibility judgement. With NOBODY
#// able to make the choice there is no opponent to name either, so the whole When Played resolves to
#// nothing: no menu for the caster, no choose for the opponent, no dangling decision on either queue.
#// Cid still enters play and is still paid for — the trigger fizzling does not undo the play.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_197

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_197
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0

---

# Weakness_DefeatsAOneHpUnit
#// HMW_197 — the −1 HP consequence. A Weakness token is a stat REDUCTION, not damage, and shrinking a
#// unit to 0 remaining HP has no state-based defeat of its own in this engine — the giver has to run
#// the shrink sweep. SOR_128 Death Star Stormtrooper is a 3/1, so one Weakness takes it to 3/0 and it
#// must leave play; the 3/7 beside it is what proves the sweep did not just wipe the board.
#// (Also the reason the choice is a genuine decision for the opponent: one of their two answers kills
#// their own unit outright.)

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_197
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1

---

# DeployedLeaderUnitIsInTheOpponentsPool
#// HMW_197 — value-CLASS variant. "a unit they control" is unqualified: it does not say "non-leader",
#// so a DEPLOYED LEADER unit is a legal answer. A leader unit's printed CardType is 'Leader', so only
#// the full ['Unit','Token Unit','Leader Unit'] filter finds it — a pool built from ['Unit'] alone
#// would quietly leave a deployed leader off the opponent's own menu.
#// P2's leader is deployed and appends AFTER the seeded unit, so it sits at ground index 1. Asserting
#// the CARDID at that index pins WHICH unit took the token rather than just that something did.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2;
  theirLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_197
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# RequestBoundary_TheOpponentsPickSurvivesIt
#// HMW_197 — the REQUEST-BOUNDARY cell, and this card genuinely needs it: the caster's seat pick and
#// the opponent's unit pick are TWO decisions in two different queues, so in production the continuation
#// that hands the choose to the named opponent, and the one that finally attaches the token, each resume
#// in a fresh process. Anything either of them held in memory rather than on its own decision would be
#// gone. Identical board and answers to the positive; one boundary inserted before the opponent answers.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_197
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# SpaceOnlyOpponentIsEligible_AndTheirSpaceUnitIsChosen
#// HMW_197 — the ARENA literal, and the likeliest way to get this card wrong. Its template LAW_216
#// Jabba's Rancor reads "a GROUND unit they control" and scans only `myGroundArena`; Cid Scaleback says
#// plain "a unit", so BOTH arenas count — for the eligibility scan and for the opponent's pool alike.
#// Copying the twin's ground-only scan would make an opponent whose whole board is in space ineligible,
#// there would be no prompt at all, and every other section here would still pass (they all use ground
#// fixtures). So: P2 controls nothing on the ground and two units in SPACE, and the token lands there.
#// Two space units so the choose is a real prompt rather than an auto-resolve.

## GIVEN
CommonSetup: yyk/rrk/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_197
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:mySpaceArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:HP:2
P2SPACEARENAUNIT:1:CARDID:JTL_069
P2SPACEARENAUNIT:1:UPGRADECOUNT:0
