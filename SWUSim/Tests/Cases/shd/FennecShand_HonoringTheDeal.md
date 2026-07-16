# FennecShand_Deployed_PlayAmbush
#// SHD_016 Fennec Shand (deployed Action) — same play-≤4 + Ambush grant. Deployed (5 resources), the
#// deployed Action plays SOR_229 (cost 2) at index 1 with Ambush (no enemy units → Ambush attack skipped).

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_229
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush

---

# FennecShand_Front_PlayAmbush
#// SHD_016 Fennec Shand (front Action [1 resource, Exhaust]) — "Play a unit that costs 4 or less from
#// your hand (paying its cost). Give it Ambush for this phase." SOR_229 (cost 2) is played and gains
#// Ambush; with no enemy units the Ambush attack is skipped, so it sits in play with the keyword.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229
WithP1Resources: 6

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
