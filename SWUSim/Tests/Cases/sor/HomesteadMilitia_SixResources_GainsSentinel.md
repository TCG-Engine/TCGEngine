# SOR_113 Homestead Militia (3/4) guard — "While you control 6 or more resources,
# this unit gains Sentinel." (Already implemented in HasConditionalKeyword_Sentinel;
# this locks the ≥6-resource condition behaviorally — no prior coverage of it.)
# P2 controls 6 resources, so its Homestead Militia has Sentinel. P1's base-attack
# is force-redirected onto it (only valid target). Combat uses printed HP: P1's 3/3
# attacker deals 3 to the 3/4 Militia (survives); the Militia deals 3 back (attacker
# dies). P2's base takes 0 — proving Sentinel blocked the base attack.

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 6
WithP1GroundArena: SOR_095:1:0    # attacker (3/3)
WithP2GroundArena: SOR_113:1:0    # Homestead Militia (3/4) → Sentinel at 6 resources

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
