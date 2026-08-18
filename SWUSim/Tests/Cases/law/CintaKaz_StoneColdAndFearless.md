# SentinelWhileUpgraded
#// LAW_105 Cinta Kaz (3/5) — While this unit is upgraded, she gains Sentinel. With SOR_120 attached she
#// has Sentinel.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_105:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_105
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# SentinelNarrowsTheEnemyAttackTargetPool
#// LAW_105 Cinta Kaz — TRIAGE RESULT: the offer axis is PORTABLE, not N/A. Cinta raises no decision of
#// her own, but her granted Sentinel is precisely a POOL restriction: per CR, enemy units in her arena
#// must attack a Sentinel when they attack you, which narrows the ATTACKER's legal-target set. That set
#// is readable with ATTACKTARGETS (SWUGetAllValidAttackTargets), so the restriction can be asserted
#// directly instead of only through its downstream effect.
#// Discriminating board: P1 fields the upgraded Cinta (Sentinel) AND a non-Sentinel Battlefield Marine,
#// and P1 has a base — three targets an enemy ground unit could otherwise choose from. With Sentinel
#// live, P2's SEC_080 has exactly ONE legal target (Cinta): the Marine and the base are both excluded.
#// COVERAGE: offer=SentinelNarrowsTheEnemyAttackTargetPool + NoUpgradeNoSentinel_FullAttackTargetPool
#//           (the enemy attack-target pool, asserted with Sentinel on and off on the same board) ·
#//           reqboundary=N/A (Sentinel is a static keyword recomputed from the host's subcards on every
#//           read; there is no written state to survive a boundary) · control=NOT COVERED (the grant is
#//           "this unit", so it follows the unit; no section changes her controller) ·
#//           boundary pair=SentinelNarrowsTheEnemyAttackTargetPool (1 upgrade → 1 target) vs
#//           NoUpgradeNoSentinel_FullAttackTargetPool (0 upgrades → 3 targets) ·
#//           decline=N/A (a static "while" grant with no "you may" anywhere)

## GIVEN
CommonSetup: bbw/bgw/{}
WithP1GroundArena: LAW_105:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN

## EXPECT
ATTACKTARGETS:2:G:0:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_095

---

# NoUpgradeNoSentinel_FullAttackTargetPool
#// LAW_105 Cinta Kaz — the control that makes the section above mean something. Identical board with the
#// upgrade REMOVED: Cinta has no Sentinel, so P2's SEC_080 sees the full pool of 3 targets (Cinta, the
#// Battlefield Marine and P1's base). 1 vs 3 on the same board is the discriminator; without this half,
#// a pool that happened to be narrow for an unrelated reason would look like Sentinel working.

## GIVEN
CommonSetup: bbw/bgw/{}
WithP1GroundArena: LAW_105:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN

## EXPECT
ATTACKTARGETS:2:G:0:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SOR_095

---

# SentinelIsLostAgainWhenTheUpgradeLeaves
#// LAW_105 Cinta Kaz — "WHILE this unit is upgraded" is a continuous condition, not a one-way stamp: it has
#// to switch back off. Cinta starts upgraded and Sentinel; P1 Confiscates her own Academy Training and she
#// loses the keyword on the same board. The existing pair compares two DIFFERENT boards (upgrade seeded vs
#// never seeded), which a condition evaluated once at seat time would also satisfy — this section changes
#// the state mid-game.

## GIVEN
CommonSetup: bbk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_105:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_105
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
