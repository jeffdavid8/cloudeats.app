To bring WebRTC into your platform alongside your existing AJAX workflows, you need a server-side **Signaling Orchestrator**.

Even though WebRTC data channels move raw packets "hand-to-hand" directly between browsers, the devices *must* initially use a central server to pass their network configuration "business cards" (Offers and Answers) to each other. Your code referred to this as dropping a note in a **Hollow Tree**.

Here is a complete, secure PHP model class named `HubSignalingEngine`. It implements a database-backed "Hollow Tree" signaling coordinator that handles connection pairings between your **Admin, Merchant, Customer, and Courier** roles.

### 1. The PHP Signaling Class (`HubSignalingEngine.php`)

Save this file alongside your other system models. It acts as the transaction broker for creating sessions, passing descriptions, and updating active telemetry pairings.

```php
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

```

---

### 2. Required Database Table Setup

Run this schema patch in your MySQL system to give your new PHP model class a reliable data table to store connection offers:

```sql
CREATE TABLE `neighborhub_webrtc_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `initiator_role` enum('admin','merchant','customer','courier') NOT NULL,
  `initiator_id` int(11) NOT NULL,
  `target_role` enum('admin','merchant','customer','courier') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `offer_sdp` text DEFAULT NULL,
  `answer_sdp` text DEFAULT NULL,
  `status` enum('waiting','offered','answered','connected','closed') DEFAULT 'waiting',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_id` (`session_id`),
  KEY `idx_lookup` (`target_role`, `target_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

```

---

### 3. Creating the API Routing Strategy (`?api=neighborhub`)

In your central backend entry controller file where you manage incoming payloads, you can route actions directly into this signaling engine alongside your legacy AJAX commands:

```php
// Inside your primary POST API Request router loop
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

switch($action) {
    // --- Legacy AJAX Endpoints remain completely untouched ---
    case 'accept_delivery':
        // Handle database assignment logic...
        break;

    // --- New WebRTC Mesh Networking Actions ---
    case 'rtc_create_channel':
        // A courier or admin initiates a private telemetry pipe
        $sid = HubSignalingEngine::createSession($input['my_role'], $input['my_id'], $input['target_role'], $input['target_id'] ?? null);
        echo json_encode(['success' => (bool)$sid, 'session_id' => $sid]);
        exit;

    case 'rtc_post_offer':
        $ok = HubSignalingEngine::postOffer($input['session_id'], $input['offer']);
        echo json_encode(['success' => $ok]);
        exit;

    case 'rtc_check_offers':
        // Target panels call this via rapid interval polls to locate pending connection tracks
        $offers = HubSignalingEngine::findPendingOffers($input['my_role'], $input['my_id']);
        echo json_encode(['success' => true, 'offers' => $offers]);
        exit;

    case 'rtc_post_answer':
        $ok = HubSignalingEngine::postAnswer($input['session_id'], $input['answer'], $input['my_id']);
        echo json_encode(['success' => $ok]);
        exit;

    case 'rtc_get_answer':
        $answer = HubSignalingEngine::getAnswer($input['session_id']);
        echo json_encode(['success' => true, 'answer' => $answer]);
        exit;
}

```

---

### How this maps out in your 4 Frontend Views:

Now that you have this engine orchestrating the handshake, here is how each separate viewport interacts with the architecture:

* **📊 Admin Live Control Map:** On page load, the admin interface loops through all active couriers on the road. It uses `rtc_create_channel` for each courier, submits an offer, and then listens via `rtc_get_answer`. Once established, the admin view receives real-time telemetry packets directly from each courier’s phone browser every 2 seconds without ever querying your database or refreshing.
* **🛵 Courier Delivery View:** The courier runs a tiny background polling loop looking for pending connection assignments targeted at their ID using `rtc_check_offers`. When it finds an offer from an Admin or a Customer tracking their food, the browser automatically signs it, returns an answer via `rtc_post_answer`, and begins streaming GPS data directly down that custom WebRTC connection pipeline.
* **🏪 Merchant Panel:** When a merchant opens an order detail window, they can connect directly to the incoming assigned courier using this handshake. The courier's pin moves across the kitchen dashboard map layout in real-time, allowing staff to time order completion precisely as the vehicle pulls up.
* **📍 Customer Tracker:** The customer panel fetches the specific active session corresponding to their delivery order. Once connected via WebRTC, they receive position updates directly from the courier's device. This gives them an instant, low-latency tracking experience similar to modern delivery apps.



---

### HubMeshNode.js

Here is a generalized JavaScript class named `HubMeshNode`. It is designed to work perfectly alongside the PHP `HubSignalingEngine` class.

This class handles all the complex WebRTC state changes, ICE candidate gathering, and automated AJAX signaling behind the scenes. You can drop this into your shared asset scripts and use it across your **Admin, Merchant, Customer, or Courier** views.

### The JavaScript Mesh Class (`HubMeshNode.js`)

```javascript
class HubMeshNode {
    /**
     * @param {Object} config
     * @param {string} config.role - 'admin', 'courier', 'merchant', or 'customer'
     * @param {number|string} config.id - The system database ID for this user/role
     * @param {Function} config.onMessageReceived - Callback function when a P2P data packet arrives
     * @param {Function} config.onConnectionStateChange - Optional callback to track when a peer connects/disconnects
     */
    constructor(config) {
        this.myRole = config.role;
        this.myId = config.id;
        this.onMessageReceived = config.onMessageReceived || function(msg) {};
        this.onConnectionStateChange = config.onConnectionStateChange || function(state) {};
        
        this.peerConnection = null;
        this.dataChannel = null;
        this.sessionId = null;
        this.pollingInterval = null;
        
        // Free public Google STUN servers to locate public IPs through home/mobile firewalls
        this.rtcConfig = {
            iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
        };
    }

