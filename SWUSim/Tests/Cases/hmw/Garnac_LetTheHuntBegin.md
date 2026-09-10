# OpponentControlsAUniqueUnit_GainsHidden
#// HMW_104 Garnac, Let the Hunt Begin! (Unit, Ground, cost 1, 3/1, [Command][Villainy], Underworld,
#// unique) — "While an opponent controls a Unique unit, this unit gains Hidden. / When Attack Ends: You
#// may attack with another unit."
#//
#// COVERAGE: offer=AttackEnd_OfferIsReadyFriendlyUnitsOnly (SELECTABLEEXACT — excludes an exhausted
#//           friendly unit and a ready ENEMY unit; includes a space unit) ·
#//           decline=AttackEnd_Decline_SecondUnitStaysReady ·
#//           boundary=HiddenEndsWhenTheOpponentsLastUniqueUnitLeaves (the condition switching off
#//           mid-phase; the condition itself has no numeric threshold) ·
#//           control=AStolenUniqueUnitCountsForItsCONTROLLER + AUniqueUnitStolenFromTheOpponentDoesNotCount
#//           + StolenGarnac_ReadsHisCONTROLLERsOpponents + AttackEnd_StolenGarnac_OffersHisControllersUnits ·
#//           reqboundary=RequestBoundary_HiddenStillHoldsInTheNextRequest +
#//           AttackEnd_RequestBoundaryBeforeTheAnswer ·
#//           modes=2P,TwinSuns,TeamSuns — "an OPPONENT controls" is a player reference (existential over
#//           every opponent — TwinSuns_OnlyTheFarSeatHasAUniqueUnit) and "opponent" excludes a teammate
#//           (TeamSuns_ATeammatesUniqueUnitDoesNotCount).
#//           "another" (clause 2) = N/A as a section: the attack's Begin-attack step always exhausts Garnac
#//           and the offer is READY units only, so no printed card can make him an offerable candidate;
#//           the handler still excludes him by UniqueID.
#//
#// Hidden (CR 7.5.18.a): "This unit can't be attacked if it was played/deployed/created this phase." So
#// Garnac must be PLAYED for the grant to matter — a seeded unit carries no entered-this-phase flag and is
#// attackable with or without Hidden. ATTACKTARGETS counts P2's legal targets: Battlefield Marine + P1's
#// base = 2 with Garnac hidden, 3 without. P2's unique unit is SEC_046 Galen Erso, seeded (so his When
#// Played naming never happens).

## GIVEN
CommonSetup: ggk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1Hand: HMW_104
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_104
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
ATTACKTARGETS:2:G:0:2

---

# NoOpponentUniqueUnit_NoHidden_EvenWithAUniqueUnitOfYourOwn
#// HMW_104 — the NEGATIVE, and it catches two wrong readings at once. P2 controls only a non-unique Dark
#// Trooper. Garnac is himself unique and P1 also controls SEC_046 Galen Erso (unique) — so "any unique unit
#// in play" or "another unique unit" both grant Hidden here, and only "an OPPONENT controls" does not.
#// P2's targets: Galen + Garnac + P1's base = 3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1Hand: HMW_104
WithP1GroundArena: SEC_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_104
P1GROUNDARENAUNIT:1:NOTKEYWORD:Hidden
ATTACKTARGETS:2:G:0:3

---

# AnOpponentsDeployedLeaderUnitIsAUniqueUnit
#// HMW_104 — VALUE CLASS: a leader is unique, so an opponent's deployed LEADER UNIT satisfies the
#// condition. P2's only unit is its deployed leader; P2's targets are Marine + base = 2 (Garnac hidden).

## GIVEN
CommonSetup: ggk/rrk/{myResources:1;theirLeaderDeployed:true}
WithActivePlayer: 1
WithP1Hand: HMW_104
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
ATTACKTARGETS:2:G:0:2

---

# HiddenEndsWhenTheOpponentsLastUniqueUnitLeaves
#// HMW_104 — "WHILE an opponent controls" is continuous, so the grant ends mid-phase when the condition
#// does. Garnac is played (Hidden, per the first section's identical board), then P1's Marine attacks and
#// kills P2's pre-damaged Galen (and dies to the counter). P2's remaining Dark Trooper can now target
#// Garnac: Garnac + P1's base = 2 (still hidden would be 1).

