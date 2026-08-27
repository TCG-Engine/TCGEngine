# ClauseOne_DamagedAndSurvives_EachPlayerDraws
#// COVERAGE: offer=N/A (neither clause has a target pool — clause 1 loops every live seat, clause 2's
#//           "their base" is DETERMINED by whoever drew, so there is nothing to choose in either)
#//           decline=N/A (no "may"/"up to" anywhere — both clauses are mandatory)
#//           boundary=ClauseTwo_DrawingTwoCardsAtOnceIsOneTrigger (1-or-more is per EVENT, not per card)
#//                    + ClauseOne_DamagedAndDefeated_NoDraw (the survives edge)
#//           control=ControlChange_ClausesFollowTheNewController
#//           reqboundary=RequestBoundary_DrawChainSurvivesTheBoundary
#//           modes=2P,TwinSuns (clause 1 is "EACH PLAYER" — a loop over every live seat; clause 2 is "an
#//                 OPPONENT", which at 4 seats must consider every opponent of the DRAWING player, not
#//                 OtherPlayer(). TwinSuns_EachPlayerIncludesFarSeats covers both and cannot pass at two
#//                 seats.) · TeamSuns=partly — clause 2 says "an OPPONENT", and OpponentsOf() excludes a
#//                 TEAMMATE, so a teammate's draw must NOT punish them: TeamSuns_TeammateDrawIsNotPunished.
#//
#// HMW_169 Crosshair, I've Changed (5 cost, 5/6, Aggression/Heroism, Clone, Legendary)
#//   "When this unit is dealt damage and survives: Each player draws a card.
#//    When an opponent draws 1 or more cards during the action phase: Deal 2 damage to their base."
#//
#// ⚠ The two clauses FEED EACH OTHER and that is the card: clause 1 makes the OPPONENT draw, which is
#// exactly what clause 2 punishes. This first section isolates clause 1's draw and shows the 2 damage
#// landing on P2's base as the knock-on — P1's own draw must NOT punish P1 ("an OPPONENT", not "a
#// player"), so P1BASEDMG stays 0.

## GIVEN
CommonSetup: rrw/grk/{myResources:8}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046 SOR_128]
WithP2Deck: [SEC_080 LAW_124 SOR_128]
WithP1GroundArena: HMW_169:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_169
P1GROUNDARENAUNIT:0:DAMAGE:3
P1HANDCOUNT:1
P2HANDCOUNT:1
P2BASEDMG:2
P1BASEDMG:0

---

# ClauseOne_DamagedAndDefeated_NoDraw
#// ⚠ THE NEGATIVE for clause 1's "and survives". LAW_124 (4/7) attacks a Crosshair pre-damaged to 3, so
#// the 4 is lethal (3+4 >= 6). Nobody draws and nobody's base is touched.

## GIVEN
CommonSetup: rrw/grk/{myResources:8}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046 SOR_128]
WithP2Deck: [SEC_080 LAW_124 SOR_128]
WithP1GroundArena: HMW_169:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:0
P1BASEDMG:0
P2BASEDMG:0

---

# ClauseTwo_OpponentDrawsFromTheirOwnCard_BaseTakesTwo
#// Clause 2 standing alone, with clause 1 nowhere near it: P2 plays a card that makes P2 draw, and P1's
#// Crosshair punishes it. Proves clause 2 is a genuine field observer on ANY draw, not just a rider on
#// clause 1's own draw.
#// SOR_111 Patrolling V-Wing has "When Played: Draw a card." (Command, cost 2 under a Command base.)

## GIVEN
CommonSetup: rrw/ggw/{theirResources:8;theirhandCardIds:SOR_111}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: SOR_095
WithP2Deck: [SEC_080 LAW_124]
WithP1GroundArena: HMW_169:1:0

## WHEN
- P2>PlayHand:0
- P1>Drain

## EXPECT
P2BASEDMG:2
P1BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:HMW_169
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ClauseTwo_YourOwnDrawIsNotPunished
#// ⚠ THE NEGATIVE for clause 2's "an OPPONENT". P1 — Crosshair's own controller — draws, and nothing
#// happens to either base. A hook written as "when A PLAYER draws" (cf. SEC_159 Chairman Papanoida,
#// which really is any-player) would hit P1's own base here.

## GIVEN
CommonSetup: rrw/grk/{myResources:8;handCardIds:SOR_111}
P1OnlyActions: true
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: SEC_080
WithP1GroundArena: HMW_169:1:0

## WHEN
- P1>PlayHand:0
- P1>Drain

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1HANDCOUNT:1

---

# ClauseTwo_RegroupPhaseDrawIsNotPunished
#// ⚠ THE NEGATIVE for "during the ACTION PHASE". Both players pass to reach regroup and draw their
#// regroup card; clause 2 must stay silent. Without the phase gate P2's base would take 2 here.
#// ⚠ Decks are seeded for BOTH players — an empty deck at regroup is CR 6.1 base damage, which would
#// put damage on a base for an entirely unrelated reason and read like the clause firing.

