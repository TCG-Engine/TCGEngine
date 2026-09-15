# Hellbreak Rules of Play — Version 1.0 (Draft)

The official rulebook, and the only complete rules document Hellbreak has published. It is the
source of truth for how the game works; card-specific wording still comes from the card itself.

- Source: https://hellbreakgame.com/pages/rulesandtutorials -> "Rules of Play (Draft)"
- PDF: https://cdn.shopify.com/s/files/1/0713/2115/7771/files/HellBreak_Rules_of_Play_Draft.pdf
- Retrieved: 2026-09-15 (11 pages, sha256 8893bd7b0f...)
- The page also offers a 2-page Quick Start Guide (EN/ES/FR); its content is transcribed in
  `HellbreakSim/Rules/QuickStartRules.md`.

## Reading this text

Extracted from the PDF, so two things are lost and matter:

1. **Icons vanish.** "Action {malice} 1: Deal 1 damage" reads as "1 : Deal 1 damage", and resource
   bars read as bare numbers. When a rule turns on which resource an icon names, check the PDF or a
   card image — misread icons have already caused wrong card data twice.
2. **Columns and card diagrams interleave**, especially the play-area page. Read a whole page
   before concluding what a sentence belongs to.

Page markers below match the PDF's pages.

## What the rules settle (quick index)

