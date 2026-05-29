---
name: module-naming-convention
description: "Aplica esta skill al crear un modulo nuevo. Las tablas de base de datos deben seguir el patron {modulo}_{componente}. La primera parte identifica el modulo, la segunda indica que parte especifica del modulo es esa tabla."
---

# Convencion de Nombrado de Tablas por Modulo

## Regla 1: Tablas con prefijo de modulo

Toda tabla nueva DEBE nombrarse con el patron `{modulo}_{componente}`:

- `{modulo}` — nombre corto del modulo al que pertenece (snake_case)
- `{componente}` — que representa esa tabla dentro del modulo

Ejemplos:
- Modulo "File Manager" → tablas: `file_manager_items`, `file_manager_shares`, `file_manager_links`
- Modulo "Inventario" → tablas: `inventario_productos`, `inventario_movimientos`
- Modulo "Felicidad" → tablas: `felicidad_estados`, `felicidad_sugerencias`

## Regla 2: Nombre del modulo

El nombre del modulo debe ser:
- Corto (1-3 palabras en snake_case)
- Descriptivo del proposito general del modulo
- Consistente en toda la aplicacion (mismo nombre en tablas, modelos, config, rutas, permisos)

## Regla 3: Aplica a todo el ecosistema del modulo

El nombre del modulo debe usarse consistentemente en:
1. **Migraciones**: `database/migrations/tenant/{timestamp}_{modulo}_{componente}.php`
2. **Modelos**: clase `{Componente}` (sin prefijo, porque ya vive en el namespace del modulo)
3. **Feature en config/plans.php**: clave `'{modulo}'` con entities del modulo
4. **Permisos Shield**: se generan automaticamente con `shield:generate`
5. **Archivos Blade/Vistas**: `resources/views/{modulo}/` o `filament/{modulo}/`
6. **Lang keys**: `{Modulo}_{Clave}` en PascalCase para keys de traduccion

## Regla 4: Tablas existentes

Las tablas ya creadas NO se renombran. Esta convencion aplica solo a nuevos modulos.

## Ejemplo: Modulo File Manager

| Elemento | Convencion |
|---|---|
| Modulo | `file_manager` |
| Tablas | `file_manager_items`, `file_manager_shares`, `file_manager_links` |
| Feature plans | `'file_manager'` |
| Lang keys | `FileManager_Page_Title`, `FileManager_Navigation_Label` |
| Migraciones | `2026_01_01_000001_file_manager_items.php` |
| Ruta Filament | `/admin/file-manager` |

## Verificacion

Antes de crear cualquier tabla nueva, verificar:
1. El nombre sigue el patron `{modulo}_{componente}`
2. El modulo esta definido en `config/plans.php`
3. Todos los artefactos del modulo usan el mismo nombre de modulo
