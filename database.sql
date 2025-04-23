-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS qr_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos
USE qr_system;

-- Crear tabla de productos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Crear tabla de códigos QR
CREATE TABLE IF NOT EXISTS qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) NOT NULL UNIQUE,
    product_id INT NOT NULL,
    redeemed BOOLEAN DEFAULT FALSE,
    redeemed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX (code)
) ENGINE=InnoDB;

-- Insertar algunos productos de ejemplo
INSERT INTO products (name, description) VALUES
('Producto 1', 'Descripción del producto 1'),
('Producto 2', 'Descripción del producto 2'),
('Producto 3', 'Descripción del producto 3');
