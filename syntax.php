<?php
/**
 * favoris plugin : Affiche mes pages favorites (les plus visitees).
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Etienne M. <emauvaisfr@yahoo.fr>
 */

// based on http://wiki.splitbrain.org/plugin:tutorial

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_favoris extends DokuWiki_Syntax_Plugin {
    function getInfo() {
        return array(
        'author'  => 'Etienne M.',
        'email'   => 'emauvaisfr@yahoo.fr',
        'date'    => @file_get_contents(DOKU_PLUGIN.'favoris/VERSION'),
        'name'    => 'favoris Plugin',
        'desc'    => html_entity_decode($this->getLang('fav_description')),
        'url'     => 'http://www.dokuwiki.org/plugin:favoris'
        );
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~FAVORIS~~', $mode, 'plugin_favoris');
    }

    //function getType() { return 'substition'; }
    function getType() { return 'disabled'; }

    function getSort() { return 667; }

    function handle($match, $state, $pos, &$handler) {
        return array($match, $state, $pos);
    }

    function render($mode, &$renderer, $data) {
      $maxFav=5;
      $maxRec=5;

        if ($mode == 'xhtml') {
  	  $renderer->info['cache'] = FALSE;
          $renderer->doc .= '<script type="text/javascript" charset="utf-8" src="'.DOKU_URL.'lib/plugins/favoris/favoris.js" ></script>';

	  if (isset($_COOKIE['favoris'])) {
	    $fav=$_COOKIE['favoris'];

            //Si la page off existe et vaut 1, on sort
	    if (isset($fav['off']) && $fav['off']==1) {
	      $renderer->doc .= $this->getLang('fav_desact');
	      //Activer
	      $renderer->doc .= ' <img src="'.DOKU_URL.'lib/plugins/favoris/images/activer.png" border="0" height="18" style="vertical-align:middle;" /> <a href="javascript:deleteCookie(\'favoris[off]\', \'/\'); recharge();">'.$this->getLang('fav_activer').'</a>.<br />';
	      $renderer->doc .= $this->getLang('fav_cookies').'<br />';
	      return;
	    }

	    //Si la page off existe et vaut 2, on recharge la page
	    if (isset($fav['off']) && $fav['off']==2) {
	      $renderer->doc .= "<script>recharge();</script>";
	      return;
	    }

            //Combien de pages afficher au maximum ?
            $max=$maxFav;
            if (isset($_COOKIE['fav_maxFav'])) $max=$_COOKIE['fav_maxFav'];
	    if (intval($max) != $max) $max=$maxFav;
	    $maxFav=$max;

	    $max=$maxRec;
	    if (isset($_COOKIE['fav_maxRec'])) $max=$_COOKIE['fav_maxRec'];
	    if (intval($max) != $max) $max=$maxRec;
	    $maxRec=$max;

	    $renderer->doc .= '<div id="enveloppe" ondblclick="afficherControles(event,0);" onmouseover="afficherControles(event,2000);" onmouseout="masquerControles(event);" title="'.$this->getLang('fav_flotter').'">';

            //Pages recentes
	    //Tri des pages par date de visite decroissante
	    uasort($fav, create_function('$a, $b', '
	                                           list($cpt, $date1)=explode(";", $a);
						   list($cpt, $date2)=explode(";", $b);

						   if ($date1=="") $date1=0;
						   if ($date2=="") $date2=0;

						   $date1=intval($date1);
						   $date2=intval($date2);

						   if ($date1==$date2) return 0;
						   return ($date1 > $date2) ? -1 : 1;
	                                          '));

            $idx1=0;
            if ($maxRec>0) {
              foreach ($fav as $page => $cpt) {
                list($cpt, $date) = explode(";", $cpt);
                if ($page=='off' || $cpt<1 || !$date) continue;
                if (!$idx1) {
                  $renderer->doc .= "<b>".$this->getLang('fav_prec')."</b>";
                  $renderer->listu_open();
                }

                $lien = $this->donneLien($page, "");
                $renderer->listitem_open(1);
                $renderer->doc .= $lien;
                $renderer->listitem_close();

                $idx1++;
                if ($idx1>=$maxRec) break;
              }
            }
            if ($idx1) $renderer->listu_close();
            //else $renderer->doc .= " <br />";

            //Pages favorites
	    //Tri des pages par visites decroissantes
	    uasort($fav, create_function('$a, $b', '
	                                           list($cpt1, $date)=explode(";", $a);
						   list($cpt2, $date)=explode(";", $b);

						   $cpt1=intval($cpt1);
						   $cpt2=intval($cpt2);

						   if ($cpt1==$cpt2) return 0;
						   return ($cpt1 > $cpt2) ? -1 : 1;
	                                           '));
	    $idx2=0;
	    if ($maxFav>0) {
	      foreach ($fav as $page => $cpt) {
	        list($cpt, $date) = explode(";", $cpt);

	        if ($page=='off' || $cpt<1) continue;

                if (!$idx2) {
	          $renderer->doc .= "<b>".$this->getLang('fav_pfav')."</b>";
	          $renderer->listu_open();
                }

                $lien = $this->donneLien($page, " ($cpt ".$this->getLang('fav_visites').")");

                $renderer->doc .= "<div id=\"$page\">";
	        $renderer->listitem_open(1);
                $renderer->doc .= $lien;
	        //Reset
	        $renderer->doc .= ' <a href="javascript:deleteCookie(\'favoris['.$page.']\', \'/\'); cache(\''.$page.'\');"><img src="'.DOKU_URL.'lib/plugins/favoris/images/reset.png" title="'.$this->getLang('fav_reset').'" border="0" height="18" style="vertical-align:middle; display:none;" name="ctrl" /></a>';
	        //Exclure
	        $renderer->doc .= ' <a  href="javascript:setCookie(\'favoris['.$page.']\', -1, new Date(\'July 21, 2099 00:00:00\'), \'/\'); cache(\''.$page.'\');"><img src="'.DOKU_URL.'lib/plugins/favoris/images/exclure.png" title="'.$this->getLang('fav_exclure').'" border="0" height="18" style="vertical-align:middle; display:none;" name="ctrl" /></a>';
                $renderer->doc .= "</div>"; 
	        $renderer->listitem_close();
																
	        $idx2++;
	        if ($idx2>=$maxFav) break;
              }
	    }  
        if ($idx2) {
          if (!plugin_isdisabled('snap')) $snap=plugin_load('helper', 'snap');
          if ($snap) {
            $renderer->listitem_open(1);
            $renderer->doc .= "<a href=\"?do=snapfavoris\">".$this->getLang('fav_mosaique')." >></a><br />";
            $renderer->listitem_close();
          }
        }
	    if ($idx2) $renderer->listu_close();

	    if (!$idx1 && !$idx2) $renderer->doc .= " <br />";

	    //Pages exclues
	    //Voir/cacher les pages exclures et la configuration
	    $renderer->doc .= '<a href="javascript:afficheMasque(\'exclues\'); this.blur();"><img src="'.DOKU_URL.'lib/plugins/favoris/images/voir-cacher.png" title="'.$this->getLang('fav_voircacher').'" border="0" height="18" style="vertical-align:middle; display:none;" name="ctrl" /></a><div id="exclues" style="display:none;">';
            $exclues=0;
	    foreach ($fav as $page => $cpt) {
	      list($cpt, $date) = explode(";", $cpt);

	      if ($cpt==-1) {
	        if (!$exclues) $renderer->listu_open();

		$lien = $this->donneLien($page);
		$exclues++;

		$renderer->doc.= "<div id=\"ex_$page\">"; //<li><div class=\"li\">";
		$renderer->listitem_open(1);
		$renderer->doc .= $lien;
		//Inclure
		$renderer->doc .= ' <a href="javascript:deleteCookie(\'favoris['.$page.']\', \'/\'); cache(\'ex_'.$page.'\');"><img src="'.DOKU_URL.'lib/plugins/favoris/images/inclure.png" title="'.$this->getLang('fav_inclure').'" border="0" height="18" style="vertical-align:middle;" /></a>';
		$renderer->doc .= "</div>"; //</li></div>";
		$renderer->listitem_close();
              }
	    }
	    if ($exclues) $renderer->listu_close();

	    //Configuration
	    $renderer->doc .= "<fieldset style=\"text-align:left;\"><legend><b>".$this->getLang('fav_config')."</b></legend>";
	    $renderer->doc .= $this->getLang('fav_afficher')." ";
	    $renderer->doc .= "<select value=\"$maxRec\" id=\"maxRec\">";
	    for ($i=0; $i<10; $i++) {
	      $renderer->doc .= "<option";
	      if ($i==$maxRec) $renderer->doc .= " selected=\"selected\"";
	      $renderer->doc .= ">$i</option>";
	    }
	    $renderer->doc .= "</select>";
	    $renderer->doc .= " ".$this->getLang('fav_conf_prec')."<br />";
	    $renderer->doc .= $this->getLang('fav_afficher')." ";
	    $renderer->doc .= "<select value=\"$maxFav\" id=\"maxFav\">";
            for ($i=0; $i<10; $i++) {
              $renderer->doc .= "<option";
              if ($i==$maxFav) $renderer->doc .= " selected=\"selected\"";
              $renderer->doc .= ">$i</option>";
            }
	    $renderer->doc .= "</select>";
	    $renderer->doc .= " ".$this->getLang('fav_conf_pfav')."<br />";
	    $renderer->doc .= "<input type=\"button\" value=\"".$this->getLang('fav_sauver')."\" onclick=\"javascript:sauvePref();\" />";
	    $renderer->doc .= "</fieldset>";
	    $renderer->doc .= "</div>";

	    //Rafraichir
	    $renderer->doc .= ' <a href="javascript:recharge();"><img src="'.DOKU_URL.'lib/plugins/favoris/images/rafraichir.png" title="'.$this->getLang('fav_rafraichir').'" border="0" height="18" style="vertical-align:middle; display:none;" name="ctrl" /></a> ';
	    //Reset tous
	    $renderer->doc .= ' <a href="javascript:if(confirm(\''.$this->getLang('fav_confResetAll').'\')) {setCookie(\'favoris[off]\', 2, new Date(\'July 21, 2099 00:00:00\'), \'/\'); recharge();}"><img src="'.DOKU_URL.'lib/plugins/favoris/images/reset.png" title="'.$this->getLang('fav_resetall').'" border="0" height="18" style="vertical-align:middle; display:none;" name="ctrl" /></a> ';
	    //Desactiver
	    $renderer->doc .= ' <a href="javascript:if(confirm(\''.$this->getLang('fav_confirmation').'\')) {setCookie(\'favoris[off]\', 1, new Date(\'July 21, 2099 00:00:00\'), \'/\'); recharge();}"><img src="'.DOKU_URL.'lib/plugins/favoris/images/desactiver.png" title="'.$this->getLang('fav_desactiver').'" border="0" height="18" style="vertical-align:middle; display:none;" name="ctrl" /></a> ';

	    $renderer->doc .= "</div>";
	  }

	  else {
            $renderer->doc .= $this->getLang('fav_pasencore');
          }
        }
        return false;
    }


    function donneLien($page, $title="") {
      if (!plugin_isdisabled('pagelist')) $pagelist = plugin_load('helper', 'pagelist');

      if (!$pagelist) {
        $titrePage=explode(":",$page);
        $titrePage=$titrePage[sizeof($titrePage)-1];
	$titrePage=str_replace('_',' ',$titrePage);
      }
      else {
        $pagelist->page['id']=$page;
        $pagelist->page['exists'] = 1;
        $pagelist->_meta=NULL;
        $titrePage = $pagelist->_getMeta('title');
        if (!$titrePage) $titrePage = str_replace('_', ' ', noNS($page));
        $titrePage = hsc($titrePage);
     }
     if (@file_exists(fullpath(wikiFN($page)))) return "<a href='doku.php?id=".$page."' class='wikilink1' style='font-weight: lighter;' title='$page".$title."'>$titrePage</a>";
     else return "<a href='doku.php?id=".$page."' class='wikilink2' style='font-weight: lighter;' title='$page".$title."' rel='nofollow'>$titrePage</a>";
   }
}
?>
