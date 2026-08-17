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
- P1>AnswerDecision:myTempZone-0

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
- P1>AnswerDecision:myTempZone-0

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
- P2>AnswerDecision:myTempZone-0

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

---

# SimultaneousDefeat_OnlyTheFriendlyOneTriggers
#// COVERAGE addendum (the file's ledger sits in a frozen section): boundary=this section adds the
#//           simultaneous-defeat arm — one friendly and one enemy unit die in the same combat and
#//           exactly ONE scry is raised (P1NODECISION/P2NODECISION pin the absence of a second).
#//           ⚠ Rogue One's OWN combat defeat is NOT covered anywhere in this file: it raises no scry
#//           at all today (see the LAW Phase D report), so no section asserts it.
#//
#// LAW_119 Rogue One — "When a FRIENDLY unit is defeated" fires once per friendly defeat and not at all
#// for an enemy one, even when both sides lose a unit in the same combat. P1's SOR_225 TIE/ln Fighter
#// (2/1) attacks P2's SOR_231 TIE Advanced (3/2): the attacker deals 2 and kills the defender, the
#// defender deals 3 back and kills the attacker. Exactly ONE scry decision is raised (P1 buries the top
#// card, so the new top is SOR_095) and neither player is left holding a second one — an enemy defeat
#// counted as friendly would leave a second look-at-top-2 pending.
#// Rogue One is a bystander here: it never attacks and survives as P1's only space unit.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_231:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:1:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_119
P2SPACEARENACOUNT:0
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2
P1NODECISION
P2NODECISION

---

# SelfCombatDefeat_TriggersItsOwnScry
#// LAW_119 — "When A FRIENDLY unit is defeated" has no "another", so Rogue One's OWN defeat qualifies.
#// It attacks a 3/3 and dies on the counter; the scry must still fire for its controller.
#// Regression guard: the observer counted Rogue Ones via a helper that SKIPS anything already flagged
#// removed, so a Rogue One dying in that very batch counted ZERO and no decision was raised at all.
#// The effect-defeat path collects before removal and therefore already worked — which is why this was
#// only ever wrong on a COMBAT death, and why the file's existing stolen-and-defeated section passed.
#// Per CR simultaneous-removal the condition is evaluated as of the state that CAUSED the defeat.
#// DISCRIMINATES: both units die (both arena counts 0) and the deck is REORDERED — SOR_237 was on top
#// and is sent to the bottom, so SOR_095 becomes the new top. Without the fix no decision existed at
#// all and the deck top stayed SOR_237.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP2SpaceArena: SOR_231:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2

---

# MassWipe_FiresExactlyOncePerFriendlyDefeated_NotTwice
#// LAW_119 Rogue One caught in SOR_043 Superlaser Blast ("Defeat all units"), as the ONLY friendly unit.
#// Exactly ONE friendly is defeated (Rogue One itself), so exactly ONE scry must be raised.
#// ⚠ Regression guard for a DOUBLE-FIRE: a per-unit mass-defeat loop reaches the observer collection by
#// two routes, and the old "active copies + copies in THIS batch" count credited Rogue One's own defeat
#// TWICE — a solo Rogue One raised TWO scries for ONE defeat. Inside a simultaneous-defeat window the
#// count now comes from the pre-effect snapshot instead, which is idempotent.
#// DISCRIMINATES: one DONE answers the single scry; the second DONE must find nothing, so P1NODECISION
#// fails if a second scry exists. The companion combat section (SelfCombatDefeat_TriggersItsOwnScry)
#// covers the ONE-batch route, which never double-fired and must not regress to zero.

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LAW_119:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Hand: SOR_043

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0

---

# MassWipe_FiresForEACHFriendlyDefeated
#// LAW_119 Rogue One + a second friendly, both defeated by the same Superlaser Blast. TWO friendly
#// defeats means TWO scries — the first DONE clears one and a second must still be pending.
#// Pairs with the section above: together they pin the count at exactly one per friendly defeated,
#// catching both the drop (0 or 1 here) and the double-fire (2 there).

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LAW_119:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Hand: SOR_043

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE

## EXPECT
P1HASDECISION

---

# LookPromptOffersTheCARDS_NotTheDeckPile
#// LAW_119 Rogue One — the same prompt-render cell as LAW_237 Qui-Gon (live bug report #962), and the
#// only other member of the family engine-wide. "Look at the top 2 cards" offered the deck's own mzIDs,
#// so the client rendered the `Deck` zone — `Display: Mode=Single(Stacked), BindTo=DeckSlot`, a single
#// pile showing only its count — instead of the two cards. Staged into TempZone (`Display: Mode=None`),
#// which routes the MZMULTICHOOSE to its own card modal.
#// Nobody reported this one; it was found by sweeping for the shape.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_237 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1
