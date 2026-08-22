# OnAttack_EndorBase_OpponentPicksDeal3_DamagesTheirUnit
#// HMW_188 Giant Gorax (7/7, Aggression, cost 7, Creature, Ground, Legendary) —
#//   "Overwhelm.
#//    On Attack/When Defeated: If you control an Endor base, each opponent chooses one:
#//      You deal 3 damage to a unit or base they control.
#//      They discard a card from their hand and defeat a resource they control."
#// P1's base is HMW_029 Dendroid Wilds (Aggression/Endor, 30 HP) — the gate. The OPPONENT picks the
#// mode; on Deal3 the CASTER picks the target. LAW_124 is 4/7 so 3 damage leaves it alive at DAMAGE:3
#// (a 3-HP filler would die and reindex the arena, hiding the value).

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: [LAW_124:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Deal3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:7

---

# OnAttack_Deal3_CanTargetTheirBaseInstead
#// "a unit **or base** they control" — the base is a legal pick. 3 (ability) + 7 (combat) = 10.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: [LAW_124:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Deal3
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:10

---

# OnAttack_Deal3_OfferIsExactlyTheirUnitsAndTheirBase
#// The OFFER, not the branch: answering a target proves the branch works while a stray/missing option
#// passes every branch test. "a unit or base **they control**" must include EVERY unit they control —
#// their SPACE unit (the clause is arena-unqualified) and their DEPLOYED LEADER unit (`Leader Unit`,
#// which a bare `['Unit']` ZoneSearch silently drops) — and must exclude BOTH of P1's own units and
#// P1's own base. Decision left pending so the offer itself can be read.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [HMW_188:1:0 SEC_080:1:0]
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Deal3

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0&theirBase-0
P2NODECISION

---

# OnAttack_TheChoiceBelongsToTheOPPONENT
#// "each OPPONENT chooses one" — the mode decision sits on P2's queue, not the caster's. Asserting the
#// exact tooltip pins the prompt as well as the owner.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Giant_Gorax:_choose_one

---

# OnAttack_NoEndorBase_NoPromptAtAll
#// The load-bearing negative: without an Endor base the whole ability is skipped — no prompt for either
#// player and no damage beyond combat. (Default `r` base is a plain Aggression base, no Endor trait.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:7

---

# OnAttack_EndorBaseIsTheOpponents_NoPrompt
#// "If YOU control an Endor base" — the opponent holding Dendroid Wilds grants the caster nothing. An
#// implementation that scanned for "an Endor base" rather than the resolver's own base passes the
#// positive above and fails only here.

## GIVEN
CommonSetup: rrk/rrk/{theirBase:HMW_029}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:7

---

# OnAttack_OptionB_TheyDiscardAndDefeatAResource
#// The other mode. P2's hand is seeded to exactly ONE card so SWUDiscardCards auto-resolves (a 2+ card
#// hand queues a pick they'd also have to answer); P2 holds TWO resources so the "defeat a resource you
#// control" choose is a real MZCHOOSE (a lone cross-player PASSPARAMETER does not auto-drain).
#// P2's discard ends at 2 = the discarded hand card + the defeated resource.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029;theirhandCardIds:SOR_095;theirResources:2}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:DiscardAndDefeat
- P2>AnswerDecision:myResources-0

## EXPECT
P2HANDCOUNT:0
P2RESCOUNT:1
P2DISCARDCOUNT:2
P2BASEDMG:7

---

# OnAttack_OptionB_EmptyHand_StillDefeatsAResource
#// "discard a card **and** defeat a resource" is joined by AND, not "if you do" — an empty hand must not
#// swallow the resource half. (CANNOT-DO on the first clause, distinct from a decline.)

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029;theirResources:2}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:DiscardAndDefeat
- P2>AnswerDecision:myResources-0

## EXPECT
P2HANDCOUNT:0
P2RESCOUNT:1
P2DISCARDCOUNT:1
P2BASEDMG:7

