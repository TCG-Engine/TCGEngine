# WhenPlayed_MovesTokenUpgradeToDifferentUnit
#// JTL_242 Shuttle ST-149, Under Krennic's Authority — Shielded + "When Played/When Defeated: You may take
#// control of a token upgrade on a unit and attach it to a different eligible unit." Playing it raises two
#// entry triggers (Shielded + When Played); resolving When Played first, P1 takes the Experience token
#// (SOR_T01) off Alliance X-Wing (SOR_237) and attaches it to Green Squadron A-Wing (SOR_141). Then Shielded
#// resolves, giving the Shuttle its own Shield.

#// ⚠ ADDRESSING: the upgrade pick is offered as SUBCARD mzIDs ("<hostMz>.u<subIdx>", the raw Subcards
#// key) so the player picks the token ON THE BOARD, still attached to its host. It used to stage the
#// candidates into TempZone as bare CardIDs and offer myTempZone-N, which rendered as a flat card-art
#// popup naming no unit at all — unusable for a pool that spans every unit on the board and routinely
#// holds several identical Shield tokens.
#//
#// COVERAGE: offer asserted -> Offer_PoolIsBoardAddressedAcrossBothSides; request boundary -> N/A (no
#// this-phase/this-round duration); control change -> N/A (a When Played rider does not re-fire on a
#// later control change, and the ability reads no owner-scoped zone); boundary pair -> N/A (no numeric
#// threshold); decline branch -> WhenPlayed_MayDecline_ShieldStillResolves.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_242
WithP1SpaceArena: [SOR_237:1:0 SOR_141:1:0]
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:mySpaceArena-0.u0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_141
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:SOR_T01
P1SPACEARENAUNIT:2:CARDID:JTL_242
P1SPACEARENAUNIT:2:SHIELDCOUNT:1

---

# WhenPlayed_MayDecline_ShieldStillResolves
#// JTL_242 — the token move is a "may": declining it (Pass the When Played) leaves all tokens where they
#// are, but the Shielded trigger still resolves and gives the Shuttle its Shield.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_242
WithP1SpaceArena: [SOR_237:1:0 SOR_141:1:0]
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1SPACEARENAUNIT:2:CARDID:JTL_242
P1SPACEARENAUNIT:2:SHIELDCOUNT:1

---

# WhenDefeated_MovesTokenUpgrade
#// JTL_242 — the same "take a token upgrade and attach it to a different eligible unit" also fires on
#// When Defeated. P2 defeats P1's Shuttle with Vanquish (TWI_077). The Shuttle's controller (P1) is the
#// NON-active player, so its When Defeated lands as a static RESOLVE_TRIGGER on P1's queue; `P1>Drain`
#// runs it (mirroring production's post-action drain), then P1 moves the Experience token (SOR_T01) off
#// Alliance X-Wing (SOR_237) — the only other eligible unit, Green Squadron A-Wing (SOR_141), auto-resolves
#// as the destination.

## GIVEN
CommonSetup: rrk/bbk/{theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: [JTL_242:1:0 SOR_237:1:0 SOR_141:1:0]
WithP1SpaceArenaUpgrade: 1:SOR_T01
WithP2Hand: TWI_077

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:mySpaceArena-0.u0     # the defeated Shuttle has already left, so SOR_237 is index 0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_141
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:SOR_T01
P1DISCARDCOUNT:1

---

# WhenPlayed_MovesOwnShieldFromShielded
#// JTL_242 — the token the move relocates can be the Shuttle's OWN Shield created by its Shielded keyword.
#// Resolving the Shielded entry trigger FIRST (EffectStack-1) gives the Shuttle a Shield; the When Played
#// move then takes that Shield token (the only token in play) and attaches it to the one other eligible
#// unit, SOR_237 (auto-resolved destination). The Shuttle ends with no Shield; SOR_237 gains it.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_242
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:mySpaceArena-1.u0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:1:CARDID:JTL_242
P1SPACEARENAUNIT:1:SHIELDCOUNT:0

---

# Offer_PoolIsBoardAddressedAcrossBothSides
#// The pool is exactly the TOKEN upgrades, each addressed on its own host — a friendly Experience and an
#// ENEMY Shield — and NOT the non-token upgrade sharing a host with one of them. Asserting the mzIDs is
#// what proves the host association survives into the offer: the retired TempZone staging could only ever
#// have produced myTempZone-0/myTempZone-1, which name no unit at all, so a player facing two candidates
#// on different units had no way to tell which was which.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: nbk/nbk/{myResources:8}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [SEC_164:0:0]
WithP1GroundArenaUpgrade: [0:SOR_T01 0:ASH_086]
WithP2GroundArena: [HMW_107:0:0]
WithP2GroundArenaUpgrade: [0:SOR_T02]
WithP1Hand: [JTL_242]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0&theirGroundArena-0.u0

---

# Offer_TwoIdenticalShieldsAreDistinctTargets
#// Two Shield tokens on ONE unit are indistinguishable by CardID — under the retired TempZone staging both
#// staged as the same bare SOR_T02 art with nothing to tell them apart. As subcard mzIDs they are distinct
#// addresses (.u0 / .u1). This is precisely why the sub index is the RAW Subcards key rather than a
#// de-duplicated or filtered ordinal.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: nbk/nbk/{myResources:8}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [SEC_164:0:0]
WithP1GroundArenaUpgrade: [0:SOR_T02 0:SOR_T02]
WithP2GroundArena: [HMW_107:0:0]
WithP1Hand: [JTL_242]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0&myGroundArena-0.u1

---

# Move_EnemyTokenTakenByItsBoardAddress
#// Resolution through the board-addressed pick: an ENEMY Shield is chosen by its subcard mzID (in the
#// deciding player's frame, so "their...") and moves onto the chosen friendly unit. Asserts BOTH sides —
#// the source host loses it and the destination gains it — so a no-op or a wrong-host move cannot pass.

## GIVEN
CommonSetup: nbk/nbk/{myResources:8}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [SEC_164:0:0]
WithP2GroundArena: [HMW_107:0:0]
WithP2GroundArenaUpgrade: [0:SOR_T02]
WithP1Hand: [JTL_242]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0.u0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
