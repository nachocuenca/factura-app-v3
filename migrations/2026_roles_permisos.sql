-- Roles y permisos
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE modulos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE roles_modulos (
  role_id INT NOT NULL,
  modulo_id INT NOT NULL,
  PRIMARY KEY (role_id, modulo_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
);

ALTER TABLE usuarios ADD COLUMN role_id INT NULL;
ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_role FOREIGN KEY (role_id) REFERENCES roles(id);

INSERT INTO roles (nombre) VALUES ('admin'), ('usuario');
INSERT INTO modulos (nombre) VALUES ('dashboard'), ('clientes'), ('productos'), ('facturas'), ('gastos'), ('config');
INSERT INTO roles_modulos (role_id, modulo_id)
  SELECT r.id, m.id FROM roles r CROSS JOIN modulos m WHERE r.nombre='admin';
INSERT INTO roles_modulos (role_id, modulo_id)
  SELECT r.id, m.id FROM roles r JOIN modulos m ON m.nombre IN ('dashboard','clientes','productos','facturas','gastos')
  WHERE r.nombre='usuario';
UPDATE usuarios u JOIN roles r ON r.nombre = u.rol SET u.role_id = r.id;
