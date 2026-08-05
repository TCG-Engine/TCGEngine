# Hellbreak Quick Start Rules

Source: [Hellbreak Quick Start Guide](https://cdn.shopify.com/s/files/1/0713/2115/7771/files/Hellbreak_Quick_Start_Guide_c7d5aa8f-5b6b-4000-acc2-7cf31b2eca8c.pdf?v=1781818658), June 2026.

This is an implementation-oriented transcription in original wording. It preserves the rules in the quick-start guide without reproducing its promotional copy or page layout. The comprehensive rulebook, when available, takes precedence.

## Objective and components

Each player controls a monster. A player wins by killing the opposing monster.

The starter product contains Dracula and Jaws decks. The guide defines these card types:

- Monster
- Minion
- Asset
- Event
- Location

The game uses blood, malice, and damage counters. Cards can also provide a draw resource on their resource bars.

## Setup

1. Assign initiative. In the introductory game, Dracula begins with the initiative marker. In later games, choose the initial holder randomly.
2. Put each double-sided monster into play with its lurking side face up.
3. Choose locations. The introductory pairing uses Carfax Abbey for Dracula and North Beach for Jaws. In later games, each player secretly chooses one of their two locations, both reveal together, and the unused locations leave the game.
4. Shuffle each main deck and draw four cards.
5. Each player may mulligan any number of cards. Put those cards on the bottom in any order, then draw back to four.
6. Build each monster's health stack from the top eight cards of its deck, representing the starter monsters' 16 health. Deal the cards face down and horizontally, overlapping them.
7. Put shared counters within reach.

## Board and card state

Monsters, minions, and assets are either ready or exhausted.

The battlefield has two locations. Each player contributes one location, but control of either location may change during play. A monster is present at both locations. A minion occupies exactly one location. Assets do not occupy a location.

"Here" means the location occupied by the relevant card. Text that does not say "here" is not location-limited unless another rule says otherwise.

Cards a player controls are allied; cards controlled by the opponent are enemy.

## Round sequence

Every round has three phases:

1. Feeding
2. Horror
3. Refresh

### Feeding phase

#### Collect resources

Each player totals every resource icon visible in their vault. The vault consists of the monster's resource bar and the resource bars of cards tucked beneath that monster. Add the resulting blood and malice to the player's pools and perform any draw resources. Resources remain in the player's pool until spent.

#### Bid for initiative

Each player may secretly commit one card from hand or decline. Reveal committed cards simultaneously.

- Compare their printed blood costs; declining counts as zero.
- The higher bid wins.
- A tied bid is won by the player who did not hold initiative before the bid.
- The winner chooses which player receives the initiative marker.
- Every committed card is placed into its owner's vault, regardless of who won.

"In initiative order" means the initiative holder acts first, followed by the opponent.

### Horror phase

Beginning with the initiative holder, players alternate taking one action at a time. The available actions are:

- Play a card.
- Attack with a character.
- Scheme with a character.
- Use an Action ability.
- Slumber.
- Pass.

The phase ends after consecutive pass-like actions: if one player passes and the opponent's next action is Pass or Slumber, the Horror phase ends. A normal action after a pass breaks the sequence.

#### Play a card

A card is legal to play only when the player's vault contains at least the required number of its aspect icons. This is the card's loyalty requirement. The player then pays its blood cost.

Card-type handling:

- Minion: choose a location and enter exhausted. The player may immediately pay one malice to ready it. Resolve its Played ability, if any.
- Asset: enter ready and do not occupy a location.
- Event: resolve the event, then put it face up in its owner's crypt. Lurking-only and unleashed-only events require the matching monster side.

#### Use an Action ability

Use an available Action ability on a controlled card, paying any listed malice cost and exhausting the source if the ability requires it. Lurking-only and unleashed-only actions require the matching monster side.

#### Attack with a character

1. Declare attacker: choose a ready allied monster or minion and exhaust it. Resolve its Attack ability, if any.
2. Declare target: a minion attacks through its own location and targets an enemy character there. A monster first chooses either location, then an enemy character there.
3. Declare defender: the opponent may exhaust one ready allied character at that location as defender. The target may also be the defender.
4. Resolve combat:
   - With no defender, the attacker deals combat damage to the target and receives none in return.
   - With a defender, attacker and defender simultaneously deal combat damage to each other.
   - An attacking monster never receives combat damage from a defender.

Monsters can attack or defend at either location because they are present at both.

#### Scheme with a character

Choose and exhaust a ready allied character. Resolve its Scheme ability, if any, then resolve its printed scheme icons from left to right:

- Prowl X: deal X indirect damage to the opponent.
- Foresee X: look at the top X cards of the player's deck, then return them to the top and/or bottom in any order.
- Haunt X: add X malice from the supply to the scheming player's row on that location. When a monster schemes, choose the location first.

#### Take control of a location

Immediately take control when a player's malice row reaches or exceeds that location's malice threshold, even if that player already controls it:

1. Remove all malice from both players' rows at that location.
2. Orient the location toward the new controller if necessary.
3. Collect the location's resource icons.
4. Resolve its Take Control ability, if any.

#### Slumber

Only one player may Slumber in a round. That player gains one malice and is treated as passing for all remaining actions in the current Horror phase.

#### Pass

Passing spends the current action but does not prevent that player from acting later if the opponent takes a normal action. If the opponent's immediately following action is Pass or Slumber, the Horror phase ends.

### Refresh phase

1. Both players simultaneously ready all exhausted cards they control.
2. In initiative order, each player may flip their monster between lurking and unleashed.
3. Each player with more than six cards discards chosen cards to their crypt until they have six.

Then begin a new round with Feeding.

## Damage, death, and health stacks

Damage on minions is persistent. A minion with damage at least equal to its health is killed and placed face up in its owner's crypt.

When a player receives indirect damage, that player divides it among any number of their characters. A minion cannot be assigned more indirect damage than its remaining health.

Monster damage is applied to the top of its health stack one point at a time:

- A horizontal health card represents two health.
- The first damage rotates that card vertically, leaving one health.
- The second damage reveals and discards it.
- When revealed this way, its Jumpscare ability may be used if the player pays any associated malice cost.
- Only one card in a health stack may be vertical at a time.
- Continue resolving excess damage against the next card.

For the quick-start implementation, the newly revealed card is the only card eligible to use a Jumpscare in this window. Its Jumpscare resolves before the normal discard instruction and before excess damage continues. Explicit card text that moves or plays the revealed card overrides that normal discard destination. General response chains and cancellation timing remain deferred until comprehensive rules define them.

A monster with an empty health stack is killed and loses the game.

## Other universal rules

- Unique characters and assets use the knife symbol. If a player controls two unique cards with the same name and subtitle, that player immediately kills one.
- Traits such as Shark and Vampire have no inherent rule in this guide, but other rules may refer to them.
- An empty deck does not itself cause a loss. Each failed draw instead deals two damage to that player's monster.

## Quick-start examples captured by the guide

- A five-blood card with two Feral loyalty is legal when the vault contains at least two Feral icons, even if it also contains other aspects.
- An undefended attacker damages its declared target without retaliation.
- A defender exchanges simultaneous combat damage with the attacker; lethal minion damage is checked afterward.
- A scheme may deal indirect damage, add location malice, trigger an immediate control change, clear both malice rows, and collect the location reward in one action.

## Matters not resolved by this guide

The comprehensive rules are still needed for several edge cases:

- Timing and ordering of simultaneous or nested triggered abilities.
- The complete taxonomy and timing of ability icons.
- Replacement, prevention, and redirection ordering.
- Odd monster health values and non-starter health-stack sizes.
- What happens when both monsters would be killed simultaneously.
- Hidden-information and reveal rules beyond the examples above.
- Detailed deck-construction and location-selection requirements.
- Full keyword definitions and interactions.
