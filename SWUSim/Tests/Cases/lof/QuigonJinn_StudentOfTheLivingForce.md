# Deployed_AttackEnd_ReturnAndPlayFree
#// LOF_016 Qui-Gon Jinn (deployed) — When this unit completes an attack (and survives): you may return
#// a friendly non-leader unit to its owner's hand, then play a non-Villainy unit costing less than the
#// returned unit for free. Qui-Gon attacks the base (survives), returns the SOR_046 wall (cost 4), and
#// plays the X-Wing (SOR_237, cost 2 < 4, Heroism) from hand for free.

## GIVEN
CommonSetup: gyw/brk/{
  myLeader:LOF_016:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1

---

# ReturnPlayCheaper
#// LOF_016 Qui-Gon Jinn — Action [Exhaust, use the Force]: Return a friendly non-leader unit to hand, then
#// play a non-Villainy unit that costs less from your hand for free. P1 returns SOR_046 (cost 4) and plays
#// SOR_059 (cost 1, Vigilance) for free.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_016;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SOR_059
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_059
P1HANDCOUNT:1
P1NOFORCE

---

# ReturnsButCannotPlayVillainyCheaper
#// LOF_016 Qui-Gon Jinn — the free play must be a NON-Villainy unit. Return SOR_046 (cost 4); the only
#// cheaper hand unit is SEC_080 (Command/Villainy, cost 2), which is excluded. The return still happens
#// (SOR_046 → hand), but nothing is played, and the Force is spent.
## GIVEN
CommonSetup: byw/bbk/{myLeader:LOF_016;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SEC_080
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1NOFORCE

---

# ReturnsButEqualCostNotPlayable
#// LOF_016 — the free play must cost STRICTLY LESS than the returned unit. Return SOR_046 (cost 4); the
#// hand SOR_046 (cost 4, non-Villainy) is NOT < 4, so it can't be played. Return happens, nothing played.
## GIVEN
CommonSetup: byw/bbk/{myLeader:LOF_016;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SOR_046
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2

---

# Front_NoFriendlyUnits_UseAnyway
#// LOF_016 Qui-Gon (front) — the Action can only return a FRIENDLY unit. With no friendly units (only enemy
#// units in play), it resolves to no effect, but the cost is still paid: the Force token is spent and the
#// leader exhausts. Intended: "should not be able to return enemy units and should be able to pay costs to exhaust
#// Quigon".

## GIVEN
CommonSetup: byw/bbk/{myLeader:LOF_016;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P2GROUNDARENACOUNT:1

---

# Deployed_DiesInAttack_NoTrigger
#// LOF_016 Qui-Gon (deployed) — the trigger is "completes an attack (and survives)". Qui-Gon (4/7) starts on
#// 6 damage and attacks Battlefield Marine (SOR_095, 3 power): he deals lethal but takes 3 back and is
#// defeated, so the return-and-play trigger does NOT fire (SOR_046 stays in play, SOR_237 stays in hand).
#// Intended: "should not be able to return and play a unit if he dies in the attack".

## GIVEN
CommonSetup: gyw/bbk/{myLeader:LOF_016:1:1:0:6;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_237
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1

---

# Deployed_NoFriendlyUnits_NoTrigger
#// LOF_016 Qui-Gon (deployed) — completing an attack with no OTHER friendly unit to return leaves nothing to
#// do; the optional trigger finds no target and does nothing, so the cheaper hand unit (SOR_237) is never
#// played for free (stays in hand). Intended: "should not be able to return and play a unit if there are no
#// friendly units".

## GIVEN
CommonSetup: gyw/bbk/{myLeader:LOF_016:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:4
P1HANDCOUNT:1

---

# Deployed_ReturnEvenIfNoValidPlay
#// LOF_016 Qui-Gon (deployed) — the return happens even when no cheaper unit can be played afterward. Return
#// SOR_059 (cost 1) to hand; the only hand unit SOR_046 (cost 4) is not cheaper than 1, so nothing is played.
#// SOR_059 ends in hand. Intended: "should be able to return a unit to hand even if there is no valid card to play
#// afterward".

## GIVEN
CommonSetup: gyw/bbk/{myLeader:LOF_016:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0
WithP1Hand: SOR_046

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:2

---

# Deployed_PassAbility
#// LOF_016 Qui-Gon (deployed) — the trigger is a "may", so it can be declined: SOR_046 is a legal return
#// target but P1 passes, leaving the board untouched. Intended: "should be able to pass the ability".

## GIVEN
CommonSetup: gyw/bbk/{myLeader:LOF_016:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:1

---

# Deployed_CannotPlayVillainyCheaper
#// LOF_016 Qui-Gon (deployed) — the free play must be NON-Villainy. Return SOR_046 (cost 4); the only cheaper
#// hand unit is SEC_080 (Command/Villainy, cost 2), which is excluded. The return still happens; nothing is
#// played. Intended (deployed side): "should not be able to play a Villainy unit that costs less than the returned
#// unit".

## GIVEN
CommonSetup: gyw/bbk/{myLeader:LOF_016:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SEC_080

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:2
