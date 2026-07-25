# Darksaber_AttachesUniqueNonVehicle
#// ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Positive host case (guards over-blocking).
#// Board has a valid host: LAW_139 Admiral Motti (unique=true, Imperial/Official, NO Vehicle trait).
#// Darksaber is a legal fit → it attaches. Proves the restriction doesn't over-block a valid host.
#// Darksaber is Command, cost 4 → ggw covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_135

---

# Darksaber_CantAttachNonUnique
#// ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Host-restriction (unique half).
#// Board has ONLY a non-unique non-Vehicle unit (SOR_095 Battlefield Marine, unique=false) — it is a
#// non-Vehicle, so this isolates the *unique* rule: the only reason it's an illegal host is that it
#// isn't unique. Darksaber has no valid host → no-op, card stays in hand, the unit stays bare.
#// Darksaber is Command, cost 4 → ggw covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Darksaber_CantAttachVehicle
#// ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Host-restriction (Vehicle half).
#// Board has ONLY a unique Vehicle (SOR_089, unique Imperial Capital Ship) — it IS unique, so this
#// isolates the *non-Vehicle* rule: the only reason it's an illegal host is its Vehicle trait.
#// Darksaber has no valid host → the play is a no-op, the card stays in hand, the Vehicle stays bare.
#// Darksaber is Command, cost 4 → ggw (Command base + Command/Heroism leader) covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1SpaceArena: SOR_089:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# Darksaber_LeaderUnit
#// ASH_135 The Darksaber — "Attached unit is a leader unit." A friendly LAW_139 Admiral Motti ("friendly
#// leader units get +2/+2") sees the Darksaber-wearing SOR_046 as a leader unit, so it gets the +2/+2 on
#// top of its 7/9 (Darksaber stats) → 9/11. (Without the leader-unit grant it would stay 7/9.)
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP1GroundArena: LAW_139:1:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:11

---

# Darksaber_MandalorianTrait
#// ASH_135 The Darksaber — "Attached unit gains the Mandalorian trait." A friendly ASH_113 Mandalorian
#// Flagship ("+1/+0 for each OTHER friendly Mandalorian unit") counts the Darksaber-wearing SOR_046 (now
#// Mandalorian) and gets +1 → 5 power (base 4). SOR_046 is normally Rebel/Trooper, so without the grant
#// ASH_113 would stay at 4.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_113:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## EXPECT
P1SPACEARENAUNIT:0:POWER:5

---

# Darksaber_NoAspectWithoutIt
#// ASH_135 — control: without the Darksaber, SOR_046 provides no aspect icons, so P1 (Cunning/Villainy)
#// faces the full +2 Heroism penalty on SOR_237 (cost 2 → 4) and cannot afford it on 2 resources — the
#// unit stays in hand. Proves the aspect provision comes from the Darksaber.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1

---

# Darksaber_ProvidesAspect
#// ASH_135 The Darksaber — "While you are paying costs, the attached unit provides its aspect icons." P1
#// (Cunning/Villainy, no Heroism) plays SOR_237 (cost 2, mono-Heroism) on exactly 2 resources: the +2
#// off-aspect Heroism penalty is waived because the Darksaber-wearing SOR_046 (Vigilance/Heroism) provides
#// Heroism. The unit enters play with 0 resources left.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:0

---

# Darksaber_Stats
#// ASH_135 The Darksaber (Upgrade, +4/+2) attached to SOR_046 (3/7) — the host becomes a 7/9.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:9

---

# Darksaber_AttachesUniqueNonVehicleSpace
#// ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Positive host case in the SPACE arena.
#// LOF_098 Leia Organa is a unique, non-Vehicle SPACE unit → a legal host. Proves the restriction admits
#// unique non-Vehicle units regardless of arena. Darksaber is Command, cost 4 → ggw covers it, 4 resources.
## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1SpaceArena: LOF_098:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:ASH_135

---

# Darksaber_IsLeaderUnitFlag
#// ASH_135 The Darksaber — "Attached unit is a leader unit." Direct flag check: the Darksaber-wearing
#// SOR_046 (a plain unit) reports as a leader unit.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT

---

# Darksaber_NotLeaderUnitWithout
#// ASH_135 — control for the leader-unit grant: a bare SOR_046 (no Darksaber) is NOT a leader unit.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
## EXPECT
P1GROUNDARENAUNIT:0:NOTLEADERUNIT

---

