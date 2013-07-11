<?php
/**
 * Favorites plugin
 *
 * Based on Favoris plugin by Etienne M. <emauvaisfr@yahoo.fr>.
 *
 * @license http://www.gnu.org/licenses/gpl.txt    GPL License 3
 * @author Etienne M. <emauvaisfr@yahoo.fr>
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

if (!defined('DOKU_PLUGIN'))
{
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}
require_once(DOKU_PLUGIN . 'action.php');

/**
 * Обработчики событий
 */
class Action_Plugin_Favorites extends DokuWiki_Action_Plugin
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->setupLocale();
    }

    /**
     * Регистрирует обработчики событий
     *
     * @param Doku_Event_Handler $controller
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'updateCookie',
            array());
        $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'handleTemplateAction',
            array());
    }

    /**
     * @param Doku_Event $event
     * @param mixed      $param
     */
    public function updateCookie(Doku_Event $event, $param)
    {
        global $INFO;

        if ($event->data == 'snapfavorites')
        {
            $event->preventDefault();
        }

        //On ignore les medias (:lib:, :media:)
        if (preg_match("/:lib:/i", $INFO['id']) || preg_match("/:media:/i", $INFO['id']))
        {
            return;
        }

        if (isset($_COOKIE['favorites']))
        {
            $fav = $_COOKIE['favorites'];

            //Si on ne souhaite pas suivre les favorites
            if ($fav['off'] == 1)
            {
                //On efface les eventuels cookies existants (sauf off)
                foreach ($_COOKIE['favorites'] as $page => $cpt)
                {
                    if ($page != "off")
                    {
                        setCookie("favorites[$page]", "", time() - 3600, '/');
                    }
                }
                return;
            }

            //Si on est en mode de remise a zero des compteurs (mais on garde les pages exclues quand meme
            if ($fav['off'] == 2)
            {
                //On efface tous les cookies (y compris off)
                foreach ($_COOKIE['favorites'] as $page => $cpt)
                {
                    list($cpt, $date) = explode(";", $cpt);
                    if ($cpt != "-1")
                    {
                        setCookie("favorites[$page]", "", time() - 3600, '/');
                    }
                }
                return;
            }

            list($cpt, $date) = explode(";", $fav[$INFO['id']]);

            //Si la page est a ne pas prendre en compte (-1)
            if ($cpt == -1)
            {
                return;
            }

            //S'il existe, on recupere l'ancien compteur de visites et on l'incremente (sinon, on commence a 1)
            if ($cpt != 0 && $cpt != "")
            {
                $cpt++;
            }
            else
            {
                $cpt = 1;
            }
        }
        else
        {
            $cpt = 1;
        }

        //On positionne le cookie
        setCookie("favorites[" . $INFO['id'] . "]", "$cpt;" . time(), time() + 60 * 60 * 24 * 7, '/');
    }

    /**
     * @param Doku_Event $event
     * @param mixed      $param
     *
     * @return mixed
     */
    public function handleTemplateAction(Doku_Event $event, $param)
    {
        if ($event->data != 'snapfavorites')
        {
            return null;
        }
        $event->preventDefault();

        print "<h1>" . $this->getLang('fav_mosaique') . "</h1>";

        $fav = $_COOKIE['favorites'];
        if (!$fav)
        {
            print $this->getLang('fav_pasencore');
            return false;
        }

        uasort($fav, create_function('$a, $b', '
                           list($cpt1, $date)=explode(";", $a);
                           list($cpt2, $date)=explode(";", $b);

                           $cpt1=intval($cpt1);
                           $cpt2=intval($cpt2);

                           if ($cpt1==$cpt2) return 0;
                           return ($cpt1 > $cpt2) ? -1 : 1;
                         '));

        print "<div>";
        $idx = 0;
        foreach ($fav as $page => $cpt)
        {
            if ($page == 'off' || $cpt < 1)
            {
                continue;
            }
            $snap = plugin_load('helper', 'snap');
            if (!$snap)
            {
                print $this->getLang('fav_snapnotfound') . "<br />";
                print "</div>";
                return false;
            }

            list($imagePath, $titrePage, $target) = $snap->getSnap($page, 200, 150, true);
            if (!$snap->succeed || !$imagePath)
            {
                print $this->getLang('fav_pbsnap') . " $page<br />";
                print "</div>";
                return false;
            }

            if ($titrePage)
            {
                $titrePage = " - " . $titrePage;
            }
            $cpt = explode(";", $cpt);
            if ($cpt)
            {
                $titrePage .= " - " . $cpt[0] . " " . $this->getLang('fav_visites');
            }
            if ($snap->snapTimeFormatted)
            {
                $titrePage .= " (" . $snap->snapTimeFormatted . ")";
            }

            if (!@file_exists(fullpath(wikiFN($page))))
            {
                $style = "style=\"border:1px dashed red; margin:2px;\"";
            }
            else
            {
                $style = "style=\"border:1px solid #C0C0C0; margin:2px;\"";
            }

            print "<a href=\"" . $snap->url . "\" title=\"$page$titrePage\" $target>";

            print "<img src=\"" . DOKU_URL . "lib/plugins/snap/image.php?image=" .
                rawurlencode($imagePath) . "\" $style/>";
            print "</a>";

            $idx++;
            if ($idx >= 9)
            {
                break;
            }
            if ($idx % 3 == 0)
            {
                print "<br />";
            }
        }

        print "</div>";
        print "<br /><hr />";
        return null;
    }
}

