/**
 * This file is part of G.Snowhawk Application.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function TM_Datetime() {
    this.initFunction = undefined;
    this.callback = undefined;
    this.timer = undefined;
    this.clearPopupLock = false;
    this.calendarMinDate = undefined;
    this.calendarMaxDate = undefined;
    this.calendarWeekDays = undefined;
    this.calendarHeaders = ['S','M','T','W','T','F','S'];
}

TM_Datetime.prototype.setCallback = function(func) {
    this.callback = func;
};

TM_Datetime.prototype.setInitFunction = function(func) {
    this.initFunction = func;
};

TM_Datetime.prototype.refreshCalendar = function(element, year, month) {
    var table = this.calendar(year, month);
    element.parentNode.insertBefore(table, element);
    element.parentNode.removeChild(element);
};

TM_Datetime.prototype.setCalendarMinDate = function(date) {
    this.calendarMinDate = date;
};
TM_Datetime.prototype.setCalendarMaxDate = function(date) {
    this.calendarMaxDate = date;
};
TM_Datetime.prototype.setCalendarWeekDays = function(days) {
    this.calendarWeekDays = days;
};
TM_Datetime.prototype.setCalendarHeader = function(headers) {
    this.calendarHeaders = headers;
};

TM_Datetime.prototype.calendar = function(year, month) {
    year = parseInt(year);
    month = parseInt(month);

    var lastDay = new Date(year, month, 0);
    var firstDay = new Date(year, month - 1, 1);

    var table = document.createElement('table');
    table.classList.add('dateTimeCalendar');
    var caption = table.appendChild(document.createElement('caption'));
    var inline = caption.appendChild(document.createElement('div'));

    var title = inline.appendChild(document.createElement('span'));
    title.classList.add('current');
    title.innerHTML = year + '-' + month;

    var prevMonth = month -1;
    var prevYear = year;
    if (prevMonth < 1) {
        prevMonth = 12;
        --prevYear;
    }
    var prev = inline.appendChild(document.createElement('a'));
    prev.classList.add('navi');
    prev.classList.add('prev');
    prev.innerHTML = prevMonth;
    prev.title = '前月';
    prev.href = '#' + prevYear + '-' + prevMonth;
    prev.addEventListener('click', this.bind(this, this.listener), false);

    var now = new Date();
    var today = inline.appendChild(document.createElement('a'));
    today.classList.add('navi');
    today.classList.add('today');
    today.innerHTML = 'Today';
    today.title = '今日';
    today.href = '#' + now.getFullYear() + '-' + (now.getMonth() + 1);
    today.addEventListener('click', this.bind(this, this.listener), false);

    var nextMonth = month + 1;
    var nextYear = year;
    if (nextMonth > 12) {
        nextMonth = 1;
        ++nextYear;
    }
    var next = inline.appendChild(document.createElement('a'));
    next.classList.add('navi');
    next.classList.add('next');
    next.innerHTML = nextMonth;
    next.title = '次月';
    next.href = '#' + nextYear + '-' + nextMonth;
    next.addEventListener('click', this.bind(this, this.listener), false);

    var minDate = 0;
    if (this.calendarMinDate) {
        minDate = new Date(this.calendarMinDate);
    }

    var maxDate = 0;
    if (this.calendarMaxDate) {
        maxDate = new Date(this.calendarMaxDate);
    }

    var tr, td, d, start, last, week = 0, row = 6;

    if (this.calendarHeaders) {
        var thead = table.appendChild(document.createElement('thead'));
        tr = thead.appendChild(document.createElement('tr'));
        for (var i = 0; i < this.calendarHeaders.length; i++) {
            td = tr.appendChild(document.createElement('td'));
            td.innerHTML = this.calendarHeaders[i];
        }
    }

    var tbody = table.appendChild(document.createElement('tbody'));
    var enableWeekly = true;
    for (d = 1, last = lastDay.getDate(); d <= last; d++) {
        const ymd = year + '-' + (('00' + month).slice(-2)) + '-' + (('00' + d).slice(-2));

        if (week % 7 === 0) {
            tr = tbody.appendChild(document.createElement('tr'));
            week = 0;
            --row;
        }

        if (d === 1) {
            start = firstDay.getDay();
            while (start > 0) {
                td = tr.appendChild(document.createElement('td'));
                --start;
                ++week;
            }
        }

        if (this.calendarWeekDays) {
            enableWeekly = (this.calendarWeekDays.indexOf(week+'') != -1);
        }

        td = tr.appendChild(document.createElement('td'));
        if (ymd === now.format('Y-m-d')) {
            td.classList.add('today');
        }
        td.innerHTML = d;
        if (this.callback) {
            const time = (td.classList.contains('today') && (minDate.format && ymd === minDate.format('Y-m-d')))
                ? minDate.format('H:i:s') : '00:00:00';
            var current = new Date(ymd + 'T' + time);

            if (enableWeekly
                && (minDate === 0 || current.getTime() >= minDate.getTime())
                && (maxDate === 0 || current.getTime() <= maxDate.getTime())
            ) {
                td.classList.add('clickable');
                td.addEventListener('click', this.callback, false);
            } else {
                td.classList.add('disable');
            }
        }
        ++week;
        if (d === last) {
            end = 7 - week;
            while (end > 0) {
                td = tr.appendChild(document.createElement('td'));
                --end;
            }
        }
    }

    while (row > 0) {
        tr = tbody.appendChild(document.createElement('tr'));
        for (var i = 0; i < 7; i++) {
            td = tr.appendChild(document.createElement('td'));
        }
        --row;
    }

    if (this.initFunction) {
        this.initFunction.apply(this, [table]);
    }

    return table;
};

TM_Datetime.prototype.clearPopup = function() {
    var popup = document.getElementsByClassName('calendar-ui-popup');
    for (var i = 0; i < popup.length; i++) {
        popup[i].parentNode.removeChild(popup[i]);
    }
};

TM_Datetime.prototype.lockPopup = function() {
    TM.datetime.clearPopupLock = true;
};

TM_Datetime.prototype.unlockPopup = function() {
    TM.datetime.clearPopupLock = false;
};

TM_Datetime.prototype.createPopup = function(element) {
    try {
        var container = element.offsetParent.appendChild(document.createElement('div'));
        container.id = 'datetime-selector-container-' + element.name;
        container.classList.add('calendar-ui-popup');
        container.classList.add('calendar-ui');
        container.addEventListener('mouseover', this.lockPopup);
        container.addEventListener('mouseout', this.unlockPopup);
        container.style.top = (element.offsetTop + element.offsetHeight) + 'px';
        container.style.left = element.offsetLeft + 'px';
        container.opener = element;

        return container;
    } catch(e) {
        return;
    }
};

TM_Datetime.prototype.selectDate = function(event) {
    var element = event.currentTarget;
    var parent = element.parentNode;
    while (parent) {
        if (parent.classList.contains('dateTimeCalendar')) {
            break;
        }
        parent = parent.parentNode;
    }
    var container = parent.parentNode;

    var selected = parent.querySelectorAll('.active');
    for (i = 0; i < selected.length; i++) {
        selected[i].classList.remove('active');
    }
    element.classList.add('active');

    var current = parent.querySelector('.current');
    var unit = current.innerHTML.split('-');
    var year = parseInt(unit[0]);
    var month = parseInt(unit[1]);
    if (month < 10) {
        month = '0' + month;
    }
    var day = parseInt(element.innerHTML);
    if (day < 10) {
        day = '0' + day;
    }

    container.opener.value = year + '-' + month + '-' + day;

    var hour = container.querySelector('[name=datetime_selector_hour]');
    if (hour) {
        var minute = container.querySelector('[name=datetime_selector_minute]');
        container.opener.value = year + '-' + month + '-' + day + ' ' + hour.value + ':' + minute.value;
    }
    else {
        container.parentNode.removeChild(container);
    }
};

TM_Datetime.prototype.changeTime = function(event) {
    var element = event.currentTarget;
    var container = element.parentNode;
    while (container) {
        if (container.opener) {
            break;
        }
        container = container.parentNode;
    }

    var current = container.querySelector('.current');
    var unit = current.innerHTML.split('-');
    var year = parseInt(unit[0]);
    var month = parseInt(unit[1]);
    if (month < 10) {
        month = '0' + month;
    }
    var date = container.querySelector('.active');
    var day = parseInt(date.innerHTML);
    if (day < 10) {
        day = '0' + day;
    }
    var hour = container.querySelector('[name=datetime_selector_hour]');
    var minute = container.querySelector('[name=datetime_selector_minute]');
    container.opener.value = year + '-' + month + '-' + day + ' ' + hour.value + ':' + minute.value;
}

TM_Datetime.prototype.listener = function(event) {
    var element = event.currentTarget;
    switch (event.type) {
        case 'click':
            if (element.classList.contains('navi')) {
                event.preventDefault();
                var unit = element.hash.substr(1).split('-');
                var parent = element.parentNode;
                while (parent) {
                    if (parent.classList.contains('dateTimeCalendar')) {
                        break;
                    }
                    parent = parent.parentNode;
                }
                this.refreshCalendar(parent, unit[0], unit[1]);
            }
            break;
    }
};

TM_Datetime.prototype.bind = function(target, func) {
    return function() {
        return func.apply(target, arguments);
    };
}

TM_Datetime.prototype.strtotime = function(str) {
    if (str === '' || str === undefined || str === null) {
        return new Date();
    }

    var match;
    var year = '1970';
    var month = '01';
    var day = '01';
    var hour = '00';
    var minute = '00';
    var second = '00';
    if (match = str.match(/([0-9]{4})[-\/\.]([0-9]{1,2})[-\/\.]([0-9]{1,2})([T ]([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?)?/)) {
        year = match[1];
        month = match[2];
        if (month.length === 1) {
            month = '0' + month;
        }
        day = match[3];
        if (day.length === 1) {
            day = '0' + day;
        }

        if (match[5]) {
            hour = match[5];
            if (hour.length === 1) {
                hour = '0' + hour;
            }
        }
        if (match[6]) {
            minute = match[6];
            if (minute.length === 1) {
                minute = '0' + minute;
            }
        }
        if (match[8]) {
            second = match[8];
            if (second.length === 1) {
                second = '0' + second;
            }
        }
    }

    return new Date(year + '-' + month + '-' + day + 'T' + hour + ':' + minute + ':' + second);
}













TM_Datetime.prototype.initTime = function(elements) {
    var i;
    if (elements === undefined) {
        elements = document.querySelectorAll('[type=time]');
    }
    else if (elements.nodeName && elements.nodeName === 'INPUT') {
        elements = [elements];
    }
    else if (Array.isArray(elements)) {
        // Nop: elements = elements;
    }
    else {
        return;
    }
    for (i = 0; i < elements.length; i++) {
        var element = elements[i];
        element.addEventListener('blur', this.blurTime, false);
        element.addEventListener('focus', this.selectTime, false);
        element.addEventListener('keydown', this.inputTime, false);
        element.addEventListener('keyup', this.selectTime, false);
        element.addEventListener('mouseup', this.selectTime, false);
        element.addEventListener('click', this.selectTime, false);
        element.maxLength = 5;
    }
};

TM_Datetime.prototype.blurTime = function(event) {
    var element = event.currentTarget;
    var match = element.value.match(/([0-9\-]{1,2})?:?([0-9\-]{1,2})/);
    if (match === null) {
        match = element.value.match(/([0-9\-]{0,2}):([0-9\-]{0,2})/);
        if (match === null) {
            element.value = '';
            return;
        }
    }

    var hour = parseInt(match[1]);
    if (isNaN(hour)) {
        hour = 0;
    }
    if (hour > 23) {
        hour = 23;
    }
    if (hour < 10) {
        hour = '0' + hour;
    }

    var minute = parseInt(match[2]);
    if (isNaN(minute)) {
        minute = 0;
    }
    if (minute > 59) {
        minute = 59;
    }
    if (minute < 10) {
        minute = '0' + minute;
    }
    element.value = hour + ':' + minute;
};

TM_Datetime.prototype.selectTime = function(event) {
    event.preventDefault();
    var element = event.currentTarget;
    var start = element.selectionStart;
    var end = element.selectionEnd;
    var rangeStart = (start < 3) ? 0 : 3;

    if (event.type === 'keyup') {
        var key = event.key;
        switch (key) {
            case 'ArrowLeft':
                rangeStart = 0;
                break;
            case 'ArrowRight':
                rangeStart = 3;
                break;
            default:
                return;
        }
    }
    else if (event.type === 'focus') {
        element.dataset.beforeInput = element.value;
        if (element.value == '') {
            element.value = '--:--';
        }
        clearTimeout(TM.datetime.timer);
        TM.datetime.timer = setTimeout(function(){element.setSelectionRange(0,2)},0);
        return;
    }

    var rangeEnd = rangeStart + 2;
    element.setSelectionRange(rangeStart,rangeEnd);
};

TM_Datetime.prototype.inputTime = function(event) {
    var key = event.key;
    var isNum = true;

    if (   key === 'ArrowLeft'
        || key === 'ArrowRight'
        || key === 'Backspace'
        || key === 'Delete'
        || key === 'Tab'
    ) {
        return;
    }

    if (   key !== '0'
        && key !== '1'
        && key !== '2'
        && key !== '3'
        && key !== '4'
        && key !== '5'
        && key !== '6'
        && key !== '7'
        && key !== '8'
        && key !== '9'
    ) {
        isNum = false;
    }

    var element = event.currentTarget;
    var start = element.selectionStart;
    var end = element.selectionEnd;

    var separate = element.value.indexOf(':');
    if (separate === -1) {
        element.value += ':--';
        separate = element.value.indexOf(':');
        element.setSelectionRange(separate,separate);
    }

    var n = parseInt(key);

    var rangeStart = 0;
    if (isNaN(n) && key.length === 1) {
        element.value = element.dataset.beforeInput;
        if (key !== ':') {
            if (end > 2) {
                rangeStart = 3;
            }
        }
    }
    else {
        var hour,minute;
        if (separate >= 0 && start > separate) {
            var strMinute = element.value.substr(separate + 1);
            minute = parseInt(strMinute);
            if (isNaN(minute)) {
                minute = '00';
            }

            if (minute > 59) {
                minute = 59;
            }
            else if (strMinute.indexOf('0') === 0) {
                minute = strMinute;
            }
            else if (minute > 5 && minute < 10) {
                minute = '0' + minute;
            }

            hour = element.value.substr(0,2);
            element.value = hour + ':' + minute;

        }
        else {
            hour = parseInt(element.value.substring(0,separate));
            if (isNaN(hour)) {
                hour = 0;
            }

            if (hour < 3) {
                return;
            }
            else if (hour > 23) {
                hour = 23;
            }

            if (hour < 10) {
                hour = '0' + hour;
            }
            minute = element.value.substr(-2,2);
            element.value = hour + ':' + minute;
        }

        rangeStart = separate + 3;
        element.dataset.beforeInput = element.value;
    }

    if (element.value.length === 5) {
        rangeStart = (isNum) ? 3 : element.selectionStart;
        element.setSelectionRange(rangeStart,rangeStart + 2);
    }
};












TM_Datetime.prototype.initDate = function(elements) {
    var i;
    if (elements === undefined) {
        elements = document.querySelectorAll('[type=date]');
    }
    else if (elements.nodeName && elements.nodeName === 'INPUT') {
        elements = [elements];
    }
    else if (Array.isArray(elements)) {
        // Nop: elements = elements;
    }
    else {
        return;
    }
    for (i = 0; i < elements.length; i++) {
        var element = elements[i];
        element.addEventListener('blur', this.bind(this, this.blurDate), false);
        element.addEventListener('focus', this.bind(this, this.focusDate), false);
    }
};

TM_Datetime.prototype.blurDate = function(event) {
    if (!this.clearPopupLock) {
        this.clearPopup();
    }
};

TM_Datetime.prototype.focusDate = function(event) {
    this.clearPopup();

    var element = event.currentTarget;
    var date = (element.value == '') ? new Date() : new Date(element.value);
    var year = date.getFullYear();
    var month = date.getMonth() + 1;

    var container = this.createPopup(element);
    if (!container) return;

    var datetime = new TM_Datetime();
    datetime.setCallback(function(){
        var parent = this.parentNode;
        while (parent) {
            if (parent.classList.contains('dateTimeCalendar')) {
                break;
            }
            parent = parent.parentNode;
        }
        var current = parent.querySelector('.current');
        var unit = current.innerHTML.split('-');
        var year = parseInt(unit[0]);
        var month = parseInt(unit[1]);
        if (month < 10) {
            month = '0' + month;
        }
        var day = parseInt(this.innerHTML);
        if (day < 10) {
            day = '0' + day;
        }

        element.value = year + '-' + month + '-' + day;

        datetime.clearPopup();
    });
    datetime.setInitFunction(function(table){
        var current = table.querySelector('.current');
        var month = current.innerHTML;

        var td = table.querySelectorAll('.clickable');
        var str = element.value.replace(/\//g, '-').replace(/-0/g, '-');
        if (str.indexOf(month) === 0) {
            var tmp = str.split('-');
            var n = parseInt(tmp[2]) - 1;
            td.item(n).classList.add('active');
        }
    });

    var calendar = datetime.calendar(year,month);
    container.appendChild(calendar);
};












TM_Datetime.prototype.initDateTime = function(elements) {
    var i;
    if (elements === undefined) {
        elements = document.querySelectorAll('[type=date]');
    }
    else if (elements.nodeName && elements.nodeName === 'INPUT') {
        elements = [elements];
    }
    else if (Array.isArray(elements)) {
        // Nop: elements = elements;
    }
    else {
        return;
    }
    for (i = 0; i < elements.length; i++) {
        var element = elements[i];
        element.addEventListener('blur', this.bind(this, this.blurDate), false);
        element.addEventListener('focus', this.bind(this, this.focusDateTime), false);
    }
};

TM_Datetime.prototype.focusDateTime = function(event) {
    this.clearPopup();

    var element = event.currentTarget;
    var date = this.strtotime(element.value);
    var year = date.getFullYear();
    var month = date.getMonth() + 1;

    var container = this.createPopup(element);
    if (!container) return;

    var datetime = new TM_Datetime();
    datetime.setCallback(this.selectDate);
    datetime.setInitFunction(function(table){
        var current = table.querySelector('.current');
        var month = current.innerHTML;

        var td = table.querySelectorAll('.clickable');
        var str = element.value.replace(/\//g, '-').replace(/-0/g, '-');
        if (str.indexOf(month) === 0) {
            var tmp = str.split('-');
            var n = parseInt(tmp[2]) - 1;
            td.item(n).classList.add('active');
        }
    });

    var calendar = datetime.calendar(year,month);
    container.appendChild(calendar);

    var i, option, value, flexItem;
    var controler = container.appendChild(document.createElement('div'));
    controler.classList.add('controls');
    flexItem = controler.appendChild(document.createElement('span'));
    flexItem.classList.add('times');

    var H = date.getHours();
    var hour = flexItem.appendChild(document.createElement('select'));
    hour.name = 'datetime_selector_hour';
    for (i = 0; i < 24; i++) {
        option = hour.appendChild(document.createElement('option'));
        value = (i < 10) ? '0' + i : i;
        option.value = value;
        option.innerHTML = value;
        if (i == H) {
            option.selected = true;
        }
    }
    hour.addEventListener('change', this.changeTime);

    var colon = flexItem.appendChild(document.createElement('span'));
    colon.innerHTML = ':';

    var M = date.getMinutes();
    var minute = flexItem.appendChild(document.createElement('select'));
    minute.name = 'datetime_selector_minute';
    for (i = 0; i < 60; i++) {
        option = minute.appendChild(document.createElement('option'));
        value = (i < 10) ? '0' + i : i;
        option.value = value;
        option.innerHTML = value;
        if (i === M) {
            option.selected = true;
        }
    }
    minute.addEventListener('change', this.changeTime);
    var button = controler.appendChild(document.createElement('button'));
    button.type = 'button';
    button.innerHTML = 'Close';
    button.addEventListener('click', this.bind(this, this.clearPopup));
};

TM.datetime = new TM_Datetime();
