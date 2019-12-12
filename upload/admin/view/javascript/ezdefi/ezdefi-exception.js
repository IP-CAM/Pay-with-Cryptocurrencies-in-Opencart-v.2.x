$(function () {
    const text = {
        orderHistoryComment: "confirm from exception",
        assignOrderComment: "Assign order from exception"
    };
    var global = {};

    var oc_ezdefi_exception = function () {
        this.numberOfOrderInPage = 10;

        this.loadException();
        $("#btn-delete-exception").click(this.deleteException.bind(this));
        $("#btn-confirm-paid-exception").click(this.confirmPaidException.bind(this));
        $("#exception-search-input").keyup(this.searchException.bind(this));
        $("input[name='filter-by-currency']").change(this.searchException.bind(this));
    };

    oc_ezdefi_exception.prototype.loadException = function (keyword = '') {
        var that = this;
        var container = $("#exception-content-box");
        var urlGetException = $("#url-search-exceptions").val();
        var urlGetAllOrderPending = $("#url-get-order-pending").val();
        var totalNumber = $("#total-exception").val();
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
                let tmp = 1;
                $.each(exceptionRecords, function (exceptionKey, groupException) {
                    let currency = groupException.currency;
                    let amountId = groupException.amount_id;
                    let orderItem = "<div>";
                    $.each(groupException.orders, function (key, exceptionData) {
                        if(exceptionData[4] === '0') {
                            var paymentStatus = 'Have not paid';
                        } else if(exceptionData[4] === '1') {
                            var paymentStatus = 'Paid on time';
                        } else {
                            var paymentStatus = 'Paid on expiration';
                        }
                        orderItem += `<div id="exception-${exceptionData[0]}" class="order-${exceptionData[1]} exception-order-box ${key%2 ? 'background-grey' : ''}">
                            <div class="exception-order-info">
                                <p><span class="exception-order-label-1">order_id:</span> ${exceptionData[1]} </p>
                                <p><span class="exception-order-label-1">email:</span> ${exceptionData[2]} </p>
                                <p><span class="exception-order-label-1">expiration:</span> ${exceptionData[3]} </p>
                                <p><span class="exception-order-label-1">paid:</span> ${paymentStatus} </p>
                                <p><span class="exception-order-label-1">Pay by ezdefi wallet:</span> ${exceptionData[5] === '1' ? 'yes' : 'no'} </p>
                            </div>
                            <div class="exception-order-button-box">
                                <button class="btn btn-primary ${exceptionData[4] !== '1' ? 'btn-confirm-paid': 'hidden'}" data-toggle="modal" data-target="#confirm-paid-order-exception" data-exception-id="${exceptionData[0]}" data-order-id="${exceptionData[1]}">confirm paid</button>
                                <button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-order-exception" data-exception-id="${exceptionData[0]}">delete</button>
                            </div>
                        </div>`;
                    });
                    orderItem += `<div class="exception-order-box">
                                    <div class="exception-order-info">
                                         <select class="form-control all_order_pending" style="width: 300px" data-list_coin_url="${urlGetAllOrderPending}" id="exception-select-order-${tmp}" data-tmp="${tmp}"></select>
                                     </div>
                                    <div class="exception-order-button-box"> 
                                        <button class="btn btn-info btn-assign-order" id="btn-assign-order-${tmp}" data-toggle="modal" data-target="#confirm-paid-order-exception" data-order-id="" style="opacity: 0">Assign</button>
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
                that.initSelectCoinConfig();
            }
        });
    };

    oc_ezdefi_exception.prototype.addConfirmPaidListener = function (data) {
        $(".btn-confirm-paid").click(function () {
            let exceptionId = $(this).data('exception-id');
            let orderId = $(this).data('order-id');
            $("#exception-id--confirm").val(exceptionId);
            $("#exception-order-id").val(orderId);
        });
    };

    oc_ezdefi_exception.prototype.addDeleteExceptionListener = function (data) {
        $(".btn-delete-exception").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-id--delete").val(exceptionId);
        });
    };

    oc_ezdefi_exception.prototype.addAssignOrderListener = function() {
        $(".btn-assign-order").click(function () {
            let orderId = $(this).data('order-id');
            $("#exception-order-id").val(orderId);
            $("#exception-id--confirm").val('');
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

    oc_ezdefi_exception.prototype.deleteException = function () {
        let url = $("#url-delete-exception").val();
        let exceptionId = $("#exception-id--delete").val();
        $.ajax({
            url: url,
            method: "POST",
            data: { exception_id: exceptionId },
            success: function (response) {
                $("#exception-"+exceptionId).remove();
                $('#delete-order-exception').modal('toggle');

            }
        });
    };

    oc_ezdefi_exception.prototype.confirmPaidException = function () {
        let urlAddOrderHistory = $("#url-add-order-history").val();
        let orderId = $("#exception-order-id").val();
        let exceptionId = $('#exception-id--confirm').val();
        var that = this;
        $.ajax({
            url: urlAddOrderHistory + '&store_id=0&order_id='+orderId,
            method: "POST",
            data: {
                order_status_id: 2,
                comment: exceptionId ? text.orderHistoryComment : text.assignOrderComment
            },
            success: function (response) {
                that.deleteExceptionByOrderId(orderId);
            },
            error: function () {
                that.deleteExceptionByOrderId(orderId);
            }
        });
    };

    oc_ezdefi_exception.prototype.deleteExceptionByOrderId = function (orderId) {
        let urlDeleteExceptionByOrderId = $("#url-delete-exception-by-order-id").val();
        $.ajax({
            url: urlDeleteExceptionByOrderId + '&order_id=451&store_id=0',
            method: "POST",
            data: { order_id: orderId},
            success: function (response) {
                $(".order-"+orderId).remove();
                $('#confirm-paid-order-exception').modal('toggle');
            }
        });
    };

    oc_ezdefi_exception.prototype.searchException = function () {
        var keyword = $("#exception-search-input").val();
        this.loadException(keyword);
    };

    oc_ezdefi_exception.prototype.initSelectCoinConfig = function() {
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
            placeholder: "Enter name"
        });
        $("select.all_order_pending").on('select2:select', this.selectOrderPendingListener);
    };

    oc_ezdefi_exception.prototype.formatRepoSelection = function (repo) {
        console.log(repo)
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
                            <p><span class="exception-order-label-2">Order id:</span>${repo.id}</p>
                            <p><span class="exception-order-label-2">Email:</span>${repo.email}</p>
                            <p><span class="exception-order-label-2">Customer:</span>${repo.firstname + ' ' + repo.lastname}</p>
                            <p><span class="exception-order-label-2">Price:</span>${repo.total +' ' + repo.currency_code}</p>
                            <p><span class="exception-order-label-2">Created at:</span>${repo.date_added}</p>
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