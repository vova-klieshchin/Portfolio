<?php

namespace kirillbdev\WCUkrShipping\DB;

class OptionsRepository
{
  /**
   * @param string $key
   * @return mixed|null
   */
  public static function getOption($key)
  {
    $defaults = [
      'wc_ukr_shipping_np_method_title'           => 'Доставка службой Новая почта',
      'wc_ukr_shipping_np_block_title'            => 'Укажите адрес доставки',
      'wc_ukr_shipping_np_placeholder_area'       => 'Выберите область',
      'wc_ukr_shipping_np_placeholder_city'       => 'Выберите город',
      'wc_ukr_shipping_np_placeholder_warehouse'  => 'Выберите отделение',
      'wc_ukr_shipping_np_address_title'          => 'Нужна адресная доставка',
      'wc_ukr_shipping_np_address_placeholder'    => 'Введите адрес',
      'wc_ukr_shipping_np_block_pos'              => 'billing'
    ];

    return get_option($key, isset($defaults[ $key ]) ? $defaults[ $key ] : null);
  }

  public function save($data)
  {
    foreach ($data['wc_ukr_shipping'] as $key => $value) {
      update_option('wc_ukr_shipping_' . $key, sanitize_text_field($value));
    }

    if ( ! isset($data['wc_ukr_shipping']['address_shipping'])) {
      update_option('wc_ukr_shipping_address_shipping', 0);
    }

    if ( ! isset($data['wc_ukr_shipping']['send_statistic'])) {
      update_option('wc_ukr_shipping_send_statistic', 0);
    }

    // Flush WooCommerce Shipping Cache
    delete_option('_transient_shipping-transient-version');
  }

  public function deleteAll()
  {
	  delete_option('_transient_shipping-transient-version');
  }
}