/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017-2020 PlusFive (https://www.plus-5.com)
 * @version 1.0.1
 */
const markdownEditorActiveImageClassName = 'editor-image-active';

let markdownEditorCurrentArea;
let markdownEditorLeftString;
let markdownEditorRightString;
let markdownEditorSelectedString;
let markdownEditorClickCount = 0;
let uploadMaxFileSize = 0;
let postMaxSize = 0;

switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', markdownEditorInit)
        break;
    case 'interactive':
    case 'complete':
        markdownEditorInit();
        break;
}

function markdownEditorInit(event) {
    const buttons = document.querySelectorAll('a[data-insert]');
    buttons.forEach((button) => {
        button.addEventListener('click', markdownEditorOnClick);
    });
}

function markdownEditorOnClick(event) {
    event.preventDefault();
    const trigger = event.target;

    if (trigger.name === 'editor-insert') {
        return markdownEdirotInsertCode(trigger);
    } else if (trigger.name === 'editor-cancel') {
        return markdownEditorClearMask(event);
    }

    const id = trigger.hash;
    markdownEditorCurrentArea = document.querySelector(trigger.hash);
    if (!markdownEditorCurrentArea) {
        return;
    }
    markdownEditorCurrentArea.focus();
    const value = markdownEditorCurrentArea.value;
    const start = markdownEditorCurrentArea.selectionStart;
    const end = markdownEditorCurrentArea.selectionEnd;

    markdownEditorLeftString = value.slice(0, start);
    markdownEditorRightString = value.slice(end);
    markdownEditorSelectedString = (start != end) ? value.slice(start, end) : '';

    let callback = undefined;
    switch(trigger.dataset.insert) {
        case 'link':
            callback = markdownEditorLinkForm;
            break;
        case 'image':
            callback = markdownEditorImageForm;
            break;
    }
    if (callback) {
        markdownEditorCreateForm(
            markdownEditorCreateMask(),
            trigger,
            trigger.dataset.insert,
            callback
        );
    }
}

function markdownEditorCreateMask() {
    const mask = document.body.appendChild(document.createElement('div'));
    mask.id = 'mask';

    return mask;
}

function markdownEditorClearMask() {
    const mask = document.getElementById('mask');
    if (mask) {
        mask.parentNode.removeChild(mask);
    }
}

function markdownEditorCreateForm(mask, element, type, callback) {
    fetch(TM_Common.__DIR__ + '/TM/editor/' + type + '.html', {
        method: 'GET',
    })
    .then(response => {
        if (response.ok) {
            return response.text();
        }
    })
    .then(text => {
        mask.innerHTML = text;
        if (typeof callback === 'function') {
            callback.call(this, mask, element);
        }
    })
    .catch(error => {
        console.error(error);
    });
}

function markdownEditorLinkForm(mask, element) {
    mask.addEventListener('click', markdownEditorClearMask);

    const elements = mask.getElementsByTagName('input');
    for(let i = 0; i < elements.length; i++) {
        const element = elements[i];
        if(element.type === 'button') {
            element.addEventListener('click', markdownEditorOnClick);
            continue;
        }
        switch(element.name) {
            case 'editor-url':
                element.focus();
                break;
            case 'editor-text':
                element.value = markdownEditorSelectedString;
                break;
        }
    }

    const form = mask.getElementsByTagName('form')[0];
    form.action = location.pathname;
    form.style.height = form.clientHeight + 'px';
    form.className = 'link';
    form.addEventListener('click', markdownEditorCancelBubble);
};

function markdownEditorCancelBubble(event) {
    event.stopPropagation();
}

function markdownEdirotInsertCode(element) {
    let url, text, imark;
    switch(element.dataset.type) {
        case 'image':
            const span = document.getElementsByClassName(markdownEditorActiveImageClassName);
            if(span.length == 0) {
                alert('画像が選択されていません');
                return;
            }
            var img = span[0].getElementsByTagName('img');
            url = img[0].src;
            text = img[0].alt;
            imark = '!';
            break;
        case 'link':
            var form = element.form;
            url = form['editor-url'].value;
            text = form['editor-text'].value;
            imark = '';
            break;
    }

    if(url !== '') {
        const mStr = (text === '') ? markdownEditorSelectedString : text;
        markdownEditorCurrentArea.value = markdownEditorLeftString
            + imark + '[' + mStr + '](' + url + ')' + markdownEditorRightString;
    }
    markdownEditorLeftString = '';
    markdownEditorRightString = '';
    markdownEditorSelectedString = '';
    markdownEditorCurrentArea = undefined;
    markdownEditorClearMask();
}

