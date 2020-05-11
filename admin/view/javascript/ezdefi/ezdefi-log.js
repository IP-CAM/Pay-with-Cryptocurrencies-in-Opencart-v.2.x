var ezdefi_log = function () {
    this.searchLog();
    $("#btn-delete-log").click(this.deleteLog.bind(this));
    $("#btn-confirm-log").click(this.confirmLog.bind(this));
    $("#log-search-by-amount").change(this.searchLog.bind(this));
    $("#log-search-by-order").change(this.searchLog.bind(this));
    $("#log-search-by-email").change(this.searchLog.bind(this));

    $("#log-search-by-amount").keyup(function(event) {if (event.keyCode === 13) this.searchLog()}.bind(this));
    $("#log-search-by-order").keyup(function(event) {if (event.keyCode === 13) this.searchLog()}.bind(this));
    $("#log-search-by-email").keyup(function(event) {if (event.keyCode === 13) this.searchLog()}.bind(this));

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
            if(response.length === 0) {
                container.prev().html("<div class='text-center padding-lg'><h2>Not results</h2></div>");
                return;
            }
            var dataHtml = `<table class="table">
                        <thead>
                        <tr>
                            <th>${language.ordinal}</th>
                            <th>${language.currency}</th>
                            <th>${language.amount}</th>
                            <th>${language.order}</th>
                            <th width="110">${language.action}</th>
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

                let orderItem = '';

                if(orderId) {
                    if (paidStatus === '0') {
                        var paymentStatus = 'Have not paid';
                    } else if (paidStatus === '1') {
                        var paymentStatus = 'Paid on time';
                    } else {
                        var paymentStatus = 'Paid on expiration';
                    }
                    orderItem = `<div>
                        <div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                            <div class="exception-order-info">
                                <p><span class="exception-order-label-1">${language.orderId}:</span> <span class="exception-order-info__data"> ${orderId} </span></p>
                                <p><span class="exception-order-label-1">${language.email}:</span> <span class="exception-order-info__data">${email} </span></p>
                                <p><span class="exception-order-label-1">${language.expiration}:</span> <span class="exception-order-info__data"> ${expiration} </span></p>
                                <p><span class="exception-order-label-1">${language.paid}:</span> <span class="exception-order-info__data">${paymentStatus} </span></p>
                                <p><span class="exception-order-label-1">${language.payByEzdefi}:</span> ${hasAmount === '1' ? 'no' : 'yes'} </p>
                                <p class="${!explorerUrl ? 'hidden' : ''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>
                            </div>
                        </div>`;
                } else {
                    orderItem = `<div>
                        <div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                            <div class="exception-order-info">
                                <p class="${!explorerUrl ? 'hidden' : ''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>
                            </div>
                        </div>`
                }

                let action = `<div class="exception-order-button-box">
                            <button class="btn btn-primary btn-show-confirm-log-modal" data-toggle="modal" data-target="#confirm-log-modal" data-exception-id="${exceptionId}" data-order-id="${orderId}">${language.confirmPaid}</button>
                            <button class="btn btn-danger btn-show-delete-log-modal" data-toggle="modal" data-target="#delete-log-modal" data-exception-id="${exceptionId}">${language.delete}</button>
                        </div>`;

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
    $(".btn-show-confirm-log-modal").click(function () {
        let exceptionId = $(this).data('exception-id');
        $("#exception-id--confirm-log").val(exceptionId);
        $(".exception-loading-icon").css('display', 'none');
    });

    $(".btn-show-delete-log-modal").click(function () {
        let exceptionId = $(this).data('exception-id');
        $("#exception-id--log-delete").val(exceptionId);
        $(".exception-loading-icon").css('display', 'none');
    });
};

ezdefi_log.prototype.deleteLog = function () {
    var that = this;
    var exceptionId = $("#exception-id--log-delete").val();
    $("#btn-delete-log").prop('disabled', true);
    let url = $("#url-delete-exception").val();
    $.ajax({
        url: url,
        method: "POST",
        data: { exception_id: exceptionId },
        beforeSend: function() {
            $(".exception-loading-icon").css('display', 'inline-block');
        },
        success: function (response) {
            if ($('#delete-log-modal').hasClass('in')) {
                $('#delete-log-modal').modal('toggle');
            }
            $("#btn-delete-log").prop('disabled', false);
            that.reloadExceptionTable();
        },
        error: function () {
            $("#btn-delete-log").prop('disabled', false);
        }
    });
};

ezdefi_log.prototype.confirmLog = function () {
    $("#btn-confirm-log").prop('disabled', true);
    let exceptionId = $('#exception-id--confirm-log').val();
    let that = this;
    var url = $("#url-confirm-order").val();

    $.ajax({
        url: url,
        method: "POST",
        data: {
            exception_id: exceptionId,
        },
        beforeSend:function() {
            $(".exception-loading-icon").css('display', 'inline-block');
        },
        success: function (response) {
            if($("#confirm-log-modal").has('in')){
                $("#confirm-log-modal").modal('toggle');
            }
            $("#btn-confirm-log").prop('disabled', false);
            that.reloadExceptionTable();
        },
        error: function () {
            $("#btn-confirm-log").prop('disabled', false);
            alert('Something error');
        }
    });
};

ezdefi_log.prototype.reloadExceptionTable = function(exceptionId) {
    let page = $("#current-page-exception").val();
    let totalNumber = $("#total-number-exception").val();
    this.searchLog(page, totalNumber);
};