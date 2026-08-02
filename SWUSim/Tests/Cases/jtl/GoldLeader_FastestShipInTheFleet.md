# Defending_AttackerMinusOne
#// JTL_054 Gold Leader — While this unit is defending, the attacker gets -1/-0. P2's SOR_237 (power 2)
#// attacks Gold Leader (5/5); the attacker's power is reduced to 1, so Gold Leader takes only 1 damage,
#// and its counter (5) defeats SOR_237.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: JTL_054:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_054
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENACOUNT:0

---

# Attacking_NoDebuffOnItself
#// JTL_054 Gold Leader — the -1/-0 applies to "the attacker" only WHILE GOLD LEADER IS DEFENDING, so
#// when Gold Leader is the one ATTACKING it must not debuff itself (nor the defender). Gold Leader
#// (5/5) attacks the enemy SOR_046 (3/7): the defender takes Gold Leader's FULL 5 power (not 4) and
#// Gold Leader takes the defender's full 3 counter-damage. Negative guard against a naive
#// "attacker gets -1/-0" that keys off the attack rather than off Gold Leader defending.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_054:1:0
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_054
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:CARDID:SOR_046
P2SPACEARENAUNIT:0:DAMAGE:5

---

# OtherFriendlyUnitDefending_NoDebuff
#// JTL_054 Gold Leader — the debuff is tied to GOLD LEADER being the defender, not merely to Gold
#// Leader being on the board. With Gold Leader sitting in the same arena, P2's SOR_237 (power 2)
#// attacks a DIFFERENT friendly unit (SOR_046): that unit takes the attacker's FULL 2 damage, and Gold
#// Leader is untouched. Negative guard against the debuff leaking into every combat in the arena.
#// (SOR_046 counters for 3, which kills the 2/3 attacker — so P2's arena ends empty.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: JTL_054:1:0
WithP1SpaceArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:1

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_054
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:1:CARDID:SOR_046
P1SPACEARENAUNIT:1:DAMAGE:2
P2SPACEARENACOUNT:0
