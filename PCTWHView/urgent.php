<!DOCTYPE html>
<html>
	<head>
		<title>特殊订单录入</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	</head>
	
	<body>
			
	<form id="form" action="" method="post" onkeydown="if(event.keyCode==13){return false;}">
	
		<input name="special" id="special" type="text" class="text"/> <-- 录入特殊订单类型 （例如：紧急、 cancel、指定用料台达电源、单独备料、指定用料SG硬盘、rework、等）
		
		<br>
	
		<input onkeydown="enterSumbit()" id="selectsrcid" type="text" class="text"/> 
		
		<button class="default" id="enter" type="button" onclick="srcToDest('selectsrcid','selectdestid'); ClearFields();"> Enter PLO </button>
		
		<br>
		
		<select name="plo_arr[]" multiple="multiple" size="30" style="width=200px" id="selectdestid">   
    
		</select>

		<button id="delete" type="button" onclick="javascript:destToSrc('selectdestid')"> >> </button>
	
		<br/><br/> 请确认欲提交的 PLOs 处于高亮选中状态 （默认所有都选中）！ <br/><br/>	
		
	<button id="submit" type="submit" name="submit"> Submit </button>

	</form>

<script>
    window.onunload = refreshParent;
    function refreshParent() {
        window.opener.location.reload();
    }
</script>

<script>
$(function(){
    $('form').each(function () {
        var thisform = $(this);
        thisform.prepend(thisform.find('button.default').clone().css({
            position: 'absolute',
            left: '-999px',
            top: '-999px',
            height: 0,
            width: 0
        }));
    });
});

function enterSumbit(){  
     var event=arguments.callee.caller.arguments[0]||window.event;//消除浏览器差异  
    if (event.keyCode == 13){  
        srcToDest('selectsrcid','selectdestid');
		ClearFields();
     }  
}   

function ClearFields() {
     document.getElementById("selectsrcid").value = "";
}
	
function srcToDest(srcid,destid) {   
	var optionsObjects=document.getElementById(srcid);   
	var optionsSubObjects=document.getElementById(destid);    
	var optionsvalue=optionsObjects.value;  
	count = optionsSubObjects.length+1;
	var optionstext='#'+count+' - '+optionsObjects.value;   //count
	addoptions(destid,optionstext,optionsvalue)   
}           
         
      //向目标   
function addoptions(objectid,optionstext,optionsvalue) {   
	var optionsSubObjects=document.getElementById(objectid);   
	var hasexist=0;   
	for(var o=0;o<optionsSubObjects.length;o++) {   
		var optionsvalue_sub=optionsSubObjects.options[o].text;   
		optionsSubObjects.options[o].selected = true; // selected by default
		if(optionsvalue_sub==optionstext)   
			hasexist+=1;   
	}   
	if(hasexist==0) {   
		optionsSubObjects.add(new Option(optionstext, optionsvalue));   
		optionsSubObjects.options[o].selected = true; // selected by default
	}   
}   
  
  
//将对象中所选的项删除   
  
function destToSrc(objectid)   
{   
var optionsObjects=document.getElementById(objectid);   
  
for(var o=0;o<optionsObjects.length;o++)   
{   
if(optionsObjects.options[o].selected==true)   
 {   
 var optionsvalue=optionsObjects.options[o].value;   
 var optionstext=optionsObjects.options[o].text;   
      removeoption(objectid,optionstext,optionsvalue)   
 }   
}   
}   
  
//删除单个选项   
function removeoption(objectid,textvalue,optionsvalue)   
{   
var optionsSubObjects=document.getElementById(objectid);   
for(var o=0;o<optionsSubObjects.length;o++)   
{   
 var optionsvalue_sub=optionsSubObjects.options[o].text;   
 if(optionsvalue_sub==textvalue)   
  optionsSubObjects.options.remove(o);    
}   
}   
</script>

<?php
    
	ini_set('mssql.charset', 'UTF-8');
	
	echo "<meta charset=\"UTF-8\">";
	
	require_once('connectvars.php'); 
	
//---------------------------------------------------------------------------------------------------------------------------------------------------

	if (isset($_POST['plo_arr']) && isset($_POST['special'])) {
		$query = "
			IF OBJECT_ID('dbo.Urgent', 'U') IS NULL
					BEGIN
						CREATE TABLE Urgent
						(
						PLO NVARCHAR(20) NOT NULL,
						CreateTime SMALLDATETIME NULL,
						Remark NVARCHAR(200) NULL						
						);
						CREATE INDEX UrgentIndex
						ON Urgent (PLO);
					END
		";
		
		$n = 0;
		
		$strArr = array();
		
		foreach ($_POST['plo_arr'] as $v) {
			if (!empty($v)) {
				$PLO = trim($v);
				array_push($strArr, " IF NOT EXISTS (SELECT PLO FROM Urgent WHERE PLO = '{$PLO}') INSERT INTO Urgent (PLO, CreateTime, Remark) VALUES ('{$PLO}', GETDATE(), '{$_POST['special']}')"); 
				$n++;
			}
		}
		
		$query .= implode(' ', $strArr);
		
		// Connect to 112 DB
		$dbc = mssql_connect(DB_HOST_112, DB_USER_112, DB_PASSWORD_112) or die("connect db error");	
		mssql_select_db(DB_NAME_112,$dbc) or die('can not open db table');

		mssql_query($query,$dbc) or die('search db error ');

		mssql_close($dbc);
		
		echo $n, ' PLOs submitted.<br />';
		
		// echo "<script>window.close();</script>";
	}
	
?>

</body>
</html>