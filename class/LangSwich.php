<?php
namespace org\opencomb\localizer;

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

class LangSwich extends ControlPanel
{
	public function createBeanConfig()
	{
		$this->setCatchOutput(false) ;
		return array(
			'title'=> '文章内容',
			'view:langSwich'=>array(
				'template'=>'langSwich.html',
				'class'=>'form',
				'widgets' => array(
				),
			),
		);
	}
	
	public function process()
	{
		if($this->params['lang'])
		{
			$arrLang=explode('_',$this->params['lang']);
			Locale::switchSessionLocale($arrLang[0],$arrLang[1],true);
			$skey="切换语言";
			$this->viewLangSwich->createMessage(Message::success,"%s 成功",$skey) ;
		};
		
	}
}