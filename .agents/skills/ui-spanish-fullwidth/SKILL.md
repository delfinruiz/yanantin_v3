---
name: ui-spanish-fullwidth
description: "Aplica esta skill en cada tarea de este proyecto. Todas las etiquetas, labels, placeholders, mensajes y textos visibles en el frontend (Filament y Blade) deben estar en espanol. Las paginas y formularios deben ocupar el ancho completo (full width)."
---

# UI en espanol y Full Width

## Regla 1: Todos los textos visibles en espanol

Cada label, placeholder, helperText, description, heading, title, navigation label, model label, badge, button, mensaje de validacion y cualquier texto visible al usuario DEBE estar en espanol.

Ejemplos:
- `TextInput::make('name')->label('Nombre')` → no `->label('Name')`
- `Section::make('Informacion del tenant')` → no `'Tenant Information'`
- `->placeholder('Mi Empresa S.A.')` → no `'My Company Inc.'`
- `->helperText('El tenant sera accesible en...')` → no `'The tenant will be accessible at...'`
- `$modelLabel = 'Tenant'` → `$modelLabel = 'Inquilino'`
- `$pluralModelLabel = 'Tenants'` → `$pluralModelLabel = 'Inquilinos'`
- `$navigationGroup = 'SaaS'` → `$navigationGroup = 'Administracion'`

## Regla 2: Ancho completo (Full Width)

Todas las paginas, formularios y tablas deben usar el ancho completo disponible. No deben tener max-width restrictivo.

En Filament, usar:
```php
public function getMaxContentWidth(): string
{
    return 'full';
}
```

En Blade/Tailwind, usar `max-w-full` o `w-full` en vez de `max-w-7xl`.

## Verificacion

Antes de finalizar cualquier cambio, revisar:
1. No hay texto en ingles en labels, placeholders, helpers, botones, mensajes
2. Las paginas y formularios usan ancho completo
