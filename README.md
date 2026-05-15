# Dolibarr Warehouse Filter Module 📦

Módulo personalizado para **Dolibarr ERP/CRM** (Compatible con v20+ y probado en **v23.0.2**) que añade una funcionalidad muy solicitada: **Filtrar la lista de productos por un almacén específico**.

Por defecto, Dolibarr muestra el stock físico total sumando todos los almacenes. Con este módulo, podrás elegir un almacén desde un menú desplegable directamente en la cabecera de la lista de productos y ver exactamente cuánto stock hay en esa sucursal en particular.

## ✨ Características

- 🏗️ **Integración nativa:** Se integra en la tabla original de productos (`product/list.php`) usando Hooks (no modifica archivos del *core*).
- 🔍 **Filtro desplegable:** Añade un selector de almacenes en la barra de filtros (lupa).
- 📊 **Stock dinámico:** Al seleccionar un almacén, añade una columna que muestra el **Stock Físico Real** exclusivamente de ese almacén.
- 🚀 **Optimizado para Dolibarr v23:** Utiliza la nueva clase `FormProduct` para el selector de almacenes.

## 🛠️ Instalación

1. Descarga el repositorio o haz un clon.
2. Copia la carpeta raíz (renombrada a `warehousefilter` si es necesario) dentro de la carpeta `custom` de tu instalación de Dolibarr. La ruta debe quedar así:
   `htdocs/custom/warehousefilter/`
3. Asegúrate de que el directorio `custom` está habilitado en tu archivo `conf.php`.
4. Inicia sesión en Dolibarr como Administrador.
5. Ve a **Configuración > Módulos/Aplicaciones**.
6. Busca el módulo **WarehouseFilter** (en la pestaña *Otros*) y actívalo.

*(Nota: Si actualizas el código del módulo, recuerda desactivarlo y volverlo a activar en Dolibarr para que se refresquen los Hooks en la base de datos).*

## 📁 Estructura de Archivos

```text
warehousefilter/
 ├── class/
 │   └── actions_warehousefilter.class.php   # Lógica del Hook (inyección de columnas y SQL)
 └── core/
     └── modules/
         └── modWarehouseFilter.class.php    # Descriptor e instalador del módulo
```

## 👨‍💻 Iván Guevara

Desarrollado y compartido para la comunidad para mejorar la gestión de inventario multi-almacén en Dolibarr.
