<?php
			set_time_limit(0);
			//set_magic_quotes_runtime(0);
			// ini_set("magic_quotes_runtime",0);
			define('FILEDIR_1', dirname(__FILE__).'/Moban/Pics/');
			define('FILEDIR_2', dirname(__FILE__).'/Moban/Head/');
			//session_start();
			function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT-8');
			define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
			header('Content-type: text/html; charset=utf-8');
			function stripslashes_deep($value) {
				$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
				return $value;
			}

			function strexists($string, $find) {
				return !(strpos($string, $find) === FALSE);
			}

			function file_ext($filename) {
				return strtolower(trim(substr(strrchr($filename, '.'), 1)));
			}

			function dheader($string, $replace = true, $http_response_code = 0) {
				$string = str_replace(array("\r", "\n"), array('', ''), $string);
				if(empty($http_response_code) || PHP_VERSION < '4.3' ) {
					header($string, $replace);
				} else {
					header($string, $replace, $http_response_code);
				}
				if(preg_match('/^\s*location:/is', $string)) {
					exit();
				}
			}

			// function GetRandStr($num) {
			// 	$chars = array(
			// 		"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
			// 		"l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
			// 		"w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
			// 		"H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
			// 		"S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
			// 		"3", "4", "5", "6", "7", "8", "9"
			// 	);
			// 	$charsLen = count($chars) - 1;
			// 	shuffle($chars);
			// 	$output = "";
			// 	for ($i=0; $i<$charsLen; $i++)
			// 	{
			// 		$output .= $chars[mt_rand(0, $charsLen)];
			// 	}
			// 	return $output;
			// }

			$action = isset($_GET['action']) ? trim($_GET['action']) : '';

			if ($action == 'listdata') {
				$kw = urlencode($_GET['q']);
				$sn = intval($_GET['sn']);
				if ($kw) {
					$url = 'http://image.so.com/j?q='.$kw.'&src=srp&sn='.$sn.'&pn=30';
					echo file_get_contents($url);
					exit;
				}
				exit;
			}

			if ($action =='down') {
				$downurl = isset($_POST['downurl']) ? trim($_POST['downurl']):'';
				$referer = isset($_POST['referer']) ? trim($_POST['referer']):'';
				$where = isset($_REQUEST['where']) ? trim($_REQUEST['where']):'';
				$downurl = explode("\n",str_replace("\r","",$downurl));
				$referer = explode("\n",str_replace("\r","",$referer));

				// echo $downurl;
				// exit();

				$urls = array();
				$exts = array();

				if ( $where == '1' ) {
					$where = FILEDIR_1;
				} else {
					$where = FILEDIR_2;
				}

				if (!file_exists($where)) {
					mkdir($where, 755, true);
				}

				$mh = curl_multi_init();

				$handle = array();

				foreach ($downurl as $i=>$url) {
					$ext = file_ext($url);

					if (!$url || !in_array($ext,array('jpg','gif','png','jpeg'))) {
						continue;
					}

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_REFERER,$referer[$i]);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_TIMEOUT, 9);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
					curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
					curl_multi_add_handle($mh, $ch);
					$handle[] = $ch;
					$exts[] = $ext;
					$urls[] = $url;
				}

				$active = null;
				do {
					$mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);

				while ($active and $mrc == CURLM_OK) {
					if (curl_multi_select($mh) != -1) {
						do {
							$mrc = curl_multi_exec($mh, $active);
						} while ($mrc == CURLM_CALL_MULTI_PERFORM);
					}
				}

				$error = array();

				# 获取抓取网页的图片内容
				foreach ($handle as $i => $ch) {
					$ext = $exts[$i];
					$data = curl_multi_getcontent($ch);
					curl_multi_remove_handle($mh, $ch);

					if ( $data ) {
						$filename = rand(100, 999).'.'.$ext;
						file_put_contents($where.$filename,$data);
					} else {
						$error[] = $urls[$i];
					}
				}

				curl_multi_close($mh);

				if ( !$error ) {
					echo 'true';
				} else {
					echo implode('<br />',$error);
				}
				die;
			}

