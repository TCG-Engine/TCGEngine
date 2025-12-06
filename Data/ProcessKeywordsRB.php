<?php

include_once "../Core/LLMAPI.php";

$cardText = "SHIELD (+1 might while I'm a defender.) TANK (I must be assigned combat damage first.) Other friendly units here have SHIELD.";

$keywords = ["Shield", "Assault", "Tank", "Action", "Reaction", "Hidden", "Accelerate", "Legion", "Deflect", "Ganking", "Temporary", "Deathknell", "Vision"];

// Improved prompt with better structure and examples
$prompt = "You are a card game keyword analyzer. Extract keywords from the card text below.

AVAILABLE KEYWORDS: " . implode(", ", $keywords) . "

TASK:
1. Identify which keywords appear in the card text
2. For 'X' keywords (like Shield X, Assault X), extract the numeric value
3. Determine if the keyword applies to 'self' (this unit) or 'others' (other units)

OUTPUT FORMAT (one per line):
[Keyword]: [Value or 'N/A']: [self/others]

EXAMPLES:
Shield X: 1: self
Assault X: 2: others
Tank: N/A: self

CARD TEXT:
" . $cardText . "

OUTPUT:";

echo OpenAICall($prompt, $model="gpt-4o-mini");

?>