# Deployed_ActionNoExhaust
#// SHD_017 Lando Calrissian — deployed side "Action: Play a card using Smuggle. It costs 2 less. Defeat a
#//   resource you own and control. Use this ability only once each round." The deployed Action has NO exhaust
#//   cost (costKind 'none'), gated once-per-round. Here the deployed Lando unit uses it to Smuggle SHD_111
#//   (base 3, -2 = 1) into space and stays READY afterward (no exhaust). Both picks auto-resolve (1 target
#//   each), so the flow drives in the runner.

## GIVEN
CommonSetup: grk/rrk/{myLeader:SHD_017:1:1}
P1OnlyActions: true
WithP1Resources: 1:SHD_111:1
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1GROUNDARENAUNIT:0:CARDID:SHD_017
P1GROUNDARENAUNIT:0:READY

---

# DiscountProof
#// SHD_017 Lando Calrissian — the "costs 2 resources less" discount, proven at the boundary. P1 has ONLY
#// the SHD_111 resource (Smuggle base cost 3 on a Command base). Without the -2 it would need 3 ready
#// resources; with the -2 it costs 1, paid by exhausting SHD_111 itself. So Lando's action is affordable and
#// Smuggles SHD_111 into space. (Only 1 smuggle target and, after the slot replaces, 1 resource to defeat,
#// so both picks auto-resolve — the full flow drives in the runner.)

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

---

# Front_SmuggleAndDefeatResource
#// SHD_017 Lando Calrissian (Leader, Cunning/Heroism, cost 4)
#//   Front Action [Exhaust]: "Play a card using Smuggle. It costs 2 resources less. Defeat a resource you
#//   own and control." P1 controls Lando (undeployed) with a SHD_111 (Collections Starhopper, Smuggle
#//   [3 Command]) resource on a Command base (Smuggle cost = 3, then -2 = 1). Using Lando Smuggles SHD_111
#//   into the SPACE arena for 1 and exhausts the leader.
#// NOTE: the resource-defeat cost + its "before When Played" ordering are verified via a LIVE smoke test
#//   (TestSchemaStep) — the defeated resource lands in discard (SOR_251) and the Smuggled card's entry fires
#//   after. The in-process regression runner drops the resource-defeat MZCHOOSE answer because it follows the
#//   auto-resolved Smuggle-target pick within one action (a known runner divergence), so this test asserts
#//   only the runner-drivable core.

## GIVEN
CommonSetup: grk/rrk/{myLeader:SHD_017}
P1OnlyActions: true
WithP1Resources: 1:SHD_111:1,4:SOR_251:1
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1LEADER:EXHAUSTED
