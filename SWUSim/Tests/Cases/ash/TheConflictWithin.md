# RegroupExhaustUnlessPay
#// ASH_088 The Conflict Within (Upgrade/Condition) — Attached unit gains "When this unit readies: you may
#// pay 3 resources. If you don't, exhaust this unit." Host SOR_095 starts exhausted; at the regroup ready
#// step P1 declines to pay, so SOR_095 is exhausted again (stays exhausted).
## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:ASH_088
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

# EnemyAttach_ControllerPromptedAtRegroup_Declines
#// ASH_088 The Conflict Within can attach to an ENEMY unit. The prompt at the regroup ready step goes to the
#// CONTROLLER of the attached unit (P2 here), not the player who played the upgrade. P2's host SOR_095 starts
#// exhausted; at regroup P2 declines to pay 3, so its own unit is exhausted right back.
## GIVEN
CommonSetup: gyk/gyk/{theirResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_095:0:0
WithP2GroundArenaUpgrade: 0:ASH_088
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:NO
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# EnemyAttach_ControllerPromptedAtRegroup_Pays
#// ASH_088 The Conflict Within attached to an enemy unit — the controller P2 pays 3 resources at the regroup
#// ready step, so P2's host SOR_095 stays ready.
## GIVEN
CommonSetup: gyk/gyk/{theirResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_095:0:0
WithP2GroundArenaUpgrade: 0:ASH_088
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:READY

---

# RegroupPayKeepsReady
#// ASH_088 The Conflict Within — paying 3 resources at the regroup ready step keeps the host ready. P1
#// pays, so SOR_095 stays ready.
## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:ASH_088
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

# ControlChanged_CurrentControllerPays
#// ASH_088 The Conflict Within — the pay-or-exhaust choice falls on whoever CONTROLS the unit now, even if
#// control changed hands. SOR_095 is owned by P2 but currently controlled by P1; carrying The Conflict Within,
#// P1 attacks with it (exhausting it) then at the regroup ready step P1 (the current controller) is prompted
#// and pays 3 to keep it ready.
## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_095:2
WithP1GroundArenaUpgrade: 0:ASH_088
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY

---

# ReadiedOutsideRegroup_TaxFires
#// ASH_088 The Conflict Within — "When this unit readies" fires for ANY ready, not just the regroup ready
#// step. Host SOR_095 (exhausted) carries The Conflict Within; P1 plays Keep Fighting (SOR_169) to ready it
#// mid-phase — the tax triggers immediately, P1 declines to pay 3, and SOR_095 is exhausted right back.
#// (Regression guard: this path was previously wired only for the regroup ready step — the JTL_192 twin bug.)
## GIVEN
CommonSetup: rrk/bbk/{myBase:SOR_021;theirBase:SOR_021;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:ASH_088
WithP1Hand: SOR_169
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
