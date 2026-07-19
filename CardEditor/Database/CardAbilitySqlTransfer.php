<?php

final class CardAbilitySqlTransfer
{
    private const HEADER = '-- TCGEngine card abilities export v1';
    private const INSERT_PREFIX = 'INSERT INTO `card_abilities` (`root_name`, `card_id`, `macro_name`, `ability_type`, `ability_code`, `prereq_code`, `listener_zones`, `ability_name`, `is_implemented`) VALUES (';

    public static function export(string $rootName, array $rows): string
    {
        self::assertRootName($rootName);

        $rootToken = self::sqlString($rootName);
        $lines = [
            self::HEADER,
            '-- root-name: ' . $rootName,
            '-- generated-at: ' . gmdate('c'),
            '-- This dump replaces card_abilities rows for this root only.',
            'START TRANSACTION;',
            'DELETE FROM `card_abilities` WHERE `root_name` = ' . $rootToken . ';',
        ];

        foreach ($rows as $row) {
            if ((string)($row['root_name'] ?? '') !== $rootName) {
                throw new InvalidArgumentException('Export row belongs to a different root');
            }

            $tokens = [
                self::sqlString($row['root_name']),
                self::sqlString($row['card_id'] ?? ''),
                self::sqlString($row['macro_name'] ?? ''),
                self::sqlString($row['ability_type'] ?? 'macro'),
                self::sqlString($row['ability_code'] ?? ''),
                self::sqlNullableString($row['prereq_code'] ?? null),
                self::sqlNullableString($row['listener_zones'] ?? null),
                self::sqlNullableString($row['ability_name'] ?? null),
                !empty($row['is_implemented']) ? '1' : '0',
            ];
            $lines[] = self::INSERT_PREFIX . implode(', ', $tokens) . ');';
        }

        $lines[] = 'COMMIT;';
        return implode("\r\n", $lines) . "\r\n";
    }

    public static function import(string $sql, string $expectedRootName): array
    {
        self::assertRootName($expectedRootName);
        if (strlen($sql) > 25 * 1024 * 1024) {
            throw new InvalidArgumentException('Import file exceeds the 25 MB limit');
        }

        $rawLines = preg_split('/\R/', $sql);
        $lines = [];
        foreach ($rawLines as $line) {
            $line = trim($line);
            if ($line !== '') $lines[] = $line;
        }

        if (($lines[0] ?? '') !== self::HEADER) {
            throw new InvalidArgumentException('Not a supported TCGEngine card ability export');
        }
        if (($lines[1] ?? '') !== '-- root-name: ' . $expectedRootName) {
            throw new InvalidArgumentException('The import file is for a different app');
        }

        $expectedDelete = 'DELETE FROM `card_abilities` WHERE `root_name` = ' . self::sqlString($expectedRootName) . ';';
        $sawStart = false;
        $sawDelete = false;
        $sawCommit = false;
        $rows = [];

        foreach (array_slice($lines, 2) as $line) {
            if (str_starts_with($line, '-- ')) continue;
            if ($line === 'START TRANSACTION;' && !$sawStart && !$sawDelete && !$sawCommit) {
                $sawStart = true;
                continue;
            }
            if ($line === $expectedDelete && $sawStart && !$sawDelete && !$sawCommit) {
                $sawDelete = true;
                continue;
            }
            if ($line === 'COMMIT;' && $sawStart && $sawDelete && !$sawCommit) {
                $sawCommit = true;
                continue;
            }
            if (str_starts_with($line, self::INSERT_PREFIX) && $sawStart && $sawDelete && !$sawCommit) {
                $rows[] = self::parseInsert($line, $expectedRootName);
                continue;
            }
            throw new InvalidArgumentException('The import contains an unsupported or out-of-order SQL statement');
        }

        if (!$sawStart || !$sawDelete || !$sawCommit) {
            throw new InvalidArgumentException('The import is incomplete');
        }
        return $rows;
    }

    private static function parseInsert(string $line, string $expectedRootName): array
    {
        if (!str_ends_with($line, ');')) {
            throw new InvalidArgumentException('Malformed card ability INSERT statement');
        }
        $body = substr($line, strlen(self::INSERT_PREFIX), -2);
        $tokens = explode(', ', $body);
        if (count($tokens) !== 9) {
            throw new InvalidArgumentException('Card ability INSERT has an unexpected column count');
        }

        $values = [];
        for ($i = 0; $i < 8; $i++) {
            $values[] = self::decodeStringToken($tokens[$i], $i >= 5);
        }
        if ($tokens[8] !== '0' && $tokens[8] !== '1') {
            throw new InvalidArgumentException('Invalid implementation flag in card ability INSERT');
        }
        if ($values[0] !== $expectedRootName) {
            throw new InvalidArgumentException('An imported row belongs to a different app');
        }

        return [
            'root_name' => $values[0],
            'card_id' => $values[1],
            'macro_name' => $values[2],
            'ability_type' => $values[3],
            'ability_code' => $values[4],
            'prereq_code' => $values[5],
            'listener_zones' => $values[6],
            'ability_name' => $values[7],
            'is_implemented' => (int)$tokens[8],
        ];
    }

    private static function sqlString($value): string
    {
        return "FROM_BASE64('" . base64_encode((string)$value) . "')";
    }

    private static function sqlNullableString($value): string
    {
        return $value === null ? 'NULL' : self::sqlString($value);
    }

    private static function decodeStringToken(string $token, bool $nullable): ?string
    {
        if ($nullable && $token === 'NULL') return null;
        if (!preg_match("/^FROM_BASE64\\('([A-Za-z0-9+\\/=]*)'\\)$/", $token, $matches)) {
            throw new InvalidArgumentException('Invalid encoded string in card ability INSERT');
        }
        $decoded = base64_decode($matches[1], true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64 data in card ability INSERT');
        }
        return $decoded;
    }

    private static function assertRootName(string $rootName): void
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $rootName)) {
            throw new InvalidArgumentException('Invalid app root name');
        }
    }
}

