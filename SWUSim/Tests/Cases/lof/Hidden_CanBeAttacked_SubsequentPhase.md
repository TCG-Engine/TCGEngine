# Hidden — the block is only "the phase it was played." P1 plays Witch of the Mist (LOF_154, 1/3,
# Hidden) this phase, then the round advances (regroup clears the played-this-phase marker). Next action
# phase P2 attacks her (SEC_080, 3 power) → she is now a legal target and is defeated. Base is untouched
# (the attack lands on the unit, not redirected), proving she became attackable.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LOF_154

## WHEN
- P1>PlayHand:0
- P2>Claim
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0
