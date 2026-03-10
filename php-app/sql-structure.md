# Database Structure

## admins

```sql
CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## categories

```sql
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## products

```sql
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description LONGTEXT,
  price INT DEFAULT 0,
  whatsapp_number VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  badge_status VARCHAR(50) DEFAULT 'none',
  publish_at DATETIME NULL,
  view_count INT DEFAULT 0,
  telegram_click_count INT DEFAULT 0,
  og_image_path VARCHAR(255),
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX (is_active),
  INDEX (publish_at),
  INDEX (createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Kolom notes

| Kolom | Keterangan |
|---|---|
| `badge_status` | Nilai: `none`, `hot`, `ready`, `limited`, `new` |
| `is_active` | `1` = aktif, `0` = nonaktif |
| `publish_at` | `NULL` atau `<= NOW()` = langsung tayang |
| `price` | Saat ini selalu `0` (hardcoded di app) |
| `whatsapp_number` | Link/username Telegram untuk order |
| `og_image_path` | Path OG image untuk social sharing |

## product_images

```sql
CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX (product_id),
  INDEX (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Notes

- Maks **50 gambar** per produk (enforced di app logic)
- Diurutkan berdasarkan `sort_order ASC`, `id ASC`
- Format: JPG, PNG, WEBP

## Relasi

```
admins          (standalone)
categories  <── products.category_id
products    <── product_images.product_id (CASCADE delete)
```
