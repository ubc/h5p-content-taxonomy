import 'url-search-params-polyfill';

document.addEventListener('DOMContentLoaded', (event) => {
    const search = new URLSearchParams(window.location.search);
    
    if ('faculty_redirect' !== search.get('action')) {
        return;
    }

    jQuery('html, body').stop().animate({
        'scrollTop': jQuery('#ubc-faculty').offset().top - 80
    }, 800, 'swing', null);
})