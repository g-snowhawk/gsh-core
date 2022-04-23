/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_Form = function() {
    this.capture = {};
    this.through = false;
    this.formCount = 0;
    this.skipConfirm = false;

    this.cnMultiSelector = 'multi-selector';
    this.cnImageSelector = 'image-selector';
    this.cnCustomSelector = 'select-text';

    TM.initModule(this.init, this, 'interactive');
    TM.initModule(this.focusInFirstChild, this, 'complete');
};

TM_Form.prototype.focusInFirstChild = function() {
    if (document.forms.length > 0) {
        var i, elements = document.forms[0].elements;
        for (i = 0; i < elements.length; i++) {
            var element = elements[i];
            var rect = element.getBoundingClientRect();
            if (   rect.height === 0
                || rect.width === 0
                || rect.top + rect.height <= 0
                || rect.left + rect.width <= 0
                || rect.top >= window.innerHeight
                || rect.left >= window.innerWidth
            ) {
                continue;
            }
            element.focus();
            break;
        }
    }
};

TM_Form.prototype.onSubmit = function(evn) {
    var i, pr, el;
    var fm = evn.target;
    var invalid = 0;
    for(i = 0; i < fm.elements.length; i++){
        el = fm.elements[i];
        if(!el.dataset.validate) continue;
        if(el.dataset.validate === 'necessary' && el.value === ''){
            pr = TM.getParentNode(el, '.fieldset');
            if(pr) pr.classList.add('invalid');
            ++invalid;
        }
    }
    if(invalid > 0){
        evn.preventDefault();
        alert('未入力項目があります');
        return;
    }
    TM.form.through = true;
    if (fm.dataset.confirm && TM.form.skipConfirm === false) {
        if(!confirm(decodeURIComponent(fm.dataset.confirm))){
            evn.preventDefault();
            TM.form.through = false;
        } else {
            if(evn.type === 'externalsubmit') {
                fm.submit();
            }
        }
    }
};

TM_Form.prototype.changeStyle = function(evn) {
    var element = evn.target;
    var parent = TM.getParentNode(element, '.fieldset');
    if (parent) {
        var func = (evn.type === 'focus') ? 'add' : 'remove';
        parent.classList[func]('active');
    }
};

TM_Form.prototype.setDeleteConfirm = function() {
    var element = document.getElementsByName('delete');
    if (!element) {
        return;
    }
    for (var i = 0; i < element.length; i++) {
        element[i].addEventListener('click', this.sendDelete, false);
    }
};

TM_Form.prototype.sendDelete = function(event) {
    var element = event.target;

    if (element.checked) {
        var form = element.form;

        if (form.dataset.confirm) {
            var confirmation = form.dataset.confirm;
            if (TM.category) {
                TM.category.changeConfirm(element);
            }
            if (confirm(decodeURIComponent(form.dataset.confirm))) {
                TM.form.through = true;
                form.submit();
            }
            form.dataset.confirm = confirmation;
        }
    }
};

TM_Form.prototype.cancelEnterKey = function(event) {
    if (event.which === 13) {
        event.preventDefault();
    }
};

TM_Form.prototype.confirmUnload = function(event) {
    var instance = TM.form;
    for (var key in instance.capture) {
        var form = document.getElementById(key);
        if (!form || form.dataset.freeUnload == '1') {
            continue;
        }
        var capture = instance.captureForm(form);
        if (instance.through === false && instance.capture[key] != JSON.stringify(capture)) {
            var confirm = 'Not Save?';
            event.returnValue = confirm;
            return confirm;
        }
    }
};

TM_Form.prototype.submit = function(form) {
    this.through = true;
    form.submit();
};

TM_Form.prototype.captureForm = function(form) {
    var data = {};
    for (var i = 0; i < form.elements.length; i++) {
        var element = form.elements[i];
        if (element.nodeName === 'SELECT' && element.selectedIndex !== -1) {
            data[element.name] = element.options[element.selectedIndex].value;
        }
        else if (element.nodeName === 'TEXTAREA') {
            data[element.name] = element.value;
        }
        else {
            switch (element.type) {
                case 'button':
                case 'hidden':
                case 'submit':
                case 'reset':
                    continue;
                    break;
                case 'checkbox':
                case 'radio':
                    if (element.checked) {
                        data[element.name] = element.value;
                    }
                    break;
                default:
                    data[element.name] = element.value;
                    break;
            }
        }
    }
    //var data = new FormData(form);
    //for (var pair of data.entries()) {
    //    capture[pair[0]] = pair[1];
    //}
    return data;
};

TM_Form.prototype.setForm = function() {
    this.formCount = 0;
    var i, j, element;
    for(i = 0; i < document.forms.length; i++){
        var form = document.forms[i];
        form.addEventListener('submit', this.onSubmit, false);
        form.addEventListener('externalsubmit', this.onSubmit, false);

        for(j = 0; j < form.elements.length; j++){
            element = form.elements[j];
            element.addEventListener('focus', this.changeStyle, false);
            element.addEventListener('blur', this.changeStyle, false);
        }

        var capture = this.captureForm(form);
        var key = form.getAttribute('id');
        if (!key) {
            key = 'form_' + this.formCount;
            form.setAttribute('id', key);
        }
        this.capture[key] = JSON.stringify(capture);
        ++this.formCount;
    }
    window.addEventListener('beforeunload', this.confirmUnload);
};

TM_Form.prototype.checkInited = function() {
    if (TM.amendsInput && TM.amendsInput.inited === false) {
        setTimeout(TM.form.checkInited, 100);
        return;
    }
    TM.form.setForm();
};

TM_Form.prototype.changeSkipConfirm = function(event) {
    TM.form.skipConfirm = true;
};

TM_Form.prototype.init = function() {
    var inputs = document.getElementsByTagName('input');
    for(i = 0; i < inputs.length; i++){
        element = inputs[i];
        if (element.type === 'submit' || element.type === 'button') {
            if (element.dataset.skipConfirm === '1') {
                element.addEventListener('click', this.changeSkipConfirm, false);
            }
            continue;
        }
        element.addEventListener('keydown', this.cancelEnterKey, false);
    }

    var scripts = [];
    var styles = [];
    var multiSelector = document.getElementsByClassName(this.cnMultiSelector);
    if (multiSelector) {
        scripts.push({ src:'multi_selector', async:' async', defer:'' });
    }

    var imageSelector = document.getElementsByClassName(this.cnImageSelector);
    if (imageSelector) {
        scripts.push({ src:'uploader', async:' async', defer:'' });
    }

    var customSelector = document.getElementsByClassName(this.cnCustomSelector);
    if (multiSelector) {
        scripts.push({ src:'custom_selector', async:' async', defer:'' });
    }

    var editor = document.querySelector('a[data-insert]');
    if (editor) {
        scripts.push({ src:'editor', async:' async', defer:'' });
        styles.push({ href:'editor', rel:'stylesheet' });
    }

    scripts.push({ src:'amends_input', async:' async', defer:'' });

    if (scripts.length > 0) {
        TM.dynamicLoadModule(scripts);
    }
    if (styles.length > 0) {
        TM.loadStyle(styles);
    }

    this.setDeleteConfirm();

    setTimeout(this.checkInited, 100);
};

TM.form = new TM_Form();
