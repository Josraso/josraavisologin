/**
 * josraavisologin: aviso junto a los formularios de acceso.
 * Busca los formularios por lo que son (correo + contraseña de inicio de sesión), no por el tema,
 * y también los que aparecen después (ventanas, checkout de un paso).
 */
(function () {
  'use strict';

  var cfg = window.josraAvisoLogin;
  if (!cfg || !cfg.text) {
    return;
  }

  function make() {
    var box = document.createElement('div');
    box.className = 'josra-aviso-login' + (cfg.when === 'error' ? ' is-hidden' : '');
    box.setAttribute('role', 'alert');

    // «¿No puedes entrar?» va de titular; el resto, debajo
    var text = String(cfg.text);
    var title = '';
    var m = text.match(/^\s*(¿[^?]{2,80}\?|[^.?!]{2,60}[?!])\s*(.*)$/);
    if (m) {
      title = m[1];
      text = m[2];
    }

    var icon = document.createElement('span');
    icon.className = 'josra-aviso-login__icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.appendChild(document.createTextNode('!'));
    box.appendChild(icon);

    var body = document.createElement('div');
    body.className = 'josra-aviso-login__body';
    if (title) {
      var t = document.createElement('p');
      t.className = 'josra-aviso-login__title';
      t.appendChild(document.createTextNode(title));
      body.appendChild(t);
    }
    if (text) {
      var p = document.createElement('p');
      p.className = 'josra-aviso-login__text';
      p.appendChild(document.createTextNode(text));
      body.appendChild(p);
    }
    if (cfg.url) {
      var a = document.createElement('a');
      a.href = cfg.url;
      a.rel = 'nofollow';
      a.className = 'josra-aviso-login__btn';
      a.appendChild(document.createTextNode(cfg.link || cfg.url));
      body.appendChild(a);
    }
    box.appendChild(body);
    return box;
  }

  function alert(box) {
    var again = box.classList.contains('is-alert');
    box.classList.remove('is-hidden');
    box.classList.add('is-alert');
    if (!again) {
      box.classList.add('is-shake');
      setTimeout(function () { box.classList.remove('is-shake'); }, 700);
    }
  }

  function hasText(el) {
    return !!(el && el.textContent && el.textContent.trim() !== '');
  }

  /* Formularios de inicio de sesión de PrestaShop y del tema (página de login, checkout, ventanas de otros módulos) */
  function psForms() {
    var out = [];
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i += 1) {
      var f = forms[i];
      if (f.getAttribute('data-josra-aviso')) {
        continue;
      }
      var pass = f.querySelector('input[type="password"][name="password"]');
      var email = f.querySelector('input[name="email"]');
      var isLogin = f.querySelector('input[name="submitLogin"]') || /login/i.test(f.id || '');
      // El de registro también tiene correo y contraseña: se distingue porque no es de inicio de sesión
      if (pass && email && isLogin && !f.querySelector('input[name="firstname"]')) {
        out.push(f);
      }
    }
    return out;
  }

  function attachPs(f) {
    f.setAttribute('data-josra-aviso', '1');
    var box = make();
    // Arriba del formulario, antes del correo: es lo primero que se ve
    var first = f.querySelector('section') || f;
    first.insertBefore(box, first.firstChild);
    // Error de acceso ya mostrado al cargar (página de login tras un intento fallido)
    var scope = f.closest('section, .login-form, .modal-body') || f.parentNode;
    if (hasText(scope.querySelector('.alert-danger, .help-block li, .form-errors li'))) {
      alert(box);
    }
    // Errores que aparecen sin recargar (ventanas de login por AJAX)
    if (window.MutationObserver) {
      new MutationObserver(function () {
        if (hasText(scope.querySelector('.alert-danger, .help-block li, .form-errors li, .invalid-feedback'))) {
          alert(box);
        }
      }).observe(scope, { childList: true, subtree: true });
    }
  }

  /* Checkout de un paso de josra (josracheckout) */
  function attachJco(login) {
    login.setAttribute('data-josra-aviso', '1');
    var box = make();
    var firstField = login.querySelector('label, input');
    if (firstField && firstField.parentNode === login) {
      login.insertBefore(box, firstField);
    } else {
      login.insertBefore(box, login.firstChild);
    }
    var err = login.querySelector('[data-jco-login-err]');
    if (err && window.MutationObserver) {
      new MutationObserver(function () {
        if (hasText(err)) {
          alert(box);
        }
      }).observe(err, { childList: true, characterData: true, subtree: true });
    }
  }

  function scan() {
    var forms = psForms();
    for (var i = 0; i < forms.length; i += 1) {
      attachPs(forms[i]);
    }
    var jco = document.querySelectorAll('[data-jco-login]:not([data-josra-aviso])');
    for (var j = 0; j < jco.length; j += 1) {
      attachJco(jco[j]);
    }
  }

  function start() {
    scan();
    if (window.MutationObserver) {
      var timer = null;
      new MutationObserver(function () {
        clearTimeout(timer);
        timer = setTimeout(scan, 150);
      }).observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
