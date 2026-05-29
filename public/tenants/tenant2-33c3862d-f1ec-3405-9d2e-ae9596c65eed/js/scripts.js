// Tus scripts personalizados aqui

// Toggle dark/light/system mode
(function() {
    var t = localStorage.getItem('theme');
    if (t === 'dark') {
        document.documentElement.classList.add('dark');
        document.body.style.backgroundColor = '#070919';
    } else if (t === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
            document.body.style.backgroundColor = '#070919';
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
})();
