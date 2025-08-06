Long-Chau-Pharmacy-Management-System-LC-PMS-
A comprehensive, web-based pharmacy management system developed as a university project for SWE30003: Software Architectures and Design. This project digitizes the core operations of a pharmacy, including inventory management, sales processing, and prescription handling.

About this project

This project was built to address the operational inefficiencies faced by Long Chau Pharmacy, a major Vietnamese pharmacy chain (Based on our case study for the Assignment). The system replaces manual, disconnected processes with a centralized, digital platform. It's designed to streamline workflows for managers, pharmacists, and staff, ultimately improving service speed and customer experience

The entire development process involved an initial object-oriented design phase, followed by a practical implementation using a procedural PHP approach.

Technology stack Backend: PHP Database: MySQL Web Server: Apache (via XAMPP) Frontend: HTML, CSS, JavaScript Database Management: phpMyAdmin

Getting Started

To get a local copy up and running, follow these simple steps

Prerequisites

You'll need a local server environment. We used XAMPP for this project PHP already installed

Installation

Clone the repo into your XAMPP's htdocs folder git clone https://github.com/Minhle1212/Long-Chau-Pharmacy-Management-System-LC-PMS-.git

Start XAMPP and ensure the Apache and MySQL modules are running

Create the Database: Navigate to http://localhost/phpmyadmin/. Create a new database named long_chau_db. Import the provided .sql file into this new database.

Configure the Connection Open the settings.php file. Make sure the database credentials match your local setup (the XAMPP default is usually root with no password). You may need to change the database name

Run the Application Open your browser and go to: http://localhost/your_repository_name/

Features Role-Based Access Control: Secure login system for different user roles (Manager, Pharmacist, Staff, Customer).

Inventory Management: Managers can add new products, view the entire stock list, and edit quantities across different branches

Sales Processing: A complete point-of-sale workflow with a dynamic shopping cart (cart.php, sync_cart.php) and order processing (place_order.php).

Prescription Handling: A dedicated workflow for customers to upload prescriptions (upload_prescription.php) and for pharmacists to review and approve them (pharmacist.php).

User Management: Managers can view all staff and customer accounts and create new staff accounts with specific roles (people_manage.php).

Procurement System: Managers can generate low-stock reports and create purchase orders (purchase_order.php) to replenish inventory.

Project Structure A quick look at some of the key files:

/ |-- manage.php # Main dashboard for managers with reporting and management tasks. |-- pharmacist.php # Dashboard for pharmacists to manage prescriptions. |-- people_manage.php # Page for managers to view and create user accounts. |-- cart.php # Displays the user's shopping cart. |-- place_order.php # Handles the final checkout process. |-- add_product.php # Form for adding new products to the inventory. |-- settings.php # Contains all the database connection credentials. |-- header.inc & footer.inc # Reusable header and footer components. |-- /styles/ # Contains all CSS files. |-- /images/ # Contains all static images and product photos.

Authors 🧑‍💻 This project was a group effort by: Hoang Minh Le - (104656973) Phan Nguyen Son - (104814302) Doan Phuong Anh Tuan - (105000814)

Acknowledgments This project is a submission for the SWE30003: Software Architectures and Design unit.

Swinburne University of Technology

Dr.Duc Minh Le (Tutor)