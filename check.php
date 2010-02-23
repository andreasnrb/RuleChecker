<div class="wrap">
<div id="icon-options-general" class="icon32"></div>
<h2>Rule checker</h2>
<form action="" method="post">
<label>List functions:</label> <input id="functions" name="functions" value="yes" type="checkbox"><br>
<label>List includes/requirements:</label> <input id="increq" name="increq" value="yes" type="checkbox"><br>
<label>List globals:</label> <input id="globals" name="globals" value="yes" type="checkbox"><br>
<label>List defined constants:</label> <input id="constants" name="constants" value="yes" type="checkbox"><br>

<select id="plugins" name="plugins">
<?php //<div id="message" class="updated fade"><p><strong>Store options updated successfully.</strong></p></div>
$plugins=get_plugins();
foreach($plugins as $path => $rest){?>
<option value="<?php echo $path?>"><?php echo $rest['Name']?></option>
<?php }?>
</select>
<input id="check" name="check" value="Check" class="button-primary" type="submit">
</form>
<?php
if(isset($_POST['check'])):
	$wpGlobals=loadGlobals();
	$globals=array();
	$defineExpr='/define\( ?\W?(\w+)\W? ?, ?\W(.+)\W ?\);/';
	$globalExpr='/global +(.+);/';
	$actionExpr='/add_action\( ?\W(\w+)\W ?, ?\W(\w+)\W[, \d]*\);/';
	$filterExpr='/add_filter\( ?\W([&-_\w$]+)\W ?, ?array\(\W?([&$-_\w]+\W?, ?\W[-_\w]+)\W ?\) ?\);/';
	$actionExprVar='/add_action\( ?\W([-_\w$]+)\W ?, ?array\(\W([-_\w]+\W, ?\W[-_\w]+)\W ?\) ?\);/';
	$pageExpr='/(add_\w+_page)\( ?\W([\w-\.]+)\W,.*\);/';
	$inreqExpr='/(require_once|require|include_once|include)\((.*)\);/';
	$formExpr='/<form.+action=\W+<\?php +echo(.+)\?>.+>/';
	$doExpr='/do_action\( +\W(\w+).+\)/';
	$funcExpr='/function ([\w_]+)\((.*)\)/';
	$hrefEchoExpr='/href=\W<\?php +echo([^>]+); \?>\W/';
	$aEchoExpr='/echo \W<a.+href=\W*(.+) *\. *\W\W .+>\W? ?\.?(.+)[\S\W\.]<\/a>/';
	$echoDots='/echo .*\(.*\) *\. *[\'\"]/';
	$comExpr='#//|\/\*|\*/| +\*#';
	$files=array();
	loadPHP(WP_PLUGIN_DIR.'/'.dirname($_POST['plugins']).'/',$files);
	foreach($files as $file => $path){
		echo "<fieldset style='margin-top:20px;border:solid 1px #000;padding:10px 10px 10px 10px;'><legend style='font-size:1.2em'>$file</legend>";
		$file_array = file($path);
		$previousLine="";
		$funcCount=0;
		$func=false;
		$first=true;
		foreach ($file_array as $line_number =>$line)
		{
			if(empty($line) || mb_strlen($line)<5 || preg_match($comExpr,$line)>0)
				continue;

			if(strlen($line_number)<2)
				$line_number="00$line_number";
			else if(strlen($line_number)<3)
				$line_number="0$line_number";
			$line_number='<span style="color:#78BDED">'.$line_number.'</span>';

			if(isset($_POST['functions'])){
				preg_match($funcExpr,$line,$matches);
				if($matches){
					$func=true;
					if($funcCount>0 && !$first)
						echo ' Function has ',$funcCount,' LoC <br /><br />';
					$first=false;
					$funcCount=-1;
					echo 'Line ',$line_number.' <strong>function ',$matches[1],'</strong>(',trim($matches[2]),')<br />';/**/
				}
			}
			
			if(isset($_POST['constants'])){
				preg_match($defineExpr,$line,$matches);
				if($matches){
					echo 'Line ',$line_number,' defined ',$matches[1],': ',$matches[2];
					$s=explode(',',$line);
					if(strpos($s[0],"'")===false && strpos($s[0],'"')===false)
						echo ' <em style="color:red">No \' or " around constant name. You need to surround your constant name with \' or ".</em>';
					echo ' <br />';					
				}
			}
			
			preg_match($globalExpr,$line,$matches);
			if($matches){
				if(isset($_POST['globals'])){
					echo 'Line ',$line_number,' global: ',$matches[1];
					echo '<br />';	
				}
				$glbs=explode(',',$matches[1]);
				foreach($glbs as $glb)
					if(array_key_exists(trim($glb),$wpGlobals))
						$wpGlobals[trim($glb)]++;
					else if(!in_array(strtolower(trim($glb)),$globals))
						$globals[]=strtolower(trim($glb));

			}
			
			preg_match($actionExpr,$line,$matches);
			if($matches)
				echo 'Line ',$line_number,' action: ',$matches[1],': ',$matches[2],' <br />';

			preg_match($actionExprVar,$line,$matches);
			if($matches)
				echo 'Line ',$line_number,' action: ',$matches[1],': ',$matches[2],' <br />';				
				
			preg_match($pageExpr,$line,$matches);
			if($matches){
				echo 'Line ',$line_number,' page  :',$matches[1],': ',$matches[2],' <br />';
				if(isset($_POST['increq'])){
					preg_match($inreqExpr,$line,$matches);
					if($matches)
						echo 'Line ',$line_number,' ',$matches[1],': ',$matches[2],' <br />';
				}
			}
			preg_match($formExpr,$line,$matches);
			if($matches){
				echo 'Line ',$line_number,' <strong>Form action:</strong> ',trim($matches[1]);
				if(strpos($matches[1],'$')===false && strpos($matches[1],'esc_url')>0)
					echo ' <em style="color:pink">You don\'t need esc_url if what you print is static</em>';
				else if(strpos($matches[1],'$')>0 && strpos($matches[1],'esc_url')===false)
					echo ' <em style="color:red">No esc_url. Its best practice to escape late.</em>';
				echo '<br />';
			}
			preg_match($doExpr,$line,$matches);
			if($matches)
				echo 'Line ',$line_number,' do_action: ',$matches[1],' <br />';
					
			preg_match($aEchoExpr,$line,$matches);
			if($matches){
				echo 'Line ',$line_number,' link <em>href</em>=',$matches[1],' <em>text</em> ',trim($matches[2]," .");
				if(strpos($matches[1],'$')===false && strpos($matches[1],'esc_url')>0)
					echo ' <em style="color:pink">You don\'t need esc_url if what you print is static</em>';
				else if(strpos($matches[1],'$')>0 && strpos($matches[1],'esc_url')===false)
					echo ' <em style="color:red">No esc_url. Its best practice to escape late.</em>';
				if(strpos($matches[2],'$')===false && strpos($matches[2],'esc_')>0)
					echo ' <em style="color:#AA00BB">You don\'t need esc_ if what you print is static</em>';
				else if(strpos($matches[2],'$')>0 && strpos($matches[2],'esc_html')===false)
					echo ' <em style="color:red">No esc_html. Its best practice to escape late.</em>';
				echo '<br />';
			}

			preg_match($hrefEchoExpr,$line,$matches);
			if($matches){
				echo 'Line ',$line_number,' href echoing ',$matches[1];
				if(strpos($matches[1],'$')===false && strpos($matches[1],'esc_url')>0)
					echo ' <em style="color:#AA00BB">You don\'t need esc_url if what you print is static</em>';
				else if(strpos($matches[1],'$')>0 && strpos($matches[1],'esc_url')===false)
					echo ' <em style="color:red">No esc_url. Its best practice to escape late.</em>';
				echo '<br />';
			}

			preg_match($echoDots,$line,$matches);
			if($matches){
				$temp=$matches[0];
				$temp=str_replace('echo ','',$temp);
				$len=strlen($temp)>100?100:strlen($temp);
				$temp=substr($temp,0,$len);
				echo 'Line ',$line_number,' echoing ',esc_html($temp);
				echo ' <em style="color:#AA00BB">Its faster to use \',\' when echoing multiple strings than combining them with \'.\'</em>';
				echo '<br />';
			}
			$funcCount++;			
			$previousLine=$line;
		}
		if(isset($_POST['functions']))
			if($funcCount>0 && $func)
				echo ' Function has ',$funcCount,' LoC <br />';		
			else
				echo ' File has ',$funcCount,' LoC <br />';		
			
		echo "</fieldset>";
	}
		?>
	<h3>Plugin globals</h3>
	<ul>
	<?php foreach($globals as $glb):?>
		<li><strong><?php echo $glb ?></strong></li>
	<?php endforeach;?>
	</ul>
	<h3>Uses wp globals</h3>
	<ul>
	<?php foreach($wpGlobals as $wpgl => $count):?>
		<?php if($count>0):?>
		<li><strong><?php echo $wpgl ?>:</strong> <?php echo $count?> times</li>
		<?php endif;?>
	<?php endforeach;?>
	</ul>