| Question | Page |
|---|---|
| Round sequence: Feeding, Horror, Refresh; the six actions | 3 |
| Setup, including the health stack (half the monster's health, rounded up) | 2 |
| Card anatomy: cost, loyalty, combat, health, scheme bar, resource bar, traits | 4 |
| Locations: malice value, taking control, resource rewards | 4, 6 |
| Playing a card; aspects and loyalty ("aspect icon(s) in your vault") | 5 |
| Combat: attacking, defending, damage, killing minions | 6, 8-9 |
| Deck building: 1 monster, 2 locations (different names), 50+ cards, max 3 copies | 7 |
| Unique cards: the "\" glyph before the card type | 7, 10 |
| Keyword glossary (Bloodlust, Fearsome, Fierce, First Strike, Guardian, Malicious, Overkill, Stealth) | 7-9 |
| Jumpscare and health-stack abilities | 9 |

---
<!-- page 1 -->
TRADING CARD GAME
LEARN TO PLAY
VERSION 1.0
GAME OVERVIEW
HELLBREAK is the ultimate monster melee, a trading card game where
you play as an iconic villain from horror history. Round by round,
you’ll build resources, assemble your forces, and unleash your wrath.
Your forces are represented by a 50-card deck consisting of minions,
assets, and events.
WINNING THE GAME
The first player to chew through their opponent’s health and reduce
it to 0 wins the game!
USING THIS DOCUMENT
This rulebook is both an introduction to HELLBREAK and a reference for
the basic rules of how to play.
If you have never played a game of HELLBREAK before, we
recommend you start with the Hellbreak 2-player Starter Set.
If this document contradicts the Quick Start Guide, this document
D R A F T
COMPONENTS
In addition to cards, HELLBREAK uses a few
different counters, which are included in
the Hellbreak 2-player starter set.
Blood
Malice
Damage
There is no limit to the number of counters that can be in the game
at a given time. If there is a shortage, use any available substitute.
TABLE OF CONTENTS
takes precendence.
Overview ........................................................ 1
Table of Contents ............................................. 1
Game Setup .................................................... 2
Round Sequence and Gameplay ......................... 3
Card Anatomy ................................................. 4
Actions ........................................................... 5
Other Key Rules ............................................... 7
Deck Customization ......................................... 7
Glossary ........................................................ 8
Quick Reference ............................................. 11
COUNTERS
1

<!-- page 2 -->
LOCATION
Health Stack
Assets
Minions
SUGGESTED PLAY AREA
Jaws’ Location
Initiative Token
Malice Row
Crypt
(Discard Pile)
3
NORTH BEACH Water
  1  : Deal 1 damage to a minion here. Limit once per round
(per player).
“It’s a beautiful day, the beaches are open...” — Mayor Vaughn
Xiaofan Zhang © 2026 NBCU © 2026 SMI
DOT
020
Malice Row
Minions
Resource Pool
Deck
(Discard Pile)
Vault
Monster &
“Master, the sun is gone!” — Renfield
© 2026 NBCU © 2026 SMI
Simon Pape
DOT
001
When you win initiative, you may gain 1 .
1
1
Undead • Vampire
0 16
DRACULA
Transylvanian Terror
MONSTER
LURKING
JAWS
Apex Predator
MONSTER
LURKING
Minions
Malice Row
Crypt
Deck
1
1
Creature • Shark
0 15
While an enemy minion is damaged, this card
gets +1 and +1.
“He’s got...lifeless eyes, black eyes. Like a doll’s eyes.” — Quint
© 2026 NBCU © 2026 SMI
004
DOT
Chris Trevas
Monster &
Vault
Assets
Anthony Avon © 2026 NBCU © 2026 SMI
DOT
015
“I would like to discuss the lease on Carfax Abbey.” — Dracula
When you win initiative, you may deal 1 indirect damage to a player.
4
CARFAX ABBEY Building
Malice Row
LOCATION
Dracula’s
Location
GAME SETUP
To set up the game, perform the following steps, in order:
1. Create The Supply: Place all blood, malice, and damage
counters in a common supply within easy reach of both
players.
2. Assign Initiative: Randomly assign the initiative to one player.
3. Prepare Monster: Each player places their double-sided
monster in the center of their play area, “lurking” stance face
up.
4. Choose Location: Each player secretly chooses one of their
D R A F T
Resource Pool
Minions
Health Stack
The Supply
5. Draw Starting Hand: Each player shuffles their deck and
offers it to an opponent for additional shuffling and/or a final
cut. Then, each player draws a starting hand of four cards.
6. Take Mulligan (optional): Each player, in initiative order, has
a single opportunity to mulligan by placing any number of
cards from their starting hand on the bottom of their deck in
any order. Then, they draw cards until they have four cards in
hand. Do not shuffle your deck.
two locations, then simultaneously reveals them. If they
7. Create Health Stack: On the left side of their play area, each
player deals a number of cards from the top of their deck,
face-down, equal to half of their monster’s health, rounded up.
Arrange these horizontally one after another, starting nearest
the player and overlapping them. Then, if your monster’s
health is odd, rotate the top card of the stack vertically.
are the same location, both players must use their other
locations. Remove the unused locations from the game.
For example, if a player’s monster has 15 health, that player would deal 8 cards
face-down, then rotate one card vertically.
Each player puts their location face up to the right of their
monster, with its text facing its owner.
The game is now ready to begin.
2

<!-- page 3 -->
ROUND SEQUENCE AND GAMEPLAY
A game of HELLBREAK is played over several rounds. During a round,
players collect their strength, summon their minions to do their dark
biddings, and clash against their foes. Each round consists of three
phases:
PHASE 2: HORROR
Horror is the main phase of the game and is where most of the
action happens. In initiative order, players go back and forth taking
one action at a time, until both players consecutively pass.
Actions
The actions available to each player are:
Phase 1: Feeding
Phase 2: Horror
Phase 3: Refresh

Play a card

Use an “Action” ability

Attack with a character

Slumber

Scheme with a character

Pass
PHASE 1: FEEDING
Details for each of these actions are described starting on page 5.
In Feeding, players gain resources to enact powerful effects.
PHASE 3: REFRESH
1. COLLECT RESOURCES
Each player simultaneously collects resources equal to all the
resource icons (  , , fi fi ) shown in their vault. At the start of the
game, the only card in a player’s vault is their monster.
In Refresh, players prepare for the next assault.
1. READY CARDS
Each player simultaneously readies all exhausted cards they control.
 : Take 1   from the supply and add it to your pool.
 : Take 1   from the supply and add it to your pool.
fi fi: Draw the top card of your deck and add it to your hand.
See the “Ready and Exhausted” below for details
2. MONSTERS MAY FLIP
In initiative order, each player may choose to flip over their monster.
Players keep their resource tokens in a pool in front of them.
Monsters can be flipped from lurking to unleashed, or vice versa.
2. BID FOR INITIATIVE
In initiative order, each player may choose up to one card from their
hand and places it face down in front of them as a bid. Then, the
chosen cards are revealed simultaneously.
3. CHECK HAND LIMIT
Each player with more than six cards in hand must choose and
Finally, a new round begins with Phase 1: Feeding.
Compare the printed cost of each revealed card. The player with
ENDING THE GAME
the highest value wins and chooses which player takes initiative. If
the values are equal, the player without initiative wins and chooses
which player takes initiative.
If a card is not chosen as a bid, that player’s value is 0.
Finally, add each revealed card to its owner’s vault by sliding it
beneath their monster card so that only the incoming card’s resource
bar is visible.
The phrase “in initiative order” means the player with initiative acts
first, followed by their opponent.
LUCY WESTON
6Back from the Dead
FEEDING EXAMPLE
LUCY WESTON
6Back from the Dead
MINION
2
Undead • Vampire
3 6
Fearsome Fearsome (This card enters play ready.)
deal 2 damage to a minion here.
The Dracula player collects all the
resources shown in their vault
D R A F T
discard cards from their hand until they have only six cards in hand.
A game ends immediately once a player’s monster has 0 remaining
health and is killed. A player whose health stack is empty loses the
game, and their opponent wins the game.
Running Out of Cards
When a player has no cards left in their deck, they continue playing.
If a player would draw a card from an empty deck, instead deal
2
damage to their monster.
MINION
READY AND EXHAUSTED
Cards that are in play exist in one of two states:
LUCY WESTON
6Back from the Dead
MINION
2
Ready cards are oriented upright so that their text can be
read from left to right.
Undead • Vampire
3 6
When you take control of this location, you may
Fearsome Fearsome (This card enters play ready.)
© 2026 NBCU © 2026 SMI
Reiko Murakami
DOT
040
When you take control of this location, you may
deal 2 damage to a minion here.
Exhausted cards are rotated 90 degrees to the side. An
exhausted card cannot exhaust again until it is readied.
© 2026 NBCU © 2026 SMI
Reiko Murakami
DOT
040
Then they bid for initiative, choosing
Lucy Weston from their hand, who
has a cost of 6.
Their opponent did not bid a card,
(5  , 1 , 2fi fi). They take 5  and
so the Dracula player wins initiative.
2
1  from the supply and add
Undead • Vampire
Finally, they add Lucy to their vault.
3 6
them to their resource pool. They
On future turns, the Dracula player
Fearsome Fearsome (This card enters play ready.)
also draw 2 cards from their deck,
will collect an additional card
When you take control of this location, you may
adding them to their hand.
deal 2 damage to a minion here.
during feeding.
2 TRANSYLVANIAN WOLF
MINION
“Listen to them. Children of the night. What music they make!” — Dracula
Creature • Wolf
2 1
Creature • Wolf
2 1
Fearsome Fearsome (This card enters play ready.)
“Listen to them. Children of the night. What music they make!” — Dracula
© 2026 NBCU © 2026 SMI
Jackson Milano
DOT
028
Jackson Milano
DOT
028
© 2026 NBCU © 2026 SMI
Fearsome Fearsome (This card enters play ready.)
Ready Exhausted
2 TRANSYLVANIAN WOLF
MINION
© 2026 NBCU © 2026 SMI
Reiko Murakami
DOT
040
3

<!-- page 4 -->
CARD ANATOMY
COST
In order to play this card, you
must spend this much blood.
TITLE AND SUBTITLE
CARD TYPE
There are 5 basic card types: Asset,
Event, Location, Minion, and Monster.
Unique cards have a  symbol
preceding their card type.
BARON LATOS
5Distinguished Gentleman
MINION
LOYALTY
In order to play this card, you
must have these aspect icons
in your vault.
COMBAT
The amount of damage this
card deals when it attacks or
defends.
SCHEME BAR
The effects that occur when a
character schemes, resolved
from left to right.
TRAITS
1
2
Undead • Vampire
3 5
Bloodlust 1 Bloodlust 1 (When this card kills a minion by
 damage, gain 1 .)
ABILITY TEXT
Each other Vampire minion you control gets +1
and +1.
Most cards have at least one ability. In
this case, the card has a keyword ability
“Bloodlust”. Keywords are shorthand for
common abilities and are defined in the
glossary section of this document.
“There are far worse things awaiting man than death.” — Dracula
© 2026 NBCU © 2026 SMI
Chris Scalf
DOT
037
ARTIST NAME RESOURCE BAR
The resources gained during
feeding if this card is in your vault.
D R A F T
These are descriptive words. They
have no rules, but they may be
referred to in card abilities.
HEALTH
The amount of damage this
card can take before it is killed.
COLLECTION DATA
HELLBREAK cards are printed in sets. The three
letters show the set code, and the number is
the card’s ID number within that set. The
letter on the right is the card’s rarity.
Common
Uncommon
Rare
LOCATION
Legendary
Infamous
4 CARFAX ABBEY Building
When you win initiative, you may deal 1 indirect damage to a player.
Anthony Avon © 2026 NBCU © 2026 SMI
DOT
015
MALICE VALUE
Players must accumulate at least this
much malice in their malice row here
to take control of this location.
Special
RESOURCE REWARDS
Gain these resources when you
take control of this location.
TRAITS
These are descriptive words. They
have no rules, but they may be
referred to in card abilities.
4

<!-- page 5 -->
ACTIONS

Play a Card
To play a card, you must meet the card’s loyalty (see below). If you
meet its loyalty, then pay the card’s cost (in blood) by returning that
much   from your pool to the supply. If you can’t pay all costs, you
cannot play that card.
ASPECTS AND LOYALTY
Every card in your deck belongs to one of five different aspects:
Cursed Deranged Feral Revenant Void
Additionally, each card has loyalty, indicated by the small aspect
icon(s) just below its cost. To play a card, you must have aspect icons
in your vault equal to or greater than the card’s loyalty. See “Playing a
Card Example” below for details.
If a card ability or effect allows you to play a card for “free”, ignore
both its blood cost and loyalty requirements.
Each card type has different rules describing how it’s played. See the
“Different Card Types” sidebar for details.
DIFFERENT CARD TYPES
MINIONS
If the card is a minion, choose one of the two locations, then
place that minion in your play area below that location, as
shown on Page 2. Minions can only be at one location at a
time. Minions enter play exhausted.
Then, you may immediately pay 1   from your pool to ready
that minion.
If the minion has a  ability, you may use it either
before or after choosing to pay 1   to ready the minion.
ASSETS
If the card is an asset, place it in your play area in front of you,
ready. Assets are not at any location. Assets enter play ready.
If the asset has a  ability, use it now.
If an asset’s ability starts with “Attach to”, it enters attached to
and placed underneath a card specified by the ability, chosen
by the current player. Cards that have been attached are at the
location of the attached card. Cards that are attached to your
monster should be played with your other assets.
EVENTS
stance, respectively.
PLAYING A CARD EXAMPLE
5 THREAT FROM BELOW
MINION
The Jaws player wants to play
4 4
Threat From Below. It costs 5 
Overkill Overkill (While attacking, excess  damage is dealt
D R A F T
If the card is an event, resolve the card’s ability. After resolving
as much of the ability as possible, place the card in its owner’s
crypt. Ignore any parts of the ability that cannot resolve.
An event with  or  can
only be played if your monster is in its lurking or unleashed
MONSTERS AND LOCATIONS
Monsters and locations start the game already in play and
cannot be played.
LOCATION
3
NORTH BEACH Water
  1  : Deal 1 damage to a minion here. Limit once per round
(per player).
“It’s a beautiful day, the beaches are open...” — Mayor Vaughn
Xiaofan Zhang © 2026 NBCU © 2026 SMI
DOT
020
gets +2.
While an enemy minion is damaged, this card
to the defender's monster.)
Overkill Overkill (While attacking, excess  damage is dealt
4 4
Creature • Shark
Creature • Shark
to the defender's monster.)
and has 2 Feral loyalty.
LOCATION
3
NORTH BEACH Water
  1  : Deal 1 damage to a minion here. Limit once per round
(per player).
“It’s a beautiful day, the beaches are open...” — Mayor Vaughn
Xiaofan Zhang © 2026 NBCU © 2026 SMI
DOT
020
MINION
© 2026 NBCU © 2026 SMI
Zach Hoel
DOT
122
These lethal hunters can sense blood from miles away.
5 THREAT FROM BELOW
MINION
5 THREAT FROM BELOW
gets +2.
gets +2.
Overkill Overkill (While attacking, excess  damage is dealt
Creature • Shark
4 4
Creature • Shark
4 4
Overkill Overkill (While attacking, excess  damage is dealt
Zach Hoel
to the defender's monster.)
DOT
© 2026 NBCU © 2026 SMI
These lethal hunters can sense blood from miles away.
122
While an enemy minion is damaged, this card
While an enemy minion is damaged, this card
to the defender's monster.)
These lethal hunters can sense blood from miles away.
© 2026 NBCU © 2026 SMI
Zach Hoel
DOT
122
5 THREAT FROM BELOW
MINION
Their vault currently has 2 Feral
and 1 Deranged icon, which
satisfies the loyalty on Threat
While an enemy minion is damaged, this card
From Below, so the card
gets +2.
is able to be played.
These lethal hunters can sense blood from miles away.
The Jaws player pays 5  from their
pool and chooses to play Threat From
Below at North Beach.
Because Threat From Below is a
minion, it enters play exhausted.
Finally, Jaws pays 1  to ready
Threat From Below.
© 2026 NBCU © 2026 SMI
Zach Hoel
DOT
122
5

<!-- page 6 -->

Attack with a Character
Making an attack involves the following steps:
1. DECLARE ATTACKER
Choose one ready character (monster or minion) you control as the
attacker and exhaust it. If your attacker is your monster, also choose
which location to attack through. While attacking, your monster is
only at the chosen location.
Then if the attacker has an  ability, use it now.
2. DECLARE THE TARGET
Choose an opponent’s character at that location to be the target.
3. DECLARE DEFENDER
Your opponent may choose one ready character they control at
the same location to be the defender and exhaust it. This is the
defending character.
A single character can be both the target and the defender.
4. RESOLVE COMBAT DAMAGE
If there is no defender, the attacker deals combat damage equal to
its combat value () to the target. The target does not deal damage
in return.
If there is a defender, the attacker and defender simultaneously deal
combat damage to each other equal to their combat value ().
When a monster attacks, it never receives combat damage from
defenders.

Scheme with a Character
Choose one ready character you control and exhaust it. If that
character has a  ability, use it now.
Then, resolve its scheme icons from left to right. Each icon is paired
with a number (its value), and its effects are described below.
X
Prowl: Deal X indirect damage to your opponent (see Other
Key Rules for indirect damage).
X
and the rest on top of your deck in any order.
X
your monster, choose which location to haunt.
TAKING CONTROL OF LOCATIONS
When you have   in a location’s malice row equal to or greater
location by performing all of the following in any order:
•
Remove all   from both of that location’s malice rows.
•
Rotate the card so the location’s text is facing you.
•
Collect the locations’s resource rewards.
•
If the location has a  ability, use it now.
ATTACK EXAMPLE
9Dracula’s Son
COUNT ALUCARD
MINION
2
2
Undead • Vampire
4 7
Fearsome Fearsome (This card enters play ready.)
If you have initiative, this card costs 2  less to play.
  Add 3  to this location.
© 2026 NBCU © 2026 SMI
Terry Wolfinger
DOT
044
LOCATION
4
CARFAX ABBEY Building
When you win initiative, you may deal 1 indirect damage to a player.
“I would like to discuss the lease on Carfax Abbey.” — Dracula
Anthony Avon © 2026 NBCU © 2026 SMI
DOT
015
8 GIANT OCTOPUS
8
5 8
Creature
8
Creature
5 8
Legends of the deep.
Jake Pleshe
DOT
127
© 2026 NBCU © 2026 SMI
Legends of the deep.
© 2026 NBCU © 2026 SMI
Jake Pleshe
DOT
127
Foresee: Look at the top X cards of your deck, then put any
number of them on the bottom of your deck in any order,
Haunt: Add X   (from the supply) to the malice row on
your side of that character’s location card. If the character is
than the location’s malice value, you immediately take control of that
You can take control of a location you already control.
033
DOT
Letícia Freitas
© 2026 NBCU © 2026 SMI
“I feel wonderful. I’ve never felt better in my life.”
While this card is damaged, it gets +2 and
1 3
Human
2
2
1
Human
1
1 3
gains Vampire.
While this card is damaged, it gets +2 and
033
DOT
“I feel wonderful. I’ve never felt better in my life.”
© 2026 NBCU © 2026 SMI
Letícia Freitas
gains Vampire.
MINION
3The Count’s Obsession
MINA SEWARD
The Jaws player exhausts Giant
Octopus and declares it as their
attacker.
Alucard.
D R A F T
SCHEME EXAMPLE
The Dracula player exhausts Count
Alucard to scheme.
MINION
Count Alucard does not have a
 ability, so the Dracula Players
continues to resolve Alucard’s scheme
COUNT ALUCARD
9Dracula’s Son
icons.
Alucard has 2 , so the Jaws player takes
2 indirect damage and chooses to assigns
both points of damage to the Giant
Octopus.
Then, Alucard has 2 , so the Dracula
player adds 2   to Carfax Abbey; this
015
DOT
Anthony Avon © 2026 NBCU © 2026 SMI
“I would like to discuss the lease on Carfax Abbey.” — Dracula
When you win initiative, you may deal 1 indirect damage to a player.
CARFAX ABBEY Building
4
The Giant Octopus does not have
gives the Dracula player 5 total  in
LOCATION
their malice row at this location.
an  ability, so the Jaws
player proceeds and declares
4
CARFAX ABBEY Building
5 total  is greater than Carfax Abbey’s
LOCATION
the target of the attack as Count
4
CARFAX ABBEY Building
malice value of 4, so the Dracula player
When you win initiative, you may deal 1 indirect damage to a player.
When you win initiative, you may deal 1 indirect damage to a player.
“I would like to discuss the lease on Carfax Abbey.” — Dracula
takes control of the location.
Anthony Avon © 2026 NBCU © 2026 SMI
DOT
015
The Dracula player exhausts Mina
Seward and declares her as the
defender.
“I would like to discuss the lease on Carfax Abbey.” — Dracula
ALL   is removed from both malice
rows, and the location rotates to face the
Anthony Avon © 2026 NBCU © 2026 SMI
DOT
015
Dracula player.
The Octopus deals 5 damage to
8 GIANT OCTOPUS
Mina; Mina deals 1 damage to the
Octopus.
If Carfax Abby had a  
ability, the Dracula player would use it
now.
Because Mina now has damage
equal to or greater than her health,
Finally, the Dracula player collects the
location’s resource rewards of 1   and 1 fi fi.
she is killed and placed in her
owner’s crypt.
MINA SEWARD
3The Count’s Obsession
MINION
MINION
9Dracula’s Son
COUNT ALUCARD
  Add 3  to this location.
DOT
044
  Add 3  to this location.
© 2026 NBCU © 2026 SMI
If you have initiative, this card costs 2  less to play.
If you have initiative, this card costs 2  less to play.
LOCATION
Fearsome Fearsome (This card enters play ready.)
4 7
Undead • Vampire
2
2
2
2
Undead • Vampire
Fearsome Fearsome (This card enters play ready.)
Terry Wolfinger
4 7
© 2026 NBCU © 2026 SMI
Terry Wolfinger
DOT
044
MINION
Legends of the deep.
© 2026 NBCU © 2026 SMI
Jake Pleshe
DOT
127
8
Creature
5 8
8 GIANT OCTOPUS
MINION
MINION
6

<!-- page 7 -->

Slumber
Gain 1   (from the supply), but you must pass all your remaining
actions for the rest of this phase. Only one player can take this action
each round.

Pass
When you pass, you do nothing for this action, and you may still take
actions later in the phase. However, if your opponent’s next action is
to pass or to slumber, Horror immediately ends.

Use an “Action” Ability
Some cards have  abilities on them. You can use an
 ability on any card you control, as long as you can pay all
costs, such as   from your pool. You can use  abilities on
exhausted cards, as long as exhausting isn’t a cost.
A  ability can be used only if your monster is in
its lurking stance. An  can only be used if your
monster is in its unleashed stance.
OTHER KEY RULES
The Golden Rules
1. If the text of a card directly contradicts the text of the rules,
the text of the card takes precedence.
2. When a rule or effect instructs something to happen, and
another effect states that it can’t happen, the “can’t” effect
takes precedence.
3. While resolving an effect, resolve as much as possible, and
ignore any parts that cannot be resolved.
Resolve Effects in Full
If resolving part of the instructions of a card ability causes other card
effects to begin to resolve, finish resolving the instructions of the first
card before resolving any other card effects.
Character
A “character” is the term that encompasses any minion or monster.
Here
The term “here” is short for “at this location”. If an ability does not say
“here,” it can affect cards at any location.
Damage
DECK CUSTOMIZATION
After learning the basics of the game, the next step is to start building
your own custom deck. Additional cards to customize your deck can
be found in HELLBREAK booster packs.
health, it is killed and put face up in its owner’s crypt.
in its health stack.
Deck Customization Rules
Your deck must include:
Health Stack
•
Exactly one monster card.
•
Exactly two locations with different names.
•
At least 50 cards, consisting of any combination of minions,
assets, and events.
A deck can’t contain more than 3 copies of any card.
A good rule of thumb is for your deck to include about 30-40
minions, and about 10-20 assets and events. You should make sure
to include cards with a variety of costs, and some number of cards
with two   resource icons.
You can include cards of any aspect in your deck, but you must meet
a card’s loyalty requirements in order to play it (See “Play a Card” on
Page 5). Most decks contain between 1 and 3 aspects.
WHAT’S NEXT?
You are now familiar with the basic rules of
HELLBREAK! If a situation arises in your game
and you’re not sure how to proceed, expanded
rules and a detailed glossary, are available in the
Hellbreak Master Rulebook.
Access those rules, organized play information, and more at
HellbreakGame.com
D R A F T
Damage is persistent and is tracked by placing damage counters on
minions. If a minion has damage on it equal to or greater than its
When a monster is dealt damage, apply the damage to the top card
The health stack represents the monster’s health, which is tracked by
cards dealt face-down from the deck during setup. Each horizontal
card counts as two health, and a vertical card counts as one health.
There can only be one vertical card in a health stack.
Damage dealt to a monster is applied and resolved in full one at
a time. When a horizontal health card takes one damage, rotate it
vertical. When a vertical health card takes one damage, reveal that
card.
If it has a   ability, you may use it, paying any costs. Resolve
that   ability immediately before applying and resolving
the next point of damage to the monster. After resolving the ability,
place it in its owner’s crypt.
If it does not have a   ability, or you choose not to use the
  ability, place it in its owner’s crypt.
As soon as a monster has no cards in its health stack, it’s immediately
killed and the game ends.
Indirect Damage
When a player is dealt indirect damage, they assign that damage
(divided as they choose) among any number of characters they
control. A minion cannot be assigned more indirect damage than its
remaining health.
Unique Cards
Some cards are unique and have a symbol (  )
before their card type. If a player ever controls
two unique cards with the same name and
9Dracula’s Son
COUNT ALUCARD MINION
Ghost Galaxy and the Ghost Galaxy logo are trademarks  of Ghost Galaxy, Inc. 2026
All rights reserved. HELLBREAK, UNIVERSAL, JAWS, UNIVERSAL MONSTERS © 2026
NBCUniversal Media, LLC; Game Mechanics © Spin Master, Inc. All Rights Reserved.
subtitle, they must immediately kill one of them.
7

<!-- page 8 -->
Cost
GLOSSARY
Most card abilities require a cost to be paid in order to initiate.
This section provides a simplified definition for the most important
terms used in the game. An expanded glossary is found in the
Hellbreak Master Rulebook, which can be downloaded at
HellbreakGame.com.
A card’s resource cost is the numerical value that must be paid (in
 ) to play the card from hand. To pay a   cost, take the specified
amount of   from your pool and return them to the supply.
Action ( )
A player can use an   ability on a card they control, paying
any necessary costs to use the ability, such as  .
Crypt
A player’s crypt is their discard pile. Any card that is discarded or
killed is put on top of its owner’s crypt, as well as any event that has
finished resolving.
Actions
Damage
Players can take any of the following actions during Horror:
Damage a minion has taken is tracked by placing damage counters

Play a card

Use an “Action” ability
on the minion. A minion with damage counters is considered

Attack with a character

Slumber
“damaged” for the purposes of card abilities.

Scheme with a character

Pass
Damage a monster has taken is tracked via a monster’s health stack.
Details for each of these actions are described starting on page 5.
Damage, Indirect
Allied
When a player is dealt indirect damage, they assign that damage
The term “allied” refers to any card that is in play and under your
(divided as they choose) among any number of characters they
control.
control. A minion cannot be assigned more indirect damage than its
remaining health.
Attack ( )
Short for “When this card is declared as an attacker.” This trigger is
Discarding Cards
resolved before targets are declared.
crypt.
Banish
Banish is a face up area that is essentially a holding area for cards.
Some abilities banish a card permanently, while other abilities banish
a card only temporarily.
hand.
To banish a card, put it into the banish zone from whatever zone it’s
Drawing Cards
currently in.
Blood ( )
Blood is the basic currency in the game. When you “gain”  , take it
from the supply and place it in your resource pool.
Bloodlust X
When a minion with bloodlust kills another minion with combat
damage,
gain X  .
Character
A “character” is the term that encompasses any minion or monster.
Control
When a card is played, it enters play under the control of the active
player.
A player can take control of a card. When this happens, if it is a
minion, place it in the new controller’s play area at the same location.
If the minion was ready, it stays ready. If it is a location, perform all of
the following:
•
Remove all   from that location’s malice rows.
•
If the location’s text is not facing you, rotate the card so it is facing
you.
•
Collect the resource icons shown on the location.
D R A F T
When a card is discarded, it is placed face-up on top of its owner’s
Unless otherwise specified, when an ability refers to a player
discarding a card, the discarded card must come from that player’s
Each time a player is instructed to draw a card, take the top card of
their deck and add it to their hand.
When a player has no cards left in their deck, they continue playing.
If a player would draw a card from an empty deck, instead deal
2
damage to their monster.
Enemy
The term “enemy” refers to any card that is in play and not under
your control.
Fearsome
When a minion with fearsome enters play, it enters play ready
instead of exhausted.
Fierce X
While a character with fierce is attacking, it gets +X .
First Strike
While a character with first strike is attacking, it will deal damage
before the defending character deals damage.
Flipped ( )
Short for “When this card is flipped to this side.”
Foresee X ()
You can take control of a card you already control.
Look at the top X cards of your deck, then put any number of them
on the bottom of your deck in any order, and the rest on top of your
deck in any order.
Combat Value ()
The amount of damage this character deals during combat.
8

<!-- page 9 -->
Guardian
While a character with guardian is at a location, it must be chosen as
the target during enemy attacks at that location.
Location
Minions can only ever be at one location at a time, while monsters
are considered to be at both locations. Each location is always under
the control of one player at a time. Some locations have abilities that
Haunt X ()
are active and can be used by the player who controls the location.
Add X   (from the supply) to the malice row on your side of that
character’s location card. If the character is your monster, choose
which location to haunt.
Each location has two malice rows that represent each player’s
progress towards controlling the location. When you have  in a
location’s malice row equal to or greater than the location’s malice
value, immediately take control of that location.
Heal
If an effect “heals” a minion, remove the specified amount of
damage from the minion.
You can take control of a location you already control.
Health Stack
The health stack represents the monster’s health, which is tracked by
cards dealt face-down from the deck during setup.
Loyalty
A card’s loyalty is the required number of aspect icons in a player’s
vault in order to play a card in addition to paying the blood cost.
When a card is revealed from the health stack (usually by taking
Lurking Action ( )
damage), if it has a   ability, you may use it immediately.
A  ability can only be used only if your monster is in
its lurking stance.
As soon as a monster has no cards in its health stack, it’s immediately
killed and the game ends.
An event with  can only be played if your monster
is in its lurking stance.
Health Value ()
The amount of damage the character can take before being killed.
Malice ( )
Malice is the secondary currency in the game. When you “gain”  ,
If a character has more damage equal to or greater than its health
value, it is killed and placed in its owner’s crypt.
spend malice from their pool to pay for certain card abilities
If a character’s health is reduced to 0, it is killed and placed in its
owner’s crypt.
Malicious X
Here
The term “here” is short for “at this location”. If an ability does not
specify “here,” then it can affect cards at any location.
Move
Indirect Damage
See “Damage, Indirect”
Initiative
Initiative determines which player acts first during Horror.
The phrase “in initiative order” means the player with initiative acts
first, followed by their opponent.
Jumpscare (   )
Short for “When this card is revealed from your health stack”.
  abilities are optional, and interrupt the applying and
resolving of damage.
If the last revealed card from a health stack has a   ability,
you cannot resolve it, as the game is already over.
Kill
To kill a card, move it from play to its owner’s crypt.
The only ways a card can be killed are as a result of an effect that
uses the word “kill”, or as a result of having damage equal to or
greater than its health value. If a card is put into its owner’s crypt for
any other reason, it hasn’t been “killed.”
D R A F T
take it from the supply and add it to your resource pool. A player can
While you are paying resources to play a card with Malicious, you
may spend up to X   to pay for 1   each.
If an ability instructs you to move a minion, take that minion and
place it at another location. The minion is now at its new location.
This does not count as entering or leaving play, and all damage and
attachments remain attached.
Moved ( )
Short for “When this card is moved to a new location.”
Overkill
Overkill modifies how a character assigns combat damage while
attacking.
Once damage equal to the defender’s (or target’s, if no defender was
declared) remaining health has been assigned, any amount of excess
damage can be assigned to the defender’s monster.
Played ( )
Short for “When this card is played.”
A card entering a location without being “played” does not trigger
 abilities.
Prowl X ()
Deal X indirect damage to an opponent.
Killed ( )
Short for “When this card is killed.” See “Kill”.
Ready and Exhausted
Assets, minions, and monsters in play exist in one of two states:
ready and exhausted. An exhausted card cannot exhaust again until it
is readied (typically during Refresh or via a card ability).
9

<!-- page 10 -->
Resource Icons
Resource icons are the symbols (  , , fi fi ) shown in a card’s
resource bar. Typically, this is in the bottom left corner of a card.
Scheme ( )
Short for “When this card is declared as a schemer.” This trigger is
resolved before any effects in the scheme bar resolve.
Stealth
When a minion with stealth is declared as an attacker, choose a
character. The chosen character can’t be declared as a blocker.
Additionally, when declaring the target for the attack, ignore the
effects of Guardian.
Take Control ( )
Short for “When you take control of this card.” This does not trigger
during setup.
Terrify
To terrify a card, return it to its owner’s hand.
Token Minions
Some effects play tokens. A token is a marker used to represent
anything that isn’t represented by a card. It enters play according to
the token’s card type.
Token minions can be used just like minions and count as minions
for the purposes of abilities.
When a token minion leaves play, it is removed from the game and
can’t return to play. Applicable abilities such as  will trigger
after the token is removed from the game.
Unique Cards ()
If a player ever controls two unique cards with the same name and
subtitle, they must immediately kill one of them.
Unleashed Action ( )
An  ability can only be used only if your
monster is in its unleashed stance.
An event with  can only be played if your
monster is in its unleashed stance.
Vault
A player’s vault is the resource bars on their monster and all other
cards beneath their monster.
When counting the number of cards in your vault, include your
monster card.
D R A F T
10

<!-- page 11 -->
COUNTERS
QUICK REFERENCE
ROUND SEQUENCE
Phase 1: Feeding
1. Collect Resources equal to all the resource icons shown in
your vault.
2. Bid for Initiative, and add the card that you bid to your vault
Phase 2: Horror
In initiative order, go back and forth, taking one action at a time, until
both players consecutively pass.
Blood Counters in
values of 1 and 3
Malice Counters in
Damage Counters
values of 1 and 3
in values of 1 and 3
The actions available are:
ICONS



Play a card
Attack with a character
Scheme with a character



Use an “Action” ability
Slumber
Pass
X


Phase 3: Refresh
1. Ready all cards.
2. In initiative order, each player may flip their monster over.
3. Check your hand limit of 6 cards.
COST
UNIQUE
 
 
BANNERS
BLOOD
 
An ability you can use during Horror, paying any costs.
 
declared)
"When this card is declared as an attacker." (Before blockers are
 
“When this card is flipped to this side."
 
“When this card is revealed from your health stack."
 
“When this card is killed.”
 
An effect you can only use if your monster is in its lurking stance.
 
“When this card is moved to a new location.”
 
“When this card is played.”
 
icons)
“When this card is declared as a schemer.” (Before resolving scheme
D R A F T

COMBAT
HEALTH
fi fi
fl
MALICE
CARD
EXHAUST
XPROWL:
Deal X indirect damage to your opponent.
(They assign the damage)
FORESEE:
X
Look at the top X cards of your deck, then
put any number of them on the bottom of
your deck in any order, and the rest on top
of your deck in any order.
X
HAUNT:
Add X   (from the supply) to the malice
row on your side of that character’s location
card. If the character is your monster,
choose which location to haunt.
ASPECTS
 
“When you take control of this card.”
 
An effect you can only use if your monster is in its unleashed stance.
CURSED DERANGED FERAL REVENANT VOID
Ghost Galaxy and the Ghost Galaxy logo are trademarks  of Ghost Galaxy, Inc. 2026
All rights reserved. HELLBREAK, UNIVERSAL, JAWS, UNIVERSAL MONSTERS © 2026
NBCUniversal Media, LLC; Game Mechanics © Spin Master, Inc. All Rights Reserved.
11
