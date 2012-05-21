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

class LangDelete extends ControlPanel
{
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
			'title'=> '文章内容',
			'view:langDelete'=>array(
				'template'=>'LangDelete.html',
				'class'=>'form',
				'widgets' => array(
				),
			),
		);
	}
	
	public function process()
	{	
		$dPath=$this->params['dpath'];
		$arrLang=$this->langIterator();
		$fSelected=false;
		if($arrLang[$dPath]['selected']==1)
		{
			$fSelected=true;
		}
		//var_dump($arrLang);exit;
		$aSetting = Extension::flyweight('localizer')->setting();
		$aSetting->deleteItem('/', $dPath);
		$arrNewLang=$this->langIterator();
		$i=0;
		foreach($arrNewLang as $key=>$value)
		{
			if($i==0)
			{
				$arrNewLang[$key]['selected']=1;
			}
			$i++;
		}
		$aSetting->deleteKey('/');
		foreach($arrNewLang as $key=>$value)
		{
			$aSetting->setItem('/',$key,$value);
		}
		
		
		$sUrl="?c=org.opencomb.localizer.LangSetting";
		$this->viewLangDelete->createMessage(Message::success,"%s ",$skey='删除成功');
		$this->location($sUrl,0);
		
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