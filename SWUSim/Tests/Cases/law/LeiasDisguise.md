# WhenPlayedOnLeia_Shield
#// LAW_111 Leia's Disguise (Upgrade, cost 2, Vigilance/Heroism) — "Attach to a non-Vehicle unit. ...
#// When Played: If attached unit is Leia Organa, give a Shield token to a friendly unit." Played onto
#// SOR_189 (Leia Organa) — the only friendly unit, so the shield auto-targets her.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_189:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_189
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
