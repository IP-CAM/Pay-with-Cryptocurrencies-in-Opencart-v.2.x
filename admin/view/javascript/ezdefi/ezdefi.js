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
        editCoinDecimalInput: '.edit-coin-config__decimal',
        cancelSelectCoin: '.cancel-select-coin',
        modalEditCoin: '.modal-edit-coin'
    };

    var oc_ezdefi_admin = function() {
        $(selectors.btnAdd).click(this.addCoinConfigListener.bind(this));
        $(selectors.btnDelete).click(this.deleteCoinConfig);
        $(selectors.btnEdit).click(this.editCoinConfig);
        $(selectors.enableSimplePayInput).click(this.showSimplePayConfig);
        $(selectors.editCoinDecimalInput).focus(this.showWarningChangeDecimal);
        $(selectors.modalEditCoin).on("hide.bs.modal", this.revertEditInput);
        this.showSimplePayConfig();
        this.initSortable();
        this.initValidate();
    };

    oc_ezdefi_admin.prototype.initAllowInput = function() {
        $('.validate-float').keypress(function(eve) {
            if ((eve.which != 46 || $(this).val().indexOf('.') != -1) && (eve.which < 48 || eve.which > 57) || (eve.which == 46 && $(this).caret().start == 0) ) {
                eve.preventDefault();
            }
            $('.validate-float').keyup(function(eve) {
                if($(this).val().indexOf('.') == 0) {    $(this).val($(this).val().substring(1));
                }
            });
        });
        //
        $('.only-positive-integer').keypress(function (eve) {
            if (eve.which < 48 || eve.which > 57) {
                eve.preventDefault();
            }
        });
    };

    oc_ezdefi_admin.prototype.showWarningChangeDecimal = function() {
        $(".warning-edit-decimal").css('display', 'block');
    };

    oc_ezdefi_admin.prototype.initValidate = function() {
        this.initAllowInput();
        $.validator.addMethod("integer", function(value, element) {
            return this.optional( element ) || (Math.floor(value) == value && $.isNumeric(value) && value >= 0);
        }, "This field should be positive integer");
        $.validator.addMethod("float", function(value, element) {
            return this.optional( element ) || value.match(/^-?\d*(\.\d+)?$/);
        }, "This field should be float");
        $.validator.addMethod("more_than_0", function(value, element) {
            return this.optional( element ) || value > 0;
        }, "Please enter a value more than 0");
        $( selectors.formConfig ).validate({
            submitHandler: function(form) {
                form.submit();
            },
            errorPlacement: function(error, element) {
                if (element.data('error-label')) {
                    error.insertBefore($(element.data('error-label')));
                } else {
                    error.insertAfter(element);
                }
            }
        });

        this.validateAllInput(selectors.gatewayApiUrlInput, {url:true, required: true});
        // this.validateAllInput(selectors.apiKeyInput, {required: true});
        this.validateAllInput(selectors.orderStatusInput, {required: true});
        this.validateAllInput(selectors.coinDiscountInput, {max: 100, min:0, float: true});
        this.validateAllInput(selectors.coinNameInput, {required: true});
        this.validateAllInput(selectors.coinIdInput, {required: true});
        this.validateAllInput(selectors.coinOrderInput, {required: true});
        this.validateAllInput(selectors.coinSymbolInput, {required: true});
        this.validateAllInput(selectors.coinPaymentLifetimeInput, {integer: true});
        this.validateAllInput(selectors.coinSafeBlockDistantInput, {integer: true});
        this.validateAllInput(selectors.variationInput, {more_than_0: true, max: 100, float: true, required: () => $(selectors.enableSimplePayInput).is(':checked')});
        this.validateAllInput(selectors.coinDecimalInput, {integer: true, min:2, max:14, required: () => $(selectors.enableSimplePayInput).is(':checked')});
        this.validateAllInput(selectors.enableSimplePayInput, {required: () => !$(selectors.enableEscrowPayInput).is(':checked'), messages: {required: ''} });
        this.validateAllInput(selectors.enableEscrowPayInput, {
            required: () => !$(selectors.enableSimplePayInput).is(':checked'),
            messages: {required: 'Choose at least one payment method'},
        });
        this.validateAllInput(selectors.coinWalletAddressInput, {required: true});
        this.validateApiKey();
    };

    oc_ezdefi_admin.prototype.validateApiKey = function() {
        $(selectors.apiKeyInput).rules('add', {
            required: true,
            remote: {
                url: $(selectors.formConfig).data('url_validate_api_key'),
            },
            messages: {
                remote: "This wallet address is invalid"
            }
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
            $(".coin-decimal").css('display','table-cell');
        } else {
            $(selectors.decimalBox).css('display','none');
            $(selectors.variationBox).css('display','none');
            $(".coin-decimal").css('display','none');
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
                    alert(data.message);
                    $('#modal-delete-'+coinId).modal('hide');
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
        var thisElem = $(this);
        $(this).prop('disabled', true);

        var url = $(this).data('url_edit');
        var coinId = $(this).data('coin_id');
        var discount = $('#edit-discount-' + coinId).val();
        var paymentLifetime = $('#edit-payment-lifetime-' + coinId).val();
        var walletAddress = $('#edit-wallet-address-' + coinId).val();
        var safeBlockDistant = $('#edit-safe-block-distant-' + coinId).val();
        var decimal = $('#edit-decimal-' + coinId).val();

        $.ajax({
            url: url,
            method: "POST",
            data: {
                coin_id: coinId,
                coin_discount: discount,
                coin_payment_life_time: paymentLifetime,
                coin_wallet_address: walletAddress,
                coin_safe_block_distant: safeBlockDistant,
                coin_decimal: decimal
            },
            success: function (response) {
                var data = JSON.parse(response).data;
                if(data.status === 'success') {
                    alert(data.message);
                    discount = discount === '' ? 0 : discount;
                    $('#config-row-'+coinId).find('.coin-discount').html(discount);
                    $('#config-row-'+coinId).find('.coin-payment-lifetime').html(paymentLifetime ? paymentLifetime : 0);
                    $('#config-row-'+coinId).find('.coin-wallet-address').html(walletAddress);
                    $('#config-row-'+coinId).find('.coin-safe-block-distant').html(safeBlockDistant ? safeBlockDistant : 0);
                    $('#config-row-'+coinId).find('.coin-decimal').html(decimal ? decimal : 0);
                    $('#modal-edit-' + coinId).modal('hide')
                } else {
                    alert(data.message);
                    var oldDiscount = $('#config-row-'+coinId).find('.coin-discount').html();
                    var oldPaymentLifeTime = $('#config-row-'+coinId).find('.coin-payment-lifetime').html();
                    var oldWalletAddress = $('#config-row-'+coinId).find('.coin-wallet-address').html();
                    var oldSafeBlockDistant = $('#config-row-'+coinId).find('.coin-safe-block-distant').html();
                    var oldDecimal = $('#config-row-'+coinId).find('.coin-decimal').html();
                    $('#edit-discount-' + coinId).val(oldDiscount);
                    $('#edit-payment-lifetime-' + coinId).val(oldPaymentLifeTime);
                    $('#edit-wallet-address-' + coinId).val(oldWalletAddress);
                    $('#edit-safe-block-distant-' + coinId).val(oldSafeBlockDistant);
                    $('#edit-decimal-' + coinId).val(oldDecimal);
                }
                thisElem.prop('disabled', false);
            }
        });
    };

    oc_ezdefi_admin.prototype.revertEditInput = function(e, coinId =  null) {
        if(coinId === null) {
            coinId = $(this).data('coin-id');
        }
        var oldDiscount = $('#config-row-'+coinId).find('.coin-discount').html();
        var oldPaymentLifeTime = $('#config-row-'+coinId).find('.coin-payment-lifetime').html();
        var oldWalletAddress = $('#config-row-'+coinId).find('.coin-wallet-address').html();
        var oldSafeBlockDistant = $('#config-row-'+coinId).find('.coin-safe-block-distant').html();
        var oldDecimal = $('#config-row-'+coinId).find('.coin-decimal').html();
        $('#edit-discount-' + coinId).val(oldDiscount);
        $('#edit-payment-lifetime-' + coinId).val(oldPaymentLifeTime);
        $('#edit-wallet-address-' + coinId).val(oldWalletAddress);
        $('#edit-safe-block-distant-' + coinId).val(oldSafeBlockDistant);
        $('#edit-decimal-' + coinId).val(oldDecimal);
        $(".warning-edit-decimal").css('display', 'none');
    };

    oc_ezdefi_admin.prototype.addCoinConfigListener = function() {
        var url = $(selectors.btnAdd).data('list_coin_url');
        var container = `<tr class="${this.formatSelectorToClassName(selectors.cloneRowCoinConfig)}">
                <td class="sortable-handle">
                </td>
                <td>
                    <select class="form-control ${this.formatSelectorToClassName(selectors.selectCoinConfig)}" style="width: 200px" data-list_coin_url="${url}">
                        <option value=""></option>
                    </select><br>
                    <a class="cancel-select-coin ezdefi-btn" >cancel</a>
                </td>
                <td></td>
                <td></td>
                <td>
                    <input type="text" class="form-control">
                </td>
                <td><input type="text" class="form-control"></td>
                <td><input type="text" class="form-control"></td>
                <td><input type="text" class="form-control"></td>
            </tr>`;
        $(selectors.coinConfigTable).append(container);

        this.initSelectCoinConfig();
        this.initCancelSelectCoin();
        $(selectors.selectCoinConfig).on('select2:select', this.selectCoinListener.bind(this));
    };

    oc_ezdefi_admin.prototype.initCancelSelectCoin = function() {
        $(selectors.cancelSelectCoin).click(function () {
            $(this).parent().parent().remove();
        });
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
                    <input type="hidden" name="${data._id}[description]" value="${data.description ? data.description : ''}">
                </td>
                <td>
                    <img src="${data.logo}" alt="" class="coin-config__logo">
                    <input type="hidden" value="${data.logo}" name="${data._id}[coin_logo]">
                </td>
                <td> 
                    <input type="hidden" class="${this.formatSelectorToClassName(selectors.coinSymbolInput)}" value="${data.symbol}" name="${data._id}[coin_symbol]">
                    ${data.symbol} <br>
                    <a class="cancel-select-coin ezdefi-btn">Cancel</a>
                </td>
                <td>${data.name} <input type="hidden" class="${this.formatSelectorToClassName(selectors.coinNameInput)}" value="${data.name}" name="${data._id}[coin_name]"> </td>
                <td>
                    <div class="row">
                        <div class="col-sm-10"><input type="number" class="form-control ${this.formatSelectorToClassName(selectors.coinDiscountInput)} validate-float" name="${data._id}[coin_discount]"></div>
                        <div class="col-sm-2 text-left"></div>
                    </div>
                </td>
                <td><input type="number" class="form-control ${this.formatSelectorToClassName(selectors.coinPaymentLifetimeInput)} only-positive-integer" name="${data._id}[coin_payment_life_time]"></td>
                <td><input type="text" class="form-control ${this.formatSelectorToClassName(selectors.coinWalletAddressInput)}" name="${data._id}[coin_wallet_address]"></td>
                <td><input type="number" class="form-control ${this.formatSelectorToClassName(selectors.coinSafeBlockDistantInput)} only-positive-integer" name="${data._id}[coin_safe_block_distant]"></td>
                <td><input type="number" class="form-control ${this.formatSelectorToClassName(selectors.coinDecimalInput)} only-positive-integer" name="${data._id}[coin_decimal]" value="${data.suggestedDecimal}"></td>
            </tr>`;
            $(selectors.coinConfigTable).append(container);
            $(selectors.cloneRowCoinConfig).remove();

            this.initValidate();
            this.initCancelSelectCoin();
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
                    <div>
                        <span>
                            <img src="${repo.logo}" alt="" class="select-coin__logo">
                        </span>
                        <span class='select2-result-repository__title text-justify select-coin__name'>${repo.name}</span>
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
});