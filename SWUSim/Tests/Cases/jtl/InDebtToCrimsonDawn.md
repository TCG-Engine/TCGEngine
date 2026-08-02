# RegroupDeclineExhaust
#// JTL_192 In Debt to Crimson Dawn — When attached unit readies: exhaust it unless its controller pays 2.
#// The host SOR_095 (exhausted) readies at the regroup ready step; P1 declines to pay and it is exhausted.

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:JTL_192
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# RegroupPayKeepsReady
#// JTL_192 In Debt to Crimson Dawn — paying 2 resources keeps the host ready. P1 pays the tax at the
#// regroup ready step, so SOR_095 stays ready.

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:JTL_192
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# ReadiedOutsideRegroup_TaxFires
#// JTL_192 In Debt to Crimson Dawn — "When attached unit readies" fires for ANY ready, not just the regroup
#// ready step. P1's exhausted SOR_095 carries In Debt; P1 plays Keep Fighting (SOR_169) to ready it
#// mid-phase — the tax triggers immediately, P1 declines to pay, and SOR_095 is exhausted right back.
#// (Regression guard: this path was previously wired only for the regroup ready step.)

## GIVEN
CommonSetup: rrk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:JTL_192
WithP1Hand: SOR_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EnemyUnit_ControllerPrompted
#// JTL_192 In Debt to Crimson Dawn — when attached to an ENEMY unit, the tax prompts the CONTROLLER of that
#// unit (P2), not the upgrade's owner. P2's exhausted SOR_095 carries In Debt; at the regroup ready step P2
#// is the one asked to pay-or-exhaust. P2 declines, so the enemy unit is exhausted right back.

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_095:0:0
WithP2GroundArenaUpgrade: 0:JTL_192
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# ControlChanged_NewControllerPaysTheTax
#// JTL_192 In Debt to Crimson Dawn — "Exhaust it unless ITS CONTROLLER pays 2 resources." The tax follows
#// the unit's CURRENT controller, not the upgrade's owner and not the unit's owner. Here SOR_095 is OWNED
#// by P2 but CONTROLLED by P1 (the end state after a take-control effect) and sits exhausted carrying In
#// Debt. At the regroup ready step it is P1 — the new controller — who is asked to pay-or-exhaust; P1 pays,
#// so the unit stays READY and 2 of P1's OWN resources are spent (5 ready → 3).
#// Companion to EnemyUnit_ControllerPrompted above, which covers the un-stolen enemy-host case.
#// ⚠ Uses the 3rd field of WithP{n}GroundArenaControlled (CARD:ownerSeat:status) to seat the stolen unit
#// EXHAUSTED — without it the unit never readies and the trigger cannot fire.

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2:0
WithP1GroundArenaUpgrade: 0:JTL_192
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:3
