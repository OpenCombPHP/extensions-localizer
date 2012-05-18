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
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
			'title'=> '文章内容',
			'view:langSetting'=>array(
				'template'=>'LangSetting.html',
				'class'=>'form',
				'widgets' => array(
				),
			),
		);
	}
	
	public function process()
	{	
// 		$sSQL = "show create table frameworktest_authora";
// 		$aRecords = DB::singleton()->query($sSQL);
// 		try{
// 			$aRecords = DB::singleton()->query($sSQL);
// 		}catch(Exception $e){
// 			$b = false;
// 		}
// 		if(!$b){
// 			echo "duile ";
// 		}
// 		exit;
// 		$arrDesc = $aRecords->fetchAll();
// 		$sCreate = $arrDesc[0]['Create Table'];
// 		$ss=str_replace('frameworktest_author', 'frameworktest_author12', $sCreate);
// 		var_dump($sCreate);
// 		var_dump($ss);
// 		DB::singleton()->execute($ss);
		
// 		$con = mysql_connect('192.168.1.1','root','1');var_dump($con);
// 		$db_selected = mysql_select_db('www',$con);var_dump($db_selected);
// 		$sSQL = "select * from opencms:article";
// 		$result = mysql_query($sSQL,$con);
// 		$property = mysql_fetch_field($result);
// 		while($property)
// 		{
// 			echo $property->name;
// 		}
// 		var_dump($result);exit;
		
		
		$arrLang=$this->langIterator();
		$this->viewLangSetting->variables()->set('arrLang',$arrLang) ;
		if($this->viewLangSetting->isSubmit())
		{
			//取得国家或者地区内容
			foreach($this->params['Country_text'] as $key=>$value)
			{
				if(empty($value))
				{
					$skey="国家或者地区";
					$this->viewLangSetting->createMessage(Message::error,"%s 请输入",$skey) ;
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
					$this->viewLangSetting->createMessage(Message::error,"%s 请输入",$skey) ;
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
					$this->viewLangSetting->createMessage(Message::error,"%s 请输入",$skey) ;
					return;
				}
				$arrTitle[]=$value;
			};
			
			$aSetting = Extension::flyweight('localizer')->setting();
			
			//检测是否已存在语言
			for($i=0;$i<count($this->params['Country_text']);$i++)
			{
				if($aSetting->hasItem('/',$this->params['Language_text'][$i].'_'.$this->params['Country_text'][$i]))
				{
					$skey="此语言";
					$this->viewLangSetting->createMessage(Message::error,"%s 已存在",$skey) ;
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
					$this->viewLangSetting->createMessage(Message::error,"%s 已存在",$skey) ;
					return;
				}
			}
			
			$aKey = $aSetting->key('/',true);
			if(count($aKey->itemIterator())==0)
			{
				for($i=0;$i<count($this->params['Country_text']);$i++)
				{
					$aSetting->setItem('/',$this->params['Language_text'][$i].'_'.$this->params['Country_text'][$i],
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
					$aSetting->setItem('/',$this->params['Language_text'][$i].'_'.$this->params['Country_text'][$i],
							array('title'=>$this->params['Title_text'][$i]
									,'selected'=>0
									,'country'=>$this->params['Country_text'][$i]
									,'lang'=>$this->params['Language_text'][$i]
									,'used'=>'1'
							)
					);
				}	
			}
			
		}
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