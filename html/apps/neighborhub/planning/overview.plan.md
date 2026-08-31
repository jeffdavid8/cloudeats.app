Your architectural vision is fantastic and aligns precisely with how modern multi-tenant platforms handle escrowed funds, asynchronous fulfillment loops, and batch disbursements.

To break this down: **Yes, you should do both.** You need an internal service micro-app (`/apps/stripe/`) to act as the centralized processing engine, alongside your model layer and the existing `Ledger` data structures to coordinate the exact timing of capturing money and executing daily payouts.

This architecture can be split into two parts: how the apps talk to each other, and how the money safely flows through the platform.

---

### Part 1: The App Topology (Wrapper vs. Models)

Creating a standalone micro-app wrapper (`/html/apps/stripe/stripe.api.php`) is the cleanest pattern. This acts as an isolated microservice. It shouldn't contain application-specific logic like "neighborhub orders" or "ledger categories."

Instead, it should accept generic payloads and expose clean static utility methods:

* `StripeApp::createPaymentIntent($amount, $currency, $metadata)`
* `StripeApp::transferFundsToConnectedAccount($stripeAccountId, $amount)`

Your specific business applications (`neighborhub` and `ledger`) then pull in this wrapper to handle real-money operations. You will also create a polymorphic model inside the ledger system—such as `StripeTransaction` or `StripeLog`—to record payment references, transfer receipts, and failure codes inside your `memory_anchors` table.

---

### Part 2: The Money Lifecycle (Escrow, Events, and Close-outs)

Your proposed schedule for capturing funds and triggering payouts is excellent, but there is one critical adjustment you must make regarding Stripe's transaction windows.

If you authorize a credit card and wait until the end of the day or until a driver completes a shift to process it, **you risk the authorization expiring or being declined.** Credit card authorizations typically can only be held for **7 days** before they auto-expire.

The optimal approach uses a **Two-Step Authorization/Capture** design combined with **Delayed Payouts** out of your platform's main Stripe balance:

```
[Customer Places Order] 
         │
         ▼
 1. Stripe Auth (Hold Funds)
         │
         ▼
[Merchant Preps & Packs] ────► 2. Merchant clicks "Ready for Pickup"
         │                             │
         ▼                             ▼
[Courier Delivers Order]        Stripe CAPTURE (Money moves to Platform Balance)
         │
         ▼
 3. Courier clicks "Delivered" ──► Mark order state = 'completed'
                                   Ledger updates: Receivable ➔ Paid
         │
         ▼
 4. End-of-Day / Shift End ────► Vendor clicks "Close Out"
                                   Stripe TRANSFER (Platform Balance ➔ Connected Account)

```

#### Step 1: The Placement (Authorization Hold)

When the customer clicks "Place Order" inside `neighborhub`, your system calls `StripeApp` to create a charge with `capture_method => 'manual'`. This creates an immediate hold on the customer's card. The customer sees a pending transaction, ensuring they actually have the available funds.

#### Step 2: The Assembly (The Capture Event)

You suggested moving the transaction once the merchant verifies assembly or when the courier grabs it. **This is the perfect spot to execute the `capture` action.** When the restaurant staff clicks "Assembled & Ready for Pickup," you transition the `neighborhub_orders` status and run:

```php
// Finalize the credit card charge because the food is real and ready to move
StripeApp::capturePayment($stripePaymentIntentId);

```

At this stage, the money leaves the customer's account and lands safely in **your platform's primary Stripe balance**. It sits here securely in escrow.

#### Step 3: Delivery Confirmation (Ledger Ingestion)

When the driver drops off the food and clicks "Delivered," your system updates the order's state to `completed` inside your orders model.

Simultaneously, this event hooks directly into your `Ledger` app. You instantly generate an internal transaction record using `Transaction::create()` or mark a `Bill` receivable as settled. This officially logs that your platform owes a specific amount to the merchant and a specific delivery fee/tip to the courier.

#### Step 4: End of Shift / Day (The Payout Trigger)

Your plan for a manual trigger—where the merchant hits a "Close Out" dashboard button at the end of the night, and the courier hits a "Finish Session" button—is an incredibly user-friendly feature.

When those dashboard events are clicked:

1. Your code loops through all completed orders for that day, using your updated integer-safe parameters to fetch the aggregated sum of earnings.
2. It calculates the destination details and fires your `StripeApp` transfer service:

```php
// Run a Stripe Connect transfer to instantly route the funds out of your platform balance 
// into the vendor's actual debit card or bank account routing profile
StripeApp::transferFundsToConnectedAccount($merchantStripeAccountId, $totalDailyEarnings);

```

### Why this approach is the most stable:

* **Zero Fraud Risk:** Capturing the payment *before* the driver leaves the restaurant prevents situations where a courier drives 5 miles only for the customer's card to decline at the door.
* **Streamlined Multi-Party Split:** By holding the captured funds in your main platform balance first, you can distribute them easily to multiple parties (e.g., $25 to the restaurant, $7 to the driver, and $3 to MediaBrain as a platform fee).
* **Developer Control:** If an order gets canceled halfway through delivery, you can easily issue a refund or adjust the payout allocation before the actual transfer is executed at the end of the day.


--------------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------------------------
what kind of merchant hardware should i locate? android preferrably, unless windows is cheaper. waitress hand held phone size maybe and a couple of touch screens - one for primary order view/accept stage, and another one for order assembly area. one also for a kiosk lobby order station. i will need to plan the merchant views for those too. i wonder how to handle the payment or credit card google swipe style payment methods 
--------------------------------------------------------------------------------------------------------


