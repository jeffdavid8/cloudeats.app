<?php
if (!defined('MB_RUNNING')) exit;
?>

<div id="hub-admin-map" style="height: 650px; width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"></div>

<script>
  class LiveHubTracker {
    constructor(elementId) {
      this.map = L.map(elementId).setView([40.3866, -85.4561], 12);
      this.layerGroup = L.layerGroup().addTo(this.map);
      this.geocodingCache = {};
      this.hasAdjustedView = false;

      // 🗺️ Visual Marker Reference Registry Buckets
      this.courierMarkers = {};
      this.merchantMarkers = {};
      this.customerMarkers = {};
      this.activeMeshConnections = {}; // Track mesh instances mapped to couriers

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(this.map);

      this.icons = {
        merchant: L.divIcon({
          className: 'map-pin-merchant',
          html: '<span style="font-size: 35px; display: block; text-align: center; line-height: 60px;">🏪</span>',
          iconSize: [60, 60],
          iconAnchor: [30, 30]
        }),
        courier: L.divIcon({
          className: 'map-pin-courier',
          html: '<span style="font-size: 30px; display: block; text-align: center; line-height: 60px; transition: transform 200ms linear;">🚚</span>',
          iconSize: [60, 60],
          iconAnchor: [30, 30]
        }),
        customer: L.divIcon({
          className: 'map-pin-customer',
          html: '<span style="font-size: 20px; display: block; text-align: center; line-height: 60px;">📍</span>',
          iconSize: [60, 60],
          iconAnchor: [30, 30]
        })
      };

      this.initAdminMeshEngine();
      this.startPollingFallback();
    }

    /**
     * 🚀 Connect the Admin view to the WebRTC Mesh Infrastructure
     */
    initAdminMeshEngine() {
      console.log("[Admin Map] Initializing Direct Hand-to-Hand Mesh Orchestrator...");
      this.syncMetrics();
    }

    startPollingFallback() {
      // Relax static baseline map queries to 30 seconds since live data travels via WebRTC
      setInterval(() => this.syncMetrics(), 30000);
    }

    syncMetrics() {
      mb.ajax({
        url: '/?api=neighborhub&action=get_admin_live_tracking_metrics',
        type: 'GET',
        success: (response) => {
          if (response && response.success) {
            const merchants = response.merchants || [];
            const couriers = response.couriers || [];
            const orders = response.orders || [];

            this.plotMerchants(merchants);
            this.plotCouriers(couriers);
            this.plotOrders(orders, merchants);

            // Connect lines to any active courier on the field
            this.bindWebRTCTunnelsToCouriers(couriers);

            if (!this.hasAdjustedView && merchants.length > 0) {
              const activeMerchant = merchants.find(m => m.latitude && m.longitude);
              if (activeMerchant) {
                this.map.setView([activeMerchant.latitude, activeMerchant.longitude], 13);
                this.hasAdjustedView = true;
              }
            }
          }
        }
      });
    }

    /**
     * Runs through all detected drivers and creates real-time mesh data pipelines to them
     */
    bindWebRTCTunnelsToCouriers(couriers) {
      couriers.forEach(courier => {
        const cid = courier.id;

        // Setup a tunnel link only if we aren't currently tracking this driver's session
        if (!this.activeMeshConnections[cid]) {
          console.log(`[Admin Map Mesh] Initiating hardware tracking channel to Courier #${cid}`);

          this.activeMeshConnections[cid] = new HubMeshNode({
            role: 'admin',
            id: 1, // Global Admin ID
            onMessageReceived: (msg) => {
              if (msg.type === 'COURIER_GPS_STREAM') {
                console.log('COURIER_GPS_STREAM', [msg.latitude, msg.longitude]);

                this.animateCourierMarkerLive(msg.courierId, msg.latitude, msg.longitude);
              }
              if (msg.type === 'ORDER_STATUS_CHANGED') {
                M.toast({
                  html: `🚨 Order #${msg.orderId} status shifted: ${msg.status}`
                });
                this.syncMetrics(); // Fetch core tracking parameters
              }
            },
            // 🔌 THE HEALING COUPLER ADDITION
            onConnectionStateChange: (state) => {
              console.log(`[Admin Map] Courier #${cid} network state: ${state}`);

              // If the courier closed their tab, refreshed, or dropped cell signal:
              if (state === "disconnected" || state === "failed" || state === "closed") {
                console.warn(`[Admin Map] Connection to Courier #${cid} lost. Cleaning registry slot...`);

                // 1. Terminate the dead WebRTC instance object safely
                if (this.activeMeshConnections[cid]) {
                  this.activeMeshConnections[cid].disconnect();
                }

                // 2. Wipe it from memory so the system knows it needs a new handshake
                delete this.activeMeshConnections[cid];

                // 3. Force an immediate baseline check to restitch the pipe right away!
                setTimeout(() => this.syncMetrics(), 1000);
              }
            }
          });

          // Launch connection offer handshake targeting this specific driver node
          this.activeMeshConnections[cid].initiateConnection('courier', cid);
        }
      });
    }

    /**
     * 🚀 HIGH FIDELITY ANIMATION ENGINE
     * Smoothly glides courier pins on screen maps without refreshing layers
     */
    animateCourierMarkerLive(courierId, lat, lng) {
      const marker = this.courierMarkers[courierId];
      if (marker) {
        console.log(`[P2P Packet Match] Shifting pin for driver ${courierId} to: ${lat}, ${lng}`);
        // Leaflet's native smooth setLatLng interpolation layout update
        marker.setLatLng([parseFloat(lat), parseFloat(lng)]);
      } else {
        // If the marker doesn't exist yet, spawn it natively
        this.courierMarkers[courierId] = L.marker([parseFloat(lat), parseFloat(lng)], {
          icon: this.icons.courier
        }).addTo(this.layerGroup);
      }
    }

    plotMerchants(merchants) {
      merchants.forEach(m => {
        if (m.latitude && m.longitude && !this.merchantMarkers[m.id]) {
          this.merchantMarkers[m.id] = L.marker([m.latitude, m.longitude], {
              icon: this.icons.merchant
            })
            .bindPopup(`<b>Merchant:</b> ${m.business_name}<br><b>Addr:</b> ${m.address}`)
            .addTo(this.layerGroup);
        }
      });
    }

    plotCouriers(couriers) {
      couriers.forEach(c => {
        if (c.latitude !== null && c.longitude !== null && !isNaN(c.latitude) && !isNaN(c.longitude)) {
          if (!this.courierMarkers[c.id]) {
            this.courierMarkers[c.id] = L.marker([parseFloat(c.latitude), parseFloat(c.longitude)], {
                icon: this.icons.courier
              })
              .bindPopup(`<b>Courier:</b> ${c.business_name}<br><b>Status:</b> ${c.status}`)
              .addTo(this.layerGroup);
          }
        }
      });
    }

    async plotOrders(orders, merchants) {
      for (const o of orders) {
        if (this.customerMarkers[o.id]) continue; // Skip if already mapped out

        const merchant = merchants.find(m => m.id == o.merchant_id);
        if (!merchant || !merchant.latitude) continue;

        const customerCoords = await this.geocodeAddress(o.delivery_address);

        if (customerCoords) {
          this.customerMarkers[o.id] = L.marker([customerCoords.lat, customerCoords.lng], {
              icon: this.icons.customer
            })
            .bindPopup(`<b>Order #${o.order_number}</b><br><b>Status:</b> ${o.state}`)
            .addTo(this.layerGroup);

          L.polyline([
            [merchant.latitude, merchant.longitude],
            [customerCoords.lat, customerCoords.lng]
          ], {
            color: '#FF5733',
            weight: 3,
            opacity: 0.7,
            dashArray: '6, 6'
          }).addTo(this.layerGroup);
        }
      }
    }

    async geocodeAddress(address) {
      if (this.geocodingCache[address]) return this.geocodingCache[address];
      try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(address)}`);
        const results = await response.json();
        if (results && results.length > 0) {
          const coords = {
            lat: parseFloat(results[0].lat),
            lng: parseFloat(results[0].lon)
          };
          this.geocodingCache[address] = coords;
          return coords;
        }
      } catch (e) {
        // Graceful fallback logging
      }
      return null;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    new LiveHubTracker('hub-admin-map');
  });
</script>