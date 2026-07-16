# 068_071_GrantBadge
#// SHD_068 Public Enemy / SHD_071 Top Target — a unit wearing either upgrade shows the Bounty badge
#// (the shared $SWUBountyGrantUpgrades scan in HasConditionalKeyword_Bounty). One test guards both IDs
#// in the list.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_068
WithP1GroundArenaUpgrade: 1:SHD_071

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P1GROUNDARENAUNIT:1:HASKEYWORD:Bounty

---

# 173_176_GrantBadge
#// SHD_173 Guild Target / SHD_176 Death Mark — hosts show the Bounty badge (guards both IDs in the
#// SWUBountyGrantUpgrades list).

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_173
WithP1GroundArenaUpgrade: 1:SHD_176

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P1GROUNDARENAUNIT:1:HASKEYWORD:Bounty
