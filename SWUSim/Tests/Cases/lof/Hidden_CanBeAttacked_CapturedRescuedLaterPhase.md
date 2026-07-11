# Hidden — captured this phase, rescued in a LATER phase, becomes attackable. P2 plays Witch of the Mist
# (LOF_154, Hidden). P1 captures her with Take Captive (SHD_131); she leaves play. The round then
# advances (regroup). Next action phase P2's SOR_095 trades with P1's captor SOR_128 (both die), rescuing
# the Witch back to P2 as a fresh instance (new UniqueID). P1 then attacks with SEC_080: she is a legal
# target and is defeated, base untouched.

## GIVEN
CommonSetup: ggk/rrw/{myResources:3;theirResources:2}
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SHD_131
WithP2GroundArena: SOR_095:1:0
WithP2Hand: LOF_154
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P2>Claim
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
