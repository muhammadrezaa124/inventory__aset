-- ======================================================
-- Database: inventori_db
-- Sistem Informasi Inventory Aset Berbasis Web
-- ======================================================

-- 1. Buat database (jika belum ada)
CREATE DATABASE IF NOT EXISTS inventori_db;
USE inventori_db;

-- ======================================================
-- 2. Tabel Master Data
-- ======================================================

-- 2.1 Tipe Item (Kategori Barang)
CREATE TABLE IF NOT EXISTS m_item_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2.2 Data Barang (Master Item)
CREATE TABLE IF NOT EXISTS m_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_type_id) REFERENCES m_item_types(id) ON DELETE CASCADE
);

-- 2.3 Gedung
CREATE TABLE IF NOT EXISTS m_buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2.4 Ruangan
CREATE TABLE IF NOT EXISTS m_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES m_buildings(id) ON DELETE CASCADE
);

-- 2.5 Jenis Transaksi
CREATE TABLE IF NOT EXISTS m_transaction_type (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- 3. Tabel Transaksi & Inventory
-- ======================================================

-- 3.1 Stok Barang (Inventory)
CREATE TABLE IF NOT EXISTS t_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    barcode VARCHAR(50) UNIQUE NOT NULL,
    total_quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(12,2) NOT NULL,
    expired_date DATE DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('good', 'damaged', 'expired') DEFAULT 'good',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES m_items(id)
);

-- 3.2 Header Transaksi Inventory
CREATE TABLE IF NOT EXISTS t_inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type_id INT NOT NULL,
    budget DECIMAL(12,2) DEFAULT 0,
    realization DECIMAL(12,2) DEFAULT 0,
    evidence_file VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_type_id) REFERENCES m_transaction_type(id)
);

-- 3.3 Detail Transaksi (Barang yang terlibat)
CREATE TABLE IF NOT EXISTS t_inventory_transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES t_inventory_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES t_inventory(id)
);