---

# OnAttack_OptionB_NoResources_StillDiscards
#// The mirror half: no resources to defeat must not swallow the discard, and must leave no dangling
#// decision.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029;theirhandCardIds:SOR_095;theirResources:0}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:DiscardAndDefeat

## EXPECT
P2HANDCOUNT:0
P2RESCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
P2NODECISION
P2BASEDMG:7

---

# OnAttack_Deal3_KillsTheDefender_OverwhelmSpillsFullPowerToBase
#// Integration of the card's two printed abilities. The On Attack resolves BEFORE combat damage, so
#// spending the 3 on the 3-HP defender removes it from the attack entirely — with Overwhelm the whole
#// 7 becomes excess and hits the base (CR attack step 4.c / Overwhelm 7.f), and nothing counters.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:Deal3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:7
P1GROUNDARENAUNIT:0:CARDID:HMW_188
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenDefeated_ByEffect_EndorBase_OpponentPicksDeal3
#// The SECOND trigger window, on the EFFECT-defeat path (SOR_078 Vanquish, "Defeat a non-leader unit").
#// Vanquish is Vigilance and the setup is Aggression, so it costs 5 + 2 off-aspect = 7.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029;myResources:8;myhandCardIds:SOR_078}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:Deal3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenDefeated_ByCombatTrade_BothWindowsFire
#// Both printed windows in ONE attack, on the combat-defeat path: Gorax (pre-damaged to 1 remaining HP)
#// attacks a 4/7 body — On Attack fires first, then the trade defeats Gorax and When Defeated fires
#// again. The two prompts use DIFFERENT modes to prove each window resolves independently.
#// Combat: Gorax deals exactly 7 to a 7-HP defender (excess 0, so Overwhelm adds nothing); the defender
#// counters 4 onto a body already at 6 damage. P2's discard ends at 3 = defeated LAW_124 + the discarded
#// hand card + the defeated resource.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029;theirhandCardIds:SOR_095;theirResources:2}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:6
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:Deal3
- P1>AnswerDecision:theirBase-0
- P2>AnswerDecision:DiscardAndDefeat
- P2>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P2HANDCOUNT:0
P2RESCOUNT:1
P2DISCARDCOUNT:3

---

# WhenDefeated_NoEndorBase_NoPrompt
#// The gate is load-bearing on the When Defeated half too, not only On Attack.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;myhandCardIds:SOR_078}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1NODECISION
P2NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenDefeated_OptionB_TheyDiscardAndDefeatAResource
#// Option B reached through the When Defeated window (the clean effect-defeat path), so neither mode is
#// only ever exercised through On Attack.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029;myResources:8;myhandCardIds:SOR_078;theirhandCardIds:SOR_095;theirResources:2}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:DiscardAndDefeat
- P2>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2RESCOUNT:1
P2DISCARDCOUNT:2

---

# OnAttack_Deal3_NoEnemyUnits_AutoResolvesOntoTheirBase
#// The single-legal-target path: with the opponent controlling nothing, "a unit or base they control"
#// narrows to their base alone and the caster's pick auto-resolves (PASSPARAMETER, no prompt). This mode
#// therefore can never fizzle for want of a target — assert the damage landed AND that nothing dangles.
#// The trailing `P1>Drain` is the harness's stand-in for production's post-action drain: the opponent's
#// answer only drains THEIR queue, so the caster's auto-resolving pick (and the combat commit waiting
#// behind it) needs one drain of the caster's queue to run.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Deal3
- P1>Drain

## EXPECT
P2BASEDMG:10
P1NODECISION
P2NODECISION

---

