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
        this.loadException();
        $("#btn-delete-exception").click(this.deleteException.bind(this));
        $("#btn-confirm-paid-exception").click(this.confirmPaidException.bind(this));
        $("#btn-revert-order").click(this.revertOrder.bind(this));
        $("#exception-search-input").keyup(this.searchException.bind(this));
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

    oc_ezdefi_exception.prototype.loadException = function (keyword = '') {
        var that = this;
        var container = $("#exception-content-box");
        var urlGetException = $("#url-search-exceptions").val();
        var urlGetAllOrderPending = $("#url-get-order-pending").val();
        var currency = $("input[name='filter-by-currency']:checked").val() ? $("input[name='filter-by-currency']:checked").val() : '';
        container.pagination({
            dataSource: urlGetException + '&keyword=' +keyword + '&currency=' + currency,
            locator: 'items.exceptions',
            // totalNumber: totalNumber,
            totalNumberLocator: function(response) {
                // you can return totalNumber by analyzing response content
                return response.total_exceptions;
            },
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
                console.log(response, pagination);
                var exceptionRecords = that.convertExceptionResponse(response);
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
                $.each(exceptionRecords, function (exceptionKey, groupException) {
                    let currency = groupException.currency;
                    let amountId = groupException.amount_id;
                    let unknownTxExceptionId = '';
                    let orderItem = "<div>";
                    $.each(groupException.orders, function (key, exceptionData) {
                        var exceptionId = exceptionData[0];
                        var orderId = exceptionData[1];
                        var email = exceptionData[2];
                        var expiration = exceptionData[3];
                        var paidStatus = exceptionData[4];
                        var hasAmount = exceptionData[5];
                        var explorerUrl = exceptionData[6];
                        var unknownTxExplorerUrl = exceptionData[7];

                        if(orderId === "null" && explorerUrl !== "null") {
                            amountId += `<p><a class="exception-order-info__explorer-url" href="${unknownTxExplorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>`
                            unknownTxExceptionId =  exceptionId;
                        } else {
                            if(paidStatus === '0') {
                                var paymentStatus = 'Have not paid';
                            } else if(paidStatus === '1') {
                                var paymentStatus = 'Paid on time';
                            } else {
                                var paymentStatus = 'Paid on expiration';
                            }
                            orderItem += `<div id="exception-${exceptionId}" class="order-${orderId} exception-order-box ${key%2 ? 'background-grey' : ''}">
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
                            orderItem += paidStatus != 1 ? `<button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-order-exception" data-exception-id="${exceptionId}">${language.delete}</button>
                                                            <button class="btn btn-primary btn-confirm-paid" data-toggle="modal" data-target="#confirm-paid-order-exception" data-exception-id="${exceptionId}" data-order-id="${orderId}">${language.confirmPaid}</button>` : ''
                            orderItem +=`
                                    </div>
                                </div>`;
                        }
                    });
                    orderItem += `<div class="exception-order-box">
                                        <div class="exception-order-info">
                                             <select class="form-control all_order_pending" style="width: 300px" data-list_coin_url="${urlGetAllOrderPending}" id="exception-select-order-${tmp}" data-tmp="${tmp}"></select>
                                        </div>
                                        <div class="exception-order-button-box">
                                            <button class="btn btn-info btn-assign-order" id="btn-assign-order-${tmp}" data-toggle="modal" data-target="#confirm-paid-order-exception" data-exception-id="${unknownTxExceptionId}" data-order-id="" style="opacity: 0">Assign</button>
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
        });
    };

    oc_ezdefi_exception.prototype.addConfirmPaidListener = function (data) {
        $(".btn-confirm-paid").click(function () {
            let exceptionId = $(this).data('exception-id');
            let orderId = $(this).data('order-id');
            $("#exception-id--confirm").val(exceptionId);
            $("#exception-order-id--confirm").val(orderId);
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
            let exceptionId = $(this).data('exception-id');
            $("#exception-order-id--confirm").val(orderId);
            $("#exception-id--confirm").val(exceptionId);
            $(".exception-loading-icon__confirm-paid").css('display', 'none');
        })
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

    oc_ezdefi_exception.prototype.deleteException = function (e, exceptionId = null) {
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
                $("#exception-"+exceptionId).hide(500, function () {
                    let contSiblings = $("#exception-"+exceptionId).siblings().length;
                    if(contSiblings <= 1) {
                        $("#exception-"+exceptionId).parent().parent().parent().remove();
                    } else {
                        $("#exception-"+exceptionId).remove();
                    }
                });
                if ($('#delete-order-exception').hasClass('in')) {
                    $('#delete-order-exception').modal('toggle');
                }
                $("#btn-delete-exception").prop('disabled', false);
            },
            error: function () {
                $("#btn-delete-exception").prop('disabled', false);
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
                // that.deleteExceptionByOrderId(orderId);
                if (exceptionId) that.deleteException(null, exceptionId);
                $("#confirm-paid-order-exception").modal('toggle');
                $("#btn-confirm-paid-exception").prop('disabled', false);
            },
            error: function () {
                // that.deleteExceptionByOrderId(orderId);
                if (exceptionId) that.deleteException(null, exceptionId);
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
                that.deleteException(null, exceptionId);
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

    oc_ezdefi_exception.prototype.searchException = function () {
        var keyword = $("#exception-search-input").val();
        this.loadException(keyword);
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
            minimumInputLength: 1,
            templateResult: that.formatRepo,
            templateSelection: that.formatRepoSelection,
            placeholder: "Enter order"
        });
        $("select.all_order_pending").on('select2:select', this.selectOrderPendingListener);
    };

    oc_ezdefi_exception.prototype.formatRepoSelection = function (repo) {
        return repo.total ? 'Order: ' + repo.id : 'Choose order to assign';
    }

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