<link rel="stylesheet" type="text/css" href="<?php echo $store_url ?>catalog/view/theme/default/stylesheet/ezdefi.css">
<script src="<?php echo $store_url ?>catalog/view/javascript/bignumber.min.js"></script>
<script src="<?php echo $store_url ?>catalog/view/javascript/ezdefi.js"></script>

<div class="payment-form-wrapper">
	<input type="hidden" id="origin-value" value="<?php echo $origin_value ?>">
	<input type="hidden" id="url-check-order-complete" value="<?php echo $url_check_order_complete ?>">
	<input type="hidden" id="order-id" value="<?php echo $order_id ?>">
    <?php if($enable_simple_pay) { ?>
    	<input type="checkbox" class="hidden" id="enable_simple_pay_input" checked>
    <?php } else { ?>
        <input type="checkbox" class="hidden" id="enable_simple_pay_input">
    <?php } ?>

    <?php if($enable_escrow_pay) { ?>
        <input type="checkbox" class="hidden" id="enable_escrow_pay_input" checked>
    <?php } else { ?>
        <input type="checkbox" class="hidden" id="enable_escrow_pay_input">
    <?php } ?>
	<input type="hidden" id="url-create-payment--simple" value="<?php echo $url_create_simple_payment ?>">
	<input type="hidden" id="url-create-payment--escrow" value="<?php echo $url_create_escrow_payment ?>">

    <?php if(sizeof($coins) > 1) { ?>
        <div class="ezdefi-select-coin-box">
            <div class="ezdefi-select-coin__list">
                <?php foreach ($coins as $coin) { ?>
                    <label for="select-coin-<?php echo $coin['_id'] ?>" class="ezdefi-change-coin-item">
                        <?php if (isset($coin['token']['description'])) { ?>
                            <img src="<?php echo $coin['token']['logo'] ?>" alt="" class="ezdefi-select-coin-item__logo" data-toggle="tooltip" title="<?php echo $coin['token']['description'] ?>">
                        <?php } else { ?>
                            <img src="<?php echo $coin['token']['logo'] ?>" alt="" class="ezdefi-select-coin-item__logo">
                        <?php } ?>
                        <span class="ezdefi-select-coin-item__price"> <?php echo $coin['token']['price'] ?> </span>
                        <span class="ezdefi-select-coin-item__symbol"> <?php echo $coin['token']['symbol'] ?></span>
                        <span class="ezdefi-select-coin-item__discount">-<?php echo (float)$coin['discount'] ?>%</span>
                        <input type="radio" name="coin-selected-to-order" class="hidden select-coin-checkbox" id="select-coin-<?php echo $coin['_id'] ?>" value="<?php echo $coin['_id'] ?>" data-discount="<?php echo $coin['discount'] ?>">
                    </label>
                <?php } ?>
            </div>
            <a href="<?php echo $link_guide ?>" class="ezapy-guide-link" target="_blank"><?php echo $text_guide ?></a>
            <?php if($enable_simple_pay) { ?>
                <button class="ezdefi-btn-create-payment" data-url_create_payment="<?php echo $url_create_simple_payment ?>" disabled> <?php echo $button_next ?> </button>
            <?php } else { ?>
                <button class="ezdefi-btn-create-payment" data-url_create_payment="<?php echo $url_create_escrow_payment ?>" disabled> <?php echo $button_next ?> </button>
            <?php } ?>
        </div>
    <?php } else { ?>
        <input type="radio" name="coin-selected-to-order" class="hidden select-coin-checkbox" id="select-coin-<?php echo $coins[0]['_id'] ?>" value="<?php echo $coins[0]['_id'] ?>" data-discount="<?php echo $coins[0]['discount'] ?>" checked>
        <input type="checkbox" id="have-one-coin" class="hidden" checked>
    <?php } ?>

    <div class="ezdefi-payment-box">
		<div class="ezdefi-payment__header">
            <input type="hidden" id="selected-coin-id">
            <input type="hidden" id="selected-coin-discount">
			<img src="" alt="" class="ezdefi-payment__coin-logo tooltip-show-discount" data-toggle="popover" title="Discount" data-placement="top" data-content="Discount:" data-trigger="hover">
			<span class="ezdefi-payment__coin-name tooltip-show-discount" data-toggle="popover" title="Discount" data-placement="top" data-content="Discount:" data-trigger="hover"></span>
			<span class="wide-space"></span>
            <?php if(sizeof($coins) > 1 ) { ?>
	    		<button class="ezdefi-payment__btn-change-coin"><?php echo $text_change ?></button>
            <?php } ?>
		</div>
        <?php if(sizeof($coins) > 1 ) { ?>
            <div class="ezdefi-change-coin-box margin-top-md">
                <div class="ezdefi-select-coin__list">
                    <?php foreach ($coins as $coin) { ?>
                        <label for="select-coin-<?php echo $coin['_id'] ?>" class="ezdefi-change-coin-item">
                            <?php if(isset($coin['token']['description'])) { ?>
                                <img src="<?php echo $coin.token.logo?>" alt="" class="ezdefi-select-coin-item__logo" data-toggle="tooltip" title="<?php echo $coin['token']['description'] ?>">
                            <?php } else { ?>
                                <img src="<?php echo $coin['token']['logo'] ?>" alt="" class="ezdefi-select-coin-item__logo">
                            <?php } ?>
                            <span class="ezdefi-select-coin-item__price"> <?php echo $coin['token']['price'] ?> </span>
                            <span class="ezdefi-select-coin-item__symbol"><?php echo $coin['token']['symbol'] ?> </span>
                            <span class="ezdefi-select-coin-item__discount">-<?php echo (float)$coin['discount'] ?>%</span>
                            <input type="radio" name="coin-selected-to-order" class="hidden select-coin-checkbox" id="select-coin-<?php echo $coin._id ?>" value="<?php echo $coin._id ?>" data-discount="<?php echo $coin['discount'] ?>">
                        </label>
                    <?php } ?>
                </div>
                <?php if($enable_simple_pay) { ?>
                    <button class="ezdefi-btn-create-payment" data-url_create_payment="<?php echo $url_create_simple_payment ?>" disabled> <?php echo $button_next ?> </button>
                <?php } else { ?>
                    <button class="ezdefi-btn-create-payment" data-url_create_payment="<?php echo $url_create_escrow_payment ?>" disabled> <?php echo $button_next ?> </button>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="ezdefi-payment__content">
            <?php if($enable_simple_pay && !$enable_escrow_pay) { ?>
                <input type="radio" class="ezdefi-show-payment-radio" id="ezdefi-show-payment--simple" name="show-payment" data-suffixes="--simple" checked>
                <label for="ezdefi-show-payment--simple" class="ezdefi-tab btn-show-payment--simple btn-choose-payment-type" data-suffixes="--simple"> <?php echo $text_pay_with_any_wallet ?> </label>
                <label class="label-choose-payment"></label>
                <input type="checkbox" class="hidden" id="check-created-payment--simple">
            <?php } ?>
            <?php if($enable_escrow_pay && !$enable_simple_pay) { ?>
                <input type="radio" class="ezdefi-show-payment-radio" id="ezdefi-show-payment--escrow" name="show-payment" data-suffixes="--escrow" checked>
                <label for="ezdefi-show-payment--escrow" class="ezdefi-tab btn-show-payment--escrow btn-choose-payment-type" data-suffixes="--escrow"><?php echo $text_pay_with_ezdefi_wallet ?></label>
				<label class="label-choose-payment"></label>
				<input type="checkbox" class="hidden" id="check-created-payment--escrow">
            <?php } ?>
            <?php if($enable_simple_pay && $enable_escrow_pay) { ?>
                <input type="radio" class="ezdefi-show-payment-radio" id="ezdefi-show-payment--simple" name="show-payment" checked data-suffixes="--simple">
                <input type="radio" class="ezdefi-show-payment-radio" id="ezdefi-show-payment--escrow" name="show-payment" data-suffixes="--escrow">
                <label for="ezdefi-show-payment--simple" class="ezdefi-tab btn-show-payment--simple btn-choose-payment-type" data-suffixes="--simple"> <?php echo $text_pay_with_any_wallet ?> </label>
                <label for="ezdefi-show-payment--escrow" class="ezdefi-tab btn-show-payment--escrow btn-choose-payment-type" data-suffixes="--escrow"> <?php echo $text_pay_with_ezdefi_wallet ?></label>
                <input type="checkbox" class="hidden" id="check-created-payment--simple">
                <input type="checkbox" class="hidden" id="check-created-payment--escrow">
            <?php } ?>
            <?php if($enable_simple_pay) { ?>
                <div class="payment-box simple-pay-box">
                    <div class="loader--simple loader">
                        <div class="Loader__item"></div>
                        <div class="Loader__item"></div>
                        <div class="Loader__item"></div>
                    </div>
                    <div class="payment-content--simple">
                        <input type="radio" name="choose-simple-method-qrcode-input" class="hidden" id="choose-full-qrcode-radio" checked>
                        <input type="radio" name="choose-simple-method-qrcode-input" class="hidden" id="choose-alternative-qrcode-radio">

                        <div class="alert alert-success alert-copy alert-copy-address">
                            <strong><?php echo $text_success ?></strong> <?php echo $text_copy_address ?>
                        </div>
                        <div class="alert alert-success alert-copy alert-copy-amount">
                            <strong><?php echo $text_success ?></strong> <?php echo $text_copy_amount ?>
                        </div>
                        <div class="ezdefi-payment__value">
                            <span class="ezdefi-payment__origin-value--simple"><?php echo $origin_value ?></span> <span> <?php echo $origin_currency ?></span>
                            <span class="ezdefi-payment__icon-convert"><i class="fa fa-retweet" aria-hidden="true"></i></span>
                            <span class="ezdefi-payment__currency-value--simple"></span><span class="ezdefi-payment__currency--simple"></span>
                        </div>
                        <div class="ezdefi-payment__countdown-box">
                            <input type="hidden" id="payment-id--simple">
                            <?php echo $ezdefi_countdown_lifetime_simple ?>
                        </div>
                        <div class="qrcode-box simple-method-with-full-qrcode" >
                            <a href="" target="_blank" class="ezdefi-payment__deeplink ezdefi-payment__deeplink--simple">
                                <img src="" alt="" class="ezdefi-payment__qr-code--simple">
                                <p><?php echo $text_click_to_pay ?></p>
                            </a>
                            <div class="timeout-notification timeout-notification--simple reload-payment" data-suffixes="--simple">
                                <p><?php echo $payment_time_out ?></p>
                            </div>
                        </div>
                        <div class="qrcode-box simple-method-with-alternative-qrcode">
                            <a href="" target="_blank" class="ezdefi-payment__deeplink ezdefi-payment__deeplink--simple">
                                <img src="" alt="" class="ezdefi-payment__alternative-qr-code--simple">
                                <p><?php echo $text_click_to_pay ?></p>
                            </a>
                            <div class="timeout-notification timeout-notification--simple reload-payment" data-suffixes="--simple">
                                <p><?php echo $payment_time_out ?></p>
                            </div>
                        </div>
                        <p style="margin-bottom: 3px;">
                            <b>Address:</b>
                            <span class="btn-copy-address">
                                <span class="ezdefi-payment__wallet-address--simple"></span>
                                <span><i class="fa fa-copy ezdefi-copy-icon"></i></span>
                            </span>
                        </p>
                        <p><b>Amount:</b>
                            <span class="btn-copy-amount">
                                <span class="ezdefi-payment__amount--simple"></span>
                                <span class="copy-address-icon"><i class="fa fa-copy ezdefi-copy-icon"></i></span>
                            </span>
                        </p>
                        <p class="margin-top-md text-red simple-method-with-full-qrcode"><?php echo $text_choose_alternative_qr ?>
                            <label class="link_use_ezdefi_wallet" for="choose-alternative-qrcode-radio"><?php echo $text_use_alternative_qr ?></label>
                        </p>
                        <p class="margin-top-md text-red simple-method-with-alternative-qrcode"><?php echo $text_notify_pay_exact_amount ?> <label for="ezdefi-show-payment--escrow" class="link_use_ezdefi_wallet"><i><?php echo $text_ezdefi_wallet ?></i></label></p>
                        <label class="link_use_ezdefi_wallet simple-method-with-alternative-qrcode" for="choose-full-qrcode-radio"><?php echo $text_use_previous_qr ?></label>
                    </div>
                    <div class="payment-error--simple">
                        <?php echo $error_cant_create_amount ?>
                    </div>
                </div>
            <?php } ?>
            <?php if($enable_escrow_pay) { ?>
                <div class="payment-box escrow-pay-box">
                    <div class="loader--escrow loader">
                        <div class="Loader__item"></div>
                        <div class="Loader__item"></div>
                        <div class="Loader__item"></div>
                    </div>
                    <div class="payment-content--escrow">
                        <div class="ezdefi-payment__value">
                            <span class="ezdefi-payment__origin-value--escrow"><?php echo $origin_value ?> </span> <span> <?php echo $origin_currency ?></span>
                            <span class="ezdefi-payment__icon-convert"><i class="fa fa-retweet" aria-hidden="true"></i></span>
                            <span class="ezdefi-payment__currency-value--escrow"></span><span class="ezdefi-payment__currency--escrow"></span>
                        </div>
                        <div class="ezdefi-payment__countdown-box">
                            <input type="hidden" id="payment-id--escrow">
                            <?php echo $ezdefi_countdown_lifetime_escrow ?>
                        </div>
                        <div class="qrcode-box">
                            <a href="" class="ezdefi-payment__deeplink ezdefi-payment__deeplink--escrow" target="_blank">
                                <img src="" alt="" class="ezdefi-payment__qr-code ezdefi-payment__qr-code--escrow">
                                <p><?php echo $text_click_to_pay ?></p>
                            </a>
                            <div class="timeout-notification timeout-notification--escrow reload-payment" data-suffixes="--escrow">
                                <p><?php echo $payment_time_out ?></p>
                            </div>
                        </div>
                        <div class="ezdefi-payment__download-link">
                            <a href="<?php echo $link_download_app_ios ?>" target="_blank">
                                <img src="catalog/view/theme/default/image/ezdefi/ios-icon.png" alt="">
                            </a>
                            <a href="<?php echo $link_download_app_android ?>" target="_blank">
                                <img src="catalog/view/theme/default/image/ezdefi/android-icon.png" alt="">
                            </a>
                        </div>
                    </div>
                    <div class="payment-error--escrow">
                        <?php echo $error_cant_create_payment ?>
                    </div>
                </div>
            <?php } ?>
		</div>
	</div>
</div>