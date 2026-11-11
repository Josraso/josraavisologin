# Changelog - josraavisologin

## 1.0.3 - 2026-11-11
- Actualizador: si el servidor de actualizaciones cambia de dominio, el módulo aprende la dirección nueva solo.
- Actualizador preparado para licencias (desactivadas en este módulo).

## 1.0.2 - 2026-11-11
- El CSS y el JS del aviso se cargan fuera de la caché de PrestaShop (CCC) y con la versión en la dirección, así
  que al actualizar el módulo se ve el aviso nuevo sin borrar cachés. Al actualizar se vacía esa caché una vez.

## 1.0.1 - 2026-11-11
- Aviso mucho más visible: recuadro amarillo con icono, titular grande («¿No puedes entrar?»), texto más grande y
  botón azul «Crea una contraseña nueva aquí». Va arriba del formulario, antes del correo.
- Tras un intento fallido se pone en rojo y se mueve un instante para llamar la atención.
- En el móvil, letra grande y el botón a todo el ancho.

## 1.0.0 - 2026-11-11
- Aviso junto a todos los formularios de acceso de la tienda (página de inicio de sesión, login del checkout,
  ventanas de login de otros módulos y checkout de un paso de josra): si no recuerdas la contraseña o no te la
  acepta, enlace directo para crear una nueva. Se resalta en rojo si el intento de acceso falla.
- Texto y enlace configurables por idioma. Opción para mostrarlo siempre o solo tras un intento fallido.
- Conectado al servidor de actualizaciones de josra.
