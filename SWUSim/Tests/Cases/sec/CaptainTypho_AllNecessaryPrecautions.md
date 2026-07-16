# CantDisclose_NoHeal
#// SEC_098 Captain Typho — fizzle: P2's hand can't cover CommandHeroism (only SOR_225, Villainy), so
#//   PlayerCanDisclose is false and NO disclose decision is queued. Combat auto-resolves with no answer
#//   needed and the base is not healed. Proves the no-valid-disclose path doesn't hang combat.

## GIVEN
CommonSetup: ggw/ggw/{theirBaseDamage:3;theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_098:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION
P2NODECISION

---

# DeclineDisclose_NoHeal
#// SEC_098 Captain Typho — On Defense disclose is OPTIONAL. P2 declines the disclose (AnswerDecision:-),
#//   so no base heal happens; the base stays at 3 damage. Combat still resolves normally (Typho takes 3,
#//   counters 4). Proves the "you may" decline path no-ops cleanly and combat is not blocked.

## GIVEN
CommonSetup: ggw/ggw/{theirBaseDamage:3;theirHandCardIds:SEC_096}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_098:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# OnDefense_Disclose_HealBase
#// SEC_098 Captain Typho (Ground, 4/5, Command/Heroism) — Sentinel + On Defense (when this unit is
#//   attacked): you may disclose CommandHeroism → heal 1 damage from your base.
#// P2 controls Typho (defender), base pre-damaged 3. P1's SOR_046 (3/7, power 3) attacks Typho; before
#// combat damage P2 discloses SEC_096 (Command,Heroism → covers CommandHeroism) and heals 1 from its
#// base (3→2). Combat then resolves: Typho takes 3 (survives), counters 4 onto SOR_046 (survives).
#// This only works because the combat-pause resolves the defender's On Defense reaction before damage.

## GIVEN
CommonSetup: ggw/ggw/{theirBaseDamage:3;theirHandCardIds:SEC_096}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_098:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4
P2HANDCOUNT:1
