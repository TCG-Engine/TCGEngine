<?php

function FaBValidateDeckForQueue($deckLink, $preconstructedDeck = '', $userID = null, $format = '') {
    $source = trim((string)$deckLink);
    if ($source === '') return ['success' => false, 'message' => 'Paste a Fabrary/FaBDB link, deck JSON, or a text deck list.'];
    $resolved = FaBResolveDeckInput($source, $userID);
    if (!empty($resolved['success']) && $format === 'upf') {
        $errors = FaBUPFDeckErrors($resolved);
        return ['success' => !$errors, 'message' => implode(' ', $errors)];
    }
    return ['success' => !empty($resolved['success']), 'message' => $resolved['message'] ?? ''];
}

function FaBUPFDeckErrors(array $deck): array {
    $errors = [];
    if (!in_array('Young', (array)CardTypes($deck['hero'] ?? ''), true)) $errors[] = 'UPF requires a young hero.';
    if (count($deck['mainDeck'] ?? []) !== 40) $errors[] = 'UPF requires exactly 40 cards in the starting deck.';
    $pool = array_merge($deck['weapons'] ?? [], $deck['equipment'] ?? [], $deck['mainDeck'] ?? [], $deck['inventory'] ?? []);
    if (count($pool) > 52) $errors[] = 'UPF allows at most 52 cards besides the hero.';
    foreach (array_count_values($pool) as $id => $count) {
        if ($count > 2 && !in_array('Unlimited',(array)CardCard_keywords($id),true)) $errors[] = 'UPF allows two copies per pitch: ' . CardName($id) . '.';
        if ($count > 1 && count(array_filter((array)CardCard_keywords($id),fn($keyword)=>str_starts_with($keyword,'Legendary')))>0) $errors[] = 'Legendary allows one copy: ' . CardName($id) . '.';
        $types = (array)CardTypes($id);
        if(in_array('Companion',$types,true)&&!str_starts_with((string)CardName($deck['hero']??''),(string)($types[0]??'')))$errors[]=CardName($id).' requires its named hero.';
        if(($deck['hero']??'')==='emperor_dracai_of_aesir'&&!in_array('Equipment',$types,true)&&!in_array('Weapon',$types,true)&&intval(CardPitch($id))!==1)$errors[]='Emperor requires red cards in the deck.';
        if(($deck['hero']??'')==='shiyana_diamond_gemini'&&str_contains((string)CardFunctional_text_plain($id),'Specialization'))continue;
        foreach(['Rhinar','Bravo','Katsu','Dorinthea','Dash','Azalea','Viserai','Kano','Prism','Boltyn','Levia','Chane','Oldhim','Lexi','Briar','Iyslander','Arakni','Uzuri','Riptide','Benji','Vynnset','Levia','Maxx','Teklovossen','Kayo','Kassai','Betsy','Victor','Olympia','Gravy Bones','Marlynn','Puffin','Scurv','Valda']as$heroName){
            if(str_contains((string)CardFunctional_text_plain($id),$heroName.' Specialization') && !str_starts_with((string)CardName($deck['hero']??''),$heroName))$errors[]=CardName($id).' requires '.$heroName.'.';
        }
        if(str_contains((string)CardFunctional_text_plain($id),'Dromai or Fai Specialization')&&!preg_match('/^(Dromai|Fai)/',(string)CardName($deck['hero']??'')))$errors[]=CardName($id).' requires Dromai or Fai.';
        $classes=['Brute','Guardian','Ninja','Warrior','Mechanologist','Ranger','Runeblade','Wizard','Illusionist','Assassin','Necromancer','Thief'];
        $cardClasses=array_intersect($classes,$types);
        if($cardClasses&&!array_intersect($cardClasses,(array)CardTypes($deck['hero']??'')))$errors[]=CardName($id).' does not match your hero class.';
        $heroText=(string)CardFunctional_text_plain($deck['hero'] ?? '');
        foreach(['Earth','Ice','Lightning'] as $element)if(in_array($element,$types,true)&&stripos($heroText,$element)===false)$errors[]=CardName($id).' requires '.$element.' essence.';
        foreach (['Light','Shadow','Elemental','Draconic','Mystic','Pirate','Revered','Reviled'] as $class) {
            if (in_array($class, $types, true) && !in_array($class, (array)CardTypes($deck['hero'] ?? ''), true)) $errors[] = CardName($id) . ' does not match your hero class.';
        }
    }
    foreach($deck['mainDeck']??[]as$id)if(in_array('Incarnate',(array)CardCard_keywords($id),true)||array_intersect((array)CardTypes($id),['Hero','Demi-Hero','Weapon','Token','Companion','Material','Macro'])||(in_array('Equipment',(array)CardTypes($id),true)&&!in_array('Evo',(array)CardTypes($id),true)))$errors[]=CardName($id).' cannot start in your deck.';
    foreach($deck['equipment']??[] as $id)if(in_array('Evo',(array)CardTypes($id),true))$errors[]='Evos must start in the deck, not equipped.';
    $hands = 0;
    foreach ($deck['weapons'] ?? [] as $id) $hands += in_array('2H', (array)CardTypes($id), true) ? 2 : 1;
    $weaponZones=in_array($deck['hero']??'',['kayo','kayo_armed_and_dangerous','kayo_strong_arm','kayo_underhanded_cheat'],true)?1:2;
    $offhands=count(array_filter($deck['equipment']??[],fn($id)=>in_array('Off-Hand',(array)CardTypes($id),true)&&!in_array('Perched',(array)CardCard_keywords($id),true)));
    if($hands===2&&count(array_filter($deck['equipment']??[],fn($id)=>in_array('Off-Hand',(array)CardTypes($id),true)))>1)$errors[]='A two-handed weapon allows only one perched off-hand.';
    if($hands+$offhands>$weaponZones)$errors[]='Starting weapons and off-hand exceed your hero weapon zones.';
    if ($hands > 2) $errors[] = 'Your starting weapons require more than two hands.';
    $quivers=array_filter(array_merge($deck['weapons']??[],$deck['equipment']??[]),fn($id)=>in_array('Quiver',(array)CardTypes($id),true));
    if(count($quivers)>1)$errors[]='Choose one starting quiver.';
    $bows=array_filter($deck['weapons']??[],fn($id)=>in_array('Bow',(array)CardTypes($id),true));
    if($quivers&&$hands>=2&&!$bows)$errors[]='A quiver needs a vacant weapon slot unless equipped with a bow.';
    foreach (['Head','Chest','Arms','Legs'] as $slot) {
        $equipped = array_filter($deck['equipment'] ?? [], fn($id) => in_array($slot, (array)CardTypes($id), true));
        if (count($equipped) > 1) $errors[] = 'Choose one starting equipment for ' . strtolower($slot) . '.';
    }
    if (!empty($deck['unresolved'])) $errors[] = 'Resolve every unrecognized card before joining.';
    return $errors;
}

