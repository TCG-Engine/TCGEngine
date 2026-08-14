# FriendlyDefeatedScry
#// LAW_119 Rogue One (3/3, space) — When a friendly unit is defeated: look at the top 2 cards; put any
#// number on the bottom, rest on top. SOR_128 attacks SOR_046 and dies; put the top SOR_237 on the
#// bottom -> new top is SOR_095.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2

---

# FriendlyDefeated_DeckSizeOne_KeepOnTop
#// LAW_119 Rogue One — with only 1 card in the deck, the "look at top 2" ability shows just the single
#// card. P1 keeps it on top (chooses none to put on bottom). SOR_128 attacks SOR_046 and dies, triggering
#// Rogue One; the lone deck card SOR_237 stays on top and the deck count remains 1.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:DONE

## EXPECT
P1DECKTOPCARD:SOR_237
P1DECKCOUNT:1

---

# FriendlyLeaderUnitDefeated_Triggers
#// LAW_119 Rogue One — a friendly LEADER unit dying also counts as a friendly unit defeated. Deployed
#// Chewbacca (5/6) is seated with 5 damage (1 HP left); with no resources its optional On-Attack defeat
#// auto-skips. It attacks SOR_046 (3/7) and takes 3 combat back (8 >= 6), dying. Rogue One triggers: put
#// the top SOR_237 on the bottom, so the new top is SOR_095.

## GIVEN
CommonSetup: yrw/bgw/{
  myLeader:LAW_013:1:1:0:5;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1SpaceArena: LAW_119:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2
P1LEADER:NOTDEPLOYED

---

# StolenAndDefeated_NewControllerScrysOwnDeck
#// LAW_119 Rogue One — "When a friendly unit is defeated" resolves for whoever CONTROLS Rogue One at
#// the moment of defeat, and "your deck" follows that controller. P2 plays JTL_043 No Glory, Only
#// Results on P1's Rogue One: P2 takes control and defeats it, so Rogue One is a friendly unit dying
#// for P2 — P2 looks at the top 2 of P2's OWN deck. P2 puts the top card (SEC_080) on the bottom, so
#// P2's new top is SOR_095. The card itself still goes to its OWNER's (P1's) discard.
#// Per ruling: bottom order is random, so only the resulting TOP card is asserted.
#//
#// COVERAGE: offer=FriendlyDefeated_DeckSizeOne_KeepOnTop (the look-at pool shrinks with the deck;
#//           the 2-card pool is exercised in every other section) · decline=DONE with no picks
#//           (FriendlyDefeated_DeckSizeOne_KeepOnTop; "any number" includes zero) · control=this
#//           section (defeat under changed control scrys the NEW controller's deck) · boundary
#//           pair=PilotDefeated_DoesNotTrigger vs FriendlyDefeatedScry (upgrade-defeat vs
#//           unit-defeat) · reqboundary=the scry decision itself is a served request; answers cross
#//           it in every section.

## GIVEN
CommonSetup: bbw/rrk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1SpaceArena: LAW_119:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SEC_080
WithP2Deck: SOR_095
WithP2Deck: SOR_063

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:myDeck-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2DECKTOPCARD:SOR_095
P2DECKCOUNT:3

---

# PilotDefeated_DoesNotTrigger
#// LAW_119 Rogue One — a defeated PILOT upgrade is not a defeated unit, so the scry must NOT fire.
#// Rogue One (a Vehicle) carries the JTL_108 Clone Pilot as a pilot upgrade; P2 plays SOR_251
#// Confiscate, defeating the pilot (the only upgrade in play — the mandatory single target
#// auto-resolves). The pilot card goes to P1's discard, Rogue One survives with no upgrades, and
#// no look-at-top-2 decision is offered to anyone; P1's deck is untouched.

## GIVEN
CommonSetup: bbw/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2Hand: SOR_251
WithP1SpaceArena: LAW_119:1:0
WithP1SpaceArenaPilot: 0:JTL_108
WithP1Deck: SEC_080
WithP1Deck: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_119
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1DECKCOUNT:2
P1DECKTOPCARD:SEC_080
P1NODECISION
P2NODECISION
