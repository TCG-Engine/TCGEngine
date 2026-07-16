# WhenDefeated_PutsSelfAsResource
#// SOR_083 Superlaser Technician (2/1, Ground) — When Defeated: You may put this unit into
#// play as a resource AND READY IT. It attacks Battlefield Marine (3/3): it deals 2 (Marine
#// survives) and takes 3 (1 HP → defeated). The ramp auto-resolves (nobody declines it), moving
#// a SOR_083 copy from discard into the resource zone READY (explicit "and ready it"): resources 0 → 1.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_083:1:0    # Superlaser Technician (ready) — attacker, dies
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (3/3) — kills it back

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:1
