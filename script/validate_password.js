switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', validatePasswordInit)
        break;
    case 'interactive':
    case 'complete':
        validatePasswordInit();
        break;
}

function validatePasswordInit(event) {
    const passwords = document.querySelectorAll('.validate-password');
    passwords.forEach((element) => {
        if (event.type === 'reinit') {
            element.classList.remove('ok', 'ng');
            if (event.detail.reinit) {
                element.value = '';
                element.removeEventListener('input', validatePasswordStrong);
                element.removeEventListener('keydown', validatePasswordValidation);
                element.removeEventListener('focusout', validatePasswordValidation);
                element.removeEventListener('paste', validatePasswordValidation);
            }
        }
        element.addEventListener('input', validatePasswordStrong);
        element.addEventListener('keydown', validatePasswordValidation);
        element.addEventListener('focusout', validatePasswordValidation);
        element.addEventListener('paste', validatePasswordValidation);
        if (element.dataset.chkTypo) {
            const pair = element.form.querySelector('input[name=' + CSS.escape(element.dataset.chkTypo) + ']');
            if (pair) {
                if (event.type === 'reinit' && event.detail.reinit) {
                    pair.value = '';
                    pair.removeEventListener('input', validatePasswordTypo);
                    pair.removeEventListener('keydown', validatePasswordValidation);
                    pair.removeEventListener('focusout', validatePasswordValidation);
                    pair.removeEventListener('paste', validatePasswordValidation);
                }
                pair.addEventListener('input', validatePasswordTypo);
                pair.addEventListener('keydown', validatePasswordValidation);
                pair.addEventListener('focusout', validatePasswordValidation);
                pair.addEventListener('paste', validatePasswordValidation);
                pair.dataset.pair = element.name || element.id;
                validatePasswordTypo({ target : pair });
                if (!element.classList.contains('ok')) {
                    pair.disabled = true;
                }
            }
        }

        if (element.dataset.messageContainer) {
            const container = document.querySelectorAll(element.dataset.messageContainer);
            let validate = undefined;
            container.forEach((elm) => {
                if (elm.classList.contains('show-error-always')) {
                    if (validate === undefined) {
                        validate = validatePasswordStrength(element.form, element.value);
                    }
                    elm.textContent = validate.message;
                }
                if (event.type === 'reinit' && event.detail.reinit) {
                    elm.classList.remove('ok');
                }
            });
        }

        let x;
        if (element.form.pw_maxlength && !isNaN((x = parseInt(element.form.pw_maxlength.value)))) {
            element.maxLength = x;
        }

        if (element.dataset.unveil) {
            const unveil = document.getElementById(element.dataset.unveil);
            if (unveil) {
                if (event.type === 'reinit' && event.detail.reinit) {
                    unveil.removeEventListener('click', validatePasswordUnveil);
                }
                unveil.addEventListener('click', validatePasswordUnveil);
                unveil.dataset.target = element.name;
                unveil.dispatchEvent(new CustomEvent('click', { detail: { init: true } }));
            }
        }
    });
}

function validatePasswordReinit(event) {
    validatePasswordInit(new CustomEvent('reinit', { detail: { reinit: true } }));
}

function validatePasswordStrong(event) {
    const element = event.target;
    const container = document.querySelectorAll(element.dataset.messageContainer);
    const pair = element.form.querySelector('input[name=' + element.dataset.chkTypo + ']');
    if (element.value === '') {
        element.classList.remove('ok', 'ng');
        if (pair) {
            pair.disabled = true;
        }
    } else {
        const validate = validatePasswordStrength(element.form, element.value);
        if (validate.ok) {
            element.classList.add('ok');
            element.classList.remove('ng');
            container.forEach((elm) => {
                if (!elm.classList.contains('show-error-always')) {
                    elm.textContent = '';
                }
                elm.classList.add('ok');
            });
        } else {
            element.classList.remove('ok');
            element.classList.add('ng');
            container.forEach((elm) => {
                elm.textContent = validate.message;
                elm.classList.remove('ok');
            });
        }

        const valid = element.form.pw_validate;
        if (valid) {
            valid.value = (validate.ok) ? 'valid' : 'invalid';
        }
        if (pair) {
            pair.disabled = !validate.ok;
        }
    }
}

