# Experiment: Testing Speed for Generating Report Data & Querying Large Datasets

This experiment was to compare the performance of MariaDB and ClickHouse for generating report data and querying large datasets. The primary goal was to evaluate how each one handles large-scale queries and to measure the time to generate detailed reports over time.

## Prerequisites

-   Docker
-   PHP 8.3+
-   Go (for running the seeder, this was faster)
-   [ClickHouse CLI](https://clickhouse.com/docs/en/interfaces/cli)

## Getting Started

1. Start the docker containers

```bash
sail up -d
```

2. Seed products/variants in MariaDB

```bash
sail artisan db:seed ProductSeeder
```

3. Set up the ClickHouse schema

```bash
clickhouse-client --host=localhost --user=laravel_user --password=secret_password --database=laravel_reporting < seed-dev/schema-clickhouse.sql
```

4. Replicate the products/variants in ClickHouse

```bash
sail artisan app:migrate-to-clickhouse
```

5. Generate large dataset of orders and order rows (this will take a while to generate 5 million)

```bash
go run seed-dev/seeder.go
```

## Performance Testing

1. Query Performance

```sql
SELECT COUNT(*) FROM sales_orders;
```

```sql
SELECT COUNT(*) FROM sales_order_rows;
```

**Observations**

-   MariaDB: ~13 seconds
-   ClickHouse: 5-27ms consistently

2. Report Generation

-   Orders grouped by day
-   Top shipping locations (country, state, city)
-   Number of products sold by SKU
    Report can be viewed at `http://localhost/reports/daily`
    > **Note:** The PDF generation is the bottleneck, taking significant time for large reports (e.g., 343+ pages). Additionally, formatting large JSON responses also impacts performance.

## Results

-   **Query Performance:** ClickHouse significantly outperforms MariaDB, there isn't even a comparison here
-   **PDF Generation:** Despite the database query improvements, the PDF rendering process remains a bottleneck due to the sheer volume of data (~343+ pages)
-   **JSON Formatting:** Returning large datasets as JSON responses introduces latency, which can probably be addressed by pagination or streaming responses

## Conclusion

This experiment successfully demonstrated the advantages of using ClickHouse for large-scale analytics and reporting. ClickHouse consistently handled complex queries on large datasets with minimal latency (~10-20ms), compared to MariaDB's slower performance on just querying the primary ID column (~13 seconds)
