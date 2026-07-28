# NextLowPowerEntersReady
#// ASH_248 Neel (Ground, 1/4, cost 1) — When Played: the next unit you play this phase with 1 or less
#// power enters play ready. P1 plays Neel (arming the effect), then plays ASH_073 (0 power), which enters
#// play ready.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:ASH_248,ASH_073}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_073
P1GROUNDARENAUNIT:1:READY

---

# NeelHimselfEntersExhausted
#// ASH_248 Neel — the effect arms AFTER Neel enters, so it never readies Neel himself (he is 1 power). Neel
#// enters exhausted like any played unit.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:ASH_248}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_248
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# TwoPowerDoesNotEnterReady
#// ASH_248 Neel — the effect only readies a unit with 1 or LESS printed power. SOR_063 (Cloud City Wing
#// Guard, printed 2 power) played after Neel does NOT enter ready.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:ASH_248,SOR_063}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# NonQualifyingPlayKeepsFlag_ThenLowPowerReady
#// ASH_248 Neel — a non-qualifying play (2 power) does NOT consume the armed effect. After Neel, P1 plays
#// SOR_063 (2 power → exhausted, flag intact) then SOR_108 (Vanguard Infantry, 1 power) which enters ready.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_248,SOR_063,SOR_108}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:CARDID:SOR_108
P1GROUNDARENAUNIT:2:READY

---

# ConsumedByFirstQualifying
#// ASH_248 Neel — the effect is consumed by the FIRST qualifying unit only. After Neel, the first SOR_108
#// (1 power) enters ready; a second SOR_108 played the same phase enters exhausted.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:ASH_248,SOR_108,SOR_108}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_108
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:CARDID:SOR_108
P1GROUNDARENAUNIT:2:EXHAUSTED

---

# OnAttackArmsReadyFlag
#// ASH_248 Neel — On Attack (not just When Played) also arms the effect. A seated Neel attacks the enemy
#// base; the next 1-power unit P1 plays this phase (SOR_108) then enters ready.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:SOR_108}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_108
P1GROUNDARENAUNIT:1:READY

---

# WhenPlayedFlagExpiresNextPhase
#// ASH_248 Neel — the "next unit you play THIS PHASE" effect is a phase-long effect. P1 plays Neel (arming
#// it), then passes into the next action phase without spending the flag. A 1-power unit (SOR_108) played in
#// the new phase enters exhausted because the effect already expired.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:ASH_248,SOR_108}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_108
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# OnAttackFlagExpiresNextPhase
#// ASH_248 Neel — the On Attack arming is likewise phase-long. A seated Neel attacks, arming the effect;
#// P1 crosses into the next action phase, then plays a 1-power unit (SOR_108) which enters exhausted.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:SOR_108}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_108
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# PrintedPowerBuffedStillQualifies
#// ASH_248 Neel — qualification uses PRINTED power, not modified power. With Outcast (ASH_041) in space
#// giving each entering friendly unit +1/+0 this phase, Warrior Drone (TWI_057, printed power 1, modified to
#// 2 on entry) still qualifies and enters ready.
## GIVEN
CommonSetup: bbw/bbk/{myResources:8;handCardIds:TWI_057}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
WithP1SpaceArena: ASH_041:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_057
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:READY

---

