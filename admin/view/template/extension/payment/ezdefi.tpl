<?php echo $header; ?>  <?php echo $column_left ?>
<div id="content">
    <input type="hidden" id="url-add-order-history" value="<?php echo $url_add_order_history ?>">
    <input type="hidden" id="url-delete-exception-by-order-id" value="<?php echo $url_delete_exception_by_order_id ?>">
    <input type="hidden" id="url-get-order-pending" value="<?php echo $url_get_order_pending ?>">
    <input type="hidden" id="url-delete-exception" value="<?php echo $url_delete_exception ?>">
    <input type="hidden" id="url-confirm-order" value="<?php echo $url_confirm_order ?>">
    <input type="hidden" id="url-assign-order" value="<?php echo $url_assign_order ?>">
    <input type="hidden" id="url_assign_exception" value="<?php echo $url_assign_exception ?>">
    <input type="hidden" id="url-revert-exception" value="<?php echo $url_revert_exception ?>">
    <input type="hidden" id="url-search-exceptions" value="<?php echo $url_search_exceptions ?>">

    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" data-toggle="tooltip" title="<?php echo $button_save ?>" class="btn btn-primary" form="ezdefi-form-config"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel ?>" data-toggle="tooltip" title="<?php echo $button_cancel ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
            </div>
                <h1><?php echo $heading_title ?></h1>
            <ul class="breadcrumb">
                <?php foreach($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>


    <div class="container-fluid ezdefi-admin-content-box">
        <input type="radio" id="ezdefi-config-tab" name="btn-radio-choose-tab" class="hidden tab-radio-input" checked data-tab="config">
        <input type="radio" id="ezdefi-exception-history-tab" name="btn-radio-choose-tab" class="hidden tab-radio-input" data-tab="exception-history">
		<input type="radio" id="ezdefi-new-exception-tab" name="btn-radio-choose-tab" class="hidden tab-radio-input" data-tab="new-exception">
		<input type="radio" id="ezdefi-exception-tab" name="btn-radio-choose-tab" class="hidden tab-radio-input" data-tab="exception">

        <div class="btn-group">
            <label type="button" class="btn btn-primary" for="ezdefi-config-tab"> <?php echo $tab_config ?> </label>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                    <?php echo $text_exception_management ?><span class="caret"></span></button>
                <ul class="dropdown-menu" role="menu">
                    <li><label class="ezdefi-btn-tab" for="ezdefi-new-exception-tab"> <?php echo $text_pending ?> </label></li>
                    <li><label class="ezdefi-btn-tab"  for="ezdefi-exception-history-tab"> <?php echo $text_confirmed ?> </label></li>
                    <li><label class="ezdefi-btn-tab"  for="ezdefi-exception-tab"> <?php echo $text_archived ?> </label></li>
                </ul>
            </div>
        </div>

        <div class="panel panel-default config-content-tab">
            <form action="<?php echo $action ?>"
                  class="form-horizontal" method="POST" id="ezdefi-form-config"
                  data-url_validate_public_key="<?php echo $url_validate_public_key ?>"
                  data-url_validate_api_key="<?php echo $url_validate_api_key ?>">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $tab_edit_general ?> </h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="control-label col-sm-3" for=""><?php echo $entry_enable_extension ?></label>
                        <div class="col-sm-9">
                            <label class="control-label" for="enable-ezdefi">
                                <?php if($ezdefi_status) { ?>
                                    <input type="checkbox" class="margin-right-10" id="enable-ezdefi" name="ezdefi_status" checked> <?php echo $text_enable_ezdefi ?>
                                <?php } else { ?>
                                    <input type="checkbox" class="margin-right-10" id="enable-ezdefi" name="ezdefi_status"> <?php echo $text_enable_ezdefi ?>
                                <?php }?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3" for="gateway-api-url"> <?php echo $entry_gateway_api_url ?> </label>
                        <div class="col-sm-9">
                            <input type="text" id="gateway-api-url-input" class="form-control" name="ezdefi_gateway_api_url" value="<?php echo $ezdefi_gateway_api_url ?>" placeholder="<?php echo $config_gateway_api_url_default ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3" for="api-key-input"> <?php echo $entry_api_key ?> </label>
                        <div class="col-sm-9">
                            <input type="text" id="api-key-input" class="form-control" name="ezdefi_api_key" value="<?php echo $ezdefi_api_key ?>">
                        </div>
                        <div class="col-sm-offset-3 col-sm-9"><a href="<?php echo $text_link_register ?>" target="_blank"><?php echo $text_register ?></a></div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3" for="public-key-input"> <?php echo $entry_public_key ?> </label>
                        <div class="col-sm-9">
                            <input type="text" id="public-key-input" class="form-control" name="ezdefi_public_key" value="<?php echo $ezdefi_public_key ?>">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="panel panel-defaul exception-content-tab">
            <input type="hidden" id="url-search-exceptions" value="<?php echo $url_search_exceptions ?>">
            <input type="hidden" id="current-page-exception" value="1">
            <input type="hidden" id="total-number-exception" value="">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $tab_exception_archived ?> </h3>
            </div>
            <div class="panel-body">
                <div class="exception-filter-currency-box">
                    <div class="exception-filter-currency-item">
                        <label for="filter-exception-by-currency-all" class="margin-top-5"> All coin </label>
                        <input type="radio" name="filter-by-currency" value="" id="filter-exception-by-currency-all" checked>
                    </div>
                    <?php if($coins) { ?>
                        <?php foreach($coins as $coin) { ?>
                            <div class="exception-filter-currency-item">
                                <label for="filter-exception-by-currency-<?php $coin['token']['symbol'] ?>"><img src="<?php echo $coin['token']['logo'] ?>" alt="" class="small-logo"> <?php $coin['token']['symbol'] ?></label>
                                <input type="radio" name="filter-by-currency" value="<?php echo $coin['token']['symbol'] ?>" id="filter-exception-by-currency-<?php echo $coin['token']['symbol'] ?>">
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <br>
                <div class="ezdefi-search-box">
                    <input type="text" id="log-search-by-amount" placeholder="Enter amount" class="search-exception-input">
                    <input type="text" id="log-search-by-order" placeholder="Enter order id" class="search-exception-input">
                    <input type="text" id="log-search-by-email" placeholder="Enter email" class="search-exception-input">
                    <button class="btn" id="btn-search-log"><i class="fa fa-search"></i></button>
                </div>
                <div class="data-container"></div>
                <div id="log-content-box">
                </div>
            </div>

            <div id="delete-log-modal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo $text_delete_exception ?></h4>
                        </div>
                        <div class="modal-body">
                            <p><?php echo $text_ask_delete_exception ?></p>
                            <input type="hidden" id="exception-id--log-delete">
                        </div>
                        <div class="modal-footer" style="text-align: center">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $button_close ?></button>
                            <button type="button" class="btn btn-primary" id="btn-delete-log" data-url-delete="<?php echo $url_delete_exception ?>">
                                <i class="fa fa-refresh fa-spin exception-loading-icon exception-delete-loading"></i><?php echo $button_yes ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="confirm-log-modal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo $text_confirm_order ?></h4>
                        </div>
                        <div class="modal-body">
                            <p><?php echo $text_ask_confirm_order ?></p>
                            <input type="hidden" id="exception-id--confirm-log">
                        </div>
                        <div class="modal-footer" style="text-align: center">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $button_close ?></button>
                            <button type="button" class="btn btn-primary" id="btn-confirm-log" data-url-delete="<?php echo $url_delete_exception ?>" data-url-add-order-history="<?php echo $url_add_order_history ?>">
                                <i class="fa fa-refresh fa-spin exception-loading-icon"></i><?php echo $button_yes ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- ===========================================new excpetion==================================== -->
        <div class="panel panel-defaul new-exception-content">
            <input type="hidden" id="current-page-new-exception" value="1">
            <input type="hidden" id="total-number-new-exception" value="">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $tab_exception_pending ?> </h3>
            </div>
            <div class="panel-body">
                <div class="exception-filter-currency-box">
                    <div class="exception-filter-currency-item">
                        <label for="filter-exception-by-currency-all" class="margin-top-5"> All coin </label>
                        <input type="radio" name="new-exception-search-by-currency" value="" id="filter-exception-by-currency-all" checked>
                    </div>
                    <?php if($coins) { ?>
                    <?php foreach($coins as $coin) { ?>
                    <div class="exception-filter-currency-item">
                        <label for="filter-exception-by-currency-<?php $coin['token']['symbol'] ?>"><img src="<?php echo $coin['token']['logo'] ?>" alt="" class="small-logo"> <?php $coin['token']['symbol'] ?></label>
                        <input type="radio" name="new-exception-search-by-currency" value="<?php echo $coin['token']['symbol'] ?>" id="filter-exception-by-currency-<?php echo $coin['token']['symbol'] ?>">
                    </div>
                    <?php } ?>
                    <?php } ?>
                </div>
                <br>
                <div class="ezdefi-search-box">
                    <input type="text" id="new-exception-search-by-amount" placeholder="Enter amount" class="search-exception-input">
                    <input type="text" id="new-exception-search-by-order" placeholder="Enter order id" class="search-exception-input">
                    <input type="text" id="new-exception-search-by-email" placeholder="Enter email" class="search-exception-input">
                    <button class="btn" id="btn-search-new-exception"><i class="fa fa-search"></i></button>
                </div>
                <div class="data-container"></div>
                <div id="new-exception-content-box">
                </div>
            </div>

            <div id="delete-exception-modal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo $text_delete_exception ?></h4>
                        </div>
                        <div class="modal-body">
                            <p><?php echo $text_ask_delete_exception ?></p>
                            <input type="hidden" id="exception-id--delete">
                        </div>
                        <div class="modal-footer" style="text-align: center">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $button_close ?></button>
                            <button type="button" class="btn btn-primary" id="btn-delete-exception" data-url-delete="<?php echo $url_delete_exception ?>">
                                <i class="fa fa-refresh fa-spin exception-loading-icon exception-delete-loading"></i><?php echo $button_yes ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="confirm-exception-modal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo $text_confirm_order ?></h4>
                        </div>
                        <div class="modal-body">
                            <p><?php echo $text_ask_confirm_order ?></p>
                            <input type="hidden" id="exception-id--confirm-exception">
                        </div>
                        <div class="modal-footer" style="text-align: center">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $button_close ?></button>
                            <button type="button" class="btn btn-primary" id="btn-confirm-exception">
                                <i class="fa fa-refresh fa-spin exception-loading-icon"></i><?php echo $button_yes ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="assign-exception-modal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"> <?php echo $text_assign_order ?> </h4>
                        </div>
                        <div class="modal-body">
                            <p> <?php echo $text_ask_assign_order ?> </p>
                            <input type="hidden" id="exception-order-id--assign">
                            <input type="hidden" id="exception-id--assign">
                        </div>
                        <div class="modal-footer" style="text-align: center">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $button_close ?></button>
                            <button type="button" class="btn btn-primary" id="btn-assign-exception" data-url-assign="<?php echo $url_assign_exception ?>">
                                <i class="fa fa-refresh fa-spin exception-loading-icon exception-delete-loading"></i><?php echo $button_yes ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-defaul exception-history-content">
            <input type="hidden" id="current-page-exception-history" value="1">
            <input type="hidden" id="total-number-exception-history" value="">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $tab_exception_confirmed ?> </h3>
            </div>
            <div class="panel-body">
                <div class="exception-filter-currency-box">
                    <div class="exception-filter-currency-item">
                        <label for="filter-exception-by-currency-all" class="margin-top-5"> <?php echo $text_all_coin ?> </label>
                        <input type="radio" name="exception-history-search-by-currency" value="" id="filter-exception-by-currency-all" checked>
                    </div>
                    <?php if($coins) { ?>
                    <?php foreach($coins as $coin) { ?>
                    <div class="exception-filter-currency-item">
                        <label for="filter-exception-by-currency-<?php $coin['token']['symbol'] ?>"><img src="<?php echo $coin['token']['logo'] ?>" alt="" class="small-logo"> <?php $coin['token']['symbol'] ?></label>
                        <input type="radio" name="exception-history-search-by-currency" value="<?php echo $coin['token']['symbol'] ?>" id="filter-exception-by-currency-<?php echo $coin['token']['symbol'] ?>">
                    </div>
                    <?php } ?>
                    <?php } ?>
                </div>
                <br>
                <div class="ezdefi-search-box">
                    <input type="text" id="exception-history-search-by-amount" placeholder="Enter amount" class="search-exception-input">
                    <input type="text" id="exception-history-search-by-order" placeholder="Enter order id" class="search-exception-input">
                    <input type="text" id="exception-history-search-by-email" placeholder="Enter email" class="search-exception-input">
                    <button class="btn" id="btn-search-exception-history"><i class="fa fa-search"></i></button>
                </div>
                <div class="data-container"></div>
                <div id="exception-history-content-box">
                </div>
            </div>

            <div id="delete-exception-modal--history" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo $text_delete_exception ?></h4>
                        </div>
                        <div class="modal-body">
                            <p><?php echo $text_ask_delete_exception ?></p>
                            <input type="hidden" id="exception-id--history-delete">
                        </div>
                        <div class="modal-footer" style="text-align: center">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $button_close ?></button>
                            <button type="button" class="btn btn-primary" id="btn-delete-exception-history" data-url-delete="<?php echo $url_delete_exception ?>">
                                <i class="fa fa-refresh fa-spin exception-loading-icon exception-delete-loading"></i><?php echo $button_yes ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="revert-exception-modal" class="modal fade" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"> <?php echo $text_revert_order ?> </h4>
                        </div>
                        <div class="modal-body">
                            <p> <?php echo $text_ask_revert_order ?> </p>
                            <input type="hidden" id="exception-id--revert">
                        </div>
                        <div class="modal-footer" style="text-align: center">
                            <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo $button_close ?></button>
                            <button type="button" class="btn btn-primary" id="btn-revert-exception-history">
                                <i class="fa fa-refresh fa-spin exception-loading-icon exception-delete-loading"></i><?php echo $button_yes ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
    var language = {
        ordinal: '<?php echo $text_ordinal ?>',
        currency: '<?php echo $text_currency ?>',
        amount: '<?php echo $text_amount ?>',
        order: '<?php echo $text_order ?>',
        viewTransactionDetail: '<?php echo $text_view_transaction_detail ?>',
        orderId: '<?php echo $text_order_id ?>',
        email: '<?php echo $text_email ?>',
        price: '<?php echo $text_price ?>',
        createAt: '<?php echo $text_create_at ?>',
        customer: '<?php echo $text_customer ?>',
        expiration: '<?php echo $text_expiration?>',
        paid: '<?php echo $text_paid ?>',
        payByEzdefi: '<?php echo $text_pay_by_ezdefi ?>',
        textNo: '<?php echo $text_no ?>',
        textYes: '<?php echo $text_yes ?>',
        haveNotPaid: '<?php echo $text_have_not_paid ?>',
        paidOnTime: '<?php echo $text_paid_on_time ?>',
        paidOnExpiration: '<?php echo $text_paid_on_expiration ?>',
        delete: '<?php echo $text_delete ?>',
        confirmPaid: '<?php echo $text_confirm_paid ?>',
        revert: '<?php echo $text_revert ?>',
        assign: '<?php echo $text_assign ?>',
        old_order: '<?php echo $text_old_order?>',
        action: '<?php echo $text_action?>',
        payment_info: '<?php echo $text_payment_info?>',
    }
</script>
<?php echo $footer ?>