## GIVEN
CommonSetup: ggk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_104
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_046:1:4
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_104
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden
ATTACKTARGETS:2:G:0:2

---

# AStolenUniqueUnitCountsForItsCONTROLLER
#// HMW_104 — CONTROL, direction 1: P2 controls a unique Galen Erso that P1 OWNS. "An opponent CONTROLS a
#// Unique unit" is true, so Garnac is hidden. (An owner-based read finds P1 as Galen's owner and says no.)
#// P2's attacker is the stolen Galen: targets Marine + base = 2.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1Hand: HMW_104
WithP1GroundArena: SOR_095:1:0
WithP2GroundArenaControlled: SEC_046:1

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_046
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
ATTACKTARGETS:2:G:0:2

---

# AUniqueUnitStolenFromTheOpponentDoesNotCount
#// HMW_104 — CONTROL, direction 2: P1 controls a unique Galen Erso that P2 OWNS, and P2 controls nothing
#// unique. No opponent controls a unique unit, so no Hidden. (An owner-based read says P2 owns one.)
#// P2's Dark Trooper targets Garnac + the stolen Galen + P1's base = 3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1Hand: HMW_104
WithP1GroundArenaControlled: SEC_046:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_046
P1GROUNDARENAUNIT:1:CARDID:HMW_104
P1GROUNDARENAUNIT:1:NOTKEYWORD:Hidden
ATTACKTARGETS:2:G:0:3

---

# StolenGarnac_ReadsHisCONTROLLERsOpponents
#// HMW_104 — CONTROL of Garnac himself. P2 controls a Garnac that P1 OWNS, and P2 also controls a unique
#// Galen; P1 controls nothing unique. "An opponent" is relative to Garnac's CONTROLLER (P2), whose only
#// opponent (P1) has no unique unit → no Hidden. Read relative to his OWNER (P1), the opponent would be P2,
#// who controls two unique units → Hidden. The two readings disagree only on this board.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_046:1:0
WithP2GroundArenaControlled: HMW_104:1

## WHEN

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:HMW_104
P2GROUNDARENAUNIT:1:NOTKEYWORD:Hidden

---

# ATokenIsNotUnique
#// HMW_104 — VALUE CLASS negative: a token unit is never unique. P2 controls only a Battle Droid token,
#// so no Hidden; the token targets Marine + Garnac + base = 3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1Hand: HMW_104
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Hidden
ATTACKTARGETS:2:G:0:3

---

# ACloneCopyOfAUniqueUnitIsNotUnique
#// HMW_104 — VALUE CLASS negative: TWI_116 Clone "enters play as a copy … except it gains the Clone trait
#// and is NOT unique". P2 plays a Clone copying P1's Captain Tarpals (a unique card), so P2's only unit
#// carries a unique CardID but is not a unique unit → no Hidden. A read of the dictionary flag alone says
#// yes. (P1's own Tarpals is unique but is P1's, so it never counts.)

## GIVEN
CommonSetup: ggk/ggw/{theirResources:11}
WithActivePlayer: 2
WithP2Hand: TWI_116
WithP1GroundArena: HMW_104:1:0
WithP1GroundArena: HMW_254:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_254
P2GROUNDARENAUNIT:0:HASTRAIT:Clone
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden

---

# RequestBoundary_HiddenStillHoldsInTheNextRequest
#// HMW_104 — the REQUEST-BOUNDARY cell in its no-decision form: Garnac is played in one request and the
#// opponent's attack options are read in the next. Both halves of Hidden must survive it — the grant
#// (recomputed live from the board) and the played-this-phase flag (a GlobalEffect).

## GIVEN
CommonSetup: ggk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1Hand: HMW_104
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
ATTACKTARGETS:2:G:0:2

---

# LostAbilities_NoHidden
#// HMW_104 — clause-1 gate. Garnac carrying SOR_138 Force Lightning's lose-abilities marker gains nothing,
#// even with an opponent's unique unit on the table (the central SWUKeywordSuppressed gate).

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0:SOR_138
WithP2GroundArena: SEC_046:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden

---