function validatePasswordTypo(event) {
    const element = event.target;
    const pair = element.form.querySelector('input[name=' + element.dataset.pair + ']');
    if (pair) {
        const valid = pair.classList.contains('ok');
        const submits = element.form.querySelectorAll('*[type=submit]');
        let disable = (pair.dataset.submitLock !== 'no');
        if (element.value === '') {
            element.classList.remove('ok','ng','bad');
        } else {
            if (valid && pair.value === element.value) {
                element.classList.add('ok');
                element.classList.remove('ng','bad');
                disable = false;
            } else {
                element.classList.remove('ok','ng','bad');
                const clname = (pair.value.length > element.value.length) ? 'ng' : 'bad';
                element.classList.add(clname);
            }
        }

        if (!valid) {
            disable = true;
        }

        if (element.dataset.sendLock === 'no') {
            disable = false;
        }

        submits.forEach(function(submit) {
            submit.disabled = disable;
        });
    }
}

function validatePasswordStrength(form, password) {
    let m = 0;
    let l = 0;
    let u = 0;
    let n = 0;
    let c = 0;
    let r = 0;
    let x = 0;
    let column = 8;
    let maxlength = 0;
    let invalid = false;
    if (form['pw_lower'] && !isNaN((l = parseInt(form['pw_lower'].value))) > 0) {
        m = (password.match(/[a-z]/g) || []).length;
        if (m < l) {
            invalid = true;
        }
    }
    if (form['pw_upper'] && !isNaN((u = parseInt(form['pw_upper'].value))) > 0) {
        m = (password.match(/[A-Z]/g) || []).length;
        if (m < u) {
            invalid = true;
        }
    }
    if (form['pw_number'] && !isNaN((n = parseInt(form['pw_number'].value))) > 0) {
        m = (password.match(/[0-9]/g) || []).length;
        if (m < n) {
            invalid = true;
        }
    }
    if (form['pw_char'] && !isNaN((c = parseInt(form['pw_char'].value))) > 0) {
        m = (password.match(/[!-\/:-@[-`{-~]/g) || []).length;
        if (m < c) {
            invalid = true;
        }
    }
    if (form['pw_column'] && !isNaN((m = parseInt(form['pw_column'].value)))) {
        column = m;
    }
    if (password.length < column) {
        invalid = true;
    }
    if (form['pw_maxlength'] && !isNaN((x = parseInt(form['pw_maxlength'].value)))) {
        maxlength = x;
    }
    if (maxlength > 0 && password.length > maxlength) {
        invalid = true;
    }

    if (form['pw_repeat'] && !isNaN((r = parseInt(form['pw_repeat'].value))) > 0) {
        if (password.match('(.)\\1{' + (r - 1) + ',}')) {
            invalid = true;
        }
    }

    let error = '';
    if (invalid) {
        error = (form['pw_format'] || { value: 'Invalid passphrase!' }).value;
        error = error.replace(/%m/, column);
        error = error.replace(/%u/, u);
        error = error.replace(/%l/, l);
        error = error.replace(/%n/, n);
        error = error.replace(/%c/, c);
        error = error.replace(/%r/, r);
        error = error.replace(/%x/, x);
    }

    return { ok: !invalid, message: error };
}

function validatePasswordValidation(event) {
    const input = event.target;
    let invalid = false;
    let validity = '';

    const esc = input.required;
    if (esc) {
        input.required = false;
    }
    input.setCustomValidity('');

    if (event.type === 'paste') {
        const pastedText = event.clipboardData.getData('text');
        if (pastedText.length > input.maxLength) {
            invalid = true;
            validity = 'Too long'.translate();
        }
    } else if (event instanceof KeyboardEvent) {
         if (event.key === ' ') {
            validity = 'Space is not allowed'.translate();
            invalid = true;
        }
    }

    if (invalid) {
        event.preventDefault();
    }

    if (validity !== '') {
        input.setCustomValidity(validity);
        input.reportValidity();
    }

    if (esc) {
        setTimeout((elm) => {
            elm.required = true;
        }, 0, input);
    }
}

function validatePasswordUnveil(event) {
    const button = event.target;
    const input = button.form.querySelector('input[name="' + CSS.escape(button.dataset.target) + '"]');
    if (input.type !== 'password' || (event.detail && event.detail.init)) {
        input.type = 'password';
        button.textContent = button.dataset.unveilText;
    } else {
        input.type = 'text';
        button.textContent = button.dataset.veilText;
    }

    if (input.dataset.chkTypo) {
        const pair = input.form.querySelector('input[name=' + CSS.escape(input.dataset.chkTypo) + ']');
        if (pair) {
            pair.type = input.type;
        }
    }
}
