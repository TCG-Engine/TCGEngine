# ReactiveTrigger_JangoDraw_IsAttributed
#// Game-log follow-up (2026-09-11). Only ~11 of DispatchTrigger's 134 cases went through a dispatcher that
#// sets the log source; the ~120 reactive triggers logged with NO source ("P1 drew 1 card", naming nobody).
#// DispatchTrigger now sets the trigger's card as the source up front.
#// SHD_138 Jango Fett (3/6): "When this unit attacks and defeats a unit: Draw a card."

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_138:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 drew 1 card ([[SHD_138|Jango Fett]])

---

# ReactiveTrigger_DuringAnEvent_NamesTheReactor_NotTheEvent
#// The STALE-source half: a reaction that fires while an event resolves must name its own card. Before the
#// fix the event's source was still set, so Gideon's Experience read "P1's Sorcerous Blast gave …".
#// SOR_036 Gideon Hask: "When an enemy unit is defeated: Give an Experience token to a friendly unit."
#// LOF_172 Sorcerous Blast: "Use the Force. If you do, deal 3 damage to a unit." Two friendly units, so
#// Gideon's pick is a real decision (and the Marine is chosen, so the line is not a self-effect).

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_172
WithP1GroundArena: SOR_036:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
LOGCONTAINS:P1's [[SOR_036|Gideon Hask]] gave an Experience token to P1's [[SOR_095|Battlefield Marine]]
LOGCOUNT:0:[[LOF_172|Sorcerous Blast]] gave

---

# SelfEffect_ReadsItself
#// When the affected unit IS the source, the line says "itself" rather than naming the unit twice — and
#// still names the source (Jar Jar's random hit on himself must not read as an unexplained "took 2").
#// LAW_034 Chewbacca (4/4): "When Attack Ends: If the defending unit was defeated, give an Experience token
#// to this unit and heal 3 damage from him." (His own trigger key, 'LAW_034', not OnAttackEnd.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: LAW_034:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
LOGCONTAINS:P1's [[LAW_034|Chewbacca]] gave an Experience token to itself
LOGCONTAINS:P1's [[LAW_034|Chewbacca]] healed 3 damage from itself