    /**
     * INIT AS INITIATOR (e.g., Admin or Customer wanting to actively connect to a Courier)
     * @param {string} targetRole - The role you want to connect to
     * @param {number|string} targetId - Optional specific ID of the target user
     */
    async initiateConnection(targetRole, targetId = null) {
        console.log(`[Mesh] Creating connection offer to ${targetRole}...`);
        
        // 1. Ask the PHP backend to reserve a "Hollow Tree" session slot
        const initRes = await this._apiCall('rtc_create_channel', {
            my_role: this.myRole,
            my_id: this.myId,
            target_role: targetRole,
            target_id: targetId
        });

        if (!initRes || !initRes.session_id) {
            console.error("[Mesh] Failed to initialize a signaling session on the server.");
            return;
        }

        this.sessionId = initRes.session_id;
        this._setupPeerConnection();

        // 2. Open our side of the data pipe channel
        this.dataChannel = this.peerConnection.createDataChannel("HubFidelityTelemetry");
        this._bindDataChannelEvents();

        // 3. Generate the network layout offer
        const offer = await this.peerConnection.createOffer();
        await this.peerConnection.setLocalDescription(offer);

        // 4. Wait for ICE candidates to settle, then push offer to the PHP backend
        this.peerConnection.onicecandidate = async (e) => {
            if (!e.candidate) {
                const encodedOffer = btoa(JSON.stringify(this.peerConnection.localDescription));
                console.log("[Mesh] Local offer fully gathered. Posting to signaling engine...");
                
                await this._apiCall('rtc_post_offer', {
                    session_id: this.sessionId,
                    offer: encodedOffer
                });

                // 5. Start checking the server to see when the target role submits their Answer
                this._startPollingForAnswer();
            }
        };
    }

    /**
     * INIT AS RECIPIENT (e.g., Courier passively waiting for incoming tracking requests)
     * Call this inside a background timer on panels that need to accept peer tracks.
     */
    async listenForIncomingOffers() {
        const res = await this._apiCall('rtc_check_offers', {
            my_role: this.myRole,
            my_id: this.myId
        });

        if (res && res.offers && res.offers.length > 0) {
            // Take the newest available pairing request
            const pendingOffer = res.offers[0];
            this.sessionId = pendingOffer.session_id;
            
            console.log(`[Mesh] Found a pending offer session (${this.sessionId}) from role: ${pendingOffer.initiator_role}`);
            
            this._setupPeerConnection();

            // Intercept the channel whenever the initiator binds it
            this.peerConnection.ondatachannel = (event) => {
                this.dataChannel = event.channel;
                this._bindDataChannelEvents();
            };

            // Parse and apply the initiator's incoming network address profile
            const rawOffer = atob(pendingOffer.offer_sdp);
            const rtcOffer = new RTCSessionDescription(JSON.parse(rawOffer));
            await this.peerConnection.setRemoteDescription(rtcOffer);

            // Create our corresponding answer layout
            const answer = await this.peerConnection.createAnswer();
            await this.peerConnection.setLocalDescription(answer);

            // Wait for our local candidates to gather, then push the answer back
            this.peerConnection.onicecandidate = async (e) => {
                if (!e.candidate) {
                    const encodedAnswer = btoa(JSON.stringify(this.peerConnection.localDescription));
                    console.log("[Mesh] Local answer gathered. Finalizing handshake via PHP...");
                    
                    await this._apiCall('rtc_post_answer', {
                        session_id: this.sessionId,
                        answer: encodedAnswer,
                        my_id: this.myId
                    });
                }
            };
        }
    }

    /**
     * Broadcasts raw structured JSON data straight to the connected peer over the network card
     * @param {Object} payloadData - Any JS object or telemetry data array
     */
    send(payloadData) {
        if (this.dataChannel && this.dataChannel.readyState === "open") {
            this.dataChannel.send(JSON.stringify(payloadData));
            return true;
        }
        console.warn("[Mesh] Data channel is not open. Cannot transmit packet.");
        return false;
    }

