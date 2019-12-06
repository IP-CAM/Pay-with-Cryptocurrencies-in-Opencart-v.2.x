$(function () {
    var selectors = {
        btnGetQrCode: '.ezdefi-btn-create-payment',
        coinSelectedToPaymentInput: 'input[name="coin-selected-to-order"]',
        selectCoinBox: '.ezdefi-select-coin-box',
        chargeCoinBox: '.ezdefi-charge-coin-box',
        paymentbox: '.ezdefi-payment-box',
        paymentContent: '.ezdefi-payment__content',
        deeplink: '.ezdefi-payment__deeplink',
        qrCodeImg: '.ezdefi-payment__qr-code',
        countDownTime: '.ezdefi-payment__countdown-lifeTime',
        originValue: '.ezdefi-payment__origin-value',
        currencyValue: '.ezdefi-payment__currency-value',
        logoCoinSelected: '.ezdefi-payment__coin-logo',
        nameCoinSelected: '.ezdefi-payment__coin-name',
        urlCheckOrderCompleteInput: '#url-check-order-complete',
        orderIdInput: '#order-id',
        paymentIdInput: '#payment-id',
        btnCharge: '.ezdefi-payment__btn-charge-coin',
        tooltipShowDiscount: '.tooltip-show-discount',
        countDownLabel: '.ezdefi-countdown-lifetime'
    };

    var global = {};

    $('[data-toggle="popover"]').popover();

    $(selectors.btnCharge).click(function () {
        clearInterval(global.countDownInterval);
        $(selectors.chargeCoinBox).css('display', 'block');
        $(selectors.paymentContent).css('display', 'none');
        $(selectors.qrCodeImg).prop('src', '');
        $(selectors.deeplink).attr('href', '');
        $(selectors.currencyValue).html('');
        $(selectors.btnCharge).css('display','none');
        $(selectors.coinSelectedToPaymentInput).each(function () {
            $(this).prop("checked", false);
        });
        $(selectors.btnGetQrCode).prop("disabled", true);
        $("#check-created-payment--simple").prop('checked', false);
        $("#check-created-payment--escrow").prop('checked', false);
    });

    $(selectors.coinSelectedToPaymentInput).click(function () {
        if($(this).is(':checked')) {
            $(selectors.btnGetQrCode).prop("disabled", false);
        }
    });

    $('.btn-choose-payment-type').click(function () {
        var paymentType = $(this).data('suffixes');
        var gotPayment = $('#check-created-payment'+paymentType).is(':checked');
        $('.ezdefi-show-payment').prop('checked',false);
        $('#ezdefi-show-payment'+paymentType).prop('checked', true);
        if(!gotPayment) {
            console.log(1111);
            var url = $("#url-create-payment"+paymentType).val();
            var coinId = $('#selected-coin-id').val();
            var discount = $('#selected-coin-discount').val();
            $.ajax({
                url: url,
                method: "GET",
                data: { coin_id: coinId },
                success: function (response) {
                    var data = JSON.parse(response).data;
                    if(data.status === 'failure') {
                        alert(data.message);
                    } else {
                        renderPayment(paymentType, data, discount);
                    }
                }
            })
        }
    });

    $(selectors.btnGetQrCode).click(function () {
        var enableSimplePay = $('#enable_simple_pay_input').is(':checked');
        var enableEscrowPay = $('#enable_escrow_pay_input').is(':checked');
        if(enableSimplePay) {
            var url = $("#url-create-payment--simple").val();
            $("#ezdefi-show-payment--simple").prop('checked', true);
            $("#ezdefi-show-payment--esrow").prop('checked', false);
            var suffixes = '--simple';
        } else if(enableEscrowPay) {
            var url = $("#url-create-payment--escrow").val();
            var suffixes = '--escrow';
        } else {
            alert('Some thing error');
        }
        var coinId = $(selectors.coinSelectedToPaymentInput+':checked').val();
        var discount = $(selectors.coinSelectedToPaymentInput+':checked').data('discount');
        $('#selected-coin-id').val(coinId);
        $('#selected-coin-discount').val(discount);
        $.ajax({
            url: url,
            method: "GET",
            data: { coin_id: coinId },
            success: function (response) {
                if(JSON.parse(response).error) {
                    alert("Something error, server can't create payment");
                }
                var data = JSON.parse(response).data;
                if(data.status === 'failure') {
                    alert(data.message);
                } else {
                    renderPayment(suffixes,data,discount);
                }
            }
        })
    });

    var renderPayment = function (suffixes, data, discount ) {
        var paymentId = data._id;

        $(selectors.paymentIdInput+suffixes).val(paymentId);
        countDownTime(paymentId, data.expiredTime, suffixes);

        $(selectors.deeplink+suffixes).attr('href', data.deepLink);
        $(selectors.currencyValue+suffixes).html(parseInt(data.value) * Math.pow(10, -data.decimal) + data.currency);
        $("#check-created-payment"+suffixes).prop('checked', true);
        $(selectors.logoCoinSelected).prop('src', data.token.logo);
        $(selectors.nameCoinSelected).html(  data.token.symbol.toUpperCase() + '/' + data.token.name);
        $(selectors.tooltipShowDiscount).attr('data-content', 'Discount: ' + discount + '%');
        $(selectors.selectCoinBox).css('display', 'none');
        $(selectors.chargeCoinBox).css('display', 'none');
        $(selectors.btnCharge).css('display','block');
        $(selectors.paymentbox).css('display','block');
        $(selectors.paymentContent).css('display','grid');
        $(selectors.qrCodeImg+suffixes).prop('src', data.qr);
        $('.ezdefi-payment__wallet-address'+suffixes).html(data.to);
        $('.ezdefi-payment__amount'+suffixes).html(parseInt(data.value) * Math.pow(10, -data.decimal) + data.currency);
    };

    var checkOrderComplete = function () {
        if(global.checkOrderCompleteInterval) return;
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
    };

    var countDownTime = function (paymentId, expiredTime, suffixes) {
        global.countDownInterval = setInterval(function () {
            var currentPaymentId = $(selectors.paymentIdInput+suffixes).val();
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
                        $(selectors.countDownLabel + suffixes).html(hours +':'+ minutes +':' + seconds);
                    } else {
                        $(selectors.countDownLabel + suffixes).html(minutes +':' + seconds);
                    }
                } else {
                    $(selectors.countDownLabel + suffixes).html('0:0');
                    clearInterval(global.countDownInterval);
                }
            }
        }, 1000);
    };
    checkOrderComplete();
});