$(function () {
    const selectors = {
        formConfig: '#ezdefi-form-config',
        gatewayApiUrlInput: "#gateway-api-url-input",
        apiKeyInput: "#api-key-input",
        publicKeyInput: "#public-key-input",
    };

    var oc_ezdefi_admin = function () {
        $(selectors.gatewayApiUrlInput).keyup(this.validateConfigForm.bind(this));
        
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
                remote: "This Public Key is invalid"
            }
        });
    };


    oc_ezdefi_admin.prototype.validateConfigForm = function () {
        var apiKey = $(selectors.apiKeyInput).val();
        var publicKey = $(selectors.publicKeyInput).val();
        $(selectors.apiKeyInput).val('');
        $(selectors.publicKeyInput).val('');

        $(selectors.apiKeyInput).val(apiKey);
        $(selectors.publicKeyInput).val(publicKey);

        $(selectors.apiKeyInput).valid();
        $(selectors.publicKeyInput).valid();
    };

    new oc_ezdefi_admin();
});