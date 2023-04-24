define(
    [
        'jquery',
        'M2ePro/Common',
        'M2ePro/Plugin/Magento/AttributeCreator',
    ],
    function(jQuery) {
        window.AmazonTemplateProductTypeContent = Class.create(Common, {
            constFieldNotConfigured: M2ePro.php.constant(
                '\\Ess\\M2ePro\\Model\\Amazon\\Template\\ProductType::FIELD_NOT_CONFIGURED'
            ),
            constFieldCustomValue: M2ePro.php.constant(
                '\\Ess\\M2ePro\\Model\\Amazon\\Template\\ProductType::FIELD_CUSTOM_VALUE'
            ),
            constFieldCustomAttribute: M2ePro.php.constant(
                '\\Ess\\M2ePro\\Model\\Amazon\\Template\\ProductType::FIELD_CUSTOM_ATTRIBUTE'
            ),

            templates: {
                field: '',
                fieldset: ''
            },

            groups: {},
            settings: {},
            arraySize: {},
            htmlIdToScheme: {},
            controlElementInit: {},
            timezoneShift: 0,

            initialize: function()
            {
                var template;

                for (const type of Object.keys(this.templates)) {
                    template = $('template_' + type);
                    this.templates[type] = template.innerHTML;
                    template.remove();
                }

                jQuery.validator.addMethod(
                    'M2ePro-validate-product-type-specific-mode',
                    function(value, element) {
                        if (!element.classList.contains('required-entry')) {
                            return true;
                        }

                        if (value == AmazonTemplateProductTypeContentObj.constFieldNotConfigured) {
                            return false;
                        }

                        if (value == AmazonTemplateProductTypeContentObj.constFieldCustomValue) {
                            var htmlId = element.id.slice('field_mode_'.length);

                            return $('field_value_' + htmlId).value != '';
                        }

                        return true;
                    },
                    M2ePro.translator.translate('This is a required field.')
                );
            },

            load: function (scheme, settings, groups, timezoneShift)
            {
                this.settings = settings;
                this.initGroups(groups);
                this.timezoneShift = timezoneShift;

                if (!scheme.length) {
                    return;
                }

                this.initDictionaryHtmlIdToScheme(scheme);
                const groupFieldsets = this.createGroupFieldsets(
                    this.separateByGroups(scheme)
                );

                for (const group of Object.keys(groupFieldsets)) {
                    for (var i = 0; i < groupFieldsets[group].length; i++) {
                        this.renderFieldset(group, groupFieldsets[group][i]);
                    }
                }
            },

            initDictionaryHtmlIdToScheme: function (scheme, path = [])
            {
                var htmlId, currentPath;
                for (var i = 0; i < scheme.length; i++) {
                    currentPath = path.clone();
                    currentPath.push(scheme[i]['name']);

                    htmlId = this.getHtmlId(currentPath);
                    this.htmlIdToScheme[htmlId] = scheme[i];
                    if (scheme[i]['type'] === 'container' && scheme[i]['children'].length) {
                        this.initDictionaryHtmlIdToScheme(scheme[i]['children'], currentPath);
                    }
                }
            },

            separateByGroups: function (scheme)
            {
                const result = {};

                var item, group;
                for (var i = 0; i < scheme.length; i++) {
                    item = scheme[i];
                    group = item['group_nick'];
                    if (result[group] === undefined) {
                        result[group] = [];
                    }

                    result[group].push(item);
                }

                return result;
            },

            createGroupFieldsets: function (schemeWithGroups)
            {
                const result = {};

                var parsingContainer, fieldset, item, modeChanging;
                for (const group of Object.keys(schemeWithGroups)) {
                    if (result[group] === undefined) {
                        result[group] = [];
                    }

                    fieldset = this.getBlankFieldset();

                    item = schemeWithGroups[group][0];
                    parsingContainer = item['type'] === 'container';

                    for (var i = 0; i < schemeWithGroups[group].length; i++) {
                        item = schemeWithGroups[group][i];
                        modeChanging = parsingContainer ^ (item['type'] === 'container');

                        if (modeChanging || item['type'] === 'container') {
                            result[group].push(fieldset);
                            fieldset = this.getBlankFieldset();
                        }

                        if (item['type'] === 'container') {
                            fieldset['title'] = item['title'];
                            fieldset['description'] = item['description'];
                        }

                        fieldset['content'].push(item);
                        parsingContainer = item['type'] === 'container';
                    }

                    if (fieldset['content'].length) {
                        result[group].push(fieldset);
                    }
                }

                return result;
            },

            getBlankFieldset: function()
            {
                return {
                    title: '',
                    description: '',
                    content: [],
                };
            },

            renderFieldset: function (group, fieldset)
            {
                if (fieldset['content'].length === 0) {
                    return;
                }

                const fieldsetElement = this.createFieldsetElement(fieldset['title'], fieldset['description']);
                AmazonTemplateProductTypeTabsObj.addTabContent(group, fieldsetElement);

                var i, field;
                for (i = 0; i < fieldset['content'].length; i++) {
                    field = fieldset['content'][i];
                    this.renderFieldGeneral(this.appendChildCallback, fieldsetElement, field, [], true);
                }
            },

            createFieldsetElement: function (title, tooltip)
            {
                const html = this.templates.fieldset
                    .replaceAll('%title%', title.escapeHTML())
                    .replaceAll('%tooltip%', tooltip.escapeHTML())
                    .replaceAll(
                        '%tooltip_style%',
                        tooltip ? 'display: inline;' : 'display: none;'
                    );

                const element = new Element('div');
                element.innerHTML = html;
                return element.getElementsByTagName('fieldset')[0];
            },

            renderFieldGeneral: function (insertCallback, container, field, path, isParentRequired)
            {
                switch (field['type']) {
                    case 'container':
                        this.renderContainerField(insertCallback, container, field, path, isParentRequired);
                        break;
                    case 'number':
                    case 'integer':
                    case 'boolean':
                    case 'string':
                        const [htmlId, addedElements] = this.renderSimpleField(insertCallback, container, field, path, isParentRequired);

                        if (
                            field['validation_rules']['max_items'] !== undefined
                            && field['validation_rules']['max_items'] > 1
                            && this.controlElementInit[htmlId] === undefined
                        ) {
                            insertCallback(container, this.createInsertAnchorElement(htmlId));
                            insertCallback(container, this.createArrayControlElements(htmlId, addedElements > 1, isParentRequired));

                            this.controlElementInit[htmlId] = true;
                        }

                        break;
                }
            },

            renderSimpleField: function (insertCallback, container, scheme, path, isParentRequired)
            {
                path = path.clone();
                path.push(scheme['name']);

                var addedElements = 0;
                const settings = this.getSettings(path);
                if (settings !== null && settings[0] !== undefined) {
                    for (var i = 0; i < settings.length; i++) {
                        this.renderSimpleFieldValue(insertCallback, container, path, scheme, settings[i], isParentRequired);
                        addedElements++;
                    }
                } else {
                    this.renderSimpleFieldValue(insertCallback, container, path, scheme, null, isParentRequired);
                    addedElements = 1;
                }

                return [this.getHtmlId(path), addedElements];
            },

            renderSimpleFieldValue: function (insertCallback, container, path, scheme, settings, isParentRequired)
            {
                var htmlId = this.getHtmlId(path);
                const index = this.getArraySize(htmlId);
                this.setArraySize(htmlId, index + 1);

                path = path.clone();
                path.push(index);
                htmlId = this.getHtmlId(path);
                var formId = this.getFormId(path);

                var containerClass = '', modeClass = '';
                if (isParentRequired && scheme['validation_rules']['is_required']) {
                    containerClass = 'required';
                    modeClass = 'required-entry';
                }

                const html = this.templates.field
                    .replaceAll('%id%', htmlId)
                    .replaceAll('%formId%', formId)
                    .replaceAll('%title%', scheme['title'].escapeHTML())
                    .replaceAll('%tooltip%', scheme['description'] ? scheme['description'].escapeHTML() : '')
                    .replaceAll('%tooltipStyle%', scheme['description'] ? '' : 'display: none;')
                    .replaceAll('%containerClass%', containerClass)
                    .replaceAll('%modeClass%', modeClass);

                const temp = new Element('div');
                temp.innerHTML = html;

                const element = temp.getElementsByTagName('div')[0];
                if (scheme['hidden']) {
                    element.style.display = 'none';
                }

                var type;
                if (scheme['format'] === 'date-time') {
                    type = 'date-time';
                } else if (scheme['options'] && Object.keys(scheme['options']).length) {
                    type = 'select';
                } else if (
                    scheme['type'] === 'string'
                    && (
                        scheme['validation_rules']['max_length'] === undefined
                        || scheme['validation_rules']['max_length'] > 100
                    )
                ) {
                    type = 'textarea';
                } else {
                    type = 'input';
                }

                insertCallback(container, element);

                const customValueContainer = $('custom_value_container_' + htmlId);
                this.getCustomValueElements(type, scheme, htmlId, formId).map(
                    function (item) {
                        customValueContainer.appendChild(item);
                    }
                );

                this.initField(settings, scheme, htmlId, index == 0);
            },

            renderContainerField: function (insertCallback, container, field, path, isParentRequired)
            {
                path = path.clone();
                path.push(field['name']);

                var required = isParentRequired && field['validation_rules']['is_required'];
                for (var i = 0; i < field['children'].length; i++) {
                    this.renderFieldGeneral(insertCallback, container, field['children'][i], path, required);
                }
            },

            getSettings: function (path)
            {
                const htmlId = this.getHtmlId(path);
                return (this.settings[htmlId] !== undefined && typeof this.settings[htmlId] === 'object') ?
                    this.settings[htmlId] : null;
            },

            getArraySize: function (htmlId)
            {
                return this.arraySize[htmlId] === undefined ? 0 : this.arraySize[htmlId]
            },

            setArraySize: function (htmlId, size)
            {
                this.arraySize[htmlId] = size;
            },

            createArrayControlElements: function (htmlId, showRemoveButton, isParentRequired)
            {
                const element = new Element('div', {class: 'product_type_array_control_elements'});
                element.appendChild(this.createButtonAdd(htmlId, isParentRequired));
                element.appendChild(this.createButtonRemoveLast(htmlId, showRemoveButton));

                return element;
            },

            createButtonAdd: function (htmlId, isParentRequired)
            {
                const buttonAdd = new Element(
                    'button',
                    {
                        id: 'add_element_button_' + htmlId,
                        title: 'Add',
                        class: 'scalable primary',
                        style: 'margin-right: 2.5rem;'
                    }
                );

                buttonAdd.innerHTML = '<span>Add</span>';
                buttonAdd.dataset.htmlIdToProcess = htmlId;
                buttonAdd.dataset.isParentRequired = isParentRequired;
                buttonAdd.onclick = this.addElementOnclickHandler.bind(this);

                return buttonAdd;
            },

            createButtonRemoveLast: function (htmlId, showRemoveButton)
            {
                const displayMode = showRemoveButton ? 'inline' : 'none';
                const buttonRemove = new Element(
                    'button',
                    {
                        id: 'remove_element_button_' + htmlId,
                        title: 'Remove',
                        class: 'scalable',
                        style: 'margin-right: 2.5rem; display: ' + displayMode
                    }
                );

                buttonRemove.innerHTML = '<span>Remove</span>';
                buttonRemove.dataset.htmlIdToProcess = htmlId;
                buttonRemove.onclick = this.removeElementOnclickHandler.bind(this);

                return buttonRemove;
            },

            createInsertAnchorElement: function (htmlId)
            {
                return new Element(
                    'div',
                    {
                        id: 'insert-anchor-' + htmlId
                    }
                );
            },

            addElementOnclickHandler: function (event)
            {
                const htmlId = event.currentTarget.dataset.htmlIdToProcess;
                const isParentRequired = event.currentTarget.dataset.isParentRequired === 'true';
                const anchorId = 'insert-anchor-' + htmlId;

                const scheme = this.getSchemeFromHtmlId(htmlId);
                const path = htmlId.split('/');

                const insertCallback = function (_, insertElement)
                {
                    $(anchorId).before(insertElement);
                };
                this.renderSimpleFieldValue(insertCallback, null, path, scheme, null, isParentRequired);

                event.currentTarget.blur();
                this.changeControlElementsState(htmlId);

                return false;
            },

            removeElementOnclickHandler: function (event)
            {
                const htmlId = event.currentTarget.dataset.htmlIdToProcess;
                const size = this.getArraySize(htmlId);

                if (size > 0) {
                    const index = size - 1;
                    $('field_container_' + htmlId + '/' + index).remove();
                    this.setArraySize(htmlId, index);
                }

                event.currentTarget.blur();
                this.changeControlElementsState(htmlId);

                return false;
            },

            changeControlElementsState: function (htmlId)
            {
                const size = this.getArraySize(htmlId);
                const scheme = this.getSchemeFromHtmlId(htmlId);

                const buttonRemove = $('remove_element_button_' + htmlId);
                buttonRemove.style.display = size > 1 ? 'inline' : 'none';

                var showAddButton = false;
                if (
                    scheme['validation_rules']['max_items'] !== undefined
                    && scheme['validation_rules']['max_items'] > 1
                    && size < scheme['validation_rules']['max_items']
                ) {
                    showAddButton = true;
                }

                const buttonAdd = $('add_element_button_' + htmlId);
                buttonAdd.style.display = showAddButton ? 'inline' : 'none';
            },

            getSchemeFromHtmlId: function (htmlId)
            {
                return this.htmlIdToScheme[htmlId] !== undefined ? this.htmlIdToScheme[htmlId] : {};
            },

            renderOptions: function (options)
            {
                var result = '<option style="display: none;"></option>';
                for (const [value, title] of Object.entries(options)) {
                    result += '<option value="' + value.escapeHTML() + '">' + title.escapeHTML() + '</option>';
                }

                return result;
            },

            initGroups: function (groups)
            {
                this.groups = groups;
                for (var i = 0; i < groups.length; i++) {
                    AmazonTemplateProductTypeTabsObj.insertTab(groups[i]['nick'], groups[i]['title']);
                }

                AmazonTemplateProductTypeTabsObj.refreshTabs();
            },

            getGroupList: function ()
            {
                const groupList = [];
                for (var i = 0; i < this.groups.length; i++) {
                    groupList.push(this.groups[i]['nick']);
                }

                return groupList;
            },

            initField: function(itemData, itemScheme, htmlId, isFirstArrayField)
            {
                const modeElement = $('field_mode_' + htmlId),
                    attributeElement = $('field_attribute_' + htmlId),
                    valueElement = $('field_value_' + htmlId),
                    customValueContainer = $('custom_value_container_' + htmlId);

                var handlerObj = new AttributeCreator('field_attribute_' + htmlId);
                handlerObj.setSelectObj(attributeElement);
                handlerObj.injectAddOption();

                if (itemData !== null && itemData['mode']) {
                    if (itemData['mode'] == this.constFieldCustomAttribute) {
                        modeElement.value = itemData['mode'];
                        attributeElement.value = itemData['attribute_code'];
                        attributeElement.style.display = 'inline';
                        customValueContainer.style.display = 'inline';
                    } else if (itemData['mode'] == this.constFieldCustomValue) {
                        if (itemScheme['format'] === 'date-time') {
                            const date = new Date(itemData['value']);
                            date.setTime(date.getTime() + this.timezoneShift * 1000);

                            const parts = {
                                year: date.getFullYear(),
                                month: date.getMonth() + 1,
                                day: date.getDate(),
                                hours: date.getHours(),
                                minutes: date.getMinutes()
                            };
                            for (const key of Object.keys(parts)) {
                                parts[key] = parts[key] > 9 ? parts[key] : '0' + parts[key];
                            }

                            itemData['value'] = parts.year + '-' + parts.month + '-' + parts.day
                                + 'T' + parts.hours + ':' + parts.minutes;
                        }

                        modeElement.value = itemData['mode'];
                        valueElement.value = itemData['value'];
                        customValueContainer.style.display = 'inline';
                    } else {
                        modeElement.style.color = 'grey';
                    }
                } else if (itemScheme['default_value'] !== null && isFirstArrayField) {
                    modeElement.value = this.constFieldCustomValue;
                    valueElement.value = itemScheme['default_value'];
                    customValueContainer.style.display = 'inline';
                } else {
                    modeElement.style.color = 'grey';
                }

                var selectOnChangeHandler = function () {
                    this.onchangeItemSelectHandler(itemScheme, htmlId)
                }.bind(this);
                modeElement
                    .observe('change', selectOnChangeHandler)
                    .simulate('change');
            },

            onchangeItemSelectHandler: function (itemScheme, htmlId) {
                const modeElement = $('field_mode_' + htmlId),
                    attributeElement = $('field_attribute_' + htmlId),
                    valueElement = $('field_value_' + htmlId),
                    customValueContainer = $('custom_value_container_' + htmlId);

                modeElement.style.color = 'black';
                attributeElement.style.display = 'none';
                valueElement.style.display = 'none';
                customValueContainer.style.display = 'none';

                if (modeElement.value == this.constFieldNotConfigured) {
                    modeElement.style.color = 'grey';
                } else if (modeElement.value == this.constFieldCustomValue) {
                    valueElement.style.display = 'inline';
                    customValueContainer.style.display = 'inline';
                } else if (modeElement.value == this.constFieldCustomAttribute) {
                    attributeElement.style.display = 'inline';
                    customValueContainer.style.display = 'inline';
                }
            },

            getCustomValueElements: function (type, scheme, htmlId, formId)
            {
                var element;

                switch (type) {
                    case 'input':
                        element = new Element(
                            'input',
                            {
                                id: 'field_value_' + htmlId,
                                name: 'field_data' + formId + '[value]',
                                style: 'display: none;',
                                class: 'admin__control-text input-text product_type_setting_element',
                                placeholder: scheme['example'] ? 'Example: ' + scheme['example'] : ''
                            }
                        );
                        return [element];
                    case 'textarea':
                        element = new Element(
                            'textarea',
                            {
                                id: 'field_value_' + htmlId,
                                name: 'field_data' + formId + '[value]',
                                rows: 4,
                                style: 'display: none;',
                                class: 'textarea admin__control-textarea product_type_setting_element',
                                placeholder: scheme['example'] ? 'Example: ' + scheme['example'] : ''
                            }
                        );
                        return [element];
                    case 'select':
                        const options = scheme['options'] ? this.renderOptions(scheme['options']) : '';
                        element = new Element(
                            'select',
                            {
                                id: 'field_value_' + htmlId,
                                name: 'field_data' + formId + '[value]',
                                style: 'display: none;',
                                class: 'select admin__control-select product_type_setting_element'
                            }
                        );
                        element.innerHTML = options;
                        return [element];
                    case 'date-time':
                        element = new Element(
                            'input',
                            {
                                id: 'field_value_' + htmlId,
                                name: 'field_data' + formId + '[value]',
                                type: 'datetime-local',
                                style: 'display: none;',
                                class: 'admin__control-text input-text product_type_setting_element'
                            }
                        );
                        const format = new Element(
                            'input',
                            {
                                id: 'field_format_' + htmlId,
                                name: 'field_data' + formId + '[format]',
                                style: 'display: none;',
                                value: 'date-time'
                            }
                        );

                        return [element, format];
                    default:
                        console.error(`unsupported custom value element type: ${type}`);
                        return [];
                }
            },

            getHtmlId: function (path)
            {
                return path.join('/');
            },

            getFormId: function (path)
            {
                const result = path.clone();

                return result.map(
                    function (item) {
                        return '[' + item + ']';
                    }
                ).join('');
            },

            appendChildCallback: function (target, insertElement)
            {
                target.appendChild(insertElement);
            }
        });
    }
);
