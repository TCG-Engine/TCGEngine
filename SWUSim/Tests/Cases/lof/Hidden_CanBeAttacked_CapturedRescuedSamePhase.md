# Hidden — captured + rescued the SAME phase it was played still becomes attackable. P2 plays Witch of
# the Mist (LOF_154, Hidden) this phase (would be unattackable). P1 plays Take Captive (SHD_131),
# choosing its captor (SOR_128) and capturing the Witch (she leaves play). P2's SOR_095 (3/3) attacks
# the captor SOR_128 (3/1): they trade and both die, so the Witch is RESCUED back to P2 the same phase
# as a fresh instance (new UniqueID, no played-this-phase marker) and is the ONLY enemy ground unit.
# P1 then attacks with SEC_080 (3 power): if the rescue cleared the Hidden block she is the target and
# is defeated; if not, the attack would auto-redirect to P2's base and she'd be untouched. Defeated +
# base untouched proves rescue clears the block.

## GIVEN
CommonSetup: ggk/rrw/{myResources:3;theirResources:2}
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SHD_131
WithP2GroundArena: SOR_095:1:0
WithP2Hand: LOF_154

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
