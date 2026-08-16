# Bounty_ExpToTwoUnits
#// COVERAGE: offer=Offer_AnyUnitOnEitherSideAndEitherArena (pending SELECTABLEEXACT — every unit in play,
#//           both sides and both arenas, is eligible) · decline=Bounty_ExpToZeroUnits — the TARGET choice
#//           is not declinable; the soft pass is an AMOUNT of zero (a separate decline of the collect
#//           itself is the shared Bounty YES/NO, covered by UnlicensedHeadhunter.md) ·
#//           boundary=the amount, 2 (this section) vs 1 (Bounty_ExpToOneUnit) vs 0 (Bounty_ExpToZeroUnits),
#//           plus the empty-pool case Bounty_NoUnitsLeftInPlay_CollectResolvesToNothing ·
#//           control=the granted Bounty follows its HOST and is collected by the opponent of the host's
#//           CONTROLLER (P2 wears it, P1 collects, in every section here), and the reward's pool is not
#//           seat-restricted — Bounty_ExpCanLandOnAnEnemyUnit buffs a unit of the player it was taken from ·
#//           reqboundary=N/A — the grant snapshot, the collect and the Experience tokens all resolve inside
#//           the single action that removes the host; nothing is read back on a later request ·
#//           leave-play legs: defeat=Bounty_ExpToTwoUnits, capture=Bounty_CollectedWhenTheHostIsCaptured,
#//           return-to-hand=Bounty_CollectedWhenTheHostIsReturnedToHand
#// SHD_261 Rich Reward — attached unit gains "Bounty — Give an Experience token to each of up to 2
#// units." P2's marine wears it; LAW_124 defeats it; P1 collects and picks both surviving P1 units:
#// LAW_124 4/7 → 5/8, Consular Security Force 3/7 → 4/8.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4

---

# Offer_AnyUnitOnEitherSideAndEitherArena
#// SHD_261 Rich Reward — "Give an Experience token to each of up to 2 UNITS" has no friendly qualifier and
#// no arena restriction, so every unit in play is eligible, including the collector's OWN units and the
#// units of the player who was wearing the upgrade. P1's Industrious Team defeats the marine wearing Rich
#// Reward and takes the collect; the pick is left pending so the pool can be asserted: both P1 units
#// (ground + space) and both surviving P2 units (ground + space).

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP2SpaceArena: JTL_069:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# Bounty_ExpToOneUnit
#// SHD_261 Rich Reward — "up to 2" means one is a legal amount too. Same fixture as Bounty_ExpToTwoUnits,
#// but P1 names only Industrious Team: it goes 4/7 → 5/8 while Consular Security Force stays at its
#// printed 3/7 with no upgrade.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:POWER:3

---

# Bounty_ExpToZeroUnits
#// SHD_261 Rich Reward — the zero end of "up to 2". The target choice itself is not declinable; the soft
#// pass is an AMOUNT of zero, so P1 takes the Bounty (the collect is still accepted) and then names no
#// units. Both P1 units are left completely untouched — no Experience token anywhere.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:POWER:3

---

# Bounty_ExpCanLandOnAnEnemyUnit
#// SHD_261 Rich Reward — the concrete half of Offer_AnyUnitOnEitherSideAndEitherArena: "each of up to 2
#// units" really does let the collector buff a unit belonging to the player they just took the bounty from.
#// P1 collects and spends its single pick on P2's surviving Consular Security Force, which goes 3/7 → 4/8
#// while P1's own attacker gets nothing.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:4

---

# Bounty_NoUnitsLeftInPlay_CollectResolvesToNothing
#// SHD_261 Rich Reward — the bounty is still OFFERED when its reward can do nothing, because taking or
#// refusing a bounty is a decision in its own right. P1's Battlefield Marine trades with P2's Battlefield
#// Marine (3/3 into 3/3), which was the only wearer of Rich Reward; both die and the board is empty. P1
#// accepts the collect and the "up to 2 units" reward finds no units, so it simply resolves to nothing —
#// no follow-up pick is raised (P1NODECISION).

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1NODECISION

---

# Bounty_CollectedWhenTheHostIsCaptured
#// SHD_261 Rich Reward — a GRANTED Bounty is collected when its host is CAPTURED, not only when it is
#// defeated. P1 plays SHD_131 Take Captive; its only captor (Industrious Team) and only captive (the marine
#// wearing Rich Reward) both auto-resolve. The marine becomes a facedown captive under the Team, and P1 —
#// the opponent of its controller — collects, spending one Experience on the captor: 4/7 → 5/8, so it now
#// carries two subcards (the captive plus the Experience token).

## GIVEN
CommonSetup: grw/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# Bounty_CollectedWhenTheHostIsReturnedToHand
#// SHD_261 Rich Reward — the third leave-play leg. SHD_206 Spare the Target returns an enemy non-leader
#// unit to its owner's hand and collects that unit's Bounties, so the granted Bounty must be read off the
#// host BEFORE the bounce strips it. P1 bounces the marine wearing Rich Reward (it goes to P2's hand and
#// the upgrade to P2's discard) and collects, buffing its own Industrious Team 4/7 → 5/8.

## GIVEN
CommonSetup: ygw/ygw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_206
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
