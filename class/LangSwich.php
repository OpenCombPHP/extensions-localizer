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
use org\jecat\framework\util\EventManager;

class LangSwich extends Controller
{
	const swichLang = 'swichLang' ;
	
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
// 			'title'=> '文章内容',
// 			'view:langSwich'=>array(
// 				'template'=>'org.opencomb.localizer.langSwich.html',
// 				'class'=>'view',
// 			),
		);
	}
	
	public function process()
	{
		if($this->params['langnew'])
		{
			$sLangCountryNew = $this->params['langnew'];
			$arrLangCountry = explode('_',$sLangCountryNew);
			$sDpath = $sLangCountryNew;
			$arrLang =LangSwich::langIterator();
			$arrLang[$sDpath]['selected']=1;
			
			foreach($arrLang as $key=>$value)
			{
				if($key!=$sDpath)
				{
					$arrLang[$key]['selected']=0;
				}else{
					$arrLang[$key]['selected']=1;
				}
			
			}
			
			$aSetting = Extension::flyweight('localizer')->setting();
			$aSetting->deleteKey('/');
			foreach($arrLang as $key=>$value)
			{
				$aSetting->setItem('/',$key,$value);
			}
			
			Locale::switchSessionLocale($arrLangCountry[0],$arrLangCountry[1],true);
			
			// 触发事件
			$aEventManager = EventManager::singleton() ;
			$arrEventArgvs = array($this->params['langnew'],$this->params['langold'],$this->params['swichLangPageUrl']);
			$aEventManager->emitEvent(__CLASS__,self::swichLang,$arrEventArgvs) ;
			$sPageUrl = $this->params['swichLangPageUrl'];
// 			echo stripos('addc','c');
// 			echo stripos($_SERVER['REQUEST_URI'],'&swichLangPageUrl');
	
			$iStart = stripos($_SERVER['REQUEST_URI'],'&swichLangPageUrl')+18;
			$this->location(substr($_SERVER['REQUEST_URI'],$iStart),0);
		};
	}
	
	static function langIterator(){
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/',true);
		foreach($aKey->itemIterator() as $key=>$value){
			$arrLang[$value]=$aKey->item($value,array());
		}
		return $arrLang;
	}
}