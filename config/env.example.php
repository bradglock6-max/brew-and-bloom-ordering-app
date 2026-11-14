<?php
declare(strict_types=1);

const DB_HOST  = '127.0.0.1';
const DB_NAME  = 'your_database_name';
const DB_USER  = 'your_db_user';
const DB_PASS  = 'your_db_password';
const APP_ENV  = 'development'; // use 'production' on live servers;
const BASE_URL = '/brew-and-bloom-app';

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_errors.log');
