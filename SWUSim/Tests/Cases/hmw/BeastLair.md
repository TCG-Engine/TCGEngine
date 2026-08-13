# Fortify_PlaysOntoYourOwnBase
#// HMW_147 Beast Lair — Upgrade, cost 2, [Command], trait Fortification, NON-unique.
#// "Fortify (Attach this to your base, not a unit.)
#//  Attached base gains: 'When the action phase starts: You discard a card from your hand. If you do,
#//  create a Beast token.'"
#// Fortify needs no code (registry + keywords/Fortify.md, incl. the enemy-base exclusion). ggw covers
#// Command, so it costs exactly 2 and attaches to P1's own base with no host prompt — the friendly unit
#// ending bare is the proof it was never offered.
#// COVERAGE: offer=the discard prompt pool is the player's own hand (DiscardIsAChoice_FromOwnHand picks
#//           a specific card) · decline=N/A (the discard is MANDATORY — "You discard", not "may"; the
#//           cannot-pay branch is EmptyHand_NoDiscardNoBeast) · boundary=N/A (no numeric threshold;
#//           per-copy scaling pinned by TwoCopies_TwoDiscardsTwoBeasts) · control=EnemyBase_TheirsFires
#//           ForTHEM (the granted ability belongs to the base's controller) · reqboundary=N/A (each
#//           phase-start trigger raises and consumes its decision inside one exchange; nothing is
#//           written before a decision and read behind it)

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myhandCardIds:HMW_147}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASE:UPGRADE:0:CARDID:HMW_147
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:1

---

# ActionPhaseStart_DiscardsThenCreatesABeast
#// The granted trigger fires when the NEXT action phase starts (a seeded upgrade sees its first
#// action-phase START only after a full round). P1's hand at that point holds the 2 regroup draws; the
#// prompt discards one and a Beast token (HMW_T03, a 3/3 ground Creature) is created.
#// Discard 1 proves the cost was actually paid, hand 1 proves exactly one card left.
#// The pass chain crosses the regroup resource step, and the P1>Drain mirrors the client's poll that
#// surfaces the queued phase-start trigger.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_147
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:POWER:3
P1DISCARDCOUNT:1
P1HANDCOUNT:1

---

# EmptyHand_NoDiscardNoBeast
#// "If you do" — the cannot-pay branch. With EMPTY decks the regroup draws fail (each failed draw deals
#// 3 to the base — the +6 is asserted so the noise is priced in, not ignored), so the hand is empty at
#// the action-phase start: no discard is possible, no Beast, no dangling decision.
#// The resource prompt still appears with an empty hand (zone-form offer), so both ResourcePasses are
#// crossed explicitly before the drain.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_147

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:6
P1NODECISION

---

# TwoCopies_TwoDiscardsTwoBeasts
#// NON-unique, and each copy grants its own instance: two prompts, two discards, two Beasts. A boolean
#// "does the base have one?" implementation makes one Beast and leaves a card in hand — hand 0 and
#// ground count 2 are what tell the difference.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_147
WithP1BaseUpgrade: HMW_147
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1DISCARDCOUNT:2
P1HANDCOUNT:0

---

# EnemyBase_TheirsFiresForTHEM
#// "Attached base gains … YOU discard" — the granted ability belongs to the base's CONTROLLER. With the
#// Lair on P2's base, P2 gets the prompt (their queue — the P2>Drain mirrors their client's poll) and
#// the Beast is P2's. P1 gets nothing.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
WithP2BaseUpgrade: HMW_147
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Drain
- P2>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_T03
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0

---

# PlayedMidPhase_DoesNotFireUntilTheNextActionPhaseStarts
#// Duration edge: playing the Lair DURING an action phase must not fire the trigger retroactively —
#// "when the action phase starts" already happened. No prompt now (P1NODECISION); after the round it
#// fires normally, which the first Beast proves.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myhandCardIds:HMW_147}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1DISCARDCOUNT:1
