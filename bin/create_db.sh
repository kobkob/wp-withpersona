#!/bin/bash

# Script to create a MySQL database and a user with a password

# Prompt for database name, username, and password
read -p "Enter the name of the database: " dbname
read -p "Enter the MySQL username: " dbuser
read -sp "Enter the password for the user: " dbpass
echo

# MySQL root user credentials
read -p "Enter MySQL root username: " rootuser
read -sp "Enter password for MySQL root user: " rootpass
echo

# Commands to create database and user
mysql -u "$rootuser" -p"$rootpass" -e "CREATE DATABASE $dbname;"
mysql -u "$rootuser" -p"$rootpass" -e "CREATE USER '$dbuser'@'localhost' IDENTIFIED BY '$dbpass';"
mysql -u "$rootuser" -p"$rootpass" -e "GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@'localhost';"
mysql -u "$rootuser" -p"$rootpass" -e "FLUSH PRIVILEGES;"

# Output success message
echo "Database '$dbname' and user '$dbuser' created successfully."