-- 3.4 Mapping Barang ke Ruangan (Lokasi)
CREATE TABLE IF NOT EXISTS t_inventory_room (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    room_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES t_inventory(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES m_rooms(id)
);

-- ======================================================
-- 4. Data Contoh (Seeders)
-- ======================================================

-- 4.1 Tipe Item
INSERT INTO m_item_types (name) VALUES 
('Elektronik'),
('Furniture'),
('Lab Perangkat'),
('Alat Tulis');

-- 4.2 Gedung
INSERT INTO m_buildings (name) VALUES 
('Gedung A'),
('Gedung B'),
('Gedung C');

-- 4.3 Ruangan (dengan relasi ke gedung)
INSERT INTO m_rooms (building_id, name) VALUES 
(1, 'Lab Komputer 1'),
(1, 'Lab Komputer 2'),
(1, 'Ruang Guru'),
(2, 'Laboratorium IPA'),
(2, 'Perpustakaan'),
(3, 'Ruang Serbaguna');

-- 4.4 Jenis Transaksi
INSERT INTO m_transaction_type (name, code) VALUES 
('Pembelian', 'PUR'),
('Hibah', 'GRT'),
('Mutasi', 'MUT'),
('Penghapusan', 'DIS');

-- 4.5 Master Item (Barang)
INSERT INTO m_items (item_type_id, name, description) VALUES 
(1, 'Komputer Desktop', 'PC Core i5 RAM 8GB'),
(1, 'Laptop', 'Lenovo ThinkPad'),
(1, 'Proyektor', 'LCD Proyektor 4000 lumen'),
(2, 'Meja Kayu', 'Meja guru ukuran besar'),
(2, 'Kursi Lipat', 'Kursi plastik lipat'),
(3, 'Mikroskop', 'Mikroskop binokuler'),
(4, 'Spidol Whiteboard', 'Spidol hitam');

-- 4.6 Inventory (Stok awal)
-- Barcode sengaja dibuat unik
INSERT INTO t_inventory (item_id, barcode, total_quantity, price, status, expired_date) VALUES 
(1, 'BRC-1001', 15, 5500000, 'good', NULL),
(2, 'BRC-1002', 8, 7200000, 'good', NULL),
(3, 'BRC-1003', 4, 3800000, 'good', NULL),
(4, 'BRC-1004', 10, 850000, 'good', NULL),
(5, 'BRC-1005', 25, 120000, 'good', NULL),
(6, 'BRC-1006', 3, 2500000, 'damaged', '2025-12-31'),
(7, 'BRC-1007', 50, 15000, 'good', NULL);

-- 4.7 Mapping barang ke ruangan (distribusi awal)
INSERT INTO t_inventory_room (inventory_id, room_id, quantity) VALUES 
(1, 1, 10),  -- Komputer Desktop di Lab Komputer 1 sebanyak 10
(1, 2, 5),   -- Komputer Desktop di Lab Komputer 2 sebanyak 5
(2, 1, 5),   -- Laptop di Lab Komputer 1
(3, 4, 2),   -- Proyektor di Laboratorium IPA
(4, 3, 5),   -- Meja Kayu di Ruang Guru
(5, 3, 10),  -- Kursi Lipat di Ruang Guru
(6, 4, 1);   -- Mikroskop di Laboratorium IPA (rusak)

-- 4.8 Contoh Transaksi (opsional, untuk testing)
INSERT INTO t_inventory_transactions (transaction_number, transaction_date, transaction_type_id, budget, realization, notes) VALUES
('TRX-20250301-001', '2025-03-01', 1, 100000000, 82500000, 'Pembelian komputer baru'),
('TRX-20250315-002', '2025-03-15', 4, 0, 0, 'Penghapusan kursi rusak');

-- Detail transaksi 1: Pembelian komputer
INSERT INTO t_inventory_transaction_details (transaction_id, inventory_id, quantity, price) VALUES
(1, 1, 10, 5500000),  -- 10 komputer @5.5jt
(1, 2, 5, 7200000);   -- 5 laptop @7.2jt

-- Update stok setelah transaksi 1 (Pembelian) - stok bertambah
UPDATE t_inventory SET total_quantity = total_quantity + 10 WHERE id = 1;
UPDATE t_inventory SET total_quantity = total_quantity + 5 WHERE id = 2;

-- ======================================================
-- 5. Selesai
-- ======================================================-- ======================================================
-- Database: inventori_db
-- Sistem Informasi Inventory Aset Berbasis Web
-- ======================================================

-- 1. Buat database (jika belum ada)
CREATE DATABASE IF NOT EXISTS inventori_db;
USE inventori_db;

-- ======================================================
-- 2. Tabel Master Data
-- ======================================================

-- 2.1 Tipe Item (Kategori Barang)
CREATE TABLE IF NOT EXISTS m_item_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2.2 Data Barang (Master Item)
CREATE TABLE IF NOT EXISTS m_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_type_id) REFERENCES m_item_types(id) ON DELETE CASCADE
);

-- 2.3 Gedung
CREATE TABLE IF NOT EXISTS m_buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2.4 Ruangan
CREATE TABLE IF NOT EXISTS m_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES m_buildings(id) ON DELETE CASCADE
);

-- 2.5 Jenis Transaksi
CREATE TABLE IF NOT EXISTS m_transaction_type (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================================
-- 3. Tabel Transaksi & Inventory
-- ======================================================

-- 3.1 Stok Barang (Inventory)
CREATE TABLE IF NOT EXISTS t_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    barcode VARCHAR(50) UNIQUE NOT NULL,
    total_quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(12,2) NOT NULL,
    expired_date DATE DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('good', 'damaged', 'expired') DEFAULT 'good',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES m_items(id)
);

-- 3.2 Header Transaksi Inventory
CREATE TABLE IF NOT EXISTS t_inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type_id INT NOT NULL,
    budget DECIMAL(12,2) DEFAULT 0,
    realization DECIMAL(12,2) DEFAULT 0,
    evidence_file VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_type_id) REFERENCES m_transaction_type(id)
);

-- 3.3 Detail Transaksi (Barang yang terlibat)
CREATE TABLE IF NOT EXISTS t_inventory_transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES t_inventory_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES t_inventory(id)
);

