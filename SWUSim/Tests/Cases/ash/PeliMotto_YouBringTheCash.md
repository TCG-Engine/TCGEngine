# FirstNonUnitNoPenalty
#// ASH_212 Peli Motto (Ground, 1/1, cost 1) — "Ignore the aspect penalties of the first non-unit card you
#// play each phase." With Peli in play under a Vigilance base/leader, P1 plays ASH_136 (a Command event,
#// cost 2) — normally +2 off-aspect = 4 — for just 2 resources (the penalty is waived), buffing SOR_095 to 6.
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_136}
WithP1GroundArena: ASH_212:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
P1RESAVAILABLE:0

---

# SecondNonUnit_PaysPenalty
#// ASH_212 Peli Motto — only the FIRST non-unit card each phase ignores aspect penalties. P1 plays two
#// off-aspect Command events (ASH_136) under a Vigilance base: the first is waived (2), the second pays the
#// +2 penalty (4). Starting from 6 resources, both plays leave 0 — proving the second was NOT waived.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:ASH_136,ASH_136}
WithP1GroundArena: ASH_212:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1RESAVAILABLE:0

---

# UnitPlayedDoesNotConsumeDiscount
#// ASH_212 Peli Motto — only NON-UNIT cards count toward the "first non-unit each phase" discount. P1 first
#// plays a unit SOR_046 (on-aspect, cost 4), which does NOT consume the discount; then the off-aspect Command
#// event ASH_136 is still the first non-unit and is waived (2), buffing SOR_095 to 6. Starting from 6
#// resources, both plays leave exactly 0 — proving the unit did not use up the discount.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:SOR_046,ASH_136}
WithP1GroundArena: [ASH_212:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
P1RESAVAILABLE:0

---

# OnAspectNonUnitConsumesDiscount
#// ASH_212 Peli Motto — the FIRST non-unit card consumes the discount even when it is on-aspect (and thus had
#// no penalty to waive). P1 first plays on-aspect SHD_252 (Heroism, cost 1) which uses up the discount; the
#// following off-aspect Command event ASH_136 then pays the full +2 penalty (4). From 5 resources both plays
#// leave 0 — proving the on-aspect card consumed the once-per-phase discount.
## GIVEN
CommonSetup: bbw/bbk/{myResources:5;handCardIds:SHD_252,ASH_136}
WithP1GroundArena: [ASH_212:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
P1RESAVAILABLE:0


---

# UpgradeAsFirstNonUnit_WaivesPenalty
#// ASH_212 Peli Motto — the first non-unit each phase includes an UPGRADE. Under a Vigilance identity, playing
#// the off-aspect Bokken Saber (ASH_180, Aggression, cost 1) as the first non-unit waives the +2 penalty →
#// costs 1 (2 of 3 resources left), not 3. (The upgrade charges at attach time, so the waiver must survive to there.)
## GIVEN
CommonSetup: bbw/bbk/{myResources:3;handCardIds:ASH_180}
WithP1GroundArena: ASH_212:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# NonUnitBeforePeli_SecondPaysPenalty
#// ASH_212 Peli — the waiver is for the phase's genuinely-FIRST non-unit, tracked regardless of when Peli
#// entered. Under Vigilance: play a Command event (ASH_136, no Peli yet → pays +2 = 4), then play Peli (cost
#// 3), then a second Command event — Peli is now in play but a non-unit was ALREADY played this phase, so it
#// still pays +2 = 4 (not waived). 4 + 3 + 4 = 11 resources spent.
## GIVEN
CommonSetup: bbw/bbk/{myResources:11;handCardIds:ASH_136,ASH_212,ASH_136}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1RESAVAILABLE:0

---

# ControllerNotOwner_GetsDiscount
#// ASH_212 Peli Motto — the waiver applies to the CONTROLLER, not the owner. Peli is owned by P1 but
#// controlled by P2 (the end state after a Change of Heart / control-take). P1 (owner, does not control Peli)
#// plays an off-aspect Aggression upgrade ASH_180 (Bokken Saber, cost 1) and pays the full +2 penalty → 3
#// (5 → 2 left); P2 (controller) plays the same upgrade and the penalty is waived → 1 (5 → 4 left).
## GIVEN
CommonSetup: bbw/bbw/{}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2Resources: 5
WithP1GroundArena: SOR_095:1:0
WithP1Hand: ASH_180
WithP2GroundArenaControlled: ASH_212:1
WithP2GroundArena: SOR_095:1:0
WithP2Hand: ASH_180
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
## EXPECT
P1RESAVAILABLE:2
P2RESAVAILABLE:4
