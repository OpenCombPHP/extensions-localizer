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

class LangUsed extends ControlPanel
{
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
			'title'=> '文章内容',
			'view:langUsed'=>array(
				'template'=>'LangSetting.html',
				'class'=>'form',
				'widgets' => array(
				),
			),
		);
	}
	
	public function process()
	{	
		$arrLang=$this->langIterator();
		$sDpath=$_GET['dpath'];
		if($arrLang[$sDpath]['used']=="1")
		{
			$arrLang[$sDpath]['used']='0';
		}
		else
		{
			$arrLang[$sDpath]['used']='1';
		}
		$aSetting = Extension::flyweight('localizer')->setting();
		$aSetting->setItem('/',$sDpath,$arrLang[$sDpath]);
	}
	
	public function langIterator(){
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/',true);
		foreach($aKey->itemIterator() as $key=>$value){
			$arrLang[$value]=$aKey->item($value,array());
		}
		return $arrLang;
	}
}

?>