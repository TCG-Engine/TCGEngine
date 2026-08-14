# Action_ExhaustedUnitAttacksNotBase
#// SOR_110 Frontline Shuttle (1/3, Space) — Action [defeat this unit]: Attack with a unit,
#// even if it's exhausted. It can't attack bases for this attack.
#// Validates all three novel pieces at once:
#//   • Cost is DEFEAT (not Exhaust) → the Shuttle itself may be EXHAUSTED and still act,
#//     and it is removed from play as the cost (SpaceArena count → 0).
#//   • The chosen attacker (Battlefield Marine) is EXHAUSTED yet attacks anyway.
#//   • Bases can't be targeted: although the enemy has a base, the attack auto-resolves onto
#//     the lone enemy unit (Doctor Pershing 0/5 → takes 3), and the base takes 0.
#// Pershing has 0 power, so the Marine takes no return damage and survives (still exhausted).
#// COVERAGE: offer=AttackerOffer_ExhaustedAndReadyBothEligible + TargetOffer_EnemyUnitsOnly_BaseExcluded
#//           (both pending SELECTABLEEXACT) · reqboundary=TargetOffer_EnemyUnitsOnly_BaseExcluded (the
#//           target answer arrives on a later request than the activation) · decline=N/A (no "you may":
#//           once the defeat cost is paid the attack is mandatory; the zero-legal-target case instead
#//           refuses to activate — Action_NoEnemyUnit_NoOp) · boundary pair=Action_NoEnemyUnit_NoOp
#//           (zero legal targets → full no-op) + Action_ExhaustedUnitAttacksNotBase (one target →
#//           auto-resolve) · control=N/A (one-shot action; no lingering per-unit state)

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_110:0:0     # Frontline Shuttle — EXHAUSTED, index 0 (defeat-cost ignores ready)
WithP1GroundArena: SOR_095:0:0    # Battlefield Marine — EXHAUSTED attacker, index 0
WithP2GroundArena: SHD_028:1:0    # enemy Doctor Pershing (0/5) — the only non-base target

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# Action_NoEnemyUnit_NoOp
#// SOR_110 Frontline Shuttle — because the granted attack "can't attack bases," the action
#// has no legal effect when the enemy has no units to attack (only a base). It is then a full
#// no-op: the Shuttle is NOT defeated (cost unpaid), the friendly unit is unchanged, and no
#// decision is pending. Guards the availability gate (a base is never a valid target here).

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_110:1:0     # Frontline Shuttle (ready) — index 0
WithP1GroundArena: SOR_095:0:0    # Battlefield Marine (exhausted) — a would-be attacker
#// P2 has no arena units — only a base, which can't be attacked by this action.

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
P1NODECISION

---

# AttackerOffer_ExhaustedAndReadyBothEligible
#// Intended: "Attack with a unit, even if it's exhausted" — the attacker pool holds every friendly
#// unit still in play, ready or exhausted alike. The Shuttle itself is defeated as the COST, so by
#// the time the pool builds it is gone (space arena already empty while the pick is pending).
#// Marine (exhausted) and Consular Security Force (ready) are both offered.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_110:0:0     # Frontline Shuttle — exhausted; defeat-cost ignores ready state
WithP1GroundArena: SOR_095:0:0    # Battlefield Marine — EXHAUSTED, still eligible
WithP1GroundArena: SOR_046:1:0    # Consular Security Force — ready, also eligible
WithP2GroundArena: SHD_028:1:0    # an enemy unit so the action has a legal target

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1SPACEARENACOUNT:0

---

# TargetOffer_EnemyUnitsOnly_BaseExcluded
#// Intended: the granted attack "can't attack bases" — with TWO enemy ground units in play the
#// target pick stays interactive, and the pool is exactly the two units (the enemy base never
#// appears even though a normal attack could target it). The lone friendly unit auto-resolves as
#// the attacker; the target decision is left pending so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_110:1:0     # Frontline Shuttle (ready)
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine — sole candidate, auto-picked as attacker
WithP2GroundArena: SHD_028:1:0    # Doctor Pershing (0/5)
WithP2GroundArena: SHD_098:1:0    # Sundari Peacekeeper (1/5)

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P2BASEDMG:0

---

# ReadyUnitAttacks_TakesCounterDamage_ShuttleInDiscard
#// Intended: a READY unit may be chosen too; the attack exhausts it, combat damage is exchanged
#// (defender's power comes back at the attacker), and the Shuttle paid as the cost ends up in its
#// owner's discard pile. Consular Security Force (3/7, ready) attacks Sundari Peacekeeper (1/5):
#// Sundari takes 3, Security Force takes 1 back and ends exhausted. Single attacker + single
#// target → the whole flow auto-resolves.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_110:1:0     # Frontline Shuttle (ready)
WithP1GroundArena: SOR_046:1:0    # Consular Security Force — READY attacker
WithP2GroundArena: SHD_098:1:0    # Sundari Peacekeeper (1/5) — survives, hits back for 1

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_110
P2BASEDMG:0
P1NODECISION
