<?php
/**
 * Favorites plugin
 *
 * Displays favorite pages. Based on Favoris plugin by Etienne M. <emauvaisfr@yahoo.fr>.
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

require __DIR__ . '/classes/Storage/CookieStorage.php';

/**
 * Синтаксический модуль
 *
 * @property Doku_Lexer $Lexer
 */
class Syntax_Plugin_favorites extends DokuWiki_Syntax_Plugin
{
    /**
     * Возвращает тип синтаксиса модуля
     *
     * @return string
     */
    public function getType()
    {
        return 'disabled';
    }

    /**
     * Возвращает число, определяющее в каком порядке добавляются режимы
     *
     * @return int
     */
    public function getSort()
    {
        return 667;
    }

    /**
     * Подключает режим
     *
     * @param string $mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('~~FAVORITES~~', $mode, 'plugin_favorites');
    }

    /**
     * Подготавливает данные для отрисовки
     *
     * @param string       $match    текст, соответствующий синтаксису модуля
     * @param int          $state    стадия совпадения (см. константы DOKU_LEXER_XXX)
     * @param int          $pos      позиция, где обнаружено совпадение с синтаксисом
     * @param Doku_Handler $handler
     *
     * @return array  инструкции для метода {@link render()}
     */
    public function handle($match, $state, $pos, $handler)
    {
        return array($match, $state, $pos);
    }

    /**
     * Отрисовывает данные
     *
     * @param string        $mode      имя формата вывода, который будет использован отрисовщиком
     * @param Doku_Renderer $renderer  отрисовщик
     * @param array         $data      данные, переданные методом {@link handle()}
     *
     * @return bool
     */
    public function render($mode, $renderer, $data)
    {
        if ('xhtml' == $mode)
        {
            /** @var Doku_Renderer_xhtml $renderer */
            $renderer->info['cache'] = false;

            $storage = new \DvaSlona\DokuwikiFavorites\Storage\CookieStorage();
            $showLimit = $storage->getConfigValue('maxFav');
            $favorites = $storage->getFavorites($showLimit ?: 10);

            if (count($favorites) > 0)
            {
                $renderer->strong_open();
                $renderer->doc .= $this->getLang('favorites_favorite_pages');
                $renderer->strong_close();
                $renderer->listu_open();

                foreach ($favorites as $pageId)
                {
                    $renderer->listitem_open(1);
                    $renderer->internallink($pageId);

                    /*$lien = $this->donneLien($page, " ($cpt " . $this->getLang('fav_visites') . ")");

                    $renderer->doc .= "<div id=\"$page\">";
                    $renderer->doc .= $lien;
                    //Reset
                    $renderer->doc .= ' <a href="javascript:deleteCookie(\'favorites[' . $page .
                        ']\', \'/\'); cache(\'' . $page . '\');"><img src="' . DOKU_URL .
                        'lib/plugins/favorites/images/reset.png" title="' .
                        $this->getLang('fav_reset') .
                        '" border="0" height="18" style="vertical-align:middle; ' .
                        'display:none;" name="ctrl" /></a>';
                    //Exclure
                    $renderer->doc .= ' <a  href="javascript:setCookie(\'favorites[' . $page .
                        ']\', -1, new Date(\'July 21, 2099 00:00:00\'), \'/\'); cache(\'' .
                        $page . '\');"><img src="' . DOKU_URL .
                        'lib/plugins/favorites/images/exclure.png" title="' .
                        $this->getLang('fav_exclure') .
                        '" border="0" height="18" style="vertical-align:middle; ' .
                        'display:none;" name="ctrl" /></a>';
                    $renderer->doc .= "</div>";
                    $renderer->listitem_close();
                    */
                }
                $renderer->listu_close();
                /*
                if ($idx)
                {
                    if (!plugin_isdisabled('snap'))
                    {
                        plugin_load('helper', 'snap');
                        $renderer->listitem_open(1);
                        $renderer->doc .= "<a href=\"?do=snapfavorites\">" .
                            $this->getLang('fav_mosaique') . " >></a><br />";
                        $renderer->listitem_close();
                    }
                }
                if ($idx)
                {
                    $renderer->listu_close();
                }
                else
                {
                    $renderer->doc .= " <br />";
                }

                //Pages exclues
                //Voir/cacher les pages exclures et la configuration
                $renderer->doc .= '<a href="javascript:afficheMasque(\'exclues\'); ' .
                    'this.blur();"><img src="' . DOKU_URL .
                    'lib/plugins/favorites/images/voir-cacher.png" title="' .
                    $this->getLang('fav_voircacher') . '" border="0" height="18" ' .
                    'style="vertical-align:middle; display:none;" name="ctrl" /></a><div ' .
                    'id="exclues" style="display:none;">';
                $exclues = 0;
                foreach ($fav as $page => $cpt)
                {
                    list($cpt, $date) = explode(";", $cpt);

                    if ($cpt == -1)
                    {
                        if (!$exclues)
                        {
                            $renderer->listu_open();
                        }

                        $lien = $this->donneLien($page);
                        $exclues++;

                        $renderer->doc .= "<div id=\"ex_$page\">"; //<li><div class=\"li\">";
                        $renderer->listitem_open(1);
                        $renderer->doc .= $lien;
                        //Inclure
                        $renderer->doc .= ' <a href="javascript:deleteCookie(\'favorites[' . $page .
                            ']\', \'/\'); cache(\'ex_' . $page . '\');"><img src="' . DOKU_URL .
                            'lib/plugins/favorites/images/inclure.png" title="' .
                            $this->getLang('fav_inclure') . '" border="0" height="18" ' .
                            'style="vertical-align:middle;" /></a>';
                        $renderer->doc .= "</div>"; //</li></div>";
                        $renderer->listitem_close();
                    }
                }
                if ($exclues)
                {
                    $renderer->listu_close();
                }
                $this->renderConfig($renderer, $maxFav);*/
            }
            else
            {
                $renderer->doc .= $this->getLang('fav_pasencore');
            }
        }
        return false;
    }

