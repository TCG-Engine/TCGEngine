# Overview
The card filters should be updated to treat sets as ordered. This way we can do something like
"set>twi" which would mean "all sets after TWI"
Sets need to be retroactively added to some list to reference the order

# Prerequisites
There should be a list of sets in each sub app's root folder, sibling to CreateDeck.php. This array will be in a AllSets.php file.
## Definitions
order value refers to the value of the key value pair in the AllSets arrays

# Requirements
This feature will only apply to the SWUDeck and SoulMastersDB sub apps
## Implementation Requirements
REQ-1: The zzCardCodeGenerator.php and zzCardCodeGenerator3.php file is updated to add these auto-generated functions
REQ-2: If AllSets array is empty, then fallback to regular "=" for the filter
REQ-3: If the user inputs "set>SET" (SET being a place holder for an actual set code), then generated function should filter on sets strictly greater than the target set's order value
REQ-4: If the user inputs "set>=SET", then generated function should filter on sets greater than the target set's order value and include the set targetted
REQ-5: If the user inputs "set<SET", then generated function should filter on sets less than the target set's order value
REQ-6: If the user inputs "set<=SET", then generated function should filter onsets less that the target set's order value and include the set targetted
## Other Requirements
FMT-1: Formatting should remain the same as in existing generator, with `fwrite($handler,` lines

# Validation
VALID-1: going to http://localhost:3100/TCGEngine/zzCardCodeGenerator.php?rootName=SWUDeck and waiting for its response should result in an updated generator files