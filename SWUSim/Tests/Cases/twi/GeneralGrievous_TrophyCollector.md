# AspectIgnore_LightsaberOnHim
#// TWI_034 General Grievous (Unit 4/4, cost 3, Separatist/Official) — "Ignore the aspect penalty on each
#// Lightsaber upgrade you play on this unit." Under an Aggression base+leader, TWI_121 General's Blade
#// (Command Lightsaber, cost 3) is off-aspect (+2 penalty → 5). Played onto Grievous (the only friendly
#// host) the penalty is waived → it costs its printed 3 and attaches with exactly 3 resources.
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_121}
P1OnlyActions: true
WithP1GroundArena: TWI_034:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_034
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:0

---

# AspectIgnore_OnlyOnGrievous
#// TWI_034 General Grievous — the aspect-penalty waiver is host-specific (only when the Lightsaber is
#// played ON Grievous). With a non-Grievous host (SEC_080), TWI_121 (Command Lightsaber) keeps its +2
#// off-aspect penalty → costs 5, unaffordable on 3 resources: the play silently fails, the upgrade stays
#// in hand and nothing is attached.
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_121}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:3

---

# OnAttack_FourLightsabers_DefeatFour
#// TWI_034 General Grievous — "On Attack: If this unit has 4 or more Lightsaber upgrades attached to him,
#// defeat 4 enemy units." Grievous carries 4 Lightsabers (TWI_248, SOR_053, TWI_152, LOF_090 — all pure
#// stat/When-Played on a non-Force host, so no extra On-Attack triggers) and attacks the base → his On
#// Attack defeats all 4 enemy units (4 present → all defeated).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_034:1:0
WithP1GroundArenaUpgrade: 0:TWI_248
WithP1GroundArenaUpgrade: 0:SOR_053
WithP1GroundArenaUpgrade: 0:TWI_152
WithP1GroundArenaUpgrade: 0:LOF_090
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0 SOR_128:1:0 LAW_180:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:0

---

# OnAttack_ThreeLightsabers_NoDefeat
#// TWI_034 General Grievous — the On Attack mass-defeat needs 4+ Lightsabers. With only 3 Lightsabers
#// attached, attacking does NOT defeat any enemy units (the gate fails): all 4 enemies remain.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_034:1:0
WithP1GroundArenaUpgrade: 0:TWI_248
WithP1GroundArenaUpgrade: 0:SOR_053
WithP1GroundArenaUpgrade: 0:TWI_152
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0 SOR_128:1:0 LAW_180:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:4
