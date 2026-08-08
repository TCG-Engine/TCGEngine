# DeployedOnAttack
#// LOF_005 Morgan Elsbeth (deployed) — On Attack: the next unit you play this phase costs 1 less if it shares
#// a keyword with a friendly unit. She attacks the base (arming the discount); P1 then plays LOF_132 (Raid),
#// which shares Raid with the friendly LOF_131 — so it costs 3+2−1 = 4 instead of 5.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_005;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6
WithP1SpaceArena: LOF_131:1:0
WithP1Hand: LOF_132

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_132
P1RESAVAILABLE:2

---

# SharedKeywordPlay
#// LOF_005 Morgan Elsbeth — Action [Exhaust]: Choose a friendly unit that attacked this phase; play a unit
#// from your hand that shares a keyword with it, for 1 less. LOF_132 (Raid) attacks the base; then Morgan
#// plays LOF_131 (also Raid; cost 2 + 2 off-aspect − 1 discount = 3) from hand — affordable only with the
#// discount.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_005;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1Hand: LOF_131
WithP1Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:0


---

# DeployedOnAttack_Smuggle_GetsDiscount
#// LOF_005 Morgan Elsbeth (deployed) — the "next unit costs 1 less if it shares a keyword with a friendly
#// unit" discount also applies to a unit played via SMUGGLE (regression: the Smuggle payment path formerly
#// bypassed the discount). Morgan attacks to arm the charge; friendly SHD_111 (Smuggle) is in play, so
#// smuggling SHD_113 (Privateer Crew, Smuggle cost 6) — which shares the Smuggle keyword — costs 6-1 = 5.
#// With exactly 5 ready resources the play succeeds only because of the discount.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_005;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: SHD_111:1:0
WithP1Resources: 4:SOR_046:1,1:SHD_113:1

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>SmuggleResource:4

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_113

---

# Front_NoUnitAttacked_NoEffect
#// LOF_005 Morgan (front) — the Action needs a unit that attacked THIS phase. Nothing has attacked, so using
#// the ability resolves to no effect (leader still exhausts, hand/board unchanged). Mirrors ref "does nothing
#// if no unit attacked this phase".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1Hand: LOF_131
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:5

---

# Front_NoSharedKeyword_NoPlay
#// LOF_005 Morgan (front) — the chosen attacker (SOR_059, no keywords) shares no keyword with any hand unit,
#// so nothing can be played. Leader exhausts; the Raid unit stays in hand. Intended: "does not allow playing a
#// unit if there are no Keywords shared with the chosen unit".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0
WithP1Hand: LOF_131
WithP1Resources: 5

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1

---

# Front_EventNotPlayable
#// LOF_005 Morgan (front) — the play must be a UNIT. The attacker (SHD_111, Smuggle) shares Smuggle with the
#// hand card SHD_252, but SHD_252 is a Smuggle EVENT, so it is not a valid play. Nothing is played; the event
#// stays in hand. Intended: "does not allow playing an event that shares a Keyword with the chosen unit".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SHD_111:1:0
WithP1Hand: SHD_252
WithP1Resources: 5

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1

---

# Front_AttackedCopyNotInPlay
#// LOF_005 Morgan (front) — attacked-this-phase is tracked per unit instance. One Strikeship (LOF_131, Raid)
#// attacks into SHD_152 and is defeated; the surviving copy never attacked, so there is NO valid "unit that
#// attacked this phase" and the ability does nothing (the Raid unit in hand stays). Intended: "does not work if
#// the copy of the unit that attacked is not in play".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: [LOF_131:1:0 LOF_131:1:0]
WithP2SpaceArena: SHD_152:1:0
WithP1Hand: LOF_228
WithP1Resources: 5

## WHEN
- P1>AttackSpaceArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1LEADER:EXHAUSTED
P1HANDCOUNT:1

---

# Front_DiscountToZero
#// LOF_005 Morgan (front) — the "1 resource less" can reduce the play to 0, so it works with no ready
#// resources. Strikeship (LOF_131, Raid) attacked; playing LOF_228 (Raid, cost 1) shares Raid → cost 1-1 = 0.
#// Intended: "the leader side ability can still be used if the discounted cost is 0".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_131:1:0
WithP1Hand: LOF_228
WithP1Resources: 0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:LOF_228
P1RESAVAILABLE:0

---

# Deployed_NonSharingUnit_NoDiscount
#// LOF_005 Morgan (deployed) — the On-Attack charge only discounts a unit that SHARES a keyword with a
#// friendly unit. Morgan attacks (arms the charge); playing SOR_059 (no keywords, cost 1) shares nothing, so
#// it costs the full 1 (5 -> 4). Intended: "the effect does not give a discount if the played unit does not share
#// a Keyword with an in-play unit".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_059
WithP1Resources: 5

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_059
P1RESAVAILABLE:4

---

# Deployed_Event_NoDiscount
#// LOF_005 Morgan (deployed) — the charge only discounts UNITS. Morgan attacks (arms it); friendly SHD_111
#// (Smuggle) is in play and the played card SHD_252 (Smuggle event, cost 1 + 2 off-aspect = 3) shares Smuggle,
#// yet it is an EVENT so no discount applies (5 -> 2, not 3). Intended: "the effect does not give a discount for
#// event cards".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SHD_111:1:0
WithP1Hand: SHD_252
WithP1Resources: 5

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:2

