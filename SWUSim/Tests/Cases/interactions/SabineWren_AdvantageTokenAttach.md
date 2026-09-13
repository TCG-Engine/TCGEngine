#// ASH_208 Sabine Wren: "When 1 or more upgrades attach to this unit (including from Shielded): You may exhaust
#// a ground unit." An ADVANTAGE token (ASH_T02, a token upgrade) attaching to her must trigger it, exactly like
#// a Shield token (Shielded) or Experience tokens (ash/SabineWren_ILearnedTheHardWay.md::
#// ExperienceTokensFromEffect_TriggersOnce).
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): DoGiveAdvantageToken (GameLogic.php) calls
#//   _SWUAsh208OnUpgradeAttach like the Shield and Experience givers.
#//
#// FOUND BY: sweep retro #3 of run 2 (2026-09-13): ORDER ASH_017 + ASH_208:Shielded (60×), aggro_greef.
#//
#// ★ WAS RED (3 sections) — candidate engine bug, a bypassed observer (memory "parallel funnels skip the shared
#//   chain"). DoGiveShieldToken, DoGiveExperienceToken and the generic token attach all call
#//   _SWUAsh208OnUpgradeAttach; DoGiveAdvantageToken (GameLogic.php, "Mirrors DoGiveExperienceToken") does not.
#//   So NO Advantage giver ever reaches Sabine: Greef Karga ASH_017 (either side), Danger Squadron Wingmen
#//   ASH_157, and any other. Probe 2026-09-13, both orders with Greef, and with Wingmen alone: the Advantage
#//   attaches and no Sabine prompt appears.
#//   Second, smaller claim in RED 1: Greef's Advantage attaches DURING Greef's trigger, so Sabine's trigger
#//   from it is NESTED and resolves before the still-pending Shielded (CR 7.6.11). Before the fix the Shield
#//   attached first, because the attach from the Advantage raised nothing.
#//
#// Cards: ASH_017 Greef Karga (front: "When you play or create a unit: You may exhaust this leader. If you do,
#//   give an Advantage token to that unit.") · ASH_157 Danger Squadron Wingmen 4/5 space ("On Attack: You may
#//   give an Advantage token to another unit.") · SOR_120 Academy Training (upgrade) · SOR_095 Battlefield
#//   Marine · SEC_028 Trayus Acolyte.
#//
# RED_GreefFirst_TheAdvantageAttaching_TriggersSabine_BeforeTheShieldArrives
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_208
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_017
- P1>AnswerDecision:YES
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_ground_unit
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# RED_ShieldedFirst_ThenGreef_TwoAttachEvents_TwoExhausts
#// The Shield attaches (Sabine exhausts the Marine), then Greef's Advantage attaches: a SECOND attach event, so
#//   a second trigger (Sabine exhausts the Acolyte).
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_208
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Shielded
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_ground_unit

---

# RED_WingmenGivesSabineAnAdvantage_SheTriggers
#// No Greef at all: the Wingmen's On Attack gives Sabine (in play) an Advantage token. Her trigger must follow.
## GIVEN
CommonSetup: gyw/brk/{myLeader:SOR_005}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_157:1:0
WithP1GroundArena: ASH_208:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_ground_unit

---

# CONTROL_SameBoard_ARealUpgradeOnSabine_SheTriggers
#// The observer itself works on this board: Academy Training attached to Sabine raises her prompt.
## GIVEN
CommonSetup: gyw/brk/{myLeader:SOR_005}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1GroundArena: ASH_208:1:0
WithP1Hand: SOR_120
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_ground_unit
