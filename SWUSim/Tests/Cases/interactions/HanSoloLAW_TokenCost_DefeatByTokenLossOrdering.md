# TokenLossDefeat_WhenDefeatedWaits_ThenKillsDefenderBeforeCombat
#// Deployed Han Solo (LAW_017) On Attack: "Defeat any number of friendly tokens. Deal damage to a unit
#// equal to the number of tokens defeated this way."
#//
#// A friendly unit can DIE to the cost itself: an Experience token is +1/+1, so taking it off a damaged
#// unit can drop its remaining HP to 0. SOR_204 Greedo is 3/1; with an Experience he is 4/2, and with 1
#// damage he survives — until his Experience is spent on Han's cost. That defeat then fires his own
#// "When Defeated", and WHEN it resolves decides the combat:
#//   · it must NOT interrupt the cost — the player keeps picking tokens, and the ability's damage is
#//     dealt first;
#//   · it must resolve BEFORE combat damage, because it can remove the defender.
#//
#// The board makes the ordering unmistakable. Han (4/5) carries 3 damage, so 2 HP remain, and attacks a
#// 2/2 Crafty Smuggler wearing 2 Experience tokens — a 4/4. Han defeats his own two Experience tokens
#// (Greedo's and the Cartel Turncoat's in SPACE, proving the cost spans both arenas) and points the
#// resulting 2 damage at the Smuggler. Greedo, dead from losing his Experience, then discards the top of
#// P1's deck: LAW_225 Han's Golden Dice is an UPGRADE, not a unit, so his ability deals 2 more damage to
#// the Smuggler. That is 4 damage on a 4 HP unit — the defender is gone before the attack deals damage,
#// so Han never trades and SURVIVES on 3 damage.
#//
#// THE DISCRIMINATOR IS HAN'S LIFE. Resolve Greedo's trigger after combat instead and the 4/4 Smuggler
#// still dies, but to Han's 4 combat damage — and it deals 4 back into his 2 remaining HP, so Han is
#// defeated and P1's ground arena is empty. Asserting only "the Smuggler died" would pass either way.

## GIVEN
# myLeader is POSITIONAL — cardID:ready:deployed:epicUsed:damage. Han is deployed with 3 damage, so
# 2 HP remain. (There is no `myLeaderDamage` option; a key like that is silently ignored, which leaves
# the leader on full HP and quietly destroys this section's discriminator.)
CommonSetup: yyw/grw/{
  myLeader:LAW_017:1:1:0:3;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:1
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1SpaceArena: SHD_195:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1Deck: [LAW_225 SOR_095 SOR_095]
WithP2GroundArena: SOR_207:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:mySpaceArena-0.u0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
# The defender died to the two ability damages (2 from the cost + 2 from Greedo), before combat.
P2GROUNDARENACOUNT:0
# Han survived: still exactly his starting 3 damage, so he took no combat damage back.
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_017
P1GROUNDARENAUNIT:0:DAMAGE:3
# Greedo was defeated by the loss of his Experience token, and his trigger spent the top card.
P1DISCARDCOUNT:2
# The space unit paid its token and is otherwise untouched.
P1SPACEARENAUNIT:0:CARDID:SHD_195
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:0
# No combat damage was dealt to the base either — the attack lost its target.
P2BASEDMG:0

---

# FrontAction_TokenLossDefeat_WhenDefeatedWaitsForTheDamageClause
#// The leader-FRONT Action has the same two-clause shape: "Action [Exhaust, defeat a friendly token]:
#// Deal 1 damage to a unit." Paying the cost with Greedo's Experience defeats him (3/1 carrying 1
#// damage), and his When Defeated must wait for the damage clause rather than resolving between the
#// cost and the effect.
#//
#// A second token (a Credit) is on the board so the cost does NOT auto-resolve, and a second friendly
#// unit (SOR_095) so both damage prompts have a real choice — with a single legal target the engine
#// resolves the damage for the player and the ordering question stops being observable at all.
#//
#// The WHEN order IS the assertion: the harness refuses an answer that is not a candidate of the
#// pending decision, so these five lines only run through if the engine asks in exactly this order —
#// token, then Han's 1 damage, then Greedo's discard, then Greedo's 2 damage. Resolve Greedo first and
#// the third line (a unit mzID) lands on his YES/NO instead.
#// Outcome: the 2/2 defender takes 1 from Han and 2 from Greedo and is defeated; the bystander is
#// untouched, which is what proves each damage went where it was pointed.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: SOR_204:1:1
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [LAW_225 SOR_095 SOR_095]
WithP2GroundArena: SOR_207:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:2
P1CREDITCOUNT:1
