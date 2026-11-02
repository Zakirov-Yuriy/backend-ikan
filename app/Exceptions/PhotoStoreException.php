<?php

namespace App\Exceptions;

use RuntimeException;

/*
 * Файл фотографии не записался на диск (02.11). Раньше в этом случае в базе
 * всё равно появлялась запись с пустым путём: в админке фото «есть», а на
 * сайте и по ссылке его нет. Теперь форма показывает ошибку, запись не
 * создаётся (bootstrap/app.php).
 */
class PhotoStoreException extends RuntimeException {}
