# Regroup_AggressionTop_DealsOneToAnEnemyUnit
#// HMW_160 Noxious Refinery (Upgrade, cost 4, [Aggression][Villainy], Fortification, NON-unique) —
#// "Fortify (Attach this to your base, not a unit.) / Attached base gains: 'When the regroup phase
#// starts: Reveal the top card of your deck. If it's Aggression, deal 1 damage to an enemy unit.'"
#//
#// COVERAGE: offer=Regroup_EnemyOnly_YourOwnUnitsAreNotOffered (SELECTABLEEXACT — "an ENEMY unit" is a
#//           controller restriction, and with a friendly unit on the board a pool that ignored it would
#//           be visibly longer) ·
#//           decline=N/A (structural: no "may" on either clause; the damage is mandatory once the
#//           reveal comes up Aggression) ·
#//           boundary=Regroup_TwoCopiesFireTwice (per-copy scaling) paired with
#//           Regroup_NonAggressionTop_NoDamage (the aspect gate) ·
#//           control=N/A (structural: the ability is hosted on a BASE, which has one controller and
#//           never changes hands; "your deck" and "an enemy unit" both resolve from that seat) ·
#//           reqboundary=N/A (structural: the whole trigger resolves inside the phase transition — the
#//           reveal, the aspect read and the damage choose are one continuous resolution, with no state
#//           written by one player ACTION and read by the next. Base-subcard survival across a
#//           round-trip is covered generically by keywords/Fortify.md) ·
#//           emptydeck=Regroup_EmptyDeck_NothingRevealed_RegroupContinues (control: Regroup_SingleCardDeck_*) ·
#//           bothArenas=Regroup_EnemySpaceUnit_IsOffered + Regroup_EnemySpaceUnit_TakesTheDamage ·
#//           modes=2P only — "an enemy unit" is a controller relation the shared pool already resolves
#//           per seat, and there is no player reference to choose between opponents.
#//
#// Direct sibling: HMW_070 Dark Sanctum in this same set (base-hosted regroup trigger, per-copy).
#// Two passes end the action phase and reach regroup. The deck is seeded with an AGGRESSION card on top
#// (SOR_128 is [Aggression][Villainy]) — the enemy 3/7 takes exactly 1.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>Pass
#// ⚠ THE DRAIN IS LOAD-BEARING. Unlike its sibling HMW_070 Dark Sanctum — which resolves entirely
#// synchronously — this trigger QUEUES a target choose, so the regroup correctly pauses at RGS until it
#// is processed. `Drain` is the harness stand-in for production's post-action ProcessGoldfishAutomation.
#// Without it the phase never reaches the draw step, the damage never lands, and the section fails
#// looking exactly like the trigger not firing.
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Regroup_NonAggressionTop_NoDamage
#// ⚠ HMW_160 — THE ASPECT GATE. The same board with a NON-Aggression card on top (SOR_095 is
#// [Command][Heroism]): the reveal still happens, but nothing is dealt. An implementation that dealt
#// unconditionally passes the positive above and fails only here.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_095 SOR_128 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>Pass
#// ⚠ THE DRAIN IS LOAD-BEARING. Unlike its sibling HMW_070 Dark Sanctum — which resolves entirely
#// synchronously — this trigger QUEUES a target choose, so the regroup correctly pauses at RGS until it
#// is processed. `Drain` is the harness stand-in for production's post-action ProcessGoldfishAutomation.
#// Without it the phase never reaches the draw step, the damage never lands, and the section fails
#// looking exactly like the trigger not firing.
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Regroup_DualAspectAggressionCounts
#// ⚠ HMW_160 — "if it's AGGRESSION" is satisfied by any card CARRYING Aggression, not only a
#// mono-Aggression one. CardAspect returns a comma-joined string, so matching the whole value
#// (=== 'Aggression') instead of a substring would exclude every dual card — which is most of them.
#// LOF_159 Jedi In Hiding is MONO-[Aggression]; the positive section above already used the DUAL
#// [Aggression][Villainy] SOR_128. The pair is what pins the substring reading: both must deal 1.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [LOF_159 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>Pass
#// ⚠ THE DRAIN IS LOAD-BEARING. Unlike its sibling HMW_070 Dark Sanctum — which resolves entirely
#// synchronously — this trigger QUEUES a target choose, so the regroup correctly pauses at RGS until it
#// is processed. `Drain` is the harness stand-in for production's post-action ProcessGoldfishAutomation.
#// Without it the phase never reaches the draw step, the damage never lands, and the section fails
#// looking exactly like the trigger not firing.
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Regroup_RevealDoesNotMoveOrLoseTheCard
#// ⚠ HMW_160 — REVEAL ≠ DRAW ≠ MILL ≠ LOOK. The card is shown and STAYS on top of the deck.
#// The deck is seeded with 4; the regroup draw step takes 2, so exactly 2 remain — a reveal that
#// consumed the card would leave 1, and one that drew it would leave 1 and inflate the hand.
#// The top card afterwards is the SECOND seeded card (the first was drawn by the regroup step, not by
#// the reveal), which pins the ordering rather than just the count.
#// Every other section in this file passes against an implementation that eats the revealed card.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>Pass
#// ⚠ THE DRAIN IS LOAD-BEARING. Unlike its sibling HMW_070 Dark Sanctum — which resolves entirely
#// synchronously — this trigger QUEUES a target choose, so the regroup correctly pauses at RGS until it
#// is processed. `Drain` is the harness stand-in for production's post-action ProcessGoldfishAutomation.
#// Without it the phase never reaches the draw step, the damage never lands, and the section fails
#// looking exactly like the trigger not firing.
- P1>Drain

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Regroup_EnemyOnly_YourOwnUnitsAreNotOffered
#// HMW_160 — the OFFER cell. "an ENEMY unit" is scoped to the BASE's controller, so P1's own unit is
#// not a legal target. With exactly one enemy unit the mandatory choose auto-resolves onto it, so the
#// proof is that the friendly unit is UNDAMAGED while the enemy took the 1 — a pool that ignored the
#// controller restriction would have had two targets and raised a prompt instead.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>Pass
#// ⚠ THE DRAIN IS LOAD-BEARING. Unlike its sibling HMW_070 Dark Sanctum — which resolves entirely
#// synchronously — this trigger QUEUES a target choose, so the regroup correctly pauses at RGS until it
#// is processed. `Drain` is the harness stand-in for production's post-action ProcessGoldfishAutomation.
#// Without it the phase never reaches the draw step, the damage never lands, and the section fails
#// looking exactly like the trigger not firing.
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Regroup_NoEnemyUnit_FizzlesCleanly
#// HMW_160 — the no-valid-target cell. The reveal still happens and comes up Aggression, but there is
#// no enemy unit to damage, so the clause resolves to nothing and the regroup RUNS STRAIGHT THROUGH to
#// its own draw and resource steps. A choose queued over an empty pool would strand the phase at RGS.
#// ⚠ NOT asserted with P1NODECISION: reaching the resource step raises the ordinary "Resource up to 1
#// card" MZMAYCHOOSE for BOTH players, which has nothing to do with this card. The proof that the
#// clause fizzled without stalling is that the DRAW STEP ran at all — deck 4 -> 2 — which is
#// unreachable while a decision is parked at RGS. No drain is needed here precisely because nothing
#// was queued.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_046

