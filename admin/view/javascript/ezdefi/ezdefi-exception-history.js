$(function () {
    var ezdefi_exception_history = function () {
        this.searchExceptionHistory();
        $("#btn-delete-exception-history").click(this.deleteException.bind(this));
        $("#btn-revert-exception-history").click(this.revertException.bind(this));
        $("#exception-history-search-by-amount").change(this.searchExceptionHistory.bind(this));
        $("#exception-history-search-by-order").change(this.searchExceptionHistory.bind(this));
        $("#exception-history-search-by-email").change(this.searchExceptionHistory.bind(this));
        $("#btn-search-exception-history").click(this.searchExceptionHistory.bind(this));
        $("input[name='exception-history-search-by-currency']").change(this.searchExceptionHistory.bind(this));
    };

    ezdefi_exception_history.prototype.searchExceptionHistory = function (page = 1, totalNumber = null) {
        var that = this;
        var container = $("#exception-history-content-box");
        var keywordAmount = $("#exception-history-search-by-amount").val();
        var keywordOrder = $("#exception-history-search-by-order").val();
        var keywordEmail = $("#exception-history-search-by-email").val();
        var urlGetException = $("#url-search-exceptions").val();
        var currency = $("input[name='exception-history-search-by-currency']:checked").val() ? $("input[name='exception-history-search-by-currency']:checked").val() : '';

        var paginationObject = {
            dataSource: urlGetException + '&amount=' +keywordAmount + '&order_id='+ keywordOrder + '&email=' + keywordEmail + '&currency=' + currency + '&section=2',
            locator: 'items.exceptions',
            pageNumber: page,
            pageSize: 10,
            ajax: {
                beforeSend: function() {
                    container.prev().html('Loading data from server ...');
                }
            },
            callback: function(response, pagination) {
                $("#current-page-exception-history").val(pagination.pageNumber);
                var dataHtml = `<table class="table">
                        <thead>
                        <tr>
                            <th>${language.ordinal}</th>
                            <th>${language.currency}</th>
                            <th>${language.amount}</th>
                            <th>${language.order}</th>
                            <th>${language.old_order}</th>
                            <th>${language.payment_info}</th>
                            <th width="110">${language.action}</th>
                            
                        </tr>
                        </thead>
                        <tbody>`;
                let tmp = (pagination.pageNumber - 1) * pagination.pageSize + 1;
                $.each(response, function (exceptionKey, exceptionRecord) {
                    let oldOrderItem, paymentInfo;
                    let currency = exceptionRecord.currency;
                    let amountId = parseFloat(exceptionRecord.amount_id);
                    let exceptionId = exceptionRecord.id;
                    let expiration = exceptionRecord.expiration;
                    let paidStatus = exceptionRecord.paid;
                    let hasAmount = exceptionRecord.has_amount;
                    let explorerUrl = exceptionRecord.explorer_url;

                    let orderId = exceptionRecord.order_id;
                    let email = exceptionRecord.email;
                    let customer = exceptionRecord.customer;
                    let total= exceptionRecord.total;
                    let date= exceptionRecord.date;

                    let newOrderId = exceptionRecord.order_assigned;
                    let newEmail = exceptionRecord.new_email;
                    let newCustomer = exceptionRecord.new_customer;
                    let newTotal= exceptionRecord.new_total;
                    let newDate= exceptionRecord.new_date;

                    let paymentStatus = '';
                    if(paidStatus === '0') {
                        paymentStatus = 'Have not paid';
                    } else if(paidStatus === '1') {
                        paymentStatus = 'Paid on time';
                    } else {
                        paymentStatus = 'Paid on expiration';
                    }

                    if(orderId) {
                        paymentInfo = `<div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                            <div class="exception-order-info">
                                <p><span class="exception-order-label-1">${language.expiration}:</span> <span class="exception-order-info__data"> ${expiration} </span></p>
                                <p><span class="exception-order-label-1">${language.paid}:</span> <span class="exception-order-info__data">${paymentStatus} </span></p>
                                <p><span class="exception-order-label-1">${language.payByEzdefi}:</span> ${hasAmount === '1' ? 'no' : 'yes'} </p>
                                <p class="${!explorerUrl ? 'hidden':''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>
                            </div>
                        </div>`;

                        oldOrderItem = `
                        <div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                            <div class="exception-order-info">
                                <p><span class="exception-order-label-1">${language.orderId}:</span> <span class="exception-order-info__data"> ${orderId} </span></p>
                                <p><span class="exception-order-label-1">${language.email}:</span> <span class="exception-order-info__data">${email} </span></p>
                                   <p><span class="exception-order-label-1">Customer:</span> <span class="exception-order-info__data"> ${customer} </span></p>
                            <p><span class="exception-order-label-1">Price:</span> <span class="exception-order-info__data"> ${total} </span></p>
                            <p><span class="exception-order-label-1">Create at:</span> <span class="exception-order-info__data"> ${date} </span></p>
                            </div>
                        </div>`;
                    } else {
                        paymentInfo = `<p class="${!explorerUrl ? 'hidden':''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>`
                        oldOrderItem = ''
                    }

                    let orderItem= `<div>
                            <p><span class="exception-order-label-1">order id:</span> <span class="exception-order-info__data"> ${newOrderId} </span></p>
                            <p><span class="exception-order-label-1">${language.email}:</span> <span class="exception-order-info__data"> ${newEmail} </span></p>
                            <p><span class="exception-order-label-1">Customer:</span> <span class="exception-order-info__data"> ${newCustomer} </span></p>
                            <p><span class="exception-order-label-1">Price:</span> <span class="exception-order-info__data"> ${newTotal} </span></p>
                            <p><span class="exception-order-label-1">Create at:</span> <span class="exception-order-info__data"> ${newDate} </span></p>
                        </div>`

                    let action = `<div class="exception-order-button-box">
                                <button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-exception-modal--history" data-exception-id="${exceptionId}">${language.delete}</button>
                                <button class="btn btn-primary btn-revert-order" data-toggle="modal" data-target="#revert-exception-modal" data-exception-id="${exceptionId}" data-order-id="${orderId}">${language.revert}</button>
                            </div>`

                    dataHtml += `<tr>
                                <td>${tmp}</td>
                                <td class="text-uppercase">${currency}</td>
                                <td>${amountId} </td>
                                <td>${orderItem}</td>
                                <td>${oldOrderItem}</td>
                                <td>${paymentInfo}</td>
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
                $("#total-number-exception-history").val(response.total_exceptions);
                return response.total_exceptions;
            }
        }

        container.pagination(paginationObject);
    };

    ezdefi_exception_history.prototype.addButtonListener = function (data) {
        $(".btn-delete-exception").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-id--history-delete").val(exceptionId);
            $(".exception-loading-icon__delete").css('display', 'none');
        });

        $(".btn-revert-order").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-id--revert").val(exceptionId);
            $(".exception-loading-icon__revert").css('display', 'none');
        });


    };

    ezdefi_exception_history.prototype.deleteException = function () {
        var that = this;
        var exceptionId = $("#exception-id--history-delete").val();
        $("#btn-delete-exception-history").prop('disabled', true);
        let url = $("#url-delete-exception").val();
        $.ajax({
            url: url,
            method: "POST",
            data: { exception_id: exceptionId },
            beforeSend: function() {
                $(".exception-loading-icon__delete").css('display', 'inline-block');
            },
            success: function (response) {
                if ($('#delete-exception-modal--history').hasClass('in')) {
                    $('#delete-exception-modal--history').modal('toggle');
                }
                $("#btn-delete-exception-history").prop('disabled', false);
                that.reloadExceptionTable();
            },
            error: function () {
                $("#btn-delete-exception").prop('disabled', false);
            }
        });
    };

    ezdefi_exception_history.prototype.revertException = function() {
        $("#btn-revert-exception-history").prop('disabled', true);
        let exceptionId = $('#exception-id--revert').val();
        let urlRevert = $("#url-revert-exception").val();
        let that = this;

        $.ajax({
            url: urlRevert,
            method: "POST",
            data: {
                exception_id: exceptionId,
            },
            beforeSend: function() {
                $(".exception-loading-icon__revert").css('display', 'inline-block');
            },
            success: function (response) {
                $("#revert-exception-modal").modal('toggle');
                $("#btn-revert-exception-history").prop('disabled', false);
                that.reloadExceptionTable();
            },
            error: function () {
                alert('Something error');
            }
        });
    };

    ezdefi_exception_history.prototype.reloadExceptionTable = function() {
        let page = $("#current-page-exception-history").val();
        let totalNumber = $("#total-number-exception-history").val();
        this.searchExceptionHistory(page, totalNumber);
    };

    new ezdefi_exception_history();
});