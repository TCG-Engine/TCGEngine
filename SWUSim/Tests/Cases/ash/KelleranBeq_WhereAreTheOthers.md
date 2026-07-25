# PowerPerZeroPowerUnit
#// ASH_206 Kelleran Beq (Ground, 3/5, Ambush) — gets +1/+0 for each OTHER unit (friendly and enemy) with 0
#// power. With a friendly ASH_072 (0 power) and an enemy ASH_073 (0 power) on the board, Kelleran is at
#// power 3 + 2 = 5.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: ASH_206:1:0
WithP1GroundArena: ASH_072:1:0
WithP2GroundArena: ASH_073:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_206
P1GROUNDARENAUNIT:0:POWER:5

---

# DoesNotCountItself_WhenDebuffedToZero
#// ASH_206 Kelleran Beq — his "+1/+0 for each OTHER 0-power unit" excludes HIMSELF. Talzin's Assassin
#// (LOF_035) uses the Force to give Kelleran -3/-3 → 0/2; he stays at power 0 (does NOT self-boost to 1).
#// P1 then plays a 0-power TIE Bomber (JTL_237): Kelleran rises to power 1 (the Bomber only, still not himself).
## GIVEN
CommonSetup: yyk/bbk/{myResources:3;handCardIds:JTL_237}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Force: true
WithP2Resources: 4
WithP2Hand: LOF_035
WithP1GroundArena: ASH_206:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_206
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:2
