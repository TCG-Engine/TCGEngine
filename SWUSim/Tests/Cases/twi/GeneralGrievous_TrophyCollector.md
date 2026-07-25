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




---

# GeneralsBlade_OnAttack_SmuggleGetsDiscount
#// TWI_121 General's Blade grants a Jedi host "On Attack: the next unit costs 2 less" — the discount also
#// reduces a unit played via SMUGGLE (regression: the Smuggle payment path formerly bypassed it). The Blade
#// is played onto Jedi Yoda (SOR_045); Yoda attacks (arms -2). SHD_113 has an effective Smuggle cost of 8
#// here; after the Blade there are exactly 6 ready, so the smuggle succeeds ONLY because the -2 brings it
#// to 6. (Without the armed discount the same 6 resources cannot pay 8.)

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: SOR_045:1:0
WithP1Hand: TWI_121
WithP1Resources: 10:SOR_046:1,1:SHD_113:1

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>SmuggleResource:10

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_113
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
