<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 senomedia <>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Mooslide' for the 'mooslide' extension.
 *
 * @author	Michael 'Iggy' Rudolph <info@sensomedia.de>
 * @package	TYPO3
 * @subpackage	tx_mooslide
 */
class tx_mooslide_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_mooslide_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_mooslide_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'mooslide';	// The extension key.
	var $pi_checkCHash = true;
	

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->uid		= $this->cObj->data['uid'];
		$this->conf		= $conf;
		$this->content	= $content;
		$this->pi_setPiVarDefaults();
		$this->pi_initPIflexForm(); 
		
		// if the ts value 'prefer_ts_over_ff' is set, the ts setup value takes precedence over the corresponding flexform value 
		$prefer_ts_over_ff = isset($this->conf['prefer_ts_over_ff']) && $this->conf['prefer_ts_over_ff'] = true ? true : false; 
		
		// gets the type of the following idlist, either it's content elements (ccollection) or pages (pcollection)
		$this->config['collectiontype'] =  $this->getConfMixValue('mainsetup','collectiontype', $prefer_ts_over_ff); 
		// and put it into cobjdata array.		
		$this->cObj->data['tx_mooslide_collectiontype'] = $this->config['collectiontype'];   
		
		// get either the list of pages or content elements into idlist. 		
		$this->config['idlist'] = $this->getConfMixValue('mainsetup', $this->config['collectiontype'], $prefer_ts_over_ff);
		// and put it into cobjdata array.		
		$this->cObj->data['tx_mooslide_idlist'] = $this->config['idlist'];
		// and sort them into slide sheets... 
		$idlist = explode(',',$this->config['idlist']);
		$this->slides = array();
		foreach ($idlist as $counter=>$idvalue) {
			if ( $this->config['collectiontype'] == 'pcollection' ) {
				$this->slides[] = $this->getCEList($idvalue);
			} else {
				$this->slides[] = $idvalue;
			}
		}
		$this->config['useinternalmootools'] = trim($this->conf['useinternalmootools']);

		$this->config['bkgdcolor'] = trim($this->getConfMixValue('mainsetup', 'bkgdcolor', $prefer_ts_over_ff));
		$this->config['width'] = intval($this->getConfMixValue('mainsetup', 'width', $prefer_ts_over_ff));
		$this->config['height'] = intval($this->getConfMixValue('mainsetup', 'height', $prefer_ts_over_ff));
		$this->config['slideinterval'] = intval($this->getConfMixValue('mainsetup', 'slideinterval', $prefer_ts_over_ff));
		$this->config['slidedirection'] = $this->getConfMixValue('mainsetup', 'slidedirection', $prefer_ts_over_ff);
		$this->config['slidepauseonmousein'] = $this->getConfMixValue('mainsetup', 'slidepauseonmousein', $prefer_ts_over_ff);
		$this->config['transitiontime'] = intval($this->getConfMixValue('mainsetup', 'transitiontime', $prefer_ts_over_ff));
		$this->config['transitiontype'] = $this->getConfMixValue('mainsetup', 'transitiontype', $prefer_ts_over_ff);
		$this->config['transitionease'] = $this->getConfMixValue('mainsetup', 'transitionease', $prefer_ts_over_ff);
		$this->config['halignment'] = $this->getConfMixValue('mainsetup', 'halignment', $prefer_ts_over_ff);
		$this->config['halignmenttext'] = $this->getConfMixValue('mainsetup', 'halignmenttext', $prefer_ts_over_ff);
				
		$this->config['clicklabelcontent'] = $this->getConfMixValue('clicklabel', 'clicklabelcontent', $prefer_ts_over_ff);		
		$this->config['clicklabelalignment'] = $this->getConfMixValue('clicklabel', 'clicklabelalignment', $prefer_ts_over_ff);		
		$this->config['slidertopmargin'] = $this->getConfMixValue('clicklabel', 'slidertopmargin', $prefer_ts_over_ff);		
		$this->config['sliderleftmargin'] = $this->getConfMixValue('clicklabel', 'sliderleftmargin', $prefer_ts_over_ff);		
		$this->config['sliderrightmargin'] = $this->getConfMixValue('clicklabel', 'sliderrightmargin', $prefer_ts_over_ff);		
		$this->config['sliderbottommargin'] = $this->getConfMixValue('clicklabel', 'sliderbottommargin', $prefer_ts_over_ff);		

		$this->config['showctrlbar'] = intval($this->getConfMixValue('navbar', 'showctrlbar', $prefer_ts_over_ff));
		$this->config['ctrlbarposition'] = $this->getConfMixValue('navbar', 'ctrlbarposition', $prefer_ts_over_ff);
		$this->config['ctrlbarwidth'] = intval($this->getConfMixValue('navbar', 'ctrlbarwidth', $prefer_ts_over_ff));
		$this->config['ctrlbarbkgdcolor'] = $this->getConfMixValue('navbar', 'ctrlbarbkgdcolor', $prefer_ts_over_ff);

		$this->config['buttonsource'] = $this->getConfMixValue('navbar', 'buttonsource', $prefer_ts_over_ff);
		$bsrc = $this->config['buttonsource'] == 'fileadmin'?'c':'d';
		$this->config['ctrlrightbutton'] = $this->getConfMixValue('navbar', $bsrc.'ctrlrightbutton', $prefer_ts_over_ff);
		$this->config['ctrlleftbutton'] = $this->getConfMixValue('navbar', $bsrc.'ctrlleftbutton', $prefer_ts_over_ff);
		$this->config['ctrlupbutton'] = $this->getConfMixValue('navbar', $bsrc.'ctrlupbutton', $prefer_ts_over_ff);
		$this->config['ctrldownbutton'] = $this->getConfMixValue('navbar', $bsrc.'ctrldownbutton', $prefer_ts_over_ff);
		$this->config['buttondir'] = $this->config['buttonsource'] == 'fileadmin' ? '/fileadmin/mooslidebuttons/' : '/'.t3lib_extMgm::siteRelPath($this->extKey).'res/defaultbuttons/';		
		
		if (strpos($this->config['slidedirection'],'previous')) { $this->slides = array_reverse($this->slides); };
		
		### calculate and assemble the border css
		$border = array();
		$this->config['border']='';
		foreach( array('top','left','right','bottom') as $key => $borderpos) {
			$border[$borderpos] = '';
			$style = $this->getConfMixValue('border', $borderpos.'style', $prefer_ts_over_ff);	
			$size = $this->getConfMixValue('border', $borderpos.'size', $prefer_ts_over_ff);	
			$color = trim($this->getConfMixValue('border', $borderpos.'color', $prefer_ts_over_ff));	
			if($size && !in_array($style,array('top','left','right','bottom','none'))) {
				$border[$borderpos] = $size.'px '.$style.(empty($color)?'':' '.$color);
			} elseif(in_array($style,array('top','left','right','bottom'))) {
				$border[$borderpos] = $style;
			}
		}
		
		foreach( array('top','left','right','bottom') as $key => $borderpos) {
			if ( in_array($border[$borderpos],array('top','left','right','bottom')) && !empty($border[$border[$borderpos]]) ) {
				$this->config['border'] .= '			border-'.$borderpos.':'.$border[$border[$borderpos]].';'.chr(10);
			} elseif (!in_array($border[$borderpos],array('top','left','right','bottom')) && !empty($border[$borderpos])) {
				$this->config['border'] .= '			border-'.$borderpos.':'.$border[$borderpos].';'.chr(10); 
			}
		}
		$this->config['border']=trim($this->config['border']);


		### get the records for the regarding CEs
		$content = "";
		foreach ($this->slides as $sheet => $ce_ids) {
			$records_conf = array(
				'tables' => 'tt_content',
				'source' => $ce_ids,
				'dontCheckPid' => 1
			);
			$content .= '<div>
							'.$this->cObj->RECORDS($records_conf).'
						</div>
						';
		}
		
		### create the neccesary styles/js includes and the call to the mooslide library
		$this->createJSandStyles();

		### clicklabel - get image w/ link from content element 
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tt_content', 
			'uid='.intval($this->config['clicklabelcontent']).' '.$this->cObj->enableFields('tt_content'), 
			$groupBy='',
			$orderBy='',
			$limit=''
		);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		### create IMAGE object if there's actually an image
		if($row) {
			$img = explode(',',$row['image']);
			$img = trim($img[rand(0,count($img)-1)]);
			$imgconfig['file']='uploads/pics/'.$img;
			if($row['imagewidth']) $imgconfig['file.']['width'] = $row['imagewidth'];
			if($row['imageheight']) $imgconfig['file.']['height'] = $row['imageheight'];
			if($row['altText']) $imgconfig['altText'] = $row['altText'];
			if($row['titleText']) $imgconfig['titleText'] = $row['titleText'];
			$imgconfig['params'] = "align=".$this->config['clicklabelalignment'];
			$bkgdimg = $this->cObj->typolink( $this->cObj->IMAGE($imgconfig), array('parameter' => $row['image_link']) );
		} else {
			$bkgdimg = "&nbsp;";
		}
		
		### assemble the mooslide container 
		$ctrlbuttons = $this->config['showctrlbar'] ? '
				<div id="mooslidebuttons'.$this->uid.'">
					<div id="mooslidenext'.$this->uid.'"></div>
					<div id="mooslideprev'.$this->uid.'"></div>
				</div>' : '';
		$returndiv = '
			<div class="mooslideposition'.$this->config['halignment'].'">
				<div id="mooslidecontainer'.$this->uid.'">'.$ctrlbuttons.'
					<div id="mooslidebkgd'.$this->uid.'">'.$bkgdimg.'</div>
					<div id="mooslidemask'.$this->uid.'">	
						<div id="'.$this->prefixId.$this->uid.'">
							'.$content.'
						</div>
					</div>
				</div>
			</div>
			';
		return $returndiv;
		
	}
 	


	/**
	 * get a specific value from either flexform or tsconfig,
	 *
	 * @param	string	$sheet: flexform sheet name
	 * @param	string	$field: flexform field name
	 * @param	string	$prefer_ts_over_ff: returns the ts value (if not empty) instead of the flexform value 
	 * @return	string	value of flexform/tsconf
	 */
	function getConfMixValue ($sheet, $field, $prefer_ts_over_ff=false) {
		$sheet = empty($sheet) ? 'sDEF' : $sheet;
		if( $prefer_ts_over_ff && strlen(trim($this->conf[$field])) ) {
			return trim($this->conf[$field]);
		} else {
			$ffvalue = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $field, $sheet);
			return strlen(trim($ffvalue)) ? trim($ffvalue) : trim($this->conf[$field]);
		}
	}  
	
	
	/**
	 * get list of uid's for all the content elements in a single page
	 *
	 * @param	int $uid: the uid of the page where we want to get the CE uids from
	 * @return	string list of all the CE uid's in the page
	 */	
	function getCEList ($uid) {
		
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'tt_content',
			'pid='.intval($uid).$this->cObj->enableFields('tt_content'),
			'',
			'',
			''
		);
		
		$pidlist = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$pidlist[] = $row['uid'];
		}
		return implode(',',$pidlist);
	}
	

	/**
	* inject some js and css into the header
	*
	* @return	void
	*/	
	function createJSandStyles () { 
		
		### define some shortcut and prepared variables 
		$ml = $this->config['sliderleftmargin'];
		$mr = $this->config['sliderrightmargin'];
		$mt = $this->config['slidertopmargin'];
		$mb = $this->config['sliderbottommargin'];
		$w = $this->config['width'];
		$h = $this->config['height'];
		$bpos = $this->config['ctrlbarposition'];
		$bwidth = $this->config['ctrlbarwidth'];
		list( $slidemode, $slidedir ) = explode('_',$this->config['slidedirection']);
		$bgclr = !empty($this->config['bkgdcolor'])?$this->config['bkgdcolor']:'transparent';
		$buttondir=$this->config['buttondir'];
		
		$bstyles = '';
		if($this->config['showctrlbar']) {
			$bbgcolor = !empty($this->config['ctrlbarbkgdcolor'])?$this->config['ctrlbarbkgdcolor']:'transparent';
			switch( $bpos ) {
				case 'left': 
					$bcss = 'top:'.($mt).'px; left:'.($ml).'px; height:'.($h-$mt-$mb).'px; width:'.$bwidth.'px;';
					$ml += $bwidth;
					$panelheight = $h-$mt-$mb;
					$pheight = floor(($h-$mt-$mb)/2);
					$nheight = ceil(($h-$mt-$mb)/2);
					$pwidth = $bwidth;
					$nwidth = $bwidth;
					$prevpos = 'bottom center';
					$nextpos = 'top center';
					break;
				case 'right': 
					$bcss = 'top:'.($mt).'px; left:'.($w-$bwidth-$mr).'px; height:'.($h-$mt-$mb).'px; width:'.$bwidth.'px;';
					$mr += $bwidth;
					$panelheight = $h-$mt-$mb;
					$pheight = floor(($h-$mt-$mb)/2);
					$nheight = ceil(($h-$mt-$mb)/2);
					$pwidth = $bwidth;
					$nwidth = $bwidth;
					$prevpos = 'bottom center';
					$nextpos = 'top center';
					break;
				case 'top': 
					$bcss = 'top:'.($mt).'px; left:'.($ml).'px; height:'.$bwidth.'px; width:'.($w-$ml-$mr).'px;';
					$mt += $bwidth;
					$panelheight = $bwidth;
					$pwidth = floor(($w-$ml-$mr)/2);
					$nwidth = ceil(($w-$ml-$mr)/2);
					$pheight = $bwidth;
					$nheight = $bwidth;
					$prevpos = 'center right';
					$nextpos = 'center left';
					break;				
				case 'bottom': 
					$bcss = 'top:'.($h-$bwidth-$mb).'px; left:'.($ml).'px; height:'.$bwidth.'px; width:'.($w-$ml-$mr).'px;';
					$mb += $bwidth;
					$panelheight = $bwidth;
					$pwidth = floor(($w-$ml-$mr)/2);
					$nwidth = ceil(($w-$ml-$mr)/2);
					$pheight = $bwidth;
					$nheight = $bwidth;
					$prevpos = 'center right';
					$nextpos = 'center left';
					break;
			};
			### select navigation directions corresponding to the slidemode (horizontal=right/left, vertical=up/down)
			$prevdir = $slidemode=='h'?'right':'up';
			$nextdir = $slidemode=='h'?'left':'down';
		
			### create paths to the previous next button images if required
			$prevsubdir=$slidemode=='h'?'right':'down';
			$previmg = strlen($this->config['ctrl'.$prevsubdir.'button']) ? $buttondir.$prevsubdir.'/'.$this->config['ctrl'.$prevsubdir.'button'] : '/'.t3lib_extMgm::siteRelPath($this->extKey).'res/defaultbuttons/default_'.$prevsubdir.'.gif';
			
			$nextsubdir=$slidemode=='h'?'left':'up';
			$nextimg = strlen($this->config['ctrl'.$nextsubdir.'button']) ? $buttondir.$nextsubdir.'/'.$this->config['ctrl'.$nextsubdir.'button'] : '/'.t3lib_extMgm::siteRelPath($this->extKey).'res/defaultbuttons/default_'.$nextsubdir.'.gif';;
			
			$bstyles = '
			#mooslidebuttons'.$this->uid.'{ position:relative; padding:0; margin:0; z-index:100; background-color:'.$bbgcolor.';'.$bcss.'}
			#mooslideprev'.$this->uid.'{color:#0080FF;padding:0; cursor:pointer; height:'.$pheight.'px; width:'.$pwidth.'px; float:right; background-repeat: no-repeat; background-image:url(\''.$previmg.'\'); background-position:'.$prevpos.';}
			#mooslidenext'.$this->uid.'{color:#0080FF;padding:0; cursor:pointer; height:'.$nheight.'px; width:'.$nwidth.'px; float:left; background-repeat: no-repeat; background-image:url(\''.$nextimg.'\'); background-position:'.$nextpos.';}';
		}

		$size = $slidemode=='h'?($w-$ml-$mr):($h-$mt-$mb);
		
		$jshead = array();
		### require mootools from t3mootools if available (as suggested by this extension).  use internal if not loaded...   
		### but only if 'useinternalmootools' is set with the constant editor.
		if($this->config['useinternalmootools']){
			if (t3lib_extMgm::isLoaded('t3mootools')) {
				require_once(t3lib_extMgm::extPath('t3mootools').'class.tx_t3mootools.php');
			} else if (t3lib_extMgm::isLoaded('t3mootools12')) {
				require_once(t3lib_extMgm::extPath('t3mootools12').'class.tx_t3mootools12.php');
			}
			if (defined('T3MOOTOOLS')) {
				tx_t3mootools::addMooJS();
			} else if (defined('T3MOOTOOLS12')) {
				tx_t3mootools12::addMooJS();
			} else {
				$jshead[] = '	<script src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/mootools.js" type="text/javascript"></script>';
			} 
		}
		$jshead[] = '	<script src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/mooslide.js" type="text/javascript"></script>';
		### inject script tags into the header to load necessary libs	
		$GLOBALS['TSFE']->additionalHeaderData[$this->extKey] = $this->headerData.implode(chr(10),$jshead);

		### need to add these styles only once, no matter how much tickers are on the same page
		$GLOBALS['TSFE']->setCSS($this->extKey.'global','
		.mooslidepositionleft { position:relative; float:left; margin-right:5px; }
		.mooslidepositionright { position:relative; float:right; margin-left:5px; }
		.mooslidepositioncenter { position:relative; width:100%; }
		');		
		
		### this needs to go to the styles for each ticker individually
		$GLOBALS['TSFE']->setCSS($this->extKey,$GLOBALS['TSFE']->additionalCSS[$this->extKey].'
		.mooslidepositioncenter #mooslidecontainer'.$this->uid.'{ margin:0 auto; }'.$bstyles.'
		#mooslidemask'.$this->uid.'{
			margin:0px;
			position:relative;
			left:'.$ml.'px;
			top:'.($mt-$panelheight-$h).'px;
			width:'.($w-$ml-$mr).'px;
			height:'.($h-$mt-$mb).'px;
			overflow:hidden;
			text-align:'.$this->config['halignmenttext'].';
			background-color:'.$bgclr.'
		}
		#mooslidecontainer'.$this->uid.'{
			width:'.$w.'px;
			height:'.$h.'px;
			'.$this->config['border'].'				
			margin:0px;
			overflow:hidden;
		}
		#mooslidebkgd'.$this->uid.'{
			position:relative;
			width:'.$w.'px;
			height:'.$h.'px;
			top:'.(-$panelheight).'px;
			overflow:hidden;	
			margin:0px;
		}
		#'.$this->prefixId.$this->uid.'{ position:absolute; }
		#'.$this->prefixId.$this->uid.' div{
			width:'.($w-10-$ml-$mr).'px;
			height:'.($h-4-$mt-$mb).'px;
			padding-top:2px;
			padding-left:5px;
			padding-right:5px;
			padding-bottom:2px;
			float:left;
		}
		#'.$this->prefixId.$this->uid.' div div{
			width:auto;
			height:auto;
			padding:0px;
			margin:0px;
		}
		#'.$this->prefixId.$this->uid.' p{ 
			width:auto;
			height:auto;
			padding:0px;
			margin:0px;		
			float:none;
			text-align:'.$this->config['halignmenttext'].';
		}
		');
		
		
		### the mooslide call gets assembled and pushed to the header here.
		$slideitems = implode(',', array_map( create_function('$key','return "\'".$key."\'";'), array_keys($this->slides) ) );
		$startitem = ($slidedir=='next') ? 0 : (count($this->slides)-1) ;
		$trtype = $this->config['transitiontype'];
		$trease = $this->config['transitionease'];
		$interval = $this->config['slideinterval'];
		$pauseonmouse = $this->config['slidepauseonmousein'];
		### if transition time is less than 50ms shorter than the interval time, we need to fix this or transition will look really itchy
		$trtime = ($this->config['transitiontime']) > $interval-50 ? $interval-50 : $this->config['transitiontime'];
		
		$buttons = $this->config['showctrlbar'] ? "
					previous: $('mooslideprev".$this->uid."'),
					next: $('mooslidenext".$this->uid."')" : "";
	
		$GLOBALS['TSFE']->setJS($this->extKey.$this->uid,"
		window.addEvent('load',function(){
			var mooslide".$this->uid." = new mooSlide({
				mode: '".$slidemode."',
				container: $('".$this->prefixId.$this->uid."'),
				items: [".$slideitems."],
				startitem: ".$startitem.",
				size: ".$size.",
				autorun: true,
				direction: '".$slidedir."',
				interval: ".$interval.",
				pauseonmouse: ".$pauseonmouse.",
				FxStyle: {
					duration: ".$trtime.",
					transition: Fx.Transitions.".$trtype.$trease.",
					wait: true
				},
				buttons: { ".$buttons."
				}
			});
		});
		");   #	stop: $('stop'),	play: $('play'),

	}	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mooslide/pi1/class.tx_mooslide_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mooslide/pi1/class.tx_mooslide_pi1.php']);
}

?>