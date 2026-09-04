# Fortify_AttachesToTheBaseNotAUnit
#// HMW_172 Heavy Ion Cannon — Cost 3 · Upgrade · [Aggression][Heroism] · Fortification · NON-unique
#// Text: "Fortify (Attach this to your base, not a unit.)
#//        When Played: Draw a card.
#//        Attached base gains: \"Action [discard a card from your hand]: Deal 2 damage to a unit.
#//        Use this ability only once each phase.\""
#//
#// COVERAGE: offer=BaseAction_DamageOffer_EveryUnitBothSidesAndBothArenas (SELECTABLEEXACT — "a unit"
#//             is unqualified, so BOTH sides, BOTH arenas and leader units; contrast HMW_046, which says
#//             "a ground unit") + BaseAction_DiscardCostOffer_IsTheWholeHand
#//           decline=N/A (structural: no "may" anywhere. Playing an upgrade is not optional, the discard
#//             is a bracketed COST of an Action the player chose to activate, and the damage clause has
#//             no "you may" — so there is no declinable branch on this card)
#//           boundary=BaseAction_OnceEachPhase_SecondUseNotOffered + BaseAction_AvailableAgainNextPhase
#//             (the once-each-phase limit is the only threshold this card has, and it needs both halves)
#//           control=N/A (structural: the ability is hosted on the BASE, which has exactly one controller
#//             and cannot change hands; "your hand" is that seat's by construction)
#//           reqboundary=RequestBoundary_CostAndEffectBothSurvive
#//           modes=2P only — no player reference and no friendly/enemy wording. "your hand" is the base
#//             controller's own zone, and "a unit" is unqualified, which SWUAllUnits already spans
#//             correctly at any seat count · TwinSuns=N/A · TeamSuns=N/A
#//
#// FORTIFY is generator-wired (HMW_172 is in $Fortify_Cards), so the pool is the base slot and never the
#// units in play. With a single legal host the attach auto-resolves, so the proof is the END STATE plus
#// P1NODECISION: the upgrade is on the base and neither unit is carrying it.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_172
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1BASEUPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_172
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayed_DrawsACard
#// The second clause, and it is unconditional. Hand goes 1 (HMW_172) - 1 played + 1 drawn = 1, and the
#// drawn card is the deck's TOP (SOR_128), leaving SOR_046 on top. Asserting the deck TOP as well as the
#// count is what separates "drew the top card" from "drew something".

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_172
WithP1Deck: [SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEUPGRADECOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046
P1NODECISION

---

# BaseAction_DiscardsFromHandAndDealsTwo
#// The granted Action, end to end. The upgrade is SEEDED here rather than played, so the When Played
#// draw cannot muddy the hand accounting.
#// Cost first (discard SOR_095 from hand), then the effect (2 damage). SOR_046 is 3/7 so it survives,
#// which keeps this section about the amount rather than about a defeat.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:FROM:HAND
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1BASEUPGRADECOUNT:1
P1NODECISION

---

# BaseAction_OnceEachPhase_SecondUseNotOffered
#// "Use this ability only once each phase." The second activation in the same phase must find nothing
#// available — no prompt, no second discard, no second 2 damage. DAMAGE:2 rather than 4 is the whole
#// assertion; a handler with no limit reads 4 here and passes every other section in this file.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseBaseAbility

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# BaseAction_AvailableAgainNextPhase
#// THE DURATION HALF. "Once each PHASE" must RESET, so the pair above is only meaningful next to this:
#// the round is passed out and the ability works again, taking the total to 4.
#// ⚠ Under P1OnlyActions the opponent holds the CLAIMED initiative and therefore LEADS the new round, so
#// the chain needs a trailing P2>Pass or P1's activation is refused by the turn-player gate — which
#// reads exactly like the limit failing to reset. Both players need a seeded deck: the regroup DRAWS,
#// and an empty deck damages the base instead.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046 SOR_237 SOR_225 SOR_095 SEC_080]
WithP2Deck: [SOR_128 SOR_046 SOR_237 SOR_225 SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1BASEUPGRADECOUNT:1

---

# BaseAction_EmptyHand_NotOffered_AndTheActionIsKept
#// The bracketed cost is UNPAYABLE with an empty hand, so the Action must not be offered at all — the
#// LAW_023 / LAW_019 rule for a base Action whose cost cannot be met, and the fizzle-only-optional
#// family generally.
#//
#// ⚠ THE BOARD ASSERTIONS ALONE DO NOT DISCRIMINATE, which a green mutation proved. If the gate wrongly
#// offers the Action, SWUBaseAction auto-dispatches it, the handler finds an empty hand and aborts —
#// so nothing is discarded, nothing is damaged and no decision is left pending EITHER WAY. The only
#// observable difference is whether P1's ACTION was consumed: an Action that is never offered leaves
#// the turn with P1, while one that is offered and self-aborts closes it and hands the turn to P2.
#// Hence no P1OnlyActions here — that directive claims initiative for the opponent and makes the action
#// close invisible. TURNPLAYER:1 is the assertion that actually pins this.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
WithActivePlayer: 1
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
TURNPLAYER:1
P1HANDCOUNT:0
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEUPGRADECOUNT:1
P1NODECISION

---

# BaseAction_DiscardCostOffer_IsTheWholeHand
#// The COST offer, left pending: every card in hand is a legal discard, and nothing outside the hand is.
#// Three cards so the pick cannot auto-resolve.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080 SOR_128]
WithP1Deck: [SOR_046 SOR_237]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1&myHand-2

---

# BaseAction_DamageOffer_EveryUnitBothSidesAndBothArenas
#// THE DAMAGE OFFER. "Deal 2 damage to A UNIT" is unqualified — no "enemy", no "non-leader", no arena —
#// so the pool is every unit on the table including P1's own, both arenas, and a deployed leader unit.
#// This is the deliberate contrast with HMW_046 Krrsantan, whose text says "a GROUND unit": if this card
#// were built by copying that pool the two space units would be missing here.
#// P2's leader is deployed and lands at ground index 1.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3;theirLeaderDeployed:true}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# BaseAction_NoUnitsInPlay_StillPaysTheDiscardAndSpendsTheUse
#// An Action that fizzles still pays its cost (house ruling — there is no "use it anyway?" confirmation).
#// With an empty board the cost is payable, so the Action IS offered, the discard happens, the damage
#// finds nothing, and the once-each-phase use is spent: the trailing activation does nothing even though
#// a second card is still in hand.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>UseBaseAbility

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1NODECISION

---

# BaseAction_LethalDamage_DefeatsTheTarget
#// Ordinary damage, so it defeats what it kills. SEC_080 is 3/3 and pre-damaged to 1 remaining.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: [SEC_080:1:1 SOR_046:1:0]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_080
P1NODECISION

---

# BaseAction_ShieldAbsorbsTheDamage
#// Interaction with the standard modifiers: ordinary, preventable ability damage, so a Shield token
#// absorbs the whole instance. The cost is still paid and the use still spent.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# TwoCopies_GrantTwoUsesEachPhase
#// NON-UNIQUE, so two Heavy Ion Cannons can sit on one base — and each grants its own once-each-phase
#// Action, so the phase allows TWO activations. A limit tracked per PLAYER rather than per attached copy
#// would stop after the first and read DAMAGE:2 here.
#// Two providers make the base click raise a which-ability OPTIONCHOOSE; the engine labels them from the
#// card title, disambiguating the second with a trailing index.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080 SOR_128]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:HeavyIonCannon
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseBaseAbility
- P1>AnswerDecision:HeavyIonCannon
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseBaseAbility

## EXPECT
P1BASEUPGRADECOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:4
P1HANDCOUNT:1
P1DISCARDCOUNT:2
P1NODECISION

---

# RequestBoundary_CostAndEffectBothSurvive
#// The Action spans TWO interactive decisions, so production resolves it across two fresh processes.
#// Nothing may be held in memory between the cost pick and the damage pick. Identical to the opening
#// positive except for the boundaries inserted before each answer.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
