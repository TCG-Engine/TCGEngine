# BaseDamaged_GiveAdvantage
#// ASH_039 Baylan Skoll (Ground, 6/6, Overwhelm, cost 6) — When Played: if an enemy base was damaged this
#// phase, give an Advantage token to a unit. SOR_095 first attacks P2's base (damaging it), then Baylan is
#// played and gives an Advantage to SOR_095. (No friendly upgrade was defeated, so the second rider is skipped.)
## GIVEN
CommonSetup: ryk/ryk/{myResources:6;handCardIds:ASH_039}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# NeitherCondition_NoTrigger
#// ASH_039 Baylan — both riders are conditional. Played with no enemy base damaged and no friendly upgrade
#// defeated this phase, neither rider fires: no Advantage, no exhaust prompt.
## GIVEN
CommonSetup: ryk/ryk/{myResources:6;handCardIds:ASH_039}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:ASH_039

---

# WhenAttackEnds_BaseDamaged_Advantage
#// ASH_039 Baylan — the riders also fire on "When Attack Ends." A seated Baylan (6/6 Overwhelm) attacks P2's
#// base for 6; the enemy base was damaged this phase, so he gives an Advantage token (to himself here).
## GIVEN
CommonSetup: ryk/ryk
WithP1GroundArena: ASH_039:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# WhenPlayed_BothConditions_AdvantageAndExhaust
#// ASH_039 Baylan — both riders fire when BOTH conditions hold. SOR_164 Wampa attacks the enemy base
#// (enemy base damaged this phase), then SEC_163 Outer Rim Constable defeats the friendly SEC_069 upgrade
#// (a friendly upgrade defeated this phase). Baylan is then played: mandatory Advantage (placed on Baylan)
#// AND an optional exhaust (the enemy SOR_232 AT-ST is exhausted).
## GIVEN
CommonSetup: ryk/ryk/{myResources:15;handCardIds:ASH_039,SEC_163}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SEC_069
WithP2GroundArena: SOR_232:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-2
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:2:CARDID:ASH_039
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenPlayed_UpgradeOnly_ExhaustNoAdvantage
#// ASH_039 Baylan — with only the friendly-upgrade-defeated condition met (no enemy base damage), Baylan
#// offers the optional exhaust but NOT the Advantage. SEC_163 Outer Rim Constable defeats the friendly
#// SEC_069 upgrade; Baylan is played and exhausts the enemy SOR_232 AT-ST. Baylan gets no Advantage.
## GIVEN
CommonSetup: ryk/ryk/{myResources:15;handCardIds:ASH_039,SEC_163}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SEC_069
WithP2GroundArena: SOR_232:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:2:CARDID:ASH_039
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:0

---

# WhenPlayed_BothConditions_DeclineExhaust
#// ASH_039 Baylan — the exhaust rider is optional. With both conditions met, P1 takes the Advantage (on
#// Wampa) but declines the exhaust ('-'): Wampa gains Advantage and the enemy SOR_232 AT-ST stays ready.
## GIVEN
CommonSetup: ryk/ryk/{myResources:15;handCardIds:ASH_039,SEC_163}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SEC_069
WithP2GroundArena: SOR_232:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# WhenAttackEnds_UpgradeOnly_ExhaustNoAdvantage
#// ASH_039 Baylan — the riders also fire on When Attack Ends. SEC_163 first defeats the friendly SEC_069
#// upgrade (upgrade-defeated condition). A seated Baylan (6/6) then attacks SEC_066 Alderaanian Envoys (3/7):
#// Baylan deals 6 (Envoys survive, no Overwhelm to base) and takes 3 back; no base damage. When Attack Ends
#// fires with only the upgrade condition → optional exhaust (Wampa), no Advantage.
## GIVEN
CommonSetup: ryk/ryk/{myResources:15;handCardIds:SEC_163}
WithP1GroundArena: [SOR_164:1:0 ASH_039:1:0]
WithP1GroundArenaUpgrade: 0:SEC_069
WithP2GroundArena: SEC_066:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:1:DAMAGE:3
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0

---

# WhenAttackEnds_Neither_NoPrompt
#// ASH_039 Baylan — When Attack Ends with neither condition met yields no prompts. A seated Baylan attacks
#// SEC_066 Alderaanian Envoys (3/7): Envoys survive Baylan's 6 (no Overwhelm excess, no base damage) and no
#// upgrade was defeated. No Advantage, no exhaust; the friendly SOR_095 stays ready.
## GIVEN
CommonSetup: ryk/ryk
WithP1GroundArena: [ASH_039:1:0 SOR_095:1:0]
WithP2GroundArena: SEC_066:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:6
P2BASEDMG:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:READY

---

# TargetSelection_AdvantageToEnemyLeaderUnit
#// ASH_039 Baylan — when the base-damaged condition is met, the Advantage may go to ANY unit, including an
#// enemy leader unit. SOR_164 Wampa damages the enemy base; Baylan is played and the Advantage is placed on
#// the enemy deployed leader (SOR_011), proving enemy and leader units are valid targets.
## GIVEN
CommonSetup: ryk/ryk/{myResources:6;handCardIds:ASH_039;theirLeader:SOR_011:1:1:1}
WithP1GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# UpgradeReturnedToHand_NoExhaust
#// ASH_039 Baylan — a friendly upgrade RETURNED to hand (not defeated) does not satisfy the upgrade-defeated
#// condition. SHD_209 Criminal Muscle returns the friendly non-unique SOR_120 upgrade to P1's hand; with
#// no base damage either, Baylan resolves with no prompts and gains no Advantage.
## GIVEN
CommonSetup: ryk/ryk/{myResources:15;handCardIds:ASH_039,SHD_209}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:1
- P1>AnswerDecision:myTempZone-0
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:CARDID:ASH_039
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:0

---

# ShieldConsumedInCombat_ExhaustRiderFires
#// ASH_039 Baylan — a Shield token consumed absorbing combat damage counts as "a friendly upgrade defeated
#// this phase", arming Baylan's second rider. P2's SOR_046 attacks P1's shielded SOR_095 (shield pops); then
#// P1 plays Baylan (no enemy base damaged → no Advantage) and the exhaust rider fires on the ready enemy SOR_232.
## GIVEN
CommonSetup: ryk/ryk/{myResources:6;handCardIds:ASH_039}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: [SOR_046:1:0 SOR_232:1:0]
WithActivePlayer: 2
## WHEN
- P2>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:EXHAUSTED
