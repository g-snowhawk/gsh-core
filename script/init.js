// Create instance
window.TM = window.TM || new TM_Common();

// load styles
TM.loadStyle([
    { href:'colorpicker', rel:'stylesheet' },
    { href:'datetime', rel:'stylesheet' }
]);

// load modules
TM.loadModule([
    { src:'accordion', async:' async', defer:'' },
    { src:'colorpicker', async:'', defer:'' },
    { src:'datetime', async:'', defer:'' },
    { src:'form', async:' async', defer:'' },
    { src:'function_key', async:' async', defer:'' },
    { src:'menu', async:' async', defer:'' },
    { src:'posting', async:' async', defer:'' },
    { src:'resizer', async:'', defer:'' },
    { src:'scrollto', async:'async', defer:'' },
    { src:'subform', async:' async', defer:'' },
    { src:'xhr', async:' async', defer:'' }
]);

document.addEventListener('readystatechange', function(event){
    if (this.readyState === 'complete') {
        TM.menu = new TM_Menu('global-nav');
        var accordion = document.querySelector('a.accordion-switcher');
        if (accordion) {
            TM.accordion = new TM_Accordion();
        }
    }
});
