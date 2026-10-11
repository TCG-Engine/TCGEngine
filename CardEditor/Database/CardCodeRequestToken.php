<?php

// Authorization can be hidden from PHP by some web server configurations. The dedicated
// Card Code header avoids that dependency while existing bearer clients keep working.
function CardCodeRequestToken(array $server, array $headers = []): string
{
    $custom = trim((string)($server['HTTP_X_CARD_CODE_TOKEN'] ?? ''));
    if ($custom !== '') return $custom;

    foreach ($headers as $name => $value) {
        if (strcasecmp((string)$name, 'X-Card-Code-Token') === 0) {
            $custom = trim((string)$value);
            if ($custom !== '') return $custom;
        }
    }

    $authorization = trim((string)($server['HTTP_AUTHORIZATION'] ?? $server['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
    if ($authorization === '') {
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'Authorization') === 0) {
                $authorization = trim((string)$value);
                break;
            }
        }
    }
    return preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) ? trim($matches[1]) : '';
}
