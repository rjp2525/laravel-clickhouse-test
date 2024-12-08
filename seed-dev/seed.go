package main

import (
	"database/sql"
	"log"
	"math/rand"
	"sync"
	"time"

	_ "github.com/ClickHouse/clickhouse-go/v2"
	"github.com/brianvoe/gofakeit/v6"
	_ "github.com/go-sql-driver/mysql"
	"github.com/oklog/ulid/v2"
)

const (
	chunkSize               = 20000
	totalOrders             = 5000000
	differentAddressPercent = 15
	repeatCustomerPercent   = 10
	maxWorkers              = 10
)

type Customer struct {
	BillingID  string
	ShippingID string
}

var (
	repeatCustomers []Customer
	rnd             = rand.New(rand.NewSource(time.Now().UnixNano()))
)

func main() {
	gofakeit.Seed(0)

	mariaDBDSN := "sail:password@tcp(127.0.0.1:3306)/laravel"
	mariaDB, err := sql.Open("mysql", mariaDBDSN)
	if err != nil {
		log.Fatalf("Failed to connect to MariaDB: %v", err)
	}
	defer mariaDB.Close()

	clickHouseDSN := "clickhouse://laravel_user:secret_password@127.0.0.1:9000/laravel_reporting"
	clickHouse, err := sql.Open("clickhouse", clickHouseDSN)
	if err != nil {
		log.Fatalf("Failed to connect to ClickHouse: %v", err)
	}
	defer clickHouse.Close()

	mariaDB.SetMaxOpenConns(maxWorkers)
	mariaDB.SetMaxIdleConns(maxWorkers / 2)
	clickHouse.SetMaxOpenConns(maxWorkers)
	clickHouse.SetMaxIdleConns(maxWorkers / 2)

	log.Println("Starting data generation...")

	var wg sync.WaitGroup
	semaphore := make(chan struct{}, maxWorkers)
	repeatCustomerLimit := int(float64(totalOrders) * (repeatCustomerPercent / 100.0))

	for i := 0; i < totalOrders; i += chunkSize {
		wg.Add(1)
		semaphore <- struct{}{}
		go func(offset int) {
			defer wg.Done()
			defer func() { <-semaphore }()
			generateAndInsertData(mariaDB, clickHouse, offset, repeatCustomerLimit)
		}(i)
	}

	wg.Wait()
	log.Println("Data generation complete!")
}

func generateAndInsertData(mariaDB, clickHouse *sql.DB, offset, repeatCustomerLimit int) {
	products := fetchProductsAndVariants(mariaDB)

	addresses := make([][]interface{}, 0, chunkSize)
	orders := make([][]interface{}, 0, chunkSize)
	orderRows := make([][]interface{}, 0, chunkSize)

	for i := 0; i < chunkSize; i++ {
		billingID := generateULID()
		shippingID := billingID
		orderDate := gofakeit.DateRange(time.Now().AddDate(-2, 0, 0), time.Now())

		if len(repeatCustomers) > 0 && rnd.Intn(100) < repeatCustomerPercent {
			customer := repeatCustomers[rnd.Intn(len(repeatCustomers))]
			billingID = customer.BillingID
			shippingID = customer.ShippingID
		} else {
			addresses = append(addresses, []interface{}{
				billingID, gofakeit.Name(), gofakeit.Street(), gofakeit.StreetNumber(),
				gofakeit.City(), gofakeit.State(), gofakeit.Country(), gofakeit.Zip(), orderDate, orderDate,
			})

			if rnd.Intn(100) < differentAddressPercent {
				shippingID = generateULID()
				addresses = append(addresses, []interface{}{
					shippingID, gofakeit.Name(), gofakeit.Street(), gofakeit.StreetNumber(),
					gofakeit.City(), gofakeit.State(), gofakeit.Country(), gofakeit.Zip(), orderDate, orderDate,
				})
			}

			if len(repeatCustomers) < repeatCustomerLimit {
				repeatCustomers = append(repeatCustomers, Customer{BillingID: billingID, ShippingID: shippingID})
			}
		}

		orderID := generateULID()
		discount := 0.0
		if rnd.Intn(2) == 1 {
			discount = gofakeit.Float64Range(1, 50)
		}
		total := 0.0

		orders = append(orders, []interface{}{
			orderID, billingID, shippingID, 0, discount, orderDate, orderDate,
		})

		numRows := rnd.Intn(5) + 1
		for j := 0; j < numRows; j++ {
			productIDs := make([]string, 0, len(products))
			for productID := range products {
				productIDs = append(productIDs, productID)
			}

			productID := productIDs[rnd.Intn(len(productIDs))]
			variantIDs := products[productID]
			variantID := variantIDs[rnd.Intn(len(variantIDs))]
			quantity := rnd.Intn(10) + 1
			price := gofakeit.Float64Range(5, 500) * float64(quantity)
			total += price

			orderRows = append(orderRows, []interface{}{
				generateULID(), orderID, productID, variantID, quantity, price, orderDate, orderDate,
			})
		}

		orders[len(orders)-1][3] = total
	}

	var wg sync.WaitGroup
	wg.Add(2)

	go func() {
		defer wg.Done()
		bulkInsert(mariaDB, "INSERT INTO addresses VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", addresses)
		bulkInsert(mariaDB, "INSERT INTO sales_orders VALUES (?, ?, ?, ?, ?, ?, ?)", orders)
		bulkInsert(mariaDB, "INSERT INTO sales_order_rows VALUES (?, ?, ?, ?, ?, ?, ?, ?)", orderRows)
	}()

	go func() {
		defer wg.Done()
		bulkInsert(clickHouse, "INSERT INTO addresses VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", addresses)
		bulkInsert(clickHouse, "INSERT INTO sales_orders VALUES (?, ?, ?, ?, ?, ?, ?)", orders)
		bulkInsert(clickHouse, "INSERT INTO sales_order_rows VALUES (?, ?, ?, ?, ?, ?, ?, ?)", orderRows)
	}()

	wg.Wait()
}

func fetchProductsAndVariants(db *sql.DB) map[string][]string {
	rows, err := db.Query(`
		SELECT p.id AS product_id, pv.id AS variant_id
		FROM products p
		LEFT JOIN product_variants pv ON p.id = pv.product_id
	`)
	if err != nil {
		log.Fatalf("Failed to fetch products and variants: %v", err)
	}
	defer rows.Close()

	products := make(map[string][]string)
	for rows.Next() {
		var productID, variantID string
		if err := rows.Scan(&productID, &variantID); err != nil {
			log.Fatalf("Failed to scan product and variant IDs: %v", err)
		}
		products[productID] = append(products[productID], variantID)
	}

	if len(products) == 0 {
		log.Fatalf("No products with variants found in the database!")
	}

	return products
}

func bulkInsert(db *sql.DB, query string, data [][]interface{}) {
	if len(data) == 0 {
		return
	}

	tx, err := db.Begin()
	if err != nil {
		log.Printf("Failed to begin transaction: %v", err)
		return
	}
	defer tx.Rollback()

	stmt, err := tx.Prepare(query)
	if err != nil {
		log.Printf("Failed to prepare statement: %v", err)
		return
	}
	defer stmt.Close()

	for _, row := range data {
		if _, err := stmt.Exec(row...); err != nil {
			log.Printf("Failed to execute statement: %v", err)
			return
		}
	}

	if err := tx.Commit(); err != nil {
		log.Printf("Failed to commit transaction: %v", err)
	}
}

func generateULID() string {
	return ulid.Make().String()
}
