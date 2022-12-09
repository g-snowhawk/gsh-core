/**
 * This file is part of G.Snowhawk Application.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function Subform() {
    this.inited = false;
    this.sealedID = 'subform-sealed';
    this.cancelButtonID = 'cancel-subform-button';
    this.cancelButtonClass = 'cancel-subform-buttons';
    this.containerID = 'subform-container';
    this.position = 'left';
    this.cnTransition = 'trans-' + this.position;
    this.subFormSize = undefined;
    this.pollingTimer = undefined;
    this.startPollingDelay = 1000;
    this.pollingInterval = 10000;
    this.finally = undefined;
    this.events = {
        opened: [],
        closed: [],
        initedForm: []
    };
    TM.initModule(this.init, this, 'interactive');
}

Subform.prototype.addListener = function(eventType, func) {
    if (this.events[eventType] === undefined) {
        console.error('Invalid argument 1 is unknown event type');
        return;
    }
    if (typeof(func) !== 'function') {
        console.log(typeof(func));
        console.error('Invalid argument 2 is not function');
        return;
    }
    for (var i = 0; i < this.events[eventType].length; i++) {
        if (this.events[eventType][i] === func) {
            return i;
        }
    }
    this.events[eventType].push(func);
    return this.events[eventType].indexOf(func);
};

Subform.prototype.show = function(container) {
    var instance = TM.subform;
    container.addEventListener('transitionend', instance.listener, false);
    container.classList.add(instance.cnTransition);
    container.style[instance.position] = '0';
};

Subform.prototype.endOpenTransition = function(container) {
    var instance = TM.subform;

    var checkProperty = true;
    switch (instance.position) {
        case 'bottom':
            checkProperty = (container.offsetTop >= window.innerHeight);
            break;
        case 'left':
            checkProperty = (container.offsetLeft < 0);
            break;
        case 'right':
            //checkProperty = (container.offsetLeft >= window.innerWidth);
            checkProperty = (container.offsetLeft >= document.documentElement.clientWidth);
            break;
    }

    var eventType;
    if (checkProperty) {
        container.parentNode.removeChild(container);
        var sealed = document.getElementById(this.sealedID);
        sealed.parentNode.removeChild(sealed);

        if (instance.finally) {
            TM.apply(instance.finally.function, instance.finally.arguments);
            instance.finally = undefined;
        }
        eventType = 'closed';
    }
    else {
        instance.setListenerButtons();
        eventType = 'opened';
    }

    for (var i = 0; i < instance.events[eventType].length; i++) {
        instance.events[eventType][i].apply(instance, [{ type: eventType }]);
    }
};

Subform.prototype.setListenerButtons = function() {
    var cancelSubformButton = document.getElementById(this.cancelButtonID);
    cancelSubformButton.addEventListener('click', this.listener, false);

    var cancelButtons = document.getElementsByClassName(this.cancelButtonClass);
    for (var i = 0; i < cancelButtons.length; i++) {
        cancelButtons[i].addEventListener('click', this.listener, false);
    }
};

Subform.prototype.setListenerForms = function(container) {
    var forms = container.getElementsByTagName('form');
    for (i = 0, max = forms.length; i < max; i++) {
        forms[i].addEventListener('submit', this.listener, false);
    }
};

Subform.prototype.initForm = function(source) {
    var container = document.getElementById(this.containerID);
    if (container) {
        container.innerHTML = source;
        this.setListenerButtons();
        this.setListenerForms(container);
        this.subFormSize = (this.position !== 'bottom') ? container.offsetWidth : container.offsetHeight;
        var eventType = 'initedForm';
        for (var i = 0; i < this.events[eventType].length; i++) {
            this.events[eventType][i].apply(this, [{ type: eventType }]);
        }
        return true;
    }
    return false;
};

Subform.prototype.create = function(json) {
    var sealed = document.getElementById(this.sealedID);
    if (this.initForm(json.response)) {
        return;
    }

    sealed = document.body.appendChild(document.createElement('div'));
    sealed.id = this.sealedID;

    var container = document.body.appendChild(document.createElement('div'));
    container.id = this.containerID;
    container.innerHTML = json.response;

    var form = container.querySelector('form');
    if (form && form.dataset && form.dataset.position) {
        this.position = form.dataset.position;
        this.cnTransition = 'trans-' + this.position;
    }
    container.classList.add(this.position);

    const hidden = document.createElement('input');
    hidden.name = 'submitter';
    hidden.type = 'hidden';
    form.appendChild(hidden);

    let submit = form.querySelectorAll('*[type=submit]');
    submit.forEach((element) => {
        element.addEventListener('click', (event) => {
            const element = event.target;
            if (element.form.submitter) {
                element.form.submitter.value = element.name;
            }
        });
    });

    this.setListenerForms(container);

    this.subFormSize = (this.position !== 'bottom') ? container.offsetWidth : container.offsetHeight;

    container.style[this.position] = '-' + this.subFormSize + 'px';
    setTimeout(this.show, 0, container);
};

Subform.prototype.close = function(container) {
    container.style[this.position] = '-' + this.subFormSize + 'px';
    var sealed = document.getElementById(this.sealedID);
    sealed.classList.add('fadeout');
};

Subform.prototype.open = function(element) {
    var instance = TM.subform;

    var action = element.pathname;

    var query = element.search.substr(1);
    var queries = query.split('&');
    var data = new FormData();
    for (var i = 0; i < queries.length; i++) {
        var pair = queries[i].split('=');
        data.append(pair[0], pair[1]);
    }

    var stub = document.querySelector('[name=stub]');
    data.append('stub', stub.value);
    data.append('script_referer', location.href);
    data.append('request_type', 'response-subform');

    TM.xhr.init('POST', action, true, function(event){
        if(this.status == 200){
            try {
                var json = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            instance.create(json);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
            alert('System Error!');
        }
    });
    TM.xhr.send(data);
};

Subform.prototype.posted = function(json, form) {

    if (json.message) {
        alert(json.message);
    }

    if (json.status === 0) {
        this.close(document.getElementById(this.containerID));
    }
    else if (json.status === 45) {
        if (json.arguments && json.arguments.polling_id) {
            this.pollingTimer = setTimeout(this.polling, this.startPollingDelay, json.arguments.polling_address + "&polling_id=" + json.arguments.polling_id);
        }
        return;
    }

    var i, max;
    switch (json.response.type) {
        case 'callback':
            var args = json.arguments ? json.arguments : [];
            if (json.response.arguments) {
                args = json.response.arguments;
            }
            TM.apply(json.response.source, args);
            break;
        case 'close':
            return;
        case 'redirect':
            location.href = json.response.source;
            break;
        case 'reload':
            location.reload();
            break;
        default:
            if (json.response.source) {
                var template = document.createElement('template');
                if (template.content) {
                    template.innerHTML = json.response.source;
                    document.head.appendChild(template);
                    var nodeList = template.content.childNodes;
                    for (i = 0, max = nodeList.length; i < max; i++) {
                        var element = nodeList[i];
                        if (element.nodeType != Node.ELEMENT_NODE || !element.id) {
                            continue;
                        }
                        var origin = document.getElementById(element.id);
                        if (origin) {
                            origin.parentNode.replaceChild(element.cloneNode(true), origin);
                        }
                    }
                    document.head.removeChild(template);

                    this.setListenerForms(document.getElementById(this.containerID));
                    this.setListenerButtons();
                }
            }
            else {
                this.initForm(json.response);
            }
            break;
    }

    if (json.finally) {
        this.finally = json.finally;
    }

    var buttons = form.querySelectorAll("button[type=submit], input[type=submit]");
    for (i = 0, max = buttons.length; i < max; i++) {
        buttons[i].disabled = false;
    }
};

Subform.prototype.submit = function(form) {
    var instance = TM.subform;
    if (form.dataset && form.dataset.confirm) {
        if (!confirm(form.dataset.confirm)) {
            return;
        }
    }

    if (form.dataset.viaXHttpRquest && typeof window[form.dataset.viaXHttpRquest] === 'function') {
        window[form.dataset.viaXHttpRquest].apply(instance, [form]);
        return;
    }

    if (form.normal_request && form.normal_request.value === '1') {
        form.submit();
        return;
    }

    TM.xhr.init('POST', form.action, true, function(event){
        if(this.status == 200){
            try {
                var json = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            instance.posted(json, form);
            if (instance.finally) {
                TM.apply(instance.finally.function, instance.finally.arguments);
                instance.finally = undefined;
            }
        } else {
            // TODO: add error handling
            console.log(this.responseText);
            alert('System Error!');

            var buttons = form.querySelectorAll("button[type=submit], input[type=submit]");
            for (i = 0, max = buttons.length; i < max; i++) {
                buttons[i].disabled = false;
            }
        }
    });

    if (form.polling_id && form.polling_id.value !== '') {
        this.pollingInterval = parseInt(form.polling_id.dataset.interval);
    }

    var buttons = form.querySelectorAll("button[type=submit], input[type=submit]");
    for (i = 0, max = buttons.length; i < max; i++) {
        buttons[i].disabled = true;
    }

    var formData = new FormData(form);
    formData.append('returntype', 'json');
    TM.xhr.send(formData);
};

Subform.prototype.download = function(mode) {
    location.href = mode;
};

Subform.prototype.progress = function(args) {
    var progressbar = document.getElementById('progressbar');
    if (!progressbar) {
        var template = document.getElementById('polling-parts');
        if (template.content) {
            progressbar = template.content.getElementById('progressbar');
            progressbar = document.body.appendChild(progressbar.cloneNode(true));
        }
        var bottom = document.getElementById('cancel-subform');
        bottom.parentNode.insertBefore(progressbar, bottom);
    }
    progressbar.querySelector('.description').innerHTML = args;

    var counter = args.split(' / ');
    var percent = Math.round(parseInt(counter[0]) / parseInt(counter[1]) * 100);
    progressbar.querySelector('.bar').style.width = percent + '%';
};

Subform.prototype.ended = function(args) {
    console.log(args);
};

Subform.prototype.showLog = function(polling_id, mode) {
    var template = document.getElementById('polling-parts');
    if (template.content) {
        var panel = template.content.getElementById('log-block');
        panel = document.body.appendChild(panel.cloneNode(true));

        var iframe = panel.querySelector('iframe');
        iframe.src = '?mode=' + mode + '&polling_id=' + polling_id;

        var button = panel.querySelector('button');
        button.addEventListener('click', TM.subform.hideLog, false);
    }
};

Subform.prototype.hideLog = function() {
    var instance = TM.subform;
    var panel = document.getElementById('log-block');
    if (panel) {
        panel.parentNode.removeChild(panel);
        instance.close(document.getElementById(instance.containerID));
    }
};

Subform.prototype.polling = function(url) {
    var instance = TM.subform;
    var xhr = new TM_XMLHttpRequest();
    xhr.init('GET', url, true, function(event){
        if(this.status == 200){
            try {
                var json = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                return;
            }
            clearTimeout(instance.pollingTimer);
            if (json.status == 'running') {
                instance.pollingTimer = setTimeout(instance.polling, instance.pollingInterval, url);
            }

            if (json.finally) {
                instance.finally = json.finally;
            }
            TM.apply(json.response.source, json.arguments);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
            alert('System Error!');
        }
    });
    xhr.send(null);
};

Subform.prototype.listener = function(event) {
    var instance = TM.subform;
    var caller = event.target;
    switch (event.type) {
        case 'click':
            event.preventDefault();
            event.stopPropagation();

            var container = document.getElementById(instance.containerID);

            if (!container) {
                instance.open(caller);
            }
            else if (caller.id === instance.cancelButtonID || caller.classList.contains(instance.cancelButtonClass)) {
                var skipClose = (caller.dataset.callbackSubform && typeof window[caller.dataset.callbackSubform] === 'function')
                    ? window[caller.dataset.callbackSubform].apply(instance, [caller, container]) : false;
                if (skipClose === false) {
                    instance.close(container);
                }
            }
            break;
        case 'submit':
            event.preventDefault();
            event.stopPropagation();

            instance.submit(caller);
            break;
        case 'transitionend':
            if (caller.id === instance.containerID) {
                instance.endOpenTransition(caller);
            }
            break;
    }
};

Subform.prototype.init = function(event) {
    if (this.inited) {
        return;
    }

    var elements = document.body.getElementsByClassName('subform-opener');
    for (i = 0, max = elements.length; i < max; i++) {
        elements[i].addEventListener('click', this.listener, false);
    }

    this.inited = true;
};

TM.subform = new Subform();
