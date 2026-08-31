<?php if (!defined('MB_RUNNING')) exit; ?>

<form id="custom-builder-form" data-product-id="<?php echo intval($productId); ?>">
    <?php foreach ($steps as $step): ?>
        <?php 
            $isRadio = ($step['type'] ?? 'radio') === 'radio';

            // Find if any option in this radio step is explicitly marked as included
            $includedRadioIdx = null;
            if ($isRadio) {
                foreach ($step['options'] as $i => $o) {
                    if (!empty($o['included'])) {
                        $includedRadioIdx = $i;
                        break;
                    }
                }
            }
        ?>
        <div class="builder-step-section" style="margin-bottom: 24px;">
            <h6 style="font-weight: 600; font-size: 1.15rem; color: #d32f2f;">
                🌮 <?php echo htmlspecialchars($step['title']); ?>
            </h6>
            
            <?php foreach ($step['options'] as $idx => $opt): 
                $optPrice = floatval($opt['price'] ?? 0);
                $isIncluded = !empty($opt['included']);
                $inputId = "opt_" . htmlspecialchars($step['id']) . "_{$idx}";

                // Determine pre-checked state
                if ($isRadio) {
                    if ($includedRadioIdx !== null) {
                        $isChecked = ($idx === $includedRadioIdx);
                    } else {
                        $isChecked = ($idx === 0 && !empty($step['required']));
                    }
                } else {
                    $isChecked = $isIncluded;
                }

                // Price labels
                if ($isIncluded) {
                    $priceLabel = ' <span style="color: #388e3c; font-weight: 500; font-size: 0.85em;">(Included)</span>';
                } elseif ($optPrice > 0) {
                    $priceLabel = ' <span style="color: #e65100; font-weight: 500;">(+$' . number_format($optPrice, 2) . ')</span>';
                } elseif ($optPrice < 0) {
                    $priceLabel = ' <span style="color: #388e3c; font-weight: 500;">(-$' . number_format(abs($optPrice), 2) . ')</span>';
                } else {
                    $priceLabel = '';
                }
            ?>
                <p style="margin: 8px 0;">
                    <label style="cursor: pointer;">
                        <?php if ($isRadio): ?>
                            <input class="with-gap nh-builder-input" 
                                   name="<?php echo htmlspecialchars($step['id']); ?>" 
                                   type="radio" 
                                   id="<?php echo $inputId; ?>" 
                                   value="<?php echo htmlspecialchars($opt['name']); ?>" 
                                   data-included="<?php echo $isIncluded ? 1 : 0; ?>" 
                                   data-price="<?php echo $optPrice; ?>" 
                                   <?php echo $isChecked ? 'checked' : ''; ?> />
                        <?php else: ?>
                            <input type="checkbox" 
                                   class="nh-builder-input"
                                   name="<?php echo htmlspecialchars($step['id']); ?>[]" 
                                   id="<?php echo $inputId; ?>" 
                                   value="<?php echo htmlspecialchars($opt['name']); ?>" 
                                   data-included="<?php echo $isIncluded ? 1 : 0; ?>" 
                                   data-price="<?php echo $optPrice; ?>" 
                                   <?php echo $isChecked ? 'checked' : ''; ?> />
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($opt['name']) . $priceLabel; ?></span>
                    </label>
                </p>
            <?php endforeach; ?>
        </div>
        <hr style="border: 0; border-top: 2px dashed #ffe0b2; margin: 20px 0;">
    <?php endforeach; ?>
</form>