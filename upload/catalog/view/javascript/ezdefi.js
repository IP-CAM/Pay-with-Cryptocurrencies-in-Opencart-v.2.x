$(function () {
    var selectors = {
        btnGetQrCode: '.ezdefi-btn-create-payment',
        coinSelectedToPaymentInput: 'input[name="coin-selected-to-order"]',
        selectCoinBox: '.ezdefi-select-coin-box',
        changeCoinBox: '.ezdefi-change-coin-box',
        paymentbox: '.ezdefi-payment-box',
        paymentContent: '.ezdefi-payment__content',
        deeplink: '.ezdefi-payment__deeplink',
        qrCodeImg: '.ezdefi-payment__qr-code',
        timeoutNotify: '.timeout-notification',
        countDownTime: '.ezdefi-payment__countdown-lifeTime',
        originValue: '.ezdefi-payment__origin-value',
        currencyValue: '.ezdefi-payment__currency-value',
        logoCoinSelected: '.ezdefi-payment__coin-logo',
        nameCoinSelected: '.ezdefi-payment__coin-name',
        urlCheckOrderCompleteInput: '#url-check-order-complete',
        orderIdInput: '#order-id',
        paymentIdInput: '#payment-id',
        btnChange: '.ezdefi-payment__btn-change-coin',
        tooltipShowDiscount: '.tooltip-show-discount',
        countDownLabel: '.ezdefi-countdown-lifetime'
    };

    var global = {};
    global.countDownInterval = {};

    $('[data-toggle="popover"]').popover();

    $(selectors.btnChange).click(function () {
        for(let i in global.countDownInterval) {
            clearInterval(global.countDownInterval[i]);
        }
        $(selectors.changeCoinBox).css('display', 'block');
        $(selectors.paymentContent).css('display', 'none');
        $(selectors.qrCodeImg).prop('src', '');
        $(selectors.deeplink).attr('href', '');
        $(selectors.currencyValue).html('');
        $(selectors.btnChange).css('display','none');
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

    $('.ezdefi-show-payment-radio').change(function () {
        var paymentType = $(".ezdefi-show-payment-radio:checked").data('suffixes');
        var gotPayment = $('#check-created-payment'+paymentType).is(':checked');
        $('.ezdefi-show-payment').prop('checked',false);
        $('#ezdefi-show-payment'+paymentType).prop('checked', true);
        if(!gotPayment) {
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
                    renderPayment(suffixes,{},discount);
                } else {
                    renderPayment(suffixes,data,discount);
                }
            }
        })
    });

    var renderPayment = function (suffixes, data, discount ) {
        let paymentId = data._id;
        let originValue = $("#origin-value").val();

        $(selectors.paymentIdInput+suffixes).val(paymentId);
        enablePaymentTimeout(suffixes, false);
        countDownTime(paymentId, data.expiredTime, suffixes);
        $(selectors.deeplink+suffixes).attr('href', data.deepLink);

        let originValueWithDiscount = new BigNumber(originValue).multipliedBy(new BigNumber((100 - discount)/100));
        $(selectors.originValue + suffixes).html(originValueWithDiscount.toFormat());

        let decimalBN = new BigNumber(Math.pow(10, data.decimal));
        let valueBN = new BigNumber(data.value);
        let currencyValue = valueBN.div(decimalBN).toFormat();            // big number
        $(selectors.currencyValue+suffixes).html(currencyValue + data.currency);

        $("#check-created-payment"+suffixes).prop('checked', true);
        $(selectors.logoCoinSelected).prop('src', data.token ? data.token.logo : '');
        $(selectors.nameCoinSelected).html(  data.token ? data.token.symbol.toUpperCase() + '/' + data.token.name : '');
        $(selectors.tooltipShowDiscount).attr('data-content', 'Discount: ' + discount + '%');
        $(selectors.selectCoinBox).css('display', 'none');
        $(selectors.changeCoinBox).css('display', 'none');
        $(selectors.btnChange).css('display','block');
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
        }, 1000);
    };

    var countDownTime = function (paymentId, expiredTime, suffixes) {
        global.countDownInterval[suffixes] = setInterval(function () {
            var currentPaymentId = $(selectors.paymentIdInput+suffixes).val();
            if(currentPaymentId !== paymentId) {
                clearInterval(global.countDownInterval[suffixes]);
            } else {
                enablePaymentTimeout(suffixes, false);
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
                    enablePaymentTimeout(suffixes, true);
                    clearInterval(global.countDownInterval[suffixes]);
                }
            }
        }, 1000);
    };

    var enablePaymentTimeout = function(suffixes, enable) {
        $(selectors.qrCodeImg+suffixes).css('filter', enable ? 'blur(5px)' : 'none');
        $(selectors.timeoutNotify+suffixes).css('display', enable ? 'block' : 'none');
    };

    $(".select-coin-checkbox").change(function () {
        var inputId = $(".select-coin-checkbox:checked").attr('id');
        $("label.ezdefi-change-coin-item").css('border', '1px solid #d8d8d8');
        $("label.ezdefi-change-coin-item[for='"+inputId+"']").css('border', '2px solid lightskyblue')
    });

    checkOrderComplete();
});