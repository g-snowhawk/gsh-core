/**
 * Download CSV on console
 *
 * @copyright (c)2015-2019 PlusFive. (https://www.plus-5.com/)
 * @version 1.1.0
 */

switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', downloadFileInit)
        break;
    case 'interactive':
    case 'complete':
        downloadFileInit();
        break;
}

let downloadFileName = undefined;
function downloadFileInit(event) {
    document.getElementsByClassName('downloader').forEach((element) => {
        let action = undefined;
        switch (element.nodeName) {
            case 'A':
            case 'BUTTON':
                action = 'click';
                break;
            case 'FORM':
                action = 'submit';
                break;
            case 'INPUT':
                if (element.type === 'submit' || element.type === 'button' || element.type === 'reset') {
                    action = 'click';
                }
                break;
        }

        if (action) {
            element.addEventListener(action, downloadFileRequest);
        }
    });
}

function downloadFileRequest(event) {
    event.preventDefault();
    const element = event.currentTarget;

    setProgressScreen();

    let context = {
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
    };

    let url = undefined;
    let method = 'POST';
    if (element.nodeName === 'A') {
        method = 'GET';
        url = element.href;
    } else {
        const form = (element.nodeName === 'FORM') ? element : element.findParent('form');
        if (form) {
            method = form.method;
            url = element.action;
            context.body = new FormData(form);
        }
    }

    context.method = method;

    fetch (url, context).then((response) => {
        if (response.status >= 400) {
            throw Error("HTTP Error (" + response.status + ")");
        }
        if (response.ok) {
            let contentType = response.headers.get("content-type");
            if (contentType.match(/^application\/json/)) {
                return response.json();
            } else if (contentType.match(/^text\/csv/)) {
                const match = response.headers.get("content-disposition").match(/filename="?(.+?)"/i);
                if (match[1]) {
                    downloadFileName = match[1];
                }
                return response.blob();
            }
            throw new Error("Unexpected response");
        } else {
            throw new Error("Server Error");
        }
    }).then((result) => {
        if (result instanceof Blob) {
            const anchor = document.createElement('a');
            anchor.href = window.URL.createObjectURL(result);
            if (downloadFileName) {
                anchor.download = downloadFileName;
            }
            anchor.click();
        } else {
            if (result.status !== '0') {
                alert(result.message);
                console.warn(result.message);
            }
        }
    }).catch((error) => {
        console.error(error);
    }).then(() => {
        //
        setProgressScreen(true);
    });
}
