# Separación de Roles y Seguridad - Mi Tienda Online

## Resumen de Cambios

Se ha implementado una separación completa entre el registro de clientes y administradores, así como medidas de seguridad para proteger el panel de administración.

## Estructura de Roles

### Clientes (is_admin = 0)
- Pueden registrarse a través de:
  - `registro.php` - Registro normal
  - `registro_rapido.php` - Registro rápido desde el carrito
- Acceso limitado a:
  - Ver productos
  - Agregar productos al carrito
  - Realizar compras
  - Ver sus pedidos (`mis_pedidos.php`)
  - Gestionar su perfil

### Administradores (is_admin = 1)
- Solo pueden ser creados por:
  - `crear_primer_admin.php` - Para el primer administrador
  - `registro_admin.php` - Para administradores adicionales (requiere código secreto)
- Acceso completo a:
  - Panel de administración (`admin/`)
  - Gestión de productos
  - Gestión de categorías
  - Gestión de pedidos
  - Gestión de usuarios
  - Estadísticas del sistema

## Archivos de Seguridad

### `admin/auth_check.php`
- Verifica que el usuario esté autenticado
- Verifica que el usuario sea administrador
- Verifica que el usuario siga siendo administrador en la base de datos
- Se incluye en todas las páginas del panel de administración

### `registro_admin.php`
- Solo accesible para administradores existentes
- Requiere código secreto: `ADMIN2024`
- Crea nuevos administradores con validaciones estrictas

### `crear_primer_admin.php`
- Solo funciona si no existe ningún administrador
- Requiere código secreto: `PRIMER_ADMIN_2024`
- Para uso inicial del sistema

## Medidas de Seguridad Implementadas

1. **Verificación de Permisos**: Todas las páginas del panel de administración verifican permisos
2. **Separación de Registros**: Clientes y administradores se registran por vías diferentes
3. **Códigos Secretos**: Creación de administradores requiere códigos especiales
4. **Validación de Sesión**: Verificación continua del estado de administrador
5. **Mensajes de Error Específicos**: Información clara sobre errores de acceso

## Instrucciones de Uso

### Para Crear el Primer Administrador
1. Acceder a `http://localhost/ProyectoWeb/crear_primer_admin.php`
2. Completar el formulario con:
   - Nombre completo
   - Email
   - Contraseña (mínimo 8 caracteres)
   - Código secreto: `PRIMER_ADMIN_2024`
3. Una vez creado, eliminar o renombrar el archivo `crear_primer_admin.php`

### Para Crear Administradores Adicionales
1. Iniciar sesión como administrador
2. Ir al panel de administración
3. Hacer clic en "Registrar Admin" en el sidebar
4. Completar el formulario con:
   - Nombre completo
   - Email
   - Contraseña (mínimo 8 caracteres)
   - Código secreto: `ADMIN2024`

### Para Clientes
- Registro normal: `http://localhost/ProyectoWeb/registro.php`
- Registro rápido: Disponible desde el carrito de compras

## Protecciones Implementadas

- Los clientes no pueden acceder al panel de administración
- Los administradores no pueden cambiar su propio rol
- Los administradores no pueden eliminarse a sí mismos
- Verificación continua de permisos en cada página
- Mensajes de error específicos para diferentes situaciones

## Archivos Modificados

- `registro.php` - Solo registra clientes
- `registro_rapido.php` - Solo registra clientes
- `admin/index.php` - Incluye verificación de permisos
- `admin/productos.php` - Incluye verificación de permisos
- `admin/categorias.php` - Incluye verificación de permisos
- `admin/pedidos.php` - Incluye verificación de permisos
- `admin/usuarios.php` - Incluye verificación de permisos
- `login.php` - Manejo de mensajes de error específicos
- `includes/header.php` - Solo muestra enlace al panel para administradores

## Recomendaciones de Seguridad

1. Cambiar los códigos secretos por defecto
2. Usar contraseñas fuertes para administradores
3. Revisar regularmente los logs de acceso
4. Mantener actualizado el sistema
5. Hacer copias de seguridad regulares de la base de datos 