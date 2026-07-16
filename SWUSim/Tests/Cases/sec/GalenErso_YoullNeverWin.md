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
