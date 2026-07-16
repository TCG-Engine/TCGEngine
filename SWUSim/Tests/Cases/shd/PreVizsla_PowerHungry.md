# OnAttack_StealUpgrade
#// SHD_142 Pre Vizsla — the same upgrade-steal fires on the On Attack window too. Deployed Pre Vizsla
#// attacks P2's base; its On Attack lets P1 pay 1 to move SOR_069 off P2's SOR_046 onto Pre Vizsla.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_142:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayed_StealUpgrade
#// SHD_142 Pre Vizsla (Unit, cost 7, Villainy/Aggression, Ground) — "When Played/On Attack: You may pay the
#// cost of an upgrade attached to another non-Vehicle unit. If you do, take control of that upgrade and
#// attach it to this unit, if able." P1 plays Pre Vizsla; P2's SOR_046 wears SOR_069 (cost 1). P1 pays 1
#// and moves SOR_069 onto Pre Vizsla — SOR_046 loses it, Pre Vizsla gains it.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 15
WithP1Hand: SHD_142
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
