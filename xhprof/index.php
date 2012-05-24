<?php
	
$GLOBALS['XHPROF_LIB_ROOT'] = dirname(__FILE__) . '/xhprof_lib';
require_once($GLOBALS['XHPROF_LIB_ROOT'].'/display/xhprof.php');
require_once(dirname(__FILE__).'/config.php');



if(!isset($_GET['run']) && !isset($_GET['run1'])){
	
	echo "<html>";
	echo "<head><title>XHProf: Hierarchical Profiler Report</title>";
	xhprof_include_js_css();
	?>
	</head>
	<body>
	<h4 style="text-align:center">List of profiler files</h4>
	<input id="b_cd" type="button" value="Check difference between 2 files" disabled="disabled" onclick="checkdiff()" />
	<table id="tbl" style="border-top:1px solid #ccc">
	<?
	$files=array();
	foreach(glob($dir.'/*') as $index=>$file){
		$buf=array(
			'stat'=>stat($file),
			'pi'=>pathinfo($file),
			'file'=>str_replace($dir.'/','',$file),
		);
		$files[]=$buf;
	}
	function sortbyftime($a,$b)
	{
		return $a['stat']['mtime']<$b['stat']['mtime'];
	}
	usort($files,'sortbyftime');
	
		
	foreach($files as $index=>$file){
		?>
		<tr>
			<td><input type="checkbox" onclick="setTimeout('checkbutton()',20)" name="<?=urlencode($file['file'])?>"></td>
			<td><?=($index+1)?>.</td>
			<td><a href="?run=<?=$file['pi']['filename']?>&source=<?=$file['pi']['extension']?>"><?=$file['file']?>.</a></td>
			<td><?=toBytes($file['stat']['size'])?>.</td>
			<td><?=toTimePcs(microtime(1)-$file['stat']['mtime'])?> ago</td>
		<?
		
	
		?>
		</tr>
		<?
	}
	
	?>
	</table>
	<script type="text/javascript">
	function checkbutton()
	{
		var trs=document.getElementById('tbl').getElementsByTagName('TR');
		var cb,cnt=0,b_cd=document.getElementById('b_cd');
		for(var i=0;i<trs.length;i++){
			cb=trs[i].getElementsByTagName('input')[0];
			if(cb && cb.checked){
				cnt++;
			}
		}
		if(cnt<2){
			b_cd.disabled=true;
			for(var i=0;i<trs.length;i++){
				cb=trs[i].getElementsByTagName('input')[0];
				if(cb && cb.checked){
					cb.disabled=false;
				}
			}
		}else if(cnt==2){
			b_cd.disabled=false;
		}else{
			b_cd.disabled=true;
			for(var i=0;i<trs.length;i++){
				cb=trs[i].getElementsByTagName('input')[0];
				if(cb && !cb.checked){
					cb.disabled=true;
				}
			}
		}
	}
	
	function checkdiff()
	{
		var trs=document.getElementById('tbl').getElementsByTagName('TR');
		var cb,names=[];
		for(var i=0;i<trs.length;i++){
			cb=trs[i].getElementsByTagName('input')[0];
			if(cb && cb.checked){
				names.push(cb.name);
			}
		}
		if(names.length==2){
			var buf=names[0].split('.');
			var ext1=buf.pop();
			names[0]=buf.join('.');
			var buf=names[1].split('.');
			var ext2=buf.pop();
			names[1]=buf.join('.');
			if(ext1==ext2){
				var url='?source='+ext1+'&run1='+names[0]+'&run2='+names[1];
				window.location.href=url;
			}else{
				alert('Namespaces are different');
			}
		}
	}
	</script>
	<?
	echo "</body>";
	echo "</html>";
	exit();
}
function toTimePcs($s,$getmin=1,$usekey='float')
{
	$os=$s=intval($s);
	$l=array('seconds','minutes','hours','days');
	$fl=array(1,60,60*60,60*60*24);
	$tl=$l[$round_to];
	$r=array('float'=>array(),'int'=>array());
	for($i=sizeof($l)-1;$i>=0;$i--){
		$r['int'][$l[$i]]=floor($s/$fl[$i]);
		$s-=$r['int'][$l[$i]]*$fl[$i];
	}
	for($i=sizeof($fl)-1;$i>=0;$i--){
		if(($os/$fl[$i])>=1){
			$r['float'][$l[$i]]=$os/$fl[$i];
		}
	}
	$rnd=(reset($r[$usekey])/10)>=1?0:(reset($r[$usekey])<3?2:1);
	return $getmin?round(reset($r[$usekey]),$rnd).' '.reset(array_keys($r[$usekey])):$r;
}
function toBytes($v)
{
	$v=intval($v);
	$e=array(' bytes','KB','MB','GB','TB');
	$level=0;
	while ($level<sizeof($e)&&$v>=1024)
	{
		$v=$v/1024;
		$level++;
	}
	return ($level>0?round($v,2):$v).$e[$level];
}








// param name, its type, and default value
$params = array('run'        => array(XHPROF_STRING_PARAM, ''),
                'wts'        => array(XHPROF_STRING_PARAM, ''),
                'symbol'     => array(XHPROF_STRING_PARAM, ''),
                'sort'       => array(XHPROF_STRING_PARAM, 'wt'), // wall time
                'run1'       => array(XHPROF_STRING_PARAM, ''),
                'run2'       => array(XHPROF_STRING_PARAM, ''),
                'source'     => array(XHPROF_STRING_PARAM, 'xhprof'),
                'all'        => array(XHPROF_UINT_PARAM, 0),
                );

// pull values of these params, and create named globals for each param
xhprof_param_init($params);

/* reset params to be a array of variable names to values
   by the end of this page, param should only contain values that need
   to be preserved for the next page. unset all unwanted keys in $params.
 */
foreach ($params as $k => $v) {
  $params[$k] = $$k;

  // unset key from params that are using default values. So URLs aren't
  // ridiculously long.
  if ($params[$k] == $v[1]) {
    unset($params[$k]);
  }
}

echo "<html>";

echo "<head><title>XHProf: Hierarchical Profiler Report</title>";
xhprof_include_js_css();
echo "</head>";

echo "<body>";

?>
	<div style="text-align:center"><a href="?">List of profiler files</a></div>
<?


$vbar  = ' class="vbar"';
$vwbar = ' class="vwbar"';
$vwlbar = ' class="vwlbar"';
$vbbar = ' class="vbbar"';
$vrbar = ' class="vrbar"';
$vgbar = ' class="vgbar"';

$xhprof_runs_impl = new XHProfRuns_Default($dir);

displayXHProfReport($xhprof_runs_impl, $params, $source, $run, $wts,
                    $symbol, $sort, $run1, $run2);


echo "</body>";
echo "</html>";
