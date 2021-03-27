require('../css/display.scss');

let timeSelector = '#time';

function padNumber(number) {
    return ('' + number).padStart(2, '0');
}

setInterval(function() {
    let now = new Date();
    let timeElement = document.querySelector(timeSelector);

    if(timeElement !== null) {
        timeElement.innerHTML = padNumber(now.getHours()) + ':' + padNumber(now.getMinutes());
    }
}, 500);

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-scroll=true]').forEach(function(el) {
        if(el.hasAttribute('data-interval') !== true) {
            console.error('You must specify data-interval (in seconds)');
            return;
        }

        let interval = parseInt(el.getAttribute('data-interval'));
        let lastScrollTop = 0;

        setInterval(function() {
            let height = el.offsetHeight;
            let maxHeight = el.scrollHeight;

            let currentScrollTop = el.scrollTop;
            let newScrollTop = currentScrollTop + height - 100;

            if(newScrollTop === lastScrollTop) {
                newScrollTop = 0;
            }

            if(newScrollTop >= maxHeight - 100) {
                newScrollTop = 0;
            }

            lastScrollTop = newScrollTop;

            el.scrollTo({
                top: newScrollTop,
                behavior: 'smooth'
            });
        }, interval*1000);
    });
});