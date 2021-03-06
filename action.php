<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Etienne M. <emauvaisfr@yahoo.fr>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_favoris extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo() {
        return array(
                'author' => 'Etienne M.',
                'email'  => 'emauvaisfr@yahoo.fr',
                'date'   => @file_get_contents(DOKU_PLUGIN.'favoris/VERSION'),
                'name'   => 'favoris Plugin',
                );
    }

    /**
     * Constructor
     */
    function action_plugin_favoris() {
      $this->setupLocale();
    }
                              
    /**
     * register the eventhandlers
     */
    function register(&$contr) {
        $contr->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_update_cookie', array());
        $contr->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, '_handle_tpl_act', array());
    }

    function _update_cookie(&$event, $param) {
        global $INFO;

        if($event->data == 'snapfavoris') {
          $event->preventDefault();
        }

        //On ignore les medias (:lib:, :media:)
        if (preg_match("/:lib:/i",$INFO['id']) || preg_match("/:media:/i",$INFO['id'])) return;

        if (isset($_COOKIE['favoris'])) {
          $fav=$_COOKIE['favoris'];

          //Si on ne souhaite pas suivre les favoris
          if ($fav['off']==1) {
            //On efface les eventuels cookies existants (sauf off)
            foreach ($_COOKIE['favoris'] as $page => $cpt) if ($page != "off") setCookie("favoris[$page]", "", time()-3600, '/');
            return;
          }
          
          //Si on est en mode de remise a zero des compteurs (mais on garde les pages exclues quand meme
          if ($fav['off']==2) {
            //On efface tous les cookies (y compris off)
            foreach ($_COOKIE['favoris'] as $page => $cpt) {
              list($cpt, $date)=explode(";",$cpt);
              if ($cpt != "-1") setCookie("favoris[$page]", "", time()-3600, '/');
            }
            return;
          }

          list($cpt, $date)=explode(";", $fav[$INFO['id']]);

          //Si la page est a ne pas prendre en compte (-1)
          if ($cpt==-1) return;

          //S'il existe, on recupere l'ancien compteur de visites et on l'incremente (sinon, on commence a 1)
          if ($cpt!=0 && $cpt!="")
            $cpt++;
          else $cpt=1;
        }
        else $cpt=1;

        //On positionne le cookie
        setCookie("favoris[".$INFO['id']."]","$cpt;".time(), time()+60*60*24*7, '/');
    }


    function _handle_tpl_act(&$event, $param) {
      if($event->data != 'snapfavoris') return;
      $event->preventDefault();

      print "<h1>".$this->getLang('fav_mosaique')."</h1>";

      $fav=$_COOKIE['favoris'];
      if (!$fav) {
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
     $idx=0;
     foreach ($fav as $page => $cpt) {
       if ($page=='off' || $cpt<1) continue;
       $snap=plugin_load('helper','snap');
       if (!$snap) {
         print $this->getLang('fav_snapnotfound')."<br />";
         print "</div>";
         return false;
       }

       list($imagePath, $titrePage, $target)=$snap->getSnap($page, 200, 150, true);
       if (!$snap->succeed || !$imagePath) {
         print $this->getLang('fav_pbsnap')." $page<br />";
         print "</div>";
         return false;
       }

       if ($titrePage) $titrePage=" - ".$titrePage;
       $cpt=explode(";",$cpt);
       if ($cpt) $titrePage.= " - ".$cpt[0]." ".$this->getLang('fav_visites');
       if ($snap->snapTimeFormatted) $titrePage.=" (".$snap->snapTimeFormatted.")";

       if (!@file_exists(fullpath(wikiFN($page)))) $style="style=\"border:1px dashed red; margin:2px;\"";
       else $style="style=\"border:1px solid #C0C0C0; margin:2px;\"";

       print "<a href=\"".$snap->url."\" title=\"$page$titrePage\" $target>";

       print "<img src=\"".DOKU_URL."lib/plugins/snap/image.php?image=".rawurlencode($imagePath)."\" $style/>";
       print "</a>";

       $idx++;
       if ($idx>=9) break;
       if ($idx%3==0) print "<br />";
     }
     
     print "</div>";
     print "<br /><hr />";
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
