# OnAttack_Pay2For2Base
#// SEC_264 Clandestine Connections (Upgrade) — granted "On Attack: you may pay 2 resources → deal 2 to a
#//   base." Host SOR_095 attacks P2 base (3 combat); pay 2 → +2 to P2 base = 5; resources spent.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_264

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:6
P1RESAVAILABLE:0
P1NODECISION

---

# OnAttack_Pay2For2FriendlyBase
#// SEC_264 Clandestine Connections — the granted On Attack "deal 2 to A base" may target the CONTROLLER's
#//   OWN base. Host SOR_095 (4/4 with the upgrade's +1/+1) attacks an enemy ground unit (so no base combat
#//   damage muddies the total), then pays 2 and chooses P1's own base → P1 base takes exactly 2.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_264
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P1RESAVAILABLE:0
P1NODECISION

---

# OnAttack_NotEnoughResources_NoTrigger
#// SEC_264 Clandestine Connections — the pay-2 cost is a "may pay". With only 1 resource the controller
#//   cannot pay, so no base damage is dealt. Host attacks an enemy ground unit; neither base is damaged and
#//   the resource is not spent.

## GIVEN
CommonSetup: rrk/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_264
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1RESAVAILABLE:1

---

# OnAttack_MayDecline_Pass
#// SEC_264 Clandestine Connections — the On Attack ability is optional; declining it leaves both bases
#//   untouched and the resources unspent even though the controller could afford the 2.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_264
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1RESAVAILABLE:2

---

# GrantedOnAttack_FollowsTheHOSTSNewController
#// SEC_264 Clandestine Connections — the granted On Attack belongs to whoever controls the host. P2
#// plays SOR_122 Traitorous to take control of P1's upgraded SOR_095, then attacks with it: P2 is the
#// one offered the "pay 2 → deal 2 to a base", pays from P2's OWN resources, and aims it at P1's base.
#// P1's base takes 4 combat (SOR_095 is 3 power plus the upgrade's +1/+1) + 2 = 6.

## GIVEN
CommonSetup: rrk/ggk
WithActivePlayer: 2
WithP2Resources: 8
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_264
WithP2Hand: SOR_122

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:6
