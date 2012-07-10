<?php
namespace org\opencomb\localizer;

use org\jecat\framework\lang\Object;
use org\jecat\framework\message\Message;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\mvc\view\widget\menu\Menu;
use org\jecat\framework\mvc\view\View;
use org\jecat\framework\mvc\controller\Controller;
use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\bean\BeanFactory;
use org\opencomb\frameworktest\aspect;
use org\opencomb\platform\system\PlatformSerializer;
use org\jecat\framework\ui\xhtml\weave\Patch;
use org\jecat\framework\ui\xhtml\weave\WeaveManager;
use org\opencomb\coresystem\mvc\controller\ControlPanel;
use org\opencomb\coresystem\mvc\controller\ControlPanelFrame;
use org\jecat\framework\locale\Locale;

class LangTranslationSelect extends ControlPanel  	
{	
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array();
	}
	
	public function process()
	{	
		$sSpath = $_GET['spath'];echo $sSpath;exit;
		$arrLangTranslation = $this->langIterator($sSpath);
		$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslation);
		$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslation);
	}
	
	public function langIterator($sSpath){
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKeyBase = $aSetting->key('/translation/'.$sSpath.'base',true);
		$aKeyUi = $aSetting->key('/translation/'.$sSpath.'ui',true);
		foreach($aKeyBase->itemIterator() as $key=>$value){
			$arrLang[$sSpath]['base'] = $aKeyBase->item($value,array());
		}
		
		foreach($aKeyUi->itemIterator() as $key=>$value){
			$arrLang[$sSpath]['ui'] = $aKeyUi->item($value,array());
		}
		
		return $arrLang;
	}
	
}