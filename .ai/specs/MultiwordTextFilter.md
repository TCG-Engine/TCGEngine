# Overview
Filtering values should allow to search for multiple words in the properties

# Requirements
REQ-1: multiple words should be accepted when inside double quotes `"text here"`

# Examples
EX-1: `trait:"bounty hunter"` should return all cards with trait "Bounty Hunter" and no cards with trait "Bounty"
EX-2: `t="when defeated"` should return all cards with "When Defeated" in their text