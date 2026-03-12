# Overview
Filters need to be able to be combined to be able to search for multiple properties in one query. Groupings in paranthesis can be used to group many OR logics with other AND logic.

# Requirements
REQ-1: a single space delimiter is continued to be used for AND logic
REQ-2: " or " (space or space) pattern is used to delimit multiple queries using OR logic
REQ-3: the " or " delimiter should only be accepted in lowercase
REQ-4: shortcuts are unaffected and can be used with this pattern as well
REQ-5: parenthesis group queries together and are AND'ed together with the neighboring queries
REQ-6: users are not punished for redundant queries (see sEX-6)

# Examples for SWUDeck
EX-1: `aspect:villainy or aspect:vigilance` returns all cards with either of these aspects
EX-2: `aspect:villainy aspect:aggression or aspect:vigilance` returns all cards with either both villainy and aggression or at least vigilance
EX-3: `power>3 cost=5 or cost>6` returns all cards with cost 5 and power greater than 3 or any cards that cost over 6
EX-4: `c:gk or c:bk` returns all cards with either Command&Villainy aspects or Vigilanc&Villainy aspects (see FilterShortcuts.md spec as a reference)
EX-5: `(c:bk or c:gk) cost>5` returns all cards of cost greater than 5 with either Vigilance&Villainy aspects or Command&Villainy aspects
EX-6: `(c:bw or c:gw) or c:rk` returns all cards that are either Vigilance&Heroism, Command&Heroism, or Aggression&Villainy