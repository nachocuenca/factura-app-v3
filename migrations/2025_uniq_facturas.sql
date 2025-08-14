ALTER TABLE facturas
  ADD UNIQUE KEY uq_usuario_serie_numero (usuario_id, serie, numero);
