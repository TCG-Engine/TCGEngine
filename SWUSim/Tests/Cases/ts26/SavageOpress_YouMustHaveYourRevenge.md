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

---

# EVERYUnitTiedForTheMostPowerGainsOverwhelm
#// TS26_05 Savage Opress — "EACH friendly unit with the most power". Two 3-power units are tied for the
#// lead, so both gain Overwhelm — the grant is not limited to a single unit.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:TS26_05}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm

---

# ATEMPORARYPowerBoostMovesTheGrant
#// TS26_05 Savage Opress — the grant tracks CURRENT power, including a this-phase buff. SOR_046 (3) and
#// SEC_080 (3) start tied; Prime Minister Almec (TS26_28, 8 here for two uncovered aspects) enters at 2
#// power and hands SEC_080 +2/+2, taking it to 5. It alone now has the most power, so it alone keeps
#// Overwhelm — SOR_046 loses the share it had a moment earlier.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:TS26_05;myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_28
WithP1GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:2:POWER:2
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P1GROUNDARENAUNIT:2:NOTKEYWORD:Overwhelm
