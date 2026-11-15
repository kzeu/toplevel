-- Executar uma única vez no banco toplevel_loja já existente.
USE toplevel_loja;
ALTER TABLE products ADD COLUMN demo_url VARCHAR(1000) NULL AFTER file_path;