# Darksaber_CoversTwoUnmatchedAspects
#// ASH_135 — aspect provision covers TWO unmatched aspects at once. P1 is Cunning/Villainy (yyk), so a
#// Vigilance+Heroism card faces +2 (Vigilance) +2 (Heroism) = +4. The Darksaber-wearing SOR_046
#// (Vigilance/Heroism) provides BOTH icons, waiving the whole penalty, so SEC_044 (cost 3, Vigilance/Heroism)
#// is playable on exactly 3 resources and enters play with 0 resources left. (Without it: cost 7, unaffordable.)
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:SEC_044}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# Darksaber_DoubleAspectCard
#// ASH_135 — aspect provision works for DOUBLE-aspect cards (two icons of the SAME aspect). P1 is
#// Cunning/Villainy (yyk); a Vigilance+Vigilance card faces +4. The Darksaber-wearing JTL_056 Hondo Ohnaka
#// (Vigilance/Vigilance) provides two Vigilance icons, covering both, so LOF_055 Dume (cost 4,
#// Vigilance/Vigilance) is playable on exactly 4 resources → enters with 0 left. (Without it: cost 8.)
## GIVEN
CommonSetup: yyk/yyk/{myResources:4;handCardIds:LOF_055}
WithActivePlayer: 1
WithP1GroundArena: JTL_056:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# Darksaber_NotBelowPrintedCost
#// ASH_135 — aspect provision never reduces below printed cost. P1 is Command/Heroism (ggw), so SOR_095
#// Battlefield Marine (cost 2, Command/Heroism) already has both aspects covered by the leader+base — no
#// penalty. The Darksaber-wearing SOR_046 adds extra Vigilance/Heroism icons, but with the penalty already
#// at 0 there is nothing to discount: SOR_095 still costs exactly its printed 2, entering with 0 left.
## GIVEN
CommonSetup: ggw/ggw/{myResources:2;handCardIds:SOR_095}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# Darksaber_NeutralHostPaysPenalty
#// ASH_135 — a neutral (no-aspect) host provides NO icons, so penalties apply as normal. SHD_255 Lady
#// Proxima has no aspects; wearing the Darksaber it still contributes nothing to cost payment. P1 is
#// Cunning/Villainy (yyk), so SOR_237 (cost 2, Heroism) costs 2 + 2 penalty = 4 → affordable on exactly 4
#// resources and enters with 0 left. Proves the provision is the host's own icons, not the Darksaber's.
## GIVEN
CommonSetup: yyk/yyk/{myResources:4;handCardIds:SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SHD_255:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:0

---

# Darksaber_NoAspectForOpponent
#// ASH_135 — the aspect provision only helps the CONTROLLER. P1 controls a Darksaber-wearing SOR_046
#// (Vigilance/Heroism), but P2 (Cunning/Villainy, yyk) gains no benefit: P2's SOR_237 (cost 2, Heroism)
#// still costs 2 + 2 penalty = 4 → enters on exactly 4 resources with 0 left. (If it leaked to P2, cost 2.)
## GIVEN
CommonSetup: yyk/yyk/{theirResources:4;theirhandCardIds:SOR_237}
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P2>PlayHand:0
## EXPECT
P2SPACEARENACOUNT:1
P2RESAVAILABLE:0

---

# Darksaber_StopsProvidingAfterDefeated
#// ASH_135 — the provision ends when the Darksaber leaves play. P1 (Cunning/Villainy, yyk) has SOR_046
#// wearing the Darksaber (provides Heroism). P1 uses SOR_251 Confiscate (cost 1) to defeat its OWN Darksaber
#// (res 4 → 3). Now SOR_237 (cost 2, Heroism) costs 2 + 2 penalty = 4 > 3 remaining → the play is a no-op:
#// it stays in hand and no resources are spent. Proves the aspect no longer resolves once the Darksaber is gone.
## GIVEN
CommonSetup: yyk/yyk/{myResources:4;handCardIds:SOR_251,SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:3

---

# Darksaber_ProvidesAspectForSmuggle
#// ASH_135 — the aspect provision applies to Smuggle costs too. SOR_094 Bail Organa (Command/Heroism) wears
#// the Darksaber; P1's leader+base are Cunning/Villainy (yyk), covering neither Command nor Heroism. SHD_097
#// Freetown Backup has Smuggle [4 resources, Command Heroism]; the Darksaber-supplied Command+Heroism waive
#// the +4 penalty, so it Smuggles for exactly 4 (the Freetown resource itself + 3 others) → 0 resources left
#// and it enters the ground arena. (Without the provision the Smuggle cost would be 8, unaffordable.)
## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithP1Resources: 1:SHD_097,3:SOR_095
WithP1GroundArena: SOR_094:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>SmuggleResource:0
## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# Darksaber_NoDoubleCountLeaderAspects
#// ASH_135 — attached to a DEPLOYED leader unit, the Darksaber must not double-count the leader's own aspects.
#// Leia (Command/Heroism) deployed wearing the Darksaber; base is Aggression. Playing SHD_107 Enterprising
#// Lackeys (Command/Command, cost 4) leaves one Command unmatched → +2 penalty → cost 6 (not 4). The leader's
#// single Command is provided once (by the leader card), NOT re-provided by the Darksaber on the same card.
## GIVEN
CommonSetup: rgw/brk/{myResources:6; myLeader:SOR_009:1:1; myhandCardIds:SHD_107}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:2

---

# Darksaber_ChangeOfHeart_CantTargetLeaderUnit
#// ASH_135 The Darksaber — the attached unit is a leader unit, so effects that only target non-leader units
#// can't target it. P2 plays SOR_224 Change of Heart (targets a non-leader unit). P1 controls a Darksaber-
#// wearing SOR_046 (leader unit, excluded) plus a plain SOR_164 Wampa (selectable); P2 also controls a
#// SOR_095 Battlefield Marine (selectable). Only the two non-leader units are valid targets.
## GIVEN
CommonSetup: ggw/yyk/{theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: [SOR_046:1:0 SOR_164:1:0]
WithP1GroundArenaUpgrade: 0:ASH_135
WithP2GroundArena: SOR_095:1:0
WithP2Hand: SOR_224
## WHEN
- P2>PlayHand:0
## EXPECT
P2SELECTABLEEXACT:theirGroundArena-1&myGroundArena-0

---

# Darksaber_ChangeOfHeart_CanTargetAfterDefeated
#// ASH_135 The Darksaber — once the Darksaber leaves play the host is no longer a leader unit and can again
#// be targeted by non-leader-only effects. P1's SHD_255 Lady Proxima wears the Darksaber; P1 defeats it with
#// its own SOR_251 Confiscate. P2 then plays SOR_224 Change of Heart to take control of her (now a plain
#// non-leader unit) — she moves to P2's ground arena.
## GIVEN
CommonSetup: yyk/yyk/{myResources:1;theirResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_255:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP1Hand: SOR_251
WithP2Hand: SOR_224
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SHD_255

---

# Darksaber_CountsAsLeaderUnit_ExecutionersArena
#// ASH_135 The Darksaber — the bearer counts as a friendly leader unit for "for each friendly leader unit"
#// effects. Base TS26_11 Executioner's Arena Epic Action deals 2 damage per friendly leader unit. With a
#// deployed leader (1) plus the Darksaber-wearing SOR_046 (1) there are 2 leader units → two 2-damage
#// instances, hitting each of the two enemy units for 2.
## GIVEN
CommonSetup: rrk/rrk/{myBase:TS26_11;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP2GroundArena: [SOR_164:1:0 SOR_095:1:0]
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P1BASE:EPICUSED

---

# Darksaber_CountsAsLeaderUnit_TakeAction
#// ASH_135 The Darksaber — the bearer counts as a friendly leader unit for Take Action's cost reduction
#// (TS26_71 costs 1 less per friendly leader unit). A deployed leader (1) plus the Darksaber-wearing SOR_046
#// (1) = 2 leader units, so Take Action (printed 3) costs 1 → playable on exactly 1 resource, dealing 3 to
#// the enemy LAW_124.
## GIVEN
CommonSetup: rrk/rrk/{myResources:1;myLeader:SOR_005:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_71
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Darksaber_ProvidesAspectForPiloting
#// ASH_135 The Darksaber — the aspect provision applies to Piloting costs. P1 (Cunning/Villainy, yyk) plays
#// JTL_094 Luke Skywalker via Piloting (cost 3, Command/Heroism) onto an N-1 Starfighter (LOF_192). The
#// Darksaber-wearing SOR_094 Bail Organa (Command/Heroism) provides both icons, waiving the +4 penalty, so
#// Luke pilots for exactly 3 → 0 resources left and attaches as a Pilot upgrade.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:JTL_094}
WithActivePlayer: 1
WithP1GroundArena: SOR_094:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP1SpaceArena: LOF_192:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_094
P1RESAVAILABLE:0

---

# Darksaber_RecomputesAspectsWhenMoved
#// ASH_135 The Darksaber — the provided aspects follow the Darksaber when it moves to a new host. It starts
#// on JTL_056 Hondo Ohnaka (Vigilance/Vigilance), letting P1 play LOF_055 Dume (Vig/Vig, cost 4) for 4 on a
#// base that covers neither Vigilance nor Cunning (rgw). SHD_064 Survivors' Gauntlet then moves the Darksaber
#// onto SOR_201 Bodhi Rook (Cunning/Cunning); now it provides Cunning, so LOF_204 Zuckuss (Cun/Cun, cost 5)
#// is playable for 5. Total 9 resources spent → 0 left, and the Darksaber sits on Bodhi.
## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_055,LOF_204}
WithActivePlayer: 1
WithP1GroundArena: [JTL_056:1:0 SOR_201:1:0]
WithP1GroundArenaUpgrade: 0:ASH_135
WithP1SpaceArena: SHD_064:1:0
## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1
- P2>Pass
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:1:CARDID:SOR_201
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:ASH_135
