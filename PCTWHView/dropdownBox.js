/* This script and many more are available free online at
The JavaScript Source :: http://www.javascriptsource.com
Created by: Christian Heilmann :: http://www.icant.co.uk/ */

function populate(o) {
  d=document.getElementById('comment3');
  if(!d){return;}                 
  var mitems=new Array();
  mitems['TBD']=[''];
  mitems['Material Shortage']=[];
  mitems['Group Order']=[];
  mitems['SAP Hold']=['选择小类','1SHP hold','GTS hold','ESSC hold','MOV hold'];
  mitems['Order Management Issue']=['选择小类','GI DATE'];
  mitems['Operation Issue']=['选择小类','人员操作管理异常','设备维护管理异常','超产能下单','周末下单，周一休息','产线发不上,仓库满仓','Schenker送货延迟','MM COA备料延迟','仓库订单打印延误','仓库备料延误','MM 订单遗漏','其他'];
  mitems['Engineering Issue']=['选择小类','Diag问题','BOM问题','WI/SPEC问题','OBA问题','Purge/Hold/ECO/Control build','来料品质问题','多次维修','维修缺料','SAP系统问题','SFNG系统问题','其他'];
  mitems['Special Downtime']=['选择小类','国定假期','异常天气','停电','其他'];
  mitems['Others']=[];
  d.options.length=0;
  cur=mitems[o.options[o.selectedIndex].value];
  if(!cur){return;}
  d.options.length=cur.length;
  for(var i=0;i<cur.length;i++) {
    d.options[i].text=cur[i];
    d.options[i].value=cur[i];
  }
}