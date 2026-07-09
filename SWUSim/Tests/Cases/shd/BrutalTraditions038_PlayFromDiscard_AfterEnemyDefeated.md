# SHD_038 Brutal Traditions (2-cost +1/+2 upgrade, Villainy/Vigilance) — "Action: If an enemy unit was
# defeated this phase, play this upgrade from your discard pile (paying its cost)." Unlike LAW_200 (which
# stamps TPP at discard time), its condition can turn true AFTER it's in discard, so its play-from-discard
# availability is computed live from the SWU_ENEMY_DEFEATED phase flag. LAW_124 (4/7) kills SOR_128 (3/1)
# and survives → an enemy was defeated this phase → SHD_038 becomes playable from discard, attaches to the
# only friendly unit (LAW_124 → 5/9), and P1 pays its cost of 2.

## GIVEN
CommonSetup: brk/rrk/{myResources:4;discardCardIds:SHD_038}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_038
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:0
P1RESAVAILABLE:2