function FaBEmptyResolvedDeck($message = '') {
    return ['success' => false, 'message' => $message, 'hero' => '', 'weapons' => [], 'equipment' => [], 'mainDeck' => [], 'inventory' => [], 'unresolved' => []];
}

function FaBResolveDeckInput($input, $userID = null) {
    $input = trim((string)$input);
    if ($input === '') return FaBEmptyResolvedDeck('Deck input is required.');

    if (preg_match('/^fabdeck:(\d+)$/i', $input, $m) && function_exists('FaBDeckResolveOwnedDeck')) {
        return FaBDeckResolveOwnedDeck($m[1], $userID);
    }

    if ($input[0] === '{' || $input[0] === '[') {
        $decoded = json_decode($input, true);
        return FaBNormalizeDeckPayload($decoded);
    }

    if (str_contains($input, "\n") || preg_match('/^\s*\d+\s+.+$/m', $input)) {
        return FaBNormalizeTextDeck($input);
    }

    $fetchMessage = '';
    $payload = FaBFetchPublicDeck($input, $fetchMessage);
    if ($payload === null) return FaBEmptyResolvedDeck($fetchMessage !== '' ? $fetchMessage : 'Could not load that public Fabrary/FaBDB deck. You can paste its exported text list instead.');
    return FaBNormalizeDeckPayload($payload);
}

