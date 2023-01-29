<?php

namespace kirillbdev\WCUkrShipping\Http;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Classes\NPTranslator;
use kirillbdev\WCUkrShipping\DB\NovaPoshtaRepository;
use kirillbdev\WCUkrShipping\DB\OptionsRepository;
use kirillbdev\WCUkrShipping\Validators\OptionsValidator;

class NovaPoshtaRest
{
  private $api;
  private $novaPoshtaRepository;
  private $optionsRepository;

  public function __construct()
  {
    $this->api = new NovaPoshtaApi();
    $this->novaPoshtaRepository = new NovaPoshtaRepository();
    $this->optionsRepository = new OptionsRepository();

    add_action('rest_api_init', [ $this, 'initRoutes' ]);
  }

  public function initRoutes()
  {
    // Test route (need to check if REST active)
    register_rest_route( 'wc-ukr-shipping/v1', 'test', [
      'callback' => function (\WP_REST_Request $request) {
        set_transient('wc_ukr_shipping_request_handler', 'rest', 3600 * 24);

        return Response::make('success');
      }
    ]);

    // Options Save
    register_rest_route( 'wc-ukr-shipping/v1', 'settings', [
      'methods' => 'POST',
      'callback' => [ $this, 'saveSettings' ],
      'permission_callback' => [ $this, 'checkPermission' ]
    ]);

    // Options Areas Load to DB
    register_rest_route( 'wc-ukr-shipping/v1', 'db/areas/load', [
      'methods' => 'POST',
      'callback' => [ $this, 'loadAreas' ],
      'permission_callback' => [ $this, 'checkPermission' ]
    ]);

    // Options Cities load to DB
    register_rest_route( 'wc-ukr-shipping/v1', 'db/cities/load', [
      'methods' => 'POST',
      'callback' => [ $this, 'loadCities' ],
      'permission_callback' => [ $this, 'checkPermission' ]
    ]);

    // Options Warehouses load to DB
    register_rest_route( 'wc-ukr-shipping/v1', 'db/warehouses/load', [
      'methods' => 'POST',
      'callback' => [ $this, 'loadWarehouses' ],
      'permission_callback' => [ $this, 'checkPermission' ]
    ]);

    // Frontend Areas
    register_rest_route( 'wc_ukr_shipping/v1', 'novaposhta/area', [
      'callback' => [ $this, 'getAreas' ]
    ]);

    // Frontend Cities
    register_rest_route( 'wc_ukr_shipping/v1', 'novaposhta/cities/(?P<ref>[^\/]*)', [
      'callback' => [ $this, 'getCities' ]
    ]);

    // Frontend Warehouses
    register_rest_route( 'wc_ukr_shipping/v1', 'novaposhta/warehouses/(?P<ref>[^\/]*)', [
      'callback' => [ $this, 'getWarehouses' ]
    ]);
  }

  public function saveSettings(\WP_REST_Request $request)
  {
    $validator = new OptionsValidator();
    $result = $validator->validateRequest($_POST);

    if ($result !== true) {
      return Response::make('error', [
        'errors' => $result
      ]);
    }

    $this->optionsRepository->save($_POST);

    return Response::make('success', [
      'api_key' => get_option('wc_ukr_shipping_np_api_key', ''),
      'message' => 'Настройки успешно сохранены'
    ]);
  }

  public function getAreas(\WP_REST_Request $request)
  {
    try {
      $areas = $this->novaPoshtaRepository->getAreas();
      $npAreaTranslator = new NPTranslator();

      return Response::make('success', $npAreaTranslator->translateAreas($areas));
    }
    catch (\Error $e) {
      return Response::make('error', $e->getMessage());
    }
  }

  public function getCities(\WP_REST_Request $request)
  {
    try {
      $cities = $this->novaPoshtaRepository->getCities($request['ref']);

      return Response::make('success', $cities);
    }
    catch (\Error $e) {
      return Response::make('error', $e->getMessage());
    }
  }

  public function getWarehouses(\WP_REST_Request $request)
  {
    try {
      $warehouses = $this->novaPoshtaRepository->getWarehouses($request['ref']);

      return Response::make('success', $warehouses);
    }
    catch (\Error $e) {
      return Response::make('error', $e->getMessage());
    }
  }

  public function loadAreas()
  {
    $result = $this->api->getAreas();

    if ($result['success']) {
      $this->novaPoshtaRepository->saveAreas($result['data']);

      return Response::make('success');
    }

    return Response::make('error', [
      'errors' => $result['errors']
    ]);
  }

  public function loadCities()
  {
    $result = $this->api->getCities((int)$_POST['page']);

    if ($result['success']) {
      $this->novaPoshtaRepository->saveCities($result['data'], (int)$_POST['page']);

      return Response::make('success', [
        'loaded' => count($result['data']) === 0
      ]);
    }

    return Response::make('error', [
      'errors' => $result['errors']
    ]);
  }

  public function loadWarehouses()
  {
    $result = $this->api->getWarehouses((int)$_POST['page']);

    if ($result['success']) {
      $this->novaPoshtaRepository->saveWarehouses($result['data'], (int)$_POST['page']);

      return Response::make('success', [
        'loaded' => count($result['data']) === 0
      ]);
    }

    return Response::make('error', [
      'errors' => $result['errors']
    ]);
  }

  public function checkPermission(\WP_REST_Request $request)
  {
    return current_user_can('manage_options');
  }
}