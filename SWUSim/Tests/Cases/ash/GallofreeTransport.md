# WhenDefeated_TwoToFriendly
#// ASH_254 Gallofree Transport (Space, 3/5) — When Defeated: give 2 Advantage tokens to a friendly unit.
#// Pre-damaged to 1 HP, ASH_254 attacks SOR_225 (2/1) and dies to the counter; its WhenDefeated gives 2
#// Advantage tokens to the surviving friendly unit (SOR_237, which reindexes to space-0 after ASH_254 dies).
## GIVEN
CommonSetup: yyw/yyk
WithP1SpaceArena: ASH_254:1:4
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:2

---

# SoleFriendlyUnit_NoTarget
#// ASH_254 Gallofree Transport — When Defeated gives 2 Advantage to a friendly unit, but the defeated
#// Gallofree is itself gone. As the only friendly unit, its death leaves no target and nothing happens.
## GIVEN
CommonSetup: yyw/yyk
WithP1SpaceArena: ASH_254:1:4
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:0
P1NODECISION

---

# WhenDefeated_ToFriendlyGroundUnit
#// ASH_254 Gallofree Transport (Space) — When Defeated gives 2 Advantage tokens to a friendly unit in EITHER
#// arena. Pre-damaged to 1 HP, it attacks SOR_225 (2/1) and dies to the counter; the only surviving friendly
#// unit is a ground Battlefield Marine (SOR_095), which receives the 2 Advantage tokens.
## GIVEN
CommonSetup: yyw/yyk
WithP1SpaceArena: ASH_254:1:4
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2

---

# NGOR_WhenDefeatedResolvesForNewController
#// ASH_254 Gallofree Transport — the When Defeated "give 2 Advantage tokens to a friendly unit" resolves
#// for whoever controls it at defeat. P2 uses No Glory, Only Results (JTL_043) to take control of P1's
#// Gallofree and defeat it, so "friendly" means P2's side: the 2 Advantage tokens go to P2's own SOR_237.
## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 20
WithP2Hand: JTL_043
WithP1SpaceArena: ASH_254:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:ADVANTAGECOUNT:2
