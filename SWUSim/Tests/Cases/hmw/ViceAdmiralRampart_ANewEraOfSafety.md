# EffectDefeat_MayDefeatRampartInstead_SavesTheBaseUpgrade
#// HMW_060 Vice Admiral Rampart (1/5) — "If an upgrade on your base would be defeated, you may defeat this
#// unit instead." P2's 7-power AT-ST attacks P1's base; HMW_081 Alliance Shield Generator prevents the 5+
#// damage and would defeat itself (an EFFECT defeat → replaceable) + draw. At action end P1 is offered the
#// replacement; taking it defeats Rampart and the generator survives. The draw still happens either way.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 2
WithP1BaseUpgrade: HMW_081
WithP1GroundArena: HMW_060:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: HMW_121:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# EffectDefeat_Decline_UpgradeIsDefeatedRampartSurvives
#// Declining the replacement: the base upgrade (Alliance Shield Generator) is defeated for real and Rampart
#// stays. The prevention + draw already happened regardless.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 2
WithP1BaseUpgrade: HMW_081
WithP1GroundArena: HMW_060:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: HMW_121:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1BASE:UPGRADECOUNT:0
P1BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:HMW_060
P1HANDCOUNT:1

---

# CostDefeat_IsReplaceable_ChamberSavedRampartDefeated
#// HMW_095 Carbonite Chamber's "Action [defeat this upgrade]" is a COST — but per the SWU CR a replacement
#// effect CAN replace a cost (it still counts as paid), so Rampart MAY be defeated instead. The Chamber's own
#// effect auto-targets Rampart (the only non-Vehicle unit); at action end the replacement is offered, and
#// taking it defeats Rampart and SAVES the Chamber (it stays on the base).

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1GroundArena: HMW_060:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:1
P1GROUNDARENACOUNT:0

---

# CostDefeat_Decline_ChamberDefeatedRampartSurvives
#// Declining the replacement: the Chamber defeats itself for real and Rampart stays.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1GroundArena: HMW_060:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:-

## EXPECT
P1BASE:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:HMW_060

---

# TrapFieldSelfSacrifice_Replaceable_DamageStillDealt
#// HMW_171 Trap Field's "you may defeat this upgrade. If you do, deal 3" is also replaceable. Per the CR,
#// when a replacement replaces the text before "If you do", the player is still considered to have resolved
#// it — so the 3 is dealt regardless, and Rampart (chosen at action end) saves the Trap Field. SOR_046 (3/7)
#// takes 3 and survives; the Trap Field stays; Rampart is defeated.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_171
WithP1GroundArena: HMW_060:1:0
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# EffectDefeat_Decline_UpgradeIsDefeatedRampartSurvives_DeclinedWithNO
#// Same decline as the section above, but answering **NO** — the token the real client
#// submits for a YESNO's No button. The '-' variant is the MZMAYCHOOSE pass token and can
#// never reach this handler in a real game, so it could not catch SWUDecisionDeclined()
#// omitting 'NO' (which made a real decline resolve the effect anyway).
#// Declining the replacement: the base upgrade (Alliance Shield Generator) is defeated for real and Rampart
#// stays. The prevention + draw already happened regardless.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 2
WithP1BaseUpgrade: HMW_081
WithP1GroundArena: HMW_060:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: HMW_121:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1BASE:UPGRADECOUNT:0
P1BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:HMW_060
P1HANDCOUNT:1

---

# CostDefeat_Decline_ChamberDefeatedRampartSurvives_DeclinedWithNO
#// Same decline as the section above, but answering **NO** — the token the real client
#// submits for a YESNO's No button. The '-' variant is the MZMAYCHOOSE pass token and can
#// never reach this handler in a real game, so it could not catch SWUDecisionDeclined()
#// omitting 'NO' (which made a real decline resolve the effect anyway).
#// Declining the replacement: the Chamber defeats itself for real and Rampart stays.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1GroundArena: HMW_060:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:NO

## EXPECT
P1BASE:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:HMW_060
