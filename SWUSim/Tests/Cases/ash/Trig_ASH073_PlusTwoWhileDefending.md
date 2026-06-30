# ASH_073 Palace Chef Droid (Ground, 0/3, Sentinel) — "This unit gets +2/+0 while defending." P2's
# SEC_080 attacks ASH_073 (forced by Sentinel); ASH_073's counter is 0+2 = 2, so SEC_080 takes 2 damage.
# ASH_073 itself dies to the 3 combat damage.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_073:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
