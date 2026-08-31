<?php

class HubSignalingEngine
{
    /**
     * Creates a new signaling channel session (e.g., when an Admin opens a tracking window)
     * * @param string $initiatorRole ('admin', 'merchant', 'customer', 'courier')
     * @param int $initiatorId
     * @param string $targetRole
     * @param int|null $targetId
     * @return string Generated Session ID
     */
    public static function createSession($initiatorRole, $initiatorId, $targetRole, $targetId = null)
    {
        try {
            $db = App::getInstance('neighborhub')->db;
            $sessionId = bin2hex(random_bytes(16)); // Secure random alphanumeric string

            $stmt = $db->prepare("
                INSERT INTO neighborhub_webrtc_sessions 
                (session_id, initiator_role, initiator_id, target_role, target_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'waiting', NOW())
            ");
            
            $stmt->execute([
                $sessionId, 
                trim($initiatorRole), 
                intval($initiatorId), 
                trim($targetRole), 
                $targetId ? intval($targetId) : null
            ]);

            return $sessionId;
        } catch (Exception $e) {
            error_log("HubSignalingEngine::createSession Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Step 1 (The Offer): The initiator posts their local Base64 SDP network profile
     */
    public static function postOffer($sessionId, $encodedOffer)
    {
        try {
            $db = App::getInstance('neighborhub')->db;
            
            $stmt = $db->prepare("
                UPDATE neighborhub_webrtc_sessions 
                SET offer_sdp = ?, status = 'offered', updated_at = NOW() 
                WHERE session_id = ? AND status = 'waiting'
            ");
            
            return $stmt->execute([trim($encodedOffer), trim($sessionId)]);
        } catch (Exception $e) {
            error_log("HubSignalingEngine::postOffer Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Step 2 (The Discovery): Target loops/polls to see if an offer is waiting for their role
     */
    public static function findPendingOffers($targetRole, $targetId)
    {
        try {
            $db = App::getInstance('neighborhub')->db;
            
            $stmt = $db->prepare("
                SELECT session_id, initiator_role, initiator_id, offer_sdp 
                FROM neighborhub_webrtc_sessions 
                WHERE target_role = ? AND (target_id = ? OR target_id IS NULL) 
                AND status = 'offered'
                ORDER BY created_at DESC LIMIT 5
            ");
            
            $stmt->execute([trim($targetRole), intval($targetId)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("HubSignalingEngine::findPendingOffers Exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Step 3 (The Answer): The recipient accepts the session and drops their answer SDP back
     */
    public static function postAnswer($sessionId, $encodedAnswer, $targetId)
    {
        try {
            $db = App::getInstance('neighborhub')->db;
            
            $stmt = $db->prepare("
                UPDATE neighborhub_webrtc_sessions 
                SET answer_sdp = ?, target_id = ?, status = 'answered', updated_at = NOW() 
                WHERE session_id = ? AND status = 'offered'
            ");
            
            return $stmt->execute([trim($encodedAnswer), intval($targetId), trim($sessionId)]);
        } catch (Exception $e) {
            error_log("HubSignalingEngine::postAnswer Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Step 4 (The Completion): Initiator checks if the target peer answered the pipe
     */
    public static function getAnswer($sessionId)
    {
        try {
            $db = App::getInstance('neighborhub')->db;
            
            $stmt = $db->prepare("
                SELECT answer_sdp, status FROM neighborhub_webrtc_sessions 
                WHERE session_id = ?
            ");
            $stmt->execute([trim($sessionId)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['status'] === 'answered') {
                // Instantly update status to connected to cleanly close the signaling loop
                $update = $db->prepare("UPDATE neighborhub_webrtc_sessions SET status = 'connected' WHERE session_id = ?");
                $update->execute([trim($sessionId)]);
                return $row['answer_sdp'];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("HubSignalingEngine::getAnswer Exception: " . $e->getMessage());
            return null;
        }
    }
}