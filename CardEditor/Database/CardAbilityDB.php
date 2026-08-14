<?php
// CardEditor Database Helper
// Manages all database operations for card abilities

class CardAbilityDB {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        self::EnsureSchema($conn);
    }

    /**
     * Create card_abilities if it is missing, then bring an older table up to date.
     *
     * Mirrors CardAuthoringDB::ensureSchema(). Without the CREATE step the table only ever existed
     * where Database/*.sql ran as a docker initdb hook, so a hand-built environment (LAMPP prod)
     * fataled with "Table '<db>.card_abilities' doesn't exist" on every card-ability query.
     *
     * Safe to call repeatedly: the schema uses CREATE TABLE IF NOT EXISTS and every migration below
     * is guarded by a SHOW COLUMNS check, so existing rows are never touched.
     */
    public static function EnsureSchema($conn, $force = false) {
        if (!$conn) return;

        // Cached per connection AND database, not globally: one process can legitimately bootstrap
        // more than one database, and a plain static bool would silently skip the second.
        static $checked = [];
        $databaseResult = mysqli_query($conn, 'SELECT DATABASE()');
        $databaseRow = $databaseResult ? mysqli_fetch_row($databaseResult) : null;
        if ($databaseResult) mysqli_free_result($databaseResult);
        $cacheKey = spl_object_id($conn) . '|' . (string)($databaseRow[0] ?? '');
        // $force is for the explicit "set up database" action: the operator pressed it precisely
        // because the schema may have changed underneath a cached "already checked".
        if (!$force && isset($checked[$cacheKey])) return;

        $schemaPath = __DIR__ . '/../../Database/card_abilities_schema.sql';
        if (is_file($schemaPath)) {
            foreach (explode(';', (string)file_get_contents($schemaPath)) as $statement) {
                // Strip comment-only lines so an all-comment fragment is not sent as a statement.
                $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement));
                if ($statement === '') continue;
                if (!mysqli_query($conn, $statement)) {
                    error_log('CardAbilityDB::EnsureSchema failed: ' . mysqli_error($conn));
                }
            }
        }

        self::MigrateSchemaColumns($conn);
        $checked[$cacheKey] = true;
    }

    private static function MigrateSchemaColumns($conn) {
        $result = mysqli_query($conn, "SHOW COLUMNS FROM card_abilities LIKE 'card_id'");
        if ($result && ($column = mysqli_fetch_assoc($result))) {
            if (preg_match('/^varchar\\((\\d+)\\)$/i', $column['Type'], $matches) && (int)$matches[1] < 128) {
                mysqli_query($conn, "ALTER TABLE card_abilities MODIFY COLUMN card_id VARCHAR(128) NOT NULL COMMENT 'Card identifier (including canonical asset IDs)'");
            }
        }
        if ($result) mysqli_free_result($result);

        $result = mysqli_query($conn, "SHOW COLUMNS FROM card_abilities LIKE 'prereq_code'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE card_abilities ADD COLUMN prereq_code LONGTEXT NULL AFTER ability_code");
        }
        if ($result) mysqli_free_result($result);

        $result = mysqli_query($conn, "SHOW COLUMNS FROM card_abilities LIKE 'ability_type'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE card_abilities ADD COLUMN ability_type VARCHAR(32) NOT NULL DEFAULT 'macro' AFTER macro_name");
        }
        if ($result) mysqli_free_result($result);

        $result = mysqli_query($conn, "SHOW COLUMNS FROM card_abilities LIKE 'listener_zones'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE card_abilities ADD COLUMN listener_zones TEXT NULL AFTER prereq_code");
        }
        if ($result) mysqli_free_result($result);

        $result = mysqli_query($conn, "SHOW COLUMNS FROM card_abilities LIKE 'is_implemented'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE card_abilities ADD COLUMN is_implemented TINYINT(1) NOT NULL DEFAULT 0 AFTER updated_at");
        }
        if ($result) mysqli_free_result($result);

    }
    
    /**
     * Load all abilities for a specific card
     */
    public function loadCardAbilities($rootName, $cardId) {
        try {
            self::EnsureSchema($this->conn);
            $stmt = mysqli_prepare($this->conn, "
                SELECT id, macro_name, ability_type, ability_code, prereq_code, listener_zones, ability_name, is_implemented, created_at, updated_at
                FROM card_abilities
                WHERE root_name = ? AND card_id = ?
                ORDER BY created_at ASC
            ");
            mysqli_stmt_bind_param($stmt, "ss", $rootName, $cardId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $abilities = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $abilities[] = $row;
            }
            mysqli_stmt_close($stmt);
            return $abilities;
        } catch (Exception $e) {
            error_log("CardAbilityDB::loadCardAbilities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Save a single ability (insert or update)
     * If $id is null, creates new record. Otherwise updates existing.
     */
    public function saveAbility($id, $rootName, $cardId, $macroName, $abilityCode, $prereqCode = null, $abilityName = null, $isImplemented = 0, $abilityType = 'macro', $listenerZones = null) {
        try {
            self::EnsureSchema($this->conn);
            $abilityType = ($abilityType === 'listener') ? 'listener' : 'macro';
            if ($abilityType !== 'listener') $listenerZones = null;
            if ($id === null) {
                // Insert new
                $stmt = mysqli_prepare($this->conn, "
                    INSERT INTO card_abilities (root_name, card_id, macro_name, ability_type, ability_code, prereq_code, listener_zones, ability_name, is_implemented)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "ssssssssi", $rootName, $cardId, $macroName, $abilityType, $abilityCode, $prereqCode, $listenerZones, $abilityName, $isImplemented);
                if (mysqli_stmt_execute($stmt)) {
                    $newId = mysqli_insert_id($this->conn);
                    mysqli_stmt_close($stmt);
                    return $newId;
                }
                mysqli_stmt_close($stmt);
                return false;
            } else {
                // Update existing
                $stmt = mysqli_prepare($this->conn, "
                    UPDATE card_abilities
                    SET macro_name = ?, ability_type = ?, ability_code = ?, prereq_code = ?, listener_zones = ?, ability_name = ?, is_implemented = ?
                    WHERE id = ? AND root_name = ? AND card_id = ?
                ");
                mysqli_stmt_bind_param($stmt, "ssssssiiss", $macroName, $abilityType, $abilityCode, $prereqCode, $listenerZones, $abilityName, $isImplemented, $id, $rootName, $cardId);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result ? $id : false;
            }
        } catch (Exception $e) {
            error_log("CardAbilityDB::saveAbility error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete an ability by ID
     */
    public function deleteAbility($id, $rootName, $cardId) {
        try {
            $stmt = mysqli_prepare($this->conn, "
                DELETE FROM card_abilities
                WHERE id = ? AND root_name = ? AND card_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "iss", $id, $rootName, $cardId);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        } catch (Exception $e) {
            error_log("CardAbilityDB::deleteAbility error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all abilities for a root game and macro name
     * Used by zzGameCodeGenerator to fetch macro implementations
     */
    public function getAbilitiesByMacro($rootName, $macroName) {
        try {
            self::EnsureSchema($this->conn);
            $stmt = mysqli_prepare($this->conn, "
                SELECT card_id, ability_code, prereq_code, ability_name
                FROM card_abilities
                WHERE root_name = ? AND macro_name = ? AND ability_type = 'macro'
                ORDER BY card_id ASC
            ");
            mysqli_stmt_bind_param($stmt, "ss", $rootName, $macroName);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $abilities = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $abilities[] = $row;
            }
            mysqli_stmt_close($stmt);
            return $abilities;
        } catch (Exception $e) {
            error_log("CardAbilityDB::getAbilitiesByMacro error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get listener abilities grouped by observed macro.
     * Listener abilities are dispatched by generated helper code at app-chosen timing.
     */
    public function getListenerAbilities($rootName) {
        try {
            self::EnsureSchema($this->conn);
            $stmt = mysqli_prepare($this->conn, "
                SELECT card_id, macro_name, ability_code, prereq_code, listener_zones, ability_name
                FROM card_abilities
                WHERE root_name = ? AND ability_type = 'listener'
                ORDER BY macro_name ASC, card_id ASC
            ");
            mysqli_stmt_bind_param($stmt, "s", $rootName);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $abilities = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $abilities[] = $row;
            }
            mysqli_stmt_close($stmt);
            return $abilities;
        } catch (Exception $e) {
            error_log("CardAbilityDB::getListenerAbilities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if a card has any existing abilities in the database
     */
    public function cardHasAbilities($rootName, $cardId) {
        try {
            $stmt = mysqli_prepare($this->conn, "
                SELECT COUNT(*) as count
                FROM card_abilities
                WHERE root_name = ? AND card_id = ?
            ");
            mysqli_stmt_bind_param($stmt, "ss", $rootName, $cardId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("CardAbilityDB::cardHasAbilities error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all macros available for a given root game (from schema)
     * This would be called to populate the macro dropdown
     */
    public function getAvailableMacros($rootName) {
        // This should be populated from the GameSchema
        // For now, return empty - will be implemented when we integrate with schema parsing
        return [];
    }
}
