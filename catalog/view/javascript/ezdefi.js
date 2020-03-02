$(function () {
    var selectors = {
        btnGetQrCode                : '.ezdefi-btn-create-payment',
        coinSelectedToPaymentInput  : 'input[name="coin-selected-to-order"]',
        selectCoinBox               : '.ezdefi-select-coin-box',
        changeCoinBox               : '.ezdefi-change-coin-box',
        paymentbox                  : '.ezdefi-payment-box',
        paymentContent              : '.ezdefi-payment__content',
        deeplink                    : '.ezdefi-payment__deeplink',
        qrCodeImg                   : '.ezdefi-payment__qr-code',
        alternativeQrCode           : '.ezdefi-payment__alternative-qr-code',
        timeoutNotify               : '.timeout-notification',
        countDownTime               : '.ezdefi-payment__countdown-lifeTime',
        originValue                 : '.ezdefi-payment__origin-value',
        currencyValue               : '.ezdefi-payment__currency-value',
        logoCoinSelected            : '.ezdefi-payment__coin-logo',
        nameCoinSelected            : '.ezdefi-payment__coin-name',
        urlCheckOrderCompleteInput  : '#url-check-order-complete',
        orderIdInput                : '#order-id',
        paymentIdInput              : '#payment-id',
        btnChange                   : '.ezdefi-payment__btn-change-coin',
        tooltipShowDiscount         : '.tooltip-show-discount',
        countDownLabel              : '.ezdefi-countdown-lifetime',
        btnReloadPayment            : '.reload-payment'
    };

    var global = {
        reloadingSimple : 0,
        reloadingEscrow: 0
    };
    global.countDownInterval = {};

    $('[data-toggle="popover"]').popover();

    $(selectors.btnChange).click(function () {
        for(let i in global.countDownInterval) {
            clearInterval(global.countDownInterval[i]);
        }
        $(selectors.countDownLabel).html( '0:0');

        $(selectors.changeCoinBox).css('display', 'block');
        $(selectors.paymentContent).css('display', 'none');
        $(selectors.qrCodeImg).prop('src', '');
        $(selectors.deeplink).attr('href', '');
        $(selectors.currencyValue).html('');
        $(selectors.btnChange).css('display','none');
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
            createPayment(url, coinId, paymentType, discount);
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

        createPayment(url, coinId, suffixes, discount);
    });

    $(selectors.btnReloadPayment).click(function () {
        let suffixes = $(this).data('suffixes');

        if((suffixes === "--simple" && global.reloadingSimple === 0) || (suffixes === "--escrow" && global.reloadingEscrow === 0)) {
            if(suffixes === "--simple") global.reloadingSimple = 1;
            else if(suffixes === "--escrow") global.reloadingEscrow = 1;
            var url = $("#url-create-payment" + suffixes).val();
            var coinId = $(selectors.coinSelectedToPaymentInput+':checked').val();
            var discount = $(selectors.coinSelectedToPaymentInput+':checked').data('discount');
            createPayment(url, coinId, suffixes, discount);
        }
    });

    $(".select-coin-checkbox").change(function () {
        var inputId = $(".select-coin-checkbox:checked").attr('id');
        $("label.ezdefi-change-coin-item").css({
            border: '1px solid #d8d8d8',
            background: 'inherit'
        });
        $("label.ezdefi-change-coin-item[for='"+inputId+"']").css({
            border: '2px solid #54bdff',
            background: '#c0dcf9db'
        });
    });

    $(".btn-copy-address").click(function () {
        copytext(".ezdefi-payment__wallet-address--simple");
        $(".alert-copy").hide();
        $(".alert-copy-address").show(function () {
            $(".alert-copy-address").delay(1000).hide("slow")
        });
    });

    $(".btn-copy-amount").click(function () {
        copytext(".ezdefi-payment__amount--simple");
        $(".alert-copy").hide();
        $(".alert-copy-amount").show(function () {
            $(".alert-copy-amount").delay(1000).hide("slow")
        });
    });

    var copytext = function (elementToCopy) {
        let text = $(elementToCopy).html();
        let tmpElem = document.createElement("input");
        document.body.appendChild(tmpElem);
        tmpElem.value = text;
        tmpElem.select();
        document.execCommand("copy");
        document.body.removeChild(tmpElem);
    };
    
    var createPayment = function (url, coinId, suffixes, discount) {
        showPaymentLoading(suffixes, true);
        $(".payment-error"+suffixes).css('display', 'none');
        $(selectors.selectCoinBox).css('display', 'none');
        $(selectors.changeCoinBox).css('display', 'none');
        $(selectors.btnChange).css('display','block');
        $(selectors.paymentbox).css('display','block');
        $('.ezdefi-payment__content').css('display','grid');

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
                    renderPayment(suffixes,{},discount);
                    $(".payment-content"+suffixes).css('display', 'none');
                    $(".payment-error"+suffixes).css('display', 'block');
                    $(".loader"+suffixes).css('display', 'none');
                } else {
                    $(".payment-error"+suffixes).css('display', 'none');
                    renderPayment(suffixes,data,discount);
                }
            }
        })
    };

    var showPaymentLoading = function (suffixes, enable) {
        $(".payment-content"+suffixes).css('display', enable ? 'none' : 'block');
        $(".loader"+suffixes).css('display', enable ? 'flex' : 'none');
    };

    var renderPayment = function (suffixes, data, discount ) {
        global.reloadingSimple = 0;
        global.reloadingEscrow = 0;
        let paymentId = data._id;
        let originValue = $("#origin-value").val();

        $(selectors.paymentIdInput+suffixes).val(paymentId);
        enablePaymentTimeout(suffixes, false);
        countDownTime(paymentId, data.expiredTime, suffixes);
        $(selectors.deeplink+suffixes).attr('href', data.deepLink);

        let originValueBN = BigNumber(originValue);
        let discountBN = (new BigNumber(100 - discount)).div(new BigNumber(100));
        let originValueWithDiscount = originValueBN.multipliedBy(discountBN).toFormat();
        $(selectors.originValue + suffixes).html(originValueWithDiscount);

        let decimalBN = new BigNumber(Math.pow(10, data.decimal));
        let valueBN = new BigNumber(data.value);
        let currencyValue = valueBN.div(decimalBN).toFormat();            // big number
        $(selectors.currencyValue+suffixes).html(currencyValue);

        $("#check-created-payment"+suffixes).prop('checked', true);
        $(selectors.logoCoinSelected).prop('src', data.token ? data.token.logo : '');
        $(selectors.nameCoinSelected).html(  data.token ? data.token.symbol.toUpperCase() + '/' + data.token.name : '');
        $(selectors.tooltipShowDiscount).attr('data-content', 'Discount: ' + discount + '%');

        $(selectors.paymentContent).css('display','grid');
        $(selectors.qrCodeImg+suffixes).prop('src', data.qr);
        $(selectors.alternativeQrCode + suffixes).prop('src', "https://chart.googleapis.com/chart?cht=qr&chl="+data.to+"&chs=200x200&chld=L|0")
        $('.ezdefi-payment__wallet-address'+suffixes).html(data.to);
        $('.ezdefi-payment__amount'+suffixes).html(currencyValue);
        $('.ezdefi-payment__currency'+suffixes).html(data.currency);

        showPaymentLoading(suffixes, false);
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
        renderTimeOut(expiredTime, suffixes);
        global.countDownInterval[suffixes] = setInterval(function () {
            var currentPaymentId = $(selectors.paymentIdInput+suffixes).val();
            if(currentPaymentId !== paymentId) {
                clearInterval(global.countDownInterval[suffixes]);
            } else {
                enablePaymentTimeout(suffixes, false);
                var timestampCountdown = new Date(expiredTime) - new Date();
                var secondToCountdown = Math.floor(timestampCountdown/1000);
                if(secondToCountdown >= 0) {
                    renderTimeOut(expiredTime, suffixes)
                } else {
                    $(selectors.countDownLabel + suffixes).html('0:0');
                    enablePaymentTimeout(suffixes, true);
                    clearInterval(global.countDownInterval[suffixes]);
                }
            }
        }, 1000);
    };

    var renderTimeOut = function (expiredTime, suffixes) {
        var timestampCountdown = new Date(expiredTime) - new Date();
        var secondToCountdown = Math.floor(timestampCountdown/1000);

        var hours = Math.floor(secondToCountdown / 3600);
        secondToCountdown %= 3600;
        var minutes = Math.floor(secondToCountdown / 60);
        var seconds = secondToCountdown % 60;
        if(hours > 0 ) {
            $(selectors.countDownLabel + suffixes).html(hours +':'+ minutes +':' + seconds);
        } else {
            $(selectors.countDownLabel + suffixes).html(minutes +':' + seconds);
        }
    };

    var enablePaymentTimeout = function(suffixes, enable) {
        $(selectors.alternativeQrCode+suffixes).css('filter', enable ? 'blur(5px)' : 'none');
        $(selectors.qrCodeImg+suffixes).css('filter', enable ? 'blur(5px)' : 'none');
        $(selectors.timeoutNotify+suffixes).css('display', enable ? 'block' : 'none');
    };

    checkOrderComplete();
});