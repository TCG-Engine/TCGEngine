# MonasteryPlusChamber_PickChamberById
#// Named Action abilities on a BASE: Mystic Monastery's own Action and Carbonite Chamber's (a Fortify upgrade) are two
#// entries; naming Carbonite Chamber runs ITS Action (defeat the upgrade → the chosen unit doesn't ready next regroup)
#// and leaves the Monastery's 3 uses untouched.
## GIVEN
CommonSetup: gbk/bbk/{myBase:LOF_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>UseBaseAbility:HMW_095.0
## EXPECT
P1BASEUPGRADECOUNT:0
P1ACTIONENTRIES:myBase-0:EXACT:LOF_022.0
P1ACTIONENTRIES:myBase-0:ENABLED:LOF_022.0

---

# BareEpicAction_Ambiguous_Error
#// Two usable base Actions and no id: an error, never a silent pick (and no OPTIONCHOOSE any more).
## GIVEN
CommonSetup: gbk/bbk/{myBase:LOF_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>UseBaseAbility
## EXPECT
EXPECTERROR:ambiguous

---

# DisabledBaseEntry_Refused
#// A spent Epic Action named by id does nothing: no Shield is given.
## GIVEN
CommonSetup: gbk/bbk/{myBase:SOR_019;theirBase:SOR_021;myBaseEpicUsed:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseBaseAbility:SOR_019.0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Log_FortifyGrantedAction_NamesTheBase
#// A Fortify upgrade's granted base Action is logged as the ability AND the base that used it.
## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseBaseAbility:HMW_172.0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
LOGCONTAINS:P1 used [[HMW_172|Heavy Ion Cannon]] on

---

# DisabledFortifyEntry_Refused_NothingHappens
#// Near-miss for the refusal: Heavy Ion Cannon's cost (discard a card) can't be paid with an empty hand, so its entry
#// is greyed — naming it must do NOTHING, not even write the "used …" line. (The base's own Epic Action has a second
#// guard inside its handler; a Fortify Action has none, so only the dispatcher's refusal stands here.)
## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1BaseUpgrade: HMW_172
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseBaseAbility:HMW_172.0
## EXPECT
LOGCOUNT:0:[[HMW_172|Heavy Ion Cannon]] on
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
