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
    this.onMessageReceived = config.onMessageReceived || function (msg) {};
    this.onConnectionStateChange =
      config.onConnectionStateChange || function (state) {};

    this.peerConnection = null;
    this.dataChannel = null;
    this.sessionId = null;
    this.pollingInterval = null;

    // Free public Google STUN servers to locate public IPs through home/mobile firewalls
    // Inside the HubMeshNode constructor inside HubMeshNode.js
    this.rtcConfig = {
      iceServers: [
        {
          // TURN server acts as the absolute bulletproof fallback layer
          urls: "turn:3.22.57.169:3478",
          username: "neighborhub-service",
          credential: "Qx8#vT2$mL9^pW5!kR3&",
        },
        { urls: "stun:stun.l.google.com:19302" }, // STUN tries first (Free & Fast)
      ],
    };
    this.rtcConfig = {
      iceServers: [
        { urls: "stun:stun.l.google.com:19302" }],
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
    const initRes = await this._apiCall("rtc_create_channel", {
      my_role: this.myRole,
      my_id: this.myId,
      target_role: targetRole,
      target_id: targetId,
    });

    if (!initRes || !initRes.session_id) {
      console.error(
        "[Mesh] Failed to initialize a signaling session on the server.",
      );
      return;
    }

    this.sessionId = initRes.session_id;
    this._setupPeerConnection();

    // 2. Open our side of the data pipe channel
    this.dataChannel = this.peerConnection.createDataChannel(
      "HubFidelityTelemetry",
    );
    this._bindDataChannelEvents();

    // 3. Generate the network layout offer
    const offer = await this.peerConnection.createOffer();
    await this.peerConnection.setLocalDescription(offer);

    // 4. Wait for ICE candidates to settle, then push offer to the PHP backend
    this.peerConnection.onicecandidate = async (e) => {
      if (!e.candidate) {
        const encodedOffer = btoa(
          JSON.stringify(this.peerConnection.localDescription),
        );
        console.log(
          "[Mesh] Local offer fully gathered. Posting to signaling engine...",
        );

        await this._apiCall("rtc_post_offer", {
          session_id: this.sessionId,
          offer: encodedOffer,
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
    const res = await this._apiCall("rtc_check_offers", {
      my_role: this.myRole,
      my_id: this.myId,
    });

    if (res && res.offers && res.offers.length > 0) {
      // Take the newest available pairing request
      const pendingOffer = res.offers[0];
      this.sessionId = pendingOffer.session_id;

      console.log(
        `[Mesh] Found a pending offer session (${this.sessionId}) from role: ${pendingOffer.initiator_role}`,
      );

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
          const encodedAnswer = btoa(
            JSON.stringify(this.peerConnection.localDescription),
          );
          console.log(
            "[Mesh] Local answer gathered. Finalizing handshake via PHP...",
          );

          await this._apiCall("rtc_post_answer", {
            session_id: this.sessionId,
            answer: encodedAnswer,
            my_id: this.myId,
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
      console.log(
        `[Mesh] Connection State Altered: ${this.peerConnection.connectionState}`,
      );
      this.onConnectionStateChange(this.peerConnection.connectionState);

      if (
        this.peerConnection.connectionState === "disconnected" ||
        this.peerConnection.connectionState === "failed"
      ) {
        this.disconnect();
      }
    };
  }

  /**
   * Internal: Sets up data stream event handlers
   */
  _bindDataChannelEvents() {
    this.dataChannel.onopen = () => {
      console.log(
        "%c[Mesh] THE DIRECT HEART-PIPE MESH IS OPEN AND LIVE! <3",
        "color: #10b981; font-weight: bold;",
      );
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
      const res = await this._apiCall("rtc_get_answer", {
        session_id: this.sessionId,
      });

      if (res && res.answer) {
        clearInterval(this.pollingInterval);
        console.log(
          "[Mesh] Recipient answer located! Merging network boundaries...",
        );

        const rawAnswer = atob(res.answer);
        const rtcAnswer = new RTCSessionDescription(JSON.parse(rawAnswer));
        await this.peerConnection.setRemoteDescription(rtcAnswer);
      }
    }, 2500); // Check every 2.5 seconds
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
        type: "POST",
        url: `/?api=neighborhub&action=${action}`,
        data: JSON.stringify(payload),
        contentType: "application/json",
        dataType: "json",
        success: function (res) {
          resolve(res);
        },
        error: function (err) {
          console.error(
            `[Mesh Link Error] API Action (${action}) Failed:`,
            err,
          );
          resolve(null);
        },
      });
    });
  }
}