function markdownEditorImageForm(mask, element) {
    const stub = document.querySelector('input[name=stub]');
    if (!stub) {
        return;
    }

    const anchors = document.querySelectorAll('#image-viewer .footer a');
    anchors.forEach((anchor) => {
        anchor.addEventListener('click', markdownEditorChangeMode);
        anchor.dataset.defaultLabel = anchor.innerHTML;
    });

    mask.addEventListener('click', markdownEditorClearMask);

    const form = mask.getElementsByTagName('form')[0];
    form.action = location.pathname;
    form.stub.value = stub.value;
    form.dataset.upload = element.dataset.upload;
    form.dataset.delete = element.dataset.delete;
    form.dataset.list = element.dataset.list;
    form.dataset.confirm = element.dataset.confirm;
    form.addEventListener('click', markdownEditorCancelBubble);

    const elements = mask.getElementsByTagName('input');
    for (let i = 0; i < elements.length; i++) {
        const element = elements[i];
        if (element.type === 'file') {
            element.addEventListener('change', markdownEditorImageSelected);
        } else if (element.type === 'button') {
            element.addEventListener('click', markdownEditorOnClick);
        }
    }

    fetch(form.action + '?mode=' + form.dataset.list, {
        method: 'GET',
    })
    .then(response => {
        if (response.ok) {
            let contentType = response.headers.get("content-type");
            if (contentType.match(/^application\/json/)) {
                return response.json();
            }
            throw new Error("Unexpected response");
        } else {
            throw new Error("Server Error");
        }
    })
    .then(json => {
        if (json.status !== 0) {
            throw new Error(json.message);
        }
        uploadMaxFileSize = json.upload_max_filesize;
        postMaxSize = json.post_max_size;
        markdownEditorCreateThumbnail(json.list);
    })
    .catch(error => {
        console.error(error);
        alert(error.message);
    });
}

function markdownEditorCreateThumbnail(json) {
    const origin = document.getElementById('image-addnew');
    for(let i = 0; i < json.length; i++) {
        const item = json[i];
        const id = 'thumbnail-' + item.id;
        let thumbnail = document.getElementById(id);
        if(thumbnail) {
            const img = thumbnail.getElementsByTagName('img')[0];
            img.src = item.data;
            img.draggable = 'false';
            continue;
        }

        thumbnail = origin.cloneNode(true);
        thumbnail.id = 'thumbnail-' + item.id;

        const label = thumbnail.getElementsByTagName('label')[0];
        const dummy = label.parentNode.insertBefore(document.createElement('span'), label);
        dummy.innerHTML = label.innerHTML;
        dummy.className = 'selected';
        dummy.addEventListener('mousedown', markdownEditorClickThumbnail);
        label.parentNode.removeChild(label);

        const check = dummy.appendChild(document.createElement('span'));
        check.className = 'checked';
        check.addEventListener('click', markdownEditorSelectThumbnail);

        const span = thumbnail.getElementsByClassName('thumbnail')[0];
        const img = span.appendChild(document.createElement('img'));
        img.src = item.data;

        const input = thumbnail.getElementsByTagName('input')[0];
        input.name = 'file[id_' + item.id + ']';
        input.addEventListener('change', markdownEditorImageSelected);

        const anchor = thumbnail.appendChild(document.createElement('a'));
        anchor.href = '#delete:' + item.id;
        anchor.className = 'mark';
        anchor.addEventListener('click', markdownEditorDeleteImage);

        origin.parentNode.insertBefore(thumbnail, origin);
    }
}

