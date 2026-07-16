# WhenPlayed_MovesTokenUpgradeToDifferentUnit
#// JTL_242 Shuttle ST-149, Under Krennic's Authority — Shielded + "When Played/When Defeated: You may take
#// control of a token upgrade on a unit and attach it to a different eligible unit." Playing it raises two
#// entry triggers (Shielded + When Played); resolving When Played first, P1 takes the Experience token
#// (SOR_T01) off Alliance X-Wing (SOR_237) and attaches it to Green Squadron A-Wing (SOR_141). Then Shielded
#// resolves, giving the Shuttle its own Shield.

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
- P1>AnswerDecision:myTempZone-0
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
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_141
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:SOR_T01
P1DISCARDCOUNT:1