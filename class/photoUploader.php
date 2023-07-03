<?php 
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\Utils;

class PhotoUploader
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function uploadPhotos(array $imageUrls, $destinationPath)
    {
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $promises = [];
        $images = [];
        foreach ($imageUrls as $imageUrl) {
            $image_name = explode('/', $imageUrl);
            $image = 'https://evse.in.ua/wp-content/uploads/woocommerce_uploads/'. end($image_name);
            $promises[] = $this->client->getAsync($imageUrl, ['sink' => $destinationPath . '/'. end($image_name)]);
            $images[] = $image;
        }

        $results = Promise\Utils::unwrap($promises);
        Promise\Utils::settle($promises)->wait();
        //dd($results,2);
        return $images;
    }
}
