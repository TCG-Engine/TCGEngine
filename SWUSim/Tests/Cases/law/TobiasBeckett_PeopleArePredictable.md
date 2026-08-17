# FrontGiveControlCredit
#// LAW_002 Tobias Beckett (leader front) — "Action [Exhaust]: Choose a friendly unit. An opponent takes
#// control of it. If they do, create a Credit token." P1 gives its only unit (SEC_080) to P2 and creates
#// 1 Credit → SEC_080 moves to P2's arena.
#// COVERAGE: offer=DeployedUndefeatableUnitOffered (P1SELECTABLEEXACT on the deployed pick; the front
#//           single-unit pick auto-resolves per the single-legal-option rule) ·
#//           reqboundary=DeployedDefeatOwnNotControl (the pick is answered on a later request after the
#//           deploy) · control=the whole card: FrontGiveControlCredit (transfer succeeds) vs
#//           FrontControlBlockedByRey_NoCredit (transfer blocked → no Credit) ·
#//           boundary=DeployedDefeatOwnNotControl vs DeployedNoOwnNotControlNoCredit /
#//           DeployedNoFriendlyUnitNoCredit; deck stocked vs DeployedDefeatEmptyDeckBaseDamage ·
#//           decline=N/A here ("any number" choose-none and multi-select need more than one
#//           own-but-not-controlled unit, which the harness cannot seat).

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1CREDITCOUNT:1

---

