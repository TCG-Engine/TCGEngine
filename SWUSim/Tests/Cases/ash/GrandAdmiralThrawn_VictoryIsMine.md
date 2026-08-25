# EqualUnits_Restore
#// ASH_004 Grand Admiral Thrawn — Leader Action [Exhaust]: attack with a unit; it gains Restore 2 for this
#// attack if you control the same number of units as the defending player. P1 (1 unit) and P2 (1 unit) are
#// equal, so SOR_095's attack heals 2 from P1's base (5 → 3 damage) as it attacks SOR_046.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004;
  myBaseDamage:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# UnequalUnits_NoRestore
#// ASH_004 Grand Admiral Thrawn — the Restore 2 is gated on equal unit counts. P1 controls 1 unit but P2
#// controls 2, so no Restore is granted and P1's base stays at 5 damage when SOR_095 attacks.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004;
  myBaseDamage:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:5
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_Decline
#// ASH_004 Grand Admiral Thrawn (deployed) — On Attack defeat is a "may"; declining ('-')
#// leaves the enemy unit alive.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1

---

# Deployed_OnAttack_DefeatEnemyUnit
#// ASH_004 Grand Admiral Thrawn (deployed) — On Attack: if you control more units than the
#// defending player, you may defeat a non-leader unit they control. P1 has 2 units (Thrawn +
#// Dark Trooper), P2 has 1 → may defeat the enemy unit.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# Deployed_OnAttack_EqualUnits_NoDefeat
#// ASH_004 Grand Admiral Thrawn (deployed) — the On Attack defeat needs MORE units than the defender, not
#// equal. Thrawn (P1's only unit) vs P2's single SOR_095 is 1-1, so no defeat is offered and SOR_095 lives.
## GIVEN
CommonSetup: gbk/brk/{myLeader:ASH_004:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:1

---

# MoreUnitsThanDefender_NoRestore
#// ASH_004 Grand Admiral Thrawn — the Restore 2 is granted only when P1 controls the SAME number of units as
#// the defending player. Here P1 controls 1 unit (SOR_095) and P2 controls 0, so 1 does not equal 0 and no
#// Restore is granted; P1's base stays at 5 damage when SOR_095 attacks P2's base.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004;
  myBaseDamage:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:BASE
## EXPECT
P1BASEDMG:5
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_FewerUnits_NoDefeat
#// ASH_004 Grand Admiral Thrawn (deployed) — the On Attack defeat needs MORE units than the defender. P1's
#// only unit is Thrawn (1) while P2 controls 2, so 1 is not more than 2: no defeat is offered and both enemy
#// units survive.
## GIVEN
CommonSetup: gbk/brk/{myLeader:ASH_004:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:2

---

# TwinSuns_Deployed_ComparesAndTargetsONLYTheDefendingSeat
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23. ASH_004's deployed On Attack says "THE DEFENDING PLAYER"
#// twice, so nothing here is ever chosen — it is DETERMINED by the board and must not prompt for a seat.
#// TWO defects above two seats, and they point in OPPOSITE directions:
#//   (a) TOO NARROW — the comparison "if you control more units than the defending player" used
#//       OtherPlayer(), a single seat (and seat 1 for any far-seat attacker), so Thrawn compared himself
#//       against a player who need not be in the combat at all.
#//   (b) TOO WIDE — the target pool was 'side' => 'their', which in Twin Suns fans out across EVERY
#//       opponent, so Thrawn could defeat a BYSTANDER's unit. ⚠ This is the sweep's inverse defect: the
#//       pool GREW, so nothing looks broken — no prompt goes missing, nothing fizzles, every existing
#//       test stays green. It surfaces only as a target that should never have been selectable.
#// Fixed by SWUCurrentDefendingSeat() for the comparison and the new 'ofSeat' option on
#// SWUOfferUnitTarget for the pool.
#//
#// P1 (Thrawn + 1 unit = 2) attacks SEAT 3's base. Seat 3 controls 1 unit, so the gate opens; seat 2
#// controls a unit too and seat 4 controls TWO — none of those may be offered, only seat 3's.
#// ⚠ Under (a) alone the gate would compare against seat 2 and could open or close for the wrong reason;
#//   under (b) alone the menu would include seats 2 and 4. The SELECTABLEEXACT pins both at once.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent the defender IS "their" and OtherPlayer() is
#//   right. The seat count IS the test, and the defender must be a FAR seat.
#// Mutation check: revert either half and this reds while all seven 2-player sections above stay green.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004:1:1:1
}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
WithP4SpaceArena: SOR_225:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:1:P3B

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:p3GroundArena-0
