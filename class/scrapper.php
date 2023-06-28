<?php

use SimpleCSV;
use GuzzleHttp\Client;
use Shuchkin\SimpleXLSX;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DomCrawler\Crawler;

class Scrapper
{ 
    public $file_products; 
    public $file_template_export; 
    private $url_site =" https://brain.com.ua";
    public function init()
    {
        $data = [];
        $data_export_xlsx = [];
        $products = $this->upload_file_products();
        $template_export = $this->file_template_export()[0];
 
        $header = array_shift($products);
        unset($header[7]);
        
        foreach ($products as $key => $product) {
            unset($product[7]);   
            $product = array_combine($header, $product);
             
            $article = $product['Article'];
            
            $url = $this->search_product($product['Code'], $product['Article']);
            if($url == ''){
                $url = str_replace("opt." , "" , $product['URL']);
            }
            $contents = $this->get_contents( $url);
            $content_data = $contents['data'];
            $status_code = $contents['status_code']; 

            $item = [
                'name' => $product['Name'],
                'code' => $product['Code'],
                'article' =>  $article,
                'url' => $url,
                'status_code' => $status_code,
                'data' =>  $content_data
            ];
            $data[] = $item;
            $date = date("Y-m-d H:i:s");
            $text =  "$key. $date $status_code $article $url";
            echo $text .' <br>';
            $this->logs($text);
            $data_export_xlsx[] = $this->get_formate_xslx($template_export, array_merge($product, $content_data));
          //  $data_export_xlsx[] = array_merge($product, $this->get_formate_xslx($content_data) );
          $this->save_csv($data_export_xlsx, $template_export); 
          $this->save_xlsx($data_export_xlsx, $template_export); 
          $this->save_json($data);
           
        } 
        
    }

    private function save_json($data){
        //$date = time();
        $date = '';
        $jsonString = json_encode($data,JSON_UNESCAPED_UNICODE);
        $file = fopen('files/export_products_'. $date.'.json', 'w');
        fwrite($file, $jsonString);
        fclose($file);
    }

    private function save_xlsx($data, $header){
        //$date = time();
        $date = '';
        array_unshift($data, $header);
       
        $xlsx = Shuchkin\SimpleXLSXGen::fromArray( $data );
        $xlsx->saveAs('files/export_products_'. $date.'.xlsx');
    }
    private function save_csv($data, $header){
       //$date = time();
       $date = '';
        array_unshift($data, $header);
       
        $csv = SimpleCSV::export( $data ); 
        $file = fopen('files/export_products_'. $date.'.csv', 'w');
        fwrite($file, $csv);
        fclose($file);
    }
    private function get_formate_xslx($template_export, $data){
        $array = []; 
        foreach ($template_export as $key => $col) {
            switch ($key) {
                case 0:
                    $array[] = $data['Article'];
                    break; 
                case 1:
                    $array[] = $data['Name'];
                break; 
                case 6:
                    $array[] = $data['description'];
                break; 
                case 2:
                    $array[] = 1;
                break; 
                case 3:
                    $array[] = 0;
                break;
                case 4:
                    $array[] = 'visible';
                break;
                case 5:
                    $array[] = $data['description'];
                break;
                case 9:
                    $array[] = 'taxable';
                break;
                case 11:
                    $array[] = 1;
                break;
                case 14:
                    $array[] = 0;
                break;
                case 15:
                    $array[] = 0;
                break;
                case 20:
                    $array[] = 1;
                break;
                case 23:
                    $array[] = $data['RetailPrice'];
                break;
                case 24:
                    $array[] = implode(">", $data['categories']);
                break;
                case 27:
                    $array[] =  implode(", ", $data['pictures']);
                break;
                case 36:
                    $array[] =  0;
                break;
                default:
                    $array[]  = '';
                    break;
            }
        }
        return $array;
    }
    

    private function logs($text){
        $path = 'logs/log.txt'; 
        $file = fopen( $path, 'a+');
        fwrite($file, $text . "\n");
        fclose($file);
    }

    private function file_template_export()
    {
        if ( $xlsx = SimpleXLSX::parse($this->file_template_export) ) {
            $data = $xlsx->rows(); 
        } else {
            $data = SimpleXLSX::parseError();
        } 
        return $data;
    }