function markdownEditorImageSelected(event) {
    const element = event.currentTarget;
    if (element.value === '') {
        event.preventDefault();
        return;
    }
    const form = element.form;
    let data = new FormData(form);
    data.append('mode', form.dataset.upload);

    /*
     * Fix to Safari
     *
     * delete empty file selector
     */
    let empties = [];
    for (item of data) {
        if (typeof(item[1]) === 'object') {
            if (item[0] !== element.name) {
                empties.push(item[0]);
            }

            if (item[1].size) {
                if (uploadMaxFileSize < item[1].size) {
                    alert('File size is too large');
                    return;
                }
            }
        }
    }
    for (item of empties) data.delete(item);

    fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        header: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data
    })
    .then(response => {
        if (response.ok) {
            let contentType = response.headers.get("content-type");
            if (contentType.match(/^application\/json/)) {
                return response.json();
            }
            throw new Error("Unexpected response");
        } else {
            throw new Error("Server Error");
        }
    })
    .then(json => {
        if (json.status !== 0) {
            throw new Error(json.message);
        }
        markdownEditorCreateThumbnail(json.list);
    })
    .catch(error => {
        console.error(error);
        alert(error.message);
    });
}

function markdownEditorDeleteImage(event) {
    const element = event.target;
    const form = element.findParent('form');

    if (confirm(form.dataset.confirm)) {
        var hash = element.hash;
        var id = hash.substr(hash.indexOf(':') + 1);

        var data = new FormData();
        data.append('mode', form.dataset.delete);
        data.append('id', id);
        data.append('stub', form.stub.value);

        fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            header: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data
        })
        .then(response => {
            if (response.ok) {
                let contentType = response.headers.get("content-type");
                if (contentType.match(/^application\/json/)) {
                    return response.json();
                }
                throw new Error("Unexpected response");
            } else {
                throw new Error("Server Error");
            }
        })
        .then(json => {
            if (json.status !== 0) {
                throw new Error(json.message);
            }
            const id = 'thumbnail-' + json.id;
            const element = document.getElementById(id);
            if (element) {
                element.parentNode.removeChild(element);
            }
        })
        .catch(error => {
            console.error(error);
            alert(error.message);
        });
    }
}

function markdownEditorClickThumbnail(event) {
    event.preventDefault();

    if(markdownEditorClickCount !== 1) {
        markdownEditorClickCount = 1;
        setTimeout(function() {
            markdownEditorClickCount = 0;
        }, 300);
    } else {
        markdownEditorClickCount = 2;
        return markdownEditorDoubleClickThumbnail(event.currentTarget);
    }
}

function markdownEditorSelectThumbnail(event) {
    const element = event.target;
    const parent = element.findParent('.selected');
    const selected = document.getElementsByClassName(markdownEditorActiveImageClassName);
    let foundSelf = false;
    for (let i = 0; i < selected.length; i++) {
        const select = selected[i];
        select.classList.remove(markdownEditorActiveImageClassName);
        if (select === parent) {
            foundSelf = true;
        }
    }
    if (foundSelf === false && parent) {
        parent.classList.add(markdownEditorActiveImageClassName);
    }
}

function markdownEditorDoubleClickThumbnail(element) {
    const input = element.getElementsByTagName('input')[0];
    const event = document.createEvent('MouseEvents');
    event.initMouseEvent('click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    input.dispatchEvent(event);
}

function markdownEditorChangeMode(event) {
    const element = event.target;
    let prev = element.parentNode.previousSibling;
    while (prev.nodeType !== Node.ELEMENT_NODE) {
        prev = prev.previousSibling;
    }

    const modes = {
        select: 'select-mode',
        remove: 'edit-mode',
    };

    const key = element.dataset.mode;
    //if (element.classList.contains('select')) {
    //    key = 'select';
    //} else if (element.classList.contains('remove')) {
    //    key = 'remove';
    //}

    prev.classList.toggle(modes[key]);
    for (let name in modes) {
        if (name !== key) {
            prev.classList.remove(modes[name]);
            const anchor = element.parentNode.querySelector('a[data-mode=' + name + ']');
            if (anchor) {
                anchor.innerHTML = anchor.dataset.defaultLabel;
            }
        }
    }
    element.innerHTML = (prev.classList.contains(modes[key]))
        ? element.dataset.complete : element.dataset.defaultLabel;
}
