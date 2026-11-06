# DealsOneToAFriendlyAndOneToAnEnemy
#// IC27_102 Third Sister — Reckless (3, 3/5 Ground, Aggression/Villainy, Force·Imperial·Inquisitor, unique)
#// "On Attack: Deal 1 damage to a friendly ground unit and 1 damage to an enemy ground unit."
#// PREVIEW card (no official rulings). The released analogue is SOR_033 Death Trooper (the same sentence with 2,
#// as a When Played), and its implementation shape is reused: two MANDATORY picks, friendly first, the enemy half
#// queued as a CUSTOM continuation so it still resolves when the friendly pool is empty. Death Trooper's ruling
#// ("if it is the only friendly unit in play, it must be chosen") carries over: Third Sister is a legal friendly
#// ground target herself — no "another".
#//
#// COVERAGE: offer=Offer_Friendly_GroundOnly_IncludesHerself + Offer_Enemy_GroundOnly_IncludesTheDefender ·
#//           decline=N/A (no "you may"; both halves mandatory) ·
#//           no-target=NoEnemyGroundUnit_FriendlyHalfStillResolves + NoOtherFriendly_SheMustTakeIt
#//           (an EMPTY friendly pool is structurally unreachable: Third Sister is herself a friendly ground unit
#//           whenever her On Attack resolves — a mutation that skips the enemy half on an empty friendly pool stays
#//           green for exactly that reason, and the continuation shape is kept for parity with Death Trooper) ·
#//           self=CanDamageHerself · timing=PingDefeatsTheDefender_BeforeCombatDamage ·
#//           reqboundary=RequestBoundary_BetweenThePicks · blank=Blanked_ThirdSister_NoPrompts ·
#//           modes=2P,TeamSuns,TwinSuns ("friendly" = the team; "enemy" = every opponent) —
#//           TeamSuns_TeammateFriendly_BothEnemySeats + TwinSuns_FarSeatEnemyGroundUnit
#//
#// Sister + Marine vs one Dark Trooper: P1 picks the Marine; the lone enemy ground unit auto-resolves. Then
#// combat: 3 to the base.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0   # idx 0 — Third Sister 3/5
WithP1GroundArena: SOR_095:1:0    # idx 1 — Battlefield Marine 3/3
WithP2GroundArena: SEC_080:1:0    # enemy Imperial Dark Trooper 3/3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:3

---

# Offer_Friendly_GroundOnly_IncludesHerself
#// The friendly pool is exactly the friendly GROUND units, Sister included; a friendly space unit is not offered,
#// nor is any enemy unit. Left pending.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Offer_Enemy_GroundOnly_IncludesTheDefender
#// The enemy pool is exactly the enemy GROUND units — the defender included (Sister attacks the first Dark
#// Trooper); an enemy space unit and every friendly unit are excluded. Left pending after the friendly pick.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# CanDamageHerself
#// She may pick herself for the friendly half: 1 damage on Third Sister, 1 on the lone enemy.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# NoOtherFriendly_SheMustTakeIt
#// Death Trooper's ruling, carried over: with no other friendly ground unit (a friendly SPACE unit does not count),
#// the mandatory friendly half lands on Third Sister herself — no prompt, no decline.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:3

---

# NoEnemyGroundUnit_FriendlyHalfStillResolves
#// "and" is not "if you do": with no enemy GROUND unit (only an enemy space unit), the friendly half still
#// resolves and the enemy half no-ops — the space unit is untouched.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:3

---

# PingDefeatsTheDefender_BeforeCombatDamage
#// On Attack resolves BEFORE combat damage. Sister attacks a Death Star Stormtrooper (3/1) and the enemy ping
#// defeats it, so no combat damage is dealt either way: Sister takes nothing back, the base nothing.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0    # idx 0 — Death Star Stormtrooper 3/1 (the defender)
WithP2GroundArena: SEC_080:1:0    # idx 1 — a second enemy so the enemy half is a real choice

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:0

---

# RequestBoundary_BetweenThePicks
#// The first section's two answers with a request boundary between the picks: same result.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:3

---

# Blanked_ThirdSister_NoPrompts
#// A Third Sister that has lost all abilities (SOR_138 marker) deals nothing extra; the attack just lands.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_102:1:0:SOR_138
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3

---

# TeamSuns_TeammateFriendly_BothEnemySeats
#// Team Suns (P1+P3 vs P2+P4). The friendly pool holds teammate P3's ground unit; after it is picked, the enemy
#// pool holds BOTH enemy seats' ground units and not the teammate's. Left pending on the enemy pick.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_102:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SEC_080:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:1
P1SELECTABLEHAS:p2GroundArena-0
P1SELECTABLEHAS:p4GroundArena-0
P1SELECTABLENOT:p3GroundArena-0
P1SELECTABLENOT:myGroundArena-0

---

# TwinSuns_FarSeatEnemyGroundUnit
#// Twin Suns (3 seats): "an enemy ground unit" spans every opponent, so seat 3's unit can take the enemy ping.

## GIVEN
CommonSetup3P: rrk/bbw/bbw
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: IC27_102:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P3GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
