<?php
/**
 * 🚀 PROMO VIEW - The "Sovereign" Landing Page
 * This page celebrates the offer and lets the user select their "Warp Speed."
 */
$promo = $data['promo'] ?? null;
$service = $data['service'] ?? null; // Loaded via Service::getByUuid
$tiers = $service->tiers ?? [];
?>

<div class="promo-container">
    <div class="promo-header">
        <h1 class="lush-text"><?= htmlspecialchars($service->service_name) ?></h1>
        <p class="ledger-statement"><?= htmlspecialchars($service->service_description) ?></p>
    </div>

    <div class="tier-grid">
        <?php foreach ($tiers as $tier_key => $tier): ?>
            <div class="tier-card <?= $tier_key === 'silver' ? 'featured' : '' ?>">
                <?php if ($tier_key === 'silver'): ?>
                    <div class="tier-badge">MOST POPULAR</div>
                <?php endif; ?>
                
                <h2 class="tier-title"><?= ucfirst($tier_key) ?></h2>
                <div class="tier-price">
                    <span class="currency">$</span>
                    <span class="amount"><?= $tier['price'] ?></span>
                    <span class="period">/mo</span>
                </div>

                <ul class="tier-features">
                    <?php foreach ($tier['features'] as $feature): ?>
                        <li><i class="fas fa-check"></i> <?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="payment-actions">
                    <button class="buy-button" 
                            data-tier="<?= $tier_key ?>" 
                            data-price="<?= $tier['price'] ?>"
                            onclick="initiateWarpDrive(this)">
                        Select <?= ucfirst($tier_key) ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function initiateWarpDrive(btn) {
        const tier = $(btn).data('tier');
        const price = $(btn).data('price');
        
        console.log("Stitching Membership: " + tier + " at $" + price);
        
        /* STITCH POINT: 
           1. Call your membership.api.php to create a 'pending' membership.
           2. Redirect to PayPal/Stripe with the resulting ID.
        */
        alert("Warp Drive Initialized for " + tier + " tier! (Stitch your payment gateway here, Jeff!)");
    }
    </script>
</div>

<style>
/* "Church" Black & "Warp Core" Green Theme */
.promo-container { padding: 60px 20px; text-align: center; background: #000; min-height: 100vh; }
.lush-text { color: #00ff41; font-size: 3.5rem; text-transform: uppercase; letter-spacing: 5px; margin-bottom: 10px; }
.ledger-statement { color: #888; font-family: 'Courier New', monospace; margin-bottom: 50px; }

.tier-grid { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; }
.tier-card { 
    background: #0a0a0a; 
    border: 1px solid #333; 
    padding: 40px; 
    width: 300px; 
    position: relative;
    transition: transform 0.3s ease, border-color 0.3s ease;
}
.tier-card:hover { transform: translateY(-10px); border-color: #00ff41; }
.tier-card.featured { border: 2px solid #00ff41; box-shadow: 0 0 20px rgba(0, 255, 65, 0.2); }

.tier-badge { 
    position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
    background: #00ff41; color: #000; padding: 5px 15px; font-weight: bold; font-size: 0.8rem;
}

.tier-title { color: #fff; text-transform: uppercase; letter-spacing: 2px; }
.tier-price { margin: 25px 0; color: #00ff41; }
.tier-price .amount { font-size: 3rem; font-weight: bold; }

.tier-features { list-style: none; padding: 0; text-align: left; color: #bbb; margin-bottom: 40px; }
.tier-features li { margin-bottom: 12px; font-size: 0.9rem; }
.tier-features i { color: #00ff41; margin-right: 10px; }

.buy-button {
    background: transparent; border: 2px solid #00ff41; color: #00ff41;
    padding: 12px 30px; text-transform: uppercase; font-weight: bold; cursor: pointer;
    width: 100%; transition: all 0.3s;
}
.buy-button:hover { background: #00ff41; color: #000; }
</style>