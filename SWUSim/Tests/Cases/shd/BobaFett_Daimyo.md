# Deployed_KeywordAura
#// SHD_008 Boba Fett (deployed) — "Each OTHER friendly unit that has 1 or more keywords gets +1/+0."
#// Deployed as a unit, Boba buffs the friendly Sentinel SOR_063 (2 power → 3) but not the vanilla SOR_210
#// (4 power → 4), and not himself.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008;myLeaderDeployed:true}
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_210:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:CARDID:SOR_210
P1GROUNDARENAUNIT:1:POWER:4

---

# Front_Decline
#// SHD_008 Boba Fett (front) — the reaction is a "may": declining leaves Boba ready and applies no buff.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_063
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3

---

# Front_ExhaustToBuff
#// SHD_008 Boba Fett (front, undeployed) — "When you play a unit that has 1 or more keywords: You may
#// exhaust this leader. If you do, give a friendly unit +1/+0 for this phase." P1 plays SOR_063 (Sentinel,
#// keyword-only), accepts the reaction (exhausting Boba), and buffs its existing SOR_046 (3/7) to 4 power.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_063
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4

---

# Front_NoKeyword_NoReaction
#// SHD_008 Boba Fett (front) — a played unit with NO keywords does not trigger the reaction: Boba stays
#// ready and no buff is offered (SOR_046 is vanilla).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_046
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