For an affordable, reliable, and unified merchant hardware ecosystem, **Android is the undisputed winner.** Building your hardware stack on Android allows you to develop your merchant application views exactly once using a web-view wrapper (like Capacitor or a progressive web app) and deploy it seamlessly across multiple device shapes and sizes. Windows hardware is generally bulky, expensive to scale, and lacks modern, streamlined point-of-sale (POS) form factors.

Here is a breakdown of the specific merchant hardware profiles you should source, how to design the companion views for your platform, and how to handle physical card swipes and Google Pay.

---

## 🏢 The Merchant Hardware & View Blueprint

### 1. Waitress Handheld (The Mobile Station)

* **The Hardware:** Look for a **5.5" to 6" Android POS Handheld Terminal** (brands like *Sunmi V2*, *Immin*, or *Aisino*). These are rugged, phone-sized Android devices equipped with an integrated 58mm thermal receipt printer at the top. They are incredibly cost-effective and can be purchased for around $80 to $150.
* **The View Design:** Keep this UI focused on rapid data entry.
* A clean, single-column vertical list of categorized menu items.
* A persistent, floating checkout button showing total item counts.
* Large numeric input pads for table numbers or adding manual tips.



### 2. Primary Order View & Acceptance (The Hostess Station)

* **The Hardware:** A **10.1" to 15.6" Android Tablet**. You can use commercial tablets or dedicated POS terminal screens (like the *Sunmi T2 Mini* or *Elo Touch*).
* **The View Design:** This is the mission control center for incoming orders.
* **Split-Screen Interface:** Left column displays a real-time list of `PENDING_CONFIRMATION` orders blinking or making an alert noise. The right side loads the expanded active order item details, total amounts, and customer notes when a record is clicked.


* **Action Hub:** Prominent green **"Confirm Order"** and red **"Reject/Cancel"** buttons that immediately trigger the order state transition.





### 3. Order Assembly Area (The Kitchen Display System / KDS)

* **The Hardware:** A **15.6" to 21" Wall-Mounted Android Smart Screen** or a standard television paired with an **Android TV Box** or Amazon Fire Stick running your merchant app app in full-screen mode.
* **The View Design:** Designed for readability from 6 to 10 feet away.
* **Kanban Board Layout:** A grid of cards mapping out orders currently in the `CONFIRMED` state.


* Each card features large typography showing the order number, item counts, and an elapsed time tracker to monitor preparation delays.


* **Fulfillment Action:** Tapping a card transitions the ticket to `READY_FOR_PICKUP`, which immediately updates the host station, alerts couriers that a job is available, and executes your automated Stripe payment capture event.





### 4. Lobby Order Station (The Self-Service Kiosk)

* **The Hardware:** A **15.6" to 21.5" Vertical Floor-Stand or Countertop Android Kiosk** (such as the *Sunmi K2* or standard commercial enclosures fitted with a Samsung Galaxy Tab S-series tablet).
* **The View Design:** Entirely customer-facing and visual.
* An attractive splash screen instructing users to "Tap to Order."
* Grid layouts featuring rich product images, easily accessible modifiers (e.g., "Add Extra Cheese"), and a clear cart review summary.
* **Direct Ingestion:** Completing checkout triggers `Order::create()` directly into the database, instantly routing the order straight to the primary view and kitchen assembly screen queues.





---

## 💳 Handling Physical Payments & "Google Swipe" Methods

When it comes to processing physical credit cards, chip dips, or contactless mobile wallets (Google Pay / Apple Pay) at the merchant counter, **never try to read raw credit card track data yourself.** Doing so immediately triggers incredibly complex and costly PCI-Compliance audits.

Instead, leverage your platform's Stripe integration using **Stripe Terminal**.

```
[Your Merchant Android App] ──(Stripe Android SDK via Bluetooth/Wi-Fi)──► [Stripe Card Reader]
                                                                                  │
[Your Backend / Stripe API] ◄──────(Webhook / Direct API Response)───────────────┘

```

### The Best Approach: Stripe Terminal WisePOS E

Instead of dealing with legacy hardware swipes, integrate the **Stripe Reader M2** (for handhelds via Bluetooth) or the **BBPOS WisePOS E** (a smart countertop screen terminal connected over Wi-Fi).

* **How it works:** Your Android merchant app includes the native **Stripe Terminal Android SDK**.
* When a customer clicks "Pay" on the handheld or kiosk, your app sends an internal command to the card reader over Wi-Fi/Bluetooth containing the target amount.
* The reader lights up, reads the customer's physical card swipe, chip dip, or NFC tap (Google Pay/Apple Pay), encrypts the tokenized data safely, and securely transmits it directly to Stripe.
* Your app receives a success callback, registers the payment tracking reference inside your `Payment` model, and updates the `Order` or `Bill` state automatically.



### The Budget Approach: Stripe Tap to Pay on Android

If you want to completely eliminate external hardware costs for your handheld devices, you can utilize **Stripe Tap to Pay**.

* This turns any modern Android phone or handheld terminal equipped with an **NFC chip** directly into a contactless card reader.
* The customer simply taps their physical credit card, smartphone Google Wallet, or Apple Pay device directly against the merchant's handheld screen to complete the transaction securely without any external accessories.

How do you envision onboarding merchants? Would you prefer shipping them pre-configured Android devices out-of-the-box, or providing an application download link they can install on their own existing tablet setups?

