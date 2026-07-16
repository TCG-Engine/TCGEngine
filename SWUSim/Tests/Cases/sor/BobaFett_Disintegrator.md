# OnAttack_EnteredThisRound_NoDeal3
#// SOR_179 Boba Fett — condition gate: the exhausted defender must NOT have entered play this round.
#// P2 plays SOR_046 this round (enters exhausted, flagged SWU_PLAYED_UNIT). Boba attacks it → exhausted
#// but entered-this-round → no deal 3; only combat damage (3). (SOR_046 survives at 7 HP.)

## GIVEN
CommonSetup: yyk/bbw/{theirResources:4;theirHandCardIds:SOR_046}
WithActivePlayer: 1
WithP1GroundArena: SOR_179:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OnAttack_ExhaustedNotEntered_Deal3
#// SOR_179 Boba Fett — On Attack: if attacking an EXHAUSTED unit that didn't enter play this round,
#// deal 3 to the defender. Boba (3/5) attacks a seeded exhausted SOR_046 (3/7, not played this round):
#// OnAttack deals 3, then combat adds 3 → 6 total. SOR_046 survives (7 HP); Boba takes 3 counter.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OnAttack_ReadyDefender_NoDeal3
#// SOR_179 Boba Fett — condition gate: the defender must be EXHAUSTED. Attacking a READY SOR_046 →
#// OnAttack does NOT deal 3; only combat damage (3) lands.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
