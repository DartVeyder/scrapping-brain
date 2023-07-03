<?php
set_time_limit(0);
include_once('vendor/autoload.php');
include_once('class/scrapper.php');
include_once('class/PhotoUploader.php');
$scrapper = new Scrapper; 
/*$scrapper->file_products = "products2.xlsx";
$scrapper->file_template_export = "template_export.xlsx";
$scrapper->num_products = 3400;
$scrapper->init();*/
$scrapper->upload_price_xlsx('files/export_products_1688238682.xlsx');
//$scrapper->upload_pictures('files/export/products_full.xlsx');
