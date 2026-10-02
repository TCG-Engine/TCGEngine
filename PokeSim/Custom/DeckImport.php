<?php
const POKE_SET_CODES = ['PBL' => 'me05', 'ASC' => 'me02.5', 'SSP' => 'sv08', 'CRI' => 'me04',
    'POR' => 'me03', 'BLK' => 'sv10.5b', 'WHT' => 'sv10.5w', 'JTG' => 'sv09', 'MEE' => 'mee',
    'TEF'=>'sv05', 'PFL'=>'me02', 'SCR'=>'sv07', 'DRI'=>'sv10', 'MEG'=>'me01', 'SVI'=>'sv01', '30C'=>'30th'];

function PokeParseDeckText(string $text, bool $verify = true): array {
    $entries = [];
    foreach (preg_split('/\R/u', trim($text)) as $line) {
        $line = trim($line);
        if ($line === '' || preg_match('/^(Pokémon|Pokemon|Trainer|Energy|Total Cards|\*|#)/u', $line)) continue;
        if (preg_match('/^(\d+)\s+([a-z][a-z0-9._]*-[A-Za-z0-9]+)$/D', $line, $m)) {
            $count = (int)$m[1]; $id = $m[2]; $name = null;
        } elseif (preg_match('/^(\d+)\s+(.+)\s+([A-Z0-9]+)\s+(\d+)$/uD', $line, $m)) {
            $count = (int)$m[1]; $name = $m[2]; $set = POKE_SET_CODES[$m[3]] ?? null;
            if ($set === null && function_exists('CardSet')) {
                global $setData, $localIdData, $nameData;
                foreach ($setData as $candidate => $candidateSet) {
                    if (strcasecmp($candidateSet, $m[3]) === 0 && (int)$localIdData[$candidate] === (int)$m[4]) { $set = $candidateSet; break; }
                }
            }
            if ($set === null) throw new InvalidArgumentException("Unknown set code {$m[3]}; use a TCGdex printing ID");
            $id = $set . '-' . (isset(POKE_SET_CODES[$m[3]]) ? str_pad($m[4], 3, '0', STR_PAD_LEFT) : $m[4]);
        } else throw new InvalidArgumentException("Unrecognized deck line: $line");
        // Limitless MEE 13 is not in TCGdex's eight-card MEE catalog. Resolve
        // this basic Energy to its equivalent MEE 5 record; retain the export.
        if ($id === 'mee-013' && $name === 'Psychic Energy') $id = 'mee-005';
        if ($count < 1 || $count > 60) throw new InvalidArgumentException('Invalid card quantity');
        if ($verify && (CardName($id) === null || ($name !== null && PokeNormalizeName(CardName($id)) !== PokeNormalizeName($name)))) {
            throw new InvalidArgumentException("Card name/printing mismatch or missing import: $line ($id)");
        }
        $entries[] = ['id' => $id, 'count' => $count];
    }
    return $entries;
}

function PokeNormalizeName(string $name): string { return mb_strtolower(str_replace(['’', 'é', 'É'], ["'", 'e', 'E'], trim($name)), 'UTF-8'); }

function PokeValidateDeck(array $entries, bool $requireImplemented = true): array {
    $errors = []; $total = 0; $basic = 0; $names = []; $aceSpec = 0;
    foreach ($entries as $entry) {
        $id = $entry['id']; $count = (int)$entry['count']; $total += $count;
        if (CardName($id) === null) { $errors[] = "Unknown card $id"; continue; }
        $key = PokeNormalizeName(CardName($id));
        $names[$key] = ($names[$key] ?? 0) + $count;
        if (CardType($id) === 'Pokemon' && CardStage($id) === 'Basic') $basic += $count;
        if (CardRarity($id) === 'ACE SPEC Rare') $aceSpec += $count;
        if (!(CardType($id) === 'Energy' && CardEnergyType($id) === 'Normal') && $names[$key] > 4) $errors[] = "More than 4 copies of " . CardName($id);
        if ($requireImplemented && !PokeCardImplemented($id)) $errors[] = "Unimplemented card: " . CardName($id) . " ($id)";
    }
    if ($total !== 60) $errors[] = "Deck must have 60 cards (has $total)";
    if (!$basic) $errors[] = 'Deck needs at least one Basic Pokémon';
    if ($aceSpec > 1) $errors[] = 'Deck may have at most one ACE SPEC card';
    return array_values(array_unique($errors));
}
