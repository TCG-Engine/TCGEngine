# LoseAbilities
#// JTL_244 There Is No Escape — Choose up to 3 units; they lose all abilities and can't gain abilities
#// this round. P1 targets the enemy SHD_147, which loses its Saboteur keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP2GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# MultipleUnitsMixed_LoseAbilities
#// JTL_244 There Is No Escape — "Choose UP TO 3 units" spans BOTH sides. P1 targets its OWN Sentinel unit
#// (SOR_035) AND the enemy Saboteur unit (SHD_147); both lose their keyword this round.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP1GroundArena: SOR_035:1:0
WithP2GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:CARDID:SHD_147
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# StatModifyingAbilityRemoved
#// JTL_244 There Is No Escape — "lose all abilities" also removes a unit's OWN constant STAT-modifying
#// passive, not just keywords/triggers. IG-88 (JTL_141, "+3/+0 while an enemy unit is damaged") is at power
#// 7 with the enemy SOR_046 damaged; after P1 targets IG-88 it loses the ability and drops to its printed 4.
#// (Regression guard for the ObjectCurrentPower/HP LostAbilities gate.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP1GroundArena: JTL_141:1:0
WithP2GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_141
P1GROUNDARENAUNIT:0:POWER:4

---

# DeployedLeaderLosesAbilities
#// JTL_244 There Is No Escape — a DEPLOYED LEADER unit is a legal target and loses ALL abilities for the
#// round. P1's deployed Chewbacca (SOR_003, Death Star Prison Warden's rival — deployed side innately has
#// Sentinel + Grit) is chosen; both printed keywords drop. Confirms the effect reaches friendly leader
#// units, not just ordinary units. (Leader-side abilities are unit-scoped here; see the aura note below.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_003:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_003
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# DeclineWithTargetsAvailable
#// JTL_244 There Is No Escape — "choose UP TO 3" is OPTIONAL: P1 may pick NO targets even when legal ones
#// exist. With P1's SOR_035 (Sentinel) and the enemy SHD_147 (Saboteur) both selectable, P1 declines
#// (AnswerDecision:PASS). Neither unit loses its keyword, and the event still resolves to the discard pile.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP1GroundArena: SOR_035:1:0
WithP2GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# AutoResolveNoUnits
#// JTL_244 There Is No Escape — with NO units in play there are zero legal targets, so the "up to 3" choice
#// auto-resolves to nothing ("play anyway"): no decision is raised, the event simply resolves to the
#// discard pile.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# AuraOntoOtherUnitsRemoved
#// JTL_244 There Is No Escape — "lose all abilities" also strips an AURA the target grants to OTHER units,
#// not just its own self-passives. Admiral Yularen (TWI_092, "Each other friendly Heroism unit gets +0/+1")
#// buffs friendly Heroism Zeb Orrelios (SOR_146) to 6 HP. After P1 blanks Yularen, the +0/+1 aura is gone
#// and Zeb drops back to his printed 5 HP. (Regression guard for the source-LostAbilities aura-grant check.)

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP1GroundArena: TWI_092:1:0
WithP1GroundArena: SOR_146:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_092
P1GROUNDARENAUNIT:1:CARDID:SOR_146
P1GROUNDARENAUNIT:1:HP:5

---

# DeployedLeaderAuraOntoOtherUnitsRemoved
#// JTL_244 also strips a DEPLOYED-LEADER's aura onto other units. Deployed Director Krennic (SOR_001,
#// "Each friendly damaged unit gets +1/+0") buffs a damaged Ruthless Raider (SOR_134, printed power 4) to
#// 5. Krennic is seated at ground index 1 (the pre-seated Raider takes index 0). Blanking Krennic removes
#// his aura → the Raider drops back to printed 4. (Regression guard: the aura's source-LostAbilities check
#// must read the DEPLOYED UNIT, not the leader-zone object, which never carries the blank.)

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SOR_001:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_244
WithP1GroundArena: SOR_134:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_134
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:CARDID:SOR_001

---

# RoundBlankExpiresNextRound
#// JTL_244 There Is No Escape — the "lose all abilities … for THIS ROUND" blank is a ROUND-duration effect
#// (SWU_DUR_ROUND). It must survive the action-phase→regroup phase-expiry (so it's still active for every
#// regroup-start trigger, e.g. a Contracted Hunter self-defeat) but then CLEAR at the end of the round so
#// the unit regains its abilities next round. P1 blanks the enemy SHD_147 (Saboteur), then both players pass
#// to end the action phase; the round completes through regroup and, in the next round, SHD_147 has its
#// Saboteur keyword back. Regression guard for the SWUExpireTurnEffects round-scope drop: the expiry closure
#// must capture $scope (else round effects never expire and the unit stays blanked forever), and the
#// SWU_DUR_ROUND expiry must run at the true end of the round, AFTER the regroup-start self-defeats/triggers.
#// (Decks are seeded so the two regroup draws don't add CR 6.1 empty-deck base damage — irrelevant here but
#// keeps the pass-into-regroup flow clean.)

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;theirBase:SOR_021}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithActivePlayer: 1
WithP1Hand: JTL_244
WithP1Resources: 6
WithP2GroundArena: SHD_147:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_147
P2GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
