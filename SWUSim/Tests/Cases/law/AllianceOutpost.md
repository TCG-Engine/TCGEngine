# EpicDefeatTokenCredit
#// LAW_019 Alliance Outpost (Base, Vigilance) — "Epic Action [defeat a friendly token]: Give an
#// Experience or Shield token to a unit, or create a Credit token." P1 has one TIE Fighter token
#// (JTL_T01); the epic defeats it (cost) and P1 chooses the Credit mode → 1 Credit created.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_T01:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1

---

# EpicDefeatTokenGiveExperience
#// LAW_019 Alliance Outpost — the Experience mode of the three-way choice. The TIE Fighter token
#// (JTL_T01) is defeated to pay the cost, then SOR_046 Consular Security Force (3/7) receives an
#// Experience token: one upgrade, and +1/+1 → 4/8. The stat check is what distinguishes Experience from
#// the Shield mode, which also lands exactly one upgrade-shaped token.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [JTL_T01:1:0 SOR_046:1:0]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Experience
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1CREDITCOUNT:0

---

# EpicDefeatTokenGiveShield
#// The Shield mode. Same cost (the TIE token is defeated), but SOR_046 gets a SHIELD rather than
#// Experience — so SHIELDCOUNT is 1 and its power stays at the printed 3, which is what separates this
#// from the Experience branch above.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [JTL_T01:1:0 SOR_046:1:0]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1CREDITCOUNT:0

---

# TokenRecipientListIncludesENEMYUnits
#// "Give an Experience or Shield token to A UNIT" — unqualified, so it is NOT friendly-only. The offer
#// must include the opponent's units too (giving an enemy unit a Shield is a real, if rare, play — and
#// handing one Experience is a genuine cost of taking the Credit-free mode).
#// Stops AT the target decision so the offer list itself is the assertion; the sections above cover
#// what actually happens once a target is picked.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [JTL_T01:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Experience

## EXPECT
P1DECISIONTOOLTIP:Give_the_token_to_a_unit
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# UnpayableCost_OnlyENEMYTokensAndANonTokenUpgrade_EpicSlotIsPRESERVED
#// The Epic's cost is "[defeat a friendly token]". With NO friendly token the cost is unpayable, so the
#// Action must be a clean no-op that PRESERVES the once-per-game Epic slot — the LAW_023 Great Pit
#// bug family, where the flag was set before the ability discovered it could not pay.
#// Rewritten (user-approved, 2026-09-17) when the cost was widened past token UNITS: every token kind
#// P1 could wrongly reach is present but NOT friendly — a Shield on P2's unit, P2's Credit token and
#// P2's Force token — and P1's own unit carries only a NON-token upgrade (Academy Training). A cost scan
#// that forgot the "friendly" or the "token" half finds something here and burns the slot.

## GIVEN
CommonSetup: bbw/bbw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2Credits: 1
WithP2Force: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1BASE:EPICAVAILABLE
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2CREDITCOUNT:1
P2HASFORCE

---

# ReportedBoard505707_ShieldAndCredit_BothOfferedAsTheCost
#// Game 505707 (Chewbacca / Alliance Outpost): P1's Secretive Sage (LOF_061) holds its Shielded token and
#// P1 has one Credit token. The Epic was never offered, because the cost only counted token UNITS.
#// A Shield token attached to a unit P1 controls is a friendly token (CR 3.50.f: a player controls the
#// token upgrades attached to units they control), and so is a Credit token. Both must be offered.
#// Two legal tokens, so the prompt cannot auto-resolve and the offer itself is observable.

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:LAW_013:false:false:false:0;
  myBase:LAW_019;
  theirLeader:JTL_012:true:false:false:0;
  theirBase:JTL_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1GroundArena: [LOF_061:0:0]
WithP1GroundArenaUpgrade: [0:SOR_T02]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1DECISIONTOOLTIP:Defeat_a_friendly_token_(cost)
P1SELECTABLEEXACT:myGroundArena-0.u0&myResources-2
P1BASE:EPICUSED

---

# ReportedBoard505707_DefeatTheShield_CreateACredit
#// The deck's actual line: defeat Secretive Sage's Shield to pay, then take the Credit mode. The Shield
#// is gone, the unit stays, and P1 ends with 2 Credit tokens (the one they had plus the new one).

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:LAW_013:false:false:false:0;
  myBase:LAW_019;
  theirLeader:JTL_012:true:false:false:0;
  theirBase:JTL_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1GroundArena: [LOF_061:0:0]
