
---

# WhenDefeated_GiveAdvantage
#// ASH_167 Flarestar Attack Shuttle — the same "give an Advantage token" also fires When Defeated. Flarestar
#// (2/1) dies attacking the space wall ASH_081; its When Defeated gives an Advantage token to a friendly unit.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_167:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: ASH_081:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
