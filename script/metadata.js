/**
 * This file is part of G.Snowhawk Application
 *
 * Copyright (c)2021 Someone
 *
 * This software is released under the MIT License.
 * http://your.domain/licenses/mit-license
 */
switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', tmsMetadataInit)
        break;
    case 'interactive':
    case 'complete':
        tmsMetadataInit();
        break;
}

function tmsMetadataInit(event) {
    const author_date = document.getElementsByClassName('fix-current-timestamp');
    author_date.forEach((element) => {
        element.addEventListener('click', tmsMetadataFixCurrentTimestamp);
    });
}

function tmsMetadataFixCurrentTimestamp(event) {
    event.preventDefault();
    const element = event.currentTarget;
    const input = document.querySelector(element.hash);
    if (input) {
        const now = new Date();
        let dateStr = now.getFullYear()
                    + '-' + ('00' + now.getMonth() + 1).slice(-2)
                    + '-' + ('00' + now.getDate()).slice(-2)
                    + 'T' + ('00' + now.getHours()).slice(-2)
                    + ':' + ('00' + now.getMinutes()).slice(-2);
        input.value = dateStr;
    }
}
