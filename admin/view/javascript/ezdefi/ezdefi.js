$(function () {
    const selectors = {
        formConfig: '#ezdefi-form-config',
        gatewayApiUrlInput: "#gateway-api-url-input",
        apiKeyInput: "#api-key-input",
        publicKeyInput: "#public-key-input",
    };

    var oc_ezdefi_admin = function () {
        $(selectors.gatewayApiUrlInput).keyup(function () {
            $(selectors.gatewayApiUrlInput).valid()
        });

        $(selectors.apiKeyInput).keyup(function () {
            $(selectors.apiKeyInput).valid()
        });

        $(selectors.publicKeyInput).keyup(function () {
            $(selectors.publicKeyInput).valid()
        });

        this.initValidate();
    };

    oc_ezdefi_admin.prototype.initValidate = function () {
        $(selectors.formConfig).validate({
            submitHandler: function (form) {
                form.submit();
            },
            errorPlacement: function (error, element) {
                if (element.data('error-label')) {
                    error.insertBefore($(element.data('error-label')));
                } else {
                    error.insertAfter(element);
                }
            }
        });

        $(selectors.gatewayApiUrlInput).rules('add', {url: true, required: true});
        this.validateApiKey();
        this.validatePublicKey();
    };

    oc_ezdefi_admin.prototype.validateApiKey = function () {
        $(selectors.apiKeyInput).rules('add', {
            required: true,
            remote: {
                url: $(selectors.formConfig).data('url_validate_api_key'),
                type: 'get',
                data: {
                    gateway_url: function () {return $(selectors.gatewayApiUrlInput).val() },
                }
            },
            messages: {
                remote: "This Api Key is invalid"
            }
        });
    };

    oc_ezdefi_admin.prototype.validatePublicKey = function () {
        $(selectors.publicKeyInput).rules('add', {
            required: true,
            remote: {
                url: $(selectors.formConfig).data('url_validate_public_key'),
                type: 'get',
                data: {
                    gateway_url: function () { return $(selectors.gatewayApiUrlInput).val() },
                    api_key: function () { return $(selectors.apiKeyInput).val() }
                }
            },
            messages: {
                remote: "This Site Id is invalid"
            }
        });
    };


    new oc_ezdefi_admin();
});