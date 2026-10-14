<?php

namespace App\Enums;

/*
 * Стадия готовности апартамента. По ней страница «Новостройки» раскладывает
 * карточки по вкладкам «Строится» и «Готово». Новое значение добавляется
 * сюда и в label(), больше нигде.
 */
enum ApartmentStatus: string
{
    case Building = 'building';
    case Ready = 'ready';

    /* Подпись метки на карточке и на странице апартамента */
    public function label(): string
    {
        return match ($this) {
            self::Building => 'Строится',
            self::Ready => 'Готово к заселению',
        };
    }

    /* Для выпадающего списка в админке: значение => подпись */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
