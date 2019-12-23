$(function () {
    const text = {
        orderHistoryComment: "confirm from ezDeFi exception management",
        assignOrderComment: "Assign order from ezDeFi exception management",
        revertOrderComment: "Revert order from ezDeFi exception management"
    };
    const ORDER_STATUS = {
        PENDING: 0,
        PROCESSING: 2,
    };

    var global = {};

    var oc_ezdefi_exception = function () {
        this.searchException();
        $("#btn-delete-exception").click(this.deleteException.bind(this));
        $("#btn-confirm-paid-exception").click(this.confirmPaidException.bind(this));
        $("#btn-revert-order").click(this.revertOrder.bind(this));
        $("#exception-search-by-amount").change(this.searchException.bind(this));
        $("#exception-search-by-order").change(this.searchException.bind(this));
        $("#exception-search-by-email").change(this.searchException.bind(this));
        $("#btn-search-exception").click(this.searchException.bind(this));
        $("input[name='filter-by-currency']").change(this.searchException.bind(this));
        $(".tab-radio-input").change(this.selectTabListener);
        this.detectTabToShow();
    };

    oc_ezdefi_exception.prototype.selectTabListener = function() {
        let tab = $(this).data('tab');
        localStorage.setItem('tab', tab);
    };

    oc_ezdefi_exception.prototype.detectTabToShow = function () {
        let tab = localStorage.getItem('tab') !== null ? localStorage.getItem('tab') : 'config';

        $("input[name='btn-radio-choose-tab']").each(function (e, b) {
            if($(this).data('tab') == tab) {
                $(this).prop('checked',true);
            }
        });
    };

    oc_ezdefi_exception.prototype.searchException = function (page = 1, totalNumber = null) {
        var that = this;
        var container = $("#exception-content-box");
        var keywordAmount = $("#exception-search-by-amount").val();
        var keywordOrder = $("#exception-search-by-order").val();
        var keywordEmail = $("#exception-search-by-email").val();
        var urlGetException = $("#url-search-exceptions").val();
        var urlGetAllOrderPending = $("#url-get-order-pending").val();
        var currency = $("input[name='filter-by-currency']:checked").val() ? $("input[name='filter-by-currency']:checked").val() : '';

        var paginationObject = {
            dataSource: urlGetException + '&amount=' +keywordAmount + '&order_id='+ keywordOrder + '&email=' + keywordEmail + '&currency=' + currency,
            locator: 'items.exceptions',
            pageNumber: page,
            pageSize: 10,
            ajax: {
                beforeSend: function() {
                    container.prev().html('Loading data from server ...');
                }
            },
            callback: function(response, pagination) {
                $("#current-page-exception").val(pagination.pageNumber);
                var dataHtml = `<table class="table">
                        <thead>
                        <tr>
                            <th>${language.ordinal}</th>
                            <th>${language.currency}</th>
                            <th>${language.amount}</th>
                            <th>${language.order}</th>
                        </tr>
                        </thead>
                        <tbody>`;
                let tmp = (pagination.pageNumber - 1) * pagination.pageSize + 1;
                $.each(response, function (exceptionKey, exceptionRecord) {
                    let currency = exceptionRecord.currency;
                    let amountId = exceptionRecord.amount_id;
                    var exceptionId = exceptionRecord.id;
                    var orderId = exceptionRecord.order_id;
                    var email = exceptionRecord.email;
                    var expiration = exceptionRecord.expiration;
                    var paidStatus = exceptionRecord.paid;
                    var hasAmount = exceptionRecord.has_amount;
                    var explorerUrl = exceptionRecord.explorer_url;
                    let orderItem = "<div>";
                    if(orderId == null) {
                        amountId += `<p><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>`;
                    } else {
                        if(paidStatus === '0') {
                            var paymentStatus = 'Have not paid';
                        } else if(paidStatus === '1') {
                            var paymentStatus = 'Paid on time';
                        } else {
                            var paymentStatus = 'Paid on expiration';
                        }
                        orderItem += `<div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                            <div class="exception-order-info">
                                <p><span class="exception-order-label-1">${language.orderId}:</span> <span class="exception-order-info__data"> ${orderId} </span></p>
                                <p><span class="exception-order-label-1">${language.email}:</span> <span class="exception-order-info__data">${email} </span></p>
                                <p><span class="exception-order-label-1">${language.expiration}:</span> <span class="exception-order-info__data"> ${expiration} </span></p>
                                <p><span class="exception-order-label-1">${language.paid}:</span> <span class="exception-order-info__data">${paymentStatus} </span></p>
                                <p><span class="exception-order-label-1">${language.payByEzdefi}:</span> ${hasAmount === '1' ? 'no' : 'yes'} </p>
                                <p class="${explorerUrl == '' ? 'hidden':''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>
                            </div>
                            <div class="exception-order-button-box">`;
                        orderItem += paidStatus == 1 ? `<button class="btn btn-primary btn-revert-order" data-toggle="modal" data-target="#modal-revert-order-exception" data-exception-id="${exceptionId}" data-order-id="${orderId}">${language.revert}</button>
                                                            <button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-order-exception" data-exception-id="${exceptionId}">${language.delete}</button>` : '';
                        orderItem += paidStatus != 1 ? `<button class="btn btn-primary btn-confirm-paid" data-toggle="modal" data-target="#confirm-paid-order-exception" data-exception-id="${exceptionId}" data-order-id="${orderId}">${language.confirmPaid}</button>
                                                        <button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-order-exception" data-exception-id="${exceptionId}">${language.delete}</button>` : '';
                        orderItem +=`
                                    </div>
                                </div>`;
                    }
                    orderItem += `<div class="exception-order-box">
                                        <div class="exception-order-info">
                                             <select class="form-control all_order_pending" style="width: 300px" data-list_coin_url="${urlGetAllOrderPending}" id="exception-select-order-${tmp}" data-tmp="${tmp}"></select>
                                        </div>
                                        <div class="exception-order-button-box">
                                            <button class="btn btn-info btn-assign-order" id="btn-assign-order-${tmp}" data-toggle="modal" data-target="#confirm-paid-order-exception" data-exception-id="${exceptionId}" data-old-order-id="${orderId}" data-order-id="" style="opacity: 0">Assign</button>
                                        </div>
                                    </div>
                                </div>`;
                    dataHtml += `<tr>
                                <td>${tmp}</td>
                                <td>${currency}</td>
                                <td>${amountId}</td>
                                <td>${orderItem}</td>
                            </tr>`;
                    tmp++;
                });
                dataHtml += `</tbody>
                    </table>`;
                container.prev().html(dataHtml);
                that.addConfirmPaidListener();
                that.addDeleteExceptionListener();
                that.addAssignOrderListener();
                that.addRevertOrderListener();
                that.initSelectOrder();
            }
        };
        if (totalNumber) {
            paginationObject.totalNumber = totalNumber;
        } else {
            paginationObject.totalNumberLocator = function(response) {
                // you can return totalNumber by analyzing response content
                $("#total-number-exception").val(response.total_exceptions);
                return response.total_exceptions;
            }
        }

        container.pagination(paginationObject);
    };

    oc_ezdefi_exception.prototype.addConfirmPaidListener = function (data) {
        $(".btn-confirm-paid").click(function () {
            let exceptionId = $(this).data('exception-id');
            let orderId = $(this).data('order-id');
            $("#exception-id--confirm").val(exceptionId);
            $("#exception-order-id--confirm").val(orderId);
            $("#exception-old-order-id--confirm").val();
            $("#confirm-dialog-assign").prop('checked', false);
            $(".exception-loading-icon__confirm-paid").css('display', 'none');
        });
    };

    oc_ezdefi_exception.prototype.addDeleteExceptionListener = function (data) {
        $(".btn-delete-exception").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-id--delete").val(exceptionId);
            $(".exception-loading-icon__delete").css('display', 'none');
        });
    };

    oc_ezdefi_exception.prototype.addRevertOrderListener = function() {
        $(".btn-revert-order").click(function () {
            let exceptionId = $(this).data('exception-id');
            let orderId = $(this).data('order-id');
            $("#exception-id--revert").val(exceptionId);
            $("#exception-order-id--revert").val(orderId);
            $(".exception-loading-icon__revert").css('display', 'none');
        });
    };

    oc_ezdefi_exception.prototype.addAssignOrderListener = function() {
        $(".btn-assign-order").click(function () {
            let orderId = $(this).data('order-id');
            let oldOrderId = $(this).data('old-order-id');
            let exceptionId = $(this).data('exception-id');
            $("#exception-order-id--confirm").val(orderId);
            $("#exception-id--confirm").val(exceptionId);
            $("#exception-old-order-id--confirm").val(oldOrderId);
            $("#confirm-dialog-assign").prop('checked', true);
            $(".exception-loading-icon__confirm-paid").css('display', 'none');
        })
    };

    oc_ezdefi_exception.prototype.deleteException = function (e, exceptionId = null) {
        var that = this;
        if(exceptionId == null) {
            exceptionId = $("#exception-id--delete").val();
        }
        $("#btn-delete-exception").prop('disabled', true);
        let url = $("#url-delete-exception").val();
        $.ajax({
            url: url,
            method: "POST",
            data: { exception_id: exceptionId },
            beforeSend: function() {
                $(".exception-loading-icon__delete").css('display', 'inline-block');
            },
            success: function (response) {
                if ($('#delete-order-exception').hasClass('in')) {
                    $('#delete-order-exception').modal('toggle');
                }
                $("#btn-delete-exception").prop('disabled', false);
                let page = $("#current-page-exception").val();
                let totalNumber = $("#total-number-exception").val();
                that.searchException(page, totalNumber);
            },
            error: function () {
                $("#btn-delete-exception").prop('disabled', false);
            }
        });
    };

    oc_ezdefi_exception.prototype.deleteExceptionByOrderId = function (orderId) {
        var that = this;
        let urlDeleteExceptionByOrderId = $("#url-delete-exception-by-order-id").val();
        $.ajax({
            url: urlDeleteExceptionByOrderId,
            method: "POST",
            data: { order_id: orderId},
            success: function (response) {
                let page = $("#current-page-exception").val();
                let totalNumber = $("#total-number-exception").val();
                that.searchException(page, totalNumber);
            }
        });
    };

    oc_ezdefi_exception.prototype.confirmPaidException = function () {
        $("#btn-confirm-paid-exception").prop('disabled', true);
        let urlAddOrderHistory = $("#url-add-order-history").val();
        let orderId = $("#exception-order-id--confirm").val();
        let exceptionId = $('#exception-id--confirm').val();
        var that = this;
        $.ajax({
            url: urlAddOrderHistory + '&store_id=0&order_id='+orderId,
            method: "POST",
            data: {
                order_status_id: ORDER_STATUS.PROCESSING,
                comment: exceptionId ? text.orderHistoryComment : text.assignOrderComment
            },
            beforeSend:function() {
                $(".exception-loading-icon__confirm-paid").css('display', 'inline-block');
            },
            success: function (response) {
                let isAssign = $("#confirm-dialog-assign").prop('checked');
                let oldOrderId = $("#exception-old-order-id--confirm").val();
                if(isAssign && oldOrderId) {
                    that.deleteExceptionByOrderId(oldOrderId);
                } else if( isAssign && !oldOrderId) {
                    that.deleteExceptionByOrderId(orderId);
                    that.deleteException(null, exceptionId);
                } else {
                    that.deleteExceptionByOrderId(orderId);
                }
                $("#confirm-paid-order-exception").modal('toggle');
                $("#btn-confirm-paid-exception").prop('disabled', false);
            },
            error: function () {
                let isAssign = $("#confirm-dialog-assign").prop('checked');
                let oldOrderId = $("#exception-old-order-id--confirm").val();
                if(isAssign && oldOrderId) {
                    that.deleteExceptionByOrderId(oldOrderId);
                } else if( isAssign && !oldOrderId) {
                    that.deleteExceptionByOrderId(orderId);
                    that.deleteException(null, exceptionId);
                } else {
                    that.deleteExceptionByOrderId(orderId);
                }
                $("#confirm-paid-order-exception").modal('toggle');
                $("#btn-confirm-paid-exception").prop('disabled', false);
            }
        });
    };

    oc_ezdefi_exception.prototype.revertOrder = function() {
        $("#btn-revert-order").prop('disabled', true);
        let urlAddOrderHistory = $("#url-add-order-history").val();
        let orderId = $("#exception-order-id--revert").val();
        let exceptionId = $('#exception-id--revert').val();
        var that = this;
        $.ajax({
            url: urlAddOrderHistory + '&store_id=0&order_id='+orderId,
            method: "POST",
            data: {
                order_status_id: ORDER_STATUS.PENDING,
                comment: text.revertOrderComment
            },
            beforeSend: function() {
                $(".exception-loading-icon__revert").css('display', 'inline-block');
            },
            success: function (response) {
                that.revertException(exceptionId);
                $("#modal-revert-order-exception").modal('toggle');
                $("#btn-revert-order").prop('disabled', false);
            },
            error: function () {
                that.deleteException(null, exceptionId);
                $("#modal-revert-order-exception").modal('toggle');
                $("#btn-revert-order").prop('disabled', false);
            }
        });
    };

    oc_ezdefi_exception.prototype.revertException = function(exceptionId) {
        var that = this;
        var url = $("#url-revert-order-exception").val();
        $.ajax({
            url: url,
            method: "POST",
            data: {
                exception_id: exceptionId,
            },
            success: function (response) {
                let page = $("#current-page-exception").val();
                let totalNumber = $("#total-number-exception").val();
                that.searchException(page, totalNumber);
            },
        });

    };


    oc_ezdefi_exception.prototype.initSelectOrder = function() {
        var that = this;
        $("select.all_order_pending").select2({
            ajax: {
                url: $("select.all_order_pending").data('list_coin_url'),
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
                    global.temp = 0;
                    return {
                        results: data.data,
                        pagination: {
                            more: 1
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function (markup) { return markup; },
            minimumInputLength: 0,
            templateResult: that.formatRepo,
            templateSelection: that.formatRepoSelection,
            placeholder: "Enter order"
        });
        $("select.all_order_pending").on('select2:select', this.selectOrderPendingListener);
    };

    oc_ezdefi_exception.prototype.formatRepoSelection = function (repo) {
        return repo.total ? 'Order: ' + repo.id : 'Choose order to assign';
    };

    oc_ezdefi_exception.prototype.formatRepo = function(repo) {
        if (repo.loading) {
            return repo.text;
        }
        global.temp += 1;
        return `<div class='select2-result-repository clearfix' id="order-pending-${repo.id}">
                    <div class='select2-result-repository__meta'>
                        <div class='select2-result-repository__title text-justify ${global.temp%2 ? 'background-grey': ''}' style="padding-top: 3px;">
                            <p><span class="exception-order-label-2">${language.orderId}:</span>${repo.id}</p>
                            <p><span class="exception-order-label-2">${language.email}:</span>${repo.email}</p>
                            <p><span class="exception-order-label-2">${language.customer}:</span>${repo.firstname + ' ' + repo.lastname}</p>
                            <p><span class="exception-order-label-2">${language.price}:</span>${repo.total +' ' + repo.currency_code}</p>
                            <p><span class="exception-order-label-2">${language.createAt}:</span>${repo.date_added}</p>
                        </div>
                    </div>
                </div>`;
    };

    oc_ezdefi_exception.prototype.selectOrderPendingListener = function (e) {
        var data = e.params.data;
        var amountId = $(this).data('tmp');
        var buttonAssign = $("#btn-assign-order-"+amountId);
        buttonAssign.css('opacity', 100);
        buttonAssign.data('order-id', data.id);
    };

    new oc_ezdefi_exception();
});