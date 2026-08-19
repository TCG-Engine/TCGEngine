# NothingLeft_NoDistribute
#// ASH_211 Fateful Goodbye — the distribute is gated on something having left play this phase. With no unit
#// (or leader) having left play, Fateful Goodbye does nothing (SEC_135 gains no Advantage tokens).
## GIVEN
CommonSetup: yyw/yyk/{myResources:2;handCardIds:ASH_211}
WithP1GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# UnitLeftDistributeThree
#// ASH_211 Fateful Goodbye (Event, cost 2) — If a friendly unit left play this phase, distribute 3 Advantage
#// tokens among friendly units. SOR_095 dies attacking SOR_038 (sets the flag), then Fateful Goodbye piles 3
#// Advantage onto SEC_135.
## GIVEN
CommonSetup: yyw/yyk/{myResources:2;handCardIds:ASH_211}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SOR_038:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:3
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# EnemyUnitLeft_NoDistribute
#// ASH_211 Fateful Goodbye — the gate is a FRIENDLY unit leaving play. When only an enemy unit dies (SOR_128
#// killed by SOR_046), no friendly left play this phase, so Fateful Goodbye distributes nothing.
## GIVEN
CommonSetup: yyw/yyk/{myResources:2;handCardIds:ASH_211}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P2GROUNDARENACOUNT:0

---

# UnitLeftByBounce_DistributeThree
#// ASH_211 Fateful Goodbye — "left play" also covers a friendly unit RETURNED to hand. P1 plays SOR_222
#// (Waylay) to bounce its own SOR_095, then Fateful Goodbye piles 3 Advantage onto the remaining SEC_135.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_222,ASH_211}
WithP1GroundArena: SEC_135:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:3
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_135
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# FriendlyLeaderUnitLeft_DistributeFive
#// ASH_211 Fateful Goodbye — if a friendly LEADER unit left play this phase, it distributes 5 Advantage instead
#// of 3. P1's deployed leader (ASH_017, seated at 5 damage) attacks SEC_080 and dies to the counter (returns to
#// base = leaves play as a leader unit); Fateful Goodbye then distributes 5 onto SEC_135.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:ASH_211;myLeader:ASH_017:1:1:0:5}
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:5
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_135
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:5

---

# EnemyLeaderUnitLeft_NoDistribute
#// ASH_211 Fateful Goodbye — an ENEMY leader unit leaving play does not count. P1's tanky SOR_046 (3/7) kills
#// the opponent's deployed leader (ASH_017, seated at 4 damage) and survives; no friendly unit left, so Fateful
#// Goodbye does nothing (SEC_135 gains no Advantage).
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:ASH_211;theirLeader:ASH_017:1:1:0:4}
WithP1GroundArena: SEC_135:1:0
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SEC_135
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# NGORControlledUnitLeft_DistributeThree
#// ASH_211 Fateful Goodbye — a unit that P1 took control of (JTL_043 No Glory, Only Results) and then defeated
#// counts as "a friendly unit left play this phase" (it was friendly — controlled by P1 — when it left). P1
#// NGORs the enemy SOR_095 (takes control + defeats), then Fateful Goodbye distributes 3 Advantage to SEC_135.
## GIVEN
CommonSetup: bbk/ryk/{myResources:12;handCardIds:JTL_043,ASH_211}
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:3
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# UnitLeftByCapture_DistributeThree
#// ASH_211 Fateful Goodbye — "left play" also covers a friendly unit that an opponent CAPTURES. P1 passes,
#// then P2 plays SEC_195 Arrest to capture P1's SOR_095 (it leaves play into P2's base captives). On P1's
#// next action, a friendly unit having left play this phase lets Fateful Goodbye pile 3 Advantage onto the
#// remaining SEC_135.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:ASH_211}
WithP1GroundArena: SEC_135:1:0
WithP1GroundArena: SOR_095:1:0
WithP2Resources: 5
WithP2Hand: SEC_195
## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:3
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_135
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# BothUnitAndLeaderUnitLeft_DistributeFive
#// ASH_211 Fateful Goodbye — when BOTH a friendly non-leader unit AND a friendly leader unit leave play in
#// the same phase, the amount is still 5 (the leader-unit clause; it does not stack to 3+5=8). P1's deployed
#// leader (ASH_017, seated at 5 damage) attacks SEC_080 and dies to the counter (leader unit leaves), then
#// P1's SOR_095 attacks the tanky SOR_046 and is defeated (non-leader unit leaves). Fateful Goodbye then
#// distributes 5 onto SEC_135.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:ASH_211;myLeader:ASH_017:1:1:0:5}
WithP1GroundArena: SEC_135:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:1
- P1>AttackGroundArena:1:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:5
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_135
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:5

---

# FriendlyLeaderUPGRADELeft_NoDistributeAtAll
#// ASH_211 — a leader deployed as a PILOT is an UPGRADE, not a leader unit. Defeating that upgrade while
#// its host SURVIVES means no unit of any kind left play, so Fateful Goodbye distributes NOTHING —
#// neither the 5 (no leader UNIT left) nor the 3 (no unit left at all).
#// P1's leader is deployed as a pilot onto SEC_135; P2 spends SOR_251 Confiscate to defeat that upgrade;
#// P1 then plays Fateful Goodbye and no Advantage appears anywhere.
#// This is the distinction most likely to be implemented wrong: an implementation that keys the 5 off
#// "a leader left play" rather than "a leader UNIT left play" fires here, and so does one that treats the
#// upgrade's departure as a unit leaving play.
#// ⚠ NO `P1OnlyActions` here — it claims initiative for P1 and fights `WithActivePlayer: 2`, so P2's
#// Confiscate silently never plays and the section passes for the wrong reason (upgrade still attached).

## GIVEN
CommonSetup: yyw/grw/{myResources:9; handCardIds:ASH_211; theirResources:4; theirHandCardIds:SOR_251; myLeader:JTL_001; myLeaderDeployedPilot:1}
WithP1GroundArena: SEC_135:1:0
WithActivePlayer: 2

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1NODECISION
