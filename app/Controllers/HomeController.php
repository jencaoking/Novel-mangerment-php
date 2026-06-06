<?php 
namespace App\Controllers; 

use App\Models\ProductModel; 

class HomeController { 
    protected $productModel; 

    public function __construct() { 
        $this->productModel = new ProductModel(); 
    } 

    public function index() { 
        $novels = $this->productModel->getProductsWithFilter('novel', 0, '', 'latest', 1, 8); 
        $music = $this->productModel->getProductsWithFilter('music', 0, '', 'latest', 1, 8); 

        require __DIR__ . '/../../views/index.phtml'; 
    } 
}
