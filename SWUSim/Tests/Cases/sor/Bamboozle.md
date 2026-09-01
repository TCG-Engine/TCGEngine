# AltCostNo
#// COVERAGE: offer=ControlChange_StolenUnitIsOfferedAsATarget (the exact "Exhaust a unit" pool, left
#//           pending: it spans BOTH sides, two legal targets so nothing auto-resolves) ·
#//           reqboundary=N/A — nothing this card writes is read by a later action: the alternate-cost
#//           YES/NO and the target pick are separate serialized answers inside ONE action
#//           (AltCostYes / ControlChange_UpgradeReturnsToTheUPGRADESOwner), and the exhaust+bounce
#//           leaves no cross-action bookkeeping behind ·
#//           control=ControlChange_UpgradeReturnsToTheUPGRADESOwner (host owned by P2, controlled by
#//           P1, carrying a P1-owned upgrade — "its owner's hand" is the UPGRADE's owner) +
#//           ControlChange_StolenUnitIsOfferedAsATarget (the stolen unit is offered in the
#//           CONTROLLER's frame) · boundary pair=StripsUpgradeToHand (one real upgrade → one card to
#//           hand) vs ExhaustsUnit (no upgrade → exhaust only) vs TokenUpgradeSetAside (a token
#//           upgrade → set aside, neither hand nor discard) · decline=AltCostNo (the "you may discard
#//           a Cunning card instead" is answered NO and the printed cost is paid); the exhaust and the
#//           upgrade bounce themselves are mandatory, and NoAltCostUnplayable is the no-legal-cost half.
#// SOR_199 Bamboozle — alternate cost offered but declined; pays normal cost
#// P1 has 2 resources and Waylay (Cunning) in hand. Chooses NO → pays 2R normally.
#// Waylay remains in hand; only Bamboozle goes to discard.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199,SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# AltCostYes
#// SOR_199 Bamboozle — alternate cost: discard Cunning card instead of paying 2
#// P1 has 1 resource (can't afford normal cost). Waylay (SOR_222, Cunning) is in hand.
#// Player chooses YES → Waylay discarded, resource NOT spent, effect still fires.

## GIVEN
CommonSetup: ygw/grw/{myResources:1;handCardIds:SOR_199,SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:1
P1HANDCOUNT:0
P1DISCARDCOUNT:2

---

# ExhaustsUnit
#// SOR_199 Bamboozle — exhausts target unit (normal cost, no other Cunning card)
#// Single target → auto-resolves without player choice.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# NoAltCostUnplayable
#// SOR_199 Bamboozle — unplayable: 1 resource, no other Cunning card in hand
#// Alternate cost condition not met (no Cunning card to discard). Normal cost (2)
#// cannot be paid. Card stays in hand; no effect fires.

## GIVEN
CommonSetup: ygw/grw/{myResources:1;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# StripsUpgradeToHand
#// SOR_199 Bamboozle — returns upgrade on exhausted unit to owner's hand
#// Upgrade goes to P2's hand (not discard). Unit is also exhausted.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# TokenUpgradeSetAside
#// SOR_199 Bamboozle — token upgrades are set aside, not returned to hand
#// P2 unit has a Shield token (SOR_T02). Bamboozle bounces upgrades, but tokens
#// are set aside (out of game), so P2 hand stays empty and no discard entry.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# ReturnsLeaderPilotToBase
#// SOR_199 Bamboozle ("Exhaust a unit and return each upgrade on it to its owner's hand") vs a unit
#// piloted by a LEADER. A leader attached as a Pilot can't be returned to hand, so instead it is
#// defeated and returns to the leader zone exhausted — a state-based consequence, NOT a direct
#// enemy-ability defeat, so any leader enemy-immunity is irrelevant. P1 has JTL_012 Luke Skywalker
#// deployed as a Pilot on SOR_237; P2 plays Bamboozle at SOR_237. The host is exhausted and keeps no
#// upgrades; Luke returns to P1's base, undeployed and exhausted.

## GIVEN
CommonSetup: gyw/yyw/{myLeader:JTL_012;myLeaderDeployedPilot:true;theirResources:2}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_237:1:0
WithP2Hand: SOR_199

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:EXHAUSTED
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED

---

# ControlChange_StolenUnitIsOfferedAsATarget
#// SOR_199 Bamboozle — "Exhaust a unit" names no controller, so the pool spans BOTH sides of the
#// board. P1 controls a SOR_095 Battlefield Marine that P2 OWNS (the end state after a take-control
#// effect) while P2 fields a SEC_080 of their own. Intended: both are offered, the stolen marine in
#// P1's own frame because CONTROL, not ownership, decides which side a unit is on. A pool keyed on
#// owner shows the marine as theirGroundArena-1; a "choose an enemy unit" reading drops it entirely.
#// Two legal targets are seeded so nothing auto-resolves; the decision is left PENDING so the exact
#// offer can be read, and ControlChange_UpgradeReturnsToTheUPGRADESOwner resolves it.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ControlChange_UpgradeReturnsToTheUPGRADESOwner
#// SOR_199 Bamboozle — "return each upgrade on it to its OWNER's hand". "Its" is the UPGRADE's owner:
#// not the host unit's owner, and not the host's controller. P1 controls a SOR_095 that P2 OWNS, and
#// that stolen marine carries a P1-owned Ascension Cable (LOF_215). Intended: the host is exhausted,
#// loses the upgrade, and the Cable lands in P1's hand — P2's hand stays EMPTY even though P2 owns the
#// unit it was attached to, and neither discard pile grows except by Bamboozle itself. Reading the
#// host's owner sends the Cable to P2; reading the host's controller happens to agree here only
#// because P1 owns the Cable, which is why the host and the upgrade are deliberately split-seat.
#// StripsUpgradeToHand is the same effect with owner and controller agreeing; this is the split.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2
WithP1GroundArenaUpgrade: 0:LOF_215
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:LOF_215
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:READY