# DeployedDefeatOwnNotControl
#// LAW_002 Tobias Beckett (deployed via When Deployed) — "Defeat any number of units you own but don't
#// control. For each unit defeated this way, create a Credit token and draw a card." P2 controls one unit
#// P1 owns (SOR_164); on deploy P1 defeats it → it goes to P1's discard, and P1 gets 1 Credit + 1 card.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2ControlledUnit: SOR_164:1
WithP1Deck: [SOR_237 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1DISCARDCOUNT:1
P1DECKCOUNT:1
P1HANDCOUNT:1
P1LEADER:DEPLOYED

---

# DeployedDefeatEmptyDeckBaseDamage
#// LAW_002 Tobias Beckett (deployed) — the "draw a card" per defeated unit still happens with an empty
#// deck: each such draw deals 3 damage to your own base instead. P1's deck is empty, so defeating the one
#// own-but-not-controlled unit (SOR_164) creates 1 Credit and deals 3 damage to P1's base.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2ControlledUnit: SOR_164:1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1DISCARDCOUNT:1
P1BASEDMG:3
P1LEADER:DEPLOYED

---

# DeployedNoOwnNotControlNoCredit
#// LAW_002 Tobias Beckett (deployed) — only units you own but DON'T control are eligible. P1's own unit
#// SEC_080 is owned AND controlled by P1, so nothing is eligible: no defeat, no Credit, deck untouched.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_237 SOR_095]

## WHEN
- P1>DeployLeader

## EXPECT
P1CREDITCOUNT:0
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1LEADER:DEPLOYED

---

# DeployedNoUnitAtAllNoCredit
#// LAW_002 Tobias Beckett (deployed) — with no units in play at all, the ability finds nothing to defeat
#// and creates no Credit.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader

## EXPECT
P1CREDITCOUNT:0
P1LEADER:DEPLOYED

---

# DeployedNoFriendlyUnitNoCredit
#// LAW_002 Tobias Beckett (deployed) — an enemy unit the opponent both owns and controls (SOR_164) is not
#// a unit P1 owns, so it is not eligible: no defeat, no Credit.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1CREDITCOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P1LEADER:DEPLOYED

---

# FrontControlBlockedByRey_NoCredit
#// LAW_002 Tobias Beckett (front) — "An opponent takes control of it. IF THEY DO, create a Credit." When
#// the chosen unit is Rey (LAW_149, "opponents can't take control of this unit"), the transfer fails, so
#// NO Credit is created and Rey stays with P1.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_149:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0

---

# DeployedUndefeatableUnitOffered
#// LAW_002 Tobias Beckett (deployed) — a unit you own but don't control is offered by the When Deployed
#// "defeat any number" pick even when it cannot actually be defeated by the ability: SHD_187 Lurking TIE
#// Phantom (owned by P1, controlled by P2) "can't be captured, damaged, or defeated by enemy card
#// abilities", but it is still a legal choice. The pick is left pending here to assert the offer.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2SpaceArenaControlled: SHD_187:1
WithP1Deck: [SOR_237 SOR_095]

## WHEN
- P1>DeployLeader

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirSpaceArena-0

---

# DeployedUndefeatableUnit_NoRewardWhenDefeatBlocked
#// "For each unit defeated THIS WAY" — the Credit + draw are gated on the defeat actually happening.
#// P1 owns SHD_187 Lurking TIE Phantom (can't be captured or defeated by enemy card abilities) under
#// P2's control; on deploy P1 picks it. From the Phantom's controller's view Tobias's ability is an
#// enemy ability, so the defeat is blocked: it stays in play and NO Credit or draw is paid.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2SpaceArenaControlled: SHD_187:1
WithP1Deck: [SOR_237 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P1CREDITCOUNT:0
P1DECKCOUNT:2
P1HANDCOUNT:0
P1LEADER:DEPLOYED

---

# DeployedOfferExcludesOwnedAndControlled_AndControlledNotOwned
#// COVERAGE (correction + extension; the file's first section is pre-existing and off-limits, so this
#//   supersedes the `decline=N/A` claim in it). That claim said "'any number' choose-none and multi-select
#//   need more than one own-but-not-controlled unit, which the harness CANNOT seat" — that is FALSE:
#//   WithP{n}{Ground|Space}ArenaControlled is a LIST-valued directive, so any number of own-but-not-
#//   controlled units can be seated at once. The four sub-cases it declared unreachable are now covered:
#//   offer(mixed board)=DeployedOfferExcludesOwnedAndControlled_AndControlledNotOwned ·
#//   multi-select-all=DeployedDefeatEveryOwnedNotControlledUnit ·
#//   partial-select=DeployedDefeatFewerThanTheMaximum ·
#//   decline=DeployedDeclineTheWholeDefeat ·
#//   empty-deck scaling=DeployedDefeatThreeWithEmptyDeck_ThreeDamagePerFailedDraw.
#//
#// LAW_002 Tobias Beckett (deployed) — "Defeat any number of units you OWN but DON'T CONTROL." Owner and
#// controller are distinct: a unit you own but an opponent controls is NOT friendly to you, and a unit you
#// control but do not own is not yours to defeat this way. Prior sections proved each exclusion on a board
#// holding only one category; this one puts all three on the board at once so the offer has to discriminate:
#//   · P1 ground SEC_080 — P1 owns AND controls  → excluded
#//   · P1 ground SHD_029 owned by P2             → P1 controls, does NOT own → excluded
#//   · P2 ground SOR_164 / SOR_046 / SOR_095, all owned by P1 → owned, NOT controlled → the whole offer
#// The pick is left pending so the offer is what is asserted.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaControlled: SHD_029:2
WithP2GroundArenaControlled: [SOR_164:1 SOR_046:1 SOR_095:1]
WithP1Deck: [SOR_237 SOR_095 SOR_085 SOR_164]

## WHEN
- P1>DeployLeader

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

---

# DeployedDefeatEveryOwnedNotControlledUnit
#// LAW_002 Tobias Beckett (deployed) — "ANY NUMBER" means the pick is a multi-select, not a single target,
#// and the reward clause is "FOR EACH unit defeated this way", so it scales: taking all three own-but-not-
#// controlled units in one activation pays 3 Credits and 3 draws, not 1. All three go to their OWNER's
#// discard (P1's) even though they were defeated out of P2's arena — a defeated card always goes to the
#// discard pile of the player who owns it, never the controller's. Deck of 4 → 3 drawn, 1 left.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArenaControlled: [SOR_164:1 SOR_046:1 SOR_095:1]
WithP1Deck: [SOR_237 SOR_095 SOR_085 SOR_164]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:3
P1DISCARDCOUNT:3
P2DISCARDCOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:3
P1LEADER:DEPLOYED

---

# DeployedDefeatFewerThanTheMaximum
#// LAW_002 Tobias Beckett (deployed) — "any number" is genuinely ANY number, not "all of them": with three
#// eligible units P1 may take just two. The reward is paid strictly per unit actually defeated (2 Credits,
#// 2 draws), and the unselected third unit is untouched — it stays in play under P2's control.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArenaControlled: [SOR_164:1 SOR_046:1 SOR_095:1]
WithP1Deck: [SOR_237 SOR_095 SOR_085 SOR_164]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:2
P1DISCARDCOUNT:2
P1DECKCOUNT:2
P1HANDCOUNT:2
P1LEADER:DEPLOYED

---

# DeployedDeclineTheWholeDefeat
#// LAW_002 Tobias Beckett (deployed) — zero is a legal "any number", so the ability must be fully
#// declinable even with eligible units on the board. Declining defeats nothing, pays no Credit and draws
#// no card; all three own-but-not-controlled units stay in P2's arena and the deck is untouched. (This is
#// the lower bound that DeployedDefeatFewerThanTheMaximum and DeployedDefeatEveryOwnedNotControlledUnit
#// bracket from above.)

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArenaControlled: [SOR_164:1 SOR_046:1 SOR_095:1]
WithP1Deck: [SOR_237 SOR_095 SOR_085 SOR_164]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:3
P1CREDITCOUNT:0
P1DISCARDCOUNT:0
P1DECKCOUNT:4
P1HANDCOUNT:0
P1LEADER:DEPLOYED

---

# DeployedDefeatThreeWithEmptyDeck_ThreeDamagePerFailedDraw
#// LAW_002 Tobias Beckett (deployed) — the empty-deck draw penalty is paid PER DRAW, not once per
#// activation. DeployedDefeatEmptyDeckBaseDamage proves the single-unit case (3 damage); with an empty deck
#// and three units defeated in one activation the three failed draws deal 3 damage each — 9 to P1's own
#// base — while the Credits still scale normally to 3 and all three cards still land in their owner's
#// (P1's) discard.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_002;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArenaControlled: [SOR_164:1 SOR_046:1 SOR_095:1]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:3
P1DISCARDCOUNT:3
P1HANDCOUNT:0
P1BASEDMG:9
P1LEADER:DEPLOYED
