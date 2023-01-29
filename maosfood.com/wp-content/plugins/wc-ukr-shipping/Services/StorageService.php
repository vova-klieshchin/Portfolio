<?php

namespace kirillbdev\WCUkrShipping\Services;

class StorageService
{
  public static function getValue($key, $default = null)
  {
    if (wc()->customer->get_id()) {
      return get_user_meta(wc()->customer->get_id(), $key, true) ?: $default;
    }

    return wc()->session->get($key, $default);
  }

  public static function setValue($key, $value)
  {
    if (wc()->customer->get_id()) {
      update_user_meta(wc()->customer->get_id(), $key, $value);
    }
    else {
      wc()->session->set($key, $value);
    }
  }

  public static function deleteValue($key)
  {
    if (wc()->customer->get_id()) {
      delete_user_meta(wc()->customer->get_id(), $key);
    }
    else {
      wc()->session->set($key, null);
    }
  }
}