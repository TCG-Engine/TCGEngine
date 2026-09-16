# Ready_HasSentinel
#// COVERAGE: offer=N/A (STRUCTURAL: a constant keyword grant) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=N/A (STRUCTURAL: a ready/exhausted state, no number) · negative=Exhausted_NoSentinel
#//           duration=AfterAttacking_LosesSentinel · control=N/A (no owner-scoped zone)
#//           reqboundary=N/A (STRUCTURAL: live read)
#//           behaviour=Ready_RedirectsAnAttack / Exhausted_DoesNotRedirect
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_259 Pack Guardian — Unit (Ground) 5/4, cost 4, [Heroism], Creature.
#// "While this unit is ready, it gains Sentinel."

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_259:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Exhausted_NoSentinel

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_259:0:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# AfterAttacking_LosesSentinel

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_259:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Ready_RedirectsAnAttack
#// P2's SOR_095 attacks P1's base; the ready Guardian is the only legal target and takes the 3.

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [HMW_259:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Exhausted_DoesNotRedirect

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_259:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:0
