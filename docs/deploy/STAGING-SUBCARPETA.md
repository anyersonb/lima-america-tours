# Staging en subcarpeta: montaje y el 405 de la portada

Fecha: 2026-07-29
Staging: `https://limaamericatours.com/staging` (Laravel bajo el WordPress vivo, que queda intacto)

Estos archivos **viven solo en el servidor**, no en el repo, así que se documentan aquí: si alguien
rehace el montaje y no los replica, la portada de staging vuelve a dar error.

---

## El síntoma

`https://limaamericatours.com/staging/` (con barra final) devolvía:

```
Oops! An Error Occurred
The server returned a "405 Method Not Allowed".
```

Mientras que `https://limaamericatours.com/staging/es` funcionaba perfectamente. Es decir: **el sitio
funcionaba salvo por su puerta de entrada**, que es justo la URL que uno teclea o comparte.

## Por qué costó encontrarlo

Tres pistas engañosas, en orden:

1. **No había nada en `storage/logs/laravel.log`.** Parecía que el error no era de la app. Falso:
   Laravel **no loguea** las excepciones HTTP (404, 405) — están en `$dontReport`.
2. **`405` en vez de `403`.** Cuando un directorio no tiene índice y el listado está desactivado,
   Apache responde 403 Forbidden; **OpenLiteSpeed responde 405 Method Not Allowed**. Eso manda a
   buscar un problema de verbos HTTP que no existe.
3. **`Allow: GET` en la respuesta a un GET.** Contradictorio, y por eso revelador: significaba que la
   request llegaba al router ya deformada. La que confirmó que el 405 **sí era de Laravel** fue
   nuestra propia cabecera `x-robots-tag: noindex, nofollow`, que la pone el middleware
   `StagingNoindex`: si esa cabecera está, el código pasó por ahí.

## La causa real

Para una URL de **directorio**, el `.htaccess` del WordPress padre **no se aplica**: las reglas
`mod_rewrite` por directorio **no se heredan**, y manda el `.htaccess` de la carpeta más profunda que
existe (`staging/`). Ahí, la regla comodín

```apache
RewriteRule ^(?!public/)(.*)$ public/$1 [L]
```

**sí matchea la ruta vacía** (`(.*)` acepta cadena vacía) y reescribe a `public/`, que es otro
directorio; de ahí entra el `.htaccess` de `public/` y llega al front controller con la request ya
deformada → 405.

Por eso `/staging` (sin barra) sí funcionaba: no es un directorio, así que ahí sí se aplicaba el
`.htaccess` del padre.

## Lo que NO funcionó (para no repetirlo)

| Intento | Resultado |
|---|---|
| `RewriteRule ^$ public/index.php [L]` en `staging/.htaccess` | Sigue 405: la reescritura interna acaba en la misma cadena |
| `DirectoryIndex public/index.php` | OLS no acepta rutas con carpeta en `DirectoryIndex` |
| `index.php` redirector en la raíz de la app + excluirlo del rewrite | No se llega a ejecutar |
| `RewriteRule ^staging/?$ ...` en el `.htaccess` del WordPress padre | Solo arregla `/staging` (sin barra); para `/staging/` ese archivo no se evalúa |

## La solución

Redirección **externa**, como primera regla del `.htaccess` de la carpeta de la app:

**`/home/limaamericatours.com/public_html/staging/.htaccess`**

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /staging/

# Portada de staging: redirección EXTERNA antes de nada (ver el análisis de arriba).
RewriteRule ^$ /staging/es [R=302,L]

RewriteRule ^(?!public/)(.*)$ public/$1 [L]
</IfModule>
```

**`/home/limaamericatours.com/public_html/.htaccess`** (raíz del WordPress) mantiene el passthrough,
más la misma redirección para la variante sin barra:

```apache
# BEGIN Lima America staging passthrough
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^staging/?$ /staging/es [R=302,L]
RewriteRule ^staging($|/) - [L]
</IfModule>
# END Lima America staging passthrough
```

Costo aceptado: esa entrada no negocia idioma por `Accept-Language` (la ruta `/` de Laravel sí lo
hace, pero no se llega a ella). **En producción no aplica nada de esto**: el sitio se sirve desde la
raíz del dominio y la ruta `/` de Laravel funciona normal.

## Notas de operación

- **OLS cachea los `.htaccess`.** Tras editarlos hay que recargar: `/usr/local/lsws/bin/lswsctrl restart`
  (es graceful; el WordPress del cliente no se cae — verificado en cada paso con `curl` a `/` y a
  `/nosotros/`).
- Backups dejados en el server: `.htaccess.bak-405-*` en la raíz del WP y en `staging/`.
- Tras cualquier cambio aquí, comprobar **las dos cosas**: que staging entre por `/staging` y
  `/staging/`, y que el WordPress vivo siga respondiendo 200 en `/` y en una interna.