# PrintedPowerDebuffedDoesNotQualify
#// ASH_248 Neel — a 3-printed-power unit debuffed to 1 does NOT qualify. With Supreme Leader Snoke (SHD_037,
#// enemy non-leader units get -2/-2) on P2's board, Battlefield Marine (SOR_095, printed 3, modified to 1)
#// enters exhausted because Neel checks printed power (3 > 1).
## GIVEN
CommonSetup: bbw/bbk/{myResources:10;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
WithP2GroundArena: SHD_037:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# CreatedTokensDoNotConsumeMatcher
#// ASH_248 Neel — tokens are CREATED, not PLAYED, so token creation neither receives the ready nor consumes
#// the armed effect. Kraken (TWI_084) creates two Battle Droid tokens (printed power 1) on entry; the matcher
#// survives and the next played 1-power unit (TWI_057) enters ready.
## GIVEN
CommonSetup: bbk/bbk/{myResources:14;handCardIds:TWI_084,TWI_057}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:4:CARDID:TWI_057
P1GROUNDARENAUNIT:4:READY

---

# LeaderDeployDoesNotConsumeMatcher
#// ASH_248 Neel — leaders are DEPLOYED, not played, so deploying a 1-power leader (C-3PO SEC_015, printed
#// power 1) must not consume the armed effect. After the deploy, the next played 1-power unit (TWI_057)
#// still enters ready.
## GIVEN
CommonSetup: byk/bbk/{myResources:14;handCardIds:TWI_057;myLeader:SEC_015}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>DeployLeader:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:2:CARDID:TWI_057
P1GROUNDARENAUNIT:2:READY

---

# SelfReadyViaOwnAbilityStillConsumesMatcher
#// ASH_248 Neel — the matcher is consumed by the first qualifying unit even if that unit would enter ready
#// on its own. Salacious Crumb (LAW_210, printed power 0) enters ready via its own ability while Jabba
#// (SOR_181) is controlled; it still spends Neel's effect, so a later 1-power unit (TWI_057) enters exhausted.
## GIVEN
CommonSetup: yyk/bbk/{myResources:14;handCardIds:LAW_210,TWI_057}
P1OnlyActions: true
WithP1GroundArena: [ASH_248:1:0 SOR_181:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:2:CARDID:LAW_210
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:3:CARDID:TWI_057
P1GROUNDARENAUNIT:3:EXHAUSTED

---

# BounceAndReplayReadiesNeel
#// ASH_248 Neel — the armed effect is a phase-long player effect that persists after Neel leaves play.
#// P1 plays Neel (arming it; Neel himself enters exhausted). P2 returns Neel to P1's hand with Waylay
#// (SOR_222). When P1 replays Neel this same phase, his printed power 1 qualifies, so the still-armed
#// effect readies him on re-entry.
## GIVEN
CommonSetup: bbw/yyk/{myResources:6;theirResources:6;handCardIds:ASH_248;theirhandCardIds:SOR_222}
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_248
P1GROUNDARENAUNIT:0:READY

---

# ReadyAppliesAtEntryAlongsideAmbush
#// ASH_248 Neel — the ready is applied as the unit ENTERS play, not as a separate ability offered in the
#// When Played window, so it coexists with (and is independent of) Ambush. A seated Neel attacks, arming the
#// matcher. P1 plays Mysterious Hermit (LOF_208, printed power 1, Ambush) with an enemy unit present so its
#// Ambush is live; P1 declines the Ambush attack. Because the ready was applied at entry (not consumed by the
#// Ambush timing), the Hermit is READY afterward.
## GIVEN
CommonSetup: ybw/bbk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LOF_208
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_208
P1GROUNDARENAUNIT:1:READY

---

# RescueFromCaptureDoesNotConsumeMatcher
#// ASH_248 Neel — a unit that re-enters play by being RESCUED from capture is not "played", so it enters
#// exhausted (per rescue rules) and does NOT consume the armed matcher. A seated Neel attacks (arming the
#// matcher). P2's Cad Bane (TWI_187) is holding a captured friendly Ant Droid (ASH_116). P1 plays Fell the
#// Dragon (SHD_078) to defeat Cad Bane (power 7), rescuing the Ant Droid to P1's ground exhausted. The
#// matcher survives, so the next played 1-power unit (Warrior Drone, TWI_057) enters ready.
## GIVEN
CommonSetup: bbw/yyk/{myResources:14}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
WithP2GroundArena: TWI_187:1:0
WithP2GroundArenaCaptive: 0:ASH_116
WithP1Hand: [SHD_078 TWI_057]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_116
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:CARDID:TWI_057
P1GROUNDARENAUNIT:2:READY

---

# OwnConstantPowerBuffPrintedQualifies
#// ASH_248 Neel — qualification uses PRINTED power even when the played unit has its own constant power-buff
#// ability. A seated Neel attacks (arming the matcher). P1 plays 97th Legion (SOR_118, printed power 0, "gets
#// +1/+1 for each resource you control"). Its printed power 0 qualifies, so it enters ready; with 10
#// resources controlled its modified power is 10.
## GIVEN
CommonSetup: gbw/bbk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: ASH_248:1:0
WithP1Hand: SOR_118
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_118
P1GROUNDARENAUNIT:1:POWER:10
P1GROUNDARENAUNIT:1:READY

---

# FriendlyConstantBuffPrintedQualifies
#// ASH_248 Neel — qualification uses PRINTED power even when a friendly unit's constant ability buffs the
#// played unit. A seated Neel attacks (arming the matcher). P1's Wampa (SOR_164) wears Fulcrum (LAW_150),
#// which grants "each other friendly Rebel unit gets +2/+2." P1 plays Alliance Dispatcher (SOR_093, printed
#// power 1, Rebel). Its printed power 1 qualifies, so it enters ready; the Fulcrum buff raises its modified
#// power to 3.
## GIVEN
CommonSetup: gbw/bbk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [ASH_248:1:0 SOR_164:1:0]
WithP1GroundArenaUpgrade: 1:LAW_150
WithP1Hand: SOR_093
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:2:CARDID:SOR_093
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:READY

---

# ReadiesOnlyOneWhenBothTriggersArmFromAmbush
#// ASH_248 Neel — his ability is a single "When Played/On Attack" effect. Playing Neel with Ambush fires both
#// arms this phase: the When Played arm on entry and the On Attack arm from the Ambush attack. Even so, only
#// ONE unit is readied — the two arms are both satisfied by the first qualifying unit. The Energy Conversion
#// Lab base (SOR_022) Epic Action plays Neel and gives him Ambush; P1 resolves the ready arm, then Ambush
#// attacks the enemy Battlefield Marine. The first 1-power unit (Ant Droid, ASH_116) enters ready and
#// consumes both arms, so the second 1-power unit (Moisture Farmer, SHD_055) enters exhausted.
## GIVEN
CommonSetup: gbw/bbk/{myResources:6;myBase:SOR_022}
P1OnlyActions: true
WithP1Hand: [ASH_248 ASH_116 SHD_055]
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_248
P1GROUNDARENAUNIT:1:CARDID:ASH_116
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:CARDID:SHD_055
P1GROUNDARENAUNIT:2:EXHAUSTED
