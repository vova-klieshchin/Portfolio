<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе
 * установки. Необязательно использовать веб-интерфейс, можно
 * скопировать файл в "wp-config.php" и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки MySQL
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** Параметры MySQL: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'maosfood' );

/** Имя пользователя MySQL */
define( 'DB_USER', 'root' );

/** Пароль к базе данных MySQL */
define( 'DB_PASSWORD', '' );

/** Имя сервера MySQL */
define( 'DB_HOST', '127.0.0.1' );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

define('WP_MEMORY_LIMIT', '128M');
/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу.
 * Можно сгенерировать их с помощью {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными. Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'aHy/i4XA=)sK__9bRZbj53|pCfYaDa[ed!:_hrG@B;.a O ^jh!=*:^;7+T~n[sp' );
define( 'SECURE_AUTH_KEY',  'DA5B~~=)L2SH`U0Yb /.a@1x(!]HX10R@/&`AZWkv2[G}mjX<lJ(pnJp1Nue7etJ' );
define( 'LOGGED_IN_KEY',    'T:pl=am$4FqFA=6lOSrTx ,is59-jr?CC0rkRU^-ONKjiwx`!){17]HPHb;iZd~4' );
define( 'NONCE_KEY',        '8tYy=}m|cngxXU$KeMpnD_a]Q-e4E|( P4SK3s%K{EW+sg}<Nxb4Xp}vFouR6cjj' );
define( 'AUTH_SALT',        'P$HejDK)8Yk3kFCd:|,NrA|6>hp;35b]!0mSY|xb?vWK]1.FgBx%sOK$(])#NmbU' );
define( 'SECURE_AUTH_SALT', ' tW/7?X>3m>5o_@C!|pK`Om6sL_Mml9Akm39quJNZDu} [f!d5~8sqs9Bz9q`RY#' );
define( 'LOGGED_IN_SALT',   'y{;n<|[VJ9=k8BV`9M//a?:E*NA}YcT+9QR|lRX~yA0)OL eIh2GhG<,QfE)nqvm' );
define( 'NONCE_SALT',       '@rQlEVrnCtK?0-eHJu hgj9PBU5pz`FZ37B1DOE k=yUVgZED[Ij@_QQ81Pi81d/' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в Кодексе.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once( ABSPATH . 'wp-settings.php' );