WithP1GroundArenaUpgrade: [0:SOR_T02]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_061
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1CREDITCOUNT:2
P1BASE:EPICUSED
P1NODECISION

---

# TokenUpgrades_OnlyThePickedOneIsDefeated_NonTokenUpgradeNotOffered
#// One friendly unit carries a Shield token, an Experience token and a NON-token upgrade (Academy
#// Training). Only the two tokens are legal costs; picking the Experience must leave the Shield and the
#// Academy Training in place. Discriminates "defeat the chosen token" from "defeat the first upgrade".

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: [0:SOR_120 0:SOR_T02 0:SOR_T01]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0.u1&myGroundArena-0.u2

---

# TokenUpgrades_DefeatTheExperience_ShieldAndNonTokenStay

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: [0:SOR_120 0:SOR_T02 0:SOR_T01]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0.u2
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1CREDITCOUNT:1
P1BASE:EPICUSED

---

# CreditTokenPaysTheCost
#// A Credit token is a friendly token (CR 3.13). Picking it defeats it, so after the Experience mode
#// P1 has no Credit left and the Shield on the unit is untouched.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_095:1
WithP1Credits: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myResources-2
- P1>AnswerDecision:Experience
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1CREDITCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1BASE:EPICUSED

---

# ForceTokenPaysTheCost_OfferedOnTheBase
#// The Force token is a friendly token (CR 3.11; user ruling 2026-09-17: include it). SWUSim stores it as
#// player state shown on the base, so it is offered as the player's own base. Picking it defeats the
#// Force token; the Shield on the unit is untouched.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0.u0&myBase-0

---

# ForceTokenPaysTheCost_DefeatsTheForce

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myBase-0
- P1>AnswerDecision:Credit

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1CREDITCOUNT:1
P1BASE:EPICUSED

---

# ShieldGivenByTheOPPONENT_IsStillFriendly_TheTurnPasses
#// The Shield on P1's unit was created by P2's SOR_019 Security Complex, but it is attached to a unit P1
#// controls, so P1 controls it (CR 3.50.f) and it pays the cost. The lone legal token auto-resolves.
#// No P1OnlyActions: the Epic is one action, so the turn must pass exactly once.

## GIVEN
CommonSetup: bbw/bbw/{
  myBase:LAW_019;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P2>UseBaseAbility
- P2>AnswerDecision:theirGroundArena-0
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
TURNPLAYER:2
NOEXTRAACTION

---

# EachToken_Credit_PaysTheCost
#// LAW_T01 Credit token: defeated to pay, then the Credit mode makes a new one — so the count is still 1, and EPICUSED is what separates it from an Epic that never resolved.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Shield_PaysTheCost
#// SOR_T02 Shield token upgrade on P1's unit: defeated to pay, the unit and nothing else changes.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Experience_PaysTheCost
#// SOR_T01 Experience token upgrade on P1's unit: defeated to pay, the unit and nothing else changes.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Force_PaysTheCost
#// LOF_T03 The Force (player state, offered as myBase-0): defeated to pay, without Using the Force (CR 37.4).
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1NOFORCE
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Advantage_PaysTheCost
#// ASH_T02 Advantage token upgrade on P1's unit: defeated to pay, the unit and nothing else changes.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Weakness_PaysTheCost
#// HMW_T02 Weakness token upgrade on P1's unit: defeated to pay, the unit and nothing else changes.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_BattleDroid_PaysTheCost
#// TWI_T01 BattleDroid token unit (ground arena): defeated to pay.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Beast_PaysTheCost
#// HMW_T03 Beast token unit (ground arena): defeated to pay.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_T03:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_CloneTrooper_PaysTheCost
#// TWI_T02 CloneTrooper token unit (ground arena): defeated to pay.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Mandalorian_PaysTheCost
#// ASH_T01 Mandalorian token unit (ground arena): defeated to pay.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_T01:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_Spy_PaysTheCost
#// SEC_T01 Spy token unit (ground arena): defeated to pay.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_T01:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_TIEFighter_PaysTheCost
#// JTL_T01 TIEFighter token unit (space arena): defeated to pay.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_T01:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1SPACEARENACOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION

---

# EachToken_XWing_PaysTheCost
#// JTL_T02 XWing token unit (space arena): defeated to pay.
#// One section per token kind (user request, 2026-09-17). The token under test is P1's ONLY friendly
#// token, so the cost auto-picks it; the Credit mode then shows the Epic resolved.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_T02:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1SPACEARENACOUNT:0
P1CREDITCOUNT:1
P1BASE:EPICUSED
P1NODECISION
