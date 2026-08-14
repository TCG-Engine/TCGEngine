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
