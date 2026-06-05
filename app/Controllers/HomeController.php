<?php
namespace App\Controllers;
class HomeController {
    public function index() {
        echo "<h1>Welcome to BookMusic Mall</h1>";
        echo "<p>This is from Controller</p>";
    }
    public function show($id) {
        echo "<h1>Product Detail</h1>";
        echo "<p>Product ID: " . htmlspecialchars($id) . "</p>";
    }
}
