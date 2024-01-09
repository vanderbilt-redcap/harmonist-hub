<?php
namespace Vanderbilt\HarmonistHubExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

#Color code & Region names
$regionCountryArray = array();
$colorCountryArray = array();
$totalAreasByRegionZoom = array();
$legend = array();
$totalLegend = array();
foreach ($regionstbl as $region){
    if($region['region_tbl_option'] == 0) {
        $regionCountryArray[$region['region_code']] = $region['region_legend'];
        $colorCountryArray[$region['region_code']] = $region['region_color'];

        $legend['color'] = $region['region_color'];
        $legend['title'] = $region['region_legend'];
        array_push($totalLegend, $legend);

        $totalAreasByRegionZoom[$region['region_code']]['latitude']  = $region['region_latitude'];
        $totalAreasByRegionZoom[$region['region_code']]['longitude'] = $region['region_longitude'];
        $totalAreasByRegionZoom[$region['region_code']]['zoom'] = $region['region_zoom'];
    }
}

#Convert to ISO3 to ISO2
$codes = json_decode(file_get_contents($module->getSafePath('map/countryCodeConverter/iso2.json')), true);
$names = json_decode(file_get_contents($module->getSafePath('map/countryCodeConverter/names.json')), true);
$iso3_to_name = array();
foreach($codes as $iso2 => $iso3) {
    $iso3_to_name[$iso3] = $names[$iso3];
}

#Get the data and tranform it to a json
$RecordSetTableTBLC = \REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null);
$totalLocations = array();
$regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = 1");
foreach ($regions as $region){
    $totalAreasByRegion[$region['region_code']] = array();
}

$totalAreas= array();
$locations = array();
$areas = array();
//$icon = "M9,0C4.029,0,0,4.029,0,9s4.029,9,9,9s9-4.029,9-9S13.971,0,9,0z M9,15.93 c-3.83,0-6.93-3.1-6.93-6.93S5.17,2.07,9,2.07s6.93,3.1,6.93,6.93S12.83,15.93,9,15.93 M12.5,9c0,1.933-1.567,3.5-3.5,3.5S5.5,10.933,5.5,9S7.067,5.5,9,5.5 S12.5,7.067,12.5,9z";
$icon = "M 200 175 A 25 25 0 0 0 182.322 217.678 M 200 175 A 25 25 0 1 0 217.678 217.678 M 200 175 A 25 25 0 0 1 217.678 217.678";
foreach( $RecordSetTableTBLC as $data ){
    if($totalAreasByRegion[$data['region']] != NULL && $data['geocode_lat'] != NULL && $data['geocode_lon'] != NULL && $data['geocode_lat'] != '' && $data['geocode_lon'] != '' && ($data['drop_center'] == '' || !in_array($data['drop_center'],$data))) {
        #MAP LOCATIONS
        $locations["latitude"] = $data['geocode_lat'];
        $locations["longitude"] = $data['geocode_lon'];
        $locations["svgPath"] = $icon;
        $locations["scale"] = "0.15";
        $locations["color"] = "#4d4d4d";
        $locations["rollOverColor"] = "#000000";
        $locations["backgroundColor"] = "#000000";
        $locations["rollOverScale"] = "1.5";
        $locations["description"] = $data['program'].'<br/>Clinic: '.$data['adultped'];
        $locations["title"] = $data['name'];
        $locations["zoomLevel"] = "5";
        $locations["alpha"] = "0.7";
        #MAP AREAS
        $areas["id"] = $codes[$data['country']];
        $areas["region"] = $data['region'];
        $areas["color"] = $colorCountryArray[$data['region']];
        $areas["title"] = $regionCountryArray[$data['region']].' - '.$iso3_to_name[$codes[$data['country']]];
        $areas["selectedColor"] = \Vanderbilt\HarmonistHubExternalModule\adjustColorLightenDarken($colorCountryArray[$data['region']],"30");
        $areas["rollOverColor"] = \Vanderbilt\HarmonistHubExternalModule\adjustColorLightenDarken($colorCountryArray[$data['region']],"30");

        array_push($totalLocations, $locations);
        array_push($totalAreas, $areas);
        array_push($totalAreasByRegion[$data['region']], $areas);
    }
}

$jsonLocationData = json_encode($totalLocations);
$jsonAreaData = json_encode($totalAreas);
$jsonTotalAreasByRegionData = json_encode($totalAreasByRegion);
$totalAreasByRegionZoom = json_encode($totalAreasByRegionZoom);
$jsonLegendData = json_encode($totalLegend);
?>

<!DOCTYPE html>
<html>
<head>
    <script>
        var jsonLocationData = <?=$module->escape($jsonLocationData)?>;
        var jsonAreaDataAll = <?=$module->escape($jsonAreaData)?>;
        var jsonAreaData = <?=$module->escape($jsonTotalAreasByRegionData)?>;
        var jsonLegendData = <?=$module->escape($jsonLegendData)?>;
        var totalAreasByRegionZoom = <?=$module->escape($totalAreasByRegionZoom)?>;
        function setDataset(index) {
            map.dataProvider.getAreasFromMap = true;
            if(index == ""){
                map.dataProvider.areas = jsonAreaDataAll;
                map.dataProvider.zoomLevel = '1';
                map.dataProvider.zoomLatitude = 0;
                map.dataProvider.zoomLongitude = 0;
            }else{
                map.dataProvider.areas = jsonAreaData[index];
                map.dataProvider.zoomLevel = totalAreasByRegionZoom[index]['zoom'];
                map.dataProvider.zoomLatitude = totalAreasByRegionZoom[index]['latitude'];
                map.dataProvider.zoomLongitude = totalAreasByRegionZoom[index]['longitude'];
            }
            map.validateData();
        }
    </script>

    <script type="text/javascript" src="<?=$module->getUrl('map/ammap.js');?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('map/worldLow.js');?>"></script>
    <link rel="stylesheet" href="<?=$module->getUrl('map/ammap.css');?>" type="text/css" media="all" />
    <script type="text/javascript" src="<?=$module->getUrl('map/map_regioncolor.js');?>"></script>

    <script src="<?=$module->getUrl('map/export/export.js');?>" type="text/javascript"></script>
    <script src="<?=$module->getUrl('/map/export/libs/fabric.js/fabric.min.js');?>" type="text/javascript"></script>
    <script src="<?=$module->getUrl('map/export/libs/FileSaver.js/FileSaver.min.js');?>" type="text/javascript"></script>
    <link href="<?=$module->getUrl('map/export/export.css');?>" rel="stylesheet" type="text/css" media="all" />
    <style>
        .amChartsLegend{
            bottom:-40px !important;
        }
    </style>
</head>
<body>
<div style="width: 800px; height: 300px;margin:auto;margin-bottom: 180px;">
    <div id="chartdiv" style="width: 800px; height: 400px;margin:auto;"></div>
</div>
</body>
</html>