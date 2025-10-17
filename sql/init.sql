-- Tạo bảng products
CREATE TABLE IF NOT EXISTS products (
  id SERIAL PRIMARY KEY,
  sku VARCHAR(64) UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  quantity INT NOT NULL DEFAULT 0,
  unit_price NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger cập nhật updated_at tự động
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS set_timestamp ON products;
CREATE TRIGGER set_timestamp
BEFORE UPDATE ON products
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

-- Thêm dữ liệu mẫu
INSERT INTO products (sku, name, description, quantity, unit_price) VALUES
('SKU001', 'Áo sơ mi', 'Áo sơ mi nam tay dài', 50, 199000),
('SKU002', 'Quần jean', 'Quần jean xanh nam', 30, 299000),
('SKU003', 'Giày thể thao', 'Giày sneaker nữ', 20, 499000)
ON CONFLICT (sku) DO NOTHING;
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Trong init.sql
ALTER TABLE products ADD COLUMN category_id INT;
ALTER TABLE products ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;