---

# Deployed_EnemyOnlyKeyword_NoDiscount
#// LOF_005 Morgan (deployed) — the shared keyword must be with a FRIENDLY unit. Morgan attacks (arms it); the
#// only Saboteur unit is the enemy SOR_143, so playing SOR_133 (Saboteur, Aggression/Villainy, cost 5, all
#// aspects covered) gets no discount (6 -> 1, not 2). Intended: "the discount does not apply to units that only
#// share a Keyword with an enemy unit".
#// NOTE: no myBase override here — the code-derived RED base supplies the Aggression aspect so SOR_133 is
#// on-aspect (an SOR_021 override would force Vigilance and make SOR_133 cost 7).

## GIVEN
CommonSetup: rgk/bbk/{myLeader:LOF_005:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_133
WithP2GroundArena: SOR_143:1:0
WithP1Resources: 6

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_133
P1RESAVAILABLE:1

---

# Deployed_DiscountToZero
#// LOF_005 Morgan (deployed) — the charge can reduce a shared-keyword unit to 0. Morgan attacks (arms it);
#// friendly LOF_131 (Raid) is in play, so playing LOF_228 (Raid, cost 1) costs 1-1 = 0 with no ready
#// resources. Intended: "the leader unit side ability can still be used if the discounted cost is 0".

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_131:1:0
WithP1Hand: LOF_228
WithP1Resources: 0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:LOF_228
P1RESAVAILABLE:0

---

# Deployed_GainedKeyword_GivesDiscount
#// LOF_005 Morgan (deployed) — the shared-keyword test must read a unit's LIVE keywords, so a keyword a
#// friendly unit has GAINED counts just like a printed one. JTL_137 Vonreg's TIE Interceptor is 3/4 and
#// gains Overwhelm only "while this unit has 4 or more power"; an Experience token (+1/+1) puts it at 4/5,
#// so Overwhelm is ON. Morgan attacks to arm the charge, then SHD_235 Ruthless Assassin (keyword-only
#// Overwhelm, cost 2) is played for 1 — 5 ready resources become 4.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_235
WithP1Resources: 5
WithP1SpaceArena: JTL_137:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_137
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:CARDID:SHD_235
P1RESAVAILABLE:4

---

# Deployed_GainedKeywordTurnedOff_NoDiscount
#// LOF_005 Morgan (deployed) — the mirror NEGATIVE, and the one that proves the check is live rather
#// than a snapshot. Identical to the section above except JTL_137 carries no Experience token, so it sits
#// at its printed 3/4 and its conditional Overwhelm is OFF. Nothing in play shares a keyword with
#// SHD_235, so it costs the full 2 — 5 ready resources become 3, not 4.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_235
WithP1Resources: 5
WithP1SpaceArena: JTL_137:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_137
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:NOTKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:CARDID:SHD_235
P1RESAVAILABLE:3

---

# Deployed_DiscountExpiresNextPhase
#// LOF_005 Morgan (deployed) — the On-Attack charge discounts "the next unit you play THIS PHASE", so it
#// must not survive into the following phase. Morgan attacks to arm it, both players pass to regroup and
#// on into the next action phase WITHOUT the charge being spent, then SHD_235 Ruthless Assassin is played.
#// The keyword-share condition still holds (JTL_137 is 4/5 with its Experience token, so Overwhelm is
#// still on), which isolates EXPIRY as the only reason the discount is gone: 5 refreshed resources go to
#// 3, not 4. A charge that leaked across the phase boundary would read 4 here.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_005:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_235
WithP1Resources: 5
WithP1SpaceArena: JTL_137:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:CARDID:SHD_235
P1RESAVAILABLE:3

---

# Deployed_PlayedAsPilotUpgrade_NoDiscount
#// LOF_005 Morgan (deployed) — the charge discounts the next UNIT you play. A Piloting card played AS A
#// PILOT enters as an UPGRADE, not a unit, so it must not consume or receive the discount even though it
#// shares a keyword with an in-play unit.
#// TWI_104 Obedient Vanguard (keyword-only Raid 1) is in play, so JTL_211 Independent Smuggler (Raid,
#// piloting cost 1) does share a keyword. Morgan attacks to arm the charge, then JTL_211 is played as a
#// Pilot onto the friendly Vehicle JTL_137. Its piloting cost 1 must be paid IN FULL: 5 ready resources
#// go to 4, and it lands as an upgrade on the Vehicle rather than as a ground unit.
#// A Cunning base covers JTL_211's aspect so the arithmetic is the bare piloting cost — with the
#// discount wrongly applied this would read 5 instead of 4.

## GIVEN
CommonSetup: ygk/bbk/{myLeader:LOF_005:1:1:1;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_211
WithP1Resources: 5
WithP1GroundArena: TWI_104:1:0
WithP1SpaceArena: JTL_137:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_137
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_211
P1RESAVAILABLE:4
