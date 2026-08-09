# DeployedTokenAttackBuff
#// TS26_07 Asajj Ventress (leader deployed, 3/5) — Hidden + "While you've attacked with a token unit this
#// phase, this unit gets +2/+0." After the Battle Droid token attacks, deployed Asajj is 5 power.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_07:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TS26_07
P1GROUNDARENAUNIT:1:POWER:5

---

# FrontAttackWithToken
#// TS26_07 Asajj Ventress (leader front) — Action [Exhaust]: attack with a token unit; it gets +1/+0 for
#// this attack. The Battle Droid token (1 power) attacks the enemy base with +1 → deals 2.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_07}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# Front_OffersOnlyREADYFRIENDLYTokenUnits
#// TS26_07 Asajj Ventress (front) — "Attack with a TOKEN unit." The pool is friendly token units that
#// could actually declare an attack: the two ready tokens at indices 0 and 1. The exhausted friendly token
#// (index 2), the non-token SEC_080 (index 3) and P2's own token are all excluded — covering the three
#// separate ways a target can drop out of this pool: exhausted, not a token, not friendly.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_07}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TS26_T01:1:0 TS26_T02:1:0 TS26_T01:0:0 SEC_080:1:0]
WithP2GroundArena: TS26_T01:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Deployed_NONTokenAttack_GivesNoBuff
#// TS26_07 Asajj Ventress (deployed) — "While you've attacked with a TOKEN unit this phase, +2/+0". A
#// non-token attacker (SEC_080) does not arm it, so deployed Asajj stays at her printed 3 power.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_07:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TS26_07
P1GROUNDARENAUNIT:1:POWER:3

---

# Deployed_ENEMYTokenAttack_GivesNoBuff
#// TS26_07 Asajj Ventress (deployed) — "while YOU'VE attacked". P2 attacking with THEIR Battle Droid token
#// arms nothing for P1, so deployed Asajj is still 3 power. Pairs with DeployedTokenAttackBuff, where a
#// friendly token attack takes her to 5.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_07:1:1}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TS26_07
P1GROUNDARENAUNIT:0:POWER:3
