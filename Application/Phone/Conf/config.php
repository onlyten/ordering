<?php

return array(
	//'配置项'=>'配置值'


	/* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        //'__STYLE__' 		=> '/ordering/Public/',
        //'__CSS__'    => '/ordering/Public/Phone/css',
        //'__STYLE__' 		=> '/ordering/Public/',
        '__STYLE__' 		=>  __ROOT__ . '/Public/',
        '__CSS__'    =>  __ROOT__ . '/Public/Phone/css',
        '__JS__'    => __ROOT__ . '/Public/Phone/js',
		'__INDEX__'    => '/index.php',
		//'__PICURL__'    => '/ordering/Public/Phone/images/index ',
		'__PICURL__'    => __ROOT__ .'/Public/Phone/images/index',
		'__WXPHONE__' => '400-4000-000',//手机号
		'__URL__' => 'http://www.ebfree.com',
		'__IMG__' => 'http://www.ebfree.com/ordering/Public/images',
		'__PHONE__' =>'123456789,'
		
    ),


);
