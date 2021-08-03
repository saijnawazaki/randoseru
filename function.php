<?php
function getReferenceReplace($settings)
{
	$content = $settings['content'];
	$css_list = $settings['css_list'];

	
	preg_match_all('/(\@reference-replace+[\s]+[\'\"]+[a-zA-Z0-9\-\_]+[\'\"]+\;+$)/m', $content, $matches);

	//print('<pre>'.print_r($matches,true).'</pre>');

	foreach($matches[0] as $content_matches)
	{
		$exp_con = explode('@reference-replace',$content_matches);
		$file_inc = $exp_con[1];
		$file_inc = str_replace('"','',$file_inc);	
		$file_inc = str_replace("'",'',$file_inc);	
		$file_inc = str_replace(";",'',$file_inc);
		$filename = trim($file_inc);
		$file_inc = trim($file_inc).'.rdsr';

		$content = str_replace($content_matches,'',$content);	
		
		$ref_con = cssToArray(['content'=>$content]);
		$master_con = cssToArray(['content'=>$css_list[$file_inc]]);

		foreach($master_con as $level => $value)
		{
			foreach($master_con[$level] as $classname => $value)
			{
				if(isset($ref_con[$level][$classname]))
				{
					//echo $ref_con[$level][$classname];
					$master_con[$level][$classname] = $ref_con[$level][$classname];	
				}
			}			
		}
		//print_r($master_con);

		//echo $master_con;
		
	}



	//return array('variable'=>$content_vars,'content'=>$content);
	if(isset($master_con))
	{
		return ['css_list'=>array($file_inc=>$master_con),'content'=>$content];	
	}
	else
	{
		return ['content'=>$content];
	}
	
}

function cssToArray($settings)
{
	$content = $settings['content'];
	//get level 1
	preg_match_all('/[^\s}{]+\s*{(?:[^}{]+|{[^}{]*})*}/m', $content, $matches);
	foreach($matches[0] as $var)
	{
		$exp_var = explode('{',$var,2);
		$classname = trim($exp_var[0]);

		$exp = explode('}',$exp_var[1]);
	    $last = array_pop($exp);
	    $parts = array(implode('}', $exp), $last);
		

		$classcontent = $parts[0];

		$content_list[1][$classname] = $classcontent;
	}

	//level 2
	foreach($content_list[1] as $classname_parent => $var_parent)
	{
		preg_match_all('/[^\s}{]+\s*{(?:[^}{]+|{[^}{]*})*}/m', $var_parent, $matches);
		foreach($matches[0] as $var)
		{
			$exp_var = explode('{',$var,2);
			$classname = trim($exp_var[0]);

			$exp = explode('}',$exp_var[1]);
		    $last = array_pop($exp);
		    $parts = array(implode('}', $exp), $last);
			

			$classcontent = $parts[0];

			$content_list[2][$classname_parent][$classname] = $classcontent;
		}	
	}

	return $content_list;
}

function arrayToCSS($settings)
{
	$content_list = $settings['content_array'];
	$content_finalmix = '';
	//parse
	foreach($content_list[1] as $classname_parent => $var_parent)
	{
		if(isset($content_list[2][$classname_parent]))
		{
			if(count($content_list[2][$classname_parent]) > 0)
			{
				//nesting
				foreach($content_list[2][$classname_parent] as $classname_child => $var_child)
				{
					$content_finalmix .= $classname_parent.' '.$classname_child;
					$content_finalmix .= " {\n";
					$content_finalmix .= $var_child."\n";
					$content_finalmix .= " }\n";
				}
			}
			else
			{
				$content_finalmix .= $classname_parent;
				$content_finalmix .= " {\n";
				$content_finalmix .= $var_parent."\n";
				$content_finalmix .= " }\n";
			}	
		}
		else
		{
			$content_finalmix .= $classname_parent;
			$content_finalmix .= " {\n";
			$content_finalmix .= $var_parent."\n";
			$content_finalmix .= " }\n";
		}	
			
	}

	return $content_finalmix;
}

