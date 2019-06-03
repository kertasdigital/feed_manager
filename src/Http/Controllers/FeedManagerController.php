<?php

namespace KertasDigital\FeedManager\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FeedManagerController extends Controller
{
 
	public function index(){
		return response()->view('feed-manager::feed')->header('content-type','text/xml');
	}
	
	
}
