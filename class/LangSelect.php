<?php
namespace org\opencomb\localizer;

use org\opencomb\coresystem\user\UserModel;
use org\jecat\framework\mvc\model\db\Model;
use org\jecat\framework\bean\BeanFactory;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\io\IOutputStream;
use org\jecat\framework\util\IHashTable;
use org\jecat\framework\ui\UI;
use org\jecat\framework\mvc\model\IModel;
use org\jecat\framework\mvc\view\widget\Widget;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\locale\Locale;

class LangSelect extends Widget {
	public function __construct($aUserModel=null, $sId = '', $sTitle = null,  IView $aView = null) {
		parent::__construct ( $sId, 'localizer:LangSelect.html',$sTitle, $aView );
	}
	
	public function display(UI $aUI,IHashTable $aVariables=null,IOutputStream $aDevice=null)
	{	
		parent::display($aUI, $aVariables,$aDevice);
	}
	
	public function langIterator()
	{	
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		foreach($aSetting->value('/localizer',array()) as $key => $value){
			$arrTemp = $aSetting->value('/localizer/'.$key);
			if($arrTemp['used'] == '1'){
				$arrLang[$key] = $arrTemp;
			}
		}
		if(count($arrLang)){
			return $arrLang;
		}else{
			$aSetting = Setting::singleton();
			$sLanguage = $aSetting->value('service/locale/language','CN');
			$sCountry = $aSetting->value('service/locale/country','zh');
			if($aSetting->value('service/locale/title',array()))
			{
				$sTitle = $aSetting->value('service/locale/title',array());
			}else{
				$sTitle = "简体中文";
			};
			
			$arrItem = array(
					 'title'=>$sTitle
					,'selected'=>1
					,'country'=>$sCountry
					,'lang'=>$sLanguage
					,'used'=>'1'
			);
			
			$aSettingLocalizer = Extension::flyweight('localizer')->setting();
			$aSettingLocalizer->setValue(
				'/localizer/'.$sLanguage.'_'.$sCountry,
				$arrItem
			);
			
			foreach($aSetting->value('/localizer',array()) as $key => $value){
				$arrTemp = $aSetting->value('/localizer/'.$key);
				if($arrTemp['used'] == '1'){
					$arrLang[$key] = $arrTemp;
				}
			}
			return $arrLang;
		}
	}
	
	public function selectedLangCountry()
	{
		$aSetting = Extension::flyweight('localizer')->setting();
		foreach($aSetting->value('/localizer',array()) as $key => $value){
			$arrTemp = $aSetting->value('/localizer/'.$key);
			if($arrTemp['selected']==1)
			{
				$sSeletedLangCountry = $arrTemp['lang'].'_'.$arrTemp['country'];
			}
		}
		return $sSeletedLangCountry;
	}
	
	public function setCurrentLang()
	{
		$aLocale = Locale::singleton();
		$sLangCountry = $aLocale->language().'_'.$aLocale->country();
		return $sLangCountry;
	}
}
