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

---

# NoShieldOnNonLeia
#// LAW_111 Leia's Disguise — the "When Played: give a Shield" clause is conditional on the attached unit
#// being Leia Organa. Attached to SOR_095 Battlefield Marine (not Leia), no Shield is granted: the unit
#// just carries the disguise (1 upgrade, 0 shields).

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# AttachNonVehicleOnly
#// LAW_111 Leia's Disguise — "Attach to a non-Vehicle unit." A friendly Vehicle (SOR_232 AT-ST) is NOT a
#// legal host; only the non-Vehicle SOR_095 is selectable.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_232:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
