# Hidden on LOF_179 Aurra Sing (Hidden + Raid 2, unique, 1/4) — confirms the keyword applies to the
# unique/Raid card too, and Raid (an "while attacking" keyword) doesn't interfere with the can't-be-
# attacked block. P1 plays Aurra this phase; she's the only P1 ground unit, so P2's SEC_080 (3 power)
# has no legal unit target and auto-redirects to P1's base. Aurra is untouched.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LOF_179

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_179
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