function FaBConfiguredFabraryKey() {
    $environmentKey = trim((string)getenv('FABRARY_API_KEY'));
    if ($environmentKey !== '') return $environmentKey;

    global $FaBraryKey;
    $keyFile = __DIR__ . '/../../APIKeys/APIKeys.php';
    if (is_file($keyFile)) include_once $keyFile;
    $configuredKey = trim((string)($FaBraryKey ?? ''));
    if ($configuredKey === '' || str_starts_with($configuredKey, 'op://')) return '';
    return $configuredKey;
}

function FaBFetchJsonURL($url, $headers, &$status) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json', 'User-Agent: TCGEngine-FaBSim'], $headers),
    ]);
    $body = curl_exec($ch);
    $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    curl_close($ch);
    if ($body === false || $status < 200 || $status >= 300) return null;
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

function FaBFetchPublicDeck($input, &$message = '') {
    $message = '';
    if (!filter_var($input, FILTER_VALIDATE_URL)) return null;
    $parts = parse_url($input);
    $host = strtolower((string)($parts['host'] ?? ''));
    if (!str_contains($host, 'fabrary.net') && !str_contains($host, 'fabdb.net')) return null;
    $segments = array_values(array_filter(explode('/', trim((string)($parts['path'] ?? ''), '/'))));
    $slug = end($segments);
    if (!is_string($slug) || !preg_match('/^[A-Za-z0-9_-]+$/', $slug)) return null;

    if (str_contains($host, 'fabrary.net')) {
        $apiKey = FaBConfiguredFabraryKey();
        if ($apiKey === '') {
            $message = 'Fabrary requires a server API key even for public decks. Configure $FaBraryKey in APIKeys/APIKeys.php or FABRARY_API_KEY, or paste the exported text list.';
            return null;
        }
        $status = 0;
        $url = 'https://atofkpq0x8.execute-api.us-east-2.amazonaws.com/prod/v1/decks/' . rawurlencode($slug);
        $decoded = FaBFetchJsonURL($url, ['x-api-key: ' . $apiKey, 'Content-Type: application/json'], $status);
        if ($decoded !== null) return $decoded;
        $message = match($status) {
            403 => 'Fabrary rejected the configured API key. Update $FaBraryKey or FABRARY_API_KEY and try again.',
            404 => 'Fabrary could not find that deck. Confirm the deck URL and that it is public.',
            default => 'Fabrary returned HTTP ' . $status . ' while loading the deck. You can paste its exported text list instead.',
        };
        return null;
    }

    $status = 0;
    $decoded = FaBFetchJsonURL('https://api.fabdb.net/decks/' . rawurlencode($slug), [], $status);
    if ($decoded !== null) return $decoded;
    $message = $status === 404
        ? 'FaBDB could not find that deck. Confirm the deck URL and that it is public.'
        : 'FaBDB returned HTTP ' . $status . ' while loading the deck. You can paste its exported text list instead.';
    return null;
}

function FaBCardLookup() {
    static $lookup = null;
    if ($lookup !== null) return $lookup;
    $lookup = [];
    foreach (GetAllCardIds() as $cardID) {
        $lookup[strtolower((string)$cardID)] = $cardID;
        $name = strtolower(trim((string)CardName($cardID)));
        $pitch = intval(CardPitch($cardID));
        if ($name !== '') {
            $lookup[$name . '|' . $pitch] = $cardID;
            if (!isset($lookup[$name])) $lookup[$name] = $cardID;
        }
        $printing = strtolower(trim((string)CardPrinting_id($cardID)));
        if ($printing !== '') $lookup[$printing] = $cardID;
    }
    return $lookup;
}

