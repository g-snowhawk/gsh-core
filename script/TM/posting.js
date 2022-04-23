/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_Posting = function() {
    TM.initModule(this.init, this, 'interactive');
};

TM_Posting.prototype.post = function(element) {
    if (element.dataset && element.dataset.confirm) {
        if (!confirm(element.dataset.confirm)) {
            return;
        }
    }

    var action = element.protocol + '//';
    action += element.host;
    action += element.pathname;

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

    var stub = document.querySelector('[name=stub]');
    input.name = 'stub';
    input.value = stub.value;
    form.appendChild(input.cloneNode());

    for (var key in data) {
        input.name = key;
        input.value = data[key].toString();
        form.appendChild(input.cloneNode());
    }

    form.submit();

    document.body.removeChild(form);
};

TM_Posting.prototype.listener = function(event) {
    var instance = TM.posting;
    var element = event.currentTarget;
    switch (event.type) {
        case 'click':
            event.preventDefault();
            instance.post(element);
            break;
    }
};

TM_Posting.prototype.init = function() {
    var i;
    var elements = document.getElementsByClassName('post-request');
    for (i = 0; i < elements.length; i++) {
        elements[i].addEventListener('click', this.listener);
    }
};

TM.posting = new TM_Posting();
