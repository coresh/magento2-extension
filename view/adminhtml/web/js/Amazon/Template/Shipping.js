define([
    'M2ePro/Amazon/Template/Edit'
], function () {

    window.AmazonTemplateShipping = Class.create(AmazonTemplateEdit,  {

        initialize: function()
        {
            this.setValidationCheckRepetitionValue('M2ePro-shipping-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. ' +
                    'Policy Title must be unique.'),
                'Amazon\\Template\\Shipping', 'title', 'id',
                M2ePro.formData.id
            );
        },

        initObservers: function()
        {
             $('account_id').observe('change', this.accountChange).simulate('change');
        },

        duplicateClick: function($headId)
        {
            this.setValidationCheckRepetitionValue('M2ePro-shipping-tpl-title',
                M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                'Amazon\\Template\\Shipping', 'title', 'id', ''
            );

            CommonObj.duplicateClick($headId, M2ePro.translator.translate('Add Shipping Policy'));
        },

        accountChange: function()
        {
            var select = $('template_id');
            var refresh = $('refresh_templates');
            var options = '<option></option>';

            if (!$('account_id').hasAttribute('disabled')) {
                select.update();
                select.insert(options);
            }

            if (!this.value) {
                select.setAttribute("disabled", "disabled");
                refresh.addClassName('disabled');
            } else {
                select.removeAttribute('disabled');
                refresh.removeClassName('disabled');
            }
        },

        refreshTemplateShipping: function()
        {
            new Ajax.Request(M2ePro.url.get('amazon_template_shipping/refresh'), {
                method: 'post',
                parameters: {
                    account_id: $('account_id').value
                },
                onSuccess: function()
                {
                    AmazonTemplateShippingObj.renderTemplates();
                }
            });
        },

        renderTemplates: function()
        {
            new Ajax.Request(M2ePro.url.get('amazon_template_shipping/getTemplates'), {
                method: 'post',
                parameters: {
                    account_id: $('account_id').value
                },
                onSuccess: function(transport)
                {
                    var select = $('template_id');
                    var options = '<option></option>';
                    var firstItem = null;
                    var currentValue = select.value;

                    var data = transport.responseText.evalJSON(true);

                    data.each(function(item) {
                        options += `<option value="${item.template_id}">${item.title}</option>`;

                        if (!firstItem) {
                            firstItem = item;
                        }
                    });

                    select.update();
                    select.insert(options);

                    if (currentValue !== '') {
                        select.value = currentValue;
                    } else if (typeof id !== 'undefined' && M2ePro.formData[id] > 0) {
                        select.value = M2ePro.formData[id];
                    } else {
                        select.value = firstItem.id;
                    }
                }
            });
        }
    });
});
