<?php
/**
 * Variant printing -> base card resolution.
 *
 * Some games print one card several times under different collector numbers (Hellbreak: a base
 * number, a borderless 2xx, alt-art 4xx). Every printing plays identically, so abilities, reviewed
 * data and games use ONE ID: the base card's. A root that has variants ships
 * <root>/GeneratedCode/CardBaseMap.json ({"baseCards": {"DOT_262": "DOT_062"}}), written by that
 * root's card importer. Roots without the file resolve every ID to itself.
 *
 * Read by the CardEditor ability endpoints, the hosted Card Code Service and deck import, none of
 * which load card dictionaries. McpServer/src/tools.ts reads the same file.
 */

function CardBaseMapSetOverride(string $rootName, ?array $map): void
{
    $GLOBALS['__cardBaseMapOverrides'][$rootName] = $map;
    unset($GLOBALS['__cardBaseMapCache'][$rootName]);
}

function CardBaseMapSetEngineRoot(?string $engineRoot): void
{
    $GLOBALS['__cardBaseMapEngineRoot'] = $engineRoot;
    $GLOBALS['__cardBaseMapCache'] = [];
}

function CardBaseMapPath(string $rootName): ?string
{
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $rootName)) return null;
    $engineRoot = $GLOBALS['__cardBaseMapEngineRoot'] ?? dirname(__DIR__);
    return $engineRoot . DIRECTORY_SEPARATOR . $rootName . DIRECTORY_SEPARATOR . 'GeneratedCode' . DIRECTORY_SEPARATOR . 'CardBaseMap.json';
}

/** @return array<string,string> upper-cased variant ID => base ID */
function CardBaseMap(string $rootName): array
{
    $overrides = $GLOBALS['__cardBaseMapOverrides'] ?? [];
    if (array_key_exists($rootName, $overrides) && $overrides[$rootName] !== null) {
        $source = $overrides[$rootName];
    } else {
        if (isset($GLOBALS['__cardBaseMapCache'][$rootName])) return $GLOBALS['__cardBaseMapCache'][$rootName];
        $path = CardBaseMapPath($rootName);
        $payload = ($path !== null && is_file($path)) ? json_decode((string)file_get_contents($path), true) : null;
        $source = is_array($payload['baseCards'] ?? null) ? $payload['baseCards'] : [];
    }
    $map = [];
    foreach ($source as $variantID => $baseID) {
        $map[strtoupper(trim((string)$variantID))] = trim((string)$baseID);
    }
    $GLOBALS['__cardBaseMapCache'][$rootName] = $map;
    return $map;
}

/** The ID abilities and games use for $cardID: its base card, or itself. */
function ResolveBaseCardID(string $rootName, string $cardID): string
{
    $cardID = trim($cardID);
    return CardBaseMap($rootName)[strtoupper($cardID)] ?? $cardID;
}

/** For responses: the ID that was used, the ID that was asked for, and whether they differ. */
function CardBaseResolution(string $rootName, string $cardID): array
{
    $requested = trim($cardID);
    $resolved = ResolveBaseCardID($rootName, $requested);
    return ['cardId' => $resolved, 'requestedCardId' => $requested, 'isVariant' => $resolved !== $requested];
}
