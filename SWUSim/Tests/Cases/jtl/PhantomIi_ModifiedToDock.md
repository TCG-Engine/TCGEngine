# AttachesToGhost_Buffs
#// JTL_050 Phantom II — Action [1 resource]: attach it as an upgrade to The Ghost (JTL_053). It's no
#// longer a unit; The Ghost gets +3/+3 and gains Grit. P1 activates Phantom II's action: it leaves the
#// space arena and becomes an upgrade on The Ghost, which goes 5/6 → 8/9 and gains Grit. 1 resource spent.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: JTL_050:1:0
WithP1SpaceArena: JTL_053:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_050
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:9
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
P1RESAVAILABLE:1

---

# AttachToOpponentGhost
#// JTL_050 Phantom II — "attach it as an upgrade to The Ghost" targets by TITLE, and an OPPONENT's Ghost
#// is a legal host (CR — an ability naming "The Ghost" refers to any card titled that). P1 controls
#// Phantom II + The Ghost (JTL_053); P2 controls a different The Ghost (SOR_050 Spectre Home Base). Two
#// valid hosts → P1 is prompted and picks the ENEMY Ghost. Phantom attaches to P2's Ghost, which becomes
#// 8/8 and gains Grit (buffing the opponent's unit); P1's own Ghost is untouched. Was a real engine gap:
#// the handler hardcoded JTL_053 and only scanned the active player's arenas.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: JTL_050:1:0
WithP1SpaceArena: JTL_053:1:0
WithP2SpaceArena: SOR_050:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_050
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_050
P2SPACEARENAUNIT:0:POWER:8
P2SPACEARENAUNIT:0:HP:8
P2SPACEARENAUNIT:0:HASKEYWORD:Grit
P1RESAVAILABLE:1

---

# AttachToFriendlyGhostWhenBothPresent
#// JTL_050 Phantom II — mirror of AttachToOpponentGhost: with a friendly The Ghost (JTL_053) AND an enemy
#// The Ghost (SOR_050) both in play, P1 is prompted and picks the FRIENDLY Ghost. Phantom attaches to
#// JTL_053 (→ 8/9, Grit); the enemy SOR_050 is left unchanged (5/5, no upgrade). Confirms the 2-host
#// picker offers both and honors a friendly choice.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: JTL_050:1:0
WithP1SpaceArena: JTL_053:1:0
WithP2SpaceArena: SOR_050:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_050
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:9
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_050
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2SPACEARENAUNIT:0:POWER:5
P2SPACEARENAUNIT:0:HP:5

---

# RemovesUpgradesOnAttach
#// JTL_050 Phantom II — "(It's no longer a unit. Defeat all upgrades on it and remove all damage from it.)"
#// Phantom II carries Academy Training (SOR_120, a normal upgrade) + a Shield token (SOR_T02) and is
#// activated. On attach both are removed: the normal upgrade → owner's (P1) discard, the token → set aside
#// (no discard entry). Phantom becomes a CLEAN upgrade on The Ghost (its only upgrade), which is buffed to
#// 8/9. (The "rescue all captured units" clause is covered separately in RescuesCapturedUnitsOnAttach.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: JTL_050:1:0
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_050
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:9
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_120

---

# GritSharedToHostFunctional
#// JTL_050 Phantom II — "Attached unit ... gains Grit" is FUNCTIONAL, not just present. Phantom is attached
#// to The Ghost (8/9, Grit). P2 plays Open Fire (TWI_174: deal 4 to a unit) at the Ghost. Grit means the
#// Ghost's power scales with its damage: 8 base + 4 damage = 12 power, and it survives (4 < 9 HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 8
WithP2Hand: TWI_174
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:JTL_050

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:DAMAGE:4
P1SPACEARENAUNIT:0:POWER:12

---

# PhantomDefeatedWhenHostDefeated
#// JTL_050 Phantom II (attached) is an upgrade — when the unit it's attached to is defeated, Phantom is
#// defeated with it. Phantom is on The Ghost; P2 plays Vanquish (TWI_077: defeat a non-leader unit) at the
#// Ghost. Both The Ghost and Phantom go to their owner's (P1) discard; P1's space arena is empty.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 10
WithP2Hand: TWI_077
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:JTL_050

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2
P1HANDCOUNT:0

---

# PhantomDefeatedByUpgradeDefeat
#// JTL_050 Phantom II (attached) can be targeted directly as an upgrade. Phantom is on The Ghost; P2 plays
#// Confiscate (SHD_262: defeat an upgrade). The Ghost's only upgrade is Phantom, so it auto-targets and
#// Phantom is defeated to P1's discard. The Ghost itself survives (now unbuffed, back to 5/6).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 4
WithP2Hand: SHD_262
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:JTL_050

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_050

---

# PhantomReturnedByBamboozle
#// JTL_050 Phantom II (attached) is returned to hand by an upgrade-return effect. Phantom is on The Ghost;
#// P2 plays Bamboozle (SOR_199: exhaust a unit and return each upgrade on it to its owner's hand) at the
#// Ghost. Phantom (owned by P1) returns to P1's HAND (not discard); The Ghost is exhausted and unbuffed.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_199
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaUpgrade: 0:JTL_050

## WHEN
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1HANDCARD:0:JTL_050

---

# RescuesCapturedUnitsOnAttach
#// JTL_050 Phantom II — "(It's no longer a unit. … rescue all captured units …)". Phantom II is guarding a
#// captured enemy unit (P2's SOR_128, seeded via WithP1SpaceArenaCaptive). When P1 activates the attach
#// ability, Phantom leaves play as a unit, so per CR 8.34.4 its captive is RESCUED — SOR_128 returns to its
#// owner P2's ground arena, exhausted. Phantom becomes a clean upgrade on The Ghost (no captive carried).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: JTL_050:1:0
WithP1SpaceArena: JTL_053:1:0
WithP1SpaceArenaCaptive: 0:SOR_128

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_050
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:EXHAUSTED