function FaBResolveCardReference($value, $pitch = null) {
    if (is_array($value)) {
        $pitch = $pitch ?? ($value['pitch'] ?? $value['color'] ?? null);
        $value = $value['identifier'] ?? $value['cardIdentifier'] ?? $value['card_id'] ?? $value['id'] ?? $value['name'] ?? $value['card']['name'] ?? '';
    }
    $value = trim((string)$value);
    if ($value === '') return '';
    $lookup = FaBCardLookup();
    $key = str_replace('-', '_', strtolower($value));
    $pitchNumber = is_numeric($pitch) ? intval($pitch) : match(strtolower((string)$pitch)) {'red' => 1, 'yellow' => 2, 'blue' => 3, default => 0};
    if ($pitchNumber > 0 && isset($lookup[$key . '|' . $pitchNumber])) return $lookup[$key . '|' . $pitchNumber];
    if (isset($lookup[$key])) return $lookup[$key];
    return $lookup[$key . '|' . $pitchNumber] ?? '';
}

function FaBNormalizeTextDeck($text) {
    $result = FaBEmptyResolvedDeck();
    $section = 'mainDeck';
    foreach (preg_split('/\R/', (string)$text) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $header = strtolower(rtrim($line, ':'));
        if (in_array($header, ['hero','heroes'], true)) {$section='hero'; continue;}
        if (in_array($header, ['weapon','weapons'], true)) {$section='weapons'; continue;}
        if (in_array($header, ['equipment'], true)) {$section='equipment'; continue;}
        if (in_array($header, ['inventory','sideboard'], true)) {$section='inventory'; continue;}
        if (in_array($header, ['deck','main deck','cards'], true)) {$section='mainDeck'; continue;}
        if (!preg_match('/^(\d+)\s*[xX]?\s+(.+?)(?:\s+\((red|yellow|blue)\))?$/i', $line, $m)) continue;
        $qty = max(1, intval($m[1]));
        $cardID = FaBResolveCardReference($m[2], $m[3] ?? null);
        if ($cardID === '') {$result['unresolved'][] = $line; continue;}
        if ($section === 'hero') $result['hero'] = $cardID;
        else for ($i=0;$i<$qty;++$i) $result[$section][] = $cardID;
    }
    return FaBFinalizeResolvedDeck($result);
}

function FaBNormalizeDeckPayload($payload) {
    if (!is_array($payload)) return FaBEmptyResolvedDeck('Deck data was not valid JSON.');
    foreach (['data','deck'] as $wrapper) if (isset($payload[$wrapper]) && is_array($payload[$wrapper])) $payload = $payload[$wrapper];
    if (isset($payload['cards']) && is_array($payload['cards']) && array_is_list($payload['cards'])) {
        foreach ($payload['cards'] as $row) {
            if (is_array($row) && (array_key_exists('total', $row) || array_key_exists('sideboardTotal', $row))) {
                return FaBNormalizeTalisharDeckPayload($payload);
            }
        }
    }
    $result = FaBEmptyResolvedDeck();
    $sections = ['hero'=>'hero','heroes'=>'hero','weapon'=>'weapons','weapons'=>'weapons','equipment'=>'equipment','equipments'=>'equipment','cards'=>'mainDeck','mainDeck'=>'mainDeck','main_deck'=>'mainDeck','deckCards'=>'mainDeck','inventory'=>'inventory','sideboard'=>'inventory'];
    foreach ($sections as $source => $dest) {
        if (!isset($payload[$source])) continue;
        $rows = is_array($payload[$source]) && array_is_list($payload[$source]) ? $payload[$source] : [$payload[$source]];
        foreach ($rows as $row) {
            $qty = is_array($row) ? intval($row['quantity'] ?? $row['count'] ?? 1) : 1;
            $cardID = FaBResolveCardReference($row);
            if ($cardID === '') {$result['unresolved'][] = is_scalar($row) ? (string)$row : json_encode($row); continue;}
            if ($dest === 'hero') $result['hero'] = $cardID;
            else for ($i=0;$i<max(1,$qty);++$i) $result[$dest][] = $cardID;
        }
    }
    return FaBFinalizeResolvedDeck($result);
}