-- 3.4 Mapping Barang ke Ruangan (Lokasi)
CREATE TABLE IF NOT EXISTS t_inventory_room (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    room_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES t_inventory(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES m_rooms(id)
);

-- ======================================================
-- 4. Data Contoh (Seeders)
-- ======================================================

-- 4.1 Tipe Item
INSERT INTO m_item_types (name) VALUES 
('Elektronik'),
('Furniture'),
('Lab Perangkat'),
('Alat Tulis');

-- 4.2 Gedung
INSERT INTO m_buildings (name) VALUES 
('Gedung A'),
('Gedung B'),
('Gedung C');

-- 4.3 Ruangan (dengan relasi ke gedung)
INSERT INTO m_rooms (building_id, name) VALUES 
(1, 'Lab Komputer 1'),
(1, 'Lab Komputer 2'),
(1, 'Ruang Guru'),
(2, 'Laboratorium IPA'),
(2, 'Perpustakaan'),
(3, 'Ruang Serbaguna');

-- 4.4 Jenis Transaksi
INSERT INTO m_transaction_type (name, code) VALUES 
('Pembelian', 'PUR'),
('Hibah', 'GRT'),
('Mutasi', 'MUT'),
('Penghapusan', 'DIS');

-- 4.5 Master Item (Barang)
INSERT INTO m_items (item_type_id, name, description) VALUES 
(1, 'Komputer Desktop', 'PC Core i5 RAM 8GB'),
(1, 'Laptop', 'Lenovo ThinkPad'),
(1, 'Proyektor', 'LCD Proyektor 4000 lumen'),
(2, 'Meja Kayu', 'Meja guru ukuran besar'),
(2, 'Kursi Lipat', 'Kursi plastik lipat'),
(3, 'Mikroskop', 'Mikroskop binokuler'),
(4, 'Spidol Whiteboard', 'Spidol hitam');

-- 4.6 Inventory (Stok awal)
-- Barcode sengaja dibuat unik
INSERT INTO t_inventory (item_id, barcode, total_quantity, price, status, expired_date) VALUES 
(1, 'BRC-1001', 15, 5500000, 'good', NULL),
(2, 'BRC-1002', 8, 7200000, 'good', NULL),
(3, 'BRC-1003', 4, 3800000, 'good', NULL),
(4, 'BRC-1004', 10, 850000, 'good', NULL),
(5, 'BRC-1005', 25, 120000, 'good', NULL),
(6, 'BRC-1006', 3, 2500000, 'damaged', '2025-12-31'),
(7, 'BRC-1007', 50, 15000, 'good', NULL);

-- 4.7 Mapping barang ke ruangan (distribusi awal)
INSERT INTO t_inventory_room (inventory_id, room_id, quantity) VALUES 
(1, 1, 10),  -- Komputer Desktop di Lab Komputer 1 sebanyak 10
(1, 2, 5),   -- Komputer Desktop di Lab Komputer 2 sebanyak 5
(2, 1, 5),   -- Laptop di Lab Komputer 1
(3, 4, 2),   -- Proyektor di Laboratorium IPA
(4, 3, 5),   -- Meja Kayu di Ruang Guru
(5, 3, 10),  -- Kursi Lipat di Ruang Guru
(6, 4, 1);   -- Mikroskop di Laboratorium IPA (rusak)

-- 4.8 Contoh Transaksi (opsional, untuk testing)
INSERT INTO t_inventory_transactions (transaction_number, transaction_date, transaction_type_id, budget, realization, notes) VALUES
('TRX-20250301-001', '2025-03-01', 1, 100000000, 82500000, 'Pembelian komputer baru'),
('TRX-20250315-002', '2025-03-15', 4, 0, 0, 'Penghapusan kursi rusak');

-- Detail transaksi 1: Pembelian komputer
INSERT INTO t_inventory_transaction_details (transaction_id, inventory_id, quantity, price) VALUES
(1, 1, 10, 5500000),  -- 10 komputer @5.5jt
(1, 2, 5, 7200000);   -- 5 laptop @7.2jt

-- Update stok setelah transaksi 1 (Pembelian) - stok bertambah
UPDATE t_inventory SET total_quantity = total_quantity + 10 WHERE id = 1;
UPDATE t_inventory SET total_quantity = total_quantity + 5 WHERE id = 2;

-- ======================================================
-- 5. Selesai
-- ======================================================