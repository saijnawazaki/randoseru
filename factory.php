<?php
require 'config.php';
require 'function.php';

//get RDSR folder files
$content_rdsr = getFileFromFolder(['path'=>PATH_RDSR_FOLDER,'accept-ext'=>'rdsr']);

//print('<pre>'.print_r($content_rdsr).'</pre>');
$content_rdsr_finalmix = array();
$content_mixin = array();

foreach($content_rdsr as $filename => $content)
{
	$content_vars = array();

	$res = getModules(['content'=>$content,'path'=>PATH_RDSR_FOLDER]);
	$content = $res['content'];
	$content_vars_partial = $res['variable'];

	//print_r($content_vars_partial);
	//echo '<hr>';
	$content = getVariableFromFileSet(['content'=>$content,'variable'=>$content_vars_partial,'module'=>true]);

	$res = getMixin(['content'=>$content]);
	$content = $res['content'];

	if(isset($res['mixin']))
	{
		foreach($res['mixin'] as $mixin_name => $mixin_content)
		{
			$content_mixin[$mixin_name] = $mixin_content;	
		}
	}

	$content = setMixin(['content'=>$content,'mixin'=>$content_mixin]);

	//print_r($content_mixin);


	

	//echo $content;
	//Get Variable
	$res = getVariableFromFile(['content'=>$content]);
	$content = $res['content'];
	$content_vars = $res['variable'];

	//foreach
	$content = getForeach(['content'=>$content,'variable'=>$content_vars]);
	
	//Set Variable
	$content = getVariableFromFileSet(['content'=>$content,'variable'=>$content_vars]);

	//echo $content;

	$content_finalmix = getParseCSS(['content'=>$content]);
	

	
	//echo "<pre>".$filename."-->".$content_finalmix."</pre>";
	$content_rdsr_finalmix[$filename] = $content_finalmix;
	//print('<pre>'.print_r($content_list,true).'</pre>');
		
}

//print('<pre>'.print_r($content_rdsr_finalmix,true).'</pre>');
	
foreach($content_rdsr_finalmix as $filename => $content)
{
	$exp_filename = explode('.', $filename);
	$filename_ext = end($exp_filename);
	$filename_name = $exp_filename[0];	

	if(file_exists(PATH_CSS_FOLDER.'/'.$filename_name.'.css'))
	{
		unlink(PATH_CSS_FOLDER.'/'.$filename_name.'.css') or print($filename." error delete\n");	
	}
	
	file_put_contents(PATH_CSS_FOLDER.'/'.$filename_name.'.css', $content) or print($filename." error\n");
}

print('Generated: '.date('r'));