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

class LangSort extends ControlPanel
{
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
			'title'=> '文章内容',
			'view:langSort'=>array(
				'template'=>'LangSort.html',
				'class'=>'form',
				'widgets' => array(
				),
			),
		);
	}
	
	public function process()
	{	
		if($this->params['sortTag']=='up')
		{
			$sPathDown=$this->params['spath'];
			$arrLangOption=$this->langOption();
			$iNumberDown=array_search($sPathDown, $arrLangOption);
			if($iNumberDown==0)
			{
				$skey="不可以向上";
				$this->viewlangSort->createMessage(Message::error,"%s 移动",$skey) ;
				$sUrl="?c=org.opencomb.localizer.LangSetting";
				$this->location($sUrl,2);
				return;
			}
			$iNumberUp=$iNumberDown-1;
			$sPathUp=$arrLangOption[$iNumberUp];
			
			$arrDeleteUP=$this->deleteUp($sPathUp);
			$arrUp=$this->getUp($sPathUp);
			$arrNewLang=$this->getNewLang($sPathDown, $sPathUp, $arrDeleteUP, $arrUp);
			$this->setItem($arrNewLang);
			
			
			$sUrl="?c=org.opencomb.localizer.LangSetting";
			$this->viewlangSort->createMessage(Message::success,"%s ",$skey='向上移动成功');
			$this->location($sUrl,1);
			
		}else if($this->params['sortTag']=='down'){
			$sPathUp=$this->params['spath'];
			$arrLangOption=$this->langOption();
			$iNumberUp=array_search($sPathUp, $arrLangOption);
			if($iNumberUp+1==count($arrLangOption))
			{
				$skey="不可以向下";
				$this->viewlangSort->createMessage(Message::error,"%s 移动",$skey) ;
				$sUrl="?c=org.opencomb.localizer.LangSetting";
				$this->location($sUrl,2);
				return;
			}
			$iNumberDown=$iNumberUp+1;
			$sPathDown=$arrLangOption[$iNumberDown];
			
			$arrDeleteUP=$this->deleteUp($sPathUp);
			$arrUp=$this->getUp($sPathUp);
			$arrNewLang=$this->getNewLang($sPathDown, $sPathUp, $arrDeleteUP, $arrUp);
			$this->setItem($arrNewLang);
			
			
			$sUrl="?c=org.opencomb.localizer.LangSetting";
			$this->viewlangSort->createMessage(Message::success,"%s ",$skey='向下移动成功');
			$this->location($sUrl,1);
		}
	}
	
	
	
	//向上操作的所有方法
	public function langOption()
	{
		$arrLangOption = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/localizer',true);
		$i=0;
		foreach($aKey->itemIterator() as $key=>$value){
			$arrLangOption[$i++]=$value;
		}
		return $arrLangOption;
	}
	
	public function deleteUp($sPathUp)
	{
		$arrDeleteUP = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/localizer',true);
		$i=0;
		foreach($aKey->itemIterator() as $key=>$value)
		{
			if($value==$sPathUp)
			{
				continue;
			}else{
				$arrDeleteUP[$value]=$aKey->item($value,array());
			}
		}
		return $arrDeleteUP;
	}
	
	public function getUp($sPathUp)
	{
		$arrUp=array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/localizer',true);
		foreach($aKey->itemIterator() as $key=>$value)
		{
			if($value==$sPathUp)
			{
				$arrUp=$aKey->item($value,array());
			}
		}
		return $arrUp;
	}
	
	public function getNewLang($sPathDown,$sPathUp,$arrDeleteUP,$arrUp)
	{
		$arrNewLang=array();
		foreach($arrDeleteUP as $key=>$value)
		{
			if($key==$sPathDown)
			{
				$arrNewLang[$key]=$value;
				$arrNewLang[$sPathUp]=$arrUp;
			}else{
				$arrNewLang[$key]=$value;
			}
		}
		return $arrNewLang;
	}
	
	public function setItem($arrNewLang)
	{
		$aSetting = Extension::flyweight('localizer')->setting();
		$aSetting->deleteKey('/localizer');
		foreach($arrNewLang as $key=>$value)
		{
			$aSetting->setItem('/localizer',$key,$value);
		}
	}
}

?>