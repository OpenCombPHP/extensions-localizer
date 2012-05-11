<?php 
namespace org\opencomb\localizer ;

use org\jecat\framework\bean\BeanFactory;
use org\opencomb\platform\mvc\view\widget\Menu;
use org\jecat\framework\locale\LanguagePackageFolders;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\locale\Locale;
use org\opencomb\platform\ext\Extension ;

class Localizer extends Extension 
{
	public function load()
	{
		BeanFactory::singleton()->registerBeanClass("org\\opencomb\\localizer\\LangSelect",'langselect') ;
		Menu::registerBuildHandle(
				'org\\opencomb\\coresystem\\mvc\\controller\\ControlPanelFrame'
				, 'frameView'
				, 'mainMenu'
				, array(__CLASS__,'buildControlPanelMenu')
		) ;
		// 注册语言包目录
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
		// 合并配置数组，增加菜单
		$arrConfig['item:system']['item:platform-manage']['item:localizer'] = array(
				'title'=> '本地化' ,
				'link' => '?c=org.opencomb.localizer.LangSetting' ,
				'query' => 'c=org.opencomb.localizer.LangSetting' ,
				'menu' => 1,
				'item:langsetting' => array(
					'title' => '语言设定' ,
					'link' => '?c=org.opencomb.localizer.LangSetting' ,	
					'query' => 'c=org.opencomb.localizer.LangSetting' ,
				),
				'item:translation' => array(
					'title' => '语言翻译' ,
					'link' => '?c=org.opencomb.localizer.LangTranslation' ,
					'query' => 'c=org.opencomb.localizer.LangTranslation' ,
				)
				
		);
	}
}