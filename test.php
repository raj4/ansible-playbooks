<?php
include($_SERVER['DOCUMENT_ROOT'].'/include/connect.php');
include($_SERVER['DOCUMENT_ROOT'].'/include/functions.php');
include($_SERVER['DOCUMENT_ROOT'].'/ebooth/include/generaldata.php');
$secureUrlCheck();

$tokenArg = $eventrow['token'] ? '&t=' . $eventrow['token'] : '';

// The main screen
if (!$_REQUEST['action']) {

include($_SERVER['DOCUMENT_ROOT'].'/ebooth/include/header.php');
echo $eventrow['delegate_header'];
?>

<table width="100%" height="100%" cellspacing="0" cellpadding="2">
<tr>
<td height="100%">

<iframe id="showfloorplan" name="showfloorplan" height="500" width="100%" src="delegate.php?action=showfloorplan&eventid=<?=$_REQUEST['eventid'].$tokenArg?>" frameborder="0" scrolling="no" style="border:1px solid #EEEEEE;"></iframe>

</td>
</tr>
</table>

<?
echo $eventrow['delegate_footer'];
include($_SERVER['DOCUMENT_ROOT'].'/ebooth/include/footer.php');
} else {
$boothmouseovers=unserialize($eventrow['boothmouseovers']);
$adjustedtime=getadjustedtime($eventrow['timezoneid']);
$pricepoints=unserialize($eventrow['pricepoints']);
?>
<html>
<head>
<script language="javascript" type="text/javascript" src="/include/scripts/scripts.js"></script>
<script language="javascript" type="text/javascript" src="/include/scripts/prototype.js"></script>
<script src="/include/scripts/scriptaculous.js?load=effects,dragdrop" type="text/javascript"></script>
<script language="javascript" type="text/javascript" src="/include/scripts/boothpublic.js"></script>
<style type="text/css">
body,td { font-family:arial;font-size:12px; }
.dropdownmenu { background:#FFFFFF; border-color:#CCCCCC #676767 #676767 #CCCCCC; border-style:solid; border-width:1px; cursor:pointer; position:absolute; z-index:1000; }
.divider { border-top:1px solid #CCCCCC; }
.menuitem { background:#EEEEEE; padding-bottom:2px; padding-left:8px; padding-top:2px;width:144px; }
.inactive { color:#AAAAAA; }
.selectedmenuitem { background:#D3E3FE; }
<?
$browser=browserVersion::detect();
if ($browser['name']=='mozilla') {
    echo '.grab_cursor { cursor:-moz-grab; }'."\n";
    echo '.grabbed_cursor { cursor:-moz-grabbing; }'."\n";
} else {
    echo '.grab_cursor { cursor:url(\'/images/openhand.cur\'); }'."\n";
    echo '.grabbed_cursor { '.($browser['name']!='safari'?'cursor:url(\'/images/closedhand.cur\');':'').' }'."\n";
}

echo '.booth { position:absolute; cursor:pointer; border:1px solid #000; -moz-user-select:none; }'."\n";
echo '.highlighted { border:1px solid red; }'."\n";

// Generate styles at all zoom levels for our booth types
$typeresult=$pdoRead->quickFetchAll("SELECT typeid,bgcolor,bgcolor2,height,width,categoryid,feeid FROM boothtypedata WHERE eventid=? AND deleted=0",
array($_REQUEST['eventid'])
);
foreach ($typeresult as $typerow) {
    for ($i=1;$i<9;$i++) {
        $height = boothHelper::formatWidthHeightForBooth( $typerow['height'] );
        $width = boothHelper::formatWidthHeightForBooth( $typerow['width'] );
        echo '.type_'.$typerow['typeid'].'_'.$i.' { background-color: '.$typerow['bgcolor'].'; height:'.(($height*$i)-($browser['name']=='msie'?0:2)).'px;width:'.(($width*$i)-($browser['name']=='msie'?0:2)).'px; }'."\n";
        echo '.type_'.$typerow['typeid'].'_r_'.$i.' { background-color: '.$typerow['bgcolor2'].'; height:'.(($height*$i)-($browser['name']=='msie'?0:2)).'px;width:'.(($width*$i)-($browser['name']=='msie'?0:2)).'px; }'."\n";
        echo '.type_'.$typerow['typeid'].'_i_'.$i.' { width:100%;height:'.(($height*$i)-($browser['name']=='msie'?1:2)).'px;overflow:hidden; }'."\n";
    }
    $boothtypes.='boothtypes['.$typerow['typeid'].']=new Array('.$width.','.$height.');'."\n";
}
?>
</style>

<script type="text/javascript">
var eventwidth=<?=($eventrow['width']*8)?>;
var eventheight=<?=($eventrow['height']*8)?>;
var ie=<?=($browser['name']=='msie'?1:0)?>;
var boothtypes=new Array();
<?=$boothtypes?>
function booth_select(e) { return false; }
<?
$boothPopupInfo=new boothPopupInfo($boothmouseovers['delegate'],$eventrow['language'],$eventrow['standardcurrency'],$pdoRead,$pdoWrite);
$boothresult=$pdoRead->quickFetchAll("SELECT b.*,t.name typename,".$boothPopupInfo->regQueryFields($eventrow['ereg_name_field'],$eventrow['ereg_description_field'], $eventrow['ereg_logo_field'])." FROM boothboothdata b LEFT JOIN view_regattendeedata_join_regattendee_additional_syscols r ON b.attendeeid=r.attendeeid,boothtypedata t WHERE b.typeid=t.typeid AND b.eventid=? AND b.deleted=0",
array($_REQUEST['eventid'])
);
echo 'function add_booths_to_dom() {';
echo 'var fragment=document.createDocumentFragment();'."\n";
foreach ($boothresult as $boothrow) {
    echo 'setup_initial_booth(fragment,\''.$boothrow['boothid'].'\',\''.$boothrow['typeid'].'\',\''.$boothrow['leftpos'].'\',\''.$boothrow['toppos'].'\',\''.$boothPopupInfo->getBoothLabel($boothrow,$eventrow['ereg_name_field']).'\',\''.$boothPopupInfo->getMouseover($boothrow,$eventrow['ereg_name_field'],$eventrow['ereg_description_field'],$eventrow['ereg_logo_field']).'\',\''.($boothrow['status']!='available'?1:0).'\');'."\n";
}
echo '$(\'floor_plan\').appendChild(fragment.cloneNode(true));';
echo '}';
?>
</script>
</head>
<body <?=(!$eventrow['floor_plan_image']?'style="background:url(\'/images/10x10_grid.gif\');"':'')?>>
<div id="spacer" style="position:absolute;top:0px;left:0px;width:<?=($eventrow['width']*8)+1600?>px;height:<?=($eventrow['height']*8)+1600?>px;">&nbsp;</div>
<div id="outline" style="position:absolute;top:800px;left:800px;width:<?=($eventrow['width']*8)?>px;height:<?=($eventrow['height']*8)?>px;border:2px dotted #000000;">
<?
if ($eventrow['floor_plan_image']) {
    $filehandler=new fileHandler('html_file_uploads/','file_uploads');
    $image=$filehandler->getFile($eventrow['floor_plan_image']);
    echo '<img id="bg_image" style="position:absolute;top:0px;left:0px;width:'.($eventrow['width']*8).'px;height:'.($eventrow['height']*8).'px;" src="/file_uploads/'.$image['filename'].'" />';
} else {
    echo '&nbsp;';
}
?>
<div id="floor_plan"></div>
</div>
<?
$controlstyle=($browser['name']=='msie' && preg_match('/^[78]\./',$browser['version']))?'position:absolute;top:expression(eval(document.compatMode && document.compatMode==\'CSS1Compat\')?documentElement.scrollTop+15:document.body.scrollTop+15);left:expression(eval(document.compatMode && document.compatMode==\'CSS1Compat\')?documentElement.scrollLeft+(documentElement.clientWidth-this.clientWidth):document.body.scrollLeft+(document.body.clientWidth-this.clientWidth));':
    'position:fixed;top:15px;right:15px;';
echo '<div id="controls" style="'.$controlstyle.'width:54px;height:263px;z-index:50;text-align:center;-moz-user-select:none;display:none;">';
echo '<img src="/images/booth_pan_control.png" border="0" usemap="#map1" style="margin-bottom:6px;" />';
echo '<img src="/images/booth_zoom_in.png" border="0" onclick="if (currentzoom<8) { centerpos=get_center_pos(); zoom(currentzoom+1,centerpos[0],centerpos[1]); }" alt="Zoom in" title="Zoom in" style="cursor:pointer;" />';
echo '<div id="slider" style="height:160px;width:16px;margin:auto;background:url(\'/images/booth_slider_bg.png\') top center;cursor:pointer;">';
echo '<img id="slider_handle" src="/images/booth_slider.png" alt="Drag to zoom in/out" />';
echo '</div>';
echo '<img src="/images/booth_zoom_out.png" style="cursor:pointer;" border="0" onclick="if (currentzoom>1) { centerpos=get_center_pos(); zoom(currentzoom-1,centerpos[0],centerpos[1]) }" alt="Zoom out" title="Zoom out" />';
echo '</div>';
?>
<map name="map1">
<area onclick="pan('up');" alt="Pan up" title="Pan up" shape="rect" coords="17,0,34,17" style="cursor:pointer;" />
<area onclick="pan('left');" alt="Pan left" title="Pan left" shape="rect" coords="0,17,17,34" style="cursor:pointer;" />
<area onclick="pan('down');" alt="Pan down" title="Pan down" shape="rect" coords="17,32,34,49" style="cursor:pointer;" />
<area onclick="pan('right');" alt="Pan right" title="Pan right" shape="rect" coords="34,17,51,34" style="cursor:pointer;" />
<area onclick="" alt="Zoom out" title="Zoom out" shape="rect" coords="17,152,35,171" style="cursor:pointer;" />
</map>

<form method="post" id="floorplan">
<input type="hidden" name="action" value="submit" />
<input type="hidden" name="csrf" value="<?=csrf()?>" />
<input type="hidden" name="eventid" value="<?=$_REQUEST['eventid']?>" />
<input type="hidden" name="t" value="<?=$eventrow['token']?>" />
</form>

</body>
</html>

<? } ?>
