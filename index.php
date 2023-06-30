<?php
set_time_limit(0);
include_once('vendor/autoload.php');
include_once('class/scrapper.php');

$scrapper = new Scrapper; 
$scrapper->file_products = "products1.xlsx";
$scrapper->file_template_export = "template_export.xlsx";
$scrapper->num_products = 3;
$scrapper->init();