function FaBNormalizeTalisharDeckPayload($payload) {
    $result = FaBEmptyResolvedDeck();
    // Current Fabrary exports use separate main/sideboard counts. Older exports
    // included sideboardTotal in total; preserve those unless the data disproves it.
    $separateCounts=!empty($payload['separateSideboardCounts']);
    foreach(($payload['cards']??[]) as $row)if(is_array($row)&&intval($row['sideboardTotal']??0)>intval($row['total']??0))$separateCounts=true;
    foreach (($payload['cards'] ?? []) as $row) {
        if (!is_array($row)) continue;
        $cardID = FaBResolveCardReference($row);
        if ($cardID === '') {
            $result['unresolved'][] = json_encode($row);
            continue;
        }

        $total = max(0, intval($row['total'] ?? $row['quantity'] ?? $row['count'] ?? 1));
        $sideboard = max(0, intval($row['sideboardTotal'] ?? 0));
        if(!$separateCounts)$sideboard=min($total,$sideboard);
        $main = $separateCounts?$total:$total-$sideboard;
        $types = CardTypes($cardID);
        if (!is_array($types)) $types = [];

        if (in_array('Hero', $types, true)) {
            if ($main > 0 && $result['hero'] === '') $result['hero'] = $cardID;
            for ($i = 0; $i < $sideboard; ++$i) $result['inventory'][] = $cardID;
        } elseif (in_array('Weapon', $types, true)) {
            for ($i = 0; $i < $main; ++$i) $result['weapons'][] = $cardID;
            for ($i = 0; $i < $sideboard; ++$i) $result['inventory'][] = $cardID;
        } elseif ((in_array('Equipment', $types, true)&&!in_array('Evo',$types,true))) {
            for ($i = 0; $i < $main; ++$i) $result['equipment'][] = $cardID;
            for ($i = 0; $i < $sideboard; ++$i) $result['inventory'][] = $cardID;
        } else {
            for ($i = 0; $i < $main; ++$i) $result['mainDeck'][] = $cardID;
            for ($i = 0; $i < $sideboard; ++$i) $result['inventory'][] = $cardID;
        }
    }
    return FaBFinalizeResolvedDeck($result);
}

function FaBFinalizeResolvedDeck($result) {
    foreach(array_merge($result['mainDeck']??[],$result['inventory']??[]) as $id){
        if($id==='inner_chi_blue'){$result['success']=false;$result['message']='Inner Chi is a transformed card. Use its front face in your deck.';return $result;}
        if($id==='dragons_of_legend'){$result['success']=false;$result['message']='Replace Dragons of Legend with the invocation card it represents in your deck list.';return $result;}
        if(in_array('Mentor',(array)CardTypes($id),true)&&!in_array('Young',(array)CardTypes($result['hero']??''),true)){
            $result['success']=false;$result['message']='Mentor cards require a young hero.';return $result;
        }
        if(in_array('Ephemeral',(array)CardCard_keywords($id),true)){
            $result['success']=false;
            $result['message']=CardName($id).' cannot start in your deck or inventory (Ephemeral).';
            return $result;
        }
    }
    if ($result['hero'] === '' || empty($result['mainDeck'])) {
        $result['message'] = 'The deck needs a recognized hero and at least one main-deck card.';
        return $result;
    }
    $result['success'] = true;
    $result['message'] = empty($result['unresolved']) ? '' : ('Imported with ' . count($result['unresolved']) . ' unresolved line(s).');
    return $result;
}

?>
