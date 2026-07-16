# GrantedAttackAction
#// SHD_155 Heroic Resolve (Upgrade, +1/+1) grants the host: "Action [2 resources, defeat a Heroic Resolve
#// on this unit]: Attack with this unit. It gets +4/+0 and gains Overwhelm for this attack." SOR_046 (3/7)
#// wears it (→4/8); using the action pays 2 resources, defeats the upgrade (host back to 3 base power),
#// then attacks with 3+4=7 power and Overwhelm at the enemy SOR_160 (2 HP): 2 defeats it and 5 overwhelms
#// to P2's base. Afterward the host is back to 3 power (upgrade gone, attack bonus expired).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_155
WithP2GroundArena: SOR_160:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