## GIVEN
CommonSetup: rrw/grk
P1OnlyActions: true
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046 SOR_128 LAW_124 SEC_080 SOR_111]
WithP2Deck: [SEC_080 LAW_124 SOR_128 SOR_095 SOR_046 SOR_111]
WithP1GroundArena: HMW_169:1:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain
- P2>Drain

## EXPECT
P1BASEDMG:0
P2BASEDMG:0

---

# ClauseTwo_DrawingTwoCardsAtOnceIsOneTrigger
#// ⚠ THE BOUNDARY. "Draws 1 OR MORE cards" is per DRAW EVENT, not per card — a two-card draw is ONE
#// trigger and 2 damage, not 4. A per-card implementation is invisible on every single-card draw, which
#// is every other section in this file.
#// ASH_185 Intimidation is "If you control a unit with 4 or more power, draw 2 cards." — P2 seeds
#// LAW_124 (4/7) to satisfy it. One PlayHand, one draw event, two cards, ONE trigger.

## GIVEN
CommonSetup: rrw/rrk/{theirResources:4;theirhandCardIds:ASH_185}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: SOR_095
WithP2Deck: [SEC_080 LAW_124 SOR_128]
WithP1GroundArena: HMW_169:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>PlayHand:0
- P1>Drain

## EXPECT
P2HANDCOUNT:2
P2BASEDMG:2

---

# BothClauses_MirrorMatch_EachCrosshairPunishesTheOther
#// The full engine of the card, and the cleanest proof that clause 2 keys off the DRAWING player rather
#// than a fixed seat. Both players control a Crosshair. P2 attacks P1's Crosshair, which survives, so
#// EACH player draws — P1's draw is punished by P2's Crosshair and P2's draw by P1's, so BOTH bases take
#// exactly 2.
#// ⚠ Only P1's Crosshair was damaged, so only ONE clause-1 draw round happens: 2 damage per base, not 4.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046 SOR_128]
WithP2Deck: [SEC_080 LAW_124 SOR_128]
WithP1GroundArena: HMW_169:1:0
WithP2GroundArena: HMW_169:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:1:0
- P1>Drain
- P2>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_169
P1GROUNDARENAUNIT:0:DAMAGE:3
P1HANDCOUNT:1
P2HANDCOUNT:1
P1BASEDMG:2
P2BASEDMG:2

---

# ShieldOnCrosshair_NoDamageDealt_NoDraw
#// A Shield prevents the damage, so no damage is DEALT and clause 1 never fires — the same
#// "dealt damage" vs "survives" separation HMW_211 Tech needs. Crosshair lives either way, which is
#// exactly why this is a different test from the defeat negative.

## GIVEN
CommonSetup: rrw/grk/{myResources:8}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SEC_080 LAW_124]
WithP1GroundArena: HMW_169:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:0
P2BASEDMG:0

---

# ClauseOne_AbilityDamage_AlsoTriggers
#// "Dealt damage", not "dealt combat damage" — the same wording as HMW_211 Tech, settled by the official
#// Jabba the Hutt (Wonderful Human Being) ruling. Open Fire (SOR_172) deals 4 to Crosshair, which
#// survives at 4 of 6, and everyone draws.

## GIVEN
CommonSetup: rrw/rrk/{theirResources:4;theirhandCardIds:SOR_172}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SEC_080 LAW_124]
WithP1GroundArena: HMW_169:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1HANDCOUNT:1
P2HANDCOUNT:1
P2BASEDMG:2

---

# LostAllAbilities_NeitherClauseFires
#// Both clauses are Crosshair's OWN abilities, so a blanked Crosshair does nothing — it neither draws
#// off being damaged nor punishes a draw. Force Lightning (SOR_138) blanks it AND deals it 2 in one
#// card (P2 controls LOF_230, a Force unit, and pays 1), so every precondition of clause 1 is met except
#// the ability existing.

## GIVEN
CommonSetup: rrw/rrk/{theirResources:4;theirhandCardIds:SOR_138}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SEC_080 LAW_124]
WithP1GroundArena: HMW_169:1:0
WithP2GroundArena: LOF_230:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:1
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_169
P1GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:0
P2HANDCOUNT:0
P1BASEDMG:0
P2BASEDMG:0

---

# ControlChange_ClausesFollowTheNewController
#// "EACH player draws" is seat-agnostic, but clause 2's "an OPPONENT" is measured from whoever CONTROLS
#// Crosshair. P2 steals P1's Crosshair with Change of Heart (SOR_224), then damages it with Open Fire:
#// each player still draws, but now P1 is the opponent — so P1's base takes the 2 and P2's does not.
#// ⚠ Under the original controller this is the exact mirror of the opening section, so the two together
#// pin the direction.

