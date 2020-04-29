$(function () {
    var global = {};

    var oc_ezdefi_exception = function () {
        var that = this;
        this.searchException();
        $("#btn-delete-exception").click(this.deleteException.bind(this));
        $("#btn-assign-exception").click(this.assignException.bind(this))
        $("#btn-confirm-exception").click(this.confirmException.bind(this));
        $("#new-exception-search-by-amount").change(this.searchException.bind(this));
        $("#new-exception-search-by-order").change(this.searchException.bind(this));
        $("#new-exception-search-by-email").change(this.searchException.bind(this));

        $("#new-exception-search-by-amount").keyup(function(event) {if (event.keyCode === 13) this.searchException()}.bind(this));
        $("#new-exception-search-by-order").keyup(function(event) {if (event.keyCode === 13) this.searchException()}.bind(this));
        $("#new-exception-search-by-email").keyup(function(event) {if (event.keyCode === 13) this.searchException()}.bind(this));

        $("#btn-search-new-exception").click(this.searchException.bind(this));
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
        var container = $("#new-exception-content-box");
        var keywordAmount = $("#new-exception-search-by-amount").val();
        var keywordOrder = $("#new-exception-search-by-order").val();
        var keywordEmail = $("#new-exception-search-by-email").val();
        var urlGetException = $("#url-search-exceptions").val();
        var urlGetAllOrderPending = $("#url-get-order-pending").val();
        var currency = $("input[name='filter-by-currency']:checked").val() ? $("input[name='filter-by-currency']:checked").val() : '';

        var paginationObject = {
            dataSource: urlGetException + '&amount=' +keywordAmount + '&order_id='+ keywordOrder + '&email=' + keywordEmail + '&currency=' + currency + '&section=1',
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
                            <th>${language.payment_info}</th>
                            <th width="110">${language.action}</th>
                        </tr>
                        </thead>
                        <tbody>`;
                let tmp = (pagination.pageNumber - 1) * pagination.pageSize + 1;
                $.each(response, function (exceptionKey, exceptionRecord) {
                    let orderInfo, paymentInfo, paymentStatus

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

                    if(paidStatus === '0') {
                        paymentStatus = 'Have not paid';
                    } else if(paidStatus === '1') {
                        paymentStatus = 'Paid on time';
                    } else {
                        paymentStatus = 'Paid on expiration';
                    }

                    if(orderId) {
                        paymentInfo= `<div>
                            <div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                                <div class="exception-order-info">
                                    <p><span class="exception-order-label-1">${language.expiration}:</span> <span class="exception-order-info__data"> ${expiration} </span></p>
                                    <p><span class="exception-order-label-1">${language.paid}:</span> <span class="exception-order-info__data">${paymentStatus} </span></p>
                                    <p><span class="exception-order-label-1">${language.payByEzdefi}:</span> ${hasAmount === '1' ? 'no' : 'yes'} </p>
                                    <p class="${explorerUrl == '' ? 'hidden':''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>
                                </div>
                            </div>
                        </div>`;

                        orderInfo = `
                        <div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                            <div class="exception-order-info">
                                <p><span class="exception-order-label-1">${language.orderId}:</span> <span class="exception-order-info__data"> ${orderId} </span></p>
                                <p><span class="exception-order-label-1">${language.email}:</span> <span class="exception-order-info__data">${email} </span></p>
                                   <p><span class="exception-order-label-1">Customer:</span> <span class="exception-order-info__data"> ${customer} </span></p>
                                <p><span class="exception-order-label-1">Price:</span> <span class="exception-order-info__data"> ${total} </span></p>
                                <p><span class="exception-order-label-1">Create at:</span> <span class="exception-order-info__data"> ${date} </span></p>
                                <div class="exception-order-box">
                                    <div class="exception-order-info">
                                         <select class="form-control all_order_pending" style="width: 300px" data-list_coin_url="${urlGetAllOrderPending}" id="exception-select-order-${tmp}" data-tmp="${tmp}"></select>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    } else {
                        orderInfo = `<div class="exception-order-box">
                            <div class="exception-order-info">
                                 <select class="form-control all_order_pending" style="width: 300px" data-list_coin_url="${urlGetAllOrderPending}" id="exception-select-order-${tmp}" data-tmp="${tmp}"></select>
                            </div>
                        </div>`
                        paymentInfo= `<div>
                            <div id="exception-${exceptionId}" class="order-${orderId} exception-order-box">
                                <div class="exception-order-info">
                                    <p class="${explorerUrl == '' ? 'hidden':''}"><span class="exception-order-label-1">Explorer url:</span><a class="exception-order-info__explorer-url" href="${explorerUrl}" target="_blank">${language.viewTransactionDetail}</a></p>
                                </div>
                            </div>
                        </div>`;
                    }

                    let action = `<div class="exception-order-button-box">`
                    if(orderId) {
                        action += `<button class="btn btn-primary btn-show-confirm-exception-modal" data-toggle="modal" data-target="#confirm-exception-modal" data-exception-id="${exceptionId}">${language.confirmPaid}</button>`
                    }
                    action += `<button class="btn btn-danger btn-delete-exception" data-toggle="modal" data-target="#delete-exception-modal" data-exception-id="${exceptionId}">${language.delete}</button>
                                <button class="btn btn-info btn-show-assign-exception-modal" id="btn-assign-order-${tmp}" data-toggle="modal" data-target="#assign-exception-modal" data-exception-id="${exceptionId}" data-old-order-id="${orderId}" data-order-id="" style="opacity: 0">Assign</button>
                            </div>`

                    dataHtml += `<tr>
                                <td>${tmp}</td>
                                <td class="text-uppercase">${currency}</td>
                                <td>${amountId} </td>
                                <td>${orderInfo}</td>
                                <td>${paymentInfo}</td>
                                <td>${action}</td>
                            </tr>`;
                    tmp++;
                });
                dataHtml += `</tbody>
                    </table>`;
                container.prev().html(dataHtml);
                that.addButtonListener();
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

    oc_ezdefi_exception.prototype.addButtonListener = function (data) {
        $(".btn-delete-exception").click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-id--delete").val(exceptionId);
            $(".exception-loading-icon").css('display', 'none');
        });

        $(".btn-show-assign-exception-modal").click(function () {
            let orderId = $(this).data('order-id');
            let exceptionId = $(this).data('exception-id');

            $("#exception-order-id--assign").val(orderId);
            $("#exception-id--assign").val(exceptionId);
            $(".exception-loading-icon").css('display', 'none');
        })

        $('.btn-show-confirm-exception-modal').click(function () {
            let exceptionId = $(this).data('exception-id');
            $("#exception-id--confirm-exception").val(exceptionId);
            $(".exception-loading-icon").css('display', 'none');
        })
    };

    oc_ezdefi_exception.prototype.deleteException = function (e) {
        var that = this;
        let exceptionId = $("#exception-id--delete").val();
        $("#btn-delete-exception").prop('disabled', true);
        let url = $("#url-delete-exception").val();
        $.ajax({
            url: url,
            method: "POST",
            data: { exception_id: exceptionId },
            beforeSend: function() {
                $(".exception-loading-icon").css('display', 'inline-block');
            },
            success: function (response) {
                if ($('#delete-exception-modal').hasClass('in')) {
                    $('#delete-exception-modal').modal('toggle');
                }
                $("#btn-delete-exception").prop('disabled', false);
                that.reloadExceptionTable();
            },
            error: function () {
                $("#btn-delete-exception").prop('disabled', false);
            }
        });
    };

    oc_ezdefi_exception.prototype.confirmException = function () {
        $("#btn-confirm-exception").prop('disabled', true);
        let exceptionId = $('#exception-id--confirm-exception').val();
        let that = this;
        var url = $("#url-confirm-order").val();

        console.log(exceptionId, url);


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
                if($("#confirm-exception-modal").has('in')){
                    $("#confirm-exception-modal").modal('toggle');
                }
                $("#exception").prop('disabled', false);
                that.reloadExceptionTable();
            },
            error: function () {
                $("#exception").prop('disabled', false);
                alert('Something error');
            }
        });
    }

    oc_ezdefi_exception.prototype.assignException = function () {
        $("#btn-assign-exception").prop('disabled', true);
        let orderId = $("#exception-order-id--assign").val();
        let exceptionId = $('#exception-id--assign').val();

        let that = this;
        let url = $("#url-assign-order").val();

        $.ajax({
            url: url,
            method: "POST",
            data: {
                exception_id: exceptionId,
                order_id: orderId
            },
            beforeSend:function() {
                $(".exception-loading-icon").css('display', 'inline-block');
            },
            success: function (response) {
                $("#assign-exception-modal").modal('toggle');
                $("#btn-assign-exception").prop('disabled', false);
                that.reloadExceptionTable();
            },
            error: function () {
                alert('Something error');
            }
        });
    };

    oc_ezdefi_exception.prototype.reloadExceptionTable = function() {
        let page = $("#current-page-exception").val();
        let totalNumber = $("#total-number-exception").val();
        this.searchException(page, totalNumber);
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
                    return {
                        results: data.data
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
        var tmp = $(this).data('tmp');
        var buttonAssign = $("#btn-assign-order-"+tmp);
        buttonAssign.css('opacity', 100);
        buttonAssign.data('order-id', data.id);
    };

    new oc_ezdefi_exception();
});