# TwinSuns_OnlyTheFarSeatHasAUniqueUnit
#// ⚠ HMW_104 — "an opponent" is existential over EVERY opponent. Seat 2 controls only a non-unique Dark
#// Trooper; seat 3 controls the only unique unit. Garnac is hidden. A two-seat read (OtherPlayer = seat 2)
#// says no — this section cannot pass at two seats.

## GIVEN
CommonSetup: ggk/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: HMW_104:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_046:1:0

## WHEN

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# TeamSuns_ATeammatesUniqueUnitDoesNotCount
#// ⚠ HMW_104 — a TEAMMATE is never an opponent. P1's partner (seat 3) controls a unique Galen; both enemy
#// seats (2 and 4) control only non-unique units. No opponent controls a unique unit → no Hidden. A read
#// over "every other seat" says yes.

## GIVEN
CommonSetup: ggk/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: HMW_104:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SEC_046:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Hidden

---

# AttackEnd_MayAttackWithAnotherUnit
#// HMW_104 — clause 2, positive, through the real attack path. Garnac attacks P2's base (3), then attacks
#// with the Battlefield Marine (3) → 6. Run WITHOUT P1OnlyActions so the action close is observable: the
#// chained attack belongs to Garnac's action, so the turn passes to P2 exactly once (TURNPLAYER:2 +
#// NOEXTRAACTION), and the Marine's own attack does not re-offer (P1NODECISION).
#// With a single legal unit the offer still prompts — it is a "you may" (MZMAYCHOOSE never auto-resolves).

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# AttackEnd_Decline_SecondUnitStaysReady
#// HMW_104 — the DECLINE: the Marine stays ready, only Garnac's 3 lands, and the action still closes once.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:READY
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# AttackEnd_NoOtherReadyUnit_NoPrompt
#// HMW_104 — NO VALID TARGET: the only other friendly unit is exhausted, so nothing is offered and no
#// decision dangles; the action closes normally.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
TURNPLAYER:2

---

# AttackEnd_OfferIsReadyFriendlyUnitsOnly
#// HMW_104 — the OFFER. "Attack with another unit" = a READY FRIENDLY unit (the attack rules), in either
#// arena. Board: a ready Marine (ground 1), an exhausted Death Star Stormtrooper (ground 2 — excluded),
#// a ready X-Wing (space 0 — included), and P2's ready Dark Trooper (enemy — excluded). Left pending.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:0:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0

---

# AttackEnd_FiresEvenWhenGarnacDies
#// ⚠ HMW_104 — CR 7.6.16.c: a "When Attack Ends" ability still triggers if its unit is defeated by combat
#// damage, and the released analogue's official ruling says the same for exactly this clause (TS26_04
#// Padmé Amidala: "Padme does not need to survive the attack in order to attack with another unit").
#// Garnac (3/1) and P2's Dark Trooper (3/3) trade; the Marine — now at ground index 0 — is still offered
#// and attacks the base.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2BASEDMG:3
TURNPLAYER:2
NOEXTRAACTION

---

# AttackEnd_RequestBoundaryBeforeTheAnswer
#// HMW_104 — the REQUEST-BOUNDARY cell: the positive section with a boundary between the offer and its
#// answer. Nothing may be parked in memory across it (the chained-attack continuation rides the decision).

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:1:EXHAUSTED
TURNPLAYER:2
NOEXTRAACTION

---

# AttackEnd_StolenGarnac_OffersHisControllersUnits
#// HMW_104 — CONTROL, clause 2: P1 controls a Garnac that P2 OWNS. "You" is the controller, so the offer
#// is P1's ready Marine — not P2's ready Dark Trooper, and the decision is P1's. (Controlled units sort
#// after plain ones: Marine ground 0, Garnac ground 1.)

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: HMW_104:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_104
P1SELECTABLEEXACT:myGroundArena-0
P2NODECISION

---

# AttackEnd_LostAbilities_NoOffer
#// HMW_104 — clause-2 gate (a card-wide gate needs one section per clause: the Hidden one above reaches
#// Garnac through the keyword path, this one through the attack-end collector). A blanked Garnac attacks
#// and offers nothing; the Marine stays ready.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: HMW_104:1:0:SOR_138
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:READY
P1NODECISION
