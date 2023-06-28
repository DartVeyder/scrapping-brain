<?php

use GuzzleHttp\Client;
use Shuchkin\SimpleXLSX;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DomCrawler\Crawler;

class Scrapper
{ 
    public $file_products; 
    public $file_template_export; 
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
             
            $url = str_replace("opt." , "" , $product['URL']);
            $article = $product['Article'];
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
            echo "$key $status_code $article  </br>";
            $data_export_xlsx[] = $this->get_formate_xslx($template_export, $item);
          //  $data_export_xlsx[] = array_merge($product, $this->get_formate_xslx($content_data) );
            
        } 
       
        $this->save_xlsx($data_export_xlsx, $template_export); 
        $this->save_json($data);
 
    }

    private function save_json($data){
        $date = time();
        $jsonString = json_encode($data,JSON_UNESCAPED_UNICODE);
        $file = fopen('files/file'. $date.'.json', 'w');
        fwrite($file, $jsonString);
        fclose($file);
    }

    private function save_xlsx($data, $header){
        $date = time(); 
        array_unshift($data, $header);
       
        $xlsx = Shuchkin\SimpleXLSXGen::fromArray( $data );
        $xlsx->saveAs('files/export_products_'. $date.'.xlsx');
    }
    /*private function get_formate_xslx($data){
        $pictures = implode(", ", $data['pictures']);
        return [$pictures,  $data['description']];
    }*/
    private function get_formate_xslx($template_export, $data){
        $array = [];
        foreach ($template_export as $key => $col) {
            switch ($key) {
                case 0:
                    $array[] = $data['article'];
                    break; 
                case 1:
                    $array[] = $data['name'];
                break; 
                case 6:
                    $array[] = $data['data']['description'];
                break; 
                default:
                    $array[]  = '';
                    break;
            }
        }
        return $array;
    }
    private function save_csv($data){

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
                $pictures = $this->get_pictures($dom);
                $description = $this->get_description($dom);
                $attributes = $this->get_attributes($dom);
                
                $result['data'] = [ 
                    'pictures' =>$pictures,
                    'description' => $description,
                    'attributes' => $attributes
                ];
            } 
        } catch (ClientException $e) {
            // Handle client-side errors (e.g., 404)
            $statusCode = $e->getResponse()->getStatusCode();
        } catch (\Exception $e) {
            // Handle other exceptions
            echo "An error occurred: " . $e->getMessage();
        } 

        $result['status_code'] =  $statusCode;
 
        return $result;
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
