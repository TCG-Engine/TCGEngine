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
