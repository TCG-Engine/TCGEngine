# WhenDefeated_PutsSelfAsResource
#// COVERAGE: offer=N/A (no target choice — the ability moves only "this unit") · decline=N/A by
#//           standing ruling — the "you may" ramp AUTO-RESOLVES (free ready resource is always
#//           taken; deliberate product call, same as SHD_085) · boundary=WhenDefeated_PutsSelfAsResource
#//           (card still in discard → ramps, READY) vs MovedToResourcesByEnemySteal_WhenDefeatedFizzles
#//           (card already gone from discard → no ramp) · control=MovedToResourcesByEnemySteal_
#//           WhenDefeatedFizzles (the stolen card ends owned by P2 but controlled by P1; the ramp is
#//           keyed to the DEFEATED unit's controller and finds nothing) · reqboundary=N/A (the ramp
#//           resolves in the defeat drain with no intervening decision)
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
P1NODECISION
P1GROUNDARENACOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:1

---

# MovedToResourcesByEnemySteal_WhenDefeatedFizzles
#// SOR_083 Superlaser Technician — the When Defeated ramp moves "this unit" from its owner's
#// discard; if the card has already LEFT the discard when the trigger resolves, it fizzles.
#// P1's Arquitens Assault Cruiser (SHD_122, 7/8: attacks-and-defeats a non-leader unit → put the
#// defeated unit into play as a resource under P1's control) attacks the Technician via Swoop Down
#// (SHD_230: space unit gains Saboteur + can attack ground, +2/+0 and defender -2/-0). The 0-power
#// Technician dies dealing nothing back; the steal takes the card out of P2's discard, so the
#// Technician's own ramp finds nothing: P2 gains NO resource. The stolen card sits in P1's resource
#// zone exhausted (steal has no "ready it" rider), owned by P2 but controlled by P1.

## GIVEN
CommonSetup: yyk/ggk/{myResources:1;handCardIds:SHD_230}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0     # Arquitens Assault Cruiser — attacker
WithP2GroundArena: SOR_083:1:0    # Superlaser Technician 2/1 — defeated, then stolen

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESCOUNT:2
P1RESAVAILABLE:0
P2RESCOUNT:0
P2DISCARDCOUNT:0
P2GROUNDARENACOUNT:0
P1NODECISION
