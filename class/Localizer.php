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
		$arrConfig['item']['system']['item']['platform-manage']['item']['localizer'] = array(
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
					'query' => array(
								'c=org.opencomb.localizer.LangTranslation'
							   ,'c=org.opencomb.localizer.LangSearchKey'
					)
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

	}
	
	static public function filterForFrontFrameMergeIcon(ObjectContainer $aObjectContainer,Node $aTargetObject)
	{	
		// 将 这个 node 标签改为 div
		$aTargetObject->headTag()->setName('div') ;		// 头部标签
		$aTargetObject->tailTag()->setName('div') ;		// 尾部标签
	}
}