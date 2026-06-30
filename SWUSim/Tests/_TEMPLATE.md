## GIVEN
SkipPreGame: true       # no drew-6 math; state is EXACTLY what GIVEN says (no auto hand/deck fill)
# P1OnlyActions: true     # shorthand for: WithInitiativePlayer:2 + WithInitiativeClaimed:true + WithActivePlayer:1
                        #   → P2 holds claimed initiative and auto-passes after every P1 action

# CommonSetup: myCode/theirCode/{opts}   — code is 3 chars: {baseColor}{leaderAspect}{leaderAlignment}, e.g. rrk = Red base + Aggression/Villainy leader (SOR_026 + SOR_010)
# opts (';'-separated). 'my'/'their' variants for each:
#   myResources:N / theirResources:N            — resource count
#   myhandCardIds:A,B / theirhandCardIds:A,B    — hand cards   (legacy aliases: handCardIds / theirHandCardIds)
#   discardCardIds:A,B / theirDiscardCardIds:A,B — discard cards
#   myBaseDamage:N / theirBaseDamage:N          — base damage
#   myLeader:CARDID / theirLeader:CARDID        — override the code's leader with any cardID (e.g. a JTL pilot leader)
#   myLeaderDeployed:true / theirLeaderDeployed:true       — deploy leader as a real ground-arena leader unit
#   myLeaderDeployedPilot:true / theirLeaderDeployedPilot:true — deploy leader as a Pilot upgrade on the player's FIRST friendly unit (host Vehicle)
#   myLeaderReady / myLeaderEpicUsed (+ their)  — leader-side flags
# The opts block can be inline — {myResources:5; ...} — OR spread across multiple lines inside the { } (as below).
CommonSetup: bgw/grk/{
  myResources:5;
  theirResources:5;
  myhandCardIds:SOR_095,SOR_046;
  theirhandCardIds:SOR_092
}

# --- Alternative to CommonSetup: set leader+base explicitly (do NOT use alongside CommonSetup) ---
# P1LeaderBase: SOR_010:1/SOR_026         # leader spec = CARDID:ready:deployed:epicUsed  →  ready leader, NOT deployed  /  base = CARDID:damage:epicUsed
# P1LeaderBase: SOR_010:1:1/SOR_026       # ...continuing: ':1:1' = ready AND already-deployed leader unit on the field

# Units in each of the four arenas — spec = CARDID:ready:damage  (ready 1/0, damage default 0)
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_085:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
# - P1>PlayHand:0                         # play hand card at index 0
# - P1>AttackGroundArena:0:BASE           # ground unit 0 attacks enemy base   (target: BASE | S<idx> cross-arena space | <groundIdx>)
# - P1>AttackSpaceArena:0:BASE            # space unit 0 attacks               (target: BASE | G<idx> cross-arena ground | <spaceIdx>)
# - P1>DeployLeader:0                     # deploy leader (optional pip index)
# - P1>UseLeaderAbility:0                 # use leader action ability (optional index)
# - P1>UseBaseAbility                     # use base ability
# - P1>UseUnitAbility:myGroundArena-0     # use a unit's "Action [Exhaust]:" ability (zone-idx ref)
# - P1>SmuggleResource:0                  # smuggle the resource at index 0
# - P1>PlayFromDiscard:0                  # play hand-equivalent from own discard
# - P1>PlayFromOpponentDiscard:0          # play from opponent's discard
# - P1>Pass                               # pass action
# - P1>Claim                              # claim initiative
# - P1>ResourceHand:0                     # (pregame/regroup) resource the hand card at idx 0
# - P1>ResourcePass                       # (regroup) decline the optional resource
# - P1>MulliganNo                         # (pregame) keep opening hand   (MulliganYes to mulligan)
# - P1>AnswerDecision:theirGroundArena-0  # answer a pending picker with a raw target token
# - P1>ChooseMyGroundUnit:0               # sugar for AnswerDecision:myGroundArena-0   (also ChooseMySpaceUnit / ChooseTheirGroundUnit / ChooseTheirSpaceUnit)
# - P1>ResolveTrigger:WHEN_PLAYED         # pick a pending EffectStack trigger by type (optional :CardID filter)

## EXPECT
# P1WIN  /  P2WIN                         # game winner
# PHASE:MAIN   /   PHASEISNOT:MAIN        # current phase
# INITIATIVECOUNTER:P2_CLAIMED            # initiative-counter state
# P1BASEDMG:5                             # base damage
# P1BASE:EPICAVAILABLE  /  EPICUSED       # base epic-action state
# P1BASEACTIONUSES:1                      # base repeatable-action uses left
# P1RESCOUNT:5    P1RESAVAILABLE:3        # total / ready resource count
# P1CREDITCOUNT:2                         # Credit-token count
# P1HANDCOUNT:3   P1HANDCARD:0:SOR_095    # hand size / card id at index
# P1DECKCOUNT:30  P1DECKTOPCARD:SOR_095   # deck size / top card
# P1DISCARDCOUNT:1                        # discard size
# P1DISCARDUNIT:0:CARDID:SOR_095          # discard entry field (CARDID | MODIFIER | FROM)
# P1GROUNDARENACOUNT:2   P1SPACEARENACOUNT:1   # arena occupancy
# P1HASDECISION  /  P1NODECISION          # pending-decision presence
# P1HASFORCE     /  P1NOFORCE             # controls the Force token
# P1LEADER:READY|EXHAUSTED|DEPLOYED|NOTDEPLOYED|EPICUSED|EPICAVAILABLE
# P1HANDPILOTPLAYABLE:0  /  P1HANDPILOTPLAYABLENOT:0   # pilot-playable hand index
# EFFECTSTACKCOUNT:1     EFFECTSTACKHAS:WHEN_PLAYED    # effect-stack size / has trigger type
# LOGCONTAINS:text       LASTLOGCONTAINS:text          # game-log text search
# Unit assertion — P{n}{GROUND|SPACE}ARENAUNIT:idx:CHECK
# P1GROUNDARENAUNIT:0:READY            # or EXHAUSTED
# P1GROUNDARENAUNIT:0:CARDID:SOR_095
# P1GROUNDARENAUNIT:0:DAMAGE:2
# P1GROUNDARENAUNIT:0:POWER:3
# P1GROUNDARENAUNIT:0:HP:4
# P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
# P1GROUNDARENAUNIT:0:SHIELDCOUNT:1       # Shield tokens (SOR_T02)
# P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1    # Advantage tokens (ASH_T02)
# P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T02
# P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel   # or NOTKEYWORD:Sentinel
# P1GROUNDARENAUNIT:0:ISLEADERUNIT          # or NOTLEADERUNIT
