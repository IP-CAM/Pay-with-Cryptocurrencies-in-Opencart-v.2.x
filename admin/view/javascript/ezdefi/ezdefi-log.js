$(function () {
    var ezdefi_log = function () {
        this.searchLog();
        $("#btn-delete-exception").click(this.deleteException.bind(this));
        $("#btn-confirm-paid-exception").click(this.confirmPaidException.bind(this));
        $("#exception-search-by-amount").change(this.searchLog.bind(this));
        $("#exception-search-by-order").change(this.searchLog.bind(this));
        $("#exception-search-by-email").change(this.searchLog.bind(this));
        $("#btn-search-log").click(this.searchLog.bind(this));
        $("input[name='filter-by-currency']").change(this.searchLog.bind(this));
    };

    ezdefi_log.prototype.searchLog = function (page = 1, totalNumber = null) {
        var that = this;
        var container = $("#log-content-box");
        var keywordAmount = $("#log-search-by-amount").val();
        var keywordOrder = $("#log-search-by-order").val();
        var keywordEmail = $("#log-search-by-email").val();
        var urlGetException = $("#url-search-exceptions").val();
        var currency = $("input[name='filter-by-currency']:checked").val() ? $("input[name='filter-by-currency']:checked").val() : '';

        var paginationObject = {
            dataSource: urlGetException + '&amount=' +keywordAmount + '&order_id='+ keywordOrder + '&email=' + keywordEmail + '&currency=' + currency + '&section=3',
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
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>`;
                let tmp = (pagination.pageNumber - 1) * pagination.pageSize + 1;
                $.each(response, function (exceptionKey, exceptionRecord) {
                    let currency = exceptionRecord.currency;
                    let amountId = parseFloat(exceptionRecord.amount_id);
                    var exceptionId = exceptionRecord.id;
                    var orderId = exceptionRecord.order_id;
                    var email = exceptionRecord.email;
                    var expiration = exceptionRecord.expiration;
                    var paidStatus = exceptionRecord.paid;
                    var hasAmount = exceptionRecord.has_amount;
                    var explorerUrl = exceptionRecord.explorer_url;
                    let orderItem = "<div>";

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
                            <p class="${!explorerUrl ? 'hidden':''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>
                        </div>
                    </div>`;

                    let action = '';
                    if(paidStatus == 1 ) {
                        action = `<div class="exception-order-button-box">
                            <button class="btn btn-primary btn-revert-order" data-toggle="modal" data-target="#modal-revert-order-exception" data-exception-id="${exceptionId}" data-order-id="${orderId}">${language.revert}</button>
                            <button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-order-exception" data-exception-id="${exceptionId}">${language.delete}</button>
                        </div>`;
                    } else if(paidStatus != 1 ) {
                        action = `<div class="exception-order-button-box">
                            <button class="btn btn-primary btn-confirm-paid" data-toggle="modal" data-target="#confirm-paid-order-exception" data-exception-id="${exceptionId}" data-order-id="${orderId}">${language.confirmPaid}</button>
                            <button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-order-exception" data-exception-id="${exceptionId}">${language.delete}</button>
                        </div>`;
                    }

                    dataHtml += `<tr>
                                <td>${tmp}</td>
                                <td class="text-uppercase">${currency}</td>
                                <td>${amountId} </td>
                                <td>${orderItem}</td>
                                <td>${action}</td>
                            </tr>`;
                    tmp++;
                });
                dataHtml += `</tbody>
                    </table>`;
                container.prev().html(dataHtml);
                that.addButtonListener();
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

    ezdefi_log.prototype.addButtonListener = function (data) {
        $(".btn-confirm-paid").click(function () {
            let exceptionId = $(this).data('exception-id');
            let orderId = $(this).data('order-id');
            $("#exception-id--confirm").val(exceptionId);
            $("#exception-order-id--confirm").val(orderId);
            $("#exception-old-order-id--confirm").val();
            $("#confirm-dialog-assign").prop('checked', false);
            $(".exception-loading-icon__confirm-paid").css('display', 'none');
        });

        $(".btn-delete-exception").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-id--delete").val(exceptionId);
            $(".exception-loading-icon__delete").css('display', 'none');
        });

        $(".btn-revert-order").click(function () {
            let exceptionId = $(this).data('exception-id');
            let orderId = $(this).data('order-id');
            $("#exception-id--revert").val(exceptionId);
            $("#exception-order-id--revert").val(orderId);
            $(".exception-loading-icon__revert").css('display', 'none');
        });

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

    ezdefi_log.prototype.deleteException = function (e, exceptionId = null) {
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
                that.reloadExceptionTable();
            },
            error: function () {
                $("#btn-delete-exception").prop('disabled', false);
            }
        });
    };

    ezdefi_log.prototype.confirmPaidException = function () {
        $("#btn-confirm-paid-exception").prop('disabled', true);
        let orderId = $("#exception-order-id--confirm").val();
        let exceptionId = $('#exception-id--confirm').val();
        let that = this;

        let isAssign = $("#confirm-dialog-assign").prop('checked');
        if(isAssign) {
            var url = $("#url-assign-order").val();
        } else {
            var url = $("#url-confirm-order").val();
        }

        $.ajax({
            url: url,
            method: "POST",
            data: {
                exception_id: exceptionId,
                order_id: orderId
            },
            beforeSend:function() {
                $(".exception-loading-icon__confirm-paid").css('display', 'inline-block');
            },
            success: function (response) {
                $("#confirm-paid-order-exception").modal('toggle');
                $("#btn-confirm-paid-exception").prop('disabled', false);
                that.reloadExceptionTable();
            },
            error: function () {
                alert('Something error');
            }
        });
    };

    ezdefi_log.prototype.reloadExceptionTable = function(exceptionId) {
        let page = $("#current-page-exception").val();
        let totalNumber = $("#total-number-exception").val();
        this.searchLog(page, totalNumber);
    };

    new ezdefi_log();
});