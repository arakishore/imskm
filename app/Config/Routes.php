<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Apply the auth filter to all routes within this group
$routes->group('api', ['filter' => ['customCorsFilter','authFilter']], function(RouteCollection $routes) {
    $routes->get('logout', 'AuthController::logout');
    $routes->delete('user/(:any)', 'UserController::delete/$1');
    $routes->get('user', 'UserController::index');
    $routes->post('user', 'UserController::create');
    $routes->put('user/profileupdate', 'UserController::profileUpdate');
    $routes->put('user/changepassword', 'UserController::changePassword');
    $routes->get('user/(:any)', 'UserController::show/$1');
    $routes->put('user/(:any)', 'UserController::update/$1');


    $routes->get('item-groups', 'ItemGroupController::index');
    $routes->get('item-groups/(:any)', 'ItemGroupController::show/$1');
    $routes->post('item-groups', 'ItemGroupController::create');
    $routes->put('item-groups/(:any)', 'ItemGroupController::update/$1');
    $routes->delete('item-groups/(:any)', 'ItemGroupController::delete/$1');
    
    // Party routes
    $routes->get('party', 'PartyController::index');                // Get all parties
    $routes->get('party/(:any)', 'PartyController::show/$1');       // Get a single party by ID
    $routes->post('party', 'PartyController::create');              // Create a new party
    $routes->put('party/(:any)', 'PartyController::update/$1');     // Update a party by ID
    $routes->delete('party/(:any)', 'PartyController::delete/$1');  // Delete a party by ID (soft delete)

    $routes->get('party-address', 'PartyController::index');                // Get all parties
    $routes->get('party-address/(:any)', 'PartyController::show/$1');       // Get a single party by ID
    $routes->post('party-address', 'PartyController::create');              // Create a new party
    $routes->put('party-address/(:any)', 'PartyController::update/$1');     // Update a party by ID
    $routes->delete('party-address/(:any)', 'PartyController::delete/$1');  // Delete a party by ID (soft delete)

    
    $routes->get('items', 'ItemController::index');
    $routes->post('items', 'ItemController::create');
    $routes->get('items/(:any)', 'ItemController::show/$1');
    $routes->put('items/(:any)', 'ItemController::update/$1');
    $routes->delete('items/(:any)', 'ItemController::delete/$1');

    // Add all your other API routes here

    $routes->get('lookup', 'ComLookupController::index');
    // Get a specific lookup by ID
    $routes->get('lookup/(:any)', 'ComLookupController::show/$1');
    $routes->post('lookup', 'ComLookupController::addLookup');
    $routes->put('lookup/(:any)', 'ComLookupController::updateLookup/$1');
    $routes->delete('lookup/(:any)', 'ComLookupController::deleteLookup/$1');

    $routes->get('lookup-values', 'ComLookupController::showlookupvalues');
    $routes->get('lookup-values/(:any)', 'ComLookupController::showlookupvaluesingle/$1');
    $routes->post('lookup-values', 'ComLookupController::addLookupValue');
    $routes->put('lookup-values/(:any)', 'ComLookupController::updateLookupValue/$1');
    $routes->delete('lookup-values/(:any)', 'ComLookupController::deleteLookupValue/$1');


    $routes->get('country', 'CountryController::index');
    $routes->get('country/(:any)', 'CountryController::show/$1');
    $routes->post('country', 'CountryController::create');
    $routes->put('country/(:any)', 'CountryController::update/$1');
    $routes->delete('country/(:any)', 'CountryController::delete/$1');
    
    $routes->get('state', 'StateController::index');
    $routes->post('state', 'StateController::create');
    $routes->get('state/(:any)', 'StateController::show/$1');
    $routes->put('state/(:any)', 'StateController::update/$1');
    $routes->delete('state/(:any)', 'StateController::delete/$1');
    
    $routes->get('city', 'CityController::index');
    $routes->post('city', 'CityController::create');
    $routes->get('city/(:any)', 'CityController::show/$1');
    $routes->put('city/(:any)', 'CityController::update/$1');
    $routes->delete('city/(:any)', 'CityController::delete/$1');

    

});

// Route for login (not protected by the auth filter)

// $routes->post('api/login', 'AuthController::login');
// $routes->post('api/resetpassword', 'AuthController::resetPassword');
// $routes->get('api/test', 'AuthController::test');

$routes->post('api/login', 'AuthController::login', ['filter' => 'customCorsFilter']);
$routes->post('api/resetpassword', 'AuthController::resetPassword', ['filter' => 'customCorsFilter']);
$routes->get('api/test', 'AuthController::test', ['filter' => 'customCorsFilter']);
