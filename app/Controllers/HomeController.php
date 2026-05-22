<?php
namespace App\Controllers;

use Forge\Core\Controller;
use Forge\Core\App;
use App\Models\Book;
use App\Models\Rental;

/**
 * HomeController - Core application controller using CodeForge ActiveRecord Models.
 */
class HomeController extends Controller {
    
    public function index() {
        $app = App::getInstance();
        $guard = $app->get('guard');

        // Throttle this page request (max 100 requests per minute)
        $guard->throttle('home_page', 100, 60);

        try {
            // Fetch all records using ActiveRecord Model
            $books = Book::all();
            
            // Calculate total copies across catalog
            $totalBooks = 0;
            foreach ($books as $book) {
                $totalBooks += (int)$book->total_copies;
            }
            
            // Query outstanding rentals using ActiveRecord Query Builder
            $activeRentalsCount = Rental::query()->where('status', '=', 'rented')->get();
            
            // Render the dashboard using optional master layout
            $this->view('home', [
                'pageTitle' => 'CodeForge-Engine | Developer Dashboard',
                'books' => $books,
                'totalBooks' => $totalBooks,
                'activeRentalsCount' => count($activeRentalsCount)
            ], 'app'); // using 'app' layout
        } catch (\Exception $e) {
            // Render view with error detail if offline
            $this->view('home', [
                'pageTitle' => 'CodeForge-Engine | Developer Dashboard (Offline)',
                'books' => [],
                'totalBooks' => 0,
                'activeRentalsCount' => 0,
                'dbError' => $e->getMessage()
            ], 'app');
        }
    }

    public function api() {
        $this->json([
            'status' => 'success',
            'framework' => 'CodeForge-Engine',
            'version' => '2.0.0 (Developer Edition)',
            'cli' => 'Active',
            'active_record' => 'Active'
        ]);
    }
}
