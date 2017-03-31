<?php
define('EmpireCMSAdmin','1');
require("../../class/connect.php");
require("../../class/db_sql.php");
require("../../class/functions.php");
require("../../class/com_functions.php");
$link=db_connect();
$empire=new mysqlquery();
$editor=1;
//验证用户
$lur=is_login();
$logininid=$lur['userid'];
$loginin=$lur['username'];
$loginrnd=$lur['rnd'];
$loginlevel=$lur['groupid'];
$loginadminstyleid=$lur['adminstyleid'];
//验证权限
CheckLevel($logininid,$loginin,$classid,"feedback");
$id=(int)$_GET['id'];
$r=$empire->fetch1("select * from {$dbtbpre}enewsfeedback where id='$id' limit 1");
if(!$r[id])
{
	printerror('ErrorUrl','');
}
$bidr=ReturnAdminFeedbackClass($r['bid'],$logininid,$loginin);
//是否已读
if(empty($r['haveread']))
{
	$empire->query("update {$dbtbpre}enewsfeedback set haveread=1 where id='$id' limit 1");
}
$br=$empire->fetch1("select bname,enter,filef from {$dbtbpre}enewsfeedbackclass where bid='$r[bid]'");
$username="游客";
if($r['userid'])
{
	$username="<a href='../member/AddMember.php?enews=EditMember&userid=".$r['userid']."' target=_blank>".$r['username']."</a>";
}
$fpath=0;
$getfpath=0;
$record="<!--record-->";
$field="<!--field--->";
$er=explode($record,$br['enter']);
$count=count($er);
for($i=0;$i<$count-1;$i++)
{
	$er1=explode($field,$er[$i]);
	//附件
	if(strstr($br['filef'],",".$er1[1].","))
	{
		if($r[$er1[1]])
		{
			if(!$getfpath)
			{
				$filename=GetFilename($r[$er1[1]]);
				$filer=$empire->fetch1("select fpath from {$dbtbpre}enewsfile_other where modtype=4 and path='$r[filepath]' and filename='$filename' limit 1");
				$fpath=$filer[fpath];
				$getfpath=1;
			}
			$fspath=ReturnFileSavePath(0,$fpath);
			$fileurl=$fspath['fileurl'].$r[$er1[1]];
			$val="<b>附件：</b><a href='".$fileurl."' target=_blank>".$r[$er1[1]]."</a>";
		}
		else
		{
			$val="";
		}
	}
	else
	{
		$val=stripSlashes($r[$er1[1]]);
	}
	$feedbackinfo.="<tr><td height=25>".$er1[0].":</td><td style='text-align:left;'>".nl2br($val)."</td></tr>";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>查看反馈信息</title>
<link href="../adminstyle/<?=$loginadminstyleid?>/adminstyle.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="../adminstyle/1/yecha/yecha.css" />
<script>
  function senfe(o,a,b,c,d){
 var t=document.getElementById(o).getElementsByTagName("tr");
 for(var i=0;i<t.length;i++){
  t[i].style.backgroundColor=(t[i].sectionRowIndex%2==0)?a:b;
  t[i].onclick=function(){
   if(this.x!="1"){
    this.x="1";
    this.style.backgroundColor=d;
   }else{
    this.x="0";
    this.style.backgroundColor=(this.sectionRowIndex%2==0)?a:b;
   }
  }
  t[i].onmouseover=function(){
   if(this.x!="1")this.style.backgroundColor=c;
  }
  t[i].onmouseout=function(){
   if(this.x!="1")this.style.backgroundColor=(this.sectionRowIndex%2==0)?a:b;
  }
 }
}
</script>
<style>
.comm-table td{ padding:8px; line-height:16px; background:none;}
</style>
</head>

<body>
<div class="container">
<div class="nynav"><div>当前位置： <a href="../main.php">后台首页</a> > 查看反馈信息 > 所属分类：<?=$br[bname]?></div></div>
<div id="tab" style="margin-top:35px;">
<div class="ui-tab-bd">
<div class="ui-tab-content">
        	<h3><span>查看反馈信息</span></h3>
<div class="jqui anniuqun">
    <style>
        .reg-img {
            float:left;
            width:20px;
        }
    </style>
    <script src="/jquery/jquery-1.3.2.min.js"></script>
    <script>
        $(function(){
            var reg_array = $('#SNS').text().split(",");
            str = "";
            if(!reg_array) {
                str = '<img class="reg-img" src="/icon/fb2.jpeg">\
                <img class="reg-img" src="/icon/tw2.png">\
                <img class="reg-img" src="/icon/go2.png">\
                <img class="reg-img" src="/icon/ln2.png">';
            }
            else {
                if (reg_array[0] && reg_array[0] == "1")
                    str += '<img class="reg-img" src="/icon/fb.jpeg">';
                else
                    str += '<img class="reg-img" src="/icon/fb2.jpg">';
                if (reg_array[1] && reg_array[1] == "1")
                    str += '<img class="reg-img" src="/icon/tw.png">';
                else
                    str += '<img class="reg-img" src="/icon/tw2.png">';
                if (reg_array[2] && reg_array[2] == "1")
                    str += '<img class="reg-img" src="/icon/go.png">';
                else
                    str += '<img class="reg-img" src="/icon/go2.png">';
                if (reg_array[3] && reg_array[3] == "1")
                    str += '<img class="reg-img" src="/icon/ln.png">';
                else
                    str += '<img class="reg-img" src="/icon/ln2.png">';
            }
            $('#SNS').html(str);
        });
    </script>
<table class="comm-table" cellspacing="0" id="changecolor">
	<tbody>
      <tr bgcolor="#FFFFFF"> 
    <td>提交者:</td>
    <td style="text-align:left;"> 
      <?=$username?>
    </td>
  </tr>
  <tr bgcolor="#FFFFFF"> 
    <td>发布时间:</td>
    <td style="text-align:left;"> 
      <?=$r[saytime]?>
    </td>
  </tr>
  <tr bgcolor="#FFFFFF">
      <td>SNS:</td>
      <td style="text-align:left;" id="SNS"><?=$r[SNS]?></td>
  </tr>
  <tr bgcolor="#FFFFFF"> 
    <td>IP地址:</td>
    <td style="text-align:left;"> 
      <?=$r[ip]?>
    </td>
  </tr>
  <?=$feedbackinfo?>
  <tr bgcolor="#FFFFFF"> 
    <td colspan="2"><div align="center">[ <a href="javascript:window.close();">关 
        闭</a> ]</div></td>
  </tr>
	</tbody>
</table>
</div>
        </div>
    </div>
</div>
</div>
<script language="javascript">
senfe("changecolor","#F2F2F2","#F7F7F7","#ffffff","#ffffff");
</script>
</body>
</html>
<?php
db_close();
$empire=null;
?>