# SimulateRequestBoundary_CasterSurvivesTheOpponentsDecision
#// Every interactive decision ends the request, and the opponent's mode choice sits between the trigger
#// and the caster's damage step — so the caster's identity must be SERIALIZED, not parked in a transient
#// global (it rides the CUSTOM's own Param, `HMW_188#1|{caster}`). Boundary simulated right after the
#// attack; if the payload were an in-memory global the continuation would resolve against the wrong
#// player (or silently return) and the 3 damage would never land.

## GIVEN
CommonSetup: rrk/rrk/{myBase:HMW_029}
P1OnlyActions: true
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: [LAW_124:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P2>AnswerDecision:Deal3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:7

---

# MirrorDirection_OpponentControlsGorax_GateAndChooserFollowTheResolver
#// Everything in the ability is relative to whoever RESOLVES it: the Endor-base gate reads the Gorax
#// controller's own base, "each opponent" is that controller's opponent, and "you deal" is that
#// controller choosing. Running the whole card from seat 2 catches any seat-1-hardcoded read.

## GIVEN
CommonSetup: rrk/rrk/{theirBase:HMW_029}
WithActivePlayer: 2
WithP2GroundArena: HMW_188:1:0
WithP1GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:Deal3
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:7

---

# TwinSuns_EVERYOpponentChoosesIndependently
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-21. "EACH opponent chooses one" was implemented as
#// OtherPlayer($caster): at four seats only ONE opponent was ever asked and the card did nothing at all
#// to the other two.
#// Every opponent now gets their own mode choice, on their own queue, resolved independently — so seat 2
#// can take the damage while seat 3 takes the discard-and-defeat and seat 4 takes the damage. That
#// independence is the point: one seat's answer must not decide another's.
#// ⚠ FIXTURE: seats 3/4 need their units, hands, resources AND bases seeded — CommonSetup builds seats
#//   1 and 2 only, and the Deal3 pool includes the chooser's BASE.

## GIVEN
CommonSetup: rrk/bbw/{myBase:HMW_029}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Hand: [SOR_095 SOR_046]
WithP3Resources: 3
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:Deal3
- P1>AnswerDecision:p2GroundArena-0
- P3>AnswerDecision:DiscardAndDefeat
#// ⚠ TWO answers, in this order: with 2 cards in hand SWUDiscardCards QUEUES the pick (at or below the
#//   count it would discard inline and this line would be a spare answer that eats the next prompt), and
#//   the resource MZCHOOSE follows it.
- P3>AnswerDecision:myHand-0
- P3>AnswerDecision:myResources-0
- P4>AnswerDecision:Deal3
#// ⚠ The poll matters here: the caster's target choice for seat 4 lands on P1's queue while P1 is not
#//   otherwise acting, and a lone entry on an idle player's queue does not drain by itself (a real
#//   client polls every tick). Without it the last answer has nothing to answer.
- P1>Drain
- P1>AnswerDecision:p4Base-0

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:DAMAGE:3
P3HANDCOUNT:1
P3RESCOUNT:2
P4BASEDMG:3
P4GROUNDARENAUNIT:0:DAMAGE:0

---

# TwinSuns_TheDeal3PoolIsSCOPEDToTheSeatThatChose
#// ⚠ THE SCOPE CELL, and the half a "did the card fire?" test cannot see. "You deal 3 damage to a unit or
#// base THEY control" — the seat that just chose, not every opponent. The pool was built from the
#// caster's frame as theirGroundArena/theirSpaceArena plus the literal 'theirBase-0', and in Twin Suns
#// "their…" fans out across ALL opponents: seat 3's choice would have offered seat 2's and seat 4's units
#// as legal targets, and 'theirBase-0' is not even a valid mzID at four seats.
#// Left pending on SEAT THREE's choice: the offer must contain seat 3's unit and seat 3's base, and
#// nothing belonging to seats 2 or 4.

## GIVEN
CommonSetup: rrk/bbw/{myBase:HMW_029}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_188:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:DiscardAndDefeat
- P3>AnswerDecision:Deal3

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:p3GroundArena-0&p3Base-0
