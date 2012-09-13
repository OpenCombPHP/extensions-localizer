<?php
namespace org\opencomb\localizer;
use org\jecat\framework\verifier\Length;

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
use org\jecat\framework\setting\Setting;

class LangDelete extends ControlPanel
{
	protected $arrConfig = array(
					'title'=> '文章内容',
					'view'=>array(
							'template'=>'LangDelete.html',
							'class'=>'view',
					),
	);
	
	public function process()
	{	
		$dPath = $this->params['dpath'];
		$arrLang = $this->langIterator();
		$sUrl = "?c=org.opencomb.localizer.LangSetting";
		if($arrLang[$dPath]['selected'] == 1)
		{
			$skey = "默认语言不能被删除";
			$this->createMessage(Message::error,"%s",$skey) ;
			$this->location($sUrl,0);
			return;
		}
		$aSetting = Extension::flyweight('localizer')->setting();
		$aSetting->deleteValue('/localizer/'.$dPath);
		$arrNewLang = $this->langIterator();
		
		$aSetting->deleteKey('/localizer');
		foreach($arrNewLang as $key=>$value)
		{
			$aSetting->setValue('/localizer/'.$key,$value);
		}
		
		$this->createMessage(Message::success,"%s ",$skey='删除成功');
		$this->location($sUrl,1);
		
	}
	
	public function langIterator(){
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		foreach($aSetting->value('/localizer',array()) as $key => $value){
			$arrTemp = $aSetting->value('/localizer/'.$key);
			if($arrTemp['used'] == '1'){
				$arrLang[$key] = $arrTemp;
			}
		}
		return $arrLang;
	}
	
}
