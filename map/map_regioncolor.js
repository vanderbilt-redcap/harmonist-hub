/**
 * Add a handler that first runs before map is created
 */

AmCharts.addInitHandler( function ( map ) {
    /**
     * Iterate through all of the areas to collect group information
     */
    map.mapGroups = {};
    for ( var x in map.dataProvider.areas ) {
        var area = map.dataProvider.areas[ x ];
        if ( undefined !== area.groups && area.groups instanceof Array ) {
            // collect information about groups
            for ( var y in area.groups ) {
                var group = area.groups[ y ];
                if ( undefined === map.mapGroups[ group ] )
                    map.mapGroups[ group ] = [];
                map.mapGroups[ group ].push( area );
            }
        }
    }

    /**
     * Add rollover events
     */

    // add rollover event to the area
    // highlight related areas
    map.addListener( "rollOverMapObject", rollOverMapObject );
    map.addListener( "rollOutMapObject", rollOutMapObject );

    function rollOverMapObject ( event ) {
        handleHovers( event, "hover" );
    }

    function rollOutMapObject ( event ) {
        handleHovers( event, "blur" );
    }

    function handleHovers( event, type ) {

        // check if this area has related items defined
        var area = event.mapObject;
        if ( undefined === area.groups || ! area.groups instanceof Array )
            return;

        // simulate hovering on all related areas
        for ( var y in area.groups ) {
            var group = area.groups[ y ];
            for ( var x in map.mapGroups[ group ] ) {
                var relatedArea = map.mapGroups[ group ][ x ];
                if ( relatedArea.id === area.id )
                    continue;
                if ( "blur" === type ) {
                    relatedArea.showAsSelected = false;
                    map.returnInitialColor( relatedArea );
                }
                else {
                    relatedArea.showAsSelected = true;
                    map.returnInitialColor( relatedArea );
                }
            }
        }
    }

}, [ 'map'] );

var icon = "M16 0c-5.523 0-10 4.477-10 10 0 10 10 22 10 22s10-12 10-22c0-5.523-4.477-10-10-10zM16 16.125c-3.383 0-6.125-2.742-6.125-6.125s2.742-6.125 6.125-6.125 6.125 2.742 6.125 6.125-2.742 6.125-6.125 6.125zM12.125 10c0-2.14 1.735-3.875 3.875-3.875s3.875 1.735 3.875 3.875c0 2.14-1.735 3.875-3.875 3.875s-3.875-1.735-3.875-3.875z";
var map = AmCharts.makeChart("chartdiv", {

    type: "map",
    projection: "eckert3",
    theme: "none",
    allowClickOnSelectedObject: true,
    dataProvider: {
        map: "worldLow",
        images:  jsonLocationData ,
        areas: jsonAreaData
    },

    areasSettings: {
        rollOverOutlineColor: "#FFFFFF",
        rollOverColor: "#808080",
        color:"#d9d9d9",
        alpha:0.8,
        unlistedAreasAlpha:0.5,
        balloonText: "[[title]]",
        autoZoom: true
    },

    export: {
        enabled: true,
        libs: {
            "path": "map/export/libs/"
        },
        menu: [ {
            class: "export-main",
            menu: [ {
                label: "Download",
                menu: [
                    "PNG",
                    "JPG",
                    "PDF"
                ]
            }, {
                label: "Print",
                format: "PRINT"
            }]
        } ]
    },
    legend: {
        width: "98%",
        marginRight: 0,
        marginLeft: 0,
        equalWidths: false,
        backgroundAlpha: 0.5,
        backgroundColor: "#FFFFFF",
        borderColor: "#bfbfbf",
        borderAlpha: 1,
        bottom: 20,
        left: 7,
        fontSize:9,
        markerSize: 10,
        horizontalGap: 10,
        data: jsonLegendData
    }
});