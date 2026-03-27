<?php
// CardEditor Database Helper
// Manages all database operations for card abilities

class CardAbilityDB {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function ensurePrereqColumn() {
        static $checked = false;
        if ($checked) return;

        $result = mysqli_query($this->conn, "SHOW COLUMNS FROM card_abilities LIKE 'prereq_code'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($this->conn, "ALTER TABLE card_abilities ADD COLUMN prereq_code LONGTEXT NULL AFTER ability_code");
        }
        if ($result) mysqli_free_result($result);
        $checked = true;
    }
    
    /**
     * Load all abilities for a specific card
     */
    public function loadCardAbilities($rootName, $cardId) {
        try {
            $this->ensurePrereqColumn();
            $stmt = mysqli_prepare($this->conn, "
                SELECT id, macro_name, ability_code, prereq_code, ability_name, is_implemented, created_at, updated_at
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
    public function saveAbility($id, $rootName, $cardId, $macroName, $abilityCode, $prereqCode = null, $abilityName = null, $isImplemented = 0) {
        try {
            $this->ensurePrereqColumn();
            if ($id === null) {
                // Insert new
                $stmt = mysqli_prepare($this->conn, "
                    INSERT INTO card_abilities (root_name, card_id, macro_name, ability_code, prereq_code, ability_name, is_implemented)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, "ssssssi", $rootName, $cardId, $macroName, $abilityCode, $prereqCode, $abilityName, $isImplemented);
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
                    SET macro_name = ?, ability_code = ?, prereq_code = ?, ability_name = ?, is_implemented = ?
                    WHERE id = ? AND root_name = ? AND card_id = ?
                ");
                mysqli_stmt_bind_param($stmt, "ssssiiss", $macroName, $abilityCode, $prereqCode, $abilityName, $isImplemented, $id, $rootName, $cardId);
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
            $this->ensurePrereqColumn();
            $stmt = mysqli_prepare($this->conn, "
                SELECT card_id, ability_code, prereq_code, ability_name
                FROM card_abilities
                WHERE root_name = ? AND macro_name = ?
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
