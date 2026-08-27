# OnAttackDealExhaust
#// LAW_076 Vult Skerris's Defender (3/3, space) — On Attack: you may deal 1 damage to a space unit and
#// exhaust it. Attacks the base; hit the enemy SOR_237 (1 damage + exhausted).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_076:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:EXHAUSTED

---

# OnAttackExhaustCannotTakeDamage
#// LAW_076 Vult Skerris's Defender — On Attack: the damage+exhaust is simultaneous. Target the enemy
#// SHD_187 Lurking TIE Phantom, which "can't be captured, damaged, or defeated by enemy card abilities":
#// the damage is prevented (stays 0) but the exhaust still lands.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_076:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:EXHAUSTED

---

# WhenPlayedNoDiscardNoShield
#// LAW_076 Vult Skerris's Defender — When Played: only gains a Shield if a card was discarded from your
#// hand or deck THIS phase. Nothing was discarded, so it enters with no Shield.

## GIVEN
CommonSetup: ryk/bgw/{myResources:8}
P1OnlyActions: true
WithP1Hand: LAW_076

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_076
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# PlayingAnEventIsNotDiscardingFromYourHand
#// LAW_076 — "If you DISCARDED a card from your hand or deck this phase". Playing a card is not
#// discarding it. P1 plays Urgent Mission (TS26_64 — draws and self-damages, discards nothing) and then
#// plays this unit: NO Shield.
#// BUG THIS PINS: an event resolving to its CONTROLLER's discard reaches the shared discard funnel tagged
#// from='HAND', and the funnel's counter block did not exclude the event's own play — so merely playing
#// ANY event satisfied this gate. The play path already sets gPlayingEventCardID for exactly this reason
#// (LAW_206 That's a Rock uses it); the counters just never consulted it.

## GIVEN
CommonSetup: ryk/yyw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_64 LAW_076]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_076
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# AGenuineHandDiscardStillShieldsIt_EvenWhenTheOPPONENTCausedIt
#// LAW_076 — the positive control for the section above, and the "whose effect" question: the gate reads
#// "if YOU discarded a card from YOUR hand", not "if you chose to". P2 plays Spark of Rebellion (SOR_200)
#// to discard from P1's hand; P1 then plays this unit and DOES get its Shield.
#// Without this pairing the fix above could be over-applied into never shielding at all.

## GIVEN
CommonSetup: ryk/yyw/{myResources:5;theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP1Hand: [LAW_076 SOR_095]
WithP2Hand: SOR_200
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirHand-1
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_076
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# ADeckMillAlsoSatisfiesTheGate
#// LAW_076 — "discarded a card from your hand OR DECK this phase". Daring Delve (LAW_203) discards 2 from
#// P1's own DECK (its optional return is declined), and that is enough: the unit arrives with its Shield.
#// The deck half is a separate code path from the hand half, so it needs its own section.

## GIVEN
CommonSetup: ryk/yyw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [LAW_203 LAW_076]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_076
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# ADiscardFromTheOPPONENTSHandDoesNotCount
#// LAW_076 — "if YOU discarded a card from YOUR hand or deck". P1 plays Spark of Rebellion (SOR_200) to
#// strip a card from P2's hand: that is P2's hand, not P1's, so no Shield. P2's discard pile confirms the
#// discard really happened.
#// Pairs with AGenuineHandDiscardStillShieldsIt: the two differ only in WHOSE hand lost the card, which is
#// the whole condition. This case was untestable until the played-event bug was fixed — playing Spark is
#// itself an event play, so it used to shield him for the wrong reason.

## GIVEN
CommonSetup: ryk/yyw/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_200 LAW_076]
WithP2Hand: SOR_095
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P2DISCARDCOUNT:1

---

# ADiscardInAPREVIOUSPhaseDoesNotCount
#// LAW_076 — "THIS phase". P2 strips a card from P1's hand with Spark of Rebellion, then the action phase
#// is passed out and the regroup resolved; playing the unit in the NEW phase gets no Shield.
#// Discriminating against AGenuineHandDiscardStillShieldsIt, which is the same discard followed by an
#// immediate play and does shield.

## GIVEN
CommonSetup: ryk/yyw/{myResources:5;theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1Hand: [LAW_076 SOR_095]
WithP2Hand: SOR_200
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirHand-1
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
PHASE:MAIN
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# TheDamageLandsEvenOnAUnitThatCannotBeExhausted
#// LAW_076 — "On Attack: you may deal 1 damage to a space unit AND exhaust it." The two halves are
#// independent, so a target immune to the exhaust still takes the damage. P2's Leia Organa (LOF_098, a
#// FORCE space unit) wears Kylo Ren's Lightsaber (LOF_040), which grants "can't be exhausted by enemy card
#// abilities": she takes the 1 and stays ready, while the attack on P2's base lands for 3.
#// Complements the existing OnAttackExhaustCannotTakeDamage section, which is this case mirrored — a
#// target immune to the DAMAGE that still gets exhausted.

## GIVEN
CommonSetup: ryk/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_076:1:0
WithP2SpaceArena: LOF_098:1:0
WithP2SpaceArenaUpgrade: 0:LOF_040

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:READY
P2BASEDMG:3

---

# ADeckMillAlsoSatisfiesTheGate_SurvivesTheRequestBoundary
#// LAW_076 — request-boundary guard for ADeckMillAlsoSatisfiesTheGate. The "you discarded a card from your
#// hand or deck THIS phase" flag is written by Daring Delve's mill and read one interactive decision later
#// when the unit is played; production starts a FRESH process on every answered decision, so that flag has
#// to come back out of the serialized gamestate rather than an in-memory per-phase counter. Lose it and
#// the unit arrives with no Shield.
#// ⚠ ADeckMillAlsoSatisfiesTheGate's own deck is all SOR_095, so Daring Delve's "return an Aggression card
#// you discarded this way" finds nothing and its `AnswerDecision:-` there is a SPARE answer against no
#// pending decision — a boundary in that flow would be vacuous. The deck is therefore all-Aggression here
#// (SOR_128 / SOR_164) purely so the return offer is a real MZMAYCHOOSE [myDiscard-1&myDiscard-2]; the
#// return is still declined, and the mill still shields the unit.

## GIVEN
CommonSetup: ryk/yyw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [LAW_203 LAW_076]
WithP1Deck: [SOR_128 SOR_128 SOR_164 SOR_164]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_076
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
