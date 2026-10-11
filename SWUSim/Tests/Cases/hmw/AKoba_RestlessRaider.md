# WhenPlayed_BuffsAFriendlyUnit
#// HMW_052 A'Koba — Restless Raider. Ground Unit, cost 2, 1/4, [Aggression][Cunning].
#// Text: "Raid 1 (This unit gets +1/+0 while attacking.)
#//        When Played: Give a unit +2/+0 for this phase."
#// (Official text, 2026-09-16 flip — the preview mock read +2/+2; the HP halves below were re-pointed.)
#//
#// COVERAGE: offer=Offer_IncludesSelfFriendlyDeployedLeaderAndEnemy
#//           decline=N/A (structural — the clause prints no "may" and no "up to", so the choose is
#//                 mandatory; the only softening is the single-target auto-resolve, covered by
#//                 WhenPlayed_CanBuffItself_AutoResolvesWhenAlone)
#//           boundary=N/A (structural — a flat +2/+0, no threshold and no counted quantity)
#//           control=N/A (structural — the clause names no owner-scoped zone and moves nothing;
#//                 the buff lands on the CHOSEN unit whoever controls it, which is exactly what
#//                 WhenPlayed_CanBuffAnEnemyUnit already proves)
#//           reqboundary=BuffSurvivesRequestBoundary
#//           modes=2P only (no player reference, no friendly/enemy wording — "a unit" is
#//                 board-wide in every format, and the pool comes from _SWUAllUnitsOnly whose
#//                 theirGroundArena/theirSpaceArena searches already fan out at 3-4 seats)
#//
#// ⚠ THE TARGET SET IS THE WHOLE POINT OF THIS CARD. The clause says "a unit" — NOT "another",
#// NOT "friendly". So A'Koba may buff herself, and she may buff an ENEMY unit. Both readings are
#// load-bearing and both are the documented recurring bug shape (JTL_088 Phasma, JTL_120 Dorsal
#// Turret): the neighbouring implementation this is modelled on, IC27_079 Qui-Gon Jinn, says
#// "another friendly unit" and is correctly narrower. Copying its pool would be the bug.
#//
#// ⚠ Raid 1 needs no code — the generator already registered 'HMW_052' => 1 in $Raid_Cards, and
#// the keyword has generic coverage under Tests/Cases/keywords/. It is NOT testable against this
#// card in combination with the buff, either: A'Koba enters play exhausted, so she cannot attack
#// the turn she is played, and the +2/+0 expires at the regroup before she ever readies.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:HMW_052}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:HMW_052
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:4

---

# WhenPlayed_CanBuffAnEnemyUnit
#// "Give a unit +2/+0" carries no friendly qualifier, so an ENEMY unit is a legal target. Buffing
#// the opponent is a real (if rarely correct) play, and the pool must offer it — this is the cell
#// that reds if the handler copies IC27_079's friendly-only arenas.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:HMW_052}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:CARDID:HMW_052
P1GROUNDARENAUNIT:0:POWER:1

---

# WhenPlayed_CanBuffItself_AutoResolvesWhenAlone
#// No "another", so A'Koba is in her own pool. With her as the only unit on the board the
#// mandatory single-target choose auto-resolves through PASSPARAMETER — so the buff lands on her
#// with NO decision left pending, which is the assertion that proves she was in the pool at all.
#//
#// A'Koba's own When Played fires after she is placed, so she is already in play when the pool is
#// built — no special self-inclusion code is needed, but nothing tests it except this section.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:HMW_052}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_052
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1NODECISION

---

# Offer_IncludesSelfFriendlyDeployedLeaderAndEnemy
#// OFFER CELL. Answering a target proves the branch, never the pool — so this section leaves the
#// choose pending and reads the pool itself. The board deliberately holds one of each class the
#// unqualified wording must reach:
#//   myGroundArena-0  a plain friendly unit
#//   myGroundArena-1  a friendly DEPLOYED LEADER (a leader unit is still "a unit")
#//   myGroundArena-2  A'Koba herself, just played
#//   theirGroundArena-0  an enemy unit
#// A pool missing any one of the four is a different card from the one printed.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:HMW_052;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&theirGroundArena-0

---

# BuffedUnitHitsForFive_ThenReadsThreeAfterTheRegroup
#// DURATION CELL. ⚠ "assert printed power after the regroup" alone passes against an UNIMPLEMENTED card, so the
#// same section first proves the buff was real through a consequence: the buffed SOR_046 (3/7 → 5/7) attacks
#// P2's SOR_046 in the same phase and deals FIVE. Only then does "POWER:3 after the regroup" prove expiry.
#// (Rewritten for the official +2/+0: the old +2/+2 form relied on the extra HP keeping a unit alive.)
#// Decks are seeded for both seats: a regroup draw from an empty deck deals 6 to the base.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:HMW_052}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_128 SOR_225 SOR_237 SEC_080 SOR_095]
WithP2Deck: [SOR_046 SOR_128 SOR_225 SOR_237 SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7

---

# BuffAppliesToOnlyTheChosenUnit
#// SCOPE CELL. "Give A unit +2/+0" is singular — it is not a board buff and not a friendly-side
#// buff. With two identical friendly bodies on the table, only the answered one may move.
#//
#// A handler that looped its target pool instead of using the answer would pass every other
#// section in this file.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:HMW_052}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:7

---

# BuffSurvivesRequestBoundary
#// REQUEST-BOUNDARY CELL. The target choose ends the request: A'Koba is placed and her pool is
#// built in one process, and the answer arrives in a fresh one. Anything the handler parked in an
#// in-memory global between those two points would be empty by the time the buff is applied, and
#// the section would show an unbuffed unit rather than an error.
#//
#// Same GIVEN and same answer as the first section — only the boundary line is inserted.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:HMW_052}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3
