<?php 
namespace org\opencomb\localizer ;

use org\jecat\framework\locale\LanguagePackageFolders;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\locale\Locale;
use org\opencomb\platform\ext\Extension ;

class Localizer extends Extension 
{
	public function load()
	{
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
}