    /**
     * Возвращает ссылку?
     *
     * @param mixed  $page
     * @param string $title
     *
     * @return string
     */
    private function donneLien($page, $title = "")
    {
        $titrePage = p_get_first_heading($page);
        if (!$titrePage)
        {
            if (!plugin_isdisabled('pagelist'))
            {
                $pagelist = plugin_load('helper', 'pagelist');
            }

            if (!$pagelist)
            {
                $titrePage = explode(":", $page);
                $titrePage = $titrePage[sizeof($titrePage) - 1];
                $titrePage = str_replace('_', ' ', $titrePage);
            }
            else
            {
                $pagelist->page['id'] = $page;
                $pagelist->page['exists'] = 1;
                $pagelist->_meta = null;
                $titrePage = $pagelist->_getMeta('title');
                if (!$titrePage)
                {
                    $titrePage = str_replace('_', ' ', noNS($page));
                }
                $titrePage = hsc($titrePage);
            }
        }
        if (@file_exists(fullpath(wikiFN($page))))
        {
            return "<a href='doku.php?id=" . $page .
                "' class='wikilink1' style='font-weight: lighter;' title='$page" . $title .
                "'>$titrePage</a>";
        }
        else
        {
            return "<a href='doku.php?id=" . $page .
                "' class='wikilink2' style='font-weight: lighter;' title='$page" . $title .
            "' rel='nofollow'>$titrePage</a>";
        }
    }

    /**
     * Настройки
     *
     * @param Doku_Renderer_xhtml $renderer
     * @param int                 $maxFav
     */
    private function renderConfig($renderer, $maxFav)
    {
        $renderer->doc .= "<fieldset style=\"text-align:left;\"><legend><b>" .
            $this->getLang('fav_config') . "</b></legend>";
        $renderer->doc .= $this->getLang('fav_afficher') . " ";
        $renderer->doc .= "<select value=\"$maxFav\" id=\"maxFav\">";
        for ($i = 0; $i < 10; $i++)
        {
            $renderer->doc .= "<option";
            if ($i == $maxFav)
            {
                $renderer->doc .= " selected=\"selected\"";
            }
            $renderer->doc .= ">$i</option>";
        }
        $renderer->doc .= "</select>";
        $renderer->doc .= " " . $this->getLang('fav_conf_pfav') . "<br />";
        $renderer->doc .= "<input type=\"button\" value=\"" . $this->getLang('fav_sauver') .
            "\" onclick=\"javascript:sauvePref();\" />";
        $renderer->doc .= "</fieldset>";
        $renderer->doc .= "</div>";

        //Rafraichir
        $renderer->doc .= ' <a href="javascript:recharge();"><img src="' . DOKU_URL .
            'lib/plugins/favorites/images/rafraichir.png" title="' .
            $this->getLang('fav_rafraichir') .
            '" border="0" height="18" style="vertical-align:middle; display:none;" ' .
            'name="ctrl" /></a> ';

        $renderer->doc .= "</div>";
    }
}

