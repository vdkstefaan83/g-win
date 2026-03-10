-- Add company_name and vat_number to orders table
ALTER TABLE orders
    ADD COLUMN company_name VARCHAR(255) NULL AFTER billing_address,
    ADD COLUMN vat_number VARCHAR(50) NULL AFTER company_name;