## GIVEN
CommonSetup: rrw/yyk/{theirResources:14}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SEC_080 LAW_124]
WithP1GroundArena: HMW_169:1:0
WithP2Hand: SOR_224
WithP2Hand: SOR_172

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P2>Drain
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_169
P2GROUNDARENAUNIT:0:DAMAGE:4
P1BASEDMG:2
P2BASEDMG:0

---

# TwinSuns_EachPlayerIncludesFarSeats
#// ⚠ CANNOT PASS AT TWO SEATS, and it pins BOTH clauses at once.
#// ⚠⚠ CROSSHAIR IS ON SEAT 3, AND THAT IS THE WHOLE POINT. OtherPlayer(n) answers 1 for every seat but
#// seat 1, so a Crosshair on seat 1 makes the legacy two-seat shape give the CORRECT answer for every
#// drawing seat — the section would pass under the very bug it exists to catch. Parked on seat 3, the
#// legacy shape looks at seat 1 (and, for seat 1, at seat 2), finds no Crosshair anywhere, and leaves
#// all four bases clean.
#// Clause 1 — "EACH PLAYER draws a card" is a loop over every LIVE seat, so all four end holding a card.
#// Clause 2 — "an OPPONENT" is evaluated against the opponents of the DRAWING player, so seat 3's
#// Crosshair punishes seats 1, 2 and 4 while its own controller's base stays clean.

## GIVEN
CommonSetup: rrw/rrk/{theirResources:4;theirhandCardIds:SOR_172}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 2
SkipPreGame: true
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SEC_080 LAW_124]
WithP3Deck: [SOR_128 SOR_111]
WithP4Deck: [SOR_046 SOR_095]
WithP3GroundArena: HMW_169:1:0

## WHEN
- P2>PlayHand:0
- P3>Drain
- P1>Drain
- P2>Drain
- P4>Drain

## EXPECT
P3GROUNDARENAUNIT:0:CARDID:HMW_169
P3GROUNDARENAUNIT:0:DAMAGE:4
P1HANDCOUNT:1
P2HANDCOUNT:1
P3HANDCOUNT:1
P4HANDCOUNT:1
P3BASEDMG:0
P1BASEDMG:2
P2BASEDMG:2
P4BASEDMG:2

---

# TeamSuns_TeammateDrawIsNotPunished
#// ⚠ "An OPPONENT draws" — a TEAMMATE is never an opponent, so seat 3 (seat 1's partner, since teams are
#// seat parity) drawing must NOT damage seat 3's base, while seats 2 and 4 still take their 2.
#// Clause 1 still makes EVERY player draw, teammate included — "each player" is not team-scoped.
#// This is the one place the two seat-relations genuinely differ, so it is the only Team Suns section.

## GIVEN
CommonSetup: rrw/grk/{myResources:8}
WithTeams: true
WithGamePhase: ActionPhase
WithActivePlayer: 2
SkipPreGame: true
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SEC_080 LAW_124]
WithP3Deck: [SOR_128 SOR_111]
WithP4Deck: [SOR_046 SOR_095]
WithP1GroundArena: HMW_169:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:p1GroundArena-0
- P1>Drain
- P2>Drain
- P3>Drain
- P4>Drain

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1
P3HANDCOUNT:1
P4HANDCOUNT:1
P1BASEDMG:0
P3BASEDMG:0
P2BASEDMG:2
P4BASEDMG:2

---

# RequestBoundary_DrawChainSurvivesTheBoundary
#// ⚠ THE REQUEST-BOUNDARY CELL. Clause 1's reaction is queued as a CUSTOM while combat is still
#// resolving and drains in a later request, and the draws it makes then fire clause 2 on other seats'
#// queues. Nothing may be held in memory across that. Byte-identical to the opening section apart from
#// the boundary inserted before the drain.

## GIVEN
CommonSetup: rrw/grk/{myResources:8}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046 SOR_128]
WithP2Deck: [SEC_080 LAW_124 SOR_128]
WithP1GroundArena: HMW_169:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1HANDCOUNT:1
P2HANDCOUNT:1
P2BASEDMG:2
P1BASEDMG:0

---

# LostAllAbilities_ClauseTwoAlsoStopsPunishing
#// ⚠ Clause 2 needs its OWN blanked test: LostAllAbilities_NeitherClauseFires reaches Crosshair through
#// clause 1 (damage it, watch nothing draw), which leaves clause 2's gate completely unexercised.
#// Here Crosshair is blanked but NEVER damaged — P2 controls no Force unit, so Force Lightning's second
#// sentence is skipped and only the "loses all abilities" half resolves. P2 then draws off SOR_111 and
#// must take nothing.

## GIVEN
CommonSetup: rrw/rrk/{theirResources:8}
WithActivePlayer: 2
SkipPreGame: true
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SEC_080 LAW_124]
WithP1GroundArena: HMW_169:1:0
WithP2Hand: SOR_138
WithP2Hand: SOR_111

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_169
P1GROUNDARENAUNIT:0:DAMAGE:0
P2HANDCOUNT:1
P1BASEDMG:0
P2BASEDMG:0
