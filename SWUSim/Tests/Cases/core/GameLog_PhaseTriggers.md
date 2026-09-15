# RegroupBaseTrigger_DarkSanctum_Attributed
#// Game-log follow-up (2026-09-11) — a known gap from the first sweep: "When the regroup phase starts"
#// abilities that resolve INLINE in RegroupPhaseStart (no trigger dispatch) logged with no source, right
#// after the phase boundary cleared it ("P1 drew 1 card" / "P1's base took 2 damage"). HMW_070 Dark
#// Sanctum: attached base gains "When the regroup phase starts: Draw a card and deal 2 damage to this base."
#// (Fixture from hmw/DarkSanctum.md.)

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1BASEDMG:2
LOGCONTAINS:P1 drew 1 card ([[HMW_070|Dark Sanctum]])
LOGCONTAINS:P1's [[HMW_070|Dark Sanctum]] dealt 2 damage to P1's base
#// …and the regroup DRAW right after it is still unattributed (the source is restored, i.e. empty).
LOGCONTAINS:P1 drew 2 cards
LOGCOUNT:0:P1 drew 2 cards ([[

---

# RegroupUnitTrigger_ZilloBeast_Attributed
#// TWI_067 The Zillo Beast: "When the regroup phase starts: Heal 5 damage from this unit."
#// (Fixture from twi/TheZilloBeast_AwokenFromTheDepths.md.)

## GIVEN
CommonSetup: bbw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_067:1:5
WithP1Deck: [SOR_095 SOR_046 SOR_128]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
LOGCONTAINS:P1's [[TWI_067|The Zillo Beast]] healed 5 damage from itself

---

# NoxiousRefinery_RevealIsVisible_AndItsQueuedDamageIsAttributed
#// ⚠ Bug found in this pass: HMW_160 Noxious Refinery's reveal was written with visibility 0 — not 'ALL' and
#// no seat tag — so NOBODY ever saw it. A reveal is public to every seat.
#// (Fixture from hmw/NoxiousRefinery.md.)

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
WithP1BaseUpgrade: HMW_160
WithP1Deck: [SOR_128 SOR_095 SOR_046 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>Pass
- P1>Drain

## EXPECT
P1LOGSEES:P1 revealed [[SOR_128|Death Star Stormtrooper]] from the top of their deck ([[HMW_160|Noxious Refinery]])
P2LOGSEES:P1 revealed [[SOR_128|Death Star Stormtrooper]] from the top of their deck
#// USER DECISION 2026-09-11 (gamelog-updates #6): the queued "deal 1" (a universal DEAL_UNIT_DAMAGE
#// continuation, resolved LATER, after the source had been reset) carries the source stamped when it was
#// QUEUED — so it names the Refinery instead of reading "P2's Consular Security Force took 1 damage".
LOGCONTAINS:P1's [[HMW_160|Noxious Refinery]] dealt 1 damage to P2's [[SOR_046|Consular Security Force]]

---

# FirstLegion_NamedTrait_IsVisible
#// ⚠ Same bug as Noxious Refinery: HMW_108 The First Legion's "named the X trait" line was written with
#// visibility 1 (an int), so no seat ever saw it. (Fixture from hmw/TheFirstLegion_VadersFist.md.)

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_108:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Vehicle

## EXPECT
P1LOGSEES:P1 named the Vehicle trait; enemy cards lose it this phase ([[HMW_108|The First Legion]])
P2LOGSEES:P1 named the Vehicle trait

---

# RegroupStart_JetpackShieldDefeat_Attributed
#// SHD_225 Jetpack: "When Played: Give a Shield token to attached unit. At the start of the regroup phase,
#// defeat that token." The regroup-start defeat removed the tagged token silently. (Fixture from
#// shd/Jetpack.md.)

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
LOGCONTAINS:P1's [[SHD_225|Jetpack]] defeated a Shield token on P1's [[SOR_095|Battlefield Marine]]

---

# RegroupStart_ZoriiDiscard_Attributed
#// SHD_203 Zorii Bliss: "On Attack: Draw a card. At the start of the regroup phase, discard a card from your
#// hand." The regroup discard is a QUEUED choice; it now carries Zorii as its source (queued-source stamp).
#// (Fixture from shd/ZoriiBliss_ValiantSmuggler.md, OnAttack_DrawThenRegroupDiscard.)

## GIVEN
CommonSetup: gyw/gyw
P1OnlyActions: true
WithP1GroundArena: SHD_203:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>AnswerDecision:myHand-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LOGCONTAINS:P1 discarded [[SOR_095|Battlefield Marine]] ([[SHD_203|Zorii Bliss]])
