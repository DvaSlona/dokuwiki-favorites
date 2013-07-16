<?php
/**
 * Хранилище на базе куки
 *
 * @copyright 2013, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt	GPL License 3
 * @author Михаил Красильников <mk@dvaslona.ru>
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо (по вашему выбору) с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * Вы должны были получить копию Стандартной Общественной Лицензии
 * GNU с этой программой. Если Вы ее не получили, смотрите документ на
 * <http://www.gnu.org/licenses/>
 */

namespace DvaSlona\DokuwikiFavorites\Storage;

/**
 * Хранилище на базе куки
 */
class CookieStorage
{
    /**
     * Возвращает параметр настроек
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getConfigValue($key)
    {
        $key = "fav_$key";
        return array_key_exists($key, $_COOKIE) ? $_COOKIE[$key] : null;
    }

    /**
     * Возвращает список самых посещаемых разделов
     *
     * Разделы сортируются по уменьшению количества их просмотров
     *
     * @param int $limit  сколько, максимум, возвращать разделов
     *
     * @return array
     */
    public function getFavorites($limit = 10)
    {
        $raw = array_key_exists('favorites', $_COOKIE)
            ? $_COOKIE['favorites']
            : array();

        uasort($raw,
            function($a, $b)
            {
                list($cpt1, $date)=explode(";", $a);
                list($cpt2, $date)=explode(";", $b);

                $cpt1=intval($cpt1);
                $cpt2=intval($cpt2);

                if ($cpt1==$cpt2)
                {
                    return 0;
                }
                return ($cpt1 > $cpt2) ? -1 : 1;
            });

        $favorites = array();
        foreach ($raw as $pageId => $cpt)
        {
            list($cpt, $date) = explode(';', $cpt);

            if ($pageId == 'off' || $cpt < 1)
            {
                continue;
            }

            $ns = getNS($pageId);
            resolve_pageid($ns, $pageId, $exists);
            if (!$exists)
            {
                continue;
            }

            if (false === $ns)
            {
                $pageId = ':' . $pageId;
            }

            if (':' . $GLOBALS['conf']['start'] == $pageId)
            {
                continue;
            }
            $favorites []= $pageId;
            if (count($favorites) == $limit)
            {
                break;
            }
        }

        return $favorites;
    }
}

