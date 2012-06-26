<?php 
namespace org\opencomb\localizer ;

use org\jecat\framework\bean\BeanFactory;
use org\opencomb\platform\mvc\view\widget\Menu;
use org\jecat\framework\locale\LanguagePackageFolders;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\locale\Locale;
use org\opencomb\platform\ext\Extension ;
use org\jecat\framework\ui\xhtml\weave\WeaveManager;
use org\jecat\framework\ui\xhtml\weave\Patch;
use org\jecat\framework\ui\ObjectContainer ;
use org\jecat\framework\ui\xhtml\Node ;
use org\jecat\framework\db\sql\compiler\NameMapper; 
use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\model\db\orm\Prototype;
use org\jecat\framework\mvc\model\db\Model;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\util\EventManager;
use org\opencomb\coresystem\mvc\controller\ControlPanel ;

class Localizer extends Extension 
{
	public function load()
	{
		BeanFactory::singleton()->registerBeanClass("org\\opencomb\\localizer\\LangSelect",'langselect') ;
		//var_dump(BeanFactory::singleton());exit;
		ControlPanel::registerMenuHandler( array(__CLASS__,'buildControlPanelMenu') ) ;
		LanguagePackageFolders::singleton()->registerFolder($this->unarchiveSentenceFolder()->path()) ;
	}
	
	/**
	 * 载入扩展
	 */
	public function __destruct()
	{		
		// 保存系统中未归档的语句
		$aFolder = $this->unarchiveSentenceFolder() ;
		foreach( Locale::singleton()->loadedSentenceLibraries() as $aSentenceLib )
		{
			if( $arrUnarchiveSentences=&$aSentenceLib->unarchiveSentences() )
			{
				$sPakcagePath = $aFolder->path() . '/' . $aSentenceLib->packageFilename() ;
				
				$arrSentences = is_file($sPakcagePath)? include $sPakcagePath: array() ;
				is_array($arrSentences) ? : $arrSentences = array(); 
				$arrSentences = array_merge($arrSentences,$arrUnarchiveSentences) ;
				
				// 存入未归档语句
				file_put_contents($sPakcagePath, '<?php return '.var_export($arrSentences,1).' ;') ;
			}
		}
	}
	
	public function unarchiveSentenceFolder()
	{
		return $this->dataFolder()->findFolder('lang',Folder::FIND_AUTO_CREATE) ;
	}
	
	static public function buildControlPanelMenu(array & $arrConfig)
	{
		$aLocale = Locale::singleton();
		$sLangCountry = $aLocale->language().'_'.$aLocale->country();
		// 合并配置数组，增加菜单
		$arrConfig['item:system']['item:platform-manage']['item:localizer'] = array(
				'title'=> '本地化' ,
				'link' => '?c=org.opencomb.localizer.LangSetting' ,
				'query' => 'c=org.opencomb.localizer.LangSettingxx' ,
				'menu' => 1,
				'item:langsetting' => array(
					'title' => '语言设定' ,
					'link' => '?c=org.opencomb.localizer.LangSetting' ,	
					'query' => 'c=org.opencomb.localizer.LangSetting' ,
				),
				'item:translation' => array(
					'title' => '语言翻译' ,
					'link' => '?c=org.opencomb.localizer.LangTranslation'.'&sSwichFrontLangPath='.$sLangCountry ,
					'query' => 'c=org.opencomb.localizer.LangTranslation' ,
				)
				
		);
	}
	
	public function initRegisterUITemplateWeave(WeaveManager $aWeaveManager)
	{
		{
			$aWeaveManager->registerFilter( 'coresystem:FrontFrame.html', "/div@0/p@0", array(__CLASS__,'filterForFrontFrameMergeIcon') ) ;
			$aWeaveManager->registerFilter( 'coresystem:ControlPanelFrame.html', "/div@0/div@0/div@0", array(__CLASS__,'filterForFrontFrameMergeIcon') ) ;
			
		}
	
		// 将 mvc-merger 扩展提供的模板文件 merger/MergeIconMenu.html 做为补丁，应用到  coresystem 扩展的模板 FrontFrame.html 中的第一个<div>下的第一个<p> 内部的末尾
		$aWeaveManager->registerCode( 'coresystem:FrontFrame.html', "/div@0/p@0", '<widget type=\'langselect\'/>', Patch::insertAfter ) ;
		$aWeaveManager->registerCode( 'coresystem:ControlPanelFrame.html', "/div@0/div@0/div@0", '<widget type=\'langselect\'/>', Patch::insertAfter ) ;
		
		// -------------------------------------------------
		// 根据 setting 中保存的信息，应用模板补丁
		foreach($this->setting()->key("/merge/uiweave",true)->keyIterator() as $aNsKey)
		{
			$sNamespace = $aNsKey->name() ;
			foreach($aNsKey->keyIterator() as $aTemplateKey)
			{
				$sTemplate = $aTemplateKey->name() ;
				$arrAllPatchs = $aTemplateKey->item('arrPatchs',array()) ;
	
				foreach($arrAllPatchs as $sXPath=>$arrPatchList)
				{
					foreach($arrPatchList as $arrPatch)
					{
						$aWeaveManager->registerCode( $sNamespace.':'.$sTemplate, $sXPath, $arrPatch[1], $arrPatch[0] ) ;
					}
				}
			}
		}

	}
	
	static public function filterForFrontFrameMergeIcon(ObjectContainer $aObjectContainer,Node $aTargetObject)
	{	
		// 将 这个 node 标签改为 div
		$aTargetObject->headTag()->setName('div') ;		// 头部标签
		$aTargetObject->tailTag()->setName('div') ;		// 尾部标签
	}
	
	/*
	public function active()
	{
		$aLocale = Locale::singleton();
		$sPrefix = DB::singleton()->tableNamePrefix();
		try{
			
			$sSQL = 'select * from'.' '.$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName());
			$aRecords = DB::singleton()->query($sSQL);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_article',$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}catch(Exception $e){
			$sSQL = "show create table opencms_article";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_article', $sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_article',$sPrefix.'opencms_article'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}
	
		try{
			$sSQL = 'select * from'.' '.$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName());
			$aRecords = DB::singleton()->query($sSQL);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_attachment',$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}catch(Exception $e){
			$sSQL = "show create table opencms_attachment";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_attachment', $sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_attachment',$sPrefix.'opencms_attachment'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}
		try{
		
			$sSQL = 'select * from'.' '.$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName());
		
			$aRecords = DB::singleton()->query($sSQL);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_category',$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}catch(Exception $e){
			$sSQL = "show create table opencms_category";
			$aRecords = DB::singleton()->query($sSQL);
			$arrCreateCommand = $aRecords->fetchAll();
			$sSQLCreate = str_replace($sPrefix.'opencms_category', $sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()), $arrCreateCommand[0]['Create Table']);
			DB::singleton()->execute($sSQLCreate);
			NameMapper::singleton()->mapTableName($sPrefix.'opencms_category',$sPrefix.'opencms_category'.'_'.str_replace('-', '_', $aLocale->localeName()));
		}
		
	}
	
	public function initRegisterEvent(EventManager $aEventMgr)
	{
		$aEventMgr->registerEventHandle(
				'org\\opencomb\\localizer\\LangSwich'
				, LangSwich::beforeRespond
				, array(__CLASS__,'onBeforeRespond')
		);
	}
	
	static public function onBeforeRespond($sLangCountryNew,$sLangCountryOld,$sPageUrl)
	{
		
		$arrLangCountry = explode('_',$sLangCountryNew);
		$sDpath = $sLangCountryNew;
		$arrLang =Localizer::langIterator();
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
	*/
}