$(function () {
    var selectors = {
        btnGetQrCode: '.ezpay-btn-create-payment',
        coinIdToPaymentInput: 'input[name="coin-selected-to-order"]',
        selectCoinBox: '.ezpay-select-coin-box',
        chargeCoinBox: '.ezpay-charge-coin-box',
        paymentbox: '.ezpay-payment-box',
        paymentContent: '.ezpay-payment__content',
        qrCodeImg: '.ezpay-payment__qr-code',
        countDownTime: '.ezpay-payment__countdown-lifeTime',
        originValue: '.ezpay-payment__origin-value',
        currencyValue: '.ezpay-payment__currency-value',
        logoCoinSelected: '.ezpay-payment__coin-logo',
        nameCoinSelected: '.ezpay-payment__coin-name',
        urlCheckOrderCompleteInput: '#url-check-order-complete',
        orderIdInput: '#order-id',
        paymentIdInput: '#payment-id',
        btnCharge: '.ezpay-payment__btn-charge-coin',
        tooltipShowDiscount: '.tooltip-show-discount'
    };


    var global = {};

    $(selectors.btnCharge).click(function () {
        clearInterval(global.countDownInterval);
        clearInterval(global.checkOrderCompleteInterval);
        $(selectors.chargeCoinBox).css('display', 'block');
        $(selectors.paymentContent).css('display', 'none');
        $(selectors.qrCodeImg).prop('src', '');
        $(selectors.originValue).html('');
        $(selectors.currencyValue).html('');
        $(selectors.btnCharge).css('display','none');
        $(selectors.coinIdToPaymentInput).each(function () {
            $(this).prop("checked", false);
        });
        $(selectors.btnGetQrCode).prop("disabled", true);
        // $(selectors.logoCoinSelected).prop('src', '');
        // $(selectors.nameCoinSelected).html('');
    });

    $(selectors.coinIdToPaymentInput).click(function () {
        if($(this).is(':checked')) {
            $(selectors.btnGetQrCode).prop("disabled", false);
        }
    });

    $(selectors.btnGetQrCode).click(function () {
        var url = $(this).data('url_create_payment');
        var coinId = $(selectors.coinIdToPaymentInput+':checked').val();
        var discount = $(selectors.coinIdToPaymentInput+':checked').data('discount');

        $.ajax({
            url: url,
            method: "GET",
            data: { coin_id: coinId },
            success: function (response) {
                var data = JSON.parse(response).data;
                console.log(data);
                var paymentId = data._id;

                $(selectors.paymentIdInput).val(paymentId);
                countDownTime(paymentId, data.expiredTime);
                checkOrderComplete();

                $(selectors.originValue).html(data.originValue + data.originCurrency);
                $(selectors.currencyValue).html(parseInt(data.value) * Math.pow(10, -data.decimal) + data.currency);

                $(selectors.logoCoinSelected).prop('src', data.token.logo);
                $(selectors.nameCoinSelected).html(  data.token.symbol.toUpperCase() + '/' + data.token.name);

                $(selectors.tooltipShowDiscount).tooltip('hide')
                    .attr('data-original-title', 'Discount: ' + discount + '%')
                    .tooltip('fixTitle')
                    .tooltip('show')
                $(selectors.selectCoinBox).css('display', 'none');
                $(selectors.chargeCoinBox).css('display', 'none');
                $(selectors.btnCharge).css('display','block');
                $(selectors.paymentbox).css('display','block');
                $(selectors.paymentContent).css('display','block');
                $(selectors.qrCodeImg).prop('src', data.qr);
            }
        })
    });

    var checkOrderComplete = function () {
        var url = $(selectors.urlCheckOrderCompleteInput).val();
        var orderId =$(selectors.orderIdInput).val();

        global.checkOrderCompleteInterval = setInterval(function () {
            $.ajax({
                url: url,
                method: "GET",
                data: { order_id: orderId },
                success: function (response) {
                    var data =JSON.parse(response).data;
                    if (data.status === "DONE") {
                        clearInterval(global.checkOrderCompleteInterval);
                        window.location.href = data.url_redirect;
                    } else if (data.status === "PENDING") {
                        // waiting for payment
                    }
                }
            });
        }, 500);
    }

    var countDownTime = function (paymentId, expiredTime) {
        global.countDownInterval = setInterval(function () {
            var currentPaymentId = $(selectors.paymentIdInput).val();
            if(currentPaymentId !== paymentId) {
                clearInterval(global.countDownInterval);
            } else {
                var timestampCountdown = new Date(expiredTime) - new Date();
                var secondToCountdown = Math.floor(timestampCountdown/1000);
                if(secondToCountdown >= 0) {
                    var hours = Math.floor(secondToCountdown / 3600);
                    secondToCountdown %= 3600;
                    var minutes = Math.floor(secondToCountdown / 60);
                    var seconds = secondToCountdown % 60;
                    if(hours > 0 ) {
                        $('.ezpay-payment__countdown-lifetime').html(hours +':'+ minutes +':' + seconds);
                    } else {
                        $('.ezpay-payment__countdown-lifetime').html(minutes +':' + seconds);
                    }
                } else {
                    clearInterval(global.countDownInterval);
                }
            }
        }, 1000);
    }
})