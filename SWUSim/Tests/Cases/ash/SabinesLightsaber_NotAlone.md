# RestoreForForceUnit
#// ASH_114 Sabine's Lightsaber (Upgrade, non-Vehicle) — "If attached unit is Sabine Wren or a Force unit,
#// it gains Restore 2." Attached to ASH_112 (a Force unit) it grants Restore; attached to SOR_095 (neither
#// Sabine nor Force) it does not.
## GIVEN
CommonSetup: ggw/ggk
WithP1GroundArena: ASH_112:1:0
WithP1GroundArenaUpgrade: 0:ASH_114
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:ASH_114
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_112
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore

---

# AttachFromHandToNonVehicle
#// ASH_114 Sabine's Lightsaber — "Attach to a non-Vehicle unit." Played from hand it attaches to the
#// friendly non-Vehicle SOR_095 (Battlefield Marine), which becomes its host.
## GIVEN
CommonSetup: ggw/ggk/{myResources:2;handCardIds:ASH_114}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_114

---

# RestoreHealsBaseOnAttack
#// ASH_114 Sabine's Lightsaber — attached to LOF_061 (Secretive Sage, a Force unit) it grants Restore 2.
#// The saber also adds +2/+2, so the Sage attacks for 4. Restore 2 heals 2 damage from P1's base: P1 base
#// goes from 5 damage to 3, and the enemy base takes 4.
## GIVEN
CommonSetup: ggw/ggk/{myBaseDamage:5}
WithP1GroundArena: LOF_061:1:0
WithP1GroundArenaUpgrade: 0:ASH_114
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P2BASEDMG:4

---

# RestoreForSabineWren
#// ASH_114 Sabine's Lightsaber — "If attached unit is Sabine Wren or a Force unit, it gains Restore 2."
#// Attached to SOR_142 (Sabine Wren, a non-Force unit) it grants Restore by NAME, not by the Force trait.
## GIVEN
CommonSetup: ggw/ggk
WithP1GroundArena: SOR_142:1:0
WithP1GroundArenaUpgrade: 0:ASH_114
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