<?php endif;
?></div>
<?php

	function loadPHP($dir,&$files){
		$handle = opendir($dir);
		while(false !== ($resource = readdir($handle))) {
			if($resource!='.' && $resource!='..'){
				if(is_dir($dir.$resource))
				loadPHP($dir.$resource.'/',$files);
				else{
					$ext = substr(strrchr($resource, '.'), 1);
					if($ext=="php")
					$files[$resource]=$dir.$resource;
				}
			}
		}
		closedir($handle);
	}
	function loadGlobals(){
		return array('$admin_page_hooks'=>0,
'$ajax_results'=>0,
'$all_links'=>0,
'$allowedposttags'=>0,
'$allowedtags'=>0,
'$authordata'=>0,
'$bgcolor'=>0,
'$db_prefix'=>0,
'$cache_categories'=>0,
'$cache_lastcommentmodified'=>0,
'$cache_lastpostdate'=>0,
'$cache_lastpostmodified'=>0,
'$cache_userdata'=>0,
'$category_cache'=>0,
'$class'=>0,
'$comment'=>0,
'$comment_cache'=>0,
'$comment_count_cache'=>0,
'$commentdata'=>0,
'$current_user'=>0,
'$day'=>0,
'$debug'=>0,
'$descriptions'=>0,
'$error'=>0,
'$feeds'=>0,
'$id'=>0,
'$is_apache'=>0,
'$is_IIS'=>0,
'$is_macIE'=>0,
'$is_winIE'=>0,
'$l10n'=>0,
'$locale'=>0,
'$link'=>0,
'$m'=>0,
'$map'=>0,
'$max_num_pages'=>0,
'$menu'=>0,
'$mode'=>0,
'$month'=>0,
'$month_abbrev'=>0,
'$monthnum'=>0,
'$more'=>0,
'$multipage'=>0,
'$names'=>0,
'$newday'=>0,
'$numpages'=>0,
'$page'=>0,
'$page_cache'=>0,
'$paged'=>0,
'$pagenow'=>0,
'$pages'=>0,
'$parent_file'=>0,
'$preview'=>0,
'$previousday'=>0,
'$previousweekday'=>0,
'$plugin_page'=>0,
'$post'=>0,
'$post_cache'=>0,
'$post_default_category'=>0,
'$post_default_title'=>0,
'$post_meta_cache'=>0,
'$postc'=>0,
'$postdata'=>0,
'$posts'=>0,
'$posts_per_page'=>0,
'$previousday'=>0,
'$request'=>0,
'$result'=>0,
'$richedit'=>0,
'$single'=>0,
'$submenu'=>0,
'$table_prefix'=>0,
'$targets'=>0,
'$timedifference'=>0,
'$timestart'=>0,
'$timeend'=>0,
'$updated_timestamp'=>0,
'$urls'=>0,
'$user_ID'=>0,
'$user_email'=>0,
'$user_identity'=>0,
'$user_level'=>0,
'$user_login'=>0,
'$user_pass_md5'=>0,
'$user_url'=>0,
'$weekday'=>0,
'$weekday_abbrev'=>0,
'$weekday_initial'=>0,
'$withcomments'=>0,
'$wp'=>0,
'$wp_broken_themes'=>0,
'$wp_db_version'=>0,
'$wp_did_header'=>0,
'$wp_did_template_redirect'=>0,
'$wp_file_description'=>0,
'$wp_filter'=>0,
'$wp_importers'=>0,
'$wp_plugins'=>0,
'$wp_themes'=>0,
'$wp_object_cache'=>0,
'$wp_query'=>0,
'$wp_queries'=>0,
'$wp_rewrite'=>0,
'$wp_roles'=>0,
'$wp_similiesreplace'=>0,
'$wp_smiliessearch'=>0,
'$wp_version'=>0,
'$wpcommentspopupfile'=>0,
'$wpcommentsjavascript'=>0,
'$wpdb'=>0);
}
?>