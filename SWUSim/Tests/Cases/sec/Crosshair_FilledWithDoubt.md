# OnAttack_PingFriendlyFor2Base
#// SEC_162 Crosshair (Ground, 2/3) — On Attack: may deal 1 to another friendly unit; if you do, deal 2
#//   to the defending player's base. Attacks P2 base (2 combat) → ping SOR_095 → +2 base = 4 total.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_162:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:DAMAGE:1
P1NODECISION

---

# OnAttack_PassAbility_NoPingOnlyCombat
#// SEC_162 Crosshair (2/3) — the On Attack ping is optional. P1 declines it, so no friendly damage and
#// only the 2 combat damage lands on P2's base (no extra +2).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_162:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION

---

# TwinSuns_RiderHitsTheACTUALDefendingPlayersBase
#// "On Attack: You may deal 1 damage to another friendly unit. If you do, deal 2 damage to THE
#// DEFENDING PLAYER's base." The rider ran in a deferred continuation (SEC_162#0) that called
#// SWUDealDamageToBase(2, OtherPlayer($player)) — so the 2 landed on seat 2 no matter who was attacked.
#//
#// Crosshair is 2 power, so seat 4's base must read 2 combat + 2 rider = 4, and seat 2's must stay 0.
#// Asserting both sides: the legacy build gives seat 4 exactly 2 and seat 2 exactly 2.

## GIVEN
CommonSetup: rrk/bbw/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [SEC_162:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:P4B
- P1>AnswerDecision:myGroundArena-1

## EXPECT
SEATCOUNT:4
P4BASEDMG:4
P2BASEDMG:0
P1GROUNDARENAUNIT:1:DAMAGE:1