?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
	<head>
		<title>图片采集功能</title>
		<meta name="generator" content="aliang"/>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
		<script type="text/javascript" src="pic.js"></script>
		<script type="text/javascript">
		var phpurl='index.php';
		var page = 0;
		var downnum = 0;
		var imgnum = 0;
		var total = 0;
		var listdata = {};
		var maxnum = 0;
		var thread = 0;
		var kw ='';
		var where = '1';
		var isRunning = false;
		function down_img(i) {
			var count = listdata.length;
			if(imgnum >=maxnum || !count || downnum > total) {
				$('#msg').html('下载图片完成操作，共下载图片'+imgnum+'张!');
				isRunning = false;
				return;
			}
			if(i>=count){
				page = page + 1;
				sn = 30 * page;
				$.getJSON(phpurl, {cust:'pic',action:'listdata', q: kw, 'where': where, src: "srp",sn:sn,pn:"30" },function(data){
					total = data.total;
					listdata = data.list;
					var count = listdata.length;
					if(!total || !count) {
						$('#msg').html('找不到相关标题的图片下载!');
					}else{
						down_img(0);
					}
				});
			} else {
				var _downurl = "";
				var _referer = "";
				for (var _downnum = 0; _downnum < thread && i < count && imgnum + _downnum < maxnum ; _downnum++,i++){
					// _downurl += listdata[i].img + "\r\n";
					// _referer += listdata[i].link + "\r\n";
					_downurl += listdata[i].thumb + "\r\n";
					_referer += "" + "\r\n";
				}
				$.post(phpurl+'?action=down&cust=pic',{downurl:_downurl, referer:_referer , 'where': where},function(data){
					data =$.trim(data);
					downnum += _downnum;
					if(data == 'true'){
						imgnum += _downnum;
						if(imgnum >= maxnum){
							$('#msg').html('下载图片完成操作，共下载图片'+imgnum+'张!');
							isRunning = false;
							return;
						}
						$('#msg').html('已经下载'+imgnum+'张图片! 自动下载下一张图片!');

					}else{
						$('#msg').html('当前图片下载不了，自动跳转下载下一张图片!');
					}
				   down_img(i);
				});
			}
		}
		$(document).ready(function(){
			$('#btn').click(function(){
				if (isRunning){
					alert('正在下载中，请不要重复执行!');
					return;
				}
				where = $('input:radio[name="where"]:checked').val();
				kw = $.trim($('#kw').val());
				thread = $.trim($('#thread').val());
				thread = parseInt(thread);
				maxnum = $.trim($('#num').val());
				maxnum = parseInt(maxnum);
				page = 0;
				downnum = 0;
				imgnum = 0;
				total = 0;
				listdata = {};
				if(kw && maxnum && maxnum > 0){
					$.getJSON(phpurl, {cust:'pic',action:'listdata', q: kw, 'where': where, src: "srp",sn:'0','pn':"30" },function(data){
						total = data.total;
						listdata = data.list;
						var count = listdata.length;
						if(!total || !count) {
							$('#msg').html('找不到相关的图片进行下载!');
						}else{
							isRunning = true
							down_img(0);
						}
					});
				}
			}
			)
		})
		</script>
	</head>
	<style type="text/css">
		body {
			font-size:12px;
			background-color:#CCC;
			font-family:"楷体",sans-serif;
		}
		table {
			width:960px;
			margin:0 auto;
			padding:10px;
			background-color:#FFF;
		}
	</style>
	<body>
	<div style="text-align:center;"><h2><font color="red">图片采集功能</font></h2></div>
	<table align="center">
		<td align="center">
		<P style="line-height:50px;">
			采集标题：
			<INPUT type="text" name='kw' id='kw' >
			&nbsp;&nbsp;&nbsp; 采集数量：<INPUT type="text" name='num' id='num' value="100">
			&nbsp;&nbsp;&nbsp; 采集线程：<INPUT type="text" name='thread' id='thread' value="1">	<br />
			<input name="where" id="where1" value="1" type="radio" checked="checked"><label for="where1"><font color="red">选择：采集图片</font></label>
			<input name="where" id="where2" value="2" type="radio"><label for="where2"><font color="red">选择：采集头像</font></label>
			&nbsp;&nbsp;&nbsp;<input type="button" value="确定采集" id="btn"/>
		</p>
		<b><p id="msg" style="color:red;font-size:16px"></p></b>
		</td>
	</table>
  </body>
</html>