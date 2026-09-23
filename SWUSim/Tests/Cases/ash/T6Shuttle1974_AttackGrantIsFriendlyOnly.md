# EnemyBuffTarget_NoAttackGrant
#// ASH_109 T-6 Shuttle 1974 — "Action [Exhaust]: Give another unit +2/+2 for this phase. You may attack
#// with that unit."
#//
#// "another unit" is UNQUALIFIED, so the BUFF may legally land on an enemy unit. The ATTACK grant may
#// not: "You can only attack with a ready friendly unit" is the general rule, printed as reminder text
#// on IBH_021 / IBH_023 / IBH_030 / IBH_036 / IBH_064 / IBH_092 (owner ruling 2026-09-24).
#//
#// Bug Report, game 1157583: the bot buffed the HUMAN's ASH_062 The Mandalorian and then attacked with
#// it. BeginSWUAttack() runs in the ABILITY controller's frame, so the enemy unit's target pool was its
#// OWN side — the engine offered ["theirGroundArena-0","theirBase-0"] and it attacked ITSELF
#// ("P2's The Mandalorian attacked P1's The Mandalorian").
#//
#// ⚠ TWO friendly units on purpose. With only one other unit on the board the choose-target prompt
#// AUTO-RESOLVES, the YESNO becomes the pending decision, and an answer meant for the target is eaten
#// by it — an earlier version of this section passed for exactly that reason while the bug was live.
## GIVEN
CommonSetup: ggw/ggw
WithP1SpaceArena: ASH_109:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:ASH_062
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# FriendlyBuffTarget_AttackGrantStillOffered
#// CONTROL — the same Action on a FRIENDLY ready unit must STILL offer the attack, so the guard above
#// cannot pass by suppressing the grant outright.
## GIVEN
CommonSetup: ggw/ggw
WithP1SpaceArena: ASH_109:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1DECISIONTOOLTIP:Attack_with_that_unit?