function getForeach($settings)
{
	$content = $settings['content'];
	$variable = $settings['variable'];

	//print_r($variable);
	
	preg_match_all('/@foreach+[\(|\s]+[a-zA-Z0-9\$\=\>\s]+[\)|\s]+[\{|\s]+[a-zA-Z0-9\s\:\$\,\.\;\#\-\(\)\_\-\}\{]+[\}|\s]/m', $content, $matches);

	//print('<pre>'.print_r($matches,true).'</pre>');
	
	foreach($matches[0] as $content_matches)
	{
		$exp_cn = explode('(',$content_matches);
		$exp_cn = explode(')',$exp_cn[1]);
		$exp_cn	= explode('as', $exp_cn[0]);
		$array = trim($exp_cn[0]);
		$exp_cn = explode('=>',$exp_cn[1]);
		$array_index = trim($exp_cn[0]);
		$array_value = trim($exp_cn[1]);

		$exp_var = explode('{',$content_matches,2);
		$exp = explode('}',$exp_var[1]);
	    $last = array_pop($exp);
	    $parts = array(implode('}', $exp), $last);
		

		$classcontent = $parts[0];
		
		$array_act = array(); 
		eval("\$array_act = ".$variable[$array].";");
		//print_r($array_act);
		$classcontent_mix = '';
		foreach($array_act as $index => $value)
		{
			//echo $index.'->'.$value.'<br>';
			$classcontent_mix .= getVariableFromFileSet(['content'=>$classcontent,'variable'=>[$array_index=>$index,$array_value=>$value]]);

			
		}
		//echo "$content_matches,$classcontent_mix <br>";

		$content = str_replace($content_matches,$classcontent_mix,$content);
		//echo "<hr>$content</hr>";
	}

	//return array('mixin'=>$mixin_list,'content'=>$content);
	return $content;
}

function setMixin($settings)
{
	$content = $settings['content'];
	$mixin = $settings['mixin'];
	$mixin_origin = $mixin;
	
	preg_match_all('/@include+\s+[a-zA-Z0-9]+[\(|\s]+[a-zA-Z0-9\$\:\s]+[\)|\s]+\;+$/m', $content, $matches);

	//print('<pre>'.print_r($matches,true).'</pre>');
	//$mixin_list = array();

	foreach($matches[0] as $content_matches)
	{
		$mixin = $mixin_origin;
		$exp_cn = explode('(',$content_matches);
		$exp_cncn = explode(' ', $exp_cn[0]);
		$mixin_name = trim($exp_cncn[1]);
		$exp_cn = explode(')',$exp_cn[1]);
		$vars = getVariableParamFromFile(['content'=>$exp_cn[0]]);

		//replace
		if(isset($vars))
		{
			foreach($vars['variable'] as $index => $value)
			{
				$mixin[$mixin_name]['variable'][$index] = $value;	
			}

			
		}	

		$mixin_finalmix = $mixin[$mixin_name]['content'];
		$mixin_finalmix = getVariableFromFileSet(['content'=>$mixin_finalmix,'variable'=>$mixin[$mixin_name]['variable']]); 

		$content = str_replace($content_matches,$mixin_finalmix,$content);	
	}

	preg_match_all('/@include+\s+[a-zA-Z0-9]+\;+$/m', $content, $matches);
	//print('<pre>'.print_r($matches,true).'</pre>');
	foreach($matches[0] as $content_matches)
	{
		$mixin = $mixin_origin;
		
		$exp_cncn = explode(' ', $content_matches);
		$mixin_name = trim($exp_cncn[1]);
		$mixin_name = str_replace(';','',$mixin_name);
		
		$mixin_finalmix = $mixin[$mixin_name]['content'];
		$mixin_finalmix = getVariableFromFileSet(['content'=>$mixin_finalmix,'variable'=>$mixin[$mixin_name]['variable']]);
		
		$content = str_replace($content_matches,$mixin_finalmix,$content);		
	}

	return $content;
	//return $content;
}

function getMixin($settings)
{
	$content = $settings['content'];
	
	preg_match_all('/@mixin+\s+[a-zA-Z0-9]+[\(|\s]+[a-zA-Z0-9\$\:\s]+[\)|\s]+[\{|\s]+[a-zA-Z0-9\s\:\$\,\.\;\#\-\(\)\_\-]+[\}\s]/m', $content, $matches);

	//print('<pre>'.print_r($matches,true).'</pre>');
	$mixin_list = array();

	foreach($matches[0] as $content_matches)
	{
		$exp_var = explode('{',$content_matches,2);
		$classname = trim($exp_var[0]);

		$exp_cn = explode('(',$classname);
		$exp_cncn = explode(' ', $exp_cn[0]);
		$mixin_name = trim($exp_cncn[1]);
		$exp_cn = explode(')',$exp_cn[1]);

		//echo '??-'.$exp_cn[0];
		$vars = getVariableParamFromFile(['content'=>$exp_cn[0]]);

		$exp = explode('}',$exp_var[1]);
	    $last = array_pop($exp);
	    $parts = array(implode('}', $exp), $last);
		

		$classcontent = $parts[0];

		//$classcontent = getVariableFromFileSet(['content'=>$classcontent,'variable'=>$vars['variable']]);

		//echo $mixin_name."<hr>".$classcontent.'<hr>';
		//print_r($vars);	
		$mixin_list[$mixin_name]['content'] = $classcontent;
		$mixin_list[$mixin_name]['variable'] = $vars['variable'];
		$content = str_replace($content_matches,'',$content);
	}

	return array('mixin'=>$mixin_list,'content'=>$content);
	//return $content;
}

function getModules($settings)
{
	$content = $settings['content'];
	$path = $settings['path'];
	$content_vars = array();

	preg_match_all('/(\@use+[\s]+[\'\"]+[a-zA-Z0-9\-\_]+[\'\"]+\;+$)/m', $content, $matches);

	//print('<pre>'.print_r($matches,true).'</pre>');

	foreach($matches[0] as $content_matches)
	{
		$exp_con = explode('@use',$content_matches);
		$file_inc = $exp_con[1];
		$file_inc = str_replace('"','',$file_inc);	
		$file_inc = str_replace("'",'',$file_inc);	
		$file_inc = str_replace(";",'',$file_inc);
		$filename = trim($file_inc);
		$file_inc = '_'.trim($file_inc).'.rdsr';

		$content = str_replace($content_matches,file_get_contents($path.'/'.$file_inc),$content);

		$content_vars_res = getVariableFromFile(['content'=>$content]);
		foreach($content_vars_res['variable'] as $index => $val)
		{
			$content_vars[$filename][$index] = $val;	
		}
		
	}

	//return array('variable'=>$content_vars,'content'=>$content);
	return ['variable'=>$content_vars,'content'=>$content];
}

function getFileFromFolder($settings)
{
	$path = $settings['path'];
	$accept_ext = $settings['accept-ext'];
	
	$files = array_diff(scandir($path), array('.', '..'));
	$content = array();

	foreach($files as $index => $filename) {
	    $exp_filename = explode('.', $filename);
	    $filename_ext = end($exp_filename);

	    if(strtolower($filename_ext) == strtolower($accept_ext) && substr($filename,0,1) != '_')
	    {
	    	$content[$filename] = file_get_contents($path.'/'.$filename);
	    }
	}

	return $content;
}

function getVariableFromFile($settings)
{
	$content = $settings['content'];
	$content_vars = array();

	preg_match_all('/(\$[a-zA-Z0-9\-\_\:\,\#\s\[\]\=\>]+\;+$)/m', $content, $matches);
	//print('<pre>'.print_r($matches,true).'</pre>');
	foreach($matches[0] as $content_matches)
	{
		$exp_cm = explode(':',$content_matches);

		if(count($exp_cm) == 2)
		{
			//variable set
			$content_vars[trim($exp_cm[0])] = str_replace(';','',trim($exp_cm[1]));

			$content = str_replace($content_matches,'',$content);
		}
	}

	return ['variable'=>$content_vars,'content'=>$content];
}

