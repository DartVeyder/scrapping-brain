<?php

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use SimpleExcel\SimpleExcel;

class Scrapper
{
    private $client;
    public $file_products;
    public function __construct()
    { 
        // Створюємо екземпляр Guzzle HTTP-клієнта
        $this->client = new Client();
    }

    public function init()
    {
        $data = [];
        $products = $this->upload_file_products();
        foreach ($products as $product) {
            $data[] = [
                'name' => $product['name'],
                'code' => $product['code'],
                'article' => $product['article'],
                'data' => $this->get_contents($product['url'])
            ];
        }
        echo "<pre>";
        print_r($data);
    }

    private function upload_file_products()
    {
        $data = [
            [
            "article" => 'SC620I',
            "code" => '4444',
            "name" => 'Пристрій безперебійного живлення Smart-UPS SC 620VA APC (SC620I)',
            "url" => "https://brain.com.ua/ukr/Pristriy_bezperebiynogo_jhivlennya_APC_Smart-UPS_SC_620VA_SC620I-p23160.html"
        ]
    ];
        return $data;
    }

    private function get_contents($url)
    {
        // Виконуємо GET-запит до сторінки
        $response = $this->client->request('GET', $url);
        // Отримуємо HTML-код сторінки з відповіді
        $html = $response->getBody()->getContents();
        $dom = new Crawler($html);
        // Вибираємо всі елементи <img> на сторінці та отримуємо значення атрибуту "src"
        $pictures = $this->get_pictures($dom);
        $description = $this->get_description($dom);
        $attributes = $this->get_attributes($dom);

        return [
            'pictures' =>$pictures,
            'description' => $description,
            'attributes' => $attributes
        ];
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
