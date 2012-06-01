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
use org\jecat\framework\locale\Locale;
use org\jecat\framework\locale\SentenceLibrary;
use org\jecat\framework\setting\Setting;

class LangTranslation extends ControlPanel
{
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
			'title'=> '文章内容',
			'view:langTranslation'=>array(
				'template'=>'LangTranslation.html',
				'class'=>'form',
				'widget:paginator' => array(
						'class' => 'paginator' ,
				) ,
			),
		);
	}
	
	public function process()
	{	
		$arrLangCountry = array();
		$arrLangCountry = explode('_',$this->params['sSwichFrontLangPath']);
		if(count($arrLangCountry)!=2)
		{
			$skey = '请选择一个默认语言';
			$this->viewLangTranslation->createMessage(Message::error,"%s ",$skey) ;
			$arrLangSelectMenu = $this->getLangSelectMenu();
			$this->viewLangTranslation->variables()->set('arrLangSelectMenu',$arrLangSelectMenu);
			return;
		}
		
		$aLocale = Locale::flyweight($arrLangCountry[0], $arrLangCountry[1]);
		$sLangCountry = $aLocale->language().'_'.$aLocale->country();
		$arrSentenceLibrary = $this->getSelectSentenceLibrary($sLangCountry);
		$arrLangTranslationSelect = $this->setSelectSentenceLibraryPage(null,$arrSentenceLibrary);

			
		if(count($arrLangTranslationSelect)==0)
		{
			$bFlag = false;
			$this->viewLangTranslation->variables()->set('bFlag',$bFlag);
			
			$this->viewLangTranslation->variables()->set('sSpath',$sLangCountry);
			$arrLangSelectMenu = $this->getLangSelectMenu();
			$this->viewLangTranslation->variables()->set('arrLangSelectMenu',$arrLangSelectMenu);
		
		}else{
			
			$bFlag = true;
			$this->viewLangTranslation->variables()->set('bFlag',$bFlag);
			
			$arrLangTranslationChunk = array();
			$arrLangTranslationChunk = $this->getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20);
			$arrLangTranslationNew = $this->getSelectSentenceLibraryNew($sLangCountry, $arrLangTranslationChunk,1);

			
			$this->setPaginator($arrSentenceLibrary, $sLangCountry);
			
			$this->viewLangTranslation->variables()->set('sSpath',$sLangCountry);
			$arrLangSelectMenu = $this->getLangSelectMenu();
			$this->viewLangTranslation->variables()->set('arrLangSelectMenu',$arrLangSelectMenu);
			$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslationNew);
		}
		
		//提交
		if($this->viewLangTranslation->isSubmit())
		{
			$arrSentenceBase = array();
			$arrSentenceUi = array();
			$sPathBaseLibrarySentence='';
			$sPathUiLibrarySentence='';
			$i=0;
			
			//创建Ui和Base的语言库对象
			foreach($this->params['TranslationSentence'] as $key=>$value)
			{	
				//只取循环一次的
				if($i==0)
				{
					//Base
					$arrLangType=explode('@',$key);
					$arrLangCountry=explode('_',$arrLangType[0]);
					$aLocale = new Locale($arrLangCountry[0],$arrLangCountry[1]) ;
					$aSentenceBase=$aLocale->sentenceLibrary('base');
					$sSentenceBasePkgFileName=$aSentenceBase->packageFilename();
					$sPathBaseLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceBasePkgFileName;
					$arrSentenceBase = include $sPathBaseLibrarySentence;
					
					//Ui
					$aSentenceUi = $aLocale->sentenceLibrary('ui');
					$sSentenceUiPkgFileName = $aSentenceUi->packageFilename();
					$sPathUiLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceUiPkgFileName;
					$arrSentenceUi = include $sPathUiLibrarySentence;
				};
				$i++;
			}
			
			//将Ui和Base的语言库创建长数组
			foreach($this->params['TranslationSentence'] as $key=>$value)
			{
				$arrLangType=explode('@',$key);
				if($arrLangType[1]=='base')
				{	
					$arrSentenceBase[$arrLangType[2]]=$value;
				}else{
					$arrSentenceUi[$arrLangType[2]] = $value;
				}
			};
			file_put_contents($sPathBaseLibrarySentence,'<?php return'.' '.var_export($arrSentenceBase,true).';');
			file_put_contents($sPathUiLibrarySentence,'<?php return'.' '.var_export($arrSentenceUi,true).';');
		}
		
		//选择语言
		if($this->params['type'])
		{
			$sSpath=$this->params['sSwichFrontLangPath'];
			$this->langSwichFront($sSpath);
			
		}
		
		//翻页
		if($this->params['paginator'])
		{	
			$iCurrentPageNum = $this->params['paginator'];
			$sSpath = $this->params['sSwichFrontLangPath'];
			$arrSentenceLibrary = $this->getSelectSentenceLibrary($sSpath);
			$arrLangTranslationSelect = $this->setSelectSentenceLibraryPage('', $arrSentenceLibrary);
			$arrLangTranslationChunk = array();
			$arrLangTranslationChunk = $this->getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20);
			
			$arrLangTranslationNew = $this->getSelectSentenceLibraryNew($sSpath, $arrLangTranslationChunk,$iCurrentPageNum);
			$this->viewLangTranslation->variables()->set('sSpath',$sSpath);
			$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslationNew);
		}

	}
	
	//获得选择语言列表
	public function getLangSelectMenu()
	{
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/localizer',true);
		foreach($aKey->itemIterator() as $key=>$value){
			$arrLangSelectMenu[$value]=$aKey->item($value,array());
		}
		return $arrLangSelectMenu;
	}
	
	//获得语言包数组
	public function getSelectSentenceLibrary($sSpath)
	{
		$arrSpath = explode('_',$sSpath);
		$aLocale = new Locale($arrSpath[0],$arrSpath[1]) ;
		$aSentenceBase = $aLocale->sentenceLibrary('base');
		$sSentenceBasePkgFileName = $aSentenceBase->packageFilename();
		$sPathBaseLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceBasePkgFileName;
		
		$arrSentenceBase = array();
		$arrSentenceUi = array();
		$arrSentenceLibrary = array();
		
		if(file_exists($sPathBaseLibrarySentence))
		{
			$arrSentenceBase = include $sPathBaseLibrarySentence;
		}
		
		$aSentenceUi = $aLocale->sentenceLibrary('ui');
		$sSentenceUiPkgFileName = $aSentenceUi->packageFilename();
		$sPathUiLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceUiPkgFileName;
		if(file_exists($sPathUiLibrarySentence))
		{	
			$arrSentenceUi = include $sPathUiLibrarySentence;
		}

		$arrSentenceLibrary['base'] = $arrSentenceBase;
		$arrSentenceLibrary['ui'] = $arrSentenceUi;
		
		return $arrSentenceLibrary;
	}
	
	//获得分页内容
	public function getSelectSentenceLibraryNew($sSpath, $arrLangTranslationChunk,$iCurrentPageNum=1)
	{
		foreach($arrLangTranslationChunk[$iCurrentPageNum-1] as $key=>$value)
		{
			$arrLangTranslationNew[$sSpath][$value['type']][$value['hash']]=$value['value'];
		}
		return $arrLangTranslationNew;
	}
	
	

	public function setSelectSentenceLibraryPage($sSpath,$arrSentenceLibrary)
	{
		$arrLangTranslationSelect = array();
		foreach($arrSentenceLibrary['base'] as $keyHash=>$value)
		{
			$arrLangTranslationSelect[]=array('type'=>'base','hash'=>$keyHash,'value'=>$value);
		}
	
		foreach($arrSentenceLibrary['ui'] as $keyHash=>$value)
		{
			$arrLangTranslationSelect[]=array('type'=>'ui','hash'=>$keyHash,'value'=>$value);
		}
	
		return $arrLangTranslationSelect;
	}
	
	//
	public function getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20)
	{
		return array_chunk($arrLangTranslationSelect,$nPerPageRowNumber);
	}
	
	//切换前台语言
	public function langSwichFront($sSpath)
	{
		
		$sLangCountry = $sSpath;
		$arrLangCountry = explode('_',$sSpath);
		$aLocale = new Locale($arrLangCountry[0],$arrLangCountry[1]);
		$arrSentenceLibrary = $this->getSelectSentenceLibrary($sLangCountry);
		
		$arrLangTranslationSelect = $this->setSelectSentenceLibraryPage(null,$arrSentenceLibrary);
			
		if(count($arrLangTranslationSelect)==0)
		{
				$bFlag = false;
				$this->viewLangTranslation->variables()->set('bFlag',$bFlag);
				
				$this->setPaginator($arrSentenceLibrary, $sLangCountry);
				
				$this->viewLangTranslation->variables()->set('sLangCountry',$sLangCountry);
				$this->viewLangTranslation->variables()->set('sSpath',$sLangCountry);
				$arrLangSelectMenu = $this->getLangSelectMenu();
				$this->viewLangTranslation->variables()->set('arrLangSelectMenu',$arrLangSelectMenu);
				
		}else{
		
			$bFlag = true;
			$this->viewLangTranslation->variables()->set('bFlag',$bFlag);
			
			$arrLangTranslationChunk = array();
			$arrLangTranslationChunk = $this->getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20);
			$arrLangTranslationNew = $this->getSelectSentenceLibraryNew($sLangCountry, $arrLangTranslationChunk,1);
			
			$this->setPaginator($arrSentenceLibrary, $sLangCountry);
						
			$this->viewLangTranslation->variables()->set('sLangCountry',$sLangCountry);
			$this->viewLangTranslation->variables()->set('sSpath',$sLangCountry);
			$arrLangSelectMenu = $this->getLangSelectMenu();
			$this->viewLangTranslation->variables()->set('arrLangSelectMenu',$arrLangSelectMenu);
			$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslationNew);
			
		}
		
	}
	
	
	//设置分页器
	public function setPaginator($arrSentenceLibrary,$sLangCountry)
	{
		$nTotal = 0;
		$nTotal = count($arrSentenceLibrary['base']) + count($arrSentenceLibrary['ui']);
		$nPerPageRowNumber = 20;
		
		$this->viewLangTranslation->widget('paginator')->setTotalCount($nTotal);
		$this->viewLangTranslation->widget('paginator')->setPerPageCount($nPerPageRowNumber);
	}
	
}

?>