<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/eventbrite', 'EventbriteController@index')->name('eventbrite')->middleware('auth');
Route::post('/eventbrite', 'EventbriteController@create')->middleware('auth');

Route::get('/mailchimp', 'MailChimpController@index')->name('mailchimp')->middleware('auth');
Route::get('/mailchimp/create/{folderId}', 'MailchimpController@createNewCampaigns')->middleware('auth');
Route::post('/mailchimp', 'MailChimpController@create')->middleware('auth');

