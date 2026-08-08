# PassivePowerPerCaptive
#// SEC_212 Libertine — "gets +1/+0 for each captured card it's guarding." Via SEC_106, SEC_212 captures
#//   the enemy SOR_095 → it now guards 1 captive → power 3 + 1 = 4.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: SEC_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_106

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4
P2GROUNDARENACOUNT:0
P1NODECISION

---

# WhenPlayed_EnemyCapturesFriendly
#// SEC_212 Libertine (Space, 3/7, Cunning/Cunning, cost 4) — When Played: choose an enemy unit and a
#//   non-leader friendly unit; the enemy unit captures the friendly unit. SOR_046 captures SOR_095.
#//   (The lone enemy auto-resolves; the friendly pick — SOR_095 or Libertine — is a real prompt.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_212

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_212
P1NODECISION

---

# WhenPlayed_NoEnemyUnits_NoEffect
#// SEC_212 Libertine — with no enemy units in play there is nothing to capture with, so the When Played
#//   ability has no effect and Libertine simply remains in the space arena.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_212

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_212
P1NODECISION

---

# WhenPlayed_OnlyFriendly_CapturesItself
#// SEC_212 Libertine — "a non-leader friendly unit" has no "another", so when Libertine is the ONLY
#//   friendly unit in play it is the lone valid target and the chosen enemy captures Libertine itself.
#//   With one enemy (SOR_046) and no other friendly, both picks auto-resolve → SOR_046 guards Libertine.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_212

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# PlayedViaDJ_FreesItselfByHavingTheENEMYCaptureItsOwnCaptor
#// SEC_212 Libertine — the self-rescue chain. Played through SEC_018 DJ's Action, Libertine is captured by
#// the chosen friendly captor (P1's A-Wing) BEFORE its own When Played resolves. That When Played then
#// makes an enemy unit capture a friendly one — and the only friendly unit still IN PLAY is the A-Wing
#// itself (a captive is not in play, so Libertine can't pick itself). P2's TIE Bomber captures the A-Wing,
#// and capturing a captor rescues everything it was guarding: Libertine walks free into P1's space arena.
#// End state: P1 controls only Libertine; the A-Wing is the TIE Bomber's captive.

## GIVEN
CommonSetup: gyk/rrk/{myLeader:SEC_018;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_212
WithP1SpaceArena: SOR_141:1:0
WithP2SpaceArena: JTL_237:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_212
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
