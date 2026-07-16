# LegalAuthority_CaptureWeaker
#// SHD_124 Legal Authority — "When Played: Attached unit captures an enemy non-leader unit with less
#// power than it." Played onto SOR_095 (3 power); the enemy SHD_095 (2 power < 3) is captured (removed
#// from its arena, held facedown under the host).

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SHD_124
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# LegalAuthority_NoWeakerEnemy_NoCapture
#// SHD_124 Legal Authority — the capture requires an enemy with strictly less power. Played onto
#// SOR_095 (3 power) with the only enemy being SOR_046 (3 power, NOT less) → the upgrade attaches but no
#// capture happens (no second decision), and the enemy stays on the board.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SHD_124
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
