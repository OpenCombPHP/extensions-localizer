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
				'widgets' => array(
				),
			),
		);
	}
	
	public function process()
	{	
		$aLocale=Locale::singleton();
		$sLangCountry = $aLocale->language().'_'.$aLocale->country();
		$arrSentenceLibrary = $this->getSelectSentenceLibrary($sLangCountry);
		$arrLangTranslationSelect = $this->setSelectSentenceLibraryPage(null,$arrSentenceLibrary);
		$arrLangTranslationChunk = $this->getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20);
		$arrLangTranslationNew = $this->setSelectSentenceLibraryNew($sLangCountry, $arrLangTranslationChunk,0,$nPerPageRowNumber=20);

		$aSentenceBase=$aLocale->sentenceLibrary('base');
		$aSentenceUi=$aLocale->sentenceLibrary('ui');
		
		$nTotal=0;
		$sSentenceBasePkgFileName=$aSentenceBase->packageFilename();
		$sPathBaseLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceBasePkgFileName;
		$arrSentenceBase = array();
		if(file_exists($sPathBaseLibrarySentence))
		{	
			$arrSentenceBase = include $sPathBaseLibrarySentence;
		}

		$aSentenceUi = $aLocale->sentenceLibrary('ui');
		$sSentenceUiPkgFileName = $aSentenceUi->packageFilename();
		$sPathUiLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceUiPkgFileName;
		$arrSentenceUi = array();
		if(file_exists($sPathUiLibrarySentence))
		{		
			$arrSentenceUi = include $sPathUiLibrarySentence;
		}
		
		$nTotal = count($arrSentenceBase)+count($arrSentenceUi);
		
		$nPerPageRowNumber = 20;
		$nPage = ceil($nTotal/$nPerPageRowNumber);
		
		if($nPage>1)
		{
			for($i=0;$i<$nPage;$i++)
			{
				$arrPage[$i+1]=$i*$nPerPageRowNumber;
			}
			$this->viewLangTranslation->variables()->set('arrPage',$arrPage);
		}else if($nPage>0){
			$arrPage[1]=0;
			$this->viewLangTranslation->variables()->set('arrPage',$arrPage);
		}
		
		$selectedPage = 0;
		$this->viewLangTranslation->variables()->set('sLangCountry',$sLangCountry);
		$this->viewLangTranslation->variables()->set('sSpath',$sLangCountry);
		$arrLangSelectMenu = $this->getLangSelectMenu();
		$this->viewLangTranslation->variables()->set('arrLangSelectMenu',$arrLangSelectMenu);
		$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslationNew);
		$this->viewLangTranslation->variables()->set('selectedPage',$selectedPage);
		
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
			$this->displayTranslationLibrary();
		}
		
		//选择语言
		if($this->params['spath'])
		{
			$sSpath=$this->params['spath'];
			$this->langSwichFront($sSpath);
		}
		
		//翻页
		if($this->params['selectpage'])
		{
			$sSpath=$this->params['spath'];
			$nNumberRow = $this->params['rownumber'];
			$selectedPage = $nNumberRow;
			$arrSentenceLibrary = $this->getSelectSentenceLibrary($sSpath);
			$arrLangTranslationSelect = $this->setSelectSentenceLibraryPage($sSpath, $arrSentenceLibrary);
			
			$arrLangTranslationChunk = $this->getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20);
			$arrLangTranslationNew = $this->setSelectSentenceLibraryNew($sSpath, $arrLangTranslationChunk,$nNumberRow,$nPerPageRowNumber=20);
			$this->viewLangTranslation->variables()->set('sSpath',$sSpath);
			$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslationNew);
			$this->viewLangTranslation->variables()->set('selectedPage',$selectedPage);
		}
		
		
		
		
		//页面初始化

		

		
		//第一版
		/*
		 $aLocale=Locale::singleton();
		
		$aSentenceBase=$aLocale->sentenceLibrary('base');
		$aSentenceUi=$aLocale->sentenceLibrary('ui'); 
		 
		foreach($aSentenceBase->arrSentences() as $keyHash=>$value)
		{	$nTotal++;
			$arrLangTranslation[$aSentenceBase->language().'_'.$aSentenceBase->country()]['base'][$keyHash]=$value;
		}
		echo $nTotal;
		foreach($aSentenceUi->arrSentences() as $keyHash=>$value)
		{	$nTotal++;
			$arrLangTranslation[$aSentenceUi->language().'_'.$aSentenceUi->country()]['ui'][$keyHash]=$value;
		}
		echo $nTotal++;
		$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslation);
		$arrLang=$this->langIterator();
		$this->viewLangTranslation->variables()->set('arrLang',$arrLang);
		*/
		
		
		
		
		
		/*
		
		var_dump($arrLangTranslation);exit;
		//$sSentenceBasePkgFileName=$aSentenceBase->packageFilename();
		$sPathLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentencePkgFileName;
		//$arrSentence = include $sPathLibrarySentence;
		$arrSentence['5b18ddcd4ba10ba0e404e83f9fadaf26']= 'hahaha';
		//var_dump($arrSentence);
		//echo var_export($arrSentence,true);
		file_put_contents($sPathLibrarySentence,'<?php return'.var_export($arrSentence,true));
		exit;
		$arrSentence = include 'home/qusong/oc.release/services/ooc/data/extensions/localizer/lang/aa_bb.base.php';
		
		var_dump($arrSentence);exit;
		//$aSentenceBase= new SentenceLibrary('aa','bb','base');
		//$aSentenceBase->setSentence('aaddf','12121211');
		$arrNewSentences=$aSentenceBase->unarchiveSentences();
		var_dump(var_export($arrNewSentences),true);
		var_dump($aSentenceBase);
		
		*/
		
		/*对setting的操作
		if($this->viewLangTranslation->isSubmit())
		{
			$aSetting = Extension::flyweight('localizer')->setting();
			
			foreach($this->params['Country_text'] as $key=>$value)
			{
				$aSetting->setItem('/tranlation/', $key, $value);
			};
		}
		*/
		
		/*逆向操作
		if($this->viewLangTranslation->isSubmit())
		{
			$aSetting = Extension::flyweight('localizer')->setting();
		
			foreach($this->params['Country_text'] as $key=>$value)
			{
				$aSentence->setSentence($key,$value);
			};
		}
		*/
		/*
		$arrLang=$this->langIterator();
		$arrLangTranslation=array();
		foreach($arrLang as $keyLang=>$value)
		{
			$aLocale = new Locale($value['lang'],$value['country']);var_dump($aLocale);
			$aSentenceBase=$aLocale->sentenceLibrary('base');var_dum					$aSentenceUi = $aLocale->sentenceLibrary('ui');
					$sSentenceUiPkgFileName = $aSentenceUi->packageFilename();
					$sPathUiLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceUiPkgFileName;
					$arrSentenceUi = include $sPathUiLibrarySentence;p($aSentenceBase);exit;
			$aSentenceUi=$aLocale->sentenceLibrary('ui');
			foreach($aSentenceBase->arrSentences() as $keyHash=>$value)
			{
				$arrLangTranslation[$keyLang]['base'][$keyHash]=$value;
			}
			
			foreach($aSentenceUi->arrSentences() as $keyHash=>$value)
			{
				$arrLangTranslation[$keyLang]['ui'][$keyHash]=$value;
			}	
		}

		$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslation);
		$this->viewLangTranslation->variables()->set('arrLang',$arrLang);
		
		if($this->viewLangTranslation->isSubmit())
		{	
			foreach($this->params['TranslationSentence'] as $key=>$value)
			{
				$arrLangType=explode('@',$key);
				$aSetting = Extension::flyweight('localizer')->setting();
				$aSetting->setItem('/translation/'.$arrLangType[0].'.'.$arrLangType[1], $arrLangType[2], $value);
			}
		}
		
		if($this->params['spath'])
		{$nPerPageRowNumber
			$sSpath=$this->params['spath'];
			$arrLangTranslation=$this->langIteratorSelect($sSpath);
			$this->viewLangTranslation->variables()->set('sSpath',$sSpath);
			$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslation);
		}
		*/
		
	}
	
	public function getLangSelectMenu(){
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/',true);
		foreach($aKey->itemIterator() as $key=>$value){
			$arrLangSelectMenu[$value]=$aKey->item($value,array());
		}
		return $arrLangSelectMenu;
	}
	
	public function langIteratorSelect($sSpath){
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKeyBase=$aSetting->key('/translation/'.$sSpath.'.'.'base',true);
		$aKeyUi=$aSetting->key('/translation/'.$sSpath.'.'.'ui',true);
		foreach($aKeyBase->itemIterator() as $key=>$value){
			$arrLang[$sSpath]['base'][$value]=$aKeyBase->item($value,array());
		}
	
		foreach($aKeyUi->itemIterator() as $key=>$value){
			$arrLang[$sSpath]['ui'][$value]=$aKeyUi->item($value,array());
		}
	
		return $arrLang;
	}
	
	public function getSelectSentenceLibrary($sSpath)
	{
		$arrSpath=explode('_',$sSpath);
		$aLocale = new Locale($arrSpath[0],$arrSpath[1]) ;
		$aSentenceBase=$aLocale->sentenceLibrary('base');
		$sSentenceBasePkgFileName=$aSentenceBase->packageFilename();
		$sPathBaseLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceBasePkgFileName;
		$arrSentenceBase = array();
		$arrSentenceUi = array();
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
	
	public function setSelectSentenceLibrary($sSpath,$arrSentenceLibrary)
	{
		foreach($arrSentenceLibrary['base'] as $keyHash=>$value)
		{
			$arrLangTranslation[$sSpath]['base'][$keyHash]=$value;
		}
		
		foreach($arrSentenceLibrary['ui'] as $keyHash=>$value)
		{
			$arrLangTranslation[$sSpath]['ui'][$keyHash]=$value;
		}
		
		return $arrLangTranslation;
		
	}
	
	public function setSelectSentenceLibraryNew($sSpath, $arrLangTranslationChunk,$nNumberRow,$nPerPageRowNumber=20)
	{
		foreach($arrLangTranslationChunk[$nNumberRow/$nPerPageRowNumber] as $key=>$value)
		{
			$arrLangTranslationNew[$sSpath][$value['type']][$value['hash']]=$value['value'];
		}
		return $arrLangTranslationNew;
	}
	
	
	
	public function setSelectSentenceLibraryPage($sSpath,$arrSentenceLibrary)
	{
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
	
	public function getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20)
	{
		return array_chunk($arrLangTranslationSelect,$nPerPageRowNumber);
	}
	
	public function langSwichFront($sSpath)
	{
		
		$sLangCountry = $sSpath;
		$arrLangCountry = explode('_',$sSpath);
		$aLocale = new Locale($arrLangCountry[0],$arrLangCountry[1]);
		$arrSentenceLibrary = $this->getSelectSentenceLibrary($sLangCountry);
		$arrLangTranslationSelect = $this->setSelectSentenceLibraryPage(null,$arrSentenceLibrary);//var_dump($arrLangTranslationSelect);
		$arrLangTranslationChunk = $this->getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20);
		$arrLangTranslationNew = $this->setSelectSentenceLibraryNew($sLangCountry, $arrLangTranslationChunk,0,$nPerPageRowNumber=20);
		
		$aSentenceBase=$aLocale->sentenceLibrary('base');
		$aSentenceUi=$aLocale->sentenceLibrary('ui');
		$nTotal=0;
		$sSentenceBasePkgFileName=$aSentenceBase->packageFilename();
		$sPathBaseLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceBasePkgFileName;
		$arrSentenceBase = array();
		if(file_exists($sPathBaseLibrarySentence))
		{
			$arrSentenceBase = include $sPathBaseLibrarySentence;
		}

		
		$aSentenceUi = $aLocale->sentenceLibrary('ui');
		$sSentenceUiPkgFileName = $aSentenceUi->packageFilename();
		$sPathUiLibrarySentence = Extension::flyweight('localizer')->unarchiveSentenceFolder()->path().'/'.$sSentenceUiPkgFileName;
		$arrSentenceUi = array();
		if(file_exists($sPathUiLibrarySentence))
		{
			$arrSentenceUi = include $sPathUiLibrarySentence;
		}
			
		
		
		foreach($arrSentenceBase as $keyHash=>$value)
		{
			$arrLangTranslation[$aLocale->language().'_'.$aLocale->country()]['base'][$keyHash]=$value;
		}
		foreach($arrSentenceUi as $keyHash=>$value)
		{
			$arrLangTranslation[$aLocale->language().'_'.$aLocale->country()]['ui'][$keyHash]=$value;
		}
		
		$nTotal = count($arrSentenceBase)+count($arrSentenceUi);
		
		$nPerPageRowNumber = 20;
		$nPage = ceil($nTotal/$nPerPageRowNumber);
		
		if($nPage>1)
		{
			for($i=0;$i<$nPage;$i++)
			{
				$arrPage[$i+1]=$i*$nPerPageRowNumber;
			}
			$this->viewLangTranslation->variables()->set('arrPage',$arrPage);
		}else if($nPage>0){
			$arrPage[1]=0;
			$this->viewLangTranslation->variables()->set('arrPage',$arrPage);
		}
		
		$this->viewLangTranslation->variables()->set('sLangCountry',$sLangCountry);
		$this->viewLangTranslation->variables()->set('sSpath',$sLangCountry);
		$arrLangSelectMenu = $this->getLangSelectMenu();
		$this->viewLangTranslation->variables()->set('arrLangSelectMenu',$arrLangSelectMenu);
		$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslationNew);
	}
	
	
	public function displayTranslationLibrary()
	{
		$sSpath = $this->params['hiddenLangCountry'];
		$nNumberRow=$this->params['hiddenSelectedPage'];
		$selectedPage = $nNumberRow;
		$arrSentenceLibrary = $this->getSelectSentenceLibrary($sSpath);
		$arrLangTranslationSelect = $this->setSelectSentenceLibraryPage($sSpath, $arrSentenceLibrary);
		
		$arrLangTranslationChunk = $this->getLangChunk($arrLangTranslationSelect,$nPerPageRowNumber=20);
		$arrLangTranslationNew = $this->setSelectSentenceLibraryNew($sSpath, $arrLangTranslationChunk,$nNumberRow,$nPerPageRowNumber=20);
		$this->viewLangTranslation->variables()->set('sSpath',$sSpath);
		$this->viewLangTranslation->variables()->set('arrLangTranslation',$arrLangTranslationNew);
		$this->viewLangTranslation->variables()->set('selectedPage',$selectedPage);
	}
	
}

?>