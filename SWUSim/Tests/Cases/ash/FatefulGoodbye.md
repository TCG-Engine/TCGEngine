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
