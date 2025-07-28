# 🔒 Seguridad Administrativa - Mi Tienda Online

## 🛡️ Separación Completa de Roles Implementada

### **CLIENTES** (is_admin = 0)
- **Acceso**: Público
- **Registro**: `registro.php` y `registro_rapido.php`
- **Funciones**: Compras, ver productos, gestionar perfil
- **Restricciones**: NO pueden acceder al panel de administración

### **ADMINISTRADORES** (is_admin = 1)
- **Acceso**: Restringido
- **Registro**: Solo desde panel de administración
- **Funciones**: Gestión completa del sistema
- **Seguridad**: Múltiples capas de verificación

## 🔐 Sistema de Protección Implementado

### **1. Verificación de Permisos (auth_check.php)**
```php
// Se incluye en TODAS las páginas del panel de administración
require_once 'auth_check.php';
```

**Verifica:**
- ✅ Usuario autenticado
- ✅ Usuario es administrador
- ✅ Usuario sigue siendo administrador en la base de datos
- ✅ Sesión válida

### **2. Protección Adicional (protection.php)**
```php
// Para funciones críticas
require_once 'protection.php';
```

**Incluye:**
- ✅ Verificación continua de permisos
- ✅ Logging de acciones administrativas
- ✅ Verificación de acciones críticas
- ✅ Auditoría de acceso

### **3. Códigos Secretos**
- **Primer administrador**: `PRIMER_ADMIN_2024`
- **Administradores adicionales**: `ADMIN2024`

## 📁 Estructura de Archivos Segura

### **Archivos Públicos (Clientes)**
```
/ProyectoWeb/
├── registro.php              # Registro de clientes
├── registro_rapido.php       # Registro rápido desde carrito
├── login.php                 # Login para todos
├── index.php                 # Página principal
├── productos.php             # Catálogo de productos
├── carrito.php               # Carrito de compras
└── mis_pedidos.php           # Pedidos del cliente
```

### **Archivos Administrativos (Protegidos)**
```
/ProyectoWeb/admin/
├── auth_check.php            # Verificación de permisos
├── protection.php            # Protección adicional
├── index.php                 # Dashboard administrativo
├── productos.php             # Gestión de productos
├── categorias.php            # Gestión de categorías
├── pedidos.php               # Gestión de pedidos
├── usuarios.php              # Gestión de usuarios
└── crear_admin.php           # Crear nuevos administradores
```

### **Archivos de Configuración Inicial**
```
/ProyectoWeb/
├── crear_primer_admin.php    # Solo para el primer admin
└── includes/db.php           # Configuración de base de datos
```

## 🚫 Protecciones Implementadas

### **Acceso Denegado para Clientes**
- ❌ No pueden acceder a `/admin/`
- ❌ No pueden crear administradores
- ❌ No pueden gestionar productos
- ❌ No pueden ver pedidos de otros usuarios

### **Acceso Restringido para Administradores**
- ✅ Solo pueden crear administradores desde el panel
- ✅ Requieren códigos secretos
- ✅ Verificación continua de permisos
- ✅ Logging de acciones críticas

### **Seguridad de Sesiones**
- ✅ Verificación de sesión en cada página
- ✅ Verificación de permisos en tiempo real
- ✅ Destrucción de sesión si se revocan privilegios
- ✅ Redirección automática si no hay permisos

## 🔑 Proceso de Creación de Administradores

### **Paso 1: Primer Administrador**
1. Acceder a `http://localhost/ProyectoWeb/crear_primer_admin.php`
2. Usar código: `PRIMER_ADMIN_2024`
3. Completar formulario
4. **IMPORTANTE**: Eliminar archivo después de crear

### **Paso 2: Administradores Adicionales**
1. Iniciar sesión como administrador
2. Ir a panel de administración
3. Hacer clic en "Crear Admin"
4. Usar código: `ADMIN2024`
5. Completar formulario

## 📋 URLs Importantes

### **Para Clientes**
- **Registro**: `http://localhost/ProyectoWeb/registro.php`
- **Login**: `http://localhost/ProyectoWeb/login.php`
- **Tienda**: `http://localhost/ProyectoWeb/index.php`

### **Para Administradores**
- **Panel**: `http://localhost/ProyectoWeb/admin/index.php`
- **Crear Admin**: `http://localhost/ProyectoWeb/admin/crear_admin.php`
- **Gestión Productos**: `http://localhost/ProyectoWeb/admin/productos.php`

## ⚠️ Recomendaciones de Seguridad

### **1. Cambiar Códigos Secretos**
```php
// En crear_primer_admin.php
$codigo_correcto = 'TU_CODIGO_SECRETO_AQUI';

// En crear_admin.php
$codigo_admin_correcto = 'TU_CODIGO_ADMIN_AQUI';
```

### **2. Usar Contraseñas Fuertes**
- Mínimo 8 caracteres para administradores
- Combinación de letras, números y símbolos
- No usar información personal

### **3. Mantener Actualizado**
- Revisar logs de acceso regularmente
- Actualizar códigos secretos periódicamente
- Hacer copias de seguridad de la base de datos

### **4. Monitoreo**
- Revisar archivos de log del servidor
- Monitorear accesos al panel de administración
- Verificar usuarios administrativos regularmente

## 🚨 Acciones Críticas Protegidas

### **Funciones que Requieren Verificación Especial**
- ✅ Crear administradores
- ✅ Eliminar administradores
- ✅ Modificar configuración del sistema
- ✅ Eliminar productos
- ✅ Eliminar usuarios

### **Logging Automático**
Todas las acciones críticas se registran automáticamente:
- Usuario que realizó la acción
- Fecha y hora
- IP de origen
- Acción específica realizada

## 🔍 Verificación de Seguridad

### **Comandos de Verificación**
```bash
# Verificar administradores existentes
php -r "require_once 'includes/db.php'; \$stmt = \$db->prepare('SELECT id, nombre, email, is_admin FROM usuarios WHERE is_admin = 1'); \$stmt->execute(); \$result = \$stmt->get_result(); while(\$row = \$result->fetch_assoc()) { echo 'Admin: ' . \$row['nombre'] . ' (' . \$row['email'] . ')' . PHP_EOL; }"

# Verificar permisos de archivos
ls -la admin/
```

### **Pruebas de Seguridad**
1. Intentar acceder a `/admin/` sin estar logueado
2. Intentar acceder como cliente
3. Verificar que los códigos secretos funcionan
4. Probar creación de administradores

## 📞 Soporte de Seguridad

Si detectas algún problema de seguridad:
1. Revisar logs del servidor
2. Verificar permisos de archivos
3. Cambiar códigos secretos inmediatamente
4. Revisar usuarios administrativos
5. Hacer copia de seguridad de la base de datos

---

**⚠️ IMPORTANTE**: Este sistema está diseñado para máxima seguridad. Nunca compartas los códigos secretos y cámbialos regularmente en producción. 