const isTablet = (/iPad|Android/i.test(navigator.userAgent)
    || (navigator.userAgent.includes('Macintosh') && 'ontouchend' in document));

if (isTablet) {
    const template = document.getElementById('mobile-asset');
    if (template && 'content' in template) {
        const children = template.content.childNodes;
        for (let i = 0; i < children.length; i++) {
            if (children[i].nodeType !== Node.ELEMENT_NODE) {
                continue;
            }
            const child = document.importNode(children[i], true);
            const group = document.head.querySelectorAll(child.nodeName.toLowerCase());
            if (group) {
                const reference = group[group.length - 1].nextSibling;
                if (reference) {
                    document.head.insertBefore(child, reference);
                    continue;
                }
            }
            document.head.appendChild(child);
        }
    }
}
