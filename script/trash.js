/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2020 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */
'use strict';

switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', initializeTrashBox)
        break;
    case 'interactive':
    case 'complete':
        initializeTrashBox();
        break;
}

function initializeTrashBox(event) {
    const rewinds = document.getElementsByName('rewind[]');
    Array.prototype.forEach.call(rewinds, element => {
        element.addEventListener('click', rewindTrashItem);
    })

    const anchors = document.querySelectorAll("a[href$=\\:empty-trash]");
    anchors.forEach(element => {
        element.addEventListener('click', emptyTrash);
    })
}

function emptyTrash(event) {
    event.preventDefault();

    const element = event.target;

    const message = element.dataset.confirm ? decodeURI(element.dataset.confirm) : "Empty Trash?";
    if (!confirm(message)) {
        return;
    }

    Array.prototype.forEach.call(document.forms, form => {
        const rewinds = form.querySelectorAll("input[name=rewind\\[\\]]");
        if (rewinds.length > 0) {
            form.submit();
        }
    });
}

function rewindTrashItem(event) {
    event.preventDefault();

    const element = event.target;
    const form = element.form;

    const [mainpackage,subpackage,identifier] = element.value.split(':');

    let data = new FormData();
    data.append('stub', form.stub.value);
    data.append('mode', mainpackage + '.' + subpackage + '.receive:rewind-trash-item');
    data.append('identifier', identifier);

    fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        body: data
    }).then(response => response.json())
    .then((json) => {
        console.log(json);
        if (json.status === 0) {
            location.reload();
        } else {
            alert(json.message);
        }
    })
    .catch(error => console.error(error));
}