    private function upload_file_products()
    {
        if ( $xlsx = SimpleXLSX::parse($this->file_products) ) {
            $data = $xlsx->rows(); 
        } else {
            $data = SimpleXLSX::parseError();
        } 
        return $data;
    }

    private function search_product($code, $article){
        $url = "https://brain.com.ua/ukr/search/?Search=$code";
        $client = new Client();
        try {
            // Make an HTTP GET request
            $response = $client->request('GET', $url);
        
            // Get the status code from the response
            $statusCode = $response->getStatusCode(); 
            if ($statusCode == 200) {
                 // Отримуємо HTML-код сторінки з відповіді
                $html = $response->getBody()->getContents();
                $dom = new Crawler($html);
                return $this->get_product_url($dom, $article);
               
            } 
        } catch (ClientException $e) {
            // Handle client-side errors (e.g., 404)
            $statusCode = $e->getResponse()->getStatusCode();
        } catch (\Exception $e) {
            // Handle other exceptions
            echo "An error occurred: " . $e->getMessage();
        } 
    }

    private function get_product_url($dom,$article){
        $product = $dom->filter('.description-wrapper a')->eq(0) ; 
        $url = "https://brain.com.ua".$product->attr('href');
        $name = $product->text();
        if (stripos($name, $article) !== false) {
            return $url;
        }else{
            return false;
        }
         
       
    }

    private function get_contents($url)
    {
        $result = [];
        $client = new Client();
        // Виконуємо GET-запит до сторінки
        
        try {
            // Make an HTTP GET request
            $response = $client->request('GET', $url);
        
            // Get the status code from the response
            $statusCode = $response->getStatusCode(); 
            if ($statusCode == 200) {
                 // Отримуємо HTML-код сторінки з відповіді
                $html = $response->getBody()->getContents();
            
                $dom = new Crawler($html);
                // Вибираємо всі елементи <img> на сторінці та отримуємо значення атрибуту "src"
                $categories = $this->get_categories($dom);
                $pictures = $this->get_pictures($dom);
                $description = $this->get_description($dom);
                $attributes = $this->get_attributes($dom);
                
                $result['data'] = [ 
                    'pictures' =>$pictures,
                    'description' => $description,
                    'attributes' => $attributes,
                    'categories' => $categories
                ];
            } 
        } catch (ClientException $e) {
            // Handle client-side errors (e.g., 404)
            $statusCode = $e->getResponse()->getStatusCode();
            $result['data'] = [ 
                'pictures' => [''],
                'description' => '',
                'attributes' => '',
                'categories' => ''
            ];
        } catch (\Exception $e) {
            // Handle other exceptions
            echo "An error occurred: " . $e->getMessage();
        } 

        $result['status_code'] =  $statusCode;
 
        return $result;
    }
 
    private function get_categories($dom)
    {
        $data = [];
        $ul = $dom->filter('.br-breadcrumbs-list') ;
        $lis = $ul->filterXPath('//li/a');
       
        foreach ($lis as $key => $li) {
            if($key <  2 ){
                continue;
            } 
            
            $data[] = trim($li->textContent);
             
        } 

        return $data;
    }

    private function get_pictures($dom)
    {
        return $dom->filter('.br-main-img')->extract(['src']);
    }

    private function get_description($dom)
    {
        return $dom->filter('.br-pr-about')->text();
    }

    private function get_attributes($dom)
    {
        $data = [];
        $attributes = [];
        // Вибираємо елемент <div> з класом "br-pr-chr-item"
        $divElement = $dom->filter('.br-pr-chr-item');

        // Вибираємо всі елементи <span> в межах вибраного <div>
        $spanElements = $divElement->filterXPath('//div/span');

        // Перебираємо кожен елемент <span> та отримуємо назву та значення атрибутів
        foreach ($spanElements as $spanElement) {
            $name = str_replace("\n" , "" ,trim($spanElement->textContent)) ; 
            // Додаємо назву та значення до асоціативного масиву
            $data[] = $name;
        }  

        for ($i=0; $i < count($data) ; $i++) { 
            if ($i % 2 != 0) {
                continue;
            } 
            $name = $data[$i];
            $value = $data[$i +1 ];
            
            $attributes[] = [
                'name' => $name,
                'value' => $value
            ];
        } 
        return $attributes;
    }
}