---

# Regroup_TwoCopiesFireTwice
#// HMW_160 is NON-UNIQUE, and each attached copy grants its own instance of the ability — so two copies
#// deal 1 twice, for 2 total on the same enemy unit.
#// ⚠ AND THE SECOND REVEAL SEES THE SAME CARD, because a reveal does not move it. That is why both
#// copies fire off one Aggression top card rather than the second one reading whatever is underneath.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>Pass
#// ⚠ THE DRAIN IS LOAD-BEARING. Unlike its sibling HMW_070 Dark Sanctum — which resolves entirely
#// synchronously — this trigger QUEUES a target choose, so the regroup correctly pauses at RGS until it
#// is processed. `Drain` is the harness stand-in for production's post-action ProcessGoldfishAutomation.
#// Without it the phase never reaches the draw step, the damage never lands, and the section fails
#// looking exactly like the trigger not firing.
- P1>Drain

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Regroup_OnlyTheAttachedBasesController_Triggers
#// HMW_160 — the ownership half. The ability belongs to the base it is attached to, so only THAT seat
#// reveals and only THAT seat's opponents are damaged. Here P2 holds the Refinery, so P1's unit takes
#// the damage and P2's own unit is untouched — the mirror of the section above, which proves the
#// trigger is not hardcoded to seat 1.

