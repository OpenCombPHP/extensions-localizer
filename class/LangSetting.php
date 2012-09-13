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
use org\jecat\framework\db\DB;


class LangSetting extends ControlPanel
{
	
	protected $arrConfig = array(
			'title'=> '本地化设定',
			'view'=>array(
					'template'=>'LangSetting.html',
					'class'=>'view',
			),
	);
	
	public function process()
	{
		$this->doActions();
		$arrLang=$this->langIterator();
		$this->view->variables()->set('arrLang',$arrLang) ;
	}	
		
	public function form()
	{
		//取得国家或者地区内容
		foreach($this->params['Country_text'] as $key=>$value)
		{
			if(empty($value))
			{
				$skey="国家或者地区";
				$this->createMessage(Message::error,"%s 请输入",$skey) ;
				return;
			}
			$arrCountry[]=$value;
		};

		//取得语言内容
		foreach($this->params['Language_text'] as $key=>$value)
		{
			if(empty($value))
			{
				$skey="语言";
				$this->createMessage(Message::error,"%s 请输入",$skey) ;
				return;
			}
			$arrLanguage[]=$value;
		};
		
		//取得标题内容
		foreach($this->params['Title_text'] as $key=>$value)
		{
			if(empty($value))
			{
				$skey="标题";
				$this->createMessage(Message::error,"%s 请输入",$skey) ;
				return;
			}
			$arrTitle[]=$value;
		};
		
		$aSetting = Extension::flyweight('localizer')->setting();
		
		//检测是否已存在语言
		for($i=0;$i<count($this->params['Country_text']);$i++)
		{
			if($aSetting->hasValue('/localizer/'.$this->params['Language_text'][$i].'_'.$this->params['Country_text'][$i]))
			{
				$skey="此语言";
				$this->createMessage(Message::error,"%s 已存在",$skey) ;
				return;
			}
			
			$bFlagTitle=false;
			$arrLang=$this->langIterator();
			foreach($arrLang as $key=>$value)
			{
				if($value['title']==$this->params['Title_text'][$i])
				{
					$bFlagTitle=true;
				}
			}
			
			if($bFlagTitle)
			{
				$skey="此标题";
				$this->createMessage(Message::error,"%s 已存在",$skey) ;
				return;
			}
		}
		
		$aKey = $aSetting->value('/localizer',array());
		if(count($aKey)==0)
		{
			for($i=0;$i<count($this->params['Country_text']);$i++)
			{
				$aSetting->setValue('/localizer/'.$this->params['Language_text'][$i].'_'.$this->params['Country_text'][$i],
						array('title'=>$this->params['Title_text'][$i]
							  ,'selected'=>$i==0 ?1:0
							  ,'country'=>$this->params['Country_text'][$i]
							  ,'lang'=>$this->params['Language_text'][$i]
							  ,'used'=>'1'
						)
						
						
					);
			}
		}else{
			for($i=0;$i<count($this->params['Country_text']);$i++)
			{
				$aSetting->setValue('/localizer/'.$this->params['Language_text'][$i].'_'.$this->params['Country_text'][$i],
						array('title'=>$this->params['Title_text'][$i]
								,'selected'=>0
								,'country'=>$this->params['Country_text'][$i]
								,'lang'=>$this->params['Language_text'][$i]
								,'used'=>'1'
						)
				);
			}	
		}
		$arrLang=$this->langIterator();
		$this->view->variables()->set('arrLang',$arrLang) ;
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
