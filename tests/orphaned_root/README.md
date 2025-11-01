Carpeta para archivos "test_*" que estaban sueltos en la raíz del repositorio.

Por qué existen aquí
- Para evitar eliminar archivos útiles por accidente moví los archivos "test_*" que estaban directamente en la raíz a esta carpeta.

Qué hice
- Los archivos fueron movidos (cuando ejecutes el script) a este directorio.
- Se creó un script en `scripts/organize_tests.ps1` que hace el movimiento y actualiza referencias básicas en .html/.php/.js/.css.

Siguientes pasos recomendados
1. Ejecuta el script (PowerShell) desde la raíz del repo:
   cd "C:\Users\domin\Cooperativa La Pintada"
   .\scripts\organize_tests.ps1
2. Revisa los archivos en `tests/orphaned_root/`.
3. Abre las copias de seguridad `.bak` (si existen) para verificar cambios en las referencias.
4. Si confirmas que algunos test no son necesarios, bórralos manualmente o dime cuáles eliminar y lo hago.

Notas
- El script es conservador: mueve archivos y crea backups de los archivos que modifica. No borra nada permanentemente.
- Si quieres que borre automáticamente archivos sin funcionalidad detectada, dime y puedo ajustar el script para intentar detectar (p.ej., archivos con contenido vacío o sólo comentarios) y borrar los que cumplan las reglas.