## GIVEN
CommonSetup: grw/rrk/{myResources:5}
WithP2BaseUpgrade: HMW_160
WithP2Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass
- P2>Pass
#// ⚠ THE DRAIN IS LOAD-BEARING. Unlike its sibling HMW_070 Dark Sanctum — which resolves entirely
#// synchronously — this trigger QUEUES a target choose, so the regroup correctly pauses at RGS until it
#// is processed. `Drain` is the harness stand-in for production's post-action ProcessGoldfishAutomation.
#// Without it the phase never reaches the draw step, the damage never lands, and the section fails
#// looking exactly like the trigger not firing.
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Regroup_EmptyDeck_NothingRevealed_RegroupContinues
#// HMW_160 — the EMPTY-DECK cell. With no card on top there is nothing to reveal, so there is no reveal
#// log line, no aspect read and no damage — and the regroup does not stall: it runs on into its own
#// draw step, where drawing 2 from the empty deck deals the ordinary deck-out damage (3 per card not
#// drawn = 6, one event), and on to the resource step (PHASE:RES). That base damage is the proof the phase
#// got PAST the trigger; a crash or a choose queued with nothing to show would strand it before the draw.
#// The paired control is Regroup_SingleCardDeck_StillRevealsAndDeals (the same board with ONE card).
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
LOGCOUNT:0:revealed
P1DECKCOUNT:0
P1HANDCOUNT:0
P1BASEDMG:6
PHASE:RES

---

# Regroup_SingleCardDeck_StillRevealsAndDeals
#// HMW_160 — the control for the empty-deck section, and the "regardless of deck size" cell at its low
#// end: a deck of exactly ONE Aggression card (SOR_128) is revealed (one reveal line), the enemy 3/7 takes
#// 1, and only THEN does the draw step take that same card — hand 1, deck 0, and 3 deck-out damage for
#// the second card that could not be drawn. The revealed card ending up in hand pins that the reveal ran
#// BEFORE the regroup draw.
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
LOGCOUNT:1:revealed
P1DECKCOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1BASEDMG:3
PHASE:RES

---

# Regroup_EnemySpaceUnit_IsOffered
#// HMW_160 — "an enemy unit" spans BOTH arenas. The existing offer section seats one enemy unit (so the
#// choose auto-resolves) and proves only the friendly exclusion. Here P2 has a ground 3/7 AND a space
#// SOR_237 Alliance X-Wing, and P1 has its own SEC_080 on the ground: the pending choose must offer
#// exactly the two ENEMY units, the space one included, and never P1's own. Left pending; the answer is
#// resolved in the section below.
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0
P1DECISIONTOOLTIP:Deal_1_damage_to_an_enemy_unit_(Noxious_Refinery)
P1DECKCOUNT:4

---

# Regroup_EnemySpaceUnit_TakesTheDamage
#// HMW_160 — the same board, answered: the SPACE unit is chosen and takes the 1; the enemy ground unit
#// and P1's own unit are untouched. The regroup then resumes into its draw step (deck 4 → 2) and the
#// resource step — the choose was the only thing holding it.
## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>Pass
- P2>Pass
- P1>Drain
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DECKCOUNT:2
PHASE:RES
