<?php
/*
 Template Name: Home
 */
get_header();?>

    <script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.2/mapbox-gl-geocoder.min.js'></script>
    <link rel='stylesheet' href='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.4.2/mapbox-gl-geocoder.css' type='text/css' />
    <!-- Promise polyfill script required to use Mapbox GL Geocoder in IE 11 -->
    <script src="https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/es6-promise@4/dist/es6-promise.auto.min.js"></script>

    <main id="mainContent" class="p-0">
        <div class="row">
            <aside id="sidebar-menu" class="shadow-sm">
                <ul>
                    <li><a href="">Item</a></li>
                </ul>
            </aside>
        </div>
        <div class="container main-title">
            <div class="row">
                <div class="col-lg-9 m-auto">
                    <h1>La plateforme qui vous réconcilie avec le recyclage</h1>
                </div>
            </div>
        </div>
        <div id="map" class="container pull-up">
            <div class="row">
                <div class="col-lg-9 m-auto">
                    <div class="card-m-b-30 bg-light rounded-lg shadow-sm">
                        <div class="card-header border-0">
                            <div class="card-title">Localisation des bennes</div>

                        </div>
                        <div class="card-body">
                            <div id='map'></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>

            mapboxgl.accessToken = 'pk.eyJ1IjoibGlua2ViaW4iLCJhIjoiY2szbzcxNGIzMDVwdTNibXV6MWV6MXgxeiJ9.SU_Tmcud24UTRnCFDIJJVw';

            //Initialisation de la map

            var map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [-103.59179687498357, 40.66995747013945],
                zoom: 3
            });


            //Fonction pour récupérer le geojson

            function httpGet(theUrl)
            {
                var xmlHttp = new XMLHttpRequest();
                xmlHttp.open( "GET", theUrl, false ); // false for synchronous request
                xmlHttp.send( null );
                return xmlHttp.responseText;
            }

            //Intégration du geojson dans une variable
            var request = JSON.parse(httpGet("https://angotbaptiste.com/test.php"));



            map.on('load', function() {

                map.loadImage('poubelleverre.png', function(error, image) {
                    if (error) throw error;
                    map.addImage('poubelle', image);

// Add a new source from our GeoJSON data and set the
// 'cluster' option to true. GL-JS will add the point_count property to your source data.
                    map.addSource("BIN", {
                        type: "geojson",
                        data: request,
                        cluster: true,
                        clusterMaxZoom: 14, // Max zoom to cluster points on
                        clusterRadius: 50 // Radius of each cluster when clustering points (defaults to 50)
                    });




                    map.addControl(new mapboxgl.GeolocateControl({
                        positionOptions: {
                            enableHighAccuracy: true
                        },
                        trackUserLocation: true
                    }));
                    var geocoder = new MapboxGeocoder({
                        accessToken: mapboxgl.accessToken,
                        marker: {
                            color: 'orange'
                        },
                        mapboxgl: mapboxgl
                    });

                    map.addControl(geocoder);

                    map.addLayer({
                        id: "clusters",
                        type: "circle",
                        source: "BIN",
                        filter: ["has", "point_count"],
                        paint: {
// Use step expressions (https://docs.mapbox.com/mapbox-gl-js/style-spec/#expressions-step)
// with three steps to implement three types of circles:
//   * Blue, 20px circles when point count is less than 100
//   * Yellow, 30px circles when point count is between 100 and 750
//   * Pink, 40px circles when point count is greater than or equal to 750
                            "circle-color": [
                                "step",
                                ["get", "point_count"],
                                "#51bbd6",
                                100,
                                "#f1f075",
                                750,
                                "#f28cb1"
                            ],
                            "circle-radius": [
                                "step",
                                ["get", "point_count"],
                                20,
                                100,
                                30,
                                750,
                                40
                            ]
                        }
                    });

                    map.addLayer({
                        id: "cluster-count",
                        type: "symbol",
                        source: "BIN",
                        filter: ["has", "point_count"],
                        layout: {
                            "text-field": "{point_count_abbreviated}",
                            "text-font": ["DIN Offc Pro Medium", "Arial Unicode MS Bold"],
                            "text-size": 12
                        }
                    });

                    map.addLayer({
                        id: "unclustered-point",
                        type: "symbol",
                        source: "BIN",
                        filter: ["!", ["has", "point_count"]],
                        layout: {
                            "icon-image": "poubelle",
                            "icon-size": 0.05
                        }
                    });
                });

// inspect a cluster on click
                map.on('click', 'clusters', function (e) {
                    var features = map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
                    var clusterId = features[0].properties.cluster_id;
                    map.getSource('BIN').getClusterExpansionZoom(clusterId, function (err, zoom) {
                        if (err)
                            return;

                        map.easeTo({
                            center: features[0].geometry.coordinates,
                            zoom: zoom
                        });
                    });
                });

                map.on('click', 'unclustered-point', function (e) {
                    var coordinates = e.features[0].geometry.coordinates.slice();
                    var commune = e.features[0].properties.commune;
                    var adresse = e.features[0].properties.adresse;
                    var type = e.features[0].properties.dmt_type;
                    console.log(adresse);
                    console.log(commune);
                    console.log(type);

                    new mapboxgl.Popup()
                        .setLngLat(coordinates)
                        .setHTML(adresse+'<p><strong>'+commune+'</strong></p>'+'<p>'+type+'</p>')
                        .addTo(map)
                });

                map.on('mouseenter', 'clusters', function () {
                    map.getCanvas().style.cursor = 'pointer';
                });
                map.on('mouseleave', 'clusters', function () {
                    map.getCanvas().style.cursor = '';
                });
            });

            // code from the next step will go here!

        </script>
    </main>



<?php  get_footer()?>