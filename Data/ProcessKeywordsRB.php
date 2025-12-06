<?php

// Increase max execution time to 1 hour (3600 seconds)
set_time_limit(3600);


include_once "../Core/LLMAPI.php";

$cardJsonFile = "RB.json";

// Load and parse the JSON file
$jsonContent = file_get_contents($cardJsonFile);
$cardsData = json_decode($jsonContent, true);

if (!$cardsData || !isset($cardsData['data'])) {
    die("Error: Could not load or parse $cardJsonFile");
}

$keywords = ["Shield", "Assault", "Tank", "Action", "Reaction", "Hidden", "Accelerate", "Legion", "Deflect", "Ganking", "Temporary", "Deathknell", "Vision"];

// Function to get keywords present in the card text
function getKeywordsInText($text, $keywords) {
    $foundKeywords = [];
    
    if (empty($text)) {
        return $foundKeywords;
    }
    
    $textLower = strtolower($text);
    foreach ($keywords as $keyword) {
        if (stripos($textLower, strtolower($keyword)) !== false) {
            $foundKeywords[] = $keyword;
        }
    }
    return $foundKeywords;
}

// Function to parse AI result into structured data
function parseKeywordResult($aiResult) {
    $keywords = [];
    
    // Split by lines
    $lines = explode("\n", trim($aiResult));
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines or lines that say "N/A" (no keywords)
        if (empty($line) || strtoupper($line) === 'N/A') {
            continue;
        }
        
        // Expected format: [Keyword]: [Value or 'N/A']: [self/others]
        $parts = explode(':', $line);
        
        if (count($parts) >= 3) {
            $keyword = trim($parts[0]);
            $value = trim($parts[1]);
            $applicability = trim($parts[2]);
            
            // Normalize value - if it says "N/A", set to null
            if (strtoupper($value) === 'N/A') {
                $value = null;
            }
            
            $keywords[] = [
                'keyword' => $keyword,
                'value' => $value,
                'applicability' => $applicability
            ];
        }
    }
    
    return $keywords;
}

// Function to process a single card
function processCard($card, $allKeywords) {
    $cardName = $card['name'] ?? 'Unknown';
    $cardId = $card['id'] ?? 'Unknown';
    $cardText = $card['effect'] ?? '';
    
    // Get only the keywords that appear in this card's text
    $cardKeywords = getKeywordsInText($cardText, $allKeywords);
    
    echo "<BR><BR>========================================<BR>";
    echo "Processing Card: <strong>$cardName</strong> ($cardId)<BR>";
    echo "Card Text: $cardText<BR>";
    echo "Keywords found in text: <em>" . implode(", ", $cardKeywords) . "</em><BR>";
    echo "----------------------------------------<BR>";
    
    $prompt = "You are a card game keyword analyzer. Extract keywords from the card text below.

AVAILABLE KEYWORDS: " . implode(", ", $cardKeywords) . "

TASK:
1. Identify which keywords appear in the card text
2. For 'X' keywords (like Shield X, Assault X), extract the numeric value
3. Determine if the keyword applies to 'self' (this unit) or 'others' (other units)
4. If the effect references keywords but does not have or grant keywords, respond with 'N/A' and nothing else.

OUTPUT FORMAT (one per line):
[Keyword]: [Value or 'N/A']: [self/others]

EXAMPLES:
Shield: 1: self
Assault: 2: others
Tank: N/A: self

CARD TEXT:
" . $cardText . "

OUTPUT:";
    
    //$result = OpenAICall($prompt, $model="gpt-4o-mini");
    //$result = OpenAICall($prompt, $model="gpt-5-nano");
    //$result = OpenAICall($prompt, $model="gpt-5-mini");
    $result = OpenAICall($prompt, $model="gpt-4.1");
    echo "Result: <BR>$result<BR>";
    
    // Parse the result
    $parsedKeywords = parseKeywordResult($result);
    
    // Flush output buffer to show progress in real-time
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
    
    return [
        'cardId' => $cardId,
        'cardName' => $cardName,
        'keywords' => $parsedKeywords
    ];
}

// Process each card
$processedCount = 0;
$skippedCount = 0;
$keywordData = []; // Array to store all card keyword data

echo "<h2>Processing Cards from $cardJsonFile</h2>";

foreach ($cardsData['data'] as $card) {
    $cardText = $card['effect'] ?? '';
    
    // Get keywords present in this card's text
    $cardKeywords = getKeywordsInText($cardText, $keywords);
    
    // Only process if the card contains at least one keyword
    if (!empty($cardKeywords)) {
        $cardData = processCard($card, $keywords);
        
        // Add to our results array
        $keywordData[$cardData['cardId']] = [
            'name' => $cardData['cardName'],
            'keywords' => $cardData['keywords']
        ];
        
        $processedCount++;
        //if($processedCount == 10) break;
        
        // Optional: Add a small delay to avoid rate limiting
        // usleep(100000); // 0.1 second delay
    } else {
        $skippedCount++;
    }
}

echo "<BR><BR>========================================<BR>";
echo "<strong>Summary:</strong><BR>";
echo "Processed: $processedCount cards<BR>";
echo "Skipped: $skippedCount cards (no keywords found)<BR>";

// Generate and save JSON output
$jsonOutput = json_encode($keywordData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$outputFile = "RB_Keywords.json";
file_put_contents($outputFile, $jsonOutput);

echo "<BR><BR><strong>JSON output saved to: $outputFile</strong><BR>";
echo "<BR><strong>Preview of JSON structure:</strong><BR>";
echo "<pre>" . htmlspecialchars($jsonOutput) . "</pre>";

?>