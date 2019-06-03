<?php

Route::group(['namespace'=>'KertasDigital\FeedManager\Http\Controllers'], function(){ 
	Route::get('feed','FeedManagerController@index')->name('feed');
});
