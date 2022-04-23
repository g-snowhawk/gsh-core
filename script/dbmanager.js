/**
 * This file is part of G.Snowhawk Application.
 *
 * Copyright (c)2022 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * http://your.domain/licenses/mit-license
 */

switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', dbManagerInit)
        break;
    case 'interactive':
    case 'complete':
        dbManagerInit();
        break;
}

let dbManagerExportSelectAll = undefined;
function dbManagerInit(event) {
    const buttons = document.getElementsByName('mode');
    buttons.forEach(button => {
        button.disabled = true;
    });

    // Init SQL
    const sqlFile = document.getElementById('sqlfile');
    sqlFile.addEventListener('change', dbManagerInputSQL);

    const sqlInput = document.getElementById('sql');
    sqlInput.addEventListener('keyup', dbManagerInputSQL);
    dbManagerInputSQL();

    // Init table selector
    dbManagerExportSelectAll = document.querySelector('input[name=select_all]');
    dbManagerExportSelectAll.addEventListener('click', dbManagerExportSelectAllClicked);
    let tables = document.getElementsByName('tables[]');
    let dummy = undefined;
    tables.forEach(table => {
        table.addEventListener('click', dbManagerExportSelectTableClicked);
        if (!dummy) {
            dummy = table;
        }
    });
    dbManagerExportSelectTableClicked({ target: dummy });

    tables = document.getElementsByName('normalizes[]');
    tables.forEach(table => {
        table.addEventListener('click', dbManagerExportSelectNormalizesClicked);
        if (!dummy) {
            dummy = table;
        }
    });
    dbManagerExportSelectNormalizesClicked({ target: dummy });
}

function dbManagerExportSelectAllClicked(event) {
    const element = event.target;
    if (!element.checked && !event.noreturn) return;

    let dummy = undefined;
    const tables = document.getElementsByName('tables[]');
    tables.forEach(table => {
        table.checked = element.checked;
        if (!dummy) {
            dummy = table;
        }
    });

    dbManagerExportSelectTableClicked({ target: dummy });
}

function dbManagerExportSelectTableClicked(event) {
    const element = event.target;
    const tables = document.getElementsByName(element.name);
    let checkedCount = 0;
    tables.forEach(table => {
        if (table.checked) {
            checkedCount++;
        }
    });

    const options = document.getElementsByClassName('export-options');
    options.forEach(option => {
        option.disabled = (checkedCount < 1);
        if (option.type === 'submit') {
            if (option.disabled) {
                option.removeEventListener('click', dbManagerConfirmationToSubmit);
            } else {
                option.addEventListener('click', dbManagerConfirmationToSubmit);
            }
        }
    });

    dbManagerExportSelectAll.checked = (tables.length === checkedCount);
}

function dbManagerExportSelectNormalizesClicked(event) {
    const element = event.target;
    const tables = document.getElementsByName(element.name);
    let checkedCount = 0;
    tables.forEach(table => {
        if (table.checked) {
            checkedCount++;
        }
    });

    const options = document.getElementsByClassName('normalize-options');
    options.forEach(option => {
        option.disabled = (checkedCount < 1);
        if (option.type === 'submit') {
            if (option.disabled) {
                option.removeEventListener('click', dbManagerConfirmationToSubmit);
            } else {
                option.addEventListener('click', dbManagerConfirmationToSubmit);
            }
        }
    });
}

function dbManagerInputSQL(event) {

    let inputCount = 0;
    const sqlInput = document.getElementById('sql');
    if (sqlInput.value !== '') {
        inputCount++;
    }
    const sqlFile = document.getElementById('sqlfile');
    if (sqlFile.value !== '') {
        inputCount++;
    }

    const button = document.querySelector('button[value="system.receive:exec-sql"]');
    button.disabled = (inputCount < 1);

    if (button.disabled) {
        button.removeEventListener('click', dbManagerConfirmationToSubmit);
    } else {
        button.addEventListener('click', dbManagerConfirmationToSubmit);
    }
}

function dbManagerResetForm(form) {
    form.reset();
    dbManagerExportSelectAllClicked({ target: dbManagerExportSelectAll, noreturn: 1 });

    const tables = document.getElementsByName('normalizes[]');
    if (tables[0]) {
        dbManagerExportSelectNormalizesClicked({ target: tables[0], noreturn: 1 })
    }
}

function dbManagerConfirmationToSubmit(event) {
    event.preventDefault();
    const element = event.target;
    if (!confirm(decodeURIComponent(element.dataset.confirm))) return;

    setProgressScreen();

    const form = element.form;
    const data = new FormData(form);
    data.append('mode', element.value);

    fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data
    }).then((response) => {
        if (response.status >= 500) {
            throw new Error("Internal Server Error");
        } else if (response.status >= 400) {
            throw new Error("Server Error");
        }

        if (response.ok) {
            const contentType = response.headers.get("content-type");
            const contentDisposition = response.headers.get("content-disposition") || '';
            if (contentType.match(/^application\/json/i)) {
                response.json().then((json) => {
                    if (json.status !== 0) {
                        if (json.description) {
                            console.warn(json.description);
                        }
                        throw new Error(json.message);
                    }
                    setProgressScreen(true);
                    dbManagerResetForm(form);
                    alert (json.message);
                }).catch(error => {
                    alert(error.message);
                });
            } else if (contentDisposition.match(/attachment/i)) {
                response.blob().then((blob) => {
                    setProgressScreen(true);
                    const match = contentDisposition.match(/filename="(.+?)"/i);
                    const fileName = match[1] || 'dump.sql';
                    const anchor = document.createElement('a');
                    anchor.href = window.URL.createObjectURL(blob);
                    if (fileName) {
                        anchor.download = fileName;
                    }
                    anchor.click();
                    delete anchor;
                    dbManagerResetForm(form);
                }).catch(error => {
                    alert(error.message);
                });
            } else {
                response.text().then((text) => {
                    console.warn(text);
                });
                throw new Error("Unexpected response");
            }
        } else {
            throw new Error("Server Error");
        }
    }).catch((error) => {
        alert(error.message);
    }).then(() => {
        setProgressScreen(true);
    });
}
