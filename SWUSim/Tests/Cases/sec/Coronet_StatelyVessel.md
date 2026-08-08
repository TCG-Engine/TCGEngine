# GrantsRestoreToOthers
#// SEC_047 Defiant (Space, 4/6) — Restore 1 + "Each other friendly unit gains Restore 1." The friendly
#//   SEC_041 (no innate Restore) gains Restore; SEC_047 itself keeps its innate Restore.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_047:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# EnemyUnitsDoNotGainRestore
#// SEC_047 Defiant — "Each other FRIENDLY unit gains Restore 1", so enemy units get nothing. P2's SOR_046
#// has no Restore while P1's Defiant is in play. This is the scope negative the positive section can't
#// prove on its own.
## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_047:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>Pass
## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Restore
P1SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# GrantsToNonHeroismFriendliesToo
#// SEC_047 Defiant — the grant has no aspect condition: "each other friendly unit" includes a
#// non-Heroism friendly. P1's SOR_046 Consular Security Force (Vigilance/Heroism)… is Heroism, so use
#// SEC_028 Trayus Acolyte (Vigilance/Villainy, no Restore) — it still gains Restore 1.
## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_047:1:0
WithP1GroundArena: SEC_028:1:0
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_028
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore

---

# DefiantLeavingPlay_RemovesTheGrant
#// SEC_047 Defiant — "each other friendly unit GAINS Restore 1" is a field-presence aura, so it must END
#// when Defiant leaves play. P1's SEC_028 has Restore while Defiant is in the space arena; P1 then plays
#// It's Worse (LOF_264) on its own Defiant, and SEC_028 loses the granted Restore.
## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_047:1:0
WithP1GroundArena: SEC_028:1:0
WithP1Hand: LOF_264
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore

---

# EachOtherExcludesItself_RestoreStaysAtOne
#// SEC_047 Defiant — "Each OTHER friendly unit gains Restore 1", so the grant must skip Defiant itself.
#// Keyword presence can't show this (Defiant already has innate Restore 1), so the test reads the AMOUNT:
#// P1's base carries 3 damage and Defiant attacks. If the aura wrongly included itself it would be
#// Restore 2 and heal to 1; the correct Restore 1 heals to 2.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
WithActivePlayer: 1
WithP1SpaceArena: SEC_047:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
