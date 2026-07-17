# DeployedGrantsOverwhelmToOthers
#// TS26_05 Savage Opress (leader deployed, 3/7) — Raid 3 + Overwhelm + "Each other friendly unit gains
#// Overwhelm." The other friendly SEC_080 gains Overwhelm; the deployed Savage has Overwhelm innately.
## GIVEN
CommonSetup: rrk/rrk/{myLeader:TS26_05:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm

---

# FrontMostPowerOverwhelm
#// TS26_05 Savage Opress (leader front, undeployed) — "Each friendly unit with the most power among
#// friendly units gains Overwhelm." SOR_198 (6 power) has the most and gains Overwhelm; SEC_080 (3 power)
#// does not.
## GIVEN
CommonSetup: rrk/rrk/{myLeader:TS26_05}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_198:1:0 SEC_080:1:0]
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
