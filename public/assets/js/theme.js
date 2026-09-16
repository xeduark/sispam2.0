(function () {
    var KEY = 'sispam-theme';

    function actualizarIcono() {
        var icono = document.querySelector('[data-theme-toggle] i');
        if (!icono) return;
        var esOscuro = document.documentElement.getAttribute('data-theme') === 'dark';
        icono.className = esOscuro ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }

    document.addEventListener('DOMContentLoaded', actualizarIcono);

    document.addEventListener('click', function (evento) {
        var boton = evento.target.closest('[data-theme-toggle]');
        if (!boton) return;

        var raiz = document.documentElement;
        var siguiente = raiz.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        raiz.setAttribute('data-theme', siguiente);
        actualizarIcono();

        try {
            localStorage.setItem(KEY, siguiente);
        } catch (e) {}
    });
})();
