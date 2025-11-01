# responsive/

Carpeta con archivos para hacer la aplicación responsive.

Archivos:
- `responsive.css` - hoja de estilos base con reglas fluidas y breakpoints.
- `responsive.js` - helpers mínimos para menú móvil y toggles.

Uso recomendado:
- Añadir en cada HTML público, dentro de `<head>`:

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/public/responsive/responsive.css">
  <script defer src="/public/responsive/responsive.js"></script>

Observaciones:
- Se agregó el CSS y JS de forma conservadora; algunos componentes específicos pueden necesitar estilos adicionales. Recomendado revisar `public/assets/css` o `css/` y consolidar estilos.
- No se modificaron aún los HTML; pide confirmación para que los inserte automáticamente en todos los HTML encontrados.
