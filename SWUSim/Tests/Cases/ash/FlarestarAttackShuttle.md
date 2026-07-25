# PlayedGiveAdvantage
#// ASH_167 Flarestar Attack Shuttle (Space, 2/1) — When Played/When Defeated: you may give an Advantage
#// token to a unit. On play, gives one to a friendly Marine.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_167}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# DefeatedGiveAdvantage
#// ASH_167 Flarestar Attack Shuttle (Space, 2/1) — When Defeated: you may give an Advantage token to a unit.
#// Flarestar attacks Alliance X-Wing (SOR_237, 2/3); it deals 2 (X-Wing survives) and dies to the 2 counter.
#// Its When Defeated then gives an Advantage token to the surviving friendly A-Wing (SEC_213), which
#// reindexes to space-0 after Flarestar leaves play.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_167:1:0
WithP1SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_213
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:2

---

# NGOR_WhenDefeatedResolvesForNewController
#// ASH_167 Flarestar Attack Shuttle — the When Defeated "you may give an Advantage token to a unit"
#// resolves for whoever controls it at defeat. P2 uses No Glory, Only Results (JTL_043) to take control of
#// P1's Flarestar and defeat it, so the Advantage token is placed by P2 onto its own SOR_046.
## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 20
WithP2Hand: JTL_043
WithP1SpaceArena: ASH_167:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
