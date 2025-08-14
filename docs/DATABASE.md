# Esquema de base de datos (mínimo)

> Ajusta tamaños a tus necesidades. Índices recomendados incluidos.

## usuarios
```sql
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  direccion VARCHAR(255) NULL,
  cp VARCHAR(10) NULL,
  localidad VARCHAR(100) NULL,
  provincia VARCHAR(100) NULL,
  telefono VARCHAR(20) NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','cliente') DEFAULT 'cliente',
  role_id INT NULL,
  cif VARCHAR(15) NULL,
  logo VARCHAR(255) NULL,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  serie_facturas VARCHAR(50) NULL,        -- tokens {yy}/{yyyy} opcionales
  inicio_serie_facturas INT DEFAULT 1,
  serie_abonos VARCHAR(50) NULL,
  inicio_serie_abonos INT DEFAULT 1,
  pie_factura TEXT NULL,
  notas_factura TEXT NULL,
  color_primario VARCHAR(7) DEFAULT '#000000',
  fuente_factura VARCHAR(100) NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

## roles
```sql
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);
```

## modulos
```sql
CREATE TABLE modulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);
```

## roles_modulos
```sql
CREATE TABLE roles_modulos (
  role_id INT NOT NULL,
  modulo_id INT NOT NULL,
  PRIMARY KEY (role_id, modulo_id),
  FOREIGN KEY (role_id) REFERENCES roles(id),
  FOREIGN KEY (modulo_id) REFERENCES modulos(id)
);
```

## clientes
```sql
CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  cif VARCHAR(20) NOT NULL,
  direccion VARCHAR(255) NULL,
  cp VARCHAR(10) NULL,
  localidad VARCHAR(100) NULL,
  provincia VARCHAR(100) NULL,
  email VARCHAR(100) NULL,
  telefono VARCHAR(20) NULL,
  usuario_id INT NOT NULL,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  activo TINYINT(1) DEFAULT 1,
  KEY idx_clientes_usuario (usuario_id)
);
```

## productos
```sql
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  referencia VARCHAR(50) NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  iva_porcentaje DECIMAL(5,2) DEFAULT 21.00,
  activo TINYINT(1) DEFAULT 1,
  usuario_id INT NOT NULL,
  KEY idx_productos_usuario (usuario_id)
);
```

## facturas
```sql
CREATE TABLE facturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  cliente_id INT NOT NULL,
  fecha DATE NOT NULL,
  numero VARCHAR(50) NOT NULL,
  serie VARCHAR(10) DEFAULT 'A',
  base_imponible DECIMAL(10,2) NOT NULL,
  iva DECIMAL(5,2) NULL,
  irpf DECIMAL(5,2) NULL,
  total DECIMAL(10,2) NOT NULL,
  pdf_generado TINYINT(1) DEFAULT 0,
  uuid CHAR(36) NULL,
  enviado_cliente TINYINT(1) DEFAULT 0,
  verifactu_hash TEXT NULL,
  verifactu_hash_anterior TEXT NULL,
  verifactu_firma TEXT NULL,
  verifactu_codigo_qr TEXT NULL,
  verifactu_json TEXT NULL,
  verifactu_fecha_generado DATETIME NULL,
  verifactu_estado VARCHAR(50) NULL,
  verifactu_error TEXT NULL,
  estado ENUM('borrador','emitida','pagada') DEFAULT 'borrador',
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuario_serie_numero (usuario_id, serie, numero),
  KEY idx_facturas_usuario_fecha (usuario_id, fecha)
);
```

## factura_productos
```sql
CREATE TABLE factura_productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  factura_id INT NOT NULL,
  producto_id INT NULL,
  nombre VARCHAR(255) NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  iva_porcentaje DECIMAL(5,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  usuario_id INT NOT NULL,
  KEY idx_fp_factura (factura_id),
  KEY idx_fp_usuario (usuario_id)
);
```

## gastos
```sql
CREATE TABLE gastos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NULL,
  fecha_valor DATE NULL,
  numero VARCHAR(50) NULL,
  base_imponible DECIMAL(10,2) NOT NULL,
  iva DECIMAL(5,2) NULL,
  tipo_iva DECIMAL(5,2) DEFAULT 21.00,
  soportado_deducible TINYINT(1) DEFAULT 1,
  irpf DECIMAL(5,2) NULL,
  total DECIMAL(10,2) NULL,
  estado ENUM('borrador','finalizado','pagado') DEFAULT 'finalizado',
  categoria VARCHAR(100) NULL,
  archivo VARCHAR(255) NULL,
  usuario_id INT NULL,
  KEY idx_gastos_usuario_fecha_estado (usuario_id, fecha, estado)
);
```

## Usuario admin (ejemplo)
```sql
INSERT INTO usuarios (nombre,email,password,rol)
VALUES ('Admin','admin@local','<PEGA_AQUI_HASH_BCRYPT>','admin');
```
Genera el hash con:
```php
<?php echo password_hash('admin123', PASSWORD_DEFAULT);
```
