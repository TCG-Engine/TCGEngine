<?php

// Increase max execution time to 1 hour (3600 seconds)
set_time_limit(3600);

include_once __DIR__ . '/../GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';

$rootName = "GrandArchiveSim";

// ===== REPORT MODE =====
$reportMode = false; // Set to true to see detailed processing output
$generatePHPMode = true; // Set to true to generate PHP code instead of JSON
// ========================

// Grand Archive Keywords
$keywords = [
    [ 'name' => "Preserve" ],
    [ 'name' => "Pride" ],
    [ 'name' => "Intercept" ],
    [ 'name' => "Floating Memory" ],
    [ 'name' => "True Sight" ],
    [ 'name' => "Vigor" ],
    [ 'name' => "Efficiency" ],
    [ 'name' => "Cleave" ],
    [ 'name' => "Stealth" ],
    [ 'name' => "Prepare" ]
];

// Grand Archive Conditions
$conditions = [
    [ 'name' => "Class Bonus", 'evalFunction' => "IsClassBonusActive(\$player)", 'isVariable' => false ],
    [ 'name' => "Level", 'evalFunction' => "GetPlayerLevel(\$player)", 'isVariable' => true ]
];

// Function to parse keywords and conditions from card effect text
function parseKeywordsAndConditions($text, $keywords, $conditions) {
    $items = [];
    
    if (empty($text)) {
        return $items;
    }
    
    // Split by newlines to process each line
    $lines = explode('<br>', $text);
    
    foreach ($lines as $line) {
        // Remove reminder text (anything in *(...)*)
        $line = preg_replace('/\*\([^)]*\)\*/i', '', $line);
        if ($GLOBALS['reportMode']) {
            echo("Processing line: $line<BR>");
        }
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        
        // Look for patterns: optional [Conditions] followed by **Keyword**
        // Match at start of line or after reminder text closing: )*
        // Pattern: (^|\)\*\s*)((?:\[[^\]]+\]\s*)*)\*\*([^*]+)\*\*
        if (preg_match_all('/(^|\)\*\s*)((?:\[[^\]]+\]\s*)*)\*\*([^*]+)\*\*/', $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $conditionsPrefix = $match[2]; // Everything before the keyword (conditions)
                $keyword = trim($match[3]); // The keyword itself
                
                // Check if this keyword is in our list
                $matchedKeyword = null;
                foreach ($keywords as $kw) {
                    if (strtolower($keyword) === strtolower($kw['name'])) {
                        $matchedKeyword = $kw;
                        break;
                    }
                }
                
                if ($matchedKeyword) {
                    $entry = [
                        'type' => 'KEYWORD',
                        'name' => $matchedKeyword['name'],
                        'conditions' => [],
                        'value' => null
                    ];
                    
                    // Extract value if keyword has X format (e.g., "Pride 2" or "Pride X")
                    if (preg_match('/(\d+)/', $keyword, $valueMatch)) {
                        $entry['value'] = $valueMatch[1];
                    }
                    
                    // Extract all conditions from the prefix
                    if (!empty($conditionsPrefix)) {
                        if (preg_match_all('/\[([^\]]+)\]/', $conditionsPrefix, $condMatches)) {
                            $conditionMatches = $condMatches[1];
                            
                            // Attach conditions to this keyword
                            foreach ($conditionMatches as $conditionText) {
                                $conditionText = trim($conditionText);
                                
                                // Check if this condition is in our list
                                foreach ($conditions as $cond) {
                                    if (stripos($conditionText, $cond['name']) !== false) {
                                        $condValue = null;
                                        // Extract numeric value if present (e.g., "Level 1+")
                                        if (preg_match('/(\d+)/', $conditionText, $valueMatch)) {
                                            $condValue = $valueMatch[1];
                                        }
                                        
                                        $entry['conditions'][] = [
                                            'name' => $cond['name'],
                                            'value' => $condValue
                                        ];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    
                    $items[] = $entry;
                }
            }
        }
    }
    
    return $items;
}



// Function to generate PHP keyword code from parsed keyword data
function generatePHPCode($keywordData, $keywords, $conditions, $rootName) {
    $php = "<?php\n\n";
    $php .= "// Generated Keyword Code for $rootName\n";
    $php .= "// Generated on: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($keywords as $kw) {
        $kwName = $kw['name'];
        $kwKey = str_replace(' ', '', $kwName); // e.g. FloatingMemory

        // Collect all cards that have this keyword
        $cardEntries = [];
        foreach ($keywordData as $cardId => $cardData) {
            foreach ($cardData['items'] as $item) {
                if ($item['name'] === $kwName) {
                    $cardEntries[$cardId] = [
                        'value' => $item['value'],
                        'conditions' => $item['conditions']
                    ];
                    break;
                }
            }
        }

        if (empty($cardEntries)) {
            continue; // No cards have this keyword, skip
        }

        $php .= "// ===== $kwName =====\n\n";

        // Emit the lookup array
        $php .= "\${$kwKey}_Cards = [\n";
        foreach ($cardEntries as $cardId => $entry) {
            $php .= "    '$cardId' => [\n";
            $php .= "        'value' => " . var_export($entry['value'], true) . ",\n";
            $php .= "        'conditions' => [\n";
            foreach ($entry['conditions'] as $cond) {
                $condValue = var_export($cond['value'], true);
                $php .= "            ['name' => '{$cond['name']}', 'value' => $condValue],\n";
            }
            $php .= "        ]\n";
            $php .= "    ],\n";
        }
        $php .= "];\n\n";

        // Emit HasKeyword function
        $php .= "function HasKeyword_{$kwKey}(\$obj) {\n";
        $php .= "    global \${$kwKey}_Cards;\n";
        $php .= "    \$cardId = \$obj->CardID;\n";
        $php .= "    if (!isset(\${$kwKey}_Cards[\$cardId])) return false;\n";
        $php .= "    \$entry = \${$kwKey}_Cards[\$cardId];\n";
        $php .= "    \$player = \$obj->PlayerID;\n";
        $php .= "    foreach (\$entry['conditions'] as \$cond) {\n";

        foreach ($conditions as $cond) {
            $condName = $cond['name'];
            $evalFn = $cond['evalFunction']; // e.g. GetPlayerLevel($player)
            $isVariable = $cond['isVariable'];

            $php .= "        if (\$cond['name'] === '$condName') {\n";
            if ($isVariable) {
                $php .= "            if (!($evalFn >= \$cond['value'])) return false;\n";
            } else {
                $php .= "            if (!($evalFn)) return false;\n";
            }
            $php .= "        }\n";
        }

        $php .= "    }\n";
        $php .= "    return true;\n";
        $php .= "}\n\n";

        // Emit GetValue function
        $php .= "function GetKeyword_{$kwKey}_Value(\$obj) {\n";
        $php .= "    if (!HasKeyword_{$kwKey}(\$obj)) return null;\n";
        $php .= "    global \${$kwKey}_Cards;\n";
        $php .= "    return \${$kwKey}_Cards[\$obj->CardID]['value'];\n";
        $php .= "}\n\n";
    }

    return $php;
}

// Function to process a single card
function processCard($cardId, $cardName, $cardText, $allKeywords, $allConditions) {
    // Parse keywords and conditions from the card text
    $items = parseKeywordsAndConditions($cardText, $allKeywords, $allConditions);
    
    // Skip if no keywords found
    if (empty($items)) {
        return null;
    }
    
    if ($GLOBALS['reportMode']) {
        echo "<BR><BR>========================================<BR>";
        echo "Processing Card: <strong>$cardName</strong> ($cardId)<BR>";
        echo "Card Text: $cardText<BR>";
        
        $keywordNames = [];
        foreach ($items as $item) {
            $keywordNames[] = $item['name'];
        }
        echo "Keywords found: <em>" . implode(", ", $keywordNames) . "</em><BR>";
        echo "----------------------------------------<BR>";
        echo "Parsed Items: <pre>" . htmlspecialchars(json_encode($items, JSON_PRETTY_PRINT)) . "</pre><BR>";
        
        // Flush output buffer to show progress in real-time
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
    
    return [
        'cardId' => $cardId,
        'cardName' => $cardName,
        'items' => $items
    ];
}

// Process each card
$processedCount = 0;
$skippedCount = 0;
$keywordData = []; // Array to store all card keyword data

if ($reportMode) {
    echo "<h2>Processing Cards</h2>";
}

$allCardIds = GetAllCardIds();
$cardsProcessedInSet = 0;

foreach ($allCardIds as $cardId) {
    // Get the set for this card
    $cardSet = CardSet($cardId);
    
    // Get card effect text
    $cardEffect = CardEffect($cardId);
    
    // Get card name (from card dictionaries)
    $cardName = $cardId; // You may need to get the name from your card dictionary
    
    // Process the card
    $cardData = processCard($cardId, $cardName, $cardEffect, $keywords, $conditions);
    
    if ($cardData !== null) {
        // Add to our results array
        $keywordData[$cardData['cardId']] = [
            'name' => $cardData['cardName'],
            'items' => $cardData['items']
        ];
        
        $processedCount++;
        $cardsProcessedInSet++;
    } else {
        $skippedCount++;
    }
}

echo "<BR><BR>========================================<BR>";
echo "<strong>Summary:</strong><BR>";
echo "Processed: $processedCount cards<BR>";
echo "Skipped: $skippedCount cards (no keywords/conditions found)<BR>";

// Generate output
if ($generatePHPMode) {
    $phpCode = generatePHPCode($keywordData, $keywords, $conditions, $rootName);
    $outputFile = __DIR__ . "/../$rootName/GeneratedCode/GeneratedKeywordCode.php";
    file_put_contents($outputFile, $phpCode);
    echo "<BR><BR><strong>PHP code saved to: $outputFile</strong><BR>";
    if ($reportMode) {
        echo "<BR><strong>Preview of generated PHP:</strong><BR>";
        echo "<pre>" . htmlspecialchars($phpCode) . "</pre>";
    }
} else {
    $jsonOutput = json_encode($keywordData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $outputFile = "GA_Keywords.json";
    file_put_contents($outputFile, $jsonOutput);
    echo "<BR><BR><strong>JSON output saved to: $outputFile</strong><BR>";
    if ($reportMode) {
        echo "<BR><strong>Preview of JSON structure:</strong><BR>";
        echo "<pre>" . htmlspecialchars($jsonOutput) . "</pre>";
    }
}

?>