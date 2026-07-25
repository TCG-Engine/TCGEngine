# CaptureFromDiscard
#// ASH_128 Bothan-5 (Space, 4/5, cost 5) — When another friendly non-Vehicle unit is defeated: you may have
#// this unit capture that unit from your discard pile (once each round). SOR_095 (non-Vehicle) dies attacking
#// SOR_046; P1 captures it from the discard onto Bothan-5, so it leaves the discard (and isn't in the arena).
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_128:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0

---

# Decline_StaysInDiscard
#// ASH_128 Bothan-5 — declining the optional capture leaves the defeated unit in the discard pile. SOR_095
#// dies attacking SOR_046 and P1 declines, so SOR_095 stays in the discard.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_128:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# EnemyDefeated_NoCapture
#// ASH_128 Bothan-5 — the trigger is a FRIENDLY unit's defeat. When an enemy unit dies (SOR_128 killed by
#// SOR_046), Bothan-5 is not offered a capture.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_128:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:0

---

# Vehicle_NoCapture
#// ASH_128 Bothan-5 — its capture trigger is a friendly NON-Vehicle defeat. When the friendly SOR_232 AT-ST
#// (Vehicle) is defeated by SHD_079 Rival's Fall, Bothan-5 offers nothing and the AT-ST stays in the discard.
## GIVEN
CommonSetup: ggk/bgk/{theirhandCardIds:SHD_079;theirResources:6}
WithP1GroundArena: SOR_232:1:0
WithP1SpaceArena: ASH_128:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>PlayHand:0
- P2>ChooseTheirGroundUnit:0
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# Bounced_NoCapture
#// ASH_128 Bothan-5 — a unit RETURNED to hand isn't defeated, so no capture. SOR_222 Waylay bounces the
#// friendly SOR_164 Wampa to its owner's hand; Bothan-5 offers nothing and P1's discard stays empty.
## GIVEN
CommonSetup: ggk/yyk/{theirhandCardIds:SOR_222;theirResources:3}
SkipPreGame: true
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: ASH_128:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>PlayHand:0
- P2>ChooseTheirGroundUnit:0
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0
P1HANDCOUNT:1

---

# OncePerRound_SecondDefeatNoCapture
#// ASH_128 Bothan-5 — the capture is limited to once each round. Two friendly Battlefield Marines (SOR_095,
#// non-Vehicle) each trade into SOR_046 Consular Security Force (3/7) and die the same round: the first is
#// captured onto Bothan-5, but the second gets no capture offer and stays in P1's discard.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1SpaceArena: ASH_128:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# NGORControlledDefeat_CaptureFizzles_UnitToOwnerDiscard
#// ASH_128 Bothan-5 — a unit P1 took control of (JTL_043) and defeated is friendly-controlled at defeat, so
#// Bothan's "may capture" trigger IS offered — but the unit goes to its OWNER's (P2's) discard, and the capture
#// only draws from YOUR discard, so it fizzles: the Wampa stays in P2's discard and Bothan gains no captive.
## GIVEN
CommonSetup: bbk/ryk/{myResources:12;handCardIds:JTL_043}
WithP1GroundArena: ASH_128:1:0
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
