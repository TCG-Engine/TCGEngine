# SHD_017 Lando Calrissian — the "costs 2 resources less" discount, proven at the boundary. P1 has ONLY
# the SHD_111 resource (Smuggle base cost 3 on a Command base). Without the -2 it would need 3 ready
# resources; with the -2 it costs 1, paid by exhausting SHD_111 itself. So Lando's action is affordable and
# Smuggles SHD_111 into space. (Only 1 smuggle target and, after the slot replaces, 1 resource to defeat,
# so both picks auto-resolve — the full flow drives in the runner.)

## GIVEN
CommonSetup: grk/rrk/{myLeader:SHD_017}
P1OnlyActions: true
WithP1Resources: 1:SHD_111:1
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1LEADER:EXHAUSTED
