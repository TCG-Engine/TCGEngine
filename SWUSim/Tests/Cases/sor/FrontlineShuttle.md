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
