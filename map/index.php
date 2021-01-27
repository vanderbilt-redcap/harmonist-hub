
<?php
#this avoids asking to log in in RedCap
define('NOAUTH',true);

require_once dirname(dirname(__FILE__))."/base.php";

#Color code & Region names
$colorCountryArray = array("AP"=>"#e74c3c","NA"=>"#95a5a6","CN"=>"#3498db","CA"=>"#1abc9c","EA"=>"#e67e22","SA"=>"#9b59b6","WA"=>"#f1c40f");
$regionCountryArray = array("AP"=>"Asia-Pacific","NA"=>"NA-ACCORD (North America)","CN"=>"CCASAnet (Latin America)","CA"=>"Central Africa","EA"=>"East Africa","SA"=>"Southern Africa","WA"=>"West Africa");

#Create the legend data
$totalLegend = array();
foreach ($regionCountryArray as $code=>$region){
    $legend['color'] = $colorCountryArray[$code];
    $legend['title'] = $region;
    array_push($totalLegend, $legend);
}

#Convert to ISO3 to ISO2
$codes = json_decode(file_get_contents('map/countryCodeConverter/iso2.json'), true);
$names = json_decode(file_get_contents('map/countryCodeConverter/names.json'), true);
$iso3_to_name = array();
foreach($codes as $iso2 => $iso3) {
    $iso3_to_name[$iso3] = $names[$iso3];
}

#Get the data and tranform it to a json
$projectIedeaTBLC = new \Plugin\Project(IEDEA_TBLCENTER);
$RecordSetTableTBLC= new \Plugin\RecordSet($projectIedeaTBLC,array(\Plugin\RecordSet::getKeyComparatorPair($projectIedeaTBLC->getFirstFieldName(),"!=") => ""));
$totalLocations = array();
$totalAreas = array();
$locations = array();
$areas = array();
#SVG PATH IMAGES
//$icon = "M16 0c-5.523 0-10 4.477-10 10 0 10 10 22 10 22s10-12 10-22c0-5.523-4.477-10-10-10zM16 16.125c-3.383 0-6.125-2.742-6.125-6.125s2.742-6.125 6.125-6.125 6.125 2.742 6.125 6.125-2.742 6.125-6.125 6.125zM12.125 10c0-2.14 1.735-3.875 3.875-3.875s3.875 1.735 3.875 3.875c0 2.14-1.735 3.875-3.875 3.875s-3.875-1.735-3.875-3.875z";
//$icon = "M9,0C4.029,0,0,4.029,0,9s4.029,9,9,9s9-4.029,9-9S13.971,0,9,0z M9,15.93 c-3.83,0-6.93-3.1-6.93-6.93S5.17,2.07,9,2.07s6.93,3.1,6.93,6.93S12.83,15.93,9,15.93 M12.5,9c0,1.933-1.567,3.5-3.5,3.5S5.5,10.933,5.5,9S7.067,5.5,9,5.5 S12.5,7.067,12.5,9z";
//circle
$icon = "M 200 175 A 25 25 0 0 0 182.322 217.678 M 200 175 A 25 25 0 1 0 217.678 217.678 M 200 175 A 25 25 0 0 1 217.678 217.678";
foreach( $RecordSetTableTBLC->getRecords() as $record ){
    $data = $record->getDetails();
    if($data['geocode_lat'] != NULL && $data['geocode_lon'] != NULL && $data['geocode_lat'] != '' && $data['geocode_lon'] != '' && ($data['drop_center'] == '' || !in_array($data['drop_center'],$data))) {
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
        $areas["color"] = $colorCountryArray[$data['region']];
        $areas["title"] = $regionCountryArray[$data['region']].' - '.$iso3_to_name[$codes[$data['country']]];
        $areas["selectedColor"] = \Vanderbilt\HarmonistHubExternalModule\adjustColorLightenDarken($colorCountryArray[$data['region']],"30");
        $areas["rollOverColor"] = \Vanderbilt\HarmonistHubExternalModule\adjustColorLightenDarken($colorCountryArray[$data['region']],"30");

        array_push($totalLocations, $locations);
        array_push($totalAreas, $areas);
    }
}
$jsonLocationData = json_encode($totalLocations);
$jsonAreaData = json_encode($totalAreas);
$jsonLegendData = json_encode($totalLegend);
?>

<!DOCTYPE html>
<html>
    <head>
        <script>
            var jsonLocationData = <?=$jsonLocationData?>;
            var jsonAreaData = <?=$jsonAreaData?>;
            var jsonLegendData = <?=$jsonLegendData?>;
        </script>

        <script type="text/javascript" src="map/ammap.js"></script>
        <script type="text/javascript" src="map/worldLow.js"></script>
        <link rel="stylesheet" href="map/ammap.css" type="text/css" media="all" />
        <script type="text/javascript" src="map/map_regioncolor.js"></script>

        <script src="map/export/export.js" type="text/javascript"></script>
        <link href="map/export/export.css" rel="stylesheet" type="text/css" media="all" />
    </head>
    <body>
    <div style="width: 800px; height: 500px;margin:auto;margin-top: 120px;">
        <div id="chartdiv" style="width: 800px; height: 700px;margin:auto;"></div>
    </div>
    </body>
</html>