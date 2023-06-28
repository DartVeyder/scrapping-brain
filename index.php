<?php
include_once('vendor/autoload.php');
include_once('class/scrapper.php');

$scrapper = new Scrapper; 
$scrapper->file_products = "products.xlsx";
$scrapper->init();
