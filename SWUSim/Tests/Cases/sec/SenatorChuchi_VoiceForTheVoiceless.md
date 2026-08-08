# OnAttack_GiveOfficialRestore2
#// SEC_045 (Ground, 2/5) — Restore 1 (auto) + On Attack: give another friendly Official unit Restore 2
#//   for this phase. SEC_045 attacks P2's base; On Attack grants SEC_041 (an Official) Restore 2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_045:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore

---

# GrantedRestoreIsPhaseDurationOnly
#// SEC_045 Senator Chuchi — the Restore 2 she grants lasts "for this phase". After the attack grants it,
#// both players pass through the regroup phase and the ally is back to having no Restore at all.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_045:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P2>Pass
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore
