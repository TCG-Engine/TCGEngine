# AttackBonusPerEnemy
#// ASH_234 Masterstroke (Event, cost 2) — Attack with a unit. It gets +1/+0 for this attack for each unit
#// the defending player controls in its arena. P1's SOR_095 (3 power) attacks while P2 has 2 ground units,
#// so it gets +2 → 5; attacking the enemy base deals 5.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_234}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:5

---

# NoEnemyUnits_NoBonus
#// ASH_234 Masterstroke — the +1/+0 is per enemy unit in the attacker's arena. With no enemy ground units,
#// SOR_095 attacks the base for its base 3 (no bonus).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_234}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:3

---

# SpaceBonusPerEnemy
#// ASH_234 Masterstroke — the +1/+0 counts enemy units in the ATTACKER's arena, so it applies to a space
#// attacker too. P1's SOR_237 (2 power) attacks while P2 has 1 space unit, so it gets +1 → 3; attacking the
#// enemy base deals 3.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_234}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:3

---

# BuffLockedWhenCountedEnemyDiesOnAttack
#// ASH_234 Masterstroke — the +1/+0 per enemy in the attacker's arena is locked in for the attack. P1's
#// SEC_171 Punishing One (3 power) gets +1 from the lone enemy space unit SOR_241 (Wing Leader, 2/1) → 4.
#// Punishing One's On Attack deals 1 to Wing Leader, defeating it, yet the buff stays: the base still takes
#// 4 (not 3), and after the attack Punishing One reverts to its base 3.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_234}
WithP1SpaceArena: SEC_171:1:0
WithP2SpaceArena: SOR_241:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2BASEDMG:4
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SEC_171
P1SPACEARENAUNIT:0:POWER:3

---

# TwinSuns_CountsOnlyTheDEFENDINGPlayersUnits
#// ASH_234 reads "+1/+0 for this attack for each unit THE DEFENDING PLAYER controls in its arena."
#// Two defects, both invisible at two seats:
#//   (a) the count used ZoneSearch("their{$arena}"), which above two seats spans EVERY live opponent;
#//   (b) it ran at DECLARATION — before BeginSWUAttack, i.e. before a target exists — so there was no
#//       defending player to scope to even in principle.
#// The fix stamps an ASH_234_ATK marker and counts inside ExecuteSWUAttack, where the defending seat
#// has been published; the same shape TWI_012 Anakin already uses.
#//
#// Seat 4 (the defender) fields ONE ground unit, seats 2 and 3 field THREE each. The correct bonus is
#// +1 (seat 4's single unit); the legacy all-opponents count would be +7. The attacker is SOR_046
#// (3 power), so seat 4's base takes 3+1 = 4 — and would take 10 under the old count. Those two
#// numbers are far apart on purpose: nothing else in the fixture can produce either by accident.

## GIVEN
CommonSetup: yyk/bbw/{theirBase:SOR_021; myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: ASH_234
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]
WithP3GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4Base-0

## EXPECT
SEATCOUNT:4
P4BASEDMG:4
