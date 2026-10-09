#// Player report (Discord, 2026-10-09, no gamestate): "Played Fennec Shand, did Ambush attack and then it let
#// me attack with her a second time." Which Fennec was unclear, so every Fennec + Ambush shape is pinned:
#//   SHD_220 Fennec Shand, Loyal Sharpshooter (7, 4/6, printed Ambush + On Attack)
#//   ASH_192 Fennec Shand (3, 3/3, printed Ambush + Saboteur)
#//   HMW_175 Fennec Shand, A Ship For a Life (1, 0/4, Raid 2) given Ambush by HMW_018 The Warrior's front
#//   SHD_016 Fennec Shand leader (plays a unit <=4 and GRANTS Ambush — on top of ASH_192's printed one)
#//   ASH_002 Fennec Shand leader (plays a unit that ENTERS PLAY READY — then its Ambush attack)
#// Each shape is asserted twice: ALTERNATING (no P1OnlyActions, so TURNPLAYER can see an extra action) with
#// NOEXTRAACTION and EXHAUSTED after the Ambush attack; and P1OnlyActions + a second
#// "P1>AttackGroundArena:<i>:BASE" that must NOT land (P2BASEDMG:0).
#// Defender: SEC_214 Skyhopper Canyon Runner 1/4 (SOR_046 Consular Security Force 3/7 vs the 4-power units) —
#// both attacker and defender survive, so the defender stays the only target and the Ambush target auto-resolves.

# SHD220_HardPlay_Ambush_ExhaustedAndTurnPasses
## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: SHD_220
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_220
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:4
TURNPLAYER:2
NOEXTRAACTION

---

# SHD220_HardPlay_Ambush_SecondAttackRefused
## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SHD_220
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0

---

# ASH192_HardPlay_Ambush_ExhaustedAndTurnPasses
## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_192
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
TURNPLAYER:2
NOEXTRAACTION

---

# ASH192_HardPlay_Ambush_SecondAttackRefused
## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# SHD016_Front_PlaysASH192_GrantedPlusPrintedAmbush_OneAttack
#// Granted Ambush (SHD_016) on top of ASH_192's printed Ambush — Ambush is one keyword; ONE trigger, ONE
#// attack. After the single YES nothing else may be pending and the turn passes.
## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_192
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# SHD016_Front_PlaysASH192_SecondAttackRefused
## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# HMW018_Front_PlaysHMW175_GrantedAmbush_ExhaustedAndTurnPasses
#// HMW_175 Fennec (0/4, Raid 2) — power 0 passes The Warrior's "3 or less power" gate. Raid 2 → 2 damage.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: HMW_175
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_175
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# HMW018_Front_PlaysHMW175_SecondAttackRefused
## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: HMW_175
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0

---

# HMW018_Front_PlaysASH192_GrantedPlusPrinted_SecondAttackRefused
## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2BASEDMG:0

---

# ASH002_Front_PlaysASH192_EntersReady_AmbushExhausts_TurnPasses
#// ASH_002 front: "It enters play ready." ASH_192 enters READY; its Ambush attack must EXHAUST it
#// (CR 6.3.1 step 3) — the one shape where a missing exhaust would leave a printed-Ambush unit ready.
#// SOR_095 (index 0) pays the "exhaust a friendly unit" cost; ASH_192 lands at index 1.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:ASH_192
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# ASH002_Front_PlaysASH192_SecondAttackRefused
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_192
P1GROUNDARENAUNIT:1:EXHAUSTED
P2BASEDMG:0

---

# ASH002_Front_PlaysSHD220_SecondAttackRefused
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SHD_220
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_220
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0

---

# ASH002_Front_PlaysASH192_DeclineAmbush_StaysReady
#// CONTROL for the ASH_002 shape: declining Ambush leaves it ready (enters-ready works), so the
#// EXHAUSTED above is caused by the Ambush attack, not by the play.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_192
P1GROUNDARENAUNIT:1:READY

---

# ASH002_Deployed_PlaysASH192_SecondAttackRefused
#// Deployed ASH_002: "Action [1 resource, exhaust a friendly unit]: Play a unit from your hand. It enters play
#// ready." SOR_095 (index 0) pays; Fennec leader unit index 1; ASH_192 lands at index 2.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:2:BASE
## EXPECT
P1GROUNDARENAUNIT:2:CARDID:ASH_192
P1GROUNDARENAUNIT:2:EXHAUSTED
P2BASEDMG:0

---