    /**
     * Internal: Connects basic WebRTC listeners
     */
    _setupPeerConnection() {
        this.peerConnection = new RTCPeerConnection(this.rtcConfig);

        this.peerConnection.onconnectionstatechange = () => {
            console.log(`[Mesh] Connection State Altered: ${this.peerConnection.connectionState}`);
            this.onConnectionStateChange(this.peerConnection.connectionState);
            
            if (this.peerConnection.connectionState === "disconnected" || this.peerConnection.connectionState === "failed") {
                this.disconnect();
            }
        };
    }

    /**
     * Internal: Sets up data stream event handlers
     */
    _bindDataChannelEvents() {
        this.dataChannel.onopen = () => {
            console.log("%c[Mesh] THE DIRECT HEART-PIPE MESH IS OPEN AND LIVE! <3", "color: #10b981; font-weight: bold;");
            this.onConnectionStateChange("open");
        };

        this.dataChannel.onmessage = (event) => {
            try {
                const parsedData = JSON.parse(event.data);
                this.onMessageReceived(parsedData);
            } catch (err) {
                console.error("[Mesh] Failed parsing incoming payload:", err);
            }
        };

        this.dataChannel.onclose = () => {
            console.log("[Mesh] Data channel cleanly terminated.");
            this.onConnectionStateChange("closed");
        };
    }

    /**
     * Internal: Poller utility used by the connection initiator
     */
    _startPollingForAnswer() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);

        this.pollingInterval = setInterval(async () => {
            const res = await this._apiCall('rtc_get_answer', { session_id: this.sessionId });
            
            if (res && res.answer) {
                clearInterval(this.pollingInterval);
                console.log("[Mesh] Recipient answer located! Merging network boundaries...");
                
                const rawAnswer = atob(res.answer);
                const rtcAnswer = new RTCSessionDescription(JSON.parse(rawAnswer));
                await this.peerConnection.setRemoteDescription(rtcAnswer);
            }
        }, 10000); // Check every 10 seconds
    }

    /**
     * Cleanly shuts down active tunnels, sockets, and server pollers
     */
    disconnect() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        if (this.dataChannel) this.dataChannel.close();
        if (this.peerConnection) this.peerConnection.close();
        
        this.dataChannel = null;
        this.peerConnection = null;
        console.log("[Mesh] Mesh connection engine offline.");
    }

    /**
     * Standard internal wrapper to route transactions safely to your framework api layer
     */
    _apiCall(action, payload) {
        return new Promise((resolve) => {
            mb.ajax({
                type: 'POST',
                url: `/?api=neighborhub&action=${action}`,
                data: JSON.stringify(payload),
                contentType: 'application/json',
                dataType: 'json',
                success: function(res) { resolve(res); },
                error: function(err) {
                    console.error(`[Mesh Link Error] API Action (${action}) Failed:`, err);
                    resolve(null);
                }
            });
        });
    }
}

```

---

### Implementation Blueprint: How to use it in your layout views

#### Pattern A: The Courier View (Broadcasting Telemetry)

The Courier view runs this inside a standard short background interval to accept incoming tracks from customers or admins looking for their coordinates.

```javascript
// Initialize our local device node
const courierNode = new HubMeshNode({
    role: 'courier',
    id: 42, // Pass the logged-in courierId from PHP
    onMessageReceived: function(msg) {
        console.log("Received data from Admin/Customer:", msg);
    }
});

// Look for incoming pairing requests every 5 seconds
setInterval(() => {
    // If we aren't already actively connected to a peer, check for signaling requests
    if (!courierNode.peerConnection) {
        courierNode.listenForIncomingOffers();
    }
}, 5000);

// Whenever your tracking geolocation watch updates, broadcast it straight down the pipe!
function onLocationWatchUpdate(lat, lng) {
    courierNode.send({
        type: 'GPS_UPDATE',
        latitude: lat,
        longitude: lng,
        timestamp: Date.now()
    });
}

```

#### Pattern B: The Admin Map View (Receiving Telemetry)

The Admin view launches an active tracking handshake targeted at a specific Courier ID:

```javascript
const adminNode = new HubMeshNode({
    role: 'admin',
    id: 1, // Admin User ID
    onMessageReceived: function(msg) {
        if (msg.type === 'GPS_UPDATE') {
            console.log(`Live location update received: ${msg.latitude}, ${msg.longitude}`);
            // 🗺️ Leaflet logic: instantly shift marker pins on screen!
            courierMapMarker.setLatLng([msg.latitude, msg.longitude]);
        }
    },
    onConnectionStateChange: function(state) {
        if (state === 'open') {
            document.getElementById('status-hud').textContent = "CONNECTED LIVE (WebRTC)";
        }
    }
});

// Click a courier's row on the map board to open a live P2P data feed to their phone card
function watchCourierLive(courierId) {
    adminNode.initiateConnection('courier', courierId);
}

```