# OppUpgrade_GiveExp
#// SEC_095 Theed Security (Ground, 2/3) — When Played: if an opponent controls an upgrade, give an
#//   Experience token to a unit. Enemy SOR_095 bears SOR_120 → give Experience to a friendly.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# NoOppUpgrade_NoExperience
#// SEC_095 Theed Security — the When Played Experience grant is gated on an opponent controlling an
#//   upgrade. With no enemy upgrade anywhere, SEC_095 just enters play; no Experience token is granted and
#//   there is no target decision. P2's SOR_095 has no upgrade.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
