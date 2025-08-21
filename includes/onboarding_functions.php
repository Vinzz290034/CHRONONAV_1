<?php
// CHRONONAV_WEB_DOSS/includes/onboarding_functions.php

/**
 * Fetches onboarding steps from the database for a given user role.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $user_role The role of the current user ('admin', 'faculty', or 'user').
 * @return array An array of onboarding steps, or an empty array on failure.
 */
function getOnboardingSteps($pdo, $user_role) {
    $steps = [];
    $sql = "SELECT title, content FROM onboarding_steps WHERE role = ? ORDER BY step_order ASC";
    
    try {
        // Use PDO prepared statement
        $stmt = $pdo->prepare($sql);
        
        // Bind the parameter and execute
        $stmt->execute([$user_role]);
        
        // Fetch all results
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Failed to fetch onboarding steps: " . $e->getMessage());
        // You might want to return an empty array or throw an exception here
        $steps = [];
    }
    
    return $steps;
}
?>