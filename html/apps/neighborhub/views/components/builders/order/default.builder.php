<?php if (!defined('MB_RUNNING')) exit; ?>

<form id="custom-builder-form" data-product-id="<?php echo intval($productId); ?>">
    <?php foreach ($steps as $step): ?>
        <?php
        $stepType = $step['type'] ?? 'radio';
        $isRadio = ($stepType === 'radio');
        $isWidget = ($stepType === 'add-subtract-widget');

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
            <h6 style="font-weight: 600; font-size: 1.15rem; color: #37474f;">
                ⚙️ <?php echo htmlspecialchars($step['title']); ?>
            </h6>

            <?php foreach ($step['options'] as $idx => $opt):
                $optPrice = floatval($opt['price'] ?? 0);
                $isIncluded = !empty($opt['included']);
                $inputId = "opt_" . htmlspecialchars($step['id']) . "_{$idx}";

                if ($isWidget):
                    $priceLabel = ($optPrice > 0) ? ' <span style="color: #0277bd; font-weight: 500;">(+$' . number_format($optPrice, 2) . ' ea)</span>' : '';
            ?>
                    <!-- Add/Subtract Quantity Control Row -->
                    <div class="nh-widget-row" style="display: flex; align-items: center; justify-content: space-between; margin: 10px 0;">
                        <span style="font-size: 0.95rem; font-weight: 600; color: var(--brand-text-main, #333);">
                            <?php echo htmlspecialchars($opt['name']) . $priceLabel; ?>
                        </span>

                        <div class="quantity-pill nh-widget-pill" style="display: flex; align-items: center;">
                            <button type="button" class="btn-flat nh-widget-minus" style="padding: 0 8px; height: 28px; line-height: 28px;">
                                <i class="fas fa-minus fa-xs"></i>
                            </button>

                            <input type="number"
                                class="nh-builder-qty-input nh-builder-input"
                                name="<?php echo htmlspecialchars($step['id']); ?>[<?php echo htmlspecialchars($opt['name']); ?>]"
                                value="0"
                                min="0"
                                data-max-quantity="<?php echo intval($opt['max_quantity'] ?? 20); ?>"
                                data-type="widget"
                                data-name="<?php echo htmlspecialchars($opt['name']); ?>"
                                data-price="<?php echo $optPrice; ?>"
                                style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700; -webkit-appearance: none; -moz-appearance: textfield;"
                                readonly />

                            <button type="button" class="btn-flat nh-widget-plus" style="padding: 0 8px; height: 28px; line-height: 28px;">
                                <i class="fas fa-plus fa-xs"></i>
                            </button>
                        </div>
                    </div>

                <?php else:
                    // Radio & Checkbox rendering
                    if ($isRadio) {
                        $isChecked = ($includedRadioIdx !== null) ? ($idx === $includedRadioIdx) : ($idx === 0 && !empty($step['required']));
                    } else {
                        $isChecked = $isIncluded;
                    }

                    if ($isIncluded) {
                        $priceLabel = ' <span style="color: #2e7d32; font-weight: 500; font-size: 0.85em;">(Included)</span>';
                    } elseif ($optPrice > 0) {
                        $priceLabel = ' <span style="color: #0277bd; font-weight: 500;">(+$' . number_format($optPrice, 2) . ')</span>';
                    } elseif ($optPrice < 0) {
                        $priceLabel = ' <span style="color: #2e7d32; font-weight: 500;">(-$' . number_format(abs($optPrice), 2) . ')</span>';
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
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 20px 0;">
    <?php endforeach; ?>
</form>