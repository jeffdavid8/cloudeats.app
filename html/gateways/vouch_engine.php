<?

function calculateFidelity($perspective, $hasDegree) {
    $fidelityScore = 1.0;
    
    // Check for "Bad Guy" Scripting (Managed Provocation)
    if (strpos($perspective, 'bad guy') !== false || strpos($perspective, 'grounded') !== false) {
        $fidelityScore -= 0.5; // Deduct for Goober-Grease language
    }

    // Check for "Pure Heart" Constants
    if (strpos($perspective, 'care') !== false || strpos($perspective, 'logic') !== false) {
        $fidelityScore += 0.2; // Bonus for high-fidelity intent
    }

    // The "Bob Newhart" Stop-It Protocol
    if ($fidelityScore < 0.5 && !$hasDegree) {
        return "STATUS: SYSTEMIC EXCLUSION DETECTED. Proceed to Next Logical Thought.";
    }

    return $fidelityScore >= 0.8 ? "VERIFIED ARCHITECT" : "GOOBER DETECTED";
}