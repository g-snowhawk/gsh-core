/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_FunctionKey = function() {
    TM.initModule(this.init, this, 'interactive');
};

TM_FunctionKey.prototype.async = function(event) {
    var stub = document.querySelector('[name=stub]');
    if (!stub) {
        return;
    }

    event.preventDefault();

    var element = event.currentTarget;

    var action = element.protocol + '//'
               + element.host
               + element.pathname;

    var data = new FormData();
    data.append('stub', stub.value);
    data.append('script_referer', location.href);

    var query = element.search.substr(1);
    var queries = query.split('&');
    for (var i = 0; i < queries.length; i++) {
        var pair = queries[i].split('=');
        data.append(decodeURI(pair[0]), decodeURI(pair[1]));
    }

    TM.xhr.init('POST', action, true, function(event){
        document.documentElement.innerHTML = this.responseText;
    });
    TM.xhr.header('X-Requested-With', 'XHRFunction');
    TM.xhr.send(data);
};

TM_FunctionKey.prototype.sync = function(event) {
    var stub = document.querySelector('[name=stub]');
    if (!stub) {
        return;
    }

    event.preventDefault();

    var element = event.currentTarget;

    var action = element.protocol + '//'
               + element.host
               + element.pathname;

    var query = element.search.substr(1);
    var queries = query.split('&');
    var data = {};
    for (var i = 0; i < queries.length; i++) {
        var pair = queries[i].split('=');
        data[pair[0]] = pair[1];
    }

    var form = document.body.appendChild(document.createElement('form'));
    form.style.display = 'none';
    form.method = 'post';
    form.enctype = 'application/x-www-form-urlencoded';
    form.action = action;

    var input = document.createElement('input');
    input.type = 'hidden';

    input.name = 'stub';
    input.value = stub.value;
    form.appendChild(input.cloneNode());

    input.name = 'script_referer';
    input.value = location.href;
    form.appendChild(input.cloneNode());

    input.name = 'convert_request_method';
    input.value = 'get';
    form.appendChild(input.cloneNode());

    for (var key in data) {
        input.name = key;
        input.value = data[key].toString();
        form.appendChild(input.cloneNode());
    }

    form.submit();

    document.body.removeChild(form);
};

TM_FunctionKey.prototype.link = function(element) {
    TM.setCookie('script_referer', location.href);
};

TM_FunctionKey.prototype.listener = function(event) {
    var instance = TM.functionKey;
    var element = event.currentTarget;
    switch (event.type) {
        case 'click':
            if (element.classList.contains('tm-with-referer')) {
                instance.link(element);
            }
            else {
                instance.sync(event);
            }
            break;
    }
};

TM_FunctionKey.prototype.init = function() {

    var elements = document.querySelectorAll('a.tm-function-key');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', this.listener);
    }

    var elements = document.querySelectorAll('a.tm-with-referer');
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', this.listener);
    }
};

TM.functionKey = new TM_FunctionKey();
