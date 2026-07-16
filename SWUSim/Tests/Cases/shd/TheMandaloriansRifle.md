# MandalorianRifle_CaptureExhausted
#// SHD_251 The Mandalorian's Rifle — "When Played: If attached unit is The Mandalorian, he captures an
#// exhausted enemy non-leader unit." Played onto SHD_049 (The Mandalorian); the exhausted enemy SHD_095
#// is captured (removed from its arena).

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_049:1:0
WithP1Hand: SHD_251
WithP2GroundArena: SHD_095:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# MandalorianRifle_NonMandalorianHost_NoCapture
#// SHD_251 — the capture is gated on the host being "The Mandalorian". Attached to a non-Mandalorian
#// host (SOR_046) with an exhausted enemy present, no capture happens.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_251
WithP2GroundArena: SHD_095:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# MandalorianRifle_ReadyEnemy_NoCapture
#// SHD_251 — only EXHAUSTED enemies can be captured. With the only enemy ready (SHD_095 status 1), the
#// whenPlayed finds no valid target: the upgrade attaches but nothing is captured.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_049:1:0
WithP1Hand: SHD_251
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
