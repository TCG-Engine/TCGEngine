# NamedBase_EpicActionDenied
#// SEC_046 Galen Erso — naming an opponent's BASE denies its Epic Action. P1 names "Security Complex"
#// (SOR_019, "Epic Action: Give a Shield token to a non-leader unit"). When P2 tries to use the base's
#// Epic Action, nothing happens — no Shield is granted, no decision appears, and the epic is not consumed.

## GIVEN
CommonSetup: bbw/brk/{
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Security Complex
- P2>UseBaseAbility

## EXPECT
P2BASE:EPICAVAILABLE
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2NODECISION

---

# NamedBounty_NoReward
#// SEC_046 Galen Erso — naming a unit denies its Bounty. SHD_027 Hylobon Enforcer's "Bounty - Draw a
#// card" should not be collectible. P1 names "Hylobon Enforcer", then defeats P2's SHD_027 (1/4) with an
#// 8/8 (SOR_039). No bounty is offered — P1 draws nothing (deck stays full) and gets no bounty decision.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_039:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SHD_027:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Hylobon Enforcer
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:2
P1NODECISION

---

# NamedCombatHit_DoesNotFire
#// SEC_046 Galen Erso — naming a unit denies its "When this unit deals combat damage to a base" trigger.
#// P1 names "Chopper" (SEC_147, "...deals combat damage to a base: Each player discards a card"). P2's
#// Chopper (4/1) attacks P1's base for 4, but the discard trigger does NOT fire — both players keep their
#// hand cards.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1Hand: SOR_095
WithP2GroundArena: SEC_147:1:0
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Chopper
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P1HANDCOUNT:1
P2HANDCOUNT:1

---

# NamedCostModifier_NoDiscount
#// SEC_046 Galen Erso — naming a card denies its own cost-reduction ability. SOR_248 Volunteer Soldier
#// (cost 3) normally costs 1 less while you control a Trooper. P2 controls a Trooper (SEC_080), but after
#// P1 names "Volunteer Soldier" the discount is gone, so P2 pays the full 3 (from 5 ready → 2 left, not 3).

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SEC_080:1:0
WithP2Resources: 5
WithP2Hand: SOR_248

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Volunteer Soldier
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2RESAVAILABLE:2

---

# NamedEnemyUnit_LosesAbilities
#// SEC_046 Galen Erso (Unit, 3/5, cost 4, Vigilance/Heroism, Imperial, Plot)
#//   "When Played: Name a card. While this unit is in play, each non-leader card an opponent owns with
#//    that name, including those not in play, loses all abilities (and can't gain abilities)."
#// P1 plays Galen and names "Cloud City Wing Guard" (SOR_063, an enemy Sentinel unit). While Galen is in
#// play, P2's SOR_063 loses all abilities, so it no longer has Sentinel. A second enemy Sentinel unit
#// (SOR_037, a DIFFERENT name) is NOT named, so it KEEPS Sentinel — proving the name-match gate.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Cloud City Wing Guard

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_046
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# NamedEvent_DoesNothing
#// SEC_046 Galen Erso — naming an EVENT denies its ability, so playing it does nothing (it still pays
#// its cost and goes to discard). P1 names "I Am the Senate" (SEC_092, "Create 5 Spy tokens"). P2 plays
#// it, but no Spy tokens are created — P2's board stays empty and the event lands in P2's discard.

## GIVEN
CommonSetup: bbw/ggk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 10
WithP2Hand: SEC_092

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:I Am the Senate
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# NamedExperience_StatsNotDenied
#// SEC_046 Galen Erso — naming "Experience" does NOT remove the stat bonus. An Experience token's +1/+1
#// is a printed STAT, not an ability, so "loses all abilities" leaves it untouched. P2's SOR_095 (3/3)
#// carries an Experience token (→ 4/4); after Galen names "Experience" it is still 4/4.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:4

---

# NamedForceBase_NoForceGain
#// SEC_046 Galen Erso — naming an opponent's Force base denies its "When a friendly Force unit attacks:
#// The Force is with you" ability. P2's base is Starlight Temple (LOF_024, a Force base). P1 plays Galen
#// and names "Starlight Temple". When P2 attacks with a Force unit, P2 does NOT gain the Force.

## GIVEN
CommonSetup: bbw/grk/{
  theirBase:LOF_024
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: LOF_231:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Starlight Temple
- P2>AttackGroundArena:0:BASE

## EXPECT
P2NOFORCE

---

# NamedPiloting_UnitOnly
#// SEC_046 Galen Erso — naming a Piloting card denies the keyword, so it can only be played as a unit
#// (no Unit/Pilot choice). P2 controls a Vehicle (JTL_069), so normally playing JTL_034 (Interceptor Ace,
#// Piloting) would prompt Unit-or-Pilot. P1 names "Interceptor Ace"; P2 plays it and it enters as a ground
#// unit directly, with no Unit/Pilot decision.

## GIVEN
CommonSetup: bbw/bbk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 8
WithP2Hand: JTL_034
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Interceptor Ace
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:JTL_034
P2NODECISION

---

# NamedPlot_CannotPlayViaPlot
#// SEC_046 Galen Erso — naming a Plot card denies its Plot keyword, so the opponent can't play it from
#// resources on a leader deploy. P2 holds SEC_111 Jar Jar Binks (Plot) as a resource. P1 names "Jar Jar
#// Binks". When P2 deploys its leader, the Plot window does NOT open (no offer appears) — so P2 ends with
#// only the deployed leader on the board and no pending decision.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 1:SEC_111:1,7:SOR_095:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Jar Jar Binks
- P2>DeployLeader

## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P2NODECISION

---

# NamedShield_DamagePreventionDenied
#// SEC_046 Galen Erso — naming "Shield" denies the Shield token's damage-prevention ability.
#// P1 plays Galen and names "Shield". P1 then attacks P2's shielded SOR_063 (2/4) with SOR_095 (3 power).
#// Normally the shield would absorb the hit; with the Shield token's ability denied, SOR_063 takes the
#// full 3 damage AND the shield token stays attached (it wasn't consumed — it just did nothing).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# NamedShielded_NoEntryShield
#// SEC_046 Galen Erso — naming a Shielded card denies the keyword, so it gets no Shield token on entry.
#// P1 names "Crafty Smuggler" (SOR_207, Shielded — normally shields itself when played). P2 then plays
#// SOR_207; with Shielded denied it enters with no shield.

## GIVEN
CommonSetup: bbw/yyk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 8
WithP2Hand: SOR_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Crafty Smuggler
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_207
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# NamedSmuggle_CannotSmuggle
#// SEC_046 Galen Erso — naming a Smuggle card denies the keyword, so the opponent can't play it from
#// resources via Smuggle. P1 names "Vigilant Pursuit Craft" (SHD_065, Smuggle). P2 tries to Smuggle it
#// from resources, but the play is blocked — the card stays put and never enters the space arena.

## GIVEN
CommonSetup: bbw/bbk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 1:SHD_065:1,8:SOR_095:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Vigilant Pursuit Craft
- P2>SmuggleResource:0

## EXPECT
P2SPACEARENACOUNT:0

---

# NamedSpy_RaidDenied
#// SEC_046 Galen Erso — naming "Spy" denies the Spy token's Raid 2. A Spy token (SEC_T01) is 0 power with
#// Raid 2, so attacking a base normally deals 2; with its Raid ability denied it deals 0. P1 plays Galen
#// and names "Spy"; P2's Spy then attacks P1's base for 0.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SEC_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Spy
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0

---

# NamedUpgrade_GrantedOnAttackDenied
#// SEC_046 Galen Erso — naming an UPGRADE denies the On Attack ability it grants its host. SOR_137 Fallen
#// Lightsaber grants "On Attack: if the attached unit is a Force unit, deal 1 to each enemy ground unit".
#// P2's Force unit (LOF_231) wears it. P1 names "Fallen Lightsaber"; when LOF_231 attacks, the granted On
#// Attack does NOT fire — P1's ground unit (SOR_046) takes no damage.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_231:1:0
WithP2GroundArenaUpgrade: 0:SOR_137

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Fallen Lightsaber
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:7

---

# NamedWhenDefeated_DoesNotFire
#// SEC_046 Galen Erso — naming a card denies its When Defeated ability. SEC_132 Imperial Occupier's
#// "When Defeated: Create a Spy token" should not fire. P1 names "Imperial Occupier"; P2's SEC_132 (2/2)
#// attacks an 8/8 (SOR_039) and dies, but no Spy is created — so P2's board is empty afterward.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_039:1:0
WithP2GroundArena: SEC_132:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Imperial Occupier
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0

---

# NamedWhenPlayed_DoesNotFire
#// SEC_046 Galen Erso — naming a card denies its When Played ability. SEC_097 Beloved Orator's "When
#// Played: Create a Spy token" should not fire when P2 plays it after Galen named "Beloved Orator". So
#// P2 ends with only Beloved Orator in play (no Spy token).

## GIVEN
CommonSetup: bbw/ggw
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 6
WithP2Hand: SEC_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Beloved Orator
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_097

---

# FriendlyUnit_KeepsKeyword
#// SEC_046 Galen Erso — the name-a-card blank only touches cards an OPPONENT owns. When Galen's own
#// controller owns the named card, it is untouched. P1 owns SOR_063 Cloud City Wing Guard (Sentinel) and
#// names "Cloud City Wing Guard" with its own Galen; the friendly SOR_063 KEEPS Sentinel.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Cloud City Wing Guard

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:CARDID:SEC_046

---

# FriendlyWhenPlayed_StillFires
#// SEC_046 Galen Erso — a friendly (Galen-owner) named card keeps its abilities. P1 names "Beloved Orator"
#// then plays its own SEC_097 Beloved Orator ("When Played: Create a Spy token"). Because P1 owns it, the
#// When Played still fires: a Spy token (SEC_T01) is created → Galen + Beloved Orator + Spy = 3 units.

## GIVEN
CommonSetup: bbw/ggw
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1Hand: SEC_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Beloved Orator
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:SEC_097
P1GROUNDARENAUNIT:2:CARDID:SEC_T01

---

# FriendlyWhenDefeated_StillFires
#// SEC_046 Galen Erso — a friendly named card keeps its When Defeated. P1 owns SEC_132 Imperial Occupier
#// ("When Defeated: Create a Spy token") and names "Imperial Occupier". P1's SEC_132 (2/2) attacks an 8/8
#// (SOR_039) and dies; because P1 owns it the When Defeated fires → a Spy token replaces it (Galen + Spy).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1GroundArena: SEC_132:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Imperial Occupier
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_046
P1GROUNDARENAUNIT:1:CARDID:SEC_T01

---

# FriendlyEvent_StillResolves
#// SEC_046 Galen Erso — a friendly named EVENT still resolves. P1 owns SEC_092 I Am the Senate ("Create 5
#// Spy tokens") and names "I Am the Senate". Because P1 owns it, playing it creates all 5 Spy tokens →
#// Galen + 5 Spies = 6 units, and the event goes to P1's discard.

## GIVEN
CommonSetup: bbw/ggk
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: SEC_046
WithP1Hand: SEC_092

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:I Am the Senate
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:6
P1DISCARDCOUNT:1

---

# FriendlyBase_KeepsEpicAction
#// SEC_046 Galen Erso — naming a FRIENDLY base does not deny its Epic Action. P1's base is SOR_019 Security
#// Complex ("Epic Action: Give a Shield token to a non-leader unit"). P1 names "Security Complex"; the only
#// non-leader unit in play is Galen himself, so the Epic auto-targets him and he gains a Shield token.

## GIVEN
CommonSetup: bbw/brk/{
  myBase:SOR_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Security Complex
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1BASE:EPICUSED

---

# FriendlyForceBase_KeepsAbility
#// SEC_046 Galen Erso — naming a FRIENDLY Force base does not deny its ability. P1's base is LOF_024
#// Starlight Temple ("When a friendly Force unit attacks: The Force is with you"). P1 names "Starlight
#// Temple"; when P1's Force unit (LOF_231) attacks, P1 still gains the Force.

## GIVEN
CommonSetup: bbw/grk/{
  myBase:LOF_024
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1GroundArena: LOF_231:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Starlight Temple
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE

---

# FriendlySpy_RaidStillDeals
#// SEC_046 Galen Erso — naming "Spy" does not deny a FRIENDLY Spy token's Raid 2. P1 owns a Spy token
#// (SEC_T01, 0 power, Raid 2) and names "Spy"; when it attacks P1's opponent's base it still deals 2.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1GroundArena: SEC_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Spy
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# FriendlyShield_PreventsAsNormal
#// SEC_046 Galen Erso — naming "Shield" does not deny a FRIENDLY Shield token's prevention. P1's SOR_063
#// (2/4) carries a Shield token and P1 names "Shield". When P2 attacks it with SOR_095 (3 power), the
#// friendly Shield still prevents all the damage and is consumed → SOR_063 takes 0, shield gone.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# FriendlyShielded_GetsEntryShield
#// SEC_046 Galen Erso — naming a FRIENDLY Shielded card does not deny the keyword. P1 names "Crafty
#// Smuggler" then plays its own SOR_207 (Shielded); because P1 owns it, it enters with a Shield token.

## GIVEN
CommonSetup: bbw/yyk
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1Hand: SOR_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Crafty Smuggler
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_207
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# FriendlyUpgrade_GrantsOnAttack
#// SEC_046 Galen Erso — naming a FRIENDLY upgrade does not deny the ability it grants. P1's Force unit
#// (LOF_231) wears SOR_137 Fallen Lightsaber ("On Attack: if the attached unit is a Force unit, deal 1 to
#// each ground unit the defending player controls"). P1 names "Fallen Lightsaber"; when LOF_231 attacks
#// P2's base, the granted On Attack STILL fires → P2's ground unit (SOR_046) takes 1.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1GroundArena: LOF_231:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Fallen Lightsaber
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# FriendlyCombatHit_StillFires
#// SEC_046 Galen Erso — naming a FRIENDLY unit does not deny its "deals combat damage to a base" trigger.
#// P1 owns SEC_147 Chopper (4/1, "...deals combat damage to a base: Each player discards a card") and names
#// "Chopper". Chopper attacks P2's base for 4; because P1 owns it the discard trigger fires — both players
#// discard their one held card (each hand → 0).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1Hand: SOR_095
WithP1GroundArena: SEC_147:1:0
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Chopper
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:0
P2HANDCOUNT:0

---

# FriendlyPiloting_UnitOrPilotChoice
#// SEC_046 Galen Erso — naming a FRIENDLY Piloting card does not deny the keyword. P1 owns a Vehicle
#// (JTL_069) and JTL_034 Interceptor Ace (Piloting) and names "Interceptor Ace". Because P1 owns it, the
#// Piloting keyword survives: playing JTL_034 offers the Unit/Pilot choice, and choosing Pilot attaches it
#// to the lone friendly Vehicle rather than entering as a separate unit.

## GIVEN
CommonSetup: bbw/bbk
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1Hand: JTL_034
WithP1SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Interceptor Ace
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENACOUNT:1

---

# NamedCredit_EnemyCreditCantReducePayment
#// SEC_046 Galen Erso — naming "Credit" disables an opponent's Credit tokens (a non-leader card they own
#// loses all abilities), so they can't be defeated to pay 1 less. P2 plays Galen and names "Credit"; on
#// P1's turn P1 plays SOR_095 (cost 2) with a Credit token available — but no pay-1-less offer appears and
#// P1 pays the full 2 resources; the Credit token stays. (Mirror of LAW_117 Conveyex Security Captain.)

## GIVEN
CommonSetup: ggw/bbw/{
  theirResources:4
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1Hand: SOR_095
WithP1Resources: 2
WithP1Credits: 1

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Credit
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# NamedEnemyLeader_Undeployed_NotBlanked
#// SEC_046 Galen Erso — the blanking is scoped to "each NON-LEADER card an opponent owns", so naming an
#// enemy LEADER does nothing at all. P1 names "Obi-Wan Kenobi" (P2's leader TWI_003, front "Action
#// [Exhaust]: Heal 1 damage from a unit"). P2's front Action still works: their damaged 3/7 heals from 3
#// to 2. This is the load-bearing scope gate — without it Galen would blank leaders too.
## GIVEN
CommonSetup: bbw/brk/{theirLeader:TWI_003}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_046:1:3
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Obi-Wan Kenobi
- P2>UseLeaderAbility
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2LEADER:EXHAUSTED

---

# NamedEnemyLeader_Deployed_NotBlanked
#// SEC_046 Galen Erso — a DEPLOYED enemy leader is still a leader card, so naming it changes nothing.
#// P1 names "Nute Gunray" (P2's deployed leader TWI_002, "On Attack: Create a Battle Droid token").
#// P2's deployed leader attacks P1's base and the Battle Droid is still created.
## GIVEN
CommonSetup: bbw/brk/{theirLeader:TWI_002;theirLeaderDeployed:true}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Nute Gunray
- P2>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:2
P1BASEDMG:2

---

# NamedEnemyLeader_AsPilot_NotBlanked
#// SEC_046 Galen Erso — a leader deployed AS A PILOT upgrade is still a leader card and is not blanked.
#// P1 names "Asajj Ventress" (P2's leader JTL_001, deployed as a Pilot: the attached unit "is a leader
#// unit. It gains Grit and: On Attack: You may deal 1 damage to a friendly unit. If you do, deal 1 damage
#// to an enemy unit in the same arena."). P2's piloted Vehicle attacks and the granted On Attack still
#// fires — 1 damage onto P2's own host and 1 onto P1's ground unit.
## GIVEN
CommonSetup: bbw/brk/{theirLeader:JTL_001;theirLeaderDeployedPilot:true}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_183:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Asajj Ventress
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_183
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# NamedUnit_OwnedByOpponent_StillBlankedWhenP1TakesControl
#// SEC_046 Galen Erso — the gate is OWNERSHIP, not control: "each non-leader card an opponent OWNS".
#// A P2-OWNED SHD_027 Hylobon Enforcer (Grit) sits under P1's CONTROL. It is still a card Galen's
#// opponent owns, so naming it blanks it and it loses Grit — control moving to Galen's own side does
#// not rescue it.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArenaControlled: SHD_027:2
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Hylobon Enforcer
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_027
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# NamedUnit_OwnedByGalensOwner_NotBlankedEvenWhenOpponentControlsIt
#// SEC_046 Galen Erso — the mirror: a card GALEN'S OWNER owns is never blanked, even while the OPPONENT
#// controls it. P1 names "Hylobon Enforcer" and P2 controls a P1-OWNED Hylobon Enforcer. Because P1 owns
#// it, it is not "a card an opponent owns" and keeps Grit.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArenaControlled: SHD_027:1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Hylobon Enforcer
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_027
P2GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# FriendlyCredit_StillReducesPayment_NotOverBlanked
#// SEC_046 Galen Erso naming "Credit" — the blanking hits only cards an OPPONENT owns, so GALEN'S OWN
#// side's Credit tokens keep working. The over-blanking direction, and the companion to
#// NamedCredit_EnemyCreditCantReducePayment. P2 plays Galen (declining to spend their Credit on him),
#// names "Credit", then plays SOR_095 — and their own Credit is STILL offered and spent.
#// ⚠ Holding Credits inserts the alt-payment offer BEFORE the card's own decision, so every play by a
#// credit-holding player needs that answer first or it eats the naming answer.
## GIVEN
CommonSetup: ggw/bbw/{theirResources:12}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP2Hand: SOR_095
WithP2Credits: 1
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:-
- P2>AnswerDecision:Credit
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:myResources-12
## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2CREDITCOUNT:0
P2RESAVAILABLE:5

---

# NamedEnemyUpgrade_KeepsStatBonus
#// SEC_046 Galen Erso — blanking removes ABILITIES, not printed STAT modifiers. A named enemy upgrade
#// stops granting its ability (covered by NamedUpgrade_GrantedOnAttackDenied) but its +3/+3 stays.
#// P1 names "Jedi Lightsaber" (SOR_054) on P2's Plo Koon: he must remain 9/11, not fall back to 6/8.
#// This is the over-blanking direction — a "loses all abilities" implementation that strips stat bonuses
#// too would pass every ability-denial section already in this file.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: LOF_050:1:0
WithP2GroundArenaUpgrade: 0:SOR_054
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Jedi Lightsaber
## EXPECT
P2GROUNDARENAUNIT:0:POWER:9
P2GROUNDARENAUNIT:0:HP:11
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NamedEnemyPilotUpgrade_KeepsStatIncrease_LosesGrantedKeyword
#// SEC_046 Galen Erso — the same split on a PILOT upgrade, which is the sharper case because a pilot
#// carries BOTH a stat increase and a granted keyword. P2's Vehicle hosts Hera Syndulla (JTL_045) as a
#// Pilot: 5/5 with a granted Restore. P1 names "Hera Syndulla" — the host KEEPS 5/5 (stat increase is not
#// an ability) but LOSES the granted Restore.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2SpaceArena: SOR_231:1:0
WithP2SpaceArenaUpgrade: 0:JTL_045
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Hera Syndulla
## EXPECT
P2SPACEARENAUNIT:0:POWER:5
P2SPACEARENAUNIT:0:HP:5
P2SPACEARENAUNIT:0:NOTKEYWORD:Restore

---

# NamedEnemyPilotUpgrade_LosesGrantedRaid
#// SEC_046 Galen Erso — the Raid half of the same blanked-upgrade rule (the fix touched both value
#// keywords, so both need a guard). P2's Vehicle hosts Independent Smuggler (JTL_211) as a Pilot,
#// "Attached unit gains Raid 1". P1 names "Independent Smuggler" and the host loses Raid while keeping
#// the pilot's stat increase.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2SpaceArena: SOR_231:1:0
WithP2SpaceArenaUpgrade: 0:JTL_211
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Independent Smuggler
## EXPECT
P2SPACEARENAUNIT:0:NOTKEYWORD:Raid
P2SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# NamedEnemyPilotUpgrade_LosesGrantedOverwhelm_BooleanGrant
#// SEC_046 Galen Erso — the BOOLEAN keyword grants from a pilot go through a different code path than the
#// value keywords (a `_SWUUnitHasUpgrade` presence check rather than an upgrade loop), so they need their
#// own guard. P2's TIE Advanced (SOR_231, a Fighter) hosts Biggs Darklighter (JTL_150) as a Pilot, "If
#// attached unit is a Fighter, it gains Overwhelm". P1 names "Biggs Darklighter" → the blanked pilot
#// grants nothing and the host loses Overwhelm.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2SpaceArena: SOR_231:1:0
WithP2SpaceArenaUpgrade: 0:JTL_150
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Biggs Darklighter
## EXPECT
P2SPACEARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# NamedEnemyUnit_CannotGAINKeywordFromAnUnnamedUpgrade
#// SEC_046 Galen Erso — the second half of the clause: a named card "loses all abilities AND CAN'T GAIN
#// abilities". Here the UPGRADE is untouched and the HOST is named. P2's TIE Advanced (SOR_231) hosts
#// Independent Smuggler (JTL_211) as a Pilot, "Attached unit gains Raid 1". P1 names "TIE Advanced" — the
#// host is blanked, so it cannot gain Raid even though the granting pilot is perfectly fine.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2SpaceArena: SOR_231:1:0
WithP2SpaceArenaUpgrade: 0:JTL_211
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:TIE Advanced
## EXPECT
P2SPACEARENAUNIT:0:NOTKEYWORD:Raid

---

# NamedShield_StolenByOpponent_BecomesBlanked
#// SEC_046 Galen Erso + CR 6 token ownership — a TOKEN has no printed owner; whoever controls it owns it.
#// So when the OPPONENT takes control of a shielded unit, the Shield token becomes a card GALEN'S OPPONENT
#// owns, and naming "Shield" now blanks it. P1 names "Shield" while their own SOR_063 (2/4) carries one
#// (harmless — see FriendlyShield_PreventsAsNormal). P2 then plays Change of Heart (SOR_224) to take
#// control of it. P1 attacks the now-enemy unit with SOR_095 (3 power): the stolen shield must NOT prevent,
#// so the unit takes the full 3 and the token is still attached (it did nothing rather than being spent).
## GIVEN
CommonSetup: bbw/yyk/{theirResources:8}
WithActivePlayer: 1
WithP1Resources: 12
WithP1Hand: SEC_046
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
WithP2Hand: SOR_224
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# NamedEnemyUnit_CannotGainKeyword_UpgradeAttachedAFTERNaming
#// SEC_046 Galen Erso — "loses all abilities (AND CAN'T GAIN ABILITIES)". The existing
#// NamedEnemyUnit_CannotGAINKeywordFromAnUnnamedUpgrade seats the granting pilot BEFORE Galen, so it
#// really proves "loses gained". This is the other half, temporally: P1 names "TIE Advanced" FIRST, and
#// only then does P2 pilot Independent Smuggler (JTL_211, "Attached unit gains Raid 1") onto it. The
#// blanked host still cannot gain Raid.
## GIVEN
CommonSetup: bbw/rrk/{theirResources:6}
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Hand: JTL_211
WithP2SpaceArena: SOR_231:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:TIE Advanced
- P2>PlayHand:0
- P2>AnswerDecision:Pilot
## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:NOTKEYWORD:Raid

---

# FriendlyNamedUnit_StillGainsKeyword
#// SEC_046 Galen Erso — over-blanking negative for the "can't gain abilities" clause. Naming a card
#// GALEN'S OWN SIDE owns does nothing, so P1's own TIE Advanced still gains Raid from its pilot even
#// though P1 named "TIE Advanced".
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1SpaceArena: SOR_231:1:0
WithP1SpaceArenaUpgrade: 0:JTL_211
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:TIE Advanced
## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Raid

---

# FriendlyUpgrade_KeepsStatBonus
#// SEC_046 Galen Erso — the friendly mirror of NamedEnemyUpgrade_KeepsStatBonus. Naming a card P1 owns
#// changes nothing at all, so P1's own Plo Koon wearing Jedi Lightsaber (SOR_054, +3/+3) stays 9/11.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Jedi Lightsaber
## EXPECT
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:11



---

# NamedEnemyEvent_CannotSmuggle
#// SEC_046 Galen Erso — the EVENT side of Smuggle denial (the existing NamedSmuggle_CannotSmuggle uses a
#// UNIT). A blanked card loses Smuggle, so a named enemy EVENT can't be played from resources.
#// P1 names "Covert Strength" (SHD_075, an event with Smuggle [3 resources Vigilance]) held as a P2
#// resource. P2's Smuggle is blocked: their damaged Plo Koon is NOT healed and nothing hits their discard.
#// Load-bearing thanks to FriendlyEvent_CanStillSmuggle below, which proves the smuggle WORKS unnamed.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: LOF_050:1:4
WithP2Resources: 1:SHD_075:1,10:SOR_095:1
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Covert Strength
- P2>SmuggleResource:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:0

---

# FriendlyEvent_CanStillSmuggle
#// SEC_046 Galen Erso — POSITIVE CONTROL for the section above AND the over-blanking negative: naming a
#// card GALEN'S OWN SIDE owns leaves its Smuggle intact. P1 names "Covert Strength" while holding it as
#// their OWN resource, then Smuggles it — Plo Koon heals 4 → 2 and gains an Experience token.
#// (Galen is a second friendly unit by then, so the heal target prompts rather than auto-resolving.)
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_046
WithP1GroundArena: LOF_050:1:4
WithP1Resources: 1:SHD_075:1,10:SOR_095:1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Covert Strength
- P1>SmuggleResource:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1

---

# CreditorsClaim_GainedWhenDefeated_PositiveControl
#// SEC_046 Galen Erso — POSITIVE CONTROL for the section below. P2's Battlefield Marine wears Creditor's
#// Claim (SEC_039, "Attached unit gains: When Defeated: You may defeat a unit with 3 or less remaining
#// HP"). With NO Galen naming it, P1 Vanquishes the Marine and the GAINED When Defeated fires for P2, who
#// uses it to defeat P1's 1/2 (SOR_108).
#// ⚠ The trigger is queued on P2's queue (the defeated unit's CONTROLLER) and does NOT drain inside P1's
#// action — `P2>Drain` is required to surface it. Without that it looks exactly like "the ability never
#// fires", which is how this scenario was mis-diagnosed on the first attempt.
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: SOR_078
WithP1GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SEC_039
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# NamedHost_GainedWhenDefeated_DoesNotFire
#// SEC_046 Galen Erso — a named card "loses all abilities (and can't gain abilities)", which includes an
#// ability GAINED from an upgrade. Same board as the positive control above, but P1 first names
#// "Battlefield Marine". The blanked Marine's GAINED When Defeated does NOT fire even after draining P2's
#// queue, so P1's 1/2 survives. (Creditor's Claim itself is untouched — the HOST is what is blanked.)
## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: SEC_046
WithP1Hand: SOR_078
WithP1GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SEC_039
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_108
P2GROUNDARENACOUNT:0

---

# NamedCredit_EnemyCreditCreatedAFTERNaming_StillCantReducePayment
#// SEC_046 Galen Erso — the blanking covers cards "INCLUDING THOSE NOT IN PLAY", so it is not a snapshot
#// of the tokens that existed when Galen named. P1 names "Credit"; P2 THEN creates a brand-new Credit with
#// Unmarked Credits (LAW_244) and plays a unit — the fresh token is dead on arrival too, so no pay-1-less
#// offer appears and the Credit is still sitting there afterwards.
## GIVEN
CommonSetup: bbw/yyk/{theirResources:14}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Hand: LAW_244
WithP2Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Credit
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0
## EXPECT
P2CREDITCOUNT:1
P2GROUNDARENACOUNT:1
P2NODECISION

---

# FriendlyCreditCreatedAFTERNaming_StillReducesPayment
#// SEC_046 Galen Erso — the over-blanking negative for the same axis: a Credit GALEN'S OWN SIDE creates
#// after the naming is unaffected. P1 names "Credit", creates one with Unmarked Credits (LAW_244), then
#// plays SOR_095 — the pay-1-less offer DOES appear and the Credit is spent.
## GIVEN
CommonSetup: bbw/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: SEC_046
WithP1Hand: LAW_244
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Credit
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myResources-14
## EXPECT
P1CREDITCOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095

---

# GalenHimselfPlayedViaPlot_StillNamesACard
#// SEC_046 Galen Erso — he carries Plot, so he can come out of the resource row when a leader deploys,
#// and his When Played must still fire from that entry path. P1 deploys their leader, plays Galen from
#// resources, and names "Battlefield Marine": P2's SOR_095 is blanked, losing its Grit — proof the
#// naming actually took effect rather than the play merely landing him in the arena.
#// Plot replaces him from the top of the deck, so the resource row holds at 10.

## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SEC_046:1,9:SOR_046:1
WithP1Deck: [SOR_046 SOR_046]
WithP2GroundArena: SHD_027:1:3

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:Hylobon Enforcer

## EXPECT
P1LEADER:DEPLOYED
P1RESCOUNT:10
P1DECKCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_027
P2GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P2GROUNDARENAUNIT:0:POWER:1

---

# FriendlyShieldBecomesBlankedOnceTheHOSTISSTOLEN_CR6TokenOwnership
#// SEC_046 Galen Erso — "each non-leader card AN OPPONENT OWNS with that name" is re-evaluated live, and
#// a token upgrade's ownership follows its host's controller (CR 6). P1 names "Shield" while the only
#// Shield on the board is on P1's OWN unit, so it is unaffected. P2 then takes control of that unit with
#// SOR_122 Traitorous: the Shield token is re-owned to P2, which brings it inside Galen's naming — so
#// when P1 attacks, the Shield no longer prevents anything. SOR_095 (3/3) takes the full 8 from SOR_039
#// and dies with the (inert) Shield still attached.
#// Companion to NamedShield_DamagePreventionDenied, where the Shield was enemy-owned from the start.

## GIVEN
CommonSetup: bbw/ggk
WithActivePlayer: 1
WithP1Resources: 4
WithP2Resources: 6
WithP1Hand: SEC_046
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_039:1:0
WithP2Hand: SOR_122

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_039

---

# NamedEnemyUnit_LOSESAKeywordItAlreadyGainedFromAnUnnamedUpgrade
#// SEC_046 Galen Erso — naming the HOST blanks it, which strips abilities it had already GAINED from an
#// upgrade, even though that upgrade is not the named card and keeps working for anyone else. P2's
#// SOR_095 wears SOR_057 Protector ("attached unit gains Sentinel") BEFORE Galen resolves; naming
#// "Battlefield Marine" takes the Sentinel away.
#// Distinct from NamedEnemyUnit_CannotGAINKeywordFromAnUnnamedUpgrade, where the grant is attempted
#// AFTER the naming — this one proves an already-held grant is removed rather than merely blocked.

## GIVEN
CommonSetup: bbw/bbk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_057

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# NamedFRIENDLYUnit_KEEPSAKeywordGainedFromAnUpgrade
#// SEC_046 Galen Erso — the blanking only reaches cards AN OPPONENT OWNS, so naming a card P1 also owns
#// leaves P1's own copy untouched. P1's SOR_095 wears SOR_057 Protector and P1 names "Battlefield
#// Marine": the Sentinel survives. The friendly-side control for the section above.

## GIVEN
CommonSetup: bbw/bbk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_057

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# NamedEnemyEvent_CannotBePlayedViaPLOT
#// SEC_046 Galen Erso — Plot is an ability, so a named enemy card loses it and can no longer be played
#// out of the resource row. P2 holds SEC_235 The Wrong Ride (event, Plot, "exhaust 2 enemy resources")
#// as a resource; P1 names it, then P2 deploys their leader. The Plot window has nothing to offer: P2's
#// row and deck are untouched and — the discriminating part — P1 still has 2 READY resources. P1 holds
#// 6 and pays 4 for Galen, so if The Wrong Ride had resolved it would have exhausted exactly those 2.
#// Companion to NamedEnemyEvent_CannotSmuggle, the other alternate-play keyword.
#// The friendly-side section below doubles as the positive control that this fixture CAN Plot-play.

## GIVEN
CommonSetup: bbw/yyk
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: SEC_046
WithP2Resources: 1:SEC_235:1,9:SOR_046:1
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:The Wrong Ride
- P2>DeployLeader

## EXPECT
P2LEADER:DEPLOYED
P2RESCOUNT:10
P2DECKCOUNT:2
P1RESAVAILABLE:2
P2NODECISION

---

# NamedFRIENDLYEvent_CanStillBePlayedViaPLOT
#// SEC_046 Galen Erso — naming only reaches cards an OPPONENT owns, so P1's own Plot card keeps its
#// Plot. P1 names "The Wrong Ride" while holding a copy in P1's OWN resource row, then deploys: the
#// Plot window still offers it and P1 plays it, exhausting 2 of P2's resources. Plot replaces the spent
#// card from the deck, so P1's row holds at 10 and its deck drops 2 → 1.
#// This is also the positive control for the enemy-side section above.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithP1Resources: 1:SEC_235:1,11:SOR_046:1
WithP1Hand: SEC_046
WithP1Deck: [SOR_046 SOR_046]
WithP2Resources: 4:SOR_046:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:The Wrong Ride
- P2>Pass
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1RESCOUNT:12
P1DECKCOUNT:1
P2RESAVAILABLE:2

---

# NamedEnemyUPGRADE_CannotBeSmuggled
#// SEC_046 Galen Erso — the UPGRADE side of Smuggle denial (the existing sections cover a unit and an
#// event). A blanked card loses Smuggle, so a named enemy UPGRADE cannot be played from the resource
#// row. P1 names "Jetpack" (SHD_225, an upgrade with Smuggle whose When Played gives the attached unit
#// a Shield token) held as one of P2's resources. P2's Smuggle attempt does nothing: their unit gains
#// no upgrade and no Shield, and the resource row is unchanged.
#// The friendly control below proves this fixture CAN smuggle the same card when it is not named.

## GIVEN
CommonSetup: bbw/yyk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 1:SHD_225:1,10:SOR_046:1
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Jetpack
- P2>SmuggleResource:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2RESCOUNT:11

---

# NamedFRIENDLYUPGRADE_CanStillBeSmuggled
#// SEC_046 Galen Erso — the positive control: naming only reaches cards an OPPONENT owns, so P1's own
#// copy of "Jetpack" keeps Smuggle. P1 names it while holding it as one of P1's OWN resources, then
#// smuggles it onto P1's SOR_095 — the upgrade attaches and its When Played gives the unit a Shield.
#// Without this, the section above could pass simply because the fixture never smuggles anything.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_046
WithP1Resources: 1:SHD_225:1,12:SOR_046:1
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Jetpack
- P2>Pass
- P1>SmuggleResource:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# NamedEnemyUPGRADE_CannotBePlayedViaPLOT
#// SEC_046 Galen Erso — the UPGRADE side of Plot denial. P2 holds SEC_123 Unveiled Might (upgrade,
#// +2/+3, Plot) as a resource; P1 names it, then P2 deploys their leader. The Plot window offers
#// nothing: P2's unit gains no upgrade and stays at its printed 3/3, and P2's resource row and deck are
#// untouched. Companion to NamedEnemyUPGRADE_CannotBeSmuggled.

## GIVEN
CommonSetup: bbw/ggk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 1:SEC_123:1,9:SOR_046:1
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unveiled Might
- P2>DeployLeader

## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2RESCOUNT:10
P2DECKCOUNT:2

---

# NamedFRIENDLYUPGRADE_CanStillBePlayedViaPLOT
#// SEC_046 Galen Erso — the positive control: P1's own copy of "Unveiled Might" keeps Plot. P1 names it
#// while holding it in P1's OWN resource row, then deploys: the Plot window offers it, P1 attaches it to
#// SOR_095 (3/3 → 5/6) and Plot replaces it from the deck, so the row holds at 12 and the deck drops
#// 2 → 1. Without this the section above could pass simply because nothing was ever Plot-playable.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_046
WithP1Resources: 1:SEC_123:1,11:SOR_046:1
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unveiled Might
- P2>Pass
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESCOUNT:12
P1DECKCOUNT:1

---

# NamedFRIENDLYPilotUpgrade_KeepsBothTheStatIncreaseAndTheGrantedKeyword
#// SEC_046 Galen Erso — the friendly control for
#// NamedEnemyPilotUpgrade_KeepsStatIncrease_LosesGrantedKeyword. Naming only reaches cards an OPPONENT
#// owns, so P1 naming a Pilot P1 also owns changes nothing: P1's Vehicle hosting JTL_045 Hera Syndulla
#// keeps BOTH halves — the 5/5 stat increase AND the granted Restore.
#// Without this, the enemy-side section only shows that SOMETHING was removed, not that the removal is
#// owner-scoped.

## GIVEN
CommonSetup: bbw/brk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1SpaceArena: SOR_231:1:0
WithP1SpaceArenaUpgrade: 0:JTL_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Hera Syndulla

## EXPECT
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# NamedEnemyEvent_LosesItsALTERNATECOSTAndDoesNothing
#// SEC_046 Galen Erso — an event's alternate cost is part of its abilities, so a named enemy event
#// loses both that and its effect. P1 names "Bamboozle" (SOR_199: "you may discard a Cunning card
#// instead of paying this event's cost. Exhaust a unit and return each upgrade on it to its owner's
#// hand"). P2 plays it at P1's upgraded SOR_095: nothing happens — the unit stays READY and keeps its
#// upgrade — and P2 is never offered the discard-instead-of-paying route (SOR_210 stays in P2's hand).
#// The friendly control below proves this fixture CAN resolve Bamboozle by its alternate cost.
#// ⚠ Note: SWUSim currently lets the blanked event be played for ZERO resources (P2's 5 stay ready).
#// A card with no abilities should have no alternate cost either and so ought to cost its printed 2;
#// that is asserted loosely here and recorded in the worklist rather than pinned as correct.

## GIVEN
CommonSetup: bbw/yyw
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Resources: 5
WithP2Hand: SOR_199
WithP2Hand: SOR_210

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Bamboozle
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:READY
P2HANDCOUNT:1
P2DISCARDCOUNT:1

---

# NamedFRIENDLYEvent_KeepsItsALTERNATECOST
#// SEC_046 Galen Erso — the positive control: naming reaches only cards an OPPONENT owns, so P1's own
#// Bamboozle keeps its alternate cost. P1 names "Bamboozle", then plays its own copy by DISCARDING a
#// Cunning card instead of paying: P2's upgraded SOR_095 is exhausted and its upgrade returns to P2's
#// hand, and P1's resources are untouched (proving the alternate cost was the one used).

## GIVEN
CommonSetup: yyw/bbw
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 8
WithP1Hand: SEC_046
WithP1Hand: SOR_199
WithP1Hand: SOR_210
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Bamboozle
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:1
P1RESAVAILABLE:2
