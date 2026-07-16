# Buff_ExpiresNextPhase
#// SHD_097 Freetown Backup — the +2/+2 is "for this phase": gone after the regroup.

## GIVEN
CommonSetup: gbw/gbw
P1OnlyActions: true
WithP1GroundArena: SHD_097:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3

---

# OnAttack_BuffAnotherFriendly
#// SHD_097 Freetown Backup (1/4) — "On Attack: Give another friendly unit +2/+2 for this phase."
#// Self is excluded; the marine is picked (MZMAYCHOOSE — the OnAttack-safe choose) → 5/5 this phase.

## GIVEN
CommonSetup: gbw/gbw
P1OnlyActions: true
WithP1GroundArena: SHD_097:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:0:POWER:1
