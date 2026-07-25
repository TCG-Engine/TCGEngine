# OnAttack_NextUnitCostsLess
#// SEC_110 GNK Power Droid (Ground, 1/3) — On Attack: the next unit you play this phase costs 1 resource
#//   less. SEC_110 attacks P2's base (arming the discount); P1 then plays SOR_046 (cost 4 → 3), leaving 1
#//   of 4 resources.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_110:1:0
WithP1Hand: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:1

---

# OnAttack_DiscountExpiresNextPhase
#// SEC_110 GNK Power Droid — the armed "next unit costs 1 less" is "for this phase". If the discounted unit
#//   is not played before the phase ends, the discount is gone next action phase. GNK attacks (arms -1), P1
#//   passes to the next action phase, then plays SOR_046 (cost 4) at FULL price on 4 refreshed resources →
#//   0 left. (If the -1 had wrongly persisted, 1 would remain.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_110:1:0
WithP1Hand: SOR_046
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# OnAttack_SmuggleGetsDiscount
#// SEC_110 GNK Power Droid arms "next unit costs 1 less" — the discount applies to a unit played via
#// SMUGGLE too (regression: the Smuggle payment path formerly bypassed all next-unit discounts). GNK
#// attacks (arms -1); SHD_113 (Privateer Crew) has an effective Smuggle cost of 8 here (Command bracket,
#// off-aspect under this leader), so on exactly 7 ready resources it plays ONLY because the -1 brings it
#// to 7. (Without the armed discount the same 7 resources cannot pay 8.)

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: SEC_110:1:0
WithP1Resources: 6:SOR_046:1,1:SHD_113:1

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SmuggleResource:6

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_113


---

# OnAttack_SmuggleConsumesCharge
#// SEC_110's one-shot "next unit -1" must be CONSUMED by a Smuggle play (Smuggle bypasses ActivateCard,
#// where a normal play spends it — the fix consumes it on the Smuggle commit instead). GNK attacks (arms
#// -1); SHD_113 is smuggled (consuming the charge), leaving exactly 2 ready. The following normal SOR_063
#// (Vigilance cost 3) is then BLOCKED — it would only fit at 2 ready if a -1 had wrongly persisted (3->2).
#// Discriminator: with the charge intact, SOR_063 plays at 2 ready; here it does not, so ground count = 2.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: SEC_110:1:0
WithP1Hand: SOR_063
WithP1Deck: SOR_046
WithP1Deck: SOR_046
WithP1Resources: 9:SOR_046:1,1:SHD_113:1

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SmuggleResource:9
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_113
