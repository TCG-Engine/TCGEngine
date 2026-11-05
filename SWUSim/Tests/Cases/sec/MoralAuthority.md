# WhenPlayed_CaptureLesserHP
#// SEC_256 Moral Authority (Upgrade, Heroism, cost 3) — Attach to a friendly unique unit. When Played:
#//   attached unit captures an enemy non-leader unit with less remaining HP than it. Host SEC_065 (7 HP)
#//   captures SOR_095 (3 HP < 7).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_065:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_256

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_065
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# WhenPlayed_AttachesOnlyToUniqueFriendly
#// SEC_256 Moral Authority — the upgrade may attach ONLY to a friendly UNIQUE unit. With a unique host
#//   (SEC_065 Nala Se) and a non-unique friendly (SOR_095 Battlefield Marine) both in play, the only
#//   legal host is the unique unit: it auto-attaches to SEC_065 and the non-unique SOR_095 stays bare.
#//   The enemy SOR_095 (3 HP < 7) is then captured.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_065:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_256

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_065
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# WhenPlayed_CaptureRequiresStrictlyLessHP
#// SEC_256 Moral Authority — the captured enemy must have STRICTLY LESS remaining HP than the attached
#//   unit (equal HP is not enough). Host SEC_065 has 7 remaining HP. The enemy SOR_095 (3 HP) is a valid
#//   capture target, but LAW_124 (7 HP, exactly equal) is NOT — so only SOR_095 is captured and LAW_124
#//   remains in the enemy arena.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_065:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_256

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:CARDID:SEC_065
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# Attach_EnemyUniqueIsNotALegalHost
#// SEC_256 Moral Authority — "Attach to a FRIENDLY unique unit." Bug report 2026-11-04: the card could be
#//   attached to the OPPONENT's unique unit, whose When Played then made that unit capture one of its own
#//   (a token with upgrades was removed illegally). The upgrade default host pool is every unit on both
#//   sides (CR 2.e), and SEC_256's case narrowed it by "unique" only. Here P1's SEC_065 Nala Se (unique,
#//   7 HP) and P2's SOR_094 Bail Organa (unique, 2 HP) are both in play — only Nala Se is a legal host, so
#//   it auto-attaches there (no host pick) and Bail Organa is captured.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_065:1:0
WithP2GroundArena: SOR_094:1:0
WithP1Hand: SEC_256

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_065
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# Attach_NoFriendlyUniqueFizzles
#// SEC_256 Moral Authority — the reported board: P1 controls only a NON-unique unit (SOR_095) and P2
#//   controls a unique one (SOR_094 Bail Organa) beside a non-unique SOR_095. There is no FRIENDLY unique
#//   unit, so there is no legal host and the play fizzles: the card stays in hand, nothing attaches to
#//   either side, and P2 loses nothing to a self-capture.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_094:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_256

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