function getVariableParamFromFile($settings)
{
	$content = $settings['content'];
	$content_vars = array();

	preg_match_all('/(\$[a-zA-Z0-9\-\_\:\,\#\s]+$)/m', $content, $matches);
	//print('<pre>'.print_r($matches,true).'</pre>');
	foreach($matches[0] as $content_matches)
	{
		$exp_cm = explode(':',$content_matches);

		if(count($exp_cm) == 2)
		{
			//variable set
			$content_vars[trim($exp_cm[0])] = str_replace(';','',trim($exp_cm[1]));

			$content = str_replace($content_matches,'',$content);
		}
	}

	return ['variable'=>$content_vars,'content'=>$content];
}

function getVariableFromFileSet($settings)
{
	$content = $settings['content'];
	$content_vars = $settings['variable'];
	
	if(isset($settings['module']))
	{
		$module = $settings['module'];
	}
	else
	{
		$module = false;
	}

	if($module)
	{
		preg_match_all('/([a-zA-Z0-9\-\_]+\.+\$+[a-zA-Z0-9\-\_]+)/', $content, $matches);

		//print('<pre>'.print_r($matches,true).'</pre>');
		foreach($matches[0] as $content_matches)
		{
			$exp_name = explode('.',$content_matches);
			//echo $content_matches.'<hr>';
			//echo $content_vars[$exp_name[0]][$exp_name[1]].'<hr>';
			//print_r($exp_name);
			if(isset($content_vars[$exp_name[0]][$exp_name[1]]))
			{
				$content = str_replace($content_matches,$content_vars[$exp_name[0]][$exp_name[1]],$content);	
			}
				
		}	
	}
	else
	{
		preg_match_all('/(\$+[a-zA-Z0-9\-\_]+)/', $content, $matches);

		//print('<pre>'.print_r($matches,true).'</pre>');
		foreach($matches[0] as $content_matches)
		{
			if(isset($content_vars[$content_matches]))
			{
				$content = str_replace($content_matches,$content_vars[$content_matches],$content);	
			}
				
		}	
	}

	return $content;
}

function getParseCSS($settings)
{
	$content = $settings['content'];
	$content_list = array();
	$content_finalmix = '';

	//nesting
	//get level 1
	preg_match_all('/[^\s}{]+\s*{(?:[^}{]+|{[^}{]*})*}/m', $content, $matches);
	foreach($matches[0] as $var)
	{
		$exp_var = explode('{',$var,2);
		$classname = trim($exp_var[0]);

		$exp = explode('}',$exp_var[1]);
	    $last = array_pop($exp);
	    $parts = array(implode('}', $exp), $last);
		

		$classcontent = $parts[0];

		$content_list[1][$classname] = $classcontent;
	}

	//level 2
	foreach($content_list[1] as $classname_parent => $var_parent)
	{
		preg_match_all('/[^\s}{]+\s*{(?:[^}{]+|{[^}{]*})*}/m', $var_parent, $matches);
		foreach($matches[0] as $var)
		{
			$exp_var = explode('{',$var,2);
			$classname = trim($exp_var[0]);

			$exp = explode('}',$exp_var[1]);
		    $last = array_pop($exp);
		    $parts = array(implode('}', $exp), $last);
			

			$classcontent = $parts[0];

			$content_list[2][$classname_parent][$classname] = $classcontent;
		}	
	}

	//parse
	foreach($content_list[1] as $classname_parent => $var_parent)
	{
		if(isset($content_list[2][$classname_parent]))
		{
			if(count($content_list[2][$classname_parent]) > 0)
			{
				//nesting
				foreach($content_list[2][$classname_parent] as $classname_child => $var_child)
				{
					$content_finalmix .= $classname_parent.' '.$classname_child;
					$content_finalmix .= " {\n";
					$content_finalmix .= $var_child."\n";
					$content_finalmix .= " }\n";
				}
			}
			else
			{
				$content_finalmix .= $classname_parent;
				$content_finalmix .= " {\n";
				$content_finalmix .= $var_parent."\n";
				$content_finalmix .= " }\n";
			}	
		}
		else
		{
			$content_finalmix .= $classname_parent;
			$content_finalmix .= " {\n";
			$content_finalmix .= $var_parent."\n";
			$content_finalmix .= " }\n";
		}	
			
	}

	return $content_finalmix;
}