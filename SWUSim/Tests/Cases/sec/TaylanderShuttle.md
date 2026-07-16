# OnAttack_NoInitiative_NoSpy
#// SEC_115 Taylander Shuttle — no initiative → no Spy token. P1OnlyActions gives P2 the initiative.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_115:1:0

## WHEN
- P1>AttackSpaceArena:0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:0
P1NODECISION

---

# OnAttack_WithInitiative_CreateSpy
#// SEC_115 Taylander Shuttle (Space, 2/4, Command) — On Attack: if you have the initiative, create a Spy.
#// P1 holds claimed initiative → attacking the base creates a Spy token.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1SpaceArena: SEC_115:1:0

## WHEN
- P1>AttackSpaceArena:0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
