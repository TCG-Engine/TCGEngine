# UpgradeGrantsWhenDefeated
#// ASH_134 Warrior's Legacy (Upgrade, +2/+1) — "Attached unit gains: When Defeated: create a Mandalorian
#// token." Attached to a Stormtrooper (3/1 → 5/2); it attacks SEC_080 (3/3): deals 5 (SEC_080 dies), takes
#// 3 counter and dies (2 HP) → the granted When Defeated creates a Mandalorian token.
## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:ASH_134
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P2GROUNDARENACOUNT:0

---

# WhenDefeatedUnderEnemyControl_TokenToOpponent
#// ASH_134 Warrior's Legacy grants the attached unit "When Defeated: create a Mandalorian token." When P2
#// takes control of the attached unit with No Glory, Only Results (JTL_043) and defeats it, the granted When
#// Defeated resolves under P2's control → the Mandalorian token (ASH_T01) is created in P2's ground arena
#// (exhausted), NOT the owner's.
## GIVEN
CommonSetup: yrw/grw
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_134
WithP2Resources: 10
WithP2Hand: JTL_043
## WHEN
- P2>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:ASH_T01
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# UpgradeItselfDefeated_NoToken
#// ASH_134 Warrior's Legacy grants the When Defeated to the ATTACHED UNIT. Defeating the UPGRADE itself
#// (via SEC_163 Outer Rim Constable's "defeat an upgrade") — while the unit survives — does NOT trigger it:
#// no Mandalorian token is created.
## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:ASH_134
WithP1Hand: SEC_163
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:CARDID:SEC_163
P1NODECISION