# HMW018_Deployed_AmbushOnDeploy_SecondAttackRefused
#// The Warrior's deployed side has Ambush and enters READY (a deployed leader) — the original instance of
#// the missing Ambush exhaust. Pinned here alongside the Fennec shapes.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_018
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# TwoTargets_ASH192_HardPlay_P2Passes_SecondAttackRefused
#// Two enemy units → the Ambush target is a real MZCHOOSE (SWUAmbushAttack path, not the 1-target
#// auto-fire). Alternating: the turn passes to P2, P2 passes, P1 then tries Fennec again.
## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: [SEC_214:1:0 SEC_214:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
- P2>Pass
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_192
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:3
P2BASEDMG:0
NOEXTRAACTION

---

# TwoTargets_SHD016_Front_PlaysASH192_P2Passes_SecondAttackRefused
## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: [SEC_214:1:0 SEC_214:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
- P2>Pass
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_192
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:3
P2BASEDMG:0

---

# TwoTargets_HMW018_Front_PlaysHMW175_P2Passes_SecondAttackRefused
## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: HMW_175
WithP2GroundArena: [SEC_214:1:0 SEC_214:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
- P2>Pass
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_175
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:2
P2BASEDMG:0

---

# TwoTargets_ASH002_Front_PlaysASH192_P2Passes_SecondAttackRefused
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_214:1:0 SEC_214:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
- P2>Pass
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_192
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:3
P2BASEDMG:0

---

# CONTROL_HardPlay_TurnStaysWithActorWhileAmbushPending
#// Hard-played ASH_192: while P1 still owes the Ambush YESNO, the action is open and it is still P1's turn.
## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
TURNPLAYER:1

---

# RED_SHD016_Front_TurnPassesWhileAmbushStillPending
#// FOUND 2026-10-09 while investigating the report above (not the reported symptom — no second attack
#// happens). The leader's continuation calls SWUAfterAction unconditionally right after SWUNestedPlay.
#// The played unit's Ambush is a single ENTRY trigger, which FlushEntryTriggerBag queues as two
#// orchestration CUSTOMs (RESOLVE_NEXT_TRIGGER|EffectStack-0 + a bare SWU_TRIGGER_RESUME|1). The
#// "action waits for the actor's picks" guard at the top of SWUAfterAction sees neither (CUSTOMs are not
#// blocking, and _SWUHasQueuedTriggerResolution only matches 'RESOLVE_TRIGGER|'), so the turn passes to
#// P2 BEFORE the Ambush is even offered; the bare resume then tries to close the action a second time
#// after the attack (refused by the gate → NOEXTRAACTION fails in the sections above).
## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1HASDECISION
TURNPLAYER:1

---

# RED_HMW018_Front_TurnPassesWhileAmbushStillPending
## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_018}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: HMW_175
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1HASDECISION
TURNPLAYER:1

---

# RED_ASH002_Front_TurnPassesWhileAmbushStillPending
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
TURNPLAYER:1

---

# CONTROL_SHD016_Front_NoEnemyUnit_NoTrigger_TurnPassesOnce
#// No enemy unit → no Ambush trigger is bagged → the nested play's own close is refused synchronously and
#// the leader's continuation is the one and only close. Must stay green under any fix.
## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_016}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_192
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# RED_ASH002_Front_DeclineAmbush_TurnPassesOnce_NoExtraClose
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_192
P1GROUNDARENAUNIT:1:READY
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# CONTROL_ASH002_Front_DeclineAmbush_ReadyUnitCanAttackOnce
#// Positive control for every "SecondAttackRefused" section: the same AttackGroundArena step DOES land when
#// the unit is genuinely ready (Ambush declined → it entered ready via ASH_002) — so P2BASEDMG:0 above is
#// a real refusal, not a step that silently never runs. ASH_192 is 3/3 → 3 to the base.
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:1:EXHAUSTED
P2BASEDMG:3

---

# CONTROL_ASH002_Front_UnaffordableUnit_FizzleStillPassesTheTurn
#// Guards the FIZZLE branch of any fix to the RED sections above. ASH_002 offers every hand unit without an
#// affordability gate; ASH_192 is off-aspect here (needs Villainy: base Cunning + ASH_002 Aggression/Cunning)
#// → costs 5, and after the action's 1 only 2 resources remain. The play fizzles without reaching any close
#// of its own, so the leader's continuation MUST still close the action. A fix that closes only "when the
#// nested play's close was refused" strands the turn here (measured: TURNPLAYER stays 1).
## GIVEN
CommonSetup: yyk/yyk/{myLeader:ASH_002}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 3
WithP1Hand: ASH_192
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_214:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION
