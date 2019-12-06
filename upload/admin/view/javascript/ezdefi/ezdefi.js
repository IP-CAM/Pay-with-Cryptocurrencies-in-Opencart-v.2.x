$( function() {
    const selectors = {
        formConfig: '#ezdefi-form-config',
        selectCoinConfig: '.ezdefi-select-coin',
        coinConfigTable: '#coin-config__table',
        cloneRowCoinConfig: '.coin-config-clone',
        btnAdd: '#btn-add',
        btnDelete: '.btn-confirm-delete',
        btnEdit: '.btn-submit-edit',
        btnCancel: '.btn-cancel',
        gatewayApiUrlInput: "#gateway-api-url-input",
        apiKeyInput: "#api-key-input",
        orderStatusInput: "#order-status-input",
        enableSimplePayInput: "#enable-simple-pay",
        enableEscrowPayInput: "#enable-escrow-pay",
        decimalInput: "#decimal-input",
        variationInput: "#variation-input",
        decimalBox: ".decimal-input-box",
        variationBox: ".variation-input-box",
        coinIdInput: '.coin-config__id',
        coinOrderInput: '.coin-config__order',
        coinSymbolInput: '.coin-config__fullname',
        coinNameInput: '.coin-config__name',
        coinDiscountInput: '.coin-config__discount',
        coinPaymentLifetimeInput: '.coin-config__payment-lifetime',
        coinWalletAddressInput: '.coin-config__wallet-address',
        coinSafeBlockDistantInput: '.coin-config__safe-block-distant',
        coinDecimalInput: '.coin-config__decimal',
    };

    var oc_ezdefi_admin = function() {
        $(selectors.btnAdd).click(this.addCoinConfigListener.bind(this));
        $(selectors.btnDelete).click(this.deleteCoinConfig);
        $(selectors.btnEdit).click(this.editCoinConfig);
        $(selectors.enableSimplePayInput).click(this.showSimplePayConfig);
        this.showSimplePayConfig();
        this.initSortable();
        this.initValidate();
    };

    oc_ezdefi_admin.prototype.initValidate = function() {
        $.validator.addMethod("integer", function(value, element) {
            return this.optional( element ) || Math.floor(value) == value && $.isNumeric(value);
        }, "This field should be integer");
        $.validator.addMethod("float", function(value, element) {
            return this.optional( element ) || value.match(/^-?\d*(\.\d+)?$/);
        }, "This field should be float");
        $( selectors.formConfig ).validate({
            submitHandler: function(form) {
                form.submit();
            }
        });

        this.validateAllInput(selectors.gatewayApiUrlInput, {required: true});
        this.validateAllInput(selectors.apiKeyInput, {required: true});
        this.validateAllInput(selectors.orderStatusInput, {required: true});
        this.validateAllInput(selectors.coinDiscountInput, {max: 100, integer: true});
        this.validateAllInput(selectors.coinNameInput, {required: true});
        this.validateAllInput(selectors.coinIdInput, {required: true});
        this.validateAllInput(selectors.coinOrderInput, {required: true});
        this.validateAllInput(selectors.coinSymbolInput, {required: true});
        this.validateAllInput(selectors.coinPaymentLifetimeInput, {integer: true});
        this.validateAllInput(selectors.coinSafeBlockDistantInput, {integer: true});
        this.validateAllInput(selectors.coinSafeBlockDistantInput, {integer: true});
        this.validateAllInput(selectors.variationInput, {float: true, required: () => $(selectors.enableSimplePayInput).is(':checked')});
        this.validateAllInput(selectors.decimalInput, {integer: true, max:14, required: () => $(selectors.enableSimplePayInput).is(':checked')});
        this.validateAllInput(selectors.enableSimplePayInput, {required: () => !$(selectors.enableEscrowPayInput).is(':checked'), messages: {required: 'choose at least one payment method'}});
        this.validateAllInput(selectors.enableEscrowPayInput, {required: () => !$(selectors.enableSimplePayInput).is(':checked'), messages: {required: 'choose at least one payment method'}});

        this.validateWalletAddress();
    };

    oc_ezdefi_admin.prototype.validateWalletAddress = function() {
        $(selectors.coinWalletAddressInput).each(function () {
            var inputName = $(this).attr('name');
            $(`input[name="${inputName}"]`).rules('add', {
                required: true,
                remote: {
                    url: $(selectors.formConfig).data('url_validate_wallet'),
                    data: {
                        address: function () {
                            return $(`input[name="${inputName}"]`).val();
                        },
                    },
                },
                messages: {
                    remote: "This wallet address is invalid"
                }
            });
        });
    };

    oc_ezdefi_admin.prototype.validateAllInput = function(selector, rules) {
        $(selector).each(function () {
            var inputName = $(this).attr('name');
            if(inputName) {
                $(`input[name="${inputName}"]`).rules('add', rules);
            } else {
                var id = $(this).attr('id');
                $('#'+id).rules('add', rules);
            }

        });
    };

    oc_ezdefi_admin.prototype.showSimplePayConfig = function() {
        if($(selectors.enableSimplePayInput).is(':checked')) {
            $(selectors.decimalBox).css('display','block');
            $(selectors.variationBox).css('display','block');
        } else {
            $(selectors.decimalBox).css('display','none');
            $(selectors.variationBox).css('display','none');
        }
    };

    oc_ezdefi_admin.prototype.deleteCoinConfig = function() {
        var url = $(this).data('url_delete');
        var coinId = $(this).data('coin_id');

        $.ajax({
            url: url,
            method: "POST",
            data: { coin_id: coinId },
            success: function (response) {
                var data = JSON.parse(response).data;
                if(data.status === 'success') {
                    $('#modal-delete-'+coinId).modal('toggle');
                    $('#config-row-' + coinId).remove();
                    $("#modal-edit-"+coinId).remove();
                } else {
                    alert(data.message);
                }
            }
        });
    };

    oc_ezdefi_admin.prototype.editCoinConfig = function() {
        if(!$(selectors.formConfig).valid()) return;

        var url = $(this).data('url_edit');
        var coinId = $(this).data('coin_id');
        var discount = $('#edit-discount-' + coinId).val();
        var paymentLifetime = $('#edit-payment-lifetime-' + coinId).val();
        var walletAddress = $('#edit-wallet-address-' + coinId).val();
        var safeBlockDistant = $('#edit-safe-block-distant-' + coinId).val();

        $.ajax({
            url: url,
            method: "POST",
            data: {
                coin_id: coinId,
                discount: discount,
                payment_lifetime: paymentLifetime,
                wallet_address: walletAddress,
                safe_block_distant: safeBlockDistant
            },
            success: function (response) {
                var data = JSON.parse(response).data;
                if(data.status === 'success') {
                    discount = discount === '' ? 0 : discount;
                    $('#config-row-'+coinId).find('.coin-discount').html(discount +'%');
                    $('#config-row-'+coinId).find('.coin-payment-lifetime').html(paymentLifetime ? 0 :paymentLifetime);
                    $('#config-row-'+coinId).find('.coin-wallet-address').html(walletAddress);
                    $('#config-row-'+coinId).find('.coin-safe-block-distant').html(safeBlockDistant ? 0 : safeBlockDistant);
                    $('#modal-edit-' + coinId).modal('toggle');
                } else {
                    alert(data.message);
                    var oldDiscount = $('#config-row-'+coinId).find('.coin-discount').html();
                    var oldPaymentLifeTime = $('#config-row-'+coinId).find('.coin-payment-lifetime').html();
                    var oldWalletAddress = $('#config-row-'+coinId).find('.coin-wallet-address').html();
                    var oldSafeBlockDistant = $('#config-row-'+coinId).find('.coin-safe-block-distant').html();
                    $('#edit-discount-' + coinId).val(oldDiscount);
                    $('#edit-payment-lifetime-' + coinId).val(oldPaymentLifeTime);
                    $('#edit-wallet-address-' + coinId).val(oldWalletAddress);
                    $('#edit-safe-block-distant-' + coinId).val(oldSafeBlockDistant);
                }
            }
        });
    };

    oc_ezdefi_admin.prototype.addCoinConfigListener = function() {
        var url = $(selectors.btnAdd).data('list_coin_url');
        var container = `<tr class="${this.formatSelectorToClassName(selectors.cloneRowCoinConfig)}">
                <td class="sortable-handle">
                    
                </td>
                <td>
                    <select class="form-control ${this.formatSelectorToClassName(selectors.selectCoinConfig)}" style="width: 200px" data-list_coin_url="${url}">
                        <option value=""></option>
                    </select>
                </td>
                <td></td>
                <td></td>
                <td>
                    <div class="col-sm-10"><input type="text" class="form-control"></div>
                </td>
                <td><input type="text" class="form-control"></td>
                <td><input type="text" class="form-control"></td>
                <td><input type="text" class="form-control"></td>
            </tr>`;
        $(selectors.coinConfigTable).append(container);

        this.initSelectCoinConfig();
        $(selectors.selectCoinConfig).on('select2:select', this.selectCoinListener.bind(this));
    };

    oc_ezdefi_admin.prototype.initSelectCoinConfig = function() {
        var that = this;
        $("select.ezdefi-select-coin").select2({
            ajax: {
                url: $(selectors.selectCoinConfig).data('list_coin_url'),
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        keyword: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;

                    return {
                        results: data.data
                    };
                },
                cache: true
            },
            escapeMarkup: function (markup) { return markup; },
            minimumInputLength: 1,
            templateResult: that.formatRepo,
            // templateSelection: that.formatRepoSelection,
            placeholder: "Enter name"
        });
    }

    oc_ezdefi_admin.prototype.selectCoinListener = function (e) {
        var data = e.params.data;
        var order = !$( selectors.coinConfigTable + ' input[name="coin_order"]').last().val() ? 0 : parseInt($(selectors.coinConfigTable +' input[name="coin_order"]').last().val()) + 1;
        var duplicate = false;

        $(selectors.coinIdInput).each(function () {
            if($(this).val() === data._id) {
                duplicate = true;
            }
        });

        if(!duplicate) {
            var container = `<tr>
                <td class="sortable-handle">
                    <i class="fa fa-arrows" aria-hidden="true"></i>
                    <input type="hidden" class="${this.formatSelectorToClassName(selectors.coinOrderInput)}" name="${data._id}[coin_order]" value="${order}">
                    <input type="hidden" class="${this.formatSelectorToClassName(selectors.coinIdInput)}" name="${data._id}[coin_id]" value="${data._id}">
                </td>
                <td>
                    <img src="${data.logo}" alt="">
                    <input type="hidden" value="${data.logo}" name="${data._id}[coin_logo]">
                </td>
                <td>${data.symbol} <input type="hidden" class="${this.formatSelectorToClassName(selectors.coinSymbolInput)}" value="${data.symbol}" name="${data._id}[coin_symbol]"></td>
                <td>${data.name} <input type="hidden" class="${this.formatSelectorToClassName(selectors.coinNameInput)}" value="${data.name}" name="${data._id}[coin_name]"> </td>
                <td>
                    <div class="row">
                        <div class="col-sm-10"><input type="text" class="form-control ${this.formatSelectorToClassName(selectors.coinDiscountInput)}" name="${data._id}[coin_discount]"></div>
                        <div class="col-sm-2 text-left"></div>
                    </div>
                </td>
                <td><input type="text" class="form-control ${this.formatSelectorToClassName(selectors.coinPaymentLifetimeInput)}" name="${data._id}[coin_payment_life_time]"></td>
                <td><input type="text" class="form-control ${this.formatSelectorToClassName(selectors.coinWalletAddressInput)}" name="${data._id}[coin_wallet_address]"></td>
                <td><input type="text" class="form-control ${this.formatSelectorToClassName(selectors.coinSafeBlockDistantInput)}" name="${data._id}[coin_safe_block_distant]"></td>
                <td><input type="text" class="form-control ${this.formatSelectorToClassName(selectors.coinDecimalInput)}" value="${data.suggestedDecimal}"></td>
            </tr>`
            $(selectors.coinConfigTable).append(container);
            $(selectors.cloneRowCoinConfig).remove();

            this.initValidate();
            this.updateCoinConfigOrder();
        }
    };

    oc_ezdefi_admin.prototype.formatRepoSelection = function(repo) {
        return repo.id;
    };

    oc_ezdefi_admin.prototype.formatRepo = function(repo) {
        if (repo.loading) {
            return repo.text;
        }
        return `<div class='select2-result-repository clearfix select-coin-box' id="${repo.id}">
                <div class='select2-result-repository__meta'>
                    <div class="row">
                        <div class="col-sm-3">
                            <img src="${repo.logo}" alt="" style="width:100%">
                        </div>
                        <div class='select2-result-repository__title col-lg-9 text-justify' style="padding-top: 3px">${repo.name}</div>
                    </div>
                </div>
            </div>`;
    };

    oc_ezdefi_admin.prototype.initSortable= function() {
        var that = this;
        $( selectors.coinConfigTable ).sortable({
            handle: '.sortable-handle',
            stop: function(event, ui) {
                that.updateCoinConfigOrder();
            }
        });
    };

    oc_ezdefi_admin.prototype.updateCoinConfigOrder = function () {
        $(selectors.coinOrderInput).each(function(order) {
            $(this).val(order);
        })
    };

    oc_ezdefi_admin.prototype.formatSelectorToClassName = function(selector) {
        return selector.slice(1, selectors.length);
    };

    new oc_ezdefi_admin();


    var oc_ezdefi_exception = function () {
        this.loadException();
        $("#btn-delete-exception").click(this.deleteException).bind(this);
        $("#btn-confirm-paid-exception").click();
    };

    oc_ezdefi_exception.prototype.loadException = function () {
        var that = this;
        var container = $("#exception-content-box");
        var url = $("#url-get-exception").val();
        var totalNumber = $("#total-exception").val();
        container.pagination({
            dataSource: url,
            locator: 'items',
            totalNumber: totalNumber,
            pageSize: 10,
            showPageNumbers: true,
            showPrevious: true,
            showNext: true,
            showNavigator: true,
            showFirstOnEllipsisShow: true,
            showLastOnEllipsisShow: true,
            ajax: {
                beforeSend: function() {
                    container.prev().html('Loading data from server ...');
                }
            },
            callback: function(response, pagination) {
                var exceptionRecords = that.convertExceptionResponse(response);
                var dataHtml = `<table class="table">
                        <thead>
                        <tr>
                            <th>STT</th>
                            <th>Currency</th>
                            <th>Amount</th>
                            <th>Order</th>
                        </tr>
                        </thead>
                        <tbody>`;
                let stt = 1;
                $.each(exceptionRecords, function (exceptionKey, groupException) {
                    let currency = groupException.currency;
                    let amountId = groupException.amount_id;
                    let orderItem = "<div>";
                    $.each(groupException.orders, function (key, exceptionData) {
                        orderItem += `<div id="exception-order-${exceptionData[0]}">
                            <div>
                                <p>order_id: ${exceptionData[1]} </p>
                                <p>email: ${exceptionData[2]} </p>
                                <p>expiration: ${exceptionData[3]} </p>
                                <p>paid: ${exceptionData[4] === '1' ? 'yes' : 'no'} </p>
                                <p>Pay by ezdefi wallet: ${exceptionData[6] === '1' ? 'no' : 'yes'} </p>
                            </div>
                            <div>
                                <button class="btn btn-primary btn-confirm-paid" data-toggle="modal" data-target="#confirm-paid-order-exception" data-exception-id="${exceptionData[0]}">confirm paid</button>
                                <button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-order-exception" data-exception-id="${exceptionData[0]}">delete</button>
                            </div>
                        </div>`;
                    });
                    orderItem += "</div>";
                    dataHtml += `<tr>
                                <td>${stt}</td>
                                <td>${currency}</td>
                                <td>${amountId}</td>
                                <td>${orderItem}</td>
                            </tr>`;
                    stt++;
                });
                dataHtml += `</tbody>
                    </table>`;

                container.prev().html(dataHtml);
                that.addConfirmPaidListener();
                that.addDeleteExceptionListener();
            }
        });
    };

    oc_ezdefi_exception.prototype.addConfirmPaidListener = function (data) {
        $(".btn-confirm-paid").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-confirm-id").val(exceptionId);
        });
    };

    oc_ezdefi_exception.prototype.addDeleteExceptionListener = function (data) {
        $(".btn-delete-exception").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-delete-id").val(exceptionId);
        });
    };

    oc_ezdefi_exception.prototype.convertExceptionResponse = function (data) {
        for (let i in data) {
            let orders = data[i].group_order.split(',');
            for(let i in orders) {
                orders[i] = orders[i].split('--');
            }
            data[i].orders = orders;
        }
        return data;
    };

    oc_ezdefi_exception.prototype.deleteException = function () {
        let url = $("#url-delete-exception").val();
        let exceptionId = $("#exception-delete-id").val();
        console.log(url,exceptionId);
        $.ajax({
            url: url,
            method: "POST",
            data: { exception_id: exceptionId },
            success: function (response) {
                $("#exception-order-"+exceptionId).remove();
                $('#delete-order-exception').modal('toggle');
            }
        });
    };



    new oc_ezdefi_exception();

});