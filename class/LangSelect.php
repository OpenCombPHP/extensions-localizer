<?php
namespace org\opencomb\localizer;

use org\opencomb\coresystem\user\UserModel;
use org\jecat\framework\mvc\model\db\Model;
use org\jecat\framework\bean\BeanFactory;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\auth\IdManager;
use org\jecat\framework\io\IOutputStream;
use org\jecat\framework\util\IHashTable;
use org\jecat\framework\ui\UI;
use org\jecat\framework\mvc\model\IModel;
use org\jecat\framework\mvc\view\widget\Widget;

class LangSelect extends Widget {
	public function __construct($aUserModel=null, $sId = '', $sTitle = null,  IView $aView = null) {
		parent::__construct ( $sId, 'localizer:LangSelect.html',$sTitle, $aView );
	}
	
	public function display(UI $aUI,IHashTable $aVariables=null,IOutputStream $aDevice=null)
	{
		parent::display($aUI, $aVariables,$aDevice);
	}
	
	public function langIterator(){
		$arrLang = array();
		$aSetting = Extension::flyweight('localizer')->setting();
		$aKey=$aSetting->key('/',true);
		foreach($aKey->itemIterator() as $key=>$value){
			$arrTemp=$aKey->item($value,array());
			if($arrTemp['used']=='1')
			{
				$arrLang[$value]=$aKey->item($value,array());
			}
			
		}
		return $arrLang;
	}
	
}