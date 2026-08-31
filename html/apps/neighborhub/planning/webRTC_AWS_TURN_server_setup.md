You can set up a free TURN server by either deploying an open-source server on a free cloud hosting tier or by using a free managed commercial tier. Because TURN servers relay heavy media traffic, true "perpetually free" public servers do not exist, but these options cost nothing for development and testing.
## Option 1: Use a Free Managed Service (Easiest)
Several cloud communication providers offer a generous free allowance of TURN/STUN bandwidth. This eliminates the need to configure server instances or handle Linux firewalls.

* Metered: Offers Metered Global TURN Server with 50 GB of free data per month, which is more than enough for development and testing.
* [Xirsys](https://xirsys.com/): Provides a free tier on Xirsys Cloud that includes 500 MB of data per month, ideal for basic connectivity checks.
* Twilio: While primarily paid, [Twilio Network Traversal](https://www.twilio.com/en-us/stun-turn) offers a free trial credit that lets you generate ephemeral tokens for STUN/TURN use.

## Option 2: Deploy Coturn on a Free Cloud Tier (Full Control)
If you want to host your own server without paying, you can install Coturn (the industry-standard open-source TURN server) on a cloud provider's free virtual machine.

   1. Sign up for a free cloud VM: Use the [Oracle Cloud Free Tier](https://www.oracle.com/cloud/free/) (which gives you up to two free AMD Compute VMs perpetually) or the [AWS Free Tier](https://aws.amazon.com/free/) (free t2.micro/t3.micro instance for 12 months). Select a Ubuntu Linux image.
   2. Install Coturn: SSH into your free cloud instance and install the package:
   
   sudo apt update && sudo apt install coturn -y
   
   3. Open the cloud firewalls: In your cloud provider's web console (Security Lists or Security Groups), open the following incoming ports:
   * UDP & TCP 3478 (Standard STUN/TURN)
      * UDP & TCP 5349 (Secure TLS STUN/TURN)
      * UDP 49152 to 65535 (Dynamic media relay ports)
   4. Configure Coturn: Edit the configuration file using sudo nano /etc/turnserver.conf and uncomment/modify these key lines:
   
   listening-port=3478
   fingerprint
   lt-cred-mech
   user=myusername:mypassword
   realm=your_public_ip_or_domain
   total-quota=100
   bps-capacity=0
   
   5. Enable and Start: Edit /etc/default/coturn and set TURNSERVER_ENABLED=1. Then restart the service:
   
   sudo systemctl restart coturn
   
   
## How to Format the Server in Your WebRTC Code
Once you have your credentials or your managed service URL, plug it directly into your frontend RTCConfiguration object:

const configuration = {
  iceServers: [
    {
      urls: 'stun:your-turn-domain-or-ip.com:3478'
    },
    {
      urls: 'turn:your-turn-domain-or-ip.com:3478',
      username: 'neighborhub-service',
      credential: 'mypassword'
    }
  ]
};
const peerConnection = new RTCPeerConnection(configuration);

Which path works better for your workflow: a managed service API that you can integrate in 5 minutes, or hosting your own dedicated server?

