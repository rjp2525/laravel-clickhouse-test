DROP TABLE IF EXISTS addresses;
CREATE TABLE addresses (
  id String,
  name String,
  address_1 String,
  address_2 String,
  city String,
  state String,
  country String,
  postal_code String,
  created_at DateTime DEFAULT now(),
  updated_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY id;

DROP TABLE IF EXISTS product_variants;
CREATE TABLE product_variants (
  id String,
  name String,
  sku String,
  upc Nullable(String),
  price Nullable(Float64),
  product_id String,
  meta JSON,
  created_at DateTime DEFAULT now(),
  updated_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY (product_id, id);

DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id String,
  name String,
  created_at DateTime DEFAULT now(),
  updated_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY id;

DROP TABLE IF EXISTS sales_order_rows;
CREATE TABLE sales_order_rows (
  id String,
  sales_order_id String,
  product_id String,
  product_variant_id String,
  quantity UInt32,
  price Float64,
  created_at DateTime DEFAULT now(),
  updated_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY (sales_order_id, id);

DROP TABLE IF EXISTS sales_orders;
CREATE TABLE sales_orders (
  id String,
  shipping_address_id String,
  billing_address_id String,
  total Float64,
  discount Nullable(Float64),
  created_at DateTime DEFAULT now(),
  updated_at DateTime DEFAULT now()
) ENGINE = MergeTree()
ORDER BY id;

DROP TABLE IF EXISTS product_variants;
CREATE TABLE product_variants (
    id String,
    name String,
    sku String,
    upc String DEFAULT NULL,
    price Int32 DEFAULT NULL,
    product_id String,
    meta String DEFAULT NULL,
    created_at DateTime DEFAULT NULL,
    updated_at DateTime DEFAULT NULL
)
ENGINE = MergeTree
PRIMARY KEY id
ORDER BY (id);
