# OnDefense_CreatesCredit
#// LAW_121 Canto Bight Security (Unit, cost 5, Vigilance, 3/5) — Sentinel + On Defense: Create a Credit token.
#//   P2's SEC_080 (3/3) is forced by Sentinel to attack LAW_121. The On Defense fires → P1 gets a Credit.
#//   LAW_121 (3/5) survives the 3 damage; SEC_080 (3/3) dies to the 3 counter. Turn alternates normally.

## GIVEN
CommonSetup: bbk/grk/{}
WithActivePlayer: 2
WithP1GroundArena: LAW_121:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_121
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# AttackingDoesNotTrigger
#// LAW_121 Canto Bight Security — On Defense fires only when DEFENDING. When Canto Bight attacks (here
#// the enemy base), the On Defense ability does NOT trigger, so P1 gains no Credit. Combat deals 3.

## GIVEN
CommonSetup: bbk/grk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_121:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0
P2BASEDMG:3

---

# SentinelNarrowsTheEnemyAttackTargetPool
#// LAW_121 Canto Bight Security — the printed Sentinel is a pool restriction: an enemy ground unit that
#// attacks must attack the Sentinel, so P2's SEC_080 has exactly ONE legal target even though a
#// non-Sentinel friendly unit and P1's base are also on the board. Both existing sections show the On
#// Defense Credit or its absence; neither measures the Sentinel half at all.

## GIVEN
CommonSetup: bbk/grk/{}
WithP1GroundArena: LAW_121:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN

## EXPECT
ATTACKTARGETS:2:G:0:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_095

---

# OnDefenseStillFiresWhenTheDefenderDIES
#// LAW_121 Canto Bight Security — On Defense resolves at the start of the defence, so the Credit is created
#// even when the Security is killed by the very attack that triggered it. P2's 8/8 SOR_039 attacks into the
#// 3/5 Security: it is defeated, and P1 still ends the exchange holding 1 Credit. OnDefense_CreatesCredit
#// has the defender survive, so it cannot separate "fires on defence" from "fires if it lives".

## GIVEN
CommonSetup: bbk/grk/{}
WithActivePlayer: 2
WithP1GroundArena: LAW